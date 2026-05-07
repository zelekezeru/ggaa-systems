<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import {
    DocumentTextIcon, DocumentArrowDownIcon, CurrencyDollarIcon,
    ClockIcon, CheckCircleIcon, ExclamationTriangleIcon, XMarkIcon, PaperClipIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    invoices: Array,
    kpis: Object,
});

const fmt = (v) => isNaN(v) ? '—' : new Intl.NumberFormat('en-ET', { minimumFractionDigits: 2 }).format(v);

const filter = ref('all'); // all | open | paid

const filtered = computed(() => {
    if (filter.value === 'open')  return props.invoices.filter(i => ['sent', 'partially_paid', 'overdue'].includes(i.status));
    if (filter.value === 'paid')  return props.invoices.filter(i => i.status === 'paid');
    return props.invoices;
});

function statusClass(s) {
    return {
        paid:           'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        sent:           'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        partially_paid: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        overdue:        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        draft:          'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        cancelled:      'bg-slate-100 text-slate-500 line-through',
    }[s] || 'bg-slate-100 text-slate-700';
}

// Pay an invoice (open modal, attach receipt, submit)
const showPayModal = ref(false);
const selected = ref(null);
const payForm = useForm({
    amount: '',
    payment_method: 'bank_transfer',
    reference: '',
    paid_at: new Date().toISOString().slice(0, 10),
    notes: '',
    receipt: null,
});

function openPay(invoice) {
    selected.value = invoice;
    payForm.reset();
    payForm.amount = invoice.balance_due;
    showPayModal.value = true;
}

function submitPayment() {
    payForm.post(route('client.invoices.submit-payment', selected.value.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showPayModal.value = false;
            router.reload({ only: ['invoices', 'kpis'] });
        },
    });
}
</script>

<template>
    <Head title="Invoices" />
    <ClientLayout>
        <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2.5 rounded-xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                    <DocumentTextIcon class="h-6 w-6" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">My Invoices</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Service invoices issued by GGAA Systems</p>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total Billed</p>
                    <p class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ fmt(kpis.total_billed) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total Paid</p>
                    <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ fmt(kpis.total_paid) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Outstanding</p>
                    <p class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ fmt(kpis.outstanding) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Open Invoices</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ kpis.open_count }}</p>
                </div>
            </div>

            <!-- Filter pills -->
            <div class="flex gap-2 mb-3">
                <button v-for="f in [{k:'all',l:'All'},{k:'open',l:'Open'},{k:'paid',l:'Paid'}]" :key="f.k"
                    @click="filter = f.k"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
                    :class="filter === f.k ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700'">
                    {{ f.l }}
                </button>
            </div>

            <!-- Invoice table -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                                <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Invoice #</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Period</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold uppercase">Amount</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold uppercase">Paid</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold uppercase">Balance</th>
                                <th class="text-center px-4 py-3 text-xs font-semibold uppercase">Status</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="inv in filtered" :key="inv.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">{{ inv.invoice_number }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
                                    {{ new Date(inv.period_start).toISOString().slice(0,10) }} → {{ new Date(inv.period_end).toISOString().slice(0,10) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ fmt(inv.amount) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-emerald-600 dark:text-emerald-400">{{ fmt(inv.paid_amount) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-semibold" :class="inv.balance_due > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400'">{{ fmt(inv.balance_due) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold capitalize" :class="statusClass(inv.status)">
                                        {{ inv.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex gap-1">
                                        <a :href="route('client.invoices.download', inv.id)" target="_blank"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400">
                                            <DocumentArrowDownIcon class="h-3.5 w-3.5" /> PDF
                                        </a>
                                        <button v-if="inv.balance_due > 0 && inv.status !== 'cancelled'" @click="openPay(inv)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700">
                                            <CurrencyDollarIcon class="h-3.5 w-3.5" /> Pay
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filtered.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-slate-400">No invoices found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pay modal -->
        <div v-if="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" @click.self="showPayModal = false">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6 shadow-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Submit Payment</h3>
                    <button @click="showPayModal = false" class="text-slate-400 hover:text-slate-600">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                    Invoice <strong>{{ selected?.invoice_number }}</strong> · Balance due: <strong>{{ fmt(selected?.balance_due) }}</strong>
                </p>

                <form @submit.prevent="submitPayment" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Amount (ETB)</label>
                        <input v-model="payForm.amount" type="number" step="0.01" min="0" required
                            class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none" />
                        <p v-if="payForm.errors.amount" class="text-xs text-red-500 mt-1">{{ payForm.errors.amount }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Method</label>
                        <select v-model="payForm.payment_method" class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700">
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="card_payment">Card Payment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Reference / Transaction ID</label>
                        <input v-model="payForm.reference" type="text" maxlength="120"
                            class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Payment Date</label>
                        <input v-model="payForm.paid_at" type="date" required
                            class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1 flex items-center gap-1">
                            <PaperClipIcon class="h-3.5 w-3.5" /> Receipt (image or PDF)
                        </label>
                        <input type="file" accept="image/*,application/pdf" @change="payForm.receipt = $event.target.files[0]"
                            class="w-full text-xs text-slate-600 dark:text-slate-400" />
                        <p v-if="payForm.errors.receipt" class="text-xs text-red-500 mt-1">{{ payForm.errors.receipt }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Notes (optional)</label>
                        <textarea v-model="payForm.notes" rows="2" maxlength="500"
                            class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 resize-none"></textarea>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="button" @click="showPayModal = false"
                            class="flex-1 px-4 py-2 rounded-lg text-sm font-semibold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300">
                            Cancel
                        </button>
                        <button type="submit" :disabled="payForm.processing"
                            class="flex-1 px-4 py-2 rounded-lg text-sm font-bold bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50">
                            {{ payForm.processing ? 'Submitting...' : 'Submit Payment' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClientLayout>
</template>
