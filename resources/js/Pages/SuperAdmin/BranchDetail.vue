<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import {
    BuildingOfficeIcon,
    UsersIcon,
    UserGroupIcon,
    ClipboardDocumentListIcon,
    ExclamationTriangleIcon,
    ArrowLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    branch:      { type: Object, required: true },
    employees:   { type: Array,  required: true },
    clients:     { type: Array,  required: true },
    tasks:       { type: Array,  required: true },
    stats:       { type: Object, required: true },
    maxCapacity: { type: Number, required: true },
});

const tab = ref('overview');

const taskStatusColor = (s) => ({
    'To Do':              'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    'In Review':          'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    'Waiting on Client':  'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    'Done':               'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
}[s] || 'bg-slate-100 text-slate-700');

const sortedEmployees = computed(() => [...props.employees].sort((a, b) => b.capacity_percent - a.capacity_percent));

function quickAssign(taskId) {
    const eligible = props.employees.filter(e => e.remaining > 0);
    if (!eligible.length) {
        alert('No employees in this branch have remaining capacity. Consider rebalancing or hiring.');
        return;
    }
    const choices = eligible.map(e => `${e.id}: ${e.name} (${e.remaining} pts free)`).join('\n');
    const sel = prompt(`Assign to which employee?\n\n${choices}\n\nEnter the employee ID:`);
    const empId = Number(sel);
    if (!empId || !eligible.some(e => e.id === empId)) return;
    router.post(route('tasks.assign', taskId), { employee_id: empId }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Branch · ${branch.name}`" />
    <AdminLayout>
        <div class="p-4 sm:p-6 lg:p-8 space-y-6">
            <Link :href="route('super-admin.branches')" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white mb-2">
                <ArrowLeftIcon class="h-4 w-4" /> Back to branches
            </Link>

            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white">
                        <BuildingOfficeIcon class="h-8 w-8" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">{{ branch.name }}</h1>
                        <p class="text-slate-500 dark:text-slate-400">
                            {{ branch.location || 'No location set' }}
                            <span v-if="branch.manager"> · Manager: <span class="font-semibold">{{ branch.manager.name }}</span></span>
                        </p>
                    </div>
                </div>
                <span
                    class="px-3 py-1 rounded-full text-xs uppercase tracking-wider font-bold"
                    :class="branch.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                >
                    {{ branch.is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="rounded-xl bg-white dark:bg-slate-800 p-4 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Team</p>
                        <UsersIcon class="h-5 w-5 text-slate-400" />
                    </div>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ stats.total_employees }}</p>
                </div>
                <div class="rounded-xl bg-white dark:bg-slate-800 p-4 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm">
                    <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Utilization</p>
                    <p class="mt-2 text-3xl font-bold" :class="stats.utilization_pct >= 80 ? 'text-red-600 dark:text-red-400' : stats.utilization_pct >= 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400'">
                        {{ stats.utilization_pct }}%
                    </p>
                    <p class="text-xs text-slate-500">{{ stats.total_load }} / {{ stats.max_capacity_total }} pts</p>
                </div>
                <div class="rounded-xl bg-white dark:bg-slate-800 p-4 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm">
                    <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Clients</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ clients.length }}</p>
                </div>
                <div class="rounded-xl bg-white dark:bg-slate-800 p-4 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm">
                    <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Unassigned</p>
                    <p class="mt-2 text-3xl font-bold" :class="stats.unassigned_tasks > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'">
                        {{ stats.unassigned_tasks }}
                    </p>
                </div>
                <div class="rounded-xl bg-white dark:bg-slate-800 p-4 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm">
                    <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Overdue</p>
                    <p class="mt-2 text-3xl font-bold" :class="stats.overdue_tasks > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white'">
                        {{ stats.overdue_tasks }}
                    </p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="rounded-xl bg-white dark:bg-slate-800 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm overflow-hidden">
                <div class="flex border-b border-slate-100 dark:border-slate-700">
                    <button
                        v-for="t in [
                            { id: 'overview', name: 'Team', icon: UsersIcon },
                            { id: 'tasks', name: 'Tasks', icon: ClipboardDocumentListIcon },
                            { id: 'clients', name: 'Clients', icon: UserGroupIcon },
                        ]"
                        :key="t.id"
                        @click="tab = t.id"
                        class="flex items-center gap-2 px-5 py-3 text-sm font-medium transition"
                        :class="tab === t.id
                            ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400'
                            : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                    >
                        <component :is="t.icon" class="h-4 w-4" />
                        {{ t.name }}
                    </button>
                </div>

                <!-- Team -->
                <div v-if="tab === 'overview'" class="p-5">
                    <div v-if="employees.length === 0" class="text-center py-8 text-slate-400">No employees in this branch.</div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        <div v-for="emp in sortedEmployees" :key="emp.id" class="rounded-lg p-4 ring-1 ring-slate-100 dark:ring-slate-700 bg-slate-50/50 dark:bg-slate-700/20">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center font-bold text-blue-700 dark:text-blue-300">
                                    {{ emp.name?.charAt(0) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-900 dark:text-white truncate">{{ emp.name }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ emp.email }}</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-500">{{ emp.current_load }} / {{ maxCapacity }} pts</span>
                                    <span class="font-semibold">{{ emp.capacity_percent }}%</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-600 overflow-hidden">
                                    <div
                                        class="h-full rounded-full"
                                        :class="emp.capacity_percent >= 80 ? 'bg-red-500' : emp.capacity_percent >= 60 ? 'bg-yellow-500' : 'bg-green-500'"
                                        :style="{ width: emp.capacity_percent + '%' }"
                                    />
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Tasks</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ emp.task_count }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Free</p>
                                    <p class="text-sm font-bold text-green-600 dark:text-green-400">{{ emp.remaining }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Score</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ emp.monthly_score }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasks -->
                <div v-if="tab === 'tasks'" class="p-5">
                    <div v-if="tasks.length === 0" class="text-center py-8 text-slate-400">No tasks in this branch.</div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wider text-slate-400 font-semibold">
                                    <th class="px-3 py-2">Risk</th>
                                    <th class="px-3 py-2">Client</th>
                                    <th class="px-3 py-2">Task</th>
                                    <th class="px-3 py-2">Assignee</th>
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2">Due</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <tr v-for="t in tasks" :key="t.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                    <td class="px-3 py-3 text-lg">{{ t.risk_level }}</td>
                                    <td class="px-3 py-3 text-slate-700 dark:text-slate-200">{{ t.client?.company_name }}</td>
                                    <td class="px-3 py-3 font-medium text-slate-900 dark:text-white">{{ t.template?.name }}</td>
                                    <td class="px-3 py-3 text-slate-500">{{ t.assigned_employee?.name || '—' }}</td>
                                    <td class="px-3 py-3">
                                        <span class="inline-block px-2 py-0.5 text-xs rounded font-semibold" :class="taskStatusColor(t.status)">
                                            {{ t.status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-slate-500">{{ t.due_date }}</td>
                                    <td class="px-3 py-3">
                                        <button
                                            v-if="!t.assigned_user_id"
                                            @click="quickAssign(t.id)"
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                        >
                                            Assign
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Clients -->
                <div v-if="tab === 'clients'" class="p-5">
                    <div v-if="clients.length === 0" class="text-center py-8 text-slate-400">No clients in this branch.</div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="c in clients" :key="c.id" class="rounded-lg p-4 ring-1 ring-slate-100 dark:ring-slate-700 bg-slate-50/50 dark:bg-slate-700/20">
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="font-semibold text-slate-900 dark:text-white truncate">{{ c.company_name }}</p>
                                <span
                                    class="text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded-full"
                                    :class="c.status === 'Active' ? 'bg-green-100 text-green-700' : c.status === 'Risk' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'"
                                >
                                    {{ c.status }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">TIN: {{ c.tin_number }}</p>
                            <div class="mt-2 flex items-center gap-3 text-xs text-slate-500">
                                <span>Complexity {{ c.complexity_score }}</span>
                                <span>·</span>
                                <span>{{ c.open_tasks }} open task{{ c.open_tasks === 1 ? '' : 's' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="stats.utilization_pct >= 80" class="rounded-xl bg-amber-50 dark:bg-amber-900/20 p-4 ring-1 ring-amber-200 dark:ring-amber-800/40 flex items-start gap-3">
                <ExclamationTriangleIcon class="h-6 w-6 text-amber-500 flex-shrink-0" />
                <div>
                    <p class="font-semibold text-amber-900 dark:text-amber-200">Branch is running hot</p>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        Team utilization is at {{ stats.utilization_pct }}%, with {{ stats.employees_at_capacity }} employee(s) at or near capacity. Consider redistributing client complexity or hiring additional staff.
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
