<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ChartBarIcon, UsersIcon, BuildingOfficeIcon } from '@heroicons/vue/24/outline';

defineProps({
    branches: Array, // Array of branch objects with compliance stats
    employees: Array, // Array of employee objects with weighted capacity scores
});

// Helper to determine the color of the capacity bar based on workload
const getCapacityColor = (percentage) => {
    if (percentage >= 95) return 'bg-red-500';
    if (percentage >= 80) return 'bg-yellow-500';
    return 'bg-blue-600';
};
</script>

<template>
    <Head title="Super Admin Dashboard" />

    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">{{ $t('firm_overview') }}</h1>
                    <p class="mt-2 text-sm text-gray-700">{{ $t('dashboard_subtitle') }}</p>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div v-for="branch in branches" :key="branch.id" class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6 border border-gray-200">
                    <dt>
                        <div class="absolute rounded-md bg-blue-900 p-3">
                            <BuildingOfficeIcon class="h-6 w-6 text-white" aria-hidden="true" />
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-gray-500">{{ branch.name }}</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
                        <p class="text-2xl font-semibold text-gray-900">{{ branch.compliance_rate }}%</p>
                        <p class="ml-2 flex items-baseline text-sm font-semibold text-gray-500">
                            {{ $t('compliance_rate') }}
                        </p>
                        <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" :style="{ width: branch.compliance_rate + '%' }"></div>
                            </div>
                        </div>
                    </dd>
                </div>
            </div>

            <div class="mt-12">
                <h2 class="text-lg font-bold text-gray-900 mb-4">{{ $t('staff_capacity_ranking') }}</h2>
                <div class="bg-white shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">{{ $t('employee_info') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('branch') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('active_clients') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-1/3">{{ $t('capacity_load') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="employee in employees" :key="employee.id">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                    {{ employee.name }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ employee.branch.name }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ employee.clients_count }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 font-bold text-gray-700">{{ employee.capacity_points }}</span>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div 
                                                class="h-2.5 rounded-full" 
                                                :class="getCapacityColor((employee.capacity_points / 30) * 100)"
                                                :style="{ width: Math.min((employee.capacity_points / 30) * 100, 100) + '%' }">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>