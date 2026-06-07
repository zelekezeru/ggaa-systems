<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Google Sheets workbook ID (the long token in the sheet URL) used
            // as the raw-data entry surface for this client's monthly ledger.
            $table->string('google_sheet_id')->nullable()->after('tin_number');

            // Last time we successfully pulled values from the sheet.
            $table->timestamp('sheet_synced_at')->nullable()->after('google_sheet_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['google_sheet_id', 'sheet_synced_at']);
        });
    }
};
