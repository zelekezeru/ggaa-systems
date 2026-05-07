<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { useRoleLayout } from '@/Composables/useRoleLayout';
import { 
    DocumentTextIcon, PlusIcon, FunnelIcon, 
    ArrowDownTrayIcon, EyeIcon, CheckCircleIcon,
    ExclamationCircleIcon, ClockIcon, XMarkIcon,
    MagnifyingGlassIcon, ChartBarIcon, BanknotesIcon
} from '@heroicons/vue/24/outline';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    invoices: Array,
    kpis: Object,
});

const { t } = useI18n();
const { currentLayout } = useRoleLayout();

const search = ref('');
const statusFilter = ref('all');

const filteredInvoices = computed(() => {
    return props.invoices.filter(i => {
        const matchesSearch = i.invoice_number.toLowerCase().includes(search.value.toLowerCase()) ||
                            i.client?.company_name.toLowerCase().includes(search.value.toLowerCase());
        const matchesStatus = statusFilter.value === 'all' || i.status === statusFilter.value;
        return matchesSearch && matchesStatus;
    });
});

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date));
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(val);
};

const getStatusClass = (status) => {
    switch (status) {
        case 'paid': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'sent': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'partially_paid': return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400';
        case 'overdue': return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
        case 'cancelled': return 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-400';
        default: return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    }
};
</script>

<template>
    <Head :title="t('invoices')" />

    <component :is="currentLayout">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ t('invoices') }}</h1>
                    <p class="text-gray-500 dark:text-slate-400 font-medium">{{ t('manage_client_billings') || 'Manage client billings and collections' }}</p>
                </div>
                <Link 
                    :href="route('finance.invoices.create')"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-blue-600/20 transition-all active:scale-95"
                >
                    <PlusIcon class="h-5 w-5" />
                    {{ t('create_invoice') || 'Create Invoice' }}
                </Link>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-gray-100 dark:border-slate-700 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-2xl">
                            <DocumentTextIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ t('total_billed') }}</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">ETB {{ formatCurrency(kpis.total_billed) }}</div>
                </div>

                <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-gray-100 dark:border-slate-700 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl">
                            <CheckCircleIcon class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                    <div class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ t('total_collected') }}</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">ETB {{ formatCurrency(kpis.total_collected) }}</div>
                </div>

                <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-gray-100 dark:border-slate-700 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded-2xl">
                            <ExclamationCircleIcon class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                        </div>
                    </div>
                    <div class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ t('outstanding') }}</div>
                    <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">ETB {{ formatCurrency(kpis.outstanding) }}</div>
                </div>

                <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-gray-100 dark:border-slate-700 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-2xl">
                            <ClockIcon class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                        </div>
                    </div>
                    <div class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ t('open_invoices') || 'Open Invoices' }}</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ kpis.count_open }}</div>
                </div>
            </div>

            <!-- Table & Filters -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl border border-gray-100 dark:border-slate-700 shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div class="relative w-full md:w-96">
                        <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input 
                            v-model="search"
                            type="text" 
                            :placeholder="t('search_invoices') || 'Search invoices...'"
                            class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-slate-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white"
                        />
                    </div>
                    
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <select 
                            v-model="statusFilter"
                            class="bg-gray-50 dark:bg-slate-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white px-4 py-3 pr-10 font-bold"
                        >
                            <option value="all">{{ t('all_statuses') || 'All Statuses' }}</option>
                            <option value="draft">{{ t('draft') }}</option>
                            <option value="sent">{{ t('sent') }}</option>
                            <option value="partially_paid">{{ t('partially_paid') }}</option>
                            <option value="paid">{{ t('paid') }}</option>
                            <option value="overdue">{{ t('overdue') }}</option>
                            <option value="cancelled">{{ t('cancelled') }}</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-[0.2em] bg-gray-50/50 dark:bg-slate-900/50">
                                <th class="px-6 py-4">{{ t('invoice_no') || 'Invoice #' }}</th>
                                <th class="px-6 py-4">{{ t('client') }}</th>
                                <th class="px-6 py-4">{{ t('due_date') }}</th>
                                <th class="px-6 py-4">{{ t('amount') }}</th>
                                <th class="px-6 py-4">{{ t('balance') || 'Balance' }}</th>
                                <th class="px-6 py-4 text-center">{{ t('status') }}</th>
                                <th class="px-6 py-4 text-right">{{ t('actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            <tr v-for="invoice in filteredInvoices" :key="invoice.id" class="hover:bg-gray-50/50 dark:hover:bg-slate-700/30 transition-colors group">
                                <td class="px-6 py-5">
                                    <div class="font-mono font-bold text-blue-600 dark:text-blue-400">{{ invoice.invoice_number }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ formatDate(invoice.created_at) }}</div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ invoice.client?.company_name }}</div>
                                    <div class="text-[10px] text-gray-400">TIN: {{ invoice.client?.tin_number || 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="text-sm font-medium text-gray-600 dark:text-slate-300">{{ formatDate(invoice.due_date) }}</div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="text-sm font-black text-gray-900 dark:text-white">ETB {{ formatCurrency(invoice.amount) }}</div>
                                </td>
                                <td class="px-6 py-5">
                                    <div :class="[invoice.balance_due > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400']" class="text-sm font-black">
                                        ETB {{ formatCurrency(invoice.balance_due) }}
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex justify-center">
                                        <span :class="getStatusClass(invoice.status)" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">
                                            {{ t(invoice.status) || invoice.status }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Link 
                                            :href="route('finance.invoices.show', invoice.id)"
                                            class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                            :title="t('view')"
                                        >
                                            <EyeIcon class="h-5 w-5" />
                                        </Link>
                                        <a 
                                            :href="route('finance.invoices.download', invoice.id)"
                                            class="p-2 text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                                            :title="t('download_pdf')"
                                        >
                                            <ArrowDownTrayIcon class="h-5 w-5" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredInvoices.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="p-4 bg-gray-50 dark:bg-slate-900 rounded-full mb-4">
                                            <DocumentTextIcon class="h-10 w-10 text-gray-300 dark:text-slate-700" />
                                        </div>
                                        <p class="text-gray-500 dark:text-slate-400 font-bold">{{ t('no_invoices_found') || 'No invoices found matching your search.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </component>
</template>
