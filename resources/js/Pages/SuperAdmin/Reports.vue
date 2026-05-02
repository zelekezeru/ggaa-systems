<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ChartPieIcon, ArrowTrendingUpIcon, UsersIcon, BuildingOfficeIcon } from '@heroicons/vue/24/outline';
import { ref, computed } from 'vue';

const props = defineProps({
    stats:         Object,
    employees:     Array,
    taskBreakdown: Array,
    branches:      Array,
    filters:       Object,
});

// ── Month / Year filter ───────────────────────────────────────────────────────
const selectedMonth = ref(props.filters.month);
const selectedYear  = ref(props.filters.year);

const months = [
    { value: 1, label: 'January' }, { value: 2, label: 'February' },
    { value: 3, label: 'March' },   { value: 4, label: 'April' },
    { value: 5, label: 'May' },     { value: 6, label: 'June' },
    { value: 7, label: 'July' },    { value: 8, label: 'August' },
    { value: 9, label: 'September'},{ value: 10, label: 'October' },
    { value: 11, label: 'November'},{ value: 12, label: 'December' },
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: currentYear - 2019 }, (_, i) => currentYear - i);

const applyFilter = () => {
    router.get(route('super-admin.reports'), {
        month: selectedMonth.value,
        year:  selectedYear.value,
    }, { preserveScroll: true });
};

// ── Score helpers ─────────────────────────────────────────────────────────────
const scoreColor = (score) => {
    if (score >= 80) return 'text-green-600';
    if (score >= 60) return 'text-yellow-600';
    return 'text-red-600';
};

const scoreBadgeClass = (score) => {
    if (score >= 80) return 'bg-green-50 text-green-700 ring-green-600/20';
    if (score >= 60) return 'bg-yellow-50 text-yellow-700 ring-yellow-600/20';
    return 'bg-red-50 text-red-700 ring-red-600/20';
};

const scoreBarClass = (score) => {
    if (score >= 80) return 'bg-green-500';
    if (score >= 60) return 'bg-yellow-400';
    return 'bg-red-500';
};

// ── Task breakdown total ──────────────────────────────────────────────────────
const totalTasks = computed(() =>
    props.taskBreakdown.reduce((sum, item) => sum + item.count, 0)
);

const breakdownPercent = (count) =>
    totalTasks.value > 0 ? Math.round((count / totalTasks.value) * 100) : 0;

// ── Month label helper ────────────────────────────────────────────────────────
const selectedMonthLabel = computed(() =>
    months.find(m => m.value === props.filters.month)?.label ?? ''
);
</script>

<template>
    <Head title="Firm Reports" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8">

            <!-- Header -->
            <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('reports') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $t('reports_desc') }}</p>
                </div>

                <!-- Month / Year filter -->
                <div class="flex items-center gap-2">
                    <select
                        v-model="selectedMonth"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>

                    <select
                        v-model="selectedYear"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>

                    <button
                        @click="applyFilter"
                        class="rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 transition-colors"
                    >
                        Apply
                    </button>
                </div>
            </div>

            <!-- KPI Cards -->
            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
                <div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 px-4 py-5 shadow-sm border border-gray-200 dark:border-gray-700 sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('active_clients') }}</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ stats.total_active_clients }}</dd>
                </div>
                <div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 px-4 py-5 shadow-sm border border-gray-200 dark:border-gray-700 sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('tasks_completed_mtd') }} — {{ selectedMonthLabel }}</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-blue-600">{{ stats.tasks_completed_mtd }}</dd>
                </div>
                <div class="overflow-hidden rounded-xl bg-red-50 dark:bg-red-900/20 px-4 py-5 shadow-sm border border-red-100 dark:border-red-800 sm:p-6">
                    <dt class="truncate text-sm font-medium text-red-800 dark:text-red-400">{{ $t('waiting_on_client') }}</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-red-600">{{ stats.tasks_waiting_client }}</dd>
                </div>
            </dl>

            <!-- Two-column section: Leaderboard + Task Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

                <!-- ── Employee Performance Leaderboard (2/3 width) ── -->
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <UsersIcon class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">
                            Staff Performance — {{ selectedMonthLabel }} {{ filters.year }}
                        </h2>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow ring-1 ring-black ring-opacity-5 sm:rounded-xl overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800/60">
                                <tr>
                                    <th class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:pl-6">#</th>
                                    <th class="py-3 px-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Employee</th>
                                    <th class="py-3 px-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Branch</th>
                                    <th class="py-3 px-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Clients</th>
                                    <th class="py-3 px-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 w-40">Score</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                <tr
                                    v-if="employees.length === 0"
                                >
                                    <td colspan="5" class="py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                        No employee data for this period.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(employee, index) in employees"
                                    :key="employee.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                >
                                    <!-- Rank -->
                                    <td class="whitespace-nowrap py-3.5 pl-4 pr-3 text-sm sm:pl-6">
                                        <span
                                            v-if="index === 0"
                                            class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-400 text-yellow-900 text-xs font-bold"
                                        >1</span>
                                        <span
                                            v-else-if="index === 1"
                                            class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold"
                                        >2</span>
                                        <span
                                            v-else-if="index === 2"
                                            class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-300 text-orange-900 text-xs font-bold"
                                        >3</span>
                                        <span v-else class="text-gray-400 dark:text-gray-500 text-xs font-medium pl-1.5">{{ index + 1 }}</span>
                                    </td>

                                    <!-- Name -->
                                    <td class="whitespace-nowrap px-3 py-3.5 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ employee.name }}
                                    </td>

                                    <!-- Branch -->
                                    <td class="whitespace-nowrap px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400">
                                        {{ employee.branch.name }}
                                    </td>

                                    <!-- Clients -->
                                    <td class="whitespace-nowrap px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400">
                                        {{ employee.clients_count }}
                                    </td>

                                    <!-- Score bar -->
                                    <td class="whitespace-nowrap px-3 py-3.5 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset w-12 justify-center"
                                                :class="scoreBadgeClass(employee.score)"
                                            >{{ employee.score }}%</span>
                                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 min-w-[60px]">
                                                <div
                                                    class="h-2 rounded-full transition-all duration-500"
                                                    :class="scoreBarClass(employee.score)"
                                                    :style="{ width: employee.score + '%' }"
                                                />
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Right column: Task Breakdown + Branch Compliance ── -->
                <div class="space-y-6">

                    <!-- Task Status Breakdown -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <ChartPieIcon class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Task Breakdown</h3>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">{{ totalTasks }} tasks total this period</p>

                        <div class="space-y-3">
                            <div v-for="item in taskBreakdown" :key="item.label">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ item.label }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">{{ item.count }} ({{ breakdownPercent(item.count) }}%)</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                                    <div
                                        class="h-2 rounded-full transition-all duration-500"
                                        :class="item.color"
                                        :style="{ width: breakdownPercent(item.count) + '%' }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Branch Compliance -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <BuildingOfficeIcon class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Branch Compliance</h3>
                        </div>
                        <div class="space-y-3">
                            <div v-for="branch in branches" :key="branch.id">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ branch.name }}</span>
                                    <span class="font-bold ml-2" :class="branch.compliance_rate >= 80 ? 'text-green-600' : branch.compliance_rate >= 60 ? 'text-yellow-600' : 'text-red-600'">
                                        {{ branch.compliance_rate }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                                    <div
                                        class="h-2 rounded-full transition-all duration-500"
                                        :class="branch.compliance_rate >= 80 ? 'bg-green-500' : branch.compliance_rate >= 60 ? 'bg-yellow-400' : 'bg-red-500'"
                                        :style="{ width: branch.compliance_rate + '%' }"
                                    />
                                </div>
                            </div>
                            <p v-if="!branches.length" class="text-xs text-gray-400 dark:text-gray-500 text-center py-2">No branches yet.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Intelligence Insights banner (kept from original) -->
            <div class="bg-gradient-to-r from-slate-900 to-blue-900 rounded-2xl shadow-xl p-8 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center space-x-3 mb-4">
                        <ChartPieIcon class="h-8 w-8 text-blue-400" />
                        <h2 class="text-xl font-bold">{{ $t('system_intelligence_insights') }}</h2>
                    </div>
                    <ul class="space-y-3 text-blue-100">
                        <li class="flex items-start">
                            <span class="mr-2 text-yellow-400">⚠️</span>
                            <span><strong>Bottleneck Detected:</strong> {{ stats.tasks_waiting_client }} task(s) are currently stuck waiting on client document uploads.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-2 text-green-400">📈</span>
                            <span><strong>Month-to-Date:</strong> {{ stats.tasks_completed_mtd }} tasks completed in {{ selectedMonthLabel }} {{ filters.year }} across all branches.</span>
                        </li>
                    </ul>
                </div>
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20" />
            </div>
        </div>
    </AdminLayout>
</template>