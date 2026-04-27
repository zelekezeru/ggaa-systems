<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ChartPieIcon, ArrowTrendingUpIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({ stats: Object });
</script>

<template>
    <Head title="Firm Reports" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $t('reports') }}</h1>
                    <p class="text-sm text-gray-500">{{ $t('reports_desc') }}</p>
                </div>
                <button class="flex items-center space-x-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg font-semibold hover:bg-gray-50 transition shadow-sm">
                    <ArrowTrendingUpIcon class="h-5 w-5 text-gray-400" />
                    <span>{{ $t('export_pdf') }}</span>
                </button>
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
                <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm border border-gray-200 sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">{{ $t('active_clients') }}</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ stats.total_active_clients }}</dd>
                </div>
                <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm border border-gray-200 sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">{{ $t('tasks_completed_mtd') }}</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-blue-600">{{ stats.tasks_completed_mtd }}</dd>
                </div>
                <div class="overflow-hidden rounded-xl bg-red-50 px-4 py-5 shadow-sm border border-red-100 sm:p-6">
                    <dt class="truncate text-sm font-medium text-red-800">{{ $t('waiting_on_client') }}</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-red-600">{{ stats.tasks_waiting_client }}</dd>
                </div>
            </dl>

            <div class="bg-gradient-to-r from-slate-900 to-blue-900 rounded-2xl shadow-xl p-8 mb-8 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center space-x-3 mb-4">
                        <ChartPieIcon class="h-8 w-8 text-blue-400" />
                        <h2 class="text-xl font-bold">{{ $t('system_intelligence_insights') }}</h2>
                    </div>
                    <ul class="space-y-3 text-blue-100">
                        <li class="flex items-start">
                            <span class="mr-2 text-yellow-400">⚠️</span>
                            <span><strong>Bottleneck Detected:</strong> 45% of pending VAT reports are stuck waiting on client document uploads in the Hawassa branch.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-2 text-green-400">📈</span>
                            <span><strong>Positive Trend:</strong> Payroll processing time has decreased by 1.2 days on average since implementing the new capacity point system.</span>
                        </li>
                    </ul>
                </div>
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>
            </div>
        </div>
    </AdminLayout>
</template>