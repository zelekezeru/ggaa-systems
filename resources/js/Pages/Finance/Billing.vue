<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import FinanceLayout from '@/Layouts/FinanceLayout.vue';
import { BanknotesIcon, ExclamationCircleIcon, CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { ref, reactive, computed } from 'vue';

const props = defineProps({
    kpis: Object,
    clientsBilling: Array,
});

const page = usePage();
const flash = computed(() => page.props.flash);

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
    <Head title="Finance & Billing" />

    <FinanceLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8">

            <!-- Page header -->
            <h1 class="text-2xl font-bold leading-6 text-gray-900 mb-8">Revenue &amp; Receivables</h1>

            <!-- Flash success -->
            <div
                v-if="flash?.success"
                class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800"
            >
                <CheckCircleIcon class="h-5 w-5 text-green-500 shrink-0" />
                {{ flash.success }}
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <BanknotesIcon class="h-6 w-6 text-gray-400" aria-hidden="true" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Expected</dt>
                                <dd class="text-2xl font-semibold text-gray-900">ETB {{ kpis.total_expected }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-red-50 overflow-hidden shadow rounded-lg border border-red-200 p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <ExclamationCircleIcon class="h-6 w-6 text-red-600" aria-hidden="true" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-red-800 truncate">Overdue (&gt; 30 Days)</dt>
                                <dd class="text-2xl font-semibold text-red-700">ETB {{ kpis.total_overdue }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 overflow-hidden shadow rounded-lg border border-green-200 p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CheckCircleIcon class="h-6 w-6 text-green-600" aria-hidden="true" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-green-800 truncate">Collected This Month</dt>
                                <dd class="text-2xl font-semibold text-green-700">ETB {{ kpis.total_collected }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clients table -->
            <div class="bg-white shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Client Name</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Monthly Retainer</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Last Payment</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right font-semibold text-gray-900 text-sm">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="client in clientsBilling" :key="client.id" class="hover:bg-gray-50 transition-colors duration-100">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-bold text-gray-900 sm:pl-6">
                                {{ client.company_name }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600">ETB {{ client.retainer_fee }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ client.last_payment_date || 'N/A' }}</td>

                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                <span
                                    v-if="client.payment_status === 'Paid'"
                                    class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20"
                                >
                                    <CheckCircleIcon class="h-3.5 w-3.5 mr-1 text-green-500" />
                                    Paid
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20"
                                >
                                    Pending
                                </span>
                            </td>

                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-2">
                                <!-- Paid: only show reminder option -->
                                <template v-if="client.payment_status === 'Paid'">
                                    <span class="text-green-600 font-semibold text-xs">✓ Payment Recorded</span>
                                </template>

                                <!-- Unpaid: Mark as Paid + Send Reminder -->
                                <template v-else>
                                    <button
                                        @click="openPaymentModal(client)"
                                        class="inline-flex items-center rounded bg-blue-600 px-2.5 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors"
                                    >
                                        Mark as Paid
                                    </button>

                                    <button
                                        @click="sendReminder(client.id)"
                                        :disabled="reminderProcessingId === client.id"
                                        class="inline-flex items-center rounded bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50 transition-colors"
                                    >
                                        <svg v-if="reminderProcessingId === client.id" class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ reminderProcessingId === client.id ? 'Sending...' : 'Send Reminder' }}
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Payment Modal ──────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-gray-900/50" @click="closePaymentModal" />

                    <!-- Panel -->
                    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Record Payment</h2>
                                <p class="text-xs text-gray-500 mt-0.5">{{ paymentTarget?.company_name }}</p>
                            </div>
                            <button @click="closePaymentModal" class="rounded-full p-1 hover:bg-gray-200 transition-colors">
                                <XMarkIcon class="h-5 w-5 text-gray-500" />
                            </button>
                        </div>

                        <!-- Body -->
                        <form @submit.prevent="submitPayment" class="px-6 py-5 space-y-4">

                            <!-- Amount -->
                            <div>
                                <label for="pay-amount" class="block text-sm font-medium text-gray-700 mb-1">
                                    Amount Received (ETB) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="pay-amount"
                                    v-model="paymentForm.amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="e.g. 5000.00"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    :class="{ 'border-red-400 ring-1 ring-red-400': paymentForm.errors.amount }"
                                />
                                <p v-if="paymentForm.errors.amount" class="mt-1 text-xs text-red-600">{{ paymentForm.errors.amount }}</p>
                            </div>

                            <!-- Payment Date -->
                            <div>
                                <label for="pay-date" class="block text-sm font-medium text-gray-700 mb-1">
                                    Payment Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="pay-date"
                                    v-model="paymentForm.payment_date"
                                    type="date"
                                    :max="new Date().toISOString().slice(0, 10)"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    :class="{ 'border-red-400 ring-1 ring-red-400': paymentForm.errors.payment_date }"
                                />
                                <p v-if="paymentForm.errors.payment_date" class="mt-1 text-xs text-red-600">{{ paymentForm.errors.payment_date }}</p>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label for="pay-notes" class="block text-sm font-medium text-gray-700 mb-1">Notes <span class="text-gray-400">(optional)</span></label>
                                <textarea
                                    id="pay-notes"
                                    v-model="paymentForm.notes"
                                    rows="2"
                                    placeholder="e.g. Paid via bank transfer, ref #12345"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                />
                                <p v-if="paymentForm.errors.notes" class="mt-1 text-xs text-red-600">{{ paymentForm.errors.notes }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="closePaymentModal"
                                    class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="paymentProcessing"
                                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60 transition-colors flex items-center gap-2"
                                >
                                    <svg v-if="paymentProcessing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    {{ paymentProcessing ? 'Recording...' : 'Confirm Payment' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </FinanceLayout>
</template>