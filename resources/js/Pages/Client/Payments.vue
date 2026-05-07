<script setup>
import { Head } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import { CurrencyDollarIcon, CheckCircleIcon, ClockIcon, XCircleIcon } from '@heroicons/vue/24/outline';

defineProps({
    payments: Array,
    kpis: Object,
});

const fmt = (v) => isNaN(v) ? '—' : new Intl.NumberFormat('en-ET', { minimumFractionDigits: 2 }).format(v);

function statusClass(s) {
    return {
        Completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        Pending:   'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        Rejected:  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        Scheduled: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    }[s] || 'bg-slate-100 text-slate-700';
}

function statusIcon(s) {
    return ({ Completed: CheckCircleIcon, Pending: ClockIcon, Rejected: XCircleIcon, Scheduled: ClockIcon }[s]) || ClockIcon;
}
</script>

<template>
    <Head title="Payment History" />
    <ClientLayout>
        <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2.5 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                    <CurrencyDollarIcon class="h-6 w-6" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Payment History</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">All payments you've submitted toward GGAA invoices</p>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total Billed</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ fmt(kpis.total_billed) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total Paid</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ fmt(kpis.total_paid) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Outstanding</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ fmt(kpis.outstanding) }}</p>
                </div>
            </div>

            <!-- Payments table -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                                <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Date</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Invoice</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Method</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Reference</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold uppercase">Amount</th>
                                <th class="text-center px-4 py-3 text-xs font-semibold uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="p in payments" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ new Date(p.paid_at).toISOString().slice(0,10) }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ p.invoice?.invoice_number }}</div>
                                    <div class="text-xs text-slate-400">{{ p.invoice?.period }}</div>
                                </td>
                                <td class="px-4 py-3 capitalize text-slate-600 dark:text-slate-400">{{ p.payment_method?.replace('_', ' ') }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 font-mono">{{ p.reference || '—' }}</td>
                                <td class="px-4 py-3 text-right font-bold tabular-nums">{{ fmt(p.amount) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusClass(p.status)">
                                        <component :is="statusIcon(p.status)" class="h-3.5 w-3.5" />
                                        {{ p.status || 'Completed' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="payments.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-slate-400">No payments recorded yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
