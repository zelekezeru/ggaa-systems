<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import { CheckBadgeIcon, ExclamationTriangleIcon, CloudArrowUpIcon, PaperClipIcon } from '@heroicons/vue/24/solid';

defineProps({
    client: Object,
    pendingTasks: Array,
    stats: Object,
});
</script>

<template>
    <Head title="Client Portal" />

    <ClientLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="md:flex md:items-center md:justify-between mb-8">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl">
                        Welcome back, {{ client.company_name }}
                    </h2>
                    <div class="mt-1 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            TIN: {{ client.tin_number }}
                        </div>
                    </div>
                </div>
                <div class="mt-5 flex lg:ml-4 lg:mt-0">
                    <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                        <CheckBadgeIcon class="-ml-1 mr-1.5 h-5 w-5 text-green-500" />
                        Fully Compliant
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-100">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Upcoming Deadlines</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.upcoming_count }}</dd>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-100">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Documents Needed</dt>
                        <dd class="mt-1 text-3xl font-semibold text-red-600">{{ stats.missing_docs }}</dd>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-100">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Monthly Retainer</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">ETB {{ stats.retainer }}</dd>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-4">
                    <h3 class="text-lg font-bold text-gray-900">Current Tax Follow-up</h3>
                    <div v-if="pendingTasks.length" class="bg-white shadow rounded-lg overflow-hidden border border-gray-100">
                        <ul role="list" class="divide-y divide-gray-200">
                            <li v-for="task in pendingTasks" :key="task.id" class="p-4 hover:bg-gray-50 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div :class="[task.is_urgent ? 'bg-red-100' : 'bg-blue-100', 'p-2 rounded-lg mr-4']">
                                        <ExclamationTriangleIcon v-if="task.is_urgent" class="h-6 w-6 text-red-600" />
                                        <CloudArrowUpIcon v-else class="h-6 w-6 text-blue-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">{{ task.template.name }}</p>
                                        <p class="text-xs text-gray-500">Due: {{ task.due_date }}</p>
                                    </div>
                                </div>
                                <Link :href="route('client.tasks.show', task.id)" class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                    Upload
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                        <p class="text-gray-500">All caught up! No documents required right now.</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-blue-900 rounded-xl p-6 text-white shadow-lg">
                        <h4 class="font-bold text-lg mb-2">Need Help?</h4>
                        <p class="text-blue-100 text-sm mb-4">Message your assigned accountant directly.</p>
                        <Link :href="route('client.messages.index')" class="block w-full text-center py-2 px-4 bg-white text-blue-900 rounded-lg font-bold text-sm hover:bg-blue-50 transition">
                            Open Chat
                        </Link>
                    </div>

                    <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
                        <h4 class="font-bold text-gray-900 mb-4">Recent Documents</h4>
                        <ul class="space-y-3">
                            <li v-for="doc in stats.recent_docs" :key="doc.id" class="flex items-center text-sm">
                                <PaperClipIcon class="h-4 w-4 text-gray-400 mr-2" />
                                <span class="text-gray-600 truncate flex-1">{{ doc.name }}</span>
                                <span class="text-gray-400 text-xs">{{ doc.date }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>