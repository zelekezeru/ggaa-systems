<script setup>
import { Head, router } from '@inertiajs/vue3';
import FinanceLayout from '@/Layouts/FinanceLayout.vue';
import { BanknotesIcon, ExclamationCircleIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

defineProps({
    kpis: Object,
    clientsBilling: Array,
});

// Inertia state management for the button loaders
const processingId = ref(null);

const sendReminder = (clientId) => {
    processingId.value = clientId;
    
    // Sends a POST request to trigger the Laravel Notification we built earlier
    router.post(route('finance.reminders.send', clientId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            processingId.value = null;
            // Optionally, fire a success toast here
        },
        onError: () => {
            processingId.value = null;
        }
    });
};
</script>

<template>
    <Head title="Finance & Billing" />

    <FinanceLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-2xl font-bold leading-6 text-gray-900 mb-8">Revenue & Receivables</h1>

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
                                <dt class="text-sm font-medium text-red-800 truncate">Overdue (> 30 Days)</dt>
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

            <div class="bg-white shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
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
                        <tr v-for="client in clientsBilling" :key="client.id">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-bold text-gray-900 sm:pl-6">
                                {{ client.company_name }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600">ETB {{ client.retainer_fee }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ client.last_payment_date || 'N/A' }}</td>
                            
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                <span v-if="client.payment_status === 'Paid'" class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Paid</span>
                                <span v-else class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Pending</span>
                            </td>

                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                <button v-if="client.payment_status === 'Paid'" class="text-blue-600 hover:text-blue-900 font-semibold">
                                    View Receipt
                                </button>
                                
                                <button 
                                    v-else 
                                    @click="sendReminder(client.id)"
                                    :disabled="processingId === client.id"
                                    class="inline-flex items-center rounded bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50"
                                >
                                    <svg v-if="processingId === client.id" class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ processingId === client.id ? 'Sending...' : 'Send Reminder' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </FinanceLayout>
</template>