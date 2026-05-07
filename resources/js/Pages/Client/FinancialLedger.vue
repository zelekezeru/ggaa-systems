<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import { BookOpenIcon, DocumentArrowDownIcon, TableCellsIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    ledgersByYear:   Object,   // { '2017': [ledgers], '2016': [...] }
    ethiopianMonths: Array,
});

const years = computed(() => Object.keys(props.ledgersByYear).sort((a, b) => b - a));
const selectedYear = ref(years.value[0] ?? null);

const yearLedgers = computed(() => {
    if (!selectedYear.value) return [];
    return props.ledgersByYear[selectedYear.value] ?? [];
});

const yearTotals = computed(() => {
    const ls = yearLedgers.value;
    return {
        sales:     ls.reduce((s, l) => s + Number(l.total_sales || 0), 0),
        gross:     ls.reduce((s, l) => s + Number(l.gross_profit || 0), 0),
        net:       ls.reduce((s, l) => s + Number(l.net_profit || 0), 0),
        tax:       ls.reduce((s, l) => s + Number(l.profit_tax || 0), 0),
        verified:  ls.length,
    };
});

const fmt = (v) => isNaN(v) ? '—' : new Intl.NumberFormat('en-ET', { minimumFractionDigits: 2 }).format(v);

function ledgerForMonth(month) {
    return yearLedgers.value.find(l => l.eth_month === month);
}
</script>

<template>
    <Head title="Financial Ledger" />
    <ClientLayout>
        <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2.5 rounded-xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                    <BookOpenIcon class="h-6 w-6" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Financial Ledger</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Verified monthly P&amp;L reports — download as PDF or Excel</p>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="years.length === 0" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-12 text-center">
                <BookOpenIcon class="h-12 w-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                <p class="text-sm text-slate-500 dark:text-slate-400">No verified ledger entries yet. Reports appear here once your accountant verifies them.</p>
            </div>

            <template v-else>
                <!-- Year tabs -->
                <div class="flex gap-2 mb-4 overflow-x-auto pb-1">
                    <button
                        v-for="y in years"
                        :key="y"
                        @click="selectedYear = y"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-colors flex-shrink-0"
                        :class="selectedYear === y
                            ? 'bg-blue-600 text-white shadow-sm'
                            : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'"
                    >
                        ETH {{ y }}
                    </button>
                </div>

                <!-- Year totals -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Months Filed</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ yearTotals.verified }} / 13</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total Sales</p>
                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ fmt(yearTotals.sales) }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Net Profit</p>
                        <p class="text-xl font-bold mt-1" :class="yearTotals.net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">{{ fmt(yearTotals.net) }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Profit Tax</p>
                        <p class="text-xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ fmt(yearTotals.tax) }}</p>
                    </div>
                </div>

                <!-- Monthly cards -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">Month</th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">Sales</th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">Gross Profit</th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">Net Profit</th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">Profit Tax</th>
                                    <th class="text-center px-4 py-3 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">Status</th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">Download</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                <tr v-for="month in ethiopianMonths" :key="month" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ month }}</td>
                                    <template v-if="ledgerForMonth(month)">
                                        <td class="px-4 py-3 text-right tabular-nums">{{ fmt(ledgerForMonth(month).total_sales) }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums" :class="ledgerForMonth(month).gross_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">{{ fmt(ledgerForMonth(month).gross_profit) }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums font-semibold" :class="ledgerForMonth(month).net_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">{{ fmt(ledgerForMonth(month).net_profit) }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums text-orange-600 dark:text-orange-400">{{ fmt(ledgerForMonth(month).profit_tax) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-xs font-semibold">
                                                <CheckBadgeIcon class="h-3.5 w-3.5" />
                                                Verified
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex gap-1">
                                                <a :href="route('ledger.download.pdf', ledgerForMonth(month).id)" target="_blank"
                                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 transition-colors">
                                                    <DocumentArrowDownIcon class="h-3.5 w-3.5" /> PDF
                                                </a>
                                                <a :href="route('ledger.download.xlsx', ledgerForMonth(month).id)" target="_blank"
                                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 transition-colors">
                                                    <TableCellsIcon class="h-3.5 w-3.5" /> XLSX
                                                </a>
                                            </div>
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td class="px-4 py-3 text-right text-slate-300 dark:text-slate-600">—</td>
                                        <td class="px-4 py-3 text-right text-slate-300 dark:text-slate-600">—</td>
                                        <td class="px-4 py-3 text-right text-slate-300 dark:text-slate-600">—</td>
                                        <td class="px-4 py-3 text-right text-slate-300 dark:text-slate-600">—</td>
                                        <td class="px-4 py-3 text-center text-xs text-slate-400 dark:text-slate-500">Pending</td>
                                        <td class="px-4 py-3 text-right text-slate-300 dark:text-slate-600">—</td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </ClientLayout>
</template>
