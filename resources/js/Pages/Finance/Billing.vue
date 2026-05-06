<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import FinanceLayout from '@/Layouts/FinanceLayout.vue';
import { 
    BanknotesIcon, ExclamationCircleIcon, CheckCircleIcon, 
    XMarkIcon, BellAlertIcon, CalendarIcon, 
    ArrowPathIcon, InformationCircleIcon, ClockIcon
} from '@heroicons/vue/24/outline';
import { ref, reactive, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    kpis: Object,
    clientsBilling: Array,
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

// ── Date Formatting Helper ───────────────────────────────────────────────────
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    }).format(date);
};

const formatDateTime = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
    errors: {},
});

const openPaymentModal = (client) => {
    paymentTarget.value = client;
    paymentForm.amount = client.retainer_fee ?? '';
    paymentForm.payment_date = new Date().toISOString().slice(0, 10);
    paymentForm.notes = '';
    paymentForm.errors = {};
    showPaymentModal.value = true;
};

const closePaymentModal = () => {
    showPaymentModal.value = false;
    paymentTarget.value = null;
};

const submitPayment = () => {
    paymentProcessing.value = true;
    router.post(
        route('finance.payments.record', paymentTarget.value.id),
        {
            amount: paymentForm.amount,
            payment_date: paymentForm.payment_date,
            notes: paymentForm.notes,
        },
        {
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

            <!-- Flash success -->
            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="flash?.success"
                    class="mb-8 flex items-center gap-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-5 py-4 text-sm text-emerald-800 dark:text-emerald-300 shadow-sm"
                >
                    <CheckCircleIcon class="h-6 w-6 text-emerald-500 shrink-0" />
                    <span class="font-medium">{{ flash.success }}</span>
                </div>
            </Transition>

            <!-- KPI cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 mb-10">
                <div class="relative overflow-hidden bg-white dark:bg-slate-800 shadow-xl shadow-gray-200/50 dark:shadow-none rounded-2xl border border-gray-100 dark:border-slate-700 p-6 transition-all hover:scale-[1.02]">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <BanknotesIcon class="h-20 w-20 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="relative">
                        <dt class="text-sm font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">{{ t('total_expected') }}</dt>
                        <dd class="mt-2 text-3xl font-black text-gray-900 dark:text-white">ETB {{ kpis.total_expected }}</dd>
                        <div class="mt-2 flex items-center text-xs text-blue-600 dark:text-blue-400 font-medium">
                            <InformationCircleIcon class="h-4 w-4 mr-1" />
                            {{ t('total_expected_desc') }}
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-red-50 to-white dark:from-red-900/10 dark:to-slate-800 shadow-xl shadow-red-200/30 dark:shadow-none rounded-2xl border border-red-100 dark:border-red-900/30 p-6 transition-all hover:scale-[1.02]">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <ExclamationCircleIcon class="h-20 w-20 text-red-600 dark:text-red-400" />
                    </div>
                    <div class="relative">
                        <dt class="text-sm font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider">{{ t('overdue') }} (> 30 Days)</dt>
                        <dd class="mt-2 text-3xl font-black text-red-700 dark:text-red-500">ETB {{ kpis.total_overdue }}</dd>
                        <div class="mt-2 flex items-center text-xs text-red-600 dark:text-red-400 font-medium italic">
                            {{ t('overdue_desc') }}
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-900/10 dark:to-slate-800 shadow-xl shadow-emerald-200/30 dark:shadow-none rounded-2xl border border-emerald-100 dark:border-emerald-900/30 p-6 transition-all hover:scale-[1.02]">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <CheckCircleIcon class="h-20 w-20 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div class="relative">
                        <dt class="text-sm font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">{{ t('collected_this_month') }}</dt>
                        <dd class="mt-2 text-3xl font-black text-emerald-700 dark:text-emerald-500">ETB {{ kpis.total_collected }}</dd>
                        <div class="mt-2 flex items-center text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                            {{ ((parseFloat(kpis.total_collected.replace(/,/g, '')) / parseFloat(kpis.total_expected.replace(/,/g, ''))) * 100).toFixed(1) }}% {{ t('collection_rate') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clients table -->
            <div class="bg-white dark:bg-slate-800 shadow-2xl shadow-gray-200/50 dark:shadow-none ring-1 ring-gray-100 dark:ring-slate-700 sm:rounded-2xl overflow-hidden border border-gray-100 dark:border-slate-700">
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
                                    <span
                                        v-if="client.payment_status === 'Paid'"
                                        class="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2.5 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800"
                                    >
                                        <CheckCircleIcon class="h-3.5 w-3.5 mr-1" />
                                        {{ t('paid') }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2.5 py-0.5 text-xs font-bold text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800"
                                    >
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
                                    <!-- Paid: show status -->
                                    <template v-if="client.payment_status === 'Paid'">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold text-xs flex items-center justify-end">
                                            <CheckCircleIcon class="h-4 w-4 mr-1" />
                                            {{ t('cleared') }}
                                        </span>
                                    </template>
    
                                    <!-- Unpaid: Mark as Paid + Send Reminder -->
                                    <template v-else>
                                        <button
                                            v-if="canRecordPayment"
                                            @click="openPaymentModal(client)"
                                            class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700 active:scale-95 transition-all"
                                        >
                                            {{ t('record_payment') }}
                                        </button>
    
                                        <button
                                            v-if="canSendReminder"
                                            @click="sendReminder(client.id)"
                                            :disabled="reminderProcessingId === client.id"
                                            class="inline-flex items-center rounded-lg bg-white dark:bg-slate-700 px-3 py-1.5 text-xs font-bold text-gray-700 dark:text-slate-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-slate-600 hover:bg-gray-50 dark:hover:bg-slate-600 disabled:opacity-50 active:scale-95 transition-all"
                                        >
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
        </div>

        <!-- ── Payment Modal ──────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showPaymentModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 backdrop-blur-sm bg-slate-900/40">
                    <!-- Panel -->
                    <div class="relative w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-slate-700">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-8 py-6 border-b border-gray-100 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-700/50">
                            <div>
                                <h2 class="text-lg font-black text-gray-900 dark:text-white">{{ t('record_payment') }}</h2>
                                <p class="text-xs font-medium text-blue-600 dark:text-blue-400 mt-0.5">{{ paymentTarget?.company_name }}</p>
                            </div>
                            <button @click="closePaymentModal" class="rounded-full p-2 hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                                <XMarkIcon class="h-5 w-5 text-gray-500 dark:text-slate-400" />
                            </button>
                        </div>

                        <!-- Body -->
                        <form @submit.prevent="submitPayment" class="px-8 py-6 space-y-5">

                            <!-- Amount -->
                            <div>
                                <label for="pay-amount" class="block text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-2">
                                    {{ t('amount_received') }} (ETB)
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-400 sm:text-sm">$</span>
                                    </div>
                                    <input
                                        id="pay-amount"
                                        v-model="paymentForm.amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 pl-8 pr-4 py-3 text-sm font-bold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        :class="{ 'border-red-400 ring-1 ring-red-400': paymentForm.errors.amount }"
                                    />
                                </div>
                                <p v-if="paymentForm.errors.amount" class="mt-1 text-[10px] font-bold text-red-500 uppercase tracking-tighter">{{ paymentForm.errors.amount }}</p>
                            </div>

                            <!-- Payment Date -->
                            <div>
                                <label for="pay-date" class="block text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-2">
                                    {{ t('payment_date') }}
                                </label>
                                <div class="relative">
                                    <input
                                        id="pay-date"
                                        v-model="paymentForm.payment_date"
                                        type="date"
                                        :max="new Date().toISOString().slice(0, 10)"
                                        class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-3 text-sm font-bold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        :class="{ 'border-red-400 ring-1 ring-red-400': paymentForm.errors.payment_date }"
                                    />
                                </div>
                                <p v-if="paymentForm.errors.payment_date" class="mt-1 text-[10px] font-bold text-red-500 uppercase tracking-tighter">{{ paymentForm.errors.payment_date }}</p>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label for="pay-notes" class="block text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-2">{{ t('notes') }}</label>
                                <textarea
                                    id="pay-notes"
                                    v-model="paymentForm.notes"
                                    rows="3"
                                    placeholder="Add any additional details (e.g., bank reference)..."
                                    class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-3 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none transition-all"
                                />
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    @click="closePaymentModal"
                                    class="rounded-xl px-5 py-3 text-sm font-bold text-gray-500 dark:text-slate-400 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all"
                                >
                                    {{ t('cancel') }}
                                </button>
                                <button
                                    type="submit"
                                    :disabled="paymentProcessing"
                                    class="rounded-xl px-6 py-3 text-sm font-black text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60 shadow-xl shadow-blue-600/30 transition-all flex items-center gap-2"
                                >
                                    <ArrowPathIcon v-if="paymentProcessing" class="animate-spin h-4 w-4" />
                                    {{ paymentProcessing ? t('saving') : t('confirm_payment') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </component>
</template>
