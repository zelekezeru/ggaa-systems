<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Google Sheets reader using a service-account JWT (no heavy SDK).
 *
 * Flow: build a signed JWT from the service-account key → exchange it for a
 * short-lived OAuth access token (cached ~55 min) → call the Sheets v4
 * values endpoint. Read-only scope.
 */
class GoogleSheetsClient
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';
    // Read + write: reads pull ledger values; writes only lay down the entry
    // template (header + month rows) into a linked workbook.
    private const SCOPE     = 'https://www.googleapis.com/auth/spreadsheets';
    private const CACHE_KEY = 'google_sheets_access_token_rw';

    /** @var array{client_email:string, private_key:string} */
    private array $credentials;

    public function __construct(?string $keyPath = null)
    {
        $keyPath ??= config('services.google.service_account_json');

        if (! $keyPath || ! is_file($keyPath)) {
            throw new RuntimeException(
                'Google service-account key not found. Set GOOGLE_SERVICE_ACCOUNT_JSON to the absolute path of the JSON key file.'
            );
        }

        $json = json_decode((string) file_get_contents($keyPath), true);
        if (! isset($json['client_email'], $json['private_key'])) {
            throw new RuntimeException('Invalid Google service-account key file: missing client_email/private_key.');
        }

        $this->credentials = $json;
    }

    /**
     * Read a worksheet range. Returns the raw 2-D array of rows exactly as
     * Google returns it (first row is typically the header row).
     *
     * @return array<int, array<int, string>>
     */
    public function readRange(string $spreadsheetId, string $range): array
    {
        $url = sprintf(
            'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s',
            rawurlencode($spreadsheetId),
            rawurlencode($range)
        );

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->get($url, [
                // Numbers come back as plain values, dates as serials — we only
                // need raw numeric/string cell contents here.
                'valueRenderOption'    => 'UNFORMATTED_VALUE',
                'dateTimeRenderOption' => 'FORMATTED_STRING',
            ]);

        if ($response->status() === 403) {
            throw new RuntimeException(
                "Access denied to spreadsheet. Share it with the service account ({$this->credentials['client_email']}) as Viewer."
            );
        }
        if ($response->status() === 404) {
            throw new RuntimeException('Spreadsheet or tab not found. Check the sheet ID and tab name.');
        }
        if ($response->failed()) {
            throw new RuntimeException('Google Sheets read failed: ' . $response->body());
        }

        return $response->json('values', []);
    }

    /** The service-account email — used in setup/sharing instructions. */
    public function serviceAccountEmail(): string
    {
        return $this->credentials['client_email'];
    }

    /**
     * Write the entry template (header + month rows) into a workbook tab,
     * creating the tab if missing. Existing values in the tab are cleared
     * first so the layout is deterministic.
     *
     * @param  array<int, array<int, string|int|float>>  $rows
     */
    public function applyTemplate(string $spreadsheetId, string $tab, array $rows): void
    {
        if (! in_array($tab, $this->tabTitles($spreadsheetId), true)) {
            $this->addTab($spreadsheetId, $tab);
        }

        $this->clearValues($spreadsheetId, "{$tab}!A1:ZZ1000");
        $this->writeValues($spreadsheetId, "{$tab}!A1", $rows);
    }

    /** True when the tab is absent or has no data rows (safe to auto-fill). */
    public function tabIsEmptyOrMissing(string $spreadsheetId, string $tab): bool
    {
        if (! in_array($tab, $this->tabTitles($spreadsheetId), true)) {
            return true;
        }

        return count($this->readRange($spreadsheetId, "{$tab}!A1:B3")) === 0;
    }

    /** @return array<int,string> worksheet/tab titles in the workbook */
    public function tabTitles(string $spreadsheetId): array
    {
        $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($spreadsheetId);

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->get($url, ['fields' => 'sheets(properties(title))']);

        $this->guard($response);

        return array_map(
            fn ($s) => $s['properties']['title'] ?? '',
            $response->json('sheets', [])
        );
    }

    private function addTab(string $spreadsheetId, string $tab): void
    {
        $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($spreadsheetId) . ':batchUpdate';

        $response = Http::withToken($this->accessToken())->acceptJson()->post($url, [
            'requests' => [
                ['addSheet' => ['properties' => ['title' => $tab]]],
            ],
        ]);

        $this->guard($response);
    }

    private function clearValues(string $spreadsheetId, string $range): void
    {
        $url = sprintf(
            'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s:clear',
            rawurlencode($spreadsheetId),
            rawurlencode($range)
        );

        // values:clear requires a JSON object body; send an explicit "{}"
        // (an empty/absent body yields "Root element must be a message").
        $this->guard(
            Http::withToken($this->accessToken())
                ->withBody('{}', 'application/json')
                ->post($url)
        );
    }

    /** @param array<int, array<int, string|int|float>> $values */
    private function writeValues(string $spreadsheetId, string $range, array $values): void
    {
        $url = sprintf(
            'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?valueInputOption=USER_ENTERED',
            rawurlencode($spreadsheetId),
            rawurlencode($range)
        );

        $this->guard(
            Http::withToken($this->accessToken())->acceptJson()->put($url, ['values' => $values])
        );
    }

    /** Translate Google API write failures into clear, actionable messages. */
    private function guard(\Illuminate\Http\Client\Response $response): void
    {
        if ($response->successful()) {
            return;
        }
        if ($response->status() === 403) {
            throw new RuntimeException(
                "The service account ({$this->credentials['client_email']}) needs Editor access to this sheet to apply the template. Share the workbook with it as Editor."
            );
        }
        if ($response->status() === 404) {
            throw new RuntimeException('Spreadsheet not found. Check the linked sheet ID.');
        }
        throw new RuntimeException('Google Sheets request failed: ' . $response->body());
    }

    /** Obtain (and cache) a short-lived OAuth access token. */
    private function accessToken(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(50), function () {
            $now = time();
            $jwt = JWT::encode([
                'iss'   => $this->credentials['client_email'],
                'scope' => self::SCOPE,
                'aud'   => self::TOKEN_URI,
                'iat'   => $now,
                'exp'   => $now + 3600,
            ], $this->credentials['private_key'], 'RS256');

            $response = Http::asForm()->post(self::TOKEN_URI, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if ($response->failed() || ! $response->json('access_token')) {
                throw new RuntimeException('Google token exchange failed: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }
}
