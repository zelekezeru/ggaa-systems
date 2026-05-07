<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useI18n } from 'vue-i18n';
import {
    UsersIcon, BuildingOfficeIcon, CheckCircleIcon,
    ExclamationTriangleIcon, ClockIcon, DocumentTextIcon,
    BanknotesIcon, ChartBarIcon, BriefcaseIcon,
    FolderOpenIcon, ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    kpis:           Object,
    branches:       Array,
    employees:      Array,
    taskBreakdown:  Array,
    urgentTasks:    Array,
    pendingLedgers: Array,
});

// ── Helpers ──────────────────────────────────────────────────────────────────
const taskCompletionRate = computed(() => {
    if (!props.kpis.tasks_total) return 0;
    return Math.round((props.kpis.tasks_done / props.kpis.tasks_total) * 100);
});

const fmtEtb = (v) => new Intl.NumberFormat('en-ET', {
    style: 'currency', currency: 'ETB', minimumFractionDigits: 0, maximumFractionDigits: 0,
}).format(v ?? 0);

const getCapacityColor = (pts) => {
    const pct = (pts / 30) * 100;
    if (pct >= 95) return { bar: 'bg-rose-500',   badge: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' };
    if (pct >= 80) return { bar: 'bg-amber-400',  badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' };
    return           { bar: 'bg-emerald-500',      badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' };
};

const getComplianceColor = (rate) => {
    if (rate >= 85) return 'text-emerald-600 dark:text-emerald-400';
    if (rate >= 60) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
};

const getBranchBarColor = (rate) => {
    if (rate >= 85) return 'bg-emerald-500';
    if (rate >= 60) return 'bg-amber-400';
    return 'bg-rose-500';
};

const daysOverdue = (d) => {
    const diff = Math.floor((Date.now() - new Date(d)) / 86400000);
    return diff > 0 ? diff : 0;
};

// Donut chart arc helpers
const PIE_R = 36;
const PIE_C = 2 * Math.PI * PIE_R;
const pieR  = PIE_R; // exposed to template as non-reactive const (safe for static use)

const donutSegments = computed(() => {
    const total = props.taskBreakdown.reduce((s, x) => s + x.count, 0);
    if (!total) return [];
    let offset = 0;
    return props.taskBreakdown.map(item => {
        const pct   = item.count / total;
        const dash  = pct * PIE_C;
        const gap   = PIE_C - dash;
        const seg   = { ...item, dasharray: `${dash} ${gap}`, dashoffset: -offset * PIE_C, pct: Math.round(pct * 100) };
        offset += pct;
        return seg;
    });
});
</script>

<template>
    <Head title="Dashboard — GGAA" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 max-w-screen-2xl mx-auto space-y-8">

            <!-- ── Page Header ─────────────────────────────────────────────── -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $t('firm_overview') }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $t('dashboard_subtitle') }}</p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <Link :href="route('ledger.index')" class="px-3 py-2 text-xs font-semibold rounded-lg bg-slate-800 dark:bg-slate-700 text-white hover:bg-slate-700 dark:hover:bg-slate-600 transition-colors flex items-center gap-1.5">
                        <BanknotesIcon class="h-3.5 w-3.5" />{{ $t('ledger') }}
                    </Link>
                    <Link :href="route('super-admin.reports')" class="px-3 py-2 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors flex items-center gap-1.5">
                        <ChartBarIcon class="h-3.5 w-3.5" />{{ $t('reports') }}
                    </Link>
                </div>
            </div>

            <!-- ── KPI Grid ────────────────────────────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">

                <!-- Active Clients -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 rounded-xl bg-blue-50 dark:bg-blue-900/30">
                            <UsersIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Clients</span>
                    </div>
                    <p class="text-3xl font-black text-slate-900 dark:text-white">{{ kpis.active_clients }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ kpis.total_clients }} total</p>
                </div>

                <!-- Staff -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 rounded-xl bg-violet-50 dark:bg-violet-900/30">
                            <BriefcaseIcon class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                        </div>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Staff</span>
                    </div>
                    <p class="text-3xl font-black text-slate-900 dark:text-white">{{ kpis.total_staff }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">employees</p>
                </div>

                <!-- Task Completion -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/30">
                            <CheckCircleIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Tasks</span>
                    </div>
                    <p class="text-3xl font-black text-slate-900 dark:text-white">{{ taskCompletionRate }}%</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ kpis.tasks_done }}/{{ kpis.tasks_total }} done</p>
                </div>

                <!-- Overdue Tasks -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 rounded-xl bg-rose-50 dark:bg-rose-900/30">
                            <ExclamationTriangleIcon class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                        </div>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Overdue</span>
                    </div>
                    <p class="text-3xl font-black" :class="kpis.tasks_overdue > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white'">
                        {{ kpis.tasks_overdue }}
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ kpis.tasks_waiting }} waiting</p>
                </div>

                <!-- Ledger Review Queue -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-900/30">
                            <DocumentTextIcon class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Ledger</span>
                    </div>
                    <p class="text-3xl font-black" :class="kpis.ledger_pending > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'">
                        {{ kpis.ledger_pending }}
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ kpis.ledger_verified }} verified</p>
                </div>

                <!-- Active Projects -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/30">
                            <FolderOpenIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Projects</span>
                    </div>
                    <p class="text-3xl font-black" :class="kpis.projects_overdue > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white'">
                        {{ kpis.projects_active }}
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ kpis.projects_overdue }} overdue</p>
                </div>
            </div>

            <!-- ── Middle Row: Task Donut + Branches ─────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Task Status Donut Chart -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 flex flex-col">
                    <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-4">Task Status Breakdown</h2>
                    <div class="flex items-center gap-6 flex-1">
                        <!-- Donut SVG with centered label overlay -->
                        <div class="relative shrink-0 w-28 h-28">
                            <svg viewBox="0 0 88 88" class="w-full h-full -rotate-90">
                                <circle cx="44" cy="44" :r="pieR" fill="none" stroke="#e5e7eb" stroke-width="10" />
                                <circle
                                    v-for="(seg, i) in donutSegments" :key="i"
                                    cx="44" cy="44" :r="pieR"
                                    fill="none" :stroke="seg.color" stroke-width="10"
                                    :stroke-dasharray="seg.dasharray"
                                    :stroke-dashoffset="seg.dashoffset"
                                    stroke-linecap="butt"
                                />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="text-center">
                                    <p class="text-xl font-black text-slate-800 dark:text-white leading-none">{{ kpis.tasks_total }}</p>
                                    <p class="text-[10px] text-slate-400 leading-none mt-0.5">tasks</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2 min-w-0">
                            <div v-for="seg in donutSegments" :key="seg.label" class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ background: seg.color }"></span>
                                <span class="text-xs text-slate-600 dark:text-slate-400 truncate">{{ seg.label }}</span>
                                <span class="ml-auto text-xs font-bold text-slate-800 dark:text-slate-200">{{ seg.count }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Verified Ledger Sales -->
                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Verified Sales Revenue</p>
                        <div class="flex items-center gap-2">
                            <BanknotesIcon class="h-4 w-4 text-emerald-500" />
                            <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">
                                {{ fmtEtb(kpis.ledger_sales_etb) }}
                            </span>
                        </div>
                        <Link :href="route('ledger.index')" class="mt-1 text-xs text-blue-500 hover:underline flex items-center gap-1">
                            View all ledgers <ArrowRightIcon class="h-3 w-3" />
                        </Link>
                    </div>
                </div>

                <!-- Branch Health Grid -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200">Branch Health</h2>
                        <Link :href="route('super-admin.branches')" class="text-xs text-blue-500 hover:underline flex items-center gap-1">
                            Manage <ArrowRightIcon class="h-3 w-3" />
                        </Link>
                    </div>
                    <div class="space-y-4">
                        <div v-for="branch in branches" :key="branch.id">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2">
                                    <BuildingOfficeIcon class="h-4 w-4 text-slate-400" />
                                    <Link :href="route('super-admin.branches.show', branch.id)" class="text-sm font-semibold text-slate-800 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                        {{ branch.name }}
                                    </Link>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                    <span>{{ branch.client_count }} clients</span>
                                    <span>{{ branch.staff_count }} staff</span>
                                    <span v-if="branch.overdue_tasks > 0" class="text-rose-500 font-semibold">
                                        {{ branch.overdue_tasks }} overdue
                                    </span>
                                    <span :class="getComplianceColor(branch.compliance_rate)" class="font-bold w-10 text-right">
                                        {{ branch.compliance_rate }}%
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-500"
                                    :class="getBranchBarColor(branch.compliance_rate)"
                                    :style="{ width: branch.compliance_rate + '%' }">
                                </div>
                            </div>
                        </div>
                        <p v-if="!branches.length" class="text-sm text-slate-400 text-center py-4">No branches found.</p>
                    </div>
                </div>
            </div>

            <!-- ── Bottom Row: Urgent Tasks + Staff + Pending Ledgers ──────── -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                <!-- Urgent / Overdue Tasks -->
                <div class="xl:col-span-1 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2">
                            <ExclamationTriangleIcon class="h-4 w-4 text-rose-500" />
                            <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200">Overdue Tasks</h2>
                        </div>
                        <Link :href="route('super-admin.tasks.index')" class="text-xs text-blue-500 hover:underline flex items-center gap-1">
                            All <ArrowRightIcon class="h-3 w-3" />
                        </Link>
                    </div>
                    <div v-if="urgentTasks.length" class="divide-y divide-slate-100 dark:divide-slate-700">
                        <div v-for="task in urgentTasks" :key="task.id" class="px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{{ task.title }}</p>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[140px]">
                                    {{ task.client?.company_name ?? '—' }}
                                </span>
                                <span class="text-xs font-bold text-rose-500 shrink-0">
                                    {{ daysOverdue(task.due_date) }}d overdue
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-5 py-8 text-center text-sm text-slate-400">
                        ✅ No overdue tasks — all clear!
                    </div>
                </div>

                <!-- Staff Capacity Ranking -->
                <div class="xl:col-span-1 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2">
                            <BriefcaseIcon class="h-4 w-4 text-violet-500" />
                            <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200">Staff Capacity</h2>
                        </div>
                        <Link :href="route('super-admin.staff')" class="text-xs text-blue-500 hover:underline flex items-center gap-1">
                            All <ArrowRightIcon class="h-3 w-3" />
                        </Link>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-700 overflow-y-auto max-h-72">
                        <div v-for="emp in employees" :key="emp.id" class="px-5 py-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate max-w-[140px]">{{ emp.name }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full font-bold shrink-0" :class="getCapacityColor(emp.capacity_points).badge">
                                    {{ emp.capacity_points }} pts
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-slate-100 dark:bg-slate-700 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full transition-all duration-500"
                                        :class="getCapacityColor(emp.capacity_points).bar"
                                        :style="{ width: Math.min((emp.capacity_points / 30) * 100, 100) + '%' }">
                                    </div>
                                </div>
                                <span class="text-[10px] text-slate-400 shrink-0 w-14 text-right">{{ emp.clients_count }} clients</span>
                            </div>
                        </div>
                        <p v-if="!employees.length" class="px-5 py-6 text-sm text-slate-400 text-center">No staff found.</p>
                    </div>
                </div>

                <!-- Pending Ledger Reviews -->
                <div class="xl:col-span-1 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2">
                            <ClockIcon class="h-4 w-4 text-amber-500" />
                            <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200">Pending Review</h2>
                        </div>
                        <Link :href="route('ledger.index')" class="text-xs text-blue-500 hover:underline flex items-center gap-1">
                            All <ArrowRightIcon class="h-3 w-3" />
                        </Link>
                    </div>
                    <div v-if="pendingLedgers.length" class="divide-y divide-slate-100 dark:divide-slate-700">
                        <Link
                            v-for="entry in pendingLedgers" :key="entry.id"
                            :href="route('ledger.show', entry.client_id)"
                            class="flex items-center justify-between px-5 py-3 hover:bg-amber-50 dark:hover:bg-amber-900/10 transition-colors group">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ entry.client?.company_name ?? '—' }}
                                </p>
                                <p class="text-xs text-slate-400">{{ entry.eth_month }} {{ entry.eth_year }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                    submitted
                                </span>
                                <ArrowRightIcon class="h-3.5 w-3.5 text-slate-300 group-hover:text-blue-500 transition-colors" />
                            </div>
                        </Link>
                    </div>
                    <div v-else class="px-5 py-8 text-center text-sm text-slate-400">
                        ✅ No ledgers awaiting review.
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>