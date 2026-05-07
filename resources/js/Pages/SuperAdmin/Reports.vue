<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
    ChartPieIcon, ArrowTrendingUpIcon, UsersIcon, BuildingOfficeIcon,
    CurrencyDollarIcon, PresentationChartLineIcon, RectangleGroupIcon,
    BanknotesIcon, DocumentChartBarIcon
} from '@heroicons/vue/24/outline';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    stats:            Object,
    finance:          Object,
    employees:        Array,
    taskBreakdown:    Array,
    projectBreakdown: Array,
    branches:         Array,
    filters:          Object,
});

// ── Month / Year filter ───────────────────────────────────────────────────────
const selectedMonth = ref(props.filters.month);
const selectedYear  = ref(props.filters.year);

const months = computed(() => [
    { value: 1, label: t('january') }, { value: 2, label: t('february') },
    { value: 3, label: t('march') },   { value: 4, label: t('april') },
    { value: 5, label: t('may') },     { value: 6, label: t('june') },
    { value: 7, label: t('july') },    { value: 8, label: t('august') },
    { value: 9, label: t('september') },{ value: 10, label: t('october') },
    { value: 11, label: t('november') },{ value: 12, label: t('december') },
]);

const currentYear = new Date().getFullYear();
const years = Array.from({ length: currentYear - 2019 }, (_, i) => currentYear - i);

const applyFilter = () => {
    router.get(route('super-admin.reports'), {
        month: selectedMonth.value,
        year:  selectedYear.value,
    }, { preserveScroll: true });
};

// ── Helpers ─────────────────────────────────────────────────────────────
const formatCurrency = (val) => new Intl.NumberFormat('en-ET', { style: 'currency', currency: 'ETB' }).format(val || 0);

const scoreBadgeClass = (score) => {
    if (score >= 80) return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800';
    if (score >= 60) return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800';
    return 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400 border border-rose-200 dark:border-rose-800';
};

const scoreBarClass = (score) => {
    if (score >= 80) return 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]';
    if (score >= 60) return 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.5)]';
    return 'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.5)]';
};

const totalTasks = computed(() => props.taskBreakdown?.reduce((sum, item) => sum + item.count, 0) || 0);
const totalProjects = computed(() => props.projectBreakdown?.reduce((sum, item) => sum + item.count, 0) || 0);

const breakdownPercent = (count, total) => total > 0 ? Math.round((count / total) * 100) : 0;

const selectedMonthLabel = computed(() => months.value.find(m => m.value === props.filters.month)?.label ?? '');
</script>

<template>
    <Head :title="$t('system_intelligence')" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 max-w-[1600px] mx-auto space-y-8">

            <!-- Dynamic Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 transition-colors">
                <div class="flex items-center gap-5">
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 p-4 rounded-2xl shadow-lg shadow-indigo-500/30">
                        <DocumentChartBarIcon class="h-8 w-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ $t('system_intelligence') }}</h1>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">{{ $t('system_intelligence_desc', { month: selectedMonthLabel, year: filters.year }) }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-slate-900 p-2 rounded-2xl border border-slate-800 shadow-xl">
                    <select v-model="selectedMonth" class="rounded-xl border-none bg-transparent text-white font-bold text-sm focus:ring-0 cursor-pointer">
                        <option v-for="m in months" :key="m.value" :value="m.value" class="text-slate-900">{{ m.label }}</option>
                    </select>
                    <div class="h-6 w-px bg-slate-700"></div>
                    <select v-model="selectedYear" class="rounded-xl border-none bg-transparent text-white font-bold text-sm focus:ring-0 cursor-pointer">
                        <option v-for="y in years" :key="y" :value="y" class="text-slate-900">{{ y }}</option>
                    </select>
                    <button @click="applyFilter" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-5 py-2 shadow-md hover:shadow-lg transition-all active:scale-95">
                        {{ $t('analyze') }}
                    </button>
                </div>
            </div>

            <!-- Financial & Growth KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Finance: Invoiced -->
                <div class="bg-gradient-to-br from-white to-slate-50 dark:from-slate-800 dark:to-slate-800/80 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 bg-emerald-50 dark:bg-emerald-900/20 w-24 h-24 rounded-full group-hover:scale-150 transition-transform duration-700 ease-out"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="bg-emerald-100 dark:bg-emerald-900/40 p-2.5 rounded-xl">
                                <BanknotesIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ $t('invoiced_mtd') }}</span>
                        </div>
                        <div class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ formatCurrency(finance?.invoiced_mtd) }}</div>
                    </div>
                </div>

                <!-- Finance: Collected -->
                <div class="bg-gradient-to-br from-white to-slate-50 dark:from-slate-800 dark:to-slate-800/80 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 bg-blue-50 dark:bg-blue-900/20 w-24 h-24 rounded-full group-hover:scale-150 transition-transform duration-700 ease-out"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="bg-blue-100 dark:bg-blue-900/40 p-2.5 rounded-xl">
                                <CurrencyDollarIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ $t('collected_mtd') }}</span>
                        </div>
                        <div class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ formatCurrency(finance?.collected_mtd) }}</div>
                    </div>
                </div>

                <!-- Finance: Outstanding -->
                <div class="bg-gradient-to-br from-white to-slate-50 dark:from-slate-800 dark:to-slate-800/80 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 bg-rose-50 dark:bg-rose-900/20 w-24 h-24 rounded-full group-hover:scale-150 transition-transform duration-700 ease-out"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="bg-rose-100 dark:bg-rose-900/40 p-2.5 rounded-xl">
                                <ArrowTrendingUpIcon class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                            </div>
                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ $t('global_outstanding') }}</span>
                        </div>
                        <div class="text-3xl font-black text-rose-600 dark:text-rose-400 tracking-tight">{{ formatCurrency(finance?.outstanding_total) }}</div>
                    </div>
                </div>

                <!-- Base Metric -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 p-6 rounded-3xl shadow-lg relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 bg-indigo-500/20 w-24 h-24 rounded-full group-hover:scale-150 transition-transform duration-700 ease-out"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="bg-indigo-500/20 p-2.5 rounded-xl">
                                <UsersIcon class="h-5 w-5 text-indigo-400" />
                            </div>
                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ $t('active_clients') }}</span>
                        </div>
                        <div class="text-4xl font-black text-white tracking-tight">{{ stats.total_active_clients }}</div>
                    </div>
                </div>
            </div>

            <!-- Operations Breakdown Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Task Operations -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 p-8">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <ChartPieIcon class="h-6 w-6 text-indigo-500" /> {{ $t('task_velocity') }}
                            </h2>
                            <p class="text-sm font-medium text-slate-500 mt-1">{{ totalTasks }} {{ $t('total_tasks_tracked') }}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ $t('completed_mtd') }}</div>
                            <div class="text-3xl font-black text-indigo-600 dark:text-indigo-400">{{ stats.tasks_completed_mtd }}</div>
                        </div>
                    </div>
                    
                    <div class="space-y-5">
                        <div v-for="item in taskBreakdown" :key="item.label" class="relative">
                            <div class="flex justify-between text-sm font-bold mb-2">
                                <span class="text-slate-700 dark:text-slate-200">{{ item.label }}</span>
                                <span class="text-slate-500">{{ item.count }} <span class="text-slate-400 text-xs ml-1">({{ breakdownPercent(item.count, totalTasks) }}%)</span></span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-700/50 rounded-full h-3 overflow-hidden shadow-inner">
                                <div class="h-full rounded-full transition-all duration-1000 ease-out relative overflow-hidden" :class="item.color" :style="{ width: breakdownPercent(item.count, totalTasks) + '%' }">
                                    <div class="absolute inset-0 bg-white/20 w-full h-full -translate-x-full animate-[shimmer_2s_infinite]"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Operations -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 p-8">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <RectangleGroupIcon class="h-6 w-6 text-blue-500" /> {{ $t('project_pipeline') }}
                            </h2>
                            <p class="text-sm font-medium text-slate-500 mt-1">{{ totalProjects }} {{ $t('active_system_projects') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div v-for="p in projectBreakdown" :key="p.label" class="bg-slate-50 dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-100 dark:border-slate-700">
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ p.label }}</div>
                            <div class="flex items-end gap-2">
                                <span class="text-3xl font-black text-slate-900 dark:text-white">{{ p.count }}</span>
                                <span class="text-sm font-bold text-slate-400 mb-1">({{ breakdownPercent(p.count, totalProjects) }}%)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Branch Compliance Mini -->
                    <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-700">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <BuildingOfficeIcon class="h-4 w-4" /> {{ $t('branch_task_compliance') }}
                        </h3>
                        <div class="flex flex-wrap gap-3">
                            <div v-for="branch in branches" :key="branch.id" class="flex items-center gap-2 px-3 py-1.5 rounded-lg border" :class="branch.compliance_rate >= 80 ? 'bg-emerald-50 border-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:border-emerald-800' : 'bg-amber-50 border-amber-100 text-amber-700 dark:bg-amber-900/20 dark:border-amber-800'">
                                <span class="text-xs font-bold">{{ branch.name }}</span>
                                <span class="text-[10px] font-black opacity-70">{{ branch.compliance_rate }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leaderboard -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                <div class="p-8 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <PresentationChartLineIcon class="h-6 w-6 text-purple-500" /> {{ $t('staff_performance_matrix') }}
                    </h2>
                    <p class="text-sm font-medium text-slate-500 mt-1">{{ $t('staff_performance_matrix_desc') }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest w-16 text-center">{{ $t('rank') }}</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">{{ $t('employee') }}</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">{{ $t('branch') }}</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-center">{{ $t('clients_assig') }}</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest w-64">{{ $t('performance_score') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-if="!employees || employees.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-medium">{{ $t('no_performance_data') }}</td>
                            </tr>
                            <tr v-for="(employee, index) in employees" :key="employee.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors group">
                                <!-- Rank -->
                                <td class="px-6 py-5 text-center">
                                    <div v-if="index === 0" class="mx-auto flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-yellow-300 to-yellow-500 text-white font-black shadow-lg shadow-yellow-500/30 ring-2 ring-white dark:ring-slate-800 scale-110">1</div>
                                    <div v-else-if="index === 1" class="mx-auto flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-slate-300 to-slate-400 text-white font-black shadow-lg shadow-slate-400/30 ring-2 ring-white dark:ring-slate-800">2</div>
                                    <div v-else-if="index === 2" class="mx-auto flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-amber-600 to-amber-700 text-white font-black shadow-lg shadow-amber-600/30 ring-2 ring-white dark:ring-slate-800">3</div>
                                    <div v-else class="text-sm font-black text-slate-400">{{ index + 1 }}</div>
                                </td>
                                
                                <td class="px-6 py-5">
                                    <div class="font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ employee.name }}</div>
                                </td>
                                
                                <td class="px-6 py-5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ employee.branch?.name || '—' }}</span>
                                </td>
                                
                                <td class="px-6 py-5 text-center font-black text-slate-700 dark:text-slate-200">
                                    {{ employee.clients_count }}
                                </td>
                                
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-full bg-slate-100 dark:bg-slate-700/50 rounded-full h-2.5 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-1000 ease-out" :class="scoreBarClass(employee.score)" :style="{ width: employee.score + '%' }"></div>
                                        </div>
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-black shadow-sm" :class="scoreBadgeClass(employee.score)">
                                            {{ employee.score }}%
                                        </span>
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

<style>
@keyframes shimmer {
    100% { transform: translateX(100%); }
}
</style>