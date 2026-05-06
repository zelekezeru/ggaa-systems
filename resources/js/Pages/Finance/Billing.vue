<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import FinanceLayout from '@/Layouts/FinanceLayout.vue';
import { 
    BanknotesIcon, ExclamationCircleIcon, CheckCircleIcon, 
    XMarkIcon, BellAlertIcon, CalendarIcon, 
    ArrowPathIcon, InformationCircleIcon, ClockIcon,
    DocumentTextIcon, ReceiptPercentIcon, ChartPieIcon,
    PhotoIcon, LinkIcon, CreditCardIcon
} from '@heroicons/vue/24/outline';
import { ref, reactive, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    kpis: Object,
    clientsBilling: Array,
    recentPayments: Array,
    pendingInvoices: Array,
    canApprove: Boolean,
    canRecord: Boolean,
});

const page = usePage();
const flash = computed(() => page.props.flash);
const userPermissions = computed(() => page.props.auth?.user?.permissions ?? []);

const canSendReminder = computed(() => userPermissions.value.includes('send payment reminders'));
const canRecordPayment = computed(() => userPermissions.value.includes('record payments'));

const currentLayout = computed(() => {
    const roles = page.props.auth.user.roles || [];
    if (roles.includes('Client')) return ClientLayout;
    if (roles.includes('Employee')) return EmployeeLayout;
    if (roles.includes('Finance Admin')) return FinanceLayout;
    return AdminLayout;
});

// ── Tabs State ───────────────────────────────────────────────────────────────
const activeTab = ref('billings'); // billings, payments, pending, reports

// ── Date Formatting Helper ───────────────────────────────────────────────────
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short', day: 'numeric', year: 'numeric'
    }).format(date);
};

const formatDateTime = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    }).format(date);
};

// ── Reminder state ────────────────────────────────────────────────────────────
const reminderProcessingId = ref(null);

const sendReminder = (clientId) => {
    reminderProcessingId.value = clientId;
    router.post(route('finance.reminders.send', clientId), {}, {
        preserveScroll: true,
        onFinish: () => { reminderProcessingId.value = null; },
    });
};

// ── Payment modal state ───────────────────────────────────────────────────────
const showPaymentModal = ref(false);
const paymentTarget = ref(null);
const paymentProcessing = ref(false);

const paymentForm = reactive({
    amount: '',
    payment_method: 'bank_transfer',
    payment_date: new Date().toISOString().slice(0, 10),
    reference: '',
    receipt_link: '',
    receipt_photo: null,
    notes: '',
    errors: {},
});

const openPaymentModal = (client) => {
    paymentTarget.value = client;
    paymentForm.amount = client.retainer_fee ?? '';
    paymentForm.payment_method = 'bank_transfer';
    paymentForm.payment_date = new Date().toISOString().slice(0, 10);
    paymentForm.reference = '';
    paymentForm.receipt_link = '';
    paymentForm.receipt_photo = null;
    paymentForm.notes = '';
    paymentForm.errors = {};
    showPaymentModal.value = true;
};

const closePaymentModal = () => {
    showPaymentModal.value = false;
    paymentTarget.value = null;
    paymentForm.reset();
};

const showReceiptModal = ref(false);
const receiptTarget = ref(null);

const openReceiptModal = (payment) => {
    receiptTarget.value = payment;
    showReceiptModal.value = true;
};

const closeReceiptModal = () => {
    showReceiptModal.value = false;
    receiptTarget.value = null;
};

const approvePayment = (paymentId) => {
    if (!confirm(t('approve_confirm') || 'Are you sure you want to approve this payment?')) return;
    
    router.post(route('finance.payments.approve', paymentId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Success toast is handled globally or via flash
        }
    });
};

const onFileChange = (e) => {
    paymentForm.receipt_photo = e.target.files[0];
};

const submitPayment = (status = 'Completed') => {
    paymentProcessing.value = true;
    
    router.post(
        route('finance.payments.record', paymentTarget.value.id),
        {
            ...paymentForm,
            status: status
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                closePaymentModal();
            },
            onError: (errors) => {
                paymentForm.errors = errors;
            },
            onFinish: () => {
                paymentProcessing.value = false;
            },
        }
    );
};
</script>

<template>
    <Head :title="t('billing')" />

    <component :is="currentLayout">
        <div class="px-4 sm:px-6 lg:px-8 py-8 min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-300">

            <!-- Page header -->
            <div class="mb-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ t('revenue_and_receivables') }}</h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">{{ t('manage_billing_description') }}</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-3 py-1 text-xs font-medium text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                        <CalendarIcon class="h-3.5 w-3.5 mr-1.5" />
                        {{ t('billing_period') }}: {{ new Date().toLocaleString('default', { month: 'long', year: 'numeric' }) }}
                    </span>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="mb-8 border-b border-gray-200 dark:border-slate-700">
                <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                    <button
                        @click="activeTab = 'billings'"
                        :class="[activeTab === 'billings' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']"
                    >
                        <RectangleGroupIcon class="h-4 w-4 inline-block mr-2" />
                        {{ t('billings') || 'Clients & Billing' }}
                    </button>
                    <button
                        @click="activeTab = 'payments'"
                        :class="[activeTab === 'payments' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']"
                    >
                        <CheckCircleIcon class="h-4 w-4 inline-block mr-2" />
                        {{ t('payments_done') || 'Payments Done' }}
                    </button>
                    <button
                        @click="activeTab = 'pending'"
                        :class="[activeTab === 'pending' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']"
                    >
                        <ClockIcon class="h-4 w-4 inline-block mr-2" />
                        {{ t('pending_payments') || 'Pending Invoices' }}
                        <span v-if="props.kpis.pending_count > 0" class="ml-2 rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:text-amber-400">
                            {{ props.kpis.pending_count }}
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'reports'"
                        :class="[activeTab === 'reports' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']"
                    >
                        <ChartPieIcon class="h-4 w-4 inline-block mr-2" />
                        {{ t('report') || 'Financial Report' }}
                    </button>
                </nav>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 mb-10">
                <div class="relative overflow-hidden bg-white dark:bg-slate-800 shadow-xl shadow-gray-200/50 dark:shadow-none rounded-2xl border border-gray-100 dark:border-slate-700 p-6 transition-all hover:scale-[1.02]">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <BanknotesIcon class="h-20 w-20 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="relative">
                        <dt class="text-sm font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">{{ t('total_expected') }}</dt>
                        <dd class="mt-2 text-3xl font-black text-gray-900 dark:text-white">ETB {{ kpis.total_expected }}</dd>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-red-50 to-white dark:from-red-900/10 dark:to-slate-800 shadow-xl shadow-red-200/30 dark:shadow-none rounded-2xl border border-red-100 dark:border-red-900/30 p-6 transition-all hover:scale-[1.02]">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <ExclamationCircleIcon class="h-20 w-20 text-red-600 dark:text-red-400" />
                    </div>
                    <div class="relative">
                        <dt class="text-sm font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider">{{ t('overdue') }}</dt>
                        <dd class="mt-2 text-3xl font-black text-red-700 dark:text-red-500">ETB {{ kpis.total_overdue }}</dd>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-900/10 dark:to-slate-800 shadow-xl shadow-emerald-200/30 dark:shadow-none rounded-2xl border border-emerald-100 dark:border-emerald-900/30 p-6 transition-all hover:scale-[1.02]">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <CheckCircleIcon class="h-20 w-20 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div class="relative">
                        <dt class="text-sm font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">{{ t('collected_this_month') }}</dt>
                        <dd class="mt-2 text-3xl font-black text-emerald-700 dark:text-emerald-500">ETB {{ kpis.total_collected }}</dd>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Billings -->
            <div v-if="activeTab === 'billings'" class="bg-white dark:bg-slate-800 shadow-2xl shadow-gray-200/50 dark:shadow-none ring-1 ring-gray-100 dark:ring-slate-700 sm:rounded-2xl overflow-hidden border border-gray-100 dark:border-slate-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50/50 dark:bg-slate-700/50 backdrop-blur-sm">
                            <tr>
                                <th scope="col" class="py-4 pl-6 pr-3 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('client') }}</th>
                                <th scope="col" class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('monthly_retainer') }} (ETB)</th>
                                <th scope="col" class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('last_payment') }}</th>
                                <th scope="col" class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('status') }}</th>
                                <th scope="col" class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('reminders') }}</th>
                                <th scope="col" class="relative py-4 pl-3 pr-6 text-right text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            <tr v-for="client in clientsBilling" :key="client.id" class="hover:bg-gray-50/80 dark:hover:bg-slate-700/30 transition-all duration-200">
                                <td class="whitespace-nowrap py-5 pl-6 pr-3">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ client.company_name }}</span>
                                        <span class="text-[10px] text-gray-400 dark:text-slate-500 font-medium">ID: #C{{ client.id.toString().padStart(4, '0') }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-5 text-sm font-mono font-semibold text-gray-700 dark:text-slate-300">
                                    {{ Number(client.retainer_fee).toLocaleString() }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-5 text-sm text-gray-500 dark:text-slate-400">
                                    <div v-if="client.last_payment_date" class="flex items-center">
                                        <CalendarIcon class="h-4 w-4 mr-1.5 text-gray-400" />
                                        {{ formatDate(client.last_payment_date) }}
                                    </div>
                                    <span v-else class="text-gray-300 dark:text-slate-600 italic">{{ t('no_results_found') }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-5 text-sm">
                                    <span v-if="client.payment_status === 'Paid'" class="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2.5 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                        <CheckCircleIcon class="h-3.5 w-3.5 mr-1" />
                                        {{ t('paid') }}
                                    </span>
                                    <span v-else-if="client.payment_status === 'Partially Paid'" class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-2.5 py-0.5 text-xs font-bold text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                        <InformationCircleIcon class="h-3.5 w-3.5 mr-1" />
                                        {{ t('partially_paid') }}
                                    </span>
                                    <span v-else class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2.5 py-0.5 text-xs font-bold text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                        <ClockIcon class="h-3.5 w-3.5 mr-1" />
                                        {{ t('pending') }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-5 text-sm">
                                    <div v-if="client.last_reminder_sent_at" class="flex flex-col">
                                        <span class="inline-flex items-center text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded-md w-fit border border-blue-100 dark:border-blue-900/30">
                                            <BellAlertIcon class="h-3 w-3 mr-1" />
                                            {{ t('sent') }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 dark:text-slate-500 mt-1">{{ formatDateTime(client.last_reminder_sent_at) }}</span>
                                    </div>
                                    <span v-else class="text-[10px] text-gray-300 dark:text-slate-600 italic uppercase tracking-tighter font-bold">{{ t('never_sent') }}</span>
                                </td>
                                <td class="relative whitespace-nowrap py-5 pl-3 pr-6 text-right text-sm font-medium space-x-2">
                                    <template v-if="client.payment_status === 'Paid'">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold text-xs flex items-center justify-end">
                                            <CheckCircleIcon class="h-4 w-4 mr-1" />
                                            {{ t('cleared') }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        <button v-if="canRecordPayment" @click="openPaymentModal(client)" class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700 active:scale-95 transition-all">
                                            {{ t('record_payment') }}
                                        </button>
                                        <button v-if="canSendReminder" @click="sendReminder(client.id)" :disabled="reminderProcessingId === client.id" class="inline-flex items-center rounded-lg bg-white dark:bg-slate-700 px-3 py-1.5 text-xs font-bold text-gray-700 dark:text-slate-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-slate-600 hover:bg-gray-50 dark:hover:bg-slate-600 disabled:opacity-50 active:scale-95 transition-all">
                                            <ArrowPathIcon v-if="reminderProcessingId === client.id" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5" />
                                            <BellAlertIcon v-else class="h-3.5 w-3.5 mr-1.5 text-gray-400 dark:text-slate-400" />
                                            {{ reminderProcessingId === client.id ? t('sending') : t('remind') }}
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Recent Payments -->
            <div v-else-if="activeTab === 'payments'" class="bg-white dark:bg-slate-800 shadow-2xl rounded-2xl overflow-hidden border border-gray-100 dark:border-slate-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50/50 dark:bg-slate-700/50">
                            <tr>
                                <th class="py-4 pl-6 pr-3 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('client') }}</th>
                                <th class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('amount') || 'Amount' }}</th>
                                <th class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('method') || 'Method' }}</th>
                                <th class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('date') || 'Date' }}</th>
                                <th class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('reference') || 'Reference' }}</th>
                                <th class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            <tr v-for="payment in recentPayments" :key="payment.id" class="hover:bg-gray-50/80 dark:hover:bg-slate-700/30 transition-all">
                                <td class="py-5 pl-6 pr-3">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ payment.invoice?.client?.company_name || 'N/A' }}</span>
                                </td>
                                <td class="px-3 py-5 text-sm font-mono font-bold text-gray-700 dark:text-slate-300">
                                    ETB {{ Number(payment.amount).toLocaleString() }}
                                </td>
                                <td class="px-3 py-5 text-sm text-gray-500 dark:text-slate-400 capitalize">
                                    {{ payment.payment_method.replace('_', ' ') }}
                                </td>
                                <td class="px-3 py-5 text-sm text-gray-500 dark:text-slate-400">
                                    {{ formatDate(payment.paid_at) }}
                                </td>
                                <td class="px-3 py-5 text-sm text-gray-500 dark:text-slate-400 font-mono">
                                    {{ payment.reference || '-' }}
                                </td>
                                <td class="px-3 py-5">
                                    <div class="flex items-center gap-2">
                                        <span :class="[
                                            payment.status === 'Completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 
                                            payment.status === 'Pending Approval' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' :
                                            'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-400'
                                        ]" class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">
                                            {{ t(payment.status.toLowerCase().replace(' ', '_')) || payment.status }}
                                        </span>
                                        
                                        <!-- Actions -->
                                        <div class="flex items-center gap-1.5 ml-auto">
                                            <button 
                                                v-if="payment.receipt_photo_path || payment.receipt_link"
                                                @click="openReceiptModal(payment)"
                                                class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                                :title="t('view_receipt')"
                                            >
                                                <DocumentTextIcon class="h-4 w-4" />
                                            </button>
                                            <button 
                                                v-if="payment.status === 'Pending Approval' && canApprove"
                                                @click="approvePayment(payment.id)"
                                                class="px-2 py-1 text-[10px] font-black uppercase tracking-tighter bg-emerald-600 hover:bg-emerald-700 text-white rounded-md shadow-sm transition-all active:scale-95"
                                            >
                                                {{ t('approve') }}
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Pending Invoices -->
            <div v-else-if="activeTab === 'pending'" class="bg-white dark:bg-slate-800 shadow-2xl rounded-2xl overflow-hidden border border-gray-100 dark:border-slate-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50/50 dark:bg-slate-700/50">
                            <tr>
                                <th class="py-4 pl-6 pr-3 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('invoice') || 'Invoice #' }}</th>
                                <th class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('client') }}</th>
                                <th class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('amount') }}</th>
                                <th class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('due_date') }}</th>
                                <th class="px-3 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">{{ t('status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            <tr v-for="inv in pendingInvoices" :key="inv.id" class="hover:bg-gray-50/80 dark:hover:bg-slate-700/30 transition-all">
                                <td class="py-5 pl-6 pr-3 font-mono font-bold text-blue-600 dark:text-blue-400 text-sm">
                                    {{ inv.invoice_number }}
                                </td>
                                <td class="px-3 py-5 text-sm font-bold text-gray-900 dark:text-white">
                                    {{ inv.client?.company_name }}
                                </td>
                                <td class="px-3 py-5 text-sm font-mono font-bold text-gray-700 dark:text-slate-300">
                                    ETB {{ Number(inv.amount).toLocaleString() }}
                                </td>
                                <td class="px-3 py-5 text-sm text-gray-500 dark:text-slate-400">
                                    {{ formatDate(inv.due_date) }}
                                </td>
                                <td class="px-3 py-5">
                                    <span :class="[inv.status === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700', 'inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider']">
                                        {{ inv.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Reports -->
            <div v-else-if="activeTab === 'reports'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-white mb-2">{{ t('collection_performance') || 'Collection Performance' }}</h3>
                            <p class="text-sm text-gray-500 dark:text-slate-400">{{ t('collection_performance_desc') || 'Monthly target achievement' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-4xl font-black text-blue-600 dark:text-blue-400">84%</span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-white mb-2">{{ t('aging_receivables') || 'Aging Receivables' }}</h3>
                            <p class="text-sm text-gray-500 dark:text-slate-400">{{ t('aging_receivables_desc') || 'Outstanding over 30 days' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-4xl font-black text-red-600 dark:text-red-400">12</span>
                        </div>
                    </div>
                </div>
                <!-- Placeholder for Chart -->
                <div class="bg-white dark:bg-slate-800 p-10 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-700 h-96 flex flex-col items-center justify-center text-center">
                    <ChartPieIcon class="h-20 w-20 text-gray-200 dark:text-slate-700 mb-4" />
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ t('analytics_coming_soon') || 'Analytics Coming Soon' }}</h3>
                    <p class="text-sm text-gray-500 dark:text-slate-400 max-w-sm mt-2">We are processing your financial data to generate deep insights and projection models.</p>
                </div>
            </div>

        </div>

        <!-- ── Enhanced Payment Modal ────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showPaymentModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 backdrop-blur-sm bg-slate-900/40 overflow-y-auto">
                    <!-- Panel -->
                    <div class="relative w-full max-w-xl bg-white dark:bg-slate-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-slate-700 my-8">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-8 py-6 border-b border-gray-100 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-700/50">
                            <div>
                                <h2 class="text-xl font-black text-gray-900 dark:text-white">{{ t('record_payment') }}</h2>
                                <p class="text-xs font-medium text-blue-600 dark:text-blue-400 mt-0.5 uppercase tracking-wider">{{ paymentTarget?.company_name }}</p>
                            </div>
                            <button @click="closePaymentModal" class="rounded-full p-2 hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                                <XMarkIcon class="h-6 w-6 text-gray-500 dark:text-slate-400" />
                            </button>
                        </div>

                        <!-- Body -->
                        <form @submit.prevent="submitPayment" class="px-8 py-8 space-y-6">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Amount -->
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-[0.2em] mb-2">{{ t('amount_received') }} (ETB)</label>
                                    <div class="relative">
                                        <BanknotesIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                        <input v-model="paymentForm.amount" type="number" step="0.01" class="w-full rounded-xl border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 pl-10 pr-4 py-3 text-sm font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-400': paymentForm.errors.amount }" />
                                    </div>
                                    <p v-if="paymentForm.errors.amount" class="mt-1 text-[10px] font-bold text-red-500">{{ paymentForm.errors.amount }}</p>
                                </div>

                                <!-- Payment Method -->
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-[0.2em] mb-2">{{ t('payment_method') || 'Payment Method' }}</label>
                                    <select v-model="paymentForm.payment_method" class="w-full rounded-xl border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-3 text-sm font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                        <option value="cash">{{ t('cash') || 'Cash' }}</option>
                                        <option value="bank_transfer">{{ t('bank_transfer') || 'Bank Transfer' }}</option>
                                        <option value="mobile_money">{{ t('mobile_money') || 'Mobile Money' }}</option>
                                        <option value="check">{{ t('cheque') || 'Cheque' }}</option>
                                        <option value="card_payment">{{ t('card_payment') || 'Card Payment' }}</option>
                                        <option value="other">{{ t('other') || 'Other' }}</option>
                                    </select>
                                </div>

                                <!-- Date -->
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-[0.2em] mb-2">{{ t('payment_date') }}</label>
                                    <input v-model="paymentForm.payment_date" type="date" class="w-full rounded-xl border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-3 text-sm font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" />
                                </div>

                                <!-- Reference (Conditional) -->
                                <div v-if="paymentForm.payment_method !== 'cash'">
                                    <label class="block text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-[0.2em] mb-2">{{ t('reference_number') || 'Reference Number' }}</label>
                                    <div class="relative">
                                        <CreditCardIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                        <input v-model="paymentForm.reference" type="text" placeholder="TXN-123456" class="w-full rounded-xl border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 pl-10 pr-4 py-3 text-sm font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" />
                                    </div>
                                </div>
                            </div>

                            <!-- Extended Receipt Fields (Conditional) -->
                            <div v-if="paymentForm.payment_method !== 'cash'" class="space-y-6 pt-2">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-[0.2em] mb-2">{{ t('receipt_link') || 'Receipt Link (URL)' }}</label>
                                    <div class="relative">
                                        <LinkIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                        <input v-model="paymentForm.receipt_link" type="url" placeholder="https://..." class="w-full rounded-xl border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 pl-10 pr-4 py-3 text-sm font-medium text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-[0.2em] mb-2">{{ t('receipt_photo') || 'Receipt Photo / Screenshot' }}</label>
                                    <div class="relative border-2 border-dashed border-gray-200 dark:border-slate-700 rounded-2xl p-6 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-all group">
                                        <input @change="onFileChange" type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <PhotoIcon class="h-10 w-10 text-gray-300 dark:text-slate-600 group-hover:text-blue-500 mb-2 transition-colors" />
                                            <span class="text-xs font-bold text-gray-500 dark:text-slate-400">{{ paymentForm.receipt_photo ? paymentForm.receipt_photo.name : 'Click to upload or drag image' }}</span>
                                            <span class="text-[10px] text-gray-400 mt-1">PNG, JPG up to 2MB</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-[0.2em] mb-2">{{ t('notes') }}</label>
                                <textarea v-model="paymentForm.notes" rows="2" class="w-full rounded-xl border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-3 pt-6">
                                <button type="button" @click="closePaymentModal" class="rounded-xl px-4 py-3 text-sm font-bold text-gray-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all">
                                    {{ t('cancel') }}
                                </button>
                                <button 
                                    type="button" 
                                    @click="submitPayment('Draft')" 
                                    :disabled="paymentProcessing" 
                                    class="rounded-xl px-5 py-3 text-sm font-bold text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-600 transition-all"
                                >
                                    {{ t('save_draft') }}
                                </button>
                                <button 
                                    type="button" 
                                    @click="submitPayment('Pending Approval')" 
                                    :disabled="paymentProcessing" 
                                    class="rounded-xl px-6 py-3 text-sm font-black text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60 shadow-xl shadow-blue-600/30 transition-all flex items-center gap-2"
                                >
                                    <ArrowPathIcon v-if="paymentProcessing" class="animate-spin h-4 w-4" />
                                    {{ paymentProcessing ? t('saving') : t('submit_approval') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ── Receipt View Modal ────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showReceiptModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4 backdrop-blur-md bg-slate-900/60 overflow-y-auto">
                    <div class="relative w-full max-w-2xl bg-white dark:bg-slate-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-slate-700">
                        <div class="flex items-center justify-between px-8 py-4 border-b border-gray-100 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-700/50">
                            <h2 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-wider">{{ t('payment_receipt') }}</h2>
                            <div class="flex items-center gap-2">
                                <a 
                                    v-if="receiptTarget?.receipt_photo_path" 
                                    :href="receiptTarget.receipt_photo_url" 
                                    download 
                                    class="p-2 text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                >
                                    <ArrowPathIcon class="h-5 w-5 rotate-180" />
                                </a>
                                <button @click="closeReceiptModal" class="rounded-full p-2 hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                                    <XMarkIcon class="h-5 w-5 text-gray-500" />
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-8">
                            <div v-if="receiptTarget?.receipt_photo_path" class="rounded-2xl overflow-hidden border border-gray-100 dark:border-slate-700 shadow-inner">
                                <img :src="receiptTarget.receipt_photo_url" class="w-full h-auto max-h-[70vh] object-contain" :alt="t('payment_receipt')" />
                            </div>
                            <div v-else-if="receiptTarget?.receipt_link" class="bg-gray-50 dark:bg-slate-900/50 p-10 rounded-2xl border-2 border-dashed border-gray-200 dark:border-slate-700 flex flex-col items-center text-center">
                                <LinkIcon class="h-12 w-12 text-blue-500 mb-4" />
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ t('external_receipt_link') || 'External Receipt Link' }}</h3>
                                <a :href="receiptTarget.receipt_link" target="_blank" class="text-blue-600 dark:text-blue-400 font-mono text-sm break-all hover:underline">{{ receiptTarget.receipt_link }}</a>
                            </div>

                            <div class="mt-8 grid grid-cols-2 gap-6 text-sm">
                                <div class="space-y-1">
                                    <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('amount') }}</span>
                                    <span class="block font-mono font-black text-slate-900 dark:text-white text-lg">ETB {{ Number(receiptTarget?.amount).toLocaleString() }}</span>
                                </div>
                                <div class="space-y-1">
                                    <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('recorded_on') }}</span>
                                    <span class="block font-bold text-slate-700 dark:text-slate-300">{{ formatDate(receiptTarget?.created_at) }}</span>
                                    <span v-if="receiptTarget?.recorded_by" class="block text-[10px] text-gray-500 dark:text-slate-500 italic">By {{ receiptTarget.recorded_by?.name }}</span>
                                </div>
                                <div v-if="receiptTarget?.approved_by" class="space-y-1">
                                    <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('approved_by') || 'Approved By' }}</span>
                                    <span class="block font-bold text-emerald-600 dark:text-emerald-400">{{ receiptTarget.approved_by?.name }}</span>
                                    <span class="block text-[10px] text-gray-500 dark:text-slate-500 italic">{{ formatDate(receiptTarget.approved_at) }}</span>
                                </div>
                                <div class="space-y-1">
                                    <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('reference') }}</span>
                                    <span class="block font-mono font-bold text-blue-600 dark:text-blue-400">{{ receiptTarget?.reference || 'N/A' }}</span>
                                </div>
                                <div class="space-y-1">
                                    <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('status') }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-[10px] font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                                        {{ receiptTarget?.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </component>
</template>
