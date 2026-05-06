<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from 'vue-toastification';
import {
    BookOpenIcon, ChevronLeftIcon, CheckBadgeIcon,
    BanknotesIcon, ShoppingCartIcon, WrenchScrewdriverIcon,
    BuildingLibraryIcon, ReceiptPercentIcon, PlusIcon,
    LockClosedIcon, LockOpenIcon, ArrowPathIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const toast = useToast();

const props = defineProps({
    client: Object,
    ledgers: Object,         // keyed by "year_month"
    bankAccounts: Array,
    ethiopianMonths: Array,
    currentEthYear: Number,
    canVerify: Boolean,
});

// ── Month / Year Selection ──
const selectedYear  = ref(props.currentEthYear);
const selectedMonth = ref(props.ethiopianMonths[0]);
const isEditMode    = ref(false);

const isLocked = computed(() => existing.value?.status === 'verified' && !isEditMode.value);

const ledgerKey = computed(() => selectedYear.value + '_' + selectedMonth.value);
const existing  = computed(() => props.ledgers[ledgerKey.value] ?? null);

// ── Form ──
const emptyForm = () => ({
    eth_year:  selectedYear.value,
    eth_month: selectedMonth.value,
    status:    'draft',

    cash_machine_sales: 0,
    manual_sales:       0,

    beginning_inventory: 0,
    purchases:           0,
    ending_inventory:    0,

    salary_expense:             0,
    pension_expense:            0,
    printing_expense:           0,
    shed_rent:                  0,
    stationery_expense:         0,
    office_rent_expense:        0,
    transport_expense:          0,
    machine_fa_expense:         0,
    eeu_expense:                0,
    maintenance_expense:        0,
    advertising_expense:        0,
    uniform_expense:            0,
    indirect_materials_expense: 0,
    depreciation_expense:       0,
    legal_fee_expense:          0,
    bank_interest_expense:      0,
    bank_service_charge:        0,

    sales_vat:       0,
    purchase_vat:    0,
    withholding_tax: 0,

    notes:        '',
    bank_balances: {},
});

const form = useForm(emptyForm());

function loadEntry() {
    const e = existing.value;
    form.eth_year  = selectedYear.value;
    form.eth_month = selectedMonth.value;
    if (e) {
        Object.keys(form.data()).forEach(k => {
            if (k in e) {
                // If it's a numeric field (not eth_month, status, notes, bank_balances), default to 0
                if (['eth_year', 'eth_month', 'status', 'notes', 'bank_balances'].includes(k)) {
                    form[k] = e[k] ?? (k === 'notes' ? '' : e[k]);
                } else {
                    form[k] = e[k] ?? 0;
                }
            }
        });
        // Load bank balances from existing entry
        const balMap = {};
        (e.bank_account_balances ?? []).forEach(b => {
            balMap[b.bank_account_id] = {
                balance:           b.balance,
                loan_amount:       b.loan_amount,
                lc_margin_release: b.lc_margin_release,
                transfer_in:       b.transfer_in,
                transfer_reversal: b.transfer_reversal,
            };
        });
        form.bank_balances = balMap;
    } else {
        const reset = emptyForm();
        reset.eth_year  = selectedYear.value;
        reset.eth_month = selectedMonth.value;
        Object.assign(form, reset);
        
        // Initialize bank balances for all accounts to 0
        const balMap = {};
        props.bankAccounts.forEach(acc => {
            balMap[acc.id] = {
                balance: 0,
                loan_amount: 0,
                lc_margin_release: 0,
                transfer_in: 0,
                transfer_reversal: 0,
            };
        });
        form.bank_balances = balMap;
    }
}

watch([selectedYear, selectedMonth], loadEntry, { immediate: true });

// ── Real-time Computed Totals ──
const n = (v) => parseFloat(v) || 0;

const totalSales = computed(() => n(form.cash_machine_sales) + n(form.manual_sales));
const availableForSales = computed(() => n(form.beginning_inventory) + n(form.purchases));
const cogs = computed(() => Math.max(0, availableForSales.value - n(form.ending_inventory)));
const grossProfit = computed(() => totalSales.value - cogs.value);

const totalExpenses = computed(() =>
    n(form.salary_expense) + n(form.pension_expense) + n(form.printing_expense) +
    n(form.shed_rent) + n(form.stationery_expense) + n(form.office_rent_expense) +
    n(form.transport_expense) + n(form.machine_fa_expense) + n(form.eeu_expense) +
    n(form.maintenance_expense) + n(form.advertising_expense) + n(form.uniform_expense) +
    n(form.indirect_materials_expense) + n(form.depreciation_expense) +
    n(form.legal_fee_expense) + n(form.bank_interest_expense) + n(form.bank_service_charge)
);

const netProfit = computed(() => grossProfit.value - totalExpenses.value);

const profitTax = computed(() => {
    if (netProfit.value <= 0) return 0;
    return Math.max(0, (netProfit.value * 0.35) - 24600);
});

watch(existing, (newVal) => {
    if (newVal?.status !== 'verified') {
        isEditMode.value = false;
    }
});

const totalBankBalance = computed(() => {
    return Object.values(form.bank_balances).reduce((sum, b) => sum + n(b?.balance), 0);
});

function validate() {
    form.clearErrors();
    let hasError = false;

    const setError = (key, msg) => {
        form.setError(key, msg);
        hasError = true;
    };

    // 1. Check for negative values
    const numericFields = [
        'cash_machine_sales', 'manual_sales',
        'beginning_inventory', 'purchases', 'ending_inventory',
        'salary_expense', 'pension_expense', 'printing_expense',
        'shed_rent', 'stationery_expense', 'office_rent_expense',
        'transport_expense', 'machine_fa_expense', 'eeu_expense',
        'maintenance_expense', 'advertising_expense', 'uniform_expense',
        'indirect_materials_expense', 'depreciation_expense',
        'legal_fee_expense', 'bank_interest_expense', 'bank_service_charge',
        'sales_vat', 'purchase_vat', 'withholding_tax'
    ];

    numericFields.forEach(field => {
        if (form[field] !== '' && n(form[field]) < 0) {
            setError(field, 'Value cannot be negative');
        }
    });

    // 2. If submitting, ensure at least some income or expense is recorded
    if (form.status === 'submitted') {
        if (totalSales.value === 0 && totalExpenses.value === 0) {
            toast.error('Cannot submit an empty ledger. Please enter sales or expenses.');
            hasError = true;
        }
    }

    return !hasError;
}

// ── Submit ──
function save(submitStatus) {
    form.status    = submitStatus;
    form.eth_year  = selectedYear.value;
    form.eth_month = selectedMonth.value;

    if (!validate()) {
        toast.error('Please fix the errors before proceeding.');
        return;
    }

    const isNew = !existing.value;
    const url   = isNew
        ? route('ledger.store', props.client.id)
        : route('ledger.update', existing.value.id);

    const method = isNew ? 'post' : 'put';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(submitStatus === 'submitted' ? t('entry_submitted_success') : t('saved'));
            if (isEditMode.value) isEditMode.value = false;
        },
        onError: () => toast.error(t('fix_errors_msg')),
    });
}

function verify() {
    router.post(route('ledger.verify', existing.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success(t('verified')),
    });
}

function resetPortalPassword() {
    if (!confirm(t('are_you_sure_you_want_to_reset_password') || 'Are you sure you want to reset the portal password?')) return;
    router.post(route('admin.clients.reset-password', props.client.id), {}, {
        preserveScroll: true,
        onSuccess: () => {},
    });
}

// ── Bank balance helper ──
function bankBalanceFor(accountId) {
    if (!form.bank_balances[accountId]) {
        form.bank_balances[accountId] = { balance: '', loan_amount: '', lc_margin_release: '', transfer_in: '', transfer_reversal: '' };
    }
    return form.bank_balances[accountId];
}

// ── Month tab class helper ──
function monthTabClass(month) {
    const base = 'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all';
    if (selectedMonth.value === month) return base + ' bg-blue-600 text-white shadow-sm';
    const key = selectedYear.value + '_' + month;
    const entry = props.ledgers[key];
    if (!entry) return base + ' bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-600';
    if (entry.status === 'verified')  return base + ' bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    if (entry.status === 'submitted') return base + ' bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    return base + ' bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
}

// ── Formatting ──
const fmt = (v) => isNaN(v) ? '—' : new Intl.NumberFormat('en-ET', { minimumFractionDigits: 2 }).format(v);

const profitClass = computed(() => netProfit.value >= 0
    ? 'text-emerald-600 dark:text-emerald-400'
    : 'text-red-500 dark:text-red-400'
);

const statusBadge = computed(() => {
    const s = existing.value?.status;
    if (s === 'verified')  return { label: t('verified'),  cls: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' };
    if (s === 'submitted') return { label: t('submitted'), cls: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' };
    if (s === 'draft')     return { label: t('draft'),     cls: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' };
    return null;
});
</script>

<template>
    <Head :title="`Ledger — ${client.company_name}`" />
    <AdminLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">

            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <button @click="router.get(route('ledger.index'))" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                    <ChevronLeftIcon class="h-5 w-5 text-gray-500 dark:text-slate-400" />
                </button>
                <div class="flex-1">
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ client.company_name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-slate-200">{{ t('tin') }}: {{ client.tin_number }} &mdash; {{ t('monthly_financial_ledger') }}</p>
                </div>
                
                <!-- Edit Mode Toggle -->
                <div v-if="canVerify && existing?.status === 'verified'" class="flex items-center gap-2 mr-4">
                    <span class="text-xs font-medium text-gray-500 dark:text-slate-400">{{ isEditMode ? t('edit_mode') : t('unlock_to_edit') }}</span>
                    <button 
                        @click="isEditMode = !isEditMode"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none ring-2 ring-transparent focus:ring-blue-500"
                        :class="isEditMode ? 'bg-blue-600' : 'bg-gray-200 dark:bg-slate-700'"
                    >
                        <span
                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform flex items-center justify-center shadow-sm"
                            :class="isEditMode ? 'translate-x-6' : 'translate-x-1'"
                        >
                            <LockOpenIcon v-if="isEditMode" class="h-2.5 w-2.5 text-blue-600" />
                            <LockClosedIcon v-else class="h-2.5 w-2.5 text-gray-400" />
                        </span>
                    </button>
                </div>

                <span v-if="statusBadge" class="px-3 py-1 rounded-full text-xs font-semibold" :class="statusBadge.cls">
                    {{ statusBadge.label }}
                </span>
            </div>

            <!-- Month / Year Selector -->
            <div class="flex flex-wrap gap-2 mb-6 bg-white dark:bg-slate-800 p-3 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm">
                <select
                    v-model="selectedYear"
                    class="px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                >
                    <option v-for="y in [currentEthYear - 2, currentEthYear - 1, currentEthYear, currentEthYear + 1]" :key="y" :value="y">
                        {{ t('year') }} {{ y }}
                    </option>
                </select>
                <button
                    v-for="month in ethiopianMonths"
                    :key="month"
                    @click="selectedMonth = month"
                    :class="monthTabClass(month)"
                >
                    {{ t(month.toLowerCase()) }}
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- ── LEFT: Data Entry Form ── -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- SECTION: Sales Income -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-900/30">
                            <BanknotesIcon class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            <h2 class="text-sm font-semibold text-blue-900 dark:text-blue-300 uppercase tracking-wide">{{ t('sales_income') }}</h2>
                        </div>
                        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">{{ t('cash_machine_sales') }}</label>
                                <input v-model="form.cash_machine_sales" type="number" min="0" step="0.01" placeholder="0.00"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                                    :class="{'border-red-500': form.errors.cash_machine_sales, 'bg-gray-50 dark:bg-slate-800/50 cursor-not-allowed': isLocked}"
                                    :readonly="isLocked" />
                                <p v-if="form.errors.cash_machine_sales" class="mt-1 text-xs text-red-500">{{ form.errors.cash_machine_sales }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">{{ t('manual_sales') }}</label>
                                <input v-model="form.manual_sales" type="number" min="0" step="0.01" placeholder="0.00"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                                    :class="{'border-red-500': form.errors.manual_sales, 'bg-gray-50 dark:bg-slate-800/50 cursor-not-allowed': isLocked}"
                                    :readonly="isLocked" />
                                <p v-if="form.errors.manual_sales" class="mt-1 text-xs text-red-500">{{ form.errors.manual_sales }}</p>
                            </div>
                        </div>
                        <div class="px-5 pb-3 flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-slate-400">{{ t('total_sales') }}</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ fmt(totalSales) }} ETB</span>
                        </div>
                    </div>

                    <!-- SECTION: Cost of Goods Sold -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 bg-orange-50 dark:bg-orange-900/20 border-b border-orange-100 dark:border-orange-900/30">
                            <ShoppingCartIcon class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                            <h2 class="text-sm font-semibold text-orange-900 dark:text-orange-300 uppercase tracking-wide">{{ t('cost_of_goods_sold') }}</h2>
                        </div>
                        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">{{ t('beginning_inventory') }}</label>
                                <input v-model="form.beginning_inventory" type="number" min="0" step="0.01" placeholder="0.00"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                                    :class="{'border-red-500': form.errors.beginning_inventory, 'bg-gray-50 dark:bg-slate-800/50 cursor-not-allowed': isLocked}"
                                    :readonly="isLocked" />
                                <p v-if="form.errors.beginning_inventory" class="mt-1 text-xs text-red-500">{{ form.errors.beginning_inventory }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">{{ t('purchases') }}</label>
                                <input v-model="form.purchases" type="number" min="0" step="0.01" placeholder="0.00"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                                    :class="{'border-red-500': form.errors.purchases, 'bg-gray-50 dark:bg-slate-800/50 cursor-not-allowed': isLocked}"
                                    :readonly="isLocked" />
                                <p v-if="form.errors.purchases" class="mt-1 text-xs text-red-500">{{ form.errors.purchases }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">{{ t('ending_inventory') }}</label>
                                <input v-model="form.ending_inventory" type="number" min="0" step="0.01" placeholder="0.00"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                                    :class="{'border-red-500': form.errors.ending_inventory, 'bg-gray-50 dark:bg-slate-800/50 cursor-not-allowed': isLocked}"
                                    :readonly="isLocked" />
                                <p v-if="form.errors.ending_inventory" class="mt-1 text-xs text-red-500">{{ form.errors.ending_inventory }}</p>
                            </div>
                        </div>
                        <div class="px-5 pb-4 space-y-1.5 border-t border-gray-100 dark:border-slate-700 pt-3">
                            <div class="flex justify-between text-xs text-gray-500 dark:text-slate-400">
                                <span>{{ t('available_for_sales') }}</span>
                                <span class="font-medium text-gray-700 dark:text-slate-300">{{ fmt(availableForSales) }} ETB</span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 dark:text-slate-400">
                                <span>{{ t('cogs') }}</span>
                                <span class="font-medium text-gray-700 dark:text-slate-300">{{ fmt(cogs) }} ETB</span>
                            </div>
                            <div class="flex justify-between text-sm font-bold">
                                <span class="text-gray-700 dark:text-slate-300">{{ t('gross_profit') }}</span>
                                <span :class="grossProfit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">{{ fmt(grossProfit) }} ETB</span>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION: Administration Expenses -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 bg-purple-50 dark:bg-purple-900/20 border-b border-purple-100 dark:border-purple-900/30">
                            <WrenchScrewdriverIcon class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                            <h2 class="text-sm font-semibold text-purple-900 dark:text-purple-300 uppercase tracking-wide">{{ t('admin_expenses') }}</h2>
                        </div>
                        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template v-for="field in [
                                { key: 'salary_expense',             label: t('salary_expense') },
                                { key: 'pension_expense',            label: t('pension_expense') },
                                { key: 'eeu_expense',                label: t('eeu_expense') },
                                { key: 'maintenance_expense',        label: t('maintenance_expense') },
                                { key: 'machine_fa_expense',         label: t('machine_fa_expense') },
                                { key: 'office_rent_expense',        label: t('office_rent_expense') },
                                { key: 'shed_rent',                  label: t('shed_rent') },
                                { key: 'transport_expense',          label: t('transport_expense') },
                                { key: 'printing_expense',           label: t('printing_expense') },
                                { key: 'stationery_expense',         label: t('stationery_expense') },
                                { key: 'advertising_expense',        label: t('advertising_expense') },
                                { key: 'uniform_expense',            label: t('uniform_expense') },
                                { key: 'indirect_materials_expense', label: t('indirect_materials_expense') },
                                { key: 'depreciation_expense',       label: t('depreciation_expense') },
                                { key: 'legal_fee_expense',          label: t('legal_fee_expense') },
                                { key: 'bank_interest_expense',      label: t('bank_interest_expense') },
                                { key: 'bank_service_charge',        label: t('bank_service_charge') },
                            ]" :key="field.key">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">{{ field.label }}</label>
                                    <input v-model="form[field.key]" type="number" min="0" step="0.01" placeholder="0.00"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                                        :class="{'border-red-500': form.errors[field.key], 'bg-gray-50 dark:bg-slate-800/50 cursor-not-allowed': isLocked}"
                                        :readonly="isLocked" />
                                    <p v-if="form.errors[field.key]" class="mt-1 text-xs text-red-500">{{ form.errors[field.key] }}</p>
                                </div>
                            </template>
                        </div>
                        <div class="px-5 pb-3 flex justify-between items-center border-t border-gray-100 dark:border-slate-700 pt-3">
                            <span class="text-xs font-semibold text-gray-600 dark:text-slate-400">{{ t('total_expenses') }}</span>
                            <span class="text-sm font-bold text-red-500 dark:text-red-400">{{ fmt(totalExpenses) }} ETB</span>
                        </div>
                    </div>

                    <!-- SECTION: VAT Report -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 bg-teal-50 dark:bg-teal-900/20 border-b border-teal-100 dark:border-teal-900/30">
                            <ReceiptPercentIcon class="h-4 w-4 text-teal-600 dark:text-teal-400" />
                            <h2 class="text-sm font-semibold text-teal-900 dark:text-teal-300 uppercase tracking-wide">{{ t('vat_report') }}</h2>
                        </div>
                        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">{{ t('sales_vat') }}</label>
                                <input v-model="form.sales_vat" type="number" min="0" step="0.01" placeholder="0.00"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                                    :class="{'border-red-500': form.errors.sales_vat, 'bg-gray-50 dark:bg-slate-800/50 cursor-not-allowed': isLocked}"
                                    :readonly="isLocked" />
                                <p v-if="form.errors.sales_vat" class="mt-1 text-xs text-red-500">{{ form.errors.sales_vat }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">{{ t('purchase_vat') }}</label>
                                <input v-model="form.purchase_vat" type="number" min="0" step="0.01" placeholder="0.00"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                                    :class="{'border-red-500': form.errors.purchase_vat, 'bg-gray-50 dark:bg-slate-800/50 cursor-not-allowed': isLocked}"
                                    :readonly="isLocked" />
                                <p v-if="form.errors.purchase_vat" class="mt-1 text-xs text-red-500">{{ form.errors.purchase_vat }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">{{ t('withholding_tax_wht') }}</label>
                                <input v-model="form.withholding_tax" type="number" min="0" step="0.01" placeholder="0.00"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                                    :class="{'border-red-500': form.errors.withholding_tax, 'bg-gray-50 dark:bg-slate-800/50 cursor-not-allowed': isLocked}"
                                    :readonly="isLocked" />
                                <p v-if="form.errors.withholding_tax" class="mt-1 text-xs text-red-500">{{ form.errors.withholding_tax }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION: Bank Accounts -->
                    <div v-if="bankAccounts.length > 0" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-100 dark:border-indigo-900/30">
                            <BuildingLibraryIcon class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                            <h2 class="text-sm font-semibold text-indigo-900 dark:text-indigo-300 uppercase tracking-wide">{{ t('bank_account_balances') }}</h2>
                        </div>
                        <div class="p-5 space-y-4">
                            <div v-for="account in bankAccounts" :key="account.id" class="border border-gray-200 dark:border-slate-700 rounded-lg p-4">
                                <p class="text-xs font-semibold text-gray-700 dark:text-slate-300 mb-3">
                                    {{ account.bank_name }} — {{ account.account_number }}
                                    <span class="ml-2 px-2 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 text-[10px] uppercase">{{ account.account_type }}</span>
                                </p>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <div v-for="f in [
                                        { key: 'balance',           label: t('month_end_balance') },
                                        { key: 'loan_amount',       label: t('bank_loan') },
                                        { key: 'lc_margin_release', label: t('lc_margin_release') },
                                        { key: 'transfer_in',       label: t('transfer_in') },
                                        { key: 'transfer_reversal', label: t('transfer_reversal') },
                                    ]" :key="f.key">
                                        <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">{{ f.label }}</label>
                                        <input v-model="bankBalanceFor(account.id)[f.key]" type="number" min="0" step="0.01" placeholder="0.00"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                                            :class="{'bg-gray-50 dark:bg-slate-800/50 cursor-not-allowed': isLocked}"
                                            :readonly="isLocked" />
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center pt-2">
                                <span class="text-xs text-gray-500 dark:text-slate-400">{{ t('total_bank_credited_balance') }}</span>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ fmt(totalBankBalance) }} ETB</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm p-5">
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">{{ t('notes_remarks') }}</label>
                        <textarea v-model="form.notes" rows="3" :placeholder="t('notes_placeholder')"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none resize-none"
                            :class="{'bg-gray-50 dark:bg-slate-800/50 cursor-not-allowed': isLocked}"
                            :readonly="isLocked" />
                    </div>

                    <!-- Actions -->
                    <div v-if="existing?.status !== 'verified' || isEditMode" class="flex flex-wrap items-center gap-3 pb-8">
                        <button
                            type="button"
                            :disabled="form.processing"
                            @click="save('draft')"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50"
                        >
                            {{ form.processing ? t('saving') : t('save_draft') }}
                        </button>
                        <button
                            type="button"
                            :disabled="form.processing"
                            @click="save('submitted')"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-colors disabled:opacity-50 shadow-sm"
                        >
                            {{ form.processing ? t('sending') : t('submit_for_review') }}
                        </button>
                        <button
                            v-if="canVerify && existing?.status === 'submitted'"
                            type="button"
                            @click="verify"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-sm flex items-center gap-1.5"
                        >
                            <CheckBadgeIcon class="h-4 w-4" />
                            {{ t('verify_and_lock') }}
                        </button>
                    </div>

                    <!-- Verified Status Action -->
                    <div v-else-if="existing?.status === 'verified' && !isEditMode" class="flex items-center gap-3 pb-8">
                         <div class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-2">
                            <CheckBadgeIcon class="h-5 w-5" />
                            {{ t('entry_verified_locked') }}
                         </div>
                    </div>
                </div>

                <!-- ── RIGHT: Summary Panel ── -->
                <div class="space-y-4">
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm p-5 sticky top-6">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide mb-4">
                            {{ selectedMonth }} {{ selectedYear }} — {{ t('summary') }}
                        </h3>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500 dark:text-slate-400">{{ t('total_sales') }}</span>
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ fmt(totalSales) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500 dark:text-slate-400">{{ t('cogs') }}</span>
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ fmt(cogs) }}</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-gray-100 dark:border-slate-700 pt-3">
                                <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ t('gross_profit') }}</span>
                                <span class="text-sm font-bold" :class="grossProfit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">{{ fmt(grossProfit) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500 dark:text-slate-400">{{ t('total_expenses') }}</span>
                                <span class="text-sm font-semibold text-red-500 dark:text-red-400">{{ fmt(totalExpenses) }}</span>
                            </div>
                            <div class="flex justify-between items-center border-t-2 border-gray-200 dark:border-slate-600 pt-3">
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ t('net_profit') }}</span>
                                <span class="text-lg font-black" :class="profitClass">{{ fmt(netProfit) }}</span>
                            </div>
                            <div class="flex justify-between items-center bg-gray-50 dark:bg-slate-700/50 rounded-lg px-3 py-2">
                                <span class="text-xs text-gray-500 dark:text-slate-400">{{ t('profit_tax') }} (35%−24,600)</span>
                                <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">{{ fmt(profitTax) }}</span>
                            </div>
                            <div v-if="bankAccounts.length > 0" class="flex justify-between items-center">
                                <span class="text-xs text-gray-500 dark:text-slate-400">{{ t('total_bank_balance') }}</span>
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ fmt(totalBankBalance) }}</span>
                            </div>
                            <div v-if="bankAccounts.length > 0 && totalSales > 0" class="flex justify-between items-center bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2">
                                <span class="text-xs text-amber-700 dark:text-amber-400">{{ t('sales_vs_bank_gap') }}</span>
                                <span class="text-sm font-semibold" :class="Math.abs(totalSales - totalBankBalance) > 1 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'">
                                    {{ fmt(totalSales - totalBankBalance) }}
                                </span>
                            </div>
                        </div>

                        <!-- Entry metadata -->
                        <div v-if="existing" class="mt-5 pt-4 border-t border-gray-100 dark:border-slate-700 space-y-1.5 text-xs text-gray-400 dark:text-slate-500">
                            <p v-if="existing.submitted_by">{{ t('submitted_by') }}: <span class="text-gray-600 dark:text-slate-300">{{ existing.submitted_by?.name }}</span></p>
                            <p v-if="existing.verified_by">{{ t('verified_by') }}: <span class="text-gray-600 dark:text-slate-300">{{ existing.verified_by?.name }}</span></p>
                        </div>

                        <!-- Portal Access Info -->
                        <div class="mt-5 pt-4 border-t border-gray-100 dark:border-slate-700 space-y-3">
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ t('portal_access') }}</h4>
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500 dark:text-slate-400">{{ t('email') }}</span>
                                    <div class="flex items-center gap-1">
                                        <CheckBadgeIcon v-if="client.email_verified" class="h-3 w-3 text-emerald-500" title="Verified" />
                                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ client.email || '—' }}</span>
                                    </div>
                                </div>
                                <button 
                                    v-if="canVerify && client.email"
                                    @click="resetPortalPassword"
                                    class="w-full mt-2 px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-colors flex items-center justify-center gap-1.5"
                                >
                                    <ArrowPathIcon class="h-3 w-3" />
                                    {{ t('reset_portal_password') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
