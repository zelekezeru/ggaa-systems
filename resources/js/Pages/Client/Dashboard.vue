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
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:truncate sm:text-3xl">
                        Welcome back, {{ client.company_name }}
                    </h2>
                    <div class="mt-1 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
                        <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-slate-400">
                            TIN: {{ client.tin_number }}
                        </div>
                    </div>
                </div>
                <div class="mt-5 flex lg:ml-4 lg:mt-0">
                    <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-3 py-1 text-sm font-medium text-green-800 dark:text-green-400 border border-green-200 dark:border-green-800">
                        <CheckBadgeIcon class="-ml-1 mr-1.5 h-5 w-5 text-green-500" />
                        Fully Compliant
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-slate-700">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400 truncate">Upcoming Deadlines</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">{{ stats.upcoming_count }}</dd>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-slate-700">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400 truncate">Documents Needed</dt>
                        <dd class="mt-1 text-3xl font-semibold text-red-600 dark:text-red-400">{{ stats.missing_docs }}</dd>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-slate-700">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400 truncate">Monthly Retainer</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">ETB {{ stats.retainer }}</dd>
                    </div>
                </div>
            </div>

            <!-- Progress Section -->
            <div class="mb-8 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Compliance Progress</h3>
                    <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ stats.progress }}%</span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full shadow-[0_0_10px_rgba(37,99,235,0.4)] transition-all duration-1000" :style="{ width: stats.progress + '%' }"></div>
                </div>
                <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                    {{ stats.progress === 100 ? 'You are fully compliant! All tasks completed.' : 'Keep going! Complete your pending tasks to reach 100% compliance.' }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Current Tax Follow-up</h3>
                    <div v-if="pendingTasks.length" class="bg-white dark:bg-slate-800 shadow-sm rounded-2xl overflow-hidden border border-gray-100 dark:border-slate-700">
                        <ul role="list" class="divide-y divide-gray-200 dark:divide-slate-700">
                            <li v-for="task in pendingTasks" :key="task.id" class="p-4 hover:bg-gray-50 dark:hover:bg-slate-700/50 flex items-center justify-between transition-colors">
                                <div class="flex items-center">
                                    <div :class="[task.is_urgent ? 'bg-red-100 dark:bg-red-900/30' : 'bg-blue-100 dark:bg-blue-900/30', 'p-2 rounded-xl mr-4']">
                                        <ExclamationTriangleIcon v-if="task.is_urgent" class="h-6 w-6 text-red-600 dark:text-red-400" />
                                        <CloudArrowUpIcon v-else class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ task.template.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Due: {{ task.due_date }}</p>
                                    </div>
                                </div>
                                <Link :href="route('client.tasks.show', task.id)" class="rounded-xl bg-white dark:bg-slate-900 px-3 py-1.5 text-xs font-bold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 transition-all">
                                    Upload
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div v-else class="text-center py-12 bg-gray-50 dark:bg-slate-800/50 rounded-2xl border-2 border-dashed border-gray-300 dark:border-slate-700">
                        <p class="text-gray-500 dark:text-slate-400 font-medium">All caught up! No documents required right now.</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-blue-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 bg-blue-800 h-24 w-24 rounded-full opacity-20 group-hover:scale-150 transition-transform duration-700"></div>
                        <h4 class="font-bold text-lg mb-2 relative z-10">Need Help?</h4>
                        <p class="text-blue-100 text-sm mb-4 relative z-10">Message your assigned accountant directly.</p>
                        <Link :href="route('client.messages.index')" class="block w-full text-center py-2.5 px-4 bg-white text-blue-900 rounded-xl font-bold text-sm hover:bg-blue-50 transition shadow-sm relative z-10">
                            Open Chat
                        </Link>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-gray-100 dark:border-slate-700 shadow-sm">
                        <h4 class="font-bold text-gray-900 dark:text-white mb-4">Recent Documents</h4>
                        <ul class="space-y-3">
                            <li v-for="doc in stats.recent_docs" :key="doc.id" class="flex items-center text-sm group">
                                <PaperClipIcon class="h-4 w-4 text-gray-400 group-hover:text-blue-500 mr-2 transition-colors" />
                                <span class="text-gray-600 dark:text-slate-400 truncate flex-1">{{ doc.name }}</span>
                                <span class="text-gray-400 text-xs">{{ doc.date }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>