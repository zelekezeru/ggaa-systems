<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import {
    UsersIcon,
    BuildingOfficeIcon,
    ClipboardDocumentListIcon,
    FireIcon,
    SparklesIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    employees:       { type: Array,  required: true },
    branches:        { type: Array,  required: true },
    unassignedTasks: { type: Array,  required: true },
    suggestions:     { type: Object, required: true },
    maxCapacity:     { type: Number, required: true },
    thresholds:      { type: Object, required: true },
});

const branchFilter = ref('all');

const filteredEmployees = computed(() =>
    branchFilter.value === 'all'
        ? props.employees
        : props.employees.filter(e => e.branch_id === Number(branchFilter.value))
);

const summary = computed(() => {
    const total = props.employees.length;
    const available = props.employees.filter(e => e.status === 'available').length;
    const balanced = props.employees.filter(e => e.status === 'balanced').length;
    const overloaded = props.employees.filter(e => e.status === 'overloaded').length;
    const totalRemaining = props.employees.reduce((s, e) => s + e.remaining, 0);
    return { total, available, balanced, overloaded, totalRemaining };
});

function statusColor(status) {
    return {
        available: 'bg-green-500',
        balanced: 'bg-yellow-500',
        overloaded: 'bg-red-500',
    }[status] || 'bg-slate-300';
}

function statusBg(status) {
    return {
        available: 'bg-green-50 dark:bg-green-900/10 ring-green-200 dark:ring-green-800/40',
        balanced: 'bg-yellow-50 dark:bg-yellow-900/10 ring-yellow-200 dark:ring-yellow-800/40',
        overloaded: 'bg-red-50 dark:bg-red-900/10 ring-red-200 dark:ring-red-800/40',
    }[status] || '';
}

function statusLabel(status) {
    return { available: 'Available', balanced: 'Balanced', overloaded: 'Overloaded' }[status] || status;
}

function quickAssign(taskId, employeeId) {
    if (!confirm('Assign this task to the selected employee?')) return;
    router.post(route('tasks.assign', taskId), { employee_id: employeeId }, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Team Availability" />
    <AdminLayout>
        <div class="p-4 sm:p-6 lg:p-8 space-y-6">
            <PageHeader title="Team Availability" subtitle="Who is free, who is hot, and where the next task should land." />

            <!-- Summary cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-xl bg-white dark:bg-slate-800 p-5 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Available</p>
                        <UsersIcon class="h-5 w-5 text-green-500" />
                    </div>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ summary.available }}</p>
                    <p class="text-xs text-slate-500 mt-1">of {{ summary.total }} employees</p>
                </div>
                <div class="rounded-xl bg-white dark:bg-slate-800 p-5 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Balanced</p>
                        <SparklesIcon class="h-5 w-5 text-yellow-500" />
                    </div>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ summary.balanced }}</p>
                    <p class="text-xs text-slate-500 mt-1">healthy workload</p>
                </div>
                <div class="rounded-xl bg-white dark:bg-slate-800 p-5 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Overloaded</p>
                        <FireIcon class="h-5 w-5 text-red-500" />
                    </div>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ summary.overloaded }}</p>
                    <p class="text-xs text-slate-500 mt-1">consider rebalancing</p>
                </div>
                <div class="rounded-xl bg-white dark:bg-slate-800 p-5 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Free Capacity</p>
                        <ClipboardDocumentListIcon class="h-5 w-5 text-blue-500" />
                    </div>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ summary.totalRemaining }}</p>
                    <p class="text-xs text-slate-500 mt-1">complexity points across team</p>
                </div>
            </div>

            <!-- Branch utilization strip -->
            <div class="rounded-xl bg-white dark:bg-slate-800 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center gap-2">
                    <BuildingOfficeIcon class="h-5 w-5 text-slate-400" />
                    <h2 class="font-semibold text-slate-900 dark:text-white">Branch Utilization</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-5">
                    <Link
                        v-for="b in branches"
                        :key="b.id"
                        :href="route('super-admin.branches.show', b.id)"
                        class="block rounded-lg p-4 ring-1 ring-slate-100 dark:ring-slate-700 hover:ring-blue-300 dark:hover:ring-blue-600 hover:shadow-md transition-all bg-slate-50/50 dark:bg-slate-700/20"
                    >
                        <div class="flex items-baseline justify-between">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ b.name }}</p>
                            <span class="text-xs text-slate-400">{{ b.manager || 'No manager' }}</span>
                        </div>
                        <div class="mt-3">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-slate-500">{{ b.total_employees }} employees</span>
                                <span class="font-semibold" :class="b.utilization_pct >= 80 ? 'text-red-600 dark:text-red-400' : b.utilization_pct >= 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400'">
                                    {{ b.utilization_pct }}%
                                </span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="b.utilization_pct >= 80 ? 'bg-red-500' : b.utilization_pct >= 60 ? 'bg-yellow-500' : 'bg-green-500'"
                                    :style="{ width: b.utilization_pct + '%' }"
                                />
                            </div>
                            <p v-if="b.employees_at_capacity > 0" class="text-[11px] text-slate-500 mt-1.5">
                                ⚠️ {{ b.employees_at_capacity }} at/near cap
                            </p>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Employee load grid -->
            <div class="rounded-xl bg-white dark:bg-slate-800 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-2 flex-wrap">
                    <div class="flex items-center gap-2">
                        <UsersIcon class="h-5 w-5 text-slate-400" />
                        <h2 class="font-semibold text-slate-900 dark:text-white">Employee Load</h2>
                    </div>
                    <select
                        v-model="branchFilter"
                        class="text-sm rounded-lg border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                    >
                        <option value="all">All branches</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    <div
                        v-for="emp in filteredEmployees"
                        :key="emp.id"
                        class="rounded-lg p-4 ring-1 transition-shadow"
                        :class="statusBg(emp.status)"
                    >
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-sm font-bold text-blue-700 dark:text-blue-300">
                                {{ emp.name?.charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white truncate">{{ emp.name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ emp.branch_name }}</p>
                            </div>
                            <span
                                class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full font-bold text-white"
                                :class="statusColor(emp.status)"
                            >
                                {{ statusLabel(emp.status) }}
                            </span>
                        </div>

                        <div class="mt-3">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-500">Load: {{ emp.current_load }} / {{ maxCapacity }} pts</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ emp.capacity_percent }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-white dark:bg-slate-800 overflow-hidden ring-1 ring-slate-200 dark:ring-slate-600">
                                <div class="h-full rounded-full" :class="statusColor(emp.status)" :style="{ width: emp.capacity_percent + '%' }" />
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between text-xs">
                            <span class="text-slate-500">Performance: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ emp.monthly_score }}</span></span>
                            <span class="text-slate-500">Free: <span class="font-semibold text-green-600 dark:text-green-400">{{ emp.remaining }} pts</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unassigned task suggestions -->
            <div class="rounded-xl bg-white dark:bg-slate-800 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center gap-2">
                    <ClipboardDocumentListIcon class="h-5 w-5 text-slate-400" />
                    <h2 class="font-semibold text-slate-900 dark:text-white">Unassigned Tasks · Smart Suggestions</h2>
                </div>
                <div v-if="unassignedTasks.length === 0" class="p-8 text-center text-slate-400">
                    🎉 No unassigned tasks right now.
                </div>
                <div v-else class="divide-y divide-slate-100 dark:divide-slate-700">
                    <div v-for="task in unassignedTasks" :key="task.id" class="p-5">
                        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    <span class="mr-1">{{ task.risk_level }}</span>
                                    {{ task.template_name }}
                                </p>
                                <p class="text-sm text-slate-500">
                                    {{ task.client_name }} · Complexity {{ task.complexity }} · Due {{ task.due_date }}
                                </p>
                            </div>
                        </div>
                        <div v-if="suggestions[task.id]?.length" class="flex flex-wrap gap-2">
                            <button
                                v-for="cand in suggestions[task.id]"
                                :key="cand.id"
                                @click="quickAssign(task.id, cand.id)"
                                class="group inline-flex items-center gap-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 px-3 py-2 text-sm font-medium text-blue-700 dark:text-blue-300 transition-colors ring-1 ring-blue-200 dark:ring-blue-800/40"
                            >
                                <span class="font-semibold">{{ cand.name }}</span>
                                <span class="text-[11px] text-blue-500 dark:text-blue-400">
                                    {{ cand.remaining }} pts free · {{ cand.monthly_score }} score
                                </span>
                                <ArrowRightIcon class="h-3.5 w-3.5 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                        </div>
                        <p v-else class="text-sm text-amber-600 dark:text-amber-400">
                            ⚠️ No employee in this branch has enough remaining capacity. Consider rebalancing or hiring.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
