<?php

/*
|--------------------------------------------------------------------------
| Google Sheets → MonthlyLedger mapping
|--------------------------------------------------------------------------
|
| Each client workbook has a tab (default: "Ledger") whose FIRST ROW is a
| header row. Every following row is one Ethiopian month. We map sheet
| headers → MonthlyLedger columns by NAME (not by fixed column letter), so
| accountants can reorder/insert columns without breaking the sync.
|
| Header matching is case-insensitive and ignores spaces / punctuation:
| "Cash Machine Sales", "cash_machine_sales" and "CASH-MACHINE-SALES" all
| resolve to the `cash_machine_sales` field. Add extra human aliases below.
|
| Any header starting with "custom:" (e.g. "Custom: Insurance") is collected
| into the JSON `custom_expenses` array as {label, amount}.
|
*/

return [

    // The worksheet/tab name and A1 range to read from each workbook.
    'tab'   => env('GOOGLE_SHEETS_TAB', 'Ledger'),
    'range' => env('GOOGLE_SHEETS_READ_RANGE', 'Ledger!A1:BZ500'),

    // Prefix on a header that turns the column into a custom expense line.
    'custom_expense_prefix' => 'custom:',

    // Numeric fields that must be cast to a number (blank → 0).
    'numeric_fields' => [
        'eth_year',
        'cash_machine_sales', 'manual_sales',
        'cash_machine_start_number', 'cash_machine_end_number',
        'manual_receipt_start_number', 'manual_receipt_end_number',
        'beginning_inventory', 'purchases', 'ending_inventory',
        'inventory_items_start', 'inventory_items_end', 'inventory_sold_quantity',
        'salary_expense', 'pension_expense', 'printing_expense', 'shed_rent',
        'stationery_expense', 'office_rent_expense', 'transport_expense',
        'machine_fa_expense', 'eeu_expense', 'maintenance_expense',
        'advertising_expense', 'uniform_expense', 'indirect_materials_expense',
        'depreciation_expense', 'legal_fee_expense', 'bank_interest_expense',
        'bank_service_charge',
        'sales_vat', 'purchase_vat', 'withholding_tax', 'tax_rate',
    ],

    /*
    | Header alias → MonthlyLedger field.
    | Keys here are matched after normalisation (lowercase, non-alphanumerics
    | collapsed to "_"). Every real field key also maps to itself implicitly
    | in the sync service, so you only need entries for human-friendly names.
    */
    'aliases' => [
        // Period (required)
        'year'           => 'eth_year',
        'ethiopian_year' => 'eth_year',
        'month'          => 'eth_month',
        'ethiopian_month'=> 'eth_month',

        // Sales
        'cash_machine_sales' => 'cash_machine_sales',
        'machine_sales'      => 'cash_machine_sales',
        'manual_sales'       => 'manual_sales',
        'cash_start'         => 'cash_machine_start_number',
        'cash_end'           => 'cash_machine_end_number',
        'manual_start'       => 'manual_receipt_start_number',
        'manual_end'         => 'manual_receipt_end_number',

        // COGS
        'beginning_inventory' => 'beginning_inventory',
        'opening_inventory'   => 'beginning_inventory',
        'purchases'           => 'purchases',
        'ending_inventory'    => 'ending_inventory',
        'closing_inventory'   => 'ending_inventory',
        'units_start'         => 'inventory_items_start',
        'units_end'           => 'inventory_items_end',
        'units_sold'          => 'inventory_sold_quantity',

        // Expenses (human labels)
        'salary'            => 'salary_expense',
        'pension'           => 'pension_expense',
        'printing'          => 'printing_expense',
        'shed_rent'         => 'shed_rent',
        'stationery'        => 'stationery_expense',
        'office_rent'       => 'office_rent_expense',
        'transport'         => 'transport_expense',
        'machine_fa'        => 'machine_fa_expense',
        'eeu'               => 'eeu_expense',
        'electricity'       => 'eeu_expense',
        'maintenance'       => 'maintenance_expense',
        'advertising'       => 'advertising_expense',
        'uniform'           => 'uniform_expense',
        'indirect_materials'=> 'indirect_materials_expense',
        'depreciation'      => 'depreciation_expense',
        'legal_fee'         => 'legal_fee_expense',
        'bank_interest'     => 'bank_interest_expense',
        'bank_service_charge' => 'bank_service_charge',

        // VAT / tax
        'sales_vat'       => 'sales_vat',
        'purchase_vat'    => 'purchase_vat',
        'withholding_tax' => 'withholding_tax',
        'wht'             => 'withholding_tax',
        'tax_rate'        => 'tax_rate',

        // Notes
        'notes'   => 'notes',
        'remarks' => 'notes',
    ],

    // String/passthrough fields (not cast to number).
    'string_fields' => ['eth_month', 'notes'],

    // Fields the sync may write. Anything not here is ignored even if present
    // in the sheet, protecting workflow columns (status, verified_by, …).
    'writable_fields' => [
        'eth_year', 'eth_month',
        'cash_machine_sales', 'manual_sales',
        'cash_machine_start_number', 'cash_machine_end_number',
        'manual_receipt_start_number', 'manual_receipt_end_number',
        'beginning_inventory', 'purchases', 'ending_inventory',
        'inventory_items_start', 'inventory_items_end', 'inventory_sold_quantity',
        'salary_expense', 'pension_expense', 'printing_expense', 'shed_rent',
        'stationery_expense', 'office_rent_expense', 'transport_expense',
        'machine_fa_expense', 'eeu_expense', 'maintenance_expense',
        'advertising_expense', 'uniform_expense', 'indirect_materials_expense',
        'depreciation_expense', 'legal_fee_expense', 'bank_interest_expense',
        'bank_service_charge',
        'sales_vat', 'purchase_vat', 'withholding_tax',
        'tax_rate', 'notes', 'custom_expenses',
    ],
];
