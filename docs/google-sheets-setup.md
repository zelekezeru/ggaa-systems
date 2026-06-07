# Google Sheets ledger integration

Clients' monthly figures are entered in a **Google Sheet (one workbook per client)**.
The app reads the raw values server-side via a Google **service account**, maps them
into `MonthlyLedger` rows, and the existing PHP accessors compute totals, profit and
tax. Exports, the client portal and invoicing keep working unchanged.

```
App writes template ──► Client workbook ──► "Sync from Sheet" ──► MonthlyLedger rows
 (header + 13 months)    (staff fill figures)  (service account read)  (PHP computes P&L/tax)
```

The app can **write the entry template directly into the linked sheet** (header row +
all 13 Ethiopian months), so there's no manual column setup. Data entry stays in the
sheet; the app only *reads* the figures back. The single write path is template setup.

## 1. One-time Google Cloud setup

1. **Create a project** at <https://console.cloud.google.com> (e.g. `ggaa-ledger`).
2. **APIs & Services → Library →** enable **Google Sheets API**.
3. **APIs & Services → Credentials → Create credentials → Service account**
   (e.g. `ggaa-sheets`). No project IAM role is needed — access is granted
   per-sheet by sharing. The integration uses the read/write Sheets scope
   (`auth/spreadsheets`): reads pull figures, writes only apply the template.
4. Open the service account → **Keys → Add key → Create new key → JSON**. Download it.
   Note the service-account email, e.g.
   `ggaa-sheets-reader@ggaa-ledger.iam.gserviceaccount.com`.

## 2. Deploy the key + env

* Put the JSON key **outside the web root**, e.g. `/home/cpaneluser/secrets/ggaa-sheets.json`
  (never inside `public/`, never committed).
* In `.env`:

  ```env
  GOOGLE_SERVICE_ACCOUNT_JSON=/home/cpaneluser/secrets/ggaa-sheets.json
  GOOGLE_SHEETS_TAB=Ledger
  GOOGLE_SHEETS_READ_RANGE="Ledger!A1:BZ500"
  ```

* `php artisan config:cache` after changing env on production.

## 3. Per-client setup (automatic)

1. In Google Drive, create a **blank** Google Sheet for the client (any name).
2. **Share** it with the **service-account email** as **Editor**. Editor (not just
   Viewer) is required so the app can write the template and the embedded sheet stays
   usable; reads work with this same access.
3. Share it with the **assigned staff** (their Google accounts) as **Editor** so they can
   enter data, including in the embedded sheet in-app.
4. In the app → client's ledger page → **Google Sheet** panel → paste the sheet URL or ID
   → **Link Sheet**. The app **auto-applies the entry template** (header + 13 months) if
   the `Ledger` tab is empty. To re-apply later, use **Apply Template** (this overwrites
   the `Ledger` tab, so only do it on an empty/scratch sheet).

> Prefer to set it up by hand? **Download Template** still gives the exact `.xlsx`; import
> it to Drive and link it — the auto-apply step will detect existing data and skip.

## 4. Sheet layout (the `Ledger` tab)

Row 1 is a **header row**; every following row is one Ethiopian month. Columns can be in
any order — they're matched by header name (case-insensitive, punctuation-insensitive).
Minimum required columns: **Month** and **Year**.

| Month   | Year | Cash Machine Sales | Manual Sales | Beginning Inventory | Purchases | Ending Inventory | Salary | Pension | ... | Sales VAT | Custom: Insurance |
|---------|------|--------------------|--------------|---------------------|-----------|------------------|--------|---------|-----|-----------|-------------------|
| Meskeram| 2017 | 120000             | 30000        | 50000               | 80000     | 40000            | 25000  | 3000    | ... | 18000     | 1500              |
| Tiqimt  | 2017 | ...                | ...          | ...                 | ...       | ...              | ...    | ...     | ... | ...       | ...               |

* **Month** must be an Ethiopian month name (`Meskeram`, `Tiqimt`, … `Pagume`).
* **Year** is the Ethiopian year (e.g. `2017`).
* Any header beginning with `Custom:` becomes a custom expense line (`{label, amount}`).
* Recognised headers and human aliases are defined in [`config/ledger_sheet.php`](../config/ledger_sheet.php) —
  add more aliases there if accountants use different labels.

## 5. Syncing, stages & access

* On the client's ledger page, **Sync from Sheet** pulls every data row in.
* Once a sheet is linked the in-app ledger fields switch to **report mode** — read-only,
  showing the synced values. Data is entered in the sheet; the app only fetches it. The
  Save/Submit/Verify workflow buttons stay active to advance the stage.
* **Stage control:** new periods are created as **draft**; existing **draft/submitted**
  rows are updated on sync; **verified** rows are never overwritten (a manager must
  re-open them). Verification stays a manual action.
* **Access control:** only users with `enter ledger data` who can see the client (their
  assigned clients / branch) can open the page, link, sync, or download the template —
  enforced by route permissions + the client access scope.
* The result toast reports `added / updated / skipped` and the first few row-level issues
  (unknown month, bad year, etc.).

## Notes / limits

* The embedded iframe is editable only for a user signed into a Google account with edit
  access to that workbook; otherwise it shows read-only. Either way, **Sync** reads via the
  service account.
* **Ledger figures are read-only to the app** — it never writes computed values or staff
  data back to the sheet. The *only* write is applying the entry template (header + month
  rows), done on link or via **Apply Template**.
* Auth uses a lightweight service-account JWT (`firebase/php-jwt`) — no heavy Google SDK.
