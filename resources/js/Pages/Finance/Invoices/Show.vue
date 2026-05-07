<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { useRoleLayout } from '@/Composables/useRoleLayout';
import { 
    ChevronLeftIcon, ArrowDownTrayIcon, PaperAirplaneIcon,
    BanknotesIcon, CalendarIcon, UserIcon, PrinterIcon,
    ClockIcon, CheckCircleIcon, ExclamationCircleIcon,
    PlusIcon, DocumentTextIcon, XMarkIcon, TrashIcon
} from '@heroicons/vue/24/outline';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    invoice: Object,
});

const { t } = useI18n();
const { currentLayout } = useRoleLayout();

const showPaymentModal = ref(false);

const paymentForm = useForm({
    amount: props.invoice.balance_due,
    payment_method: 'bank_transfer',
    reference: '',
    paid_at: new Date().toISOString().substr(0, 10),
    notes: '',
});

const submitPayment = () => {
    paymentForm.post(route('finance.invoices.payments', props.invoice.id), {
        onSuccess: () => {
            showPaymentModal.value = false;
            paymentForm.reset();
        },
    });
};

const sendInvoice = () => {
    if (confirm(t('confirm_send_invoice') || 'Are you sure you want to send this invoice?')) {
        router.post(route('finance.invoices.send', props.invoice.id));
    }
};

const cancelInvoice = () => {
    if (confirm(t('confirm_cancel_invoice') || 'Are you sure you want to cancel this invoice?')) {
        router.post(route('finance.invoices.cancel', props.invoice.id));
    }
};

const deleteInvoice = () => {
    if (confirm(t('confirm_delete_invoice') || 'Are you sure you want to delete this invoice permanently?')) {
        router.delete(route('finance.invoices.destroy', props.invoice.id));
    }
};

const approvePayment = (payment) => {
    if (confirm(t('confirm_approve_payment') || 'Approve this payment?')) {
        router.post(route('finance.invoice-payments.approve', payment.id), {}, { preserveScroll: true });
    }
};

const rejectPayment = (payment) => {
    const reason = window.prompt(t('reject_reason_prompt') || 'Reason for rejection (optional):') ?? '';
    router.post(route('finance.invoice-payments.reject', payment.id), { reason }, { preserveScroll: true });
};

const paymentStatusColor = (status) => {
    switch (status) {
        case 'Completed': return 'text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10';
        case 'Pending':   return 'text-amber-500 bg-amber-50 dark:bg-amber-500/10';
        case 'Rejected':  return 'text-rose-500 bg-rose-50 dark:bg-rose-500/10';
        case 'Scheduled': return 'text-blue-500 bg-blue-50 dark:bg-blue-500/10';
        default:          return 'text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10';
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'paid': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
        case 'partially_paid': return 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400';
        case 'sent': return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400';
        case 'overdue': return 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400';
        case 'cancelled': return 'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-400';
        default: return 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-400';
    }
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date));
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'ETB' }).format(amount);
};

const pendingPaymentsCount = computed(() => (props.invoice.payments || []).filter(p => p.status === 'Pending').length);
</script>

<template>
    <Head :title="`${t('invoice')} ${invoice.invoice_number}`" />

    <component :is="currentLayout">
        <div class="max-w-6xl mx-auto pb-20">
            <!-- Breadcrumbs & Actions -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400">
                    <Link :href="route('finance.invoices.index')" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ t('invoices') }}</Link>
                    <span>/</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ invoice.invoice_number }}</span>
                </nav>

                <div class="flex items-center gap-3">
                    <button 
                        v-if="invoice.status === 'draft'"
                        @click="sendInvoice"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-600/20 transition-all flex items-center gap-2 text-sm"
                    >
                        <PaperAirplaneIcon class="h-4 w-4" />
                        {{ t('send_invoice') || 'Send Invoice' }}
                    </button>
                    
                    <a 
                        :href="route('finance.invoices.download', invoice.id)"
                        class="bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-900 dark:text-white px-6 py-2.5 rounded-xl font-bold border border-gray-100 dark:border-slate-700 shadow-sm transition-all flex items-center gap-2 text-sm"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        {{ t('download_pdf') || 'Download PDF' }}
                    </a>

                    <div class="relative group" v-if="['draft', 'sent', 'overdue'].includes(invoice.status)">
                        <button class="p-2.5 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <PlusIcon class="h-5 w-5 text-gray-500" />
                        </button>
                        <div class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-slate-700 py-2 hidden group-hover:block z-50 overflow-hidden">
                            <button 
                                v-if="invoice.status !== 'cancelled'"
                                @click="cancelInvoice"
                                class="w-full text-left px-4 py-2 text-sm font-bold text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/10 transition-colors"
                            >
                                {{ t('cancel_invoice') || 'Cancel Invoice' }}
                            </button>
                            <button 
                                v-if="invoice.status === 'draft'"
                                @click="deleteInvoice"
                                class="w-full text-left px-4 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/10 transition-colors"
                            >
                                {{ t('delete_invoice') || 'Delete Invoice' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Invoice View -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] border border-gray-100 dark:border-slate-700 shadow-xl overflow-hidden">
                        <!-- Invoice Header -->
                        <div class="p-8 md:p-12 border-b border-gray-50 dark:border-slate-700/50">
                            <div class="flex flex-col md:flex-row justify-between gap-8">
                                <div>
                                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-4" :class="getStatusColor(invoice.status)">
                                        <div class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></div>
                                        {{ t(invoice.status) }}
                                    </div>
                                    <h1 class="text-4xl font-black text-gray-900 dark:text-white uppercase tracking-tight mb-2">{{ invoice.invoice_number }}</h1>
                                    <p class="text-gray-500 dark:text-slate-400 font-medium">
                                        {{ t('issued_on') || 'Issued on' }} {{ formatDate(invoice.issued_at) }}
                                    </p>
                                </div>
                                <div class="text-left md:text-right">
                                    <div class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ t('billed_to') || 'Billed To' }}</div>
                                    <div class="text-xl font-black text-gray-900 dark:text-white">{{ invoice.client.company_name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-slate-400 font-medium mt-1">
                                        {{ t('tin') }}: {{ invoice.client.tin_number }}<br>
                                        {{ invoice.client.address || '' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Content -->
                        <div class="p-8 md:p-12">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12">
                                <div>
                                    <h3 class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-4">{{ t('billing_period') || 'Billing Period' }}</h3>
                                    <div class="flex items-center gap-4">
                                        <div class="p-3 bg-gray-50 dark:bg-slate-900 rounded-2xl">
                                            <CalendarIcon class="h-6 w-6 text-blue-600" />
                                        </div>
                                        <div>
                                            <div class="font-black text-gray-900 dark:text-white">{{ formatDate(invoice.period_start) }} — {{ formatDate(invoice.period_end) }}</div>
                                            <div class="text-xs text-gray-500 dark:text-slate-400 font-medium">{{ t('due_by') || 'Due by' }} {{ formatDate(invoice.due_date) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-4">{{ t('prepared_by') || 'Prepared By' }}</h3>
                                    <div class="flex items-center gap-4">
                                        <div class="p-3 bg-gray-50 dark:bg-slate-900 rounded-2xl">
                                            <UserIcon class="h-6 w-6 text-blue-600" />
                                        </div>
                                        <div>
                                            <div class="font-black text-gray-900 dark:text-white">{{ invoice.creator?.name || 'System' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-slate-400 font-medium">{{ formatDate(invoice.created_at) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-12">
                                <h3 class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-4">{{ t('services_rendered') }}</h3>
                                <div class="overflow-hidden border border-gray-100 dark:border-slate-700 rounded-2xl">
                                    <table class="w-full text-left">
                                        <thead class="bg-gray-50 dark:bg-slate-900">
                                            <tr>
                                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ t('description') }}</th>
                                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest text-right">{{ t('date') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                            <tr v-for="(service, idx) in invoice.services_snapshot" :key="idx" class="hover:bg-gray-50/50 dark:hover:bg-slate-700/50 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ service.name }}</div>
                                                    <div class="text-[10px] text-gray-400 uppercase tracking-tighter">{{ service.type }}</div>
                                                </td>
                                                <td class="px-6 py-4 text-right text-sm font-medium text-gray-500 dark:text-slate-400">
                                                    {{ formatDate(service.completed_at) }}
                                                </td>
                                            </tr>
                                            <tr v-if="!invoice.services_snapshot || invoice.services_snapshot.length === 0">
                                                <td colspan="2" class="px-6 py-8 text-center text-sm text-gray-400 italic">
                                                    {{ t('no_specific_services_listed') || 'No specific services listed for this invoice.' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div v-if="invoice.description" class="p-6 bg-blue-50/50 dark:bg-blue-900/10 rounded-[2rem] border border-blue-100/50 dark:border-blue-800/20">
                                <h4 class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2">{{ t('notes') }}</h4>
                                <p class="text-sm text-blue-900/80 dark:text-blue-300 font-medium leading-relaxed">{{ invoice.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] border border-gray-100 dark:border-slate-700 shadow-xl p-8">
                        <div class="flex items-center justify-between mb-8">
                            <h2 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <BanknotesIcon class="h-6 w-6 text-emerald-500" />
                                {{ t('payment_history') || 'Payment History' }}
                            </h2>
                            <button 
                                v-if="invoice.balance_due > 0 && invoice.status !== 'cancelled' && invoice.status !== 'draft'"
                                @click="showPaymentModal = true"
                                class="text-xs font-black text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 uppercase tracking-widest transition-colors flex items-center gap-1"
                            >
                                <PlusIcon class="h-4 w-4" />
                                {{ t('record_payment') || 'Record Payment' }}
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="payment in invoice.payments"
                                :key="payment.id"
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 bg-gray-50 dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-700/50"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="p-3 bg-white dark:bg-slate-800 rounded-xl shadow-sm" :class="paymentStatusColor(payment.status || 'Completed')">
                                        <CheckCircleIcon class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <div class="text-sm font-black text-gray-900 dark:text-white">{{ formatCurrency(payment.amount) }}</div>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest" :class="paymentStatusColor(payment.status || 'Completed')">
                                                {{ payment.status || 'Completed' }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-gray-500 uppercase tracking-tighter mt-0.5">
                                            {{ payment.payment_method?.replace('_', ' ') }} • {{ formatDate(payment.paid_at) }}
                                            <span v-if="payment.reference"> • {{ payment.reference }}</span>
                                        </div>
                                        <a v-if="payment.receipt_photo_url" :href="payment.receipt_photo_url" target="_blank"
                                           class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase tracking-widest mt-1 inline-block hover:underline">
                                            {{ t('view_receipt') || 'View Receipt' }}
                                        </a>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="text-right" v-if="payment.status !== 'Pending'">
                                        <div class="text-[10px] text-gray-400 uppercase tracking-widest">{{ t('recorded_by') }}</div>
                                        <div class="text-xs font-bold text-gray-600 dark:text-slate-400">{{ payment.recordedBy?.name || 'N/A' }}</div>
                                    </div>
                                    <div v-if="payment.status === 'Pending'" class="flex gap-2">
                                        <button @click="approvePayment(payment)"
                                            class="px-3 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-600/20 transition-all active:scale-95 flex items-center gap-1">
                                            <CheckCircleIcon class="h-3.5 w-3.5" />
                                            {{ t('approve') || 'Approve' }}
                                        </button>
                                        <button @click="rejectPayment(payment)"
                                            class="px-3 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 hover:bg-rose-200 transition-all active:scale-95 flex items-center gap-1">
                                            <XMarkIcon class="h-3.5 w-3.5" />
                                            {{ t('reject') || 'Reject' }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!invoice.payments || invoice.payments.length === 0" class="py-12 text-center">
                                <div class="inline-flex p-4 bg-gray-50 dark:bg-slate-900 rounded-full mb-4">
                                    <ClockIcon class="h-8 w-8 text-gray-300" />
                                </div>
                                <p class="text-sm text-gray-400 font-medium italic">{{ t('no_payments_recorded_yet') || 'No payments recorded for this invoice yet.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Sidebar -->
                <div class="space-y-6">
                    <!-- Pending payments alert -->
                    <div v-if="pendingPaymentsCount > 0" class="bg-amber-50 dark:bg-amber-900/10 p-6 rounded-[2rem] border border-amber-200 dark:border-amber-900/30">
                        <h3 class="text-xs font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest mb-2 flex items-center gap-2">
                            <ClockIcon class="h-4 w-4" />
                            {{ pendingPaymentsCount }} {{ pendingPaymentsCount === 1 ? (t('payment_awaiting_review') || 'Payment Awaiting Review') : (t('payments_awaiting_review') || 'Payments Awaiting Review') }}
                        </h3>
                        <p class="text-[10px] text-amber-800/70 dark:text-amber-300/70 font-medium leading-relaxed">
                            {{ t('client_submitted_proof') || 'The client has submitted payment proof. Review the receipt below and approve or reject.' }}
                        </p>
                    </div>

                    <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl p-8 text-white sticky top-8">
                        <h2 class="text-lg font-black uppercase tracking-wider mb-8">{{ t('financial_summary') || 'Financial Summary' }}</h2>
                        
                        <div class="space-y-8">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ t('total_amount') || 'Total Amount' }}</span>
                                <span class="text-xl font-black">{{ formatCurrency(invoice.amount) }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between pb-8 border-b border-white/10">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ t('paid_to_date') || 'Paid to Date' }}</span>
                                <span class="text-xl font-black text-emerald-400">{{ formatCurrency(invoice.paid_amount) }}</span>
                            </div>

                            <div class="flex flex-col gap-2 pt-4">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ t('balance_due') || 'Balance Due' }}</span>
                                <div class="text-5xl font-black tracking-tighter" :class="invoice.balance_due > 0 ? 'text-blue-500' : 'text-emerald-400'">
                                    {{ formatCurrency(invoice.balance_due) }}
                                </div>
                            </div>

                            <div v-if="invoice.balance_due > 0 && invoice.status !== 'cancelled' && invoice.status !== 'draft'" class="pt-8">
                                <button 
                                    @click="showPaymentModal = true"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl font-black shadow-xl shadow-blue-600/20 transition-all active:scale-95 flex items-center justify-center gap-3 uppercase tracking-widest text-xs"
                                >
                                    <BanknotesIcon class="h-5 w-5" />
                                    {{ t('record_payment') }}
                                </button>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-12 space-y-3">
                            <div class="flex items-center justify-between text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                <span>{{ t('collection_progress') || 'Collection Progress' }}</span>
                                <span>{{ Math.round((invoice.paid_amount / invoice.amount) * 100) }}%</span>
                            </div>
                            <div class="h-2 bg-white/5 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-emerald-400 transition-all duration-1000" 
                                    :style="{ width: `${(invoice.paid_amount / invoice.amount) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div v-if="invoice.status === 'draft'" class="bg-amber-50 dark:bg-amber-900/10 p-6 rounded-[2rem] border border-amber-100 dark:border-amber-900/30">
                        <h3 class="text-xs font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <ExclamationCircleIcon class="h-4 w-4" />
                            {{ t('draft_status') || 'Draft Status' }}
                        </h3>
                        <p class="text-[10px] text-amber-800/70 dark:text-amber-300/70 font-medium leading-relaxed">
                            {{ t('draft_help') || 'This invoice has not been sent to the client yet. You can still delete or modify it. Once sent, payment recording will be enabled.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Record Payment Modal -->
        <Modal :show="showPaymentModal" @close="showPaymentModal = false" max-width="lg">
            <div class="bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] relative">
                <button @click="showPaymentModal = false" class="absolute right-6 top-6 p-2 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-xl text-gray-400 transition-colors">
                    <XMarkIcon class="h-6 w-6" />
                </button>

                <h2 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight mb-8">{{ t('record_payment') }}</h2>
                
                <form @submit.prevent="submitPayment" class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ t('payment_amount') || 'Payment Amount' }} (ETB)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-gray-400">ETB</span>
                            <input 
                                v-model="paymentForm.amount"
                                type="number"
                                step="0.01"
                                class="w-full pl-14 pr-4 py-4 bg-gray-50 dark:bg-slate-900 border-none rounded-2xl text-xl font-black focus:ring-2 focus:ring-blue-500 dark:text-white"
                                required
                            />
                        </div>
                        <div v-if="paymentForm.errors.amount" class="text-rose-500 text-xs mt-1 font-bold">{{ paymentForm.errors.amount }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ t('method') }}</label>
                            <select 
                                v-model="paymentForm.payment_method"
                                class="w-full px-4 py-4 bg-gray-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-blue-500 dark:text-white appearance-none"
                            >
                                <option value="cash">{{ t('cash') }}</option>
                                <option value="bank_transfer">{{ t('bank_transfer') }}</option>
                                <option value="check">{{ t('check') }}</option>
                                <option value="mobile_money">{{ t('mobile_money') }}</option>
                                <option value="card_payment">{{ t('card_payment') || 'Card Payment' }}</option>
                                <option value="other">{{ t('other') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ t('paid_at') }}</label>
                            <input 
                                v-model="paymentForm.paid_at"
                                type="date"
                                class="w-full px-4 py-4 bg-gray-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-blue-500 dark:text-white"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ t('reference_number') || 'Reference #' }} ({{ t('optional') }})</label>
                        <input 
                            v-model="paymentForm.reference"
                            type="text"
                            class="w-full px-4 py-4 bg-gray-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-blue-500 dark:text-white"
                            :placeholder="t('ref_placeholder') || 'Transaction ID, Check #...'"
                        />
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ t('notes') }} ({{ t('optional') }})</label>
                        <textarea 
                            v-model="paymentForm.notes"
                            rows="2"
                            class="w-full px-4 py-4 bg-gray-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-medium focus:ring-2 focus:ring-blue-500 dark:text-white"
                        ></textarea>
                    </div>

                    <button 
                        type="submit"
                        :disabled="paymentForm.processing"
                        class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white py-4 rounded-2xl font-black shadow-xl shadow-emerald-600/20 transition-all flex items-center justify-center gap-3 uppercase tracking-widest text-xs"
                    >
                        <CheckCircleIcon class="h-5 w-5" v-if="!paymentForm.processing" />
                        <div v-else class="h-5 w-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        {{ t('confirm_payment') || 'Confirm Payment' }}
                    </button>
                </form>
            </div>
        </Modal>
    </component>
</template>
