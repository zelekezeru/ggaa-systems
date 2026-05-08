<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useI18n } from 'vue-i18n';
import {
    ClipboardDocumentListIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    PencilSquareIcon,
    TrashIcon,
    UserPlusIcon,
    UserMinusIcon,
    XMarkIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    EyeIcon,
    PaperClipIcon,
    ChatBubbleBottomCenterTextIcon,
} from '@heroicons/vue/24/outline';
import { CheckBadgeIcon } from '@heroicons/vue/24/solid';

const { t } = useI18n({ useScope: 'global' });

const props = defineProps({
    tasks:     { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    clients:   { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    stats:     { type: Object, default: () => ({}) },
    filters:   { type: Object, default: () => ({}) },
});

// ─── Filter state ──────────────────────────────────────────────────────────────
const search     = ref(props.filters.search     ?? '');
const statusFilter   = ref(props.filters.status     ?? 'all');
const employeeFilter = ref(props.filters.employee_id ?? 'all');
const clientFilter   = ref(props.filters.client_id   ?? 'all');

let debounceTimer = null;
function applyFilters() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('super-admin.tasks.index'), {
            search:      search.value || undefined,
            status:      statusFilter.value !== 'all' ? statusFilter.value : undefined,
            employee_id: employeeFilter.value !== 'all' ? employeeFilter.value : undefined,
            client_id:   clientFilter.value !== 'all' ? clientFilter.value : undefined,
        }, { preserveState: true, replace: true });
    }, 350);
}

watch([search, statusFilter, employeeFilter, clientFilter], applyFilters);

// ─── Modal state ───────────────────────────────────────────────────────────────
const showCreateModal = ref(false);
const showEditModal   = ref(false);
const showDeleteModal = ref(false);
const showDetailModal = ref(false);
const selectedTask    = ref(null);

// ─── Create form ───────────────────────────────────────────────────────────────
const createForm = useForm({
    client_id:        '',
    task_template_id: '',
    due_date:         '',
    assigned_user_id: '',
    status:           'To Do',
    notes:            '',
});

function openCreateModal() {
    createForm.reset();
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.post(route('super-admin.tasks.store'), {
        onSuccess: () => { showCreateModal.value = false; createForm.reset(); },
    });
}

// ─── Edit form ─────────────────────────────────────────────────────────────────
const editForm = useForm({
    client_id:        '',
    task_template_id: '',
    due_date:         '',
    assigned_user_id: '',
    status:           '',
    notes:            '',
});

function openEditModal(task) {
    selectedTask.value = task;
    editForm.client_id        = task.client_id;
    editForm.task_template_id = task.task_template_id;
    editForm.due_date         = task.due_date ? task.due_date.substring(0, 10) : '';
    editForm.assigned_user_id = task.assigned_user_id ?? '';
    editForm.status           = task.status;
    editForm.notes            = task.notes ?? '';
    showEditModal.value = true;
}

function submitEdit() {
    editForm.put(route('super-admin.tasks.update', selectedTask.value.id), {
        onSuccess: () => { showEditModal.value = false; },
    });
}

// ─── Delete ────────────────────────────────────────────────────────────────────
function openDeleteModal(task) {
    selectedTask.value = task;
    showDeleteModal.value = true;
}

function confirmDelete() {
    router.delete(route('super-admin.tasks.destroy', selectedTask.value.id), {
        onSuccess: () => { showDeleteModal.value = false; },
    });
}

// ─── Task detail view ──────────────────────────────────────────────────────────
function openDetail(task) {
    selectedTask.value = task;
    showDetailModal.value = true;
}

// ─── Assign / unassign inline ──────────────────────────────────────────────────
const assignForm = useForm({ employee_id: '' });
const assigningTaskId = ref(null);

function assignTask(task, employeeId) {
    assignForm.employee_id = employeeId;
    assignForm.post(route('tasks.assign', task.id), {
        preserveScroll: true,
        onSuccess: () => { assigningTaskId.value = null; },
        onError: () => { assigningTaskId.value = null; },
    });
}

function unassignTask(task) {
    router.post(route('tasks.unassign', task.id), {}, { preserveScroll: true });
}

// ─── Helpers ───────────────────────────────────────────────────────────────────
const STATUS_CONFIG = {
    'Waiting on Client': { label: 'Pending Docs', bg: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300', dot: 'bg-amber-400' },
    'To Do':             { label: 'In Progress',  bg: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',   dot: 'bg-blue-500' },
    'In Review':         { label: 'Under Review', bg: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300', dot: 'bg-purple-500' },
    'Done':              { label: 'Done',          bg: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',   dot: 'bg-green-500' },
};

function statusConfig(status) {
    return STATUS_CONFIG[status] ?? { label: status, bg: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400' };
}

function riskBadge(task) {
    if (task.risk_level === '🔴') return { label: 'High', cls: 'text-red-600 dark:text-red-400', icon: '🔴' };
    if (task.risk_level === '🟡') return { label: 'Med',  cls: 'text-yellow-600 dark:text-yellow-400', icon: '🟡' };
    return { label: 'Low', cls: 'text-green-600 dark:text-green-400', icon: '🟢' };
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function dueDiff(dateStr) {
    if (!dateStr) return null;
    const diff = Math.ceil((new Date(dateStr) - new Date()) / 86400000);
    return diff;
}

function dueDateClass(task) {
    if (task.status === 'Done') return 'text-gray-400 dark:text-gray-500';
    const diff = dueDiff(task.due_date);
    if (diff === null) return 'text-gray-500';
    if (diff < 0)  return 'text-red-600 dark:text-red-400 font-semibold';
    if (diff <= 2) return 'text-yellow-600 dark:text-yellow-400 font-semibold';
    return 'text-gray-600 dark:text-gray-300';
}

function dueDateLabel(task) {
    const diff = dueDiff(task.due_date);
    if (diff === null) return '—';
    if (diff < 0)  return `${Math.abs(diff)}d overdue`;
    if (diff === 0) return 'Due today';
    return formatDate(task.due_date);
}

function progressPercent(status) {
    if (status === 'Waiting on Client') return 5;
    if (status === 'To Do') return 35;
    if (status === 'In Review') return 70;
    if (status === 'Done') return 100;
    return 0;
}

const STATUSES = ['all', 'Waiting on Client', 'To Do', 'In Review', 'Done'];
const STATUS_ORDER = ['Waiting on Client', 'To Do', 'In Review', 'Done'];

function advanceTaskStage(task) {
    const idx = STATUS_ORDER.indexOf(task.status);
    if (idx === -1 || idx >= STATUS_ORDER.length - 1) return;
    
    const nextStatus = STATUS_ORDER[idx + 1];

    editForm.client_id = task.client_id;
    editForm.task_template_id = task.task_template_id;
    editForm.due_date = task.due_date ? task.due_date.substring(0, 10) : '';
    editForm.assigned_user_id = task.assigned_user_id ?? '';
    editForm.status = nextStatus;
    editForm.notes = task.notes ?? '';
    
    editForm.put(route('super-admin.tasks.update', task.id), {
        preserveScroll: true,
        onSuccess: () => { showDetailModal.value = false; }
    });
}

const atRiskTasks = computed(() => {
    return props.tasks.filter(t => t.status !== 'Done' && (t.risk_level === '🔴' || t.risk_level === '🟡')).sort((a, b) => dueDiff(a.due_date) - dueDiff(b.due_date));
});
</script>

<template>
    <Head title="Task Management" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- ── Page Header ──────────────────────────────────────────────── -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-indigo-100 dark:bg-indigo-900/40">
                        <ClipboardDocumentListIcon class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('task_management') }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ t('task_management_desc') }}</p>
                    </div>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 dark:hover:bg-indigo-500 transition-colors"
                >
                    <PlusIcon class="h-4 w-4" />
                    {{ t('add_task') }}
                </button>
            </div>

            <!-- ── Attention Required Radar ─────────────────────────────────── -->
            <div v-if="atRiskTasks.length" class="bg-gradient-to-r from-red-500/10 to-transparent border border-red-200 dark:border-red-900/50 rounded-xl p-5 shadow-sm relative overflow-hidden">
                <div class="absolute right-0 top-0 opacity-[0.03] dark:opacity-10 pointer-events-none">
                    <ExclamationTriangleIcon class="w-48 h-48 -mt-8 -mr-8 text-red-500" />
                </div>
                <div class="flex items-center gap-3 mb-4 relative z-10">
                    <span class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                    <h3 class="text-sm font-bold text-red-700 dark:text-red-400 uppercase tracking-widest">Action Required: Overdue & At Risk</h3>
                </div>
                <div class="flex flex-wrap gap-4 relative z-10">
                    <div v-for="task in atRiskTasks.slice(0, 5)" :key="task.id" @click="openDetail(task)" class="bg-white dark:bg-slate-800 border-l-4 border border-red-100 dark:border-slate-700 rounded-lg p-3.5 w-64 shadow-sm cursor-pointer hover:-translate-y-1 hover:shadow-md transition-all" :class="task.risk_level === '🔴' ? 'border-l-red-500' : 'border-l-yellow-400'">
                        <div class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-1 truncate">{{ task.client?.company_name }}</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ task.template?.name }}</div>
                        <div class="flex items-center justify-between mt-3">
                            <span class="text-xs font-bold" :class="dueDateClass(task)">{{ dueDateLabel(task) }}</span>
                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full" :class="task.risk_level === '🔴' ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300'">{{ task.risk_level === '🔴' ? 'High Risk' : 'Medium Risk' }}</span>
                        </div>
                    </div>
                    <div v-if="atRiskTasks.length > 5" class="bg-white/50 dark:bg-slate-800/50 border border-dashed border-red-200 dark:border-red-900/50 rounded-lg p-3 flex items-center justify-center font-bold text-xs text-red-600 dark:text-red-400 uppercase tracking-wide cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors w-32" @click="statusFilter = 'all'; search = ''">
                        +{{ atRiskTasks.length - 5 }} More
                    </div>
                </div>
            </div>

            <!-- ── Stats Cards ──────────────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 flex items-center gap-4 shadow-sm">
                    <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                        <ClipboardDocumentListIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Tasks</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 flex items-center gap-4 shadow-sm">
                    <div class="p-2 bg-amber-50 dark:bg-amber-900/30 rounded-lg">
                        <ClockIcon class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.unassigned }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Unassigned</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 flex items-center gap-4 shadow-sm">
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                        <EyeIcon class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.in_review }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">In Review</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 flex items-center gap-4 shadow-sm">
                    <div class="p-2 bg-red-50 dark:bg-red-900/30 rounded-lg">
                        <ExclamationTriangleIcon class="h-5 w-5 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.overdue }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Overdue</p>
                    </div>
                </div>
            </div>

            <!-- ── Filters Bar ──────────────────────────────────────────────── -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 shadow-sm p-4 flex flex-wrap gap-3 items-center">
                <FunnelIcon class="h-4 w-4 text-gray-400 flex-shrink-0" />

                <!-- Search -->
                <div class="relative flex-1 min-w-[200px]">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search client or task type..."
                        class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                </div>

                <!-- Status Filter -->
                <select
                    v-model="statusFilter"
                    class="py-2 px-3 text-sm bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="all">All Statuses</option>
                    <option value="Waiting on Client">Waiting on Client</option>
                    <option value="To Do">In Progress</option>
                    <option value="In Review">Under Review</option>
                    <option value="Done">Done</option>
                </select>

                <!-- Employee Filter -->
                <select
                    v-model="employeeFilter"
                    class="py-2 px-3 text-sm bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="all">All Employees</option>
                    <option value="unassigned">Unassigned</option>
                    <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                </select>

                <!-- Client Filter -->
                <select
                    v-model="clientFilter"
                    class="py-2 px-3 text-sm bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="all">All Clients</option>
                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company_name }}</option>
                </select>
            </div>

            <!-- ── Task Table ───────────────────────────────────────────────── -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-800/60">
                        <tr>
                            <th class="py-3 pl-5 pr-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Client / Task</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Due Date</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Risk</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Assignee</th>
                            <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 pr-5">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        <tr v-if="tasks.length === 0">
                            <td colspan="6" class="py-16 text-center text-gray-400 dark:text-gray-500">
                                <ClipboardDocumentListIcon class="h-12 w-12 mx-auto mb-3 opacity-40" />
                                <p class="text-sm font-medium">No tasks found</p>
                            </td>
                        </tr>
                        <tr
                            v-for="task in tasks"
                            :key="task.id"
                            class="hover:bg-gray-50/80 dark:hover:bg-slate-700/40 transition-colors group"
                        >
                            <!-- Client / Task type -->
                            <td class="py-3.5 pl-5 pr-3">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ task.client?.company_name }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ task.template?.name ?? '—' }}</p>
                                    <div v-if="task.document_path?.length" class="flex items-center gap-0.5 text-xs text-indigo-500" title="Has attachments">
                                        <PaperClipIcon class="h-3 w-3" />
                                        <span>{{ task.document_path.length }}</span>
                                    </div>
                                    <div v-if="task.notes" class="flex items-center gap-0.5 text-xs text-gray-400" title="Has notes">
                                        <ChatBubbleBottomCenterTextIcon class="h-3 w-3" />
                                    </div>
                                </div>
                            </td>

                            <!-- Status badge -->
                            <td class="px-3 py-3.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" :class="statusConfig(task.status).bg">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig(task.status).dot"></span>
                                    {{ statusConfig(task.status).label }}
                                </span>
                            </td>

                            <!-- Due date -->
                            <td class="px-3 py-3.5 text-sm whitespace-nowrap" :class="dueDateClass(task)">
                                {{ dueDateLabel(task) }}
                            </td>

                            <!-- Risk -->
                            <td class="px-3 py-3.5">
                                <span class="text-sm font-bold" :class="riskBadge(task).cls" :title="riskBadge(task).label + ' risk'">
                                    {{ riskBadge(task).icon }}
                                    <span class="text-xs font-semibold ml-0.5">{{ riskBadge(task).label }}</span>
                                </span>
                            </td>

                            <!-- Assignee -->
                            <td class="px-3 py-3.5">
                                <div v-if="task.assigned_employee" class="flex items-center gap-2">
                                    <div class="h-7 w-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-xs font-bold text-indigo-700 dark:text-indigo-300 flex-shrink-0">
                                        {{ task.assigned_employee.name?.charAt(0) }}
                                    </div>
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="text-sm text-gray-700 dark:text-gray-200 truncate max-w-[120px]">{{ task.assigned_employee.name }}</span>
                                        <button
                                            @click="unassignTask(task)"
                                            class="opacity-0 group-hover:opacity-100 transition-opacity text-gray-400 hover:text-red-500 dark:hover:text-red-400 flex-shrink-0"
                                            title="Unassign"
                                        >
                                            <UserMinusIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="flex items-center gap-1.5">
                                    <select
                                        class="py-1 pl-2 pr-7 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-600 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-400 max-w-[150px]"
                                        @change="e => assignTask(task, e.target.value)"
                                    >
                                        <option value="">Assign to...</option>
                                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                                    </select>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-3 py-3.5 pr-5 text-right">
                                <div class="inline-flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        @click="openDetail(task)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors"
                                        title="View details"
                                    >
                                        <EyeIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="openEditModal(task)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors"
                                        title="Edit task"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="openDeleteModal(task)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                                        title="Delete task"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Table footer count -->
                <div v-if="tasks.length > 0" class="border-t border-gray-100 dark:border-slate-700 px-5 py-3 text-xs text-gray-500 dark:text-gray-400">
                    Showing {{ tasks.length }} task{{ tasks.length !== 1 ? 's' : '' }}
                </div>
            </div>
        </div>


        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- CREATE TASK MODAL                                                  -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showCreateModal = false" />
                    <div class="relative w-full max-w-lg bg-white dark:bg-slate-800 rounded-2xl shadow-2xl overflow-hidden ring-1 ring-black/5">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="p-1.5 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                                    <PlusIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <h2 class="font-bold text-gray-900 dark:text-white">{{ t('add_task') }}</h2>
                            </div>
                            <button @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Form -->
                        <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client <span class="text-red-500">*</span></label>
                                    <select v-model="createForm.client_id" class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Select client...</option>
                                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company_name }}</option>
                                    </select>
                                    <p v-if="createForm.errors.client_id" class="mt-1 text-xs text-red-600">{{ createForm.errors.client_id }}</p>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task Type <span class="text-red-500">*</span></label>
                                    <select v-model="createForm.task_template_id" class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Select task type...</option>
                                        <option v-for="tmpl in templates" :key="tmpl.id" :value="tmpl.id">{{ tmpl.name }}</option>
                                    </select>
                                    <p v-if="createForm.errors.task_template_id" class="mt-1 text-xs text-red-600">{{ createForm.errors.task_template_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date <span class="text-red-500">*</span></label>
                                    <input type="date" v-model="createForm.due_date" class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                    <p v-if="createForm.errors.due_date" class="mt-1 text-xs text-red-600">{{ createForm.errors.due_date }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Initial Status</label>
                                    <select v-model="createForm.status" class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="Waiting on Client">Waiting on Client</option>
                                        <option value="To Do">In Progress</option>
                                        <option value="In Review">In Review</option>
                                        <option value="Done">Done</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign to Employee</label>
                                    <select v-model="createForm.assigned_user_id" class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Leave unassigned</option>
                                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                    <textarea v-model="createForm.notes" rows="3" placeholder="Optional internal notes..." class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none placeholder-gray-400" />
                                </div>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="showCreateModal = false" class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-slate-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="createForm.processing" class="flex-1 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                                    <span v-if="createForm.processing">Creating...</span>
                                    <span v-else>Create Task</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>


        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- EDIT TASK MODAL                                                    -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showEditModal = false" />
                    <div class="relative w-full max-w-lg bg-white dark:bg-slate-800 rounded-2xl shadow-2xl overflow-hidden ring-1 ring-black/5">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="p-1.5 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                                    <PencilSquareIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <h2 class="font-bold text-gray-900 dark:text-white">Edit Task</h2>
                            </div>
                            <button @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>
                        <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client</label>
                                    <select v-model="editForm.client_id" class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company_name }}</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task Type</label>
                                    <select v-model="editForm.task_template_id" class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option v-for="tmpl in templates" :key="tmpl.id" :value="tmpl.id">{{ tmpl.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date</label>
                                    <input type="date" v-model="editForm.due_date" class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                    <select v-model="editForm.status" class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="Waiting on Client">Waiting on Client</option>
                                        <option value="To Do">In Progress</option>
                                        <option value="In Review">In Review</option>
                                        <option value="Done">Done</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assigned Employee</label>
                                    <select v-model="editForm.assigned_user_id" class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Unassigned</option>
                                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                    <textarea v-model="editForm.notes" rows="3" class="w-full rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none" />
                                </div>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="showEditModal = false" class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-slate-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">Cancel</button>
                                <button type="submit" :disabled="editForm.processing" class="flex-1 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50 transition-colors">
                                    <span v-if="editForm.processing">Saving...</span>
                                    <span v-else>{{ $t('save_changes') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>


        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- TASK DETAIL MODAL                                                  -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDetailModal && selectedTask" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDetailModal = false" />
                    <div class="relative w-full max-w-md bg-white dark:bg-slate-800 rounded-2xl shadow-2xl overflow-hidden ring-1 ring-black/5">
                        <!-- Risk strip -->
                        <div class="h-1.5 w-full" :class="{
                            'bg-green-400': selectedTask.risk_level === '🟢',
                            'bg-yellow-400': selectedTask.risk_level === '🟡',
                            'bg-red-500': selectedTask.risk_level === '🔴',
                        }" />
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-4 mb-5">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">{{ selectedTask.client?.company_name }}</p>
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedTask.template?.name ?? 'Task' }}</h2>
                                </div>
                                <button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-gray-50 dark:bg-slate-700 rounded-xl p-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Status</p>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full" :class="statusConfig(selectedTask.status).bg">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig(selectedTask.status).dot"></span>
                                        {{ statusConfig(selectedTask.status).label }}
                                    </span>
                                </div>
                                <div class="bg-gray-50 dark:bg-slate-700 rounded-xl p-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Due Date</p>
                                    <p class="text-sm font-semibold dark:text-white" :class="dueDateClass(selectedTask)">{{ dueDateLabel(selectedTask) }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-slate-700 rounded-xl p-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Risk Level</p>
                                    <p class="text-sm font-semibold" :class="riskBadge(selectedTask).cls">{{ selectedTask.risk_level }} {{ riskBadge(selectedTask).label }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-slate-700 rounded-xl p-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Complexity</p>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white">Score {{ selectedTask.client?.complexity_score }}</p>
                                </div>
                                <div class="col-span-2 bg-gray-50 dark:bg-slate-700 rounded-xl p-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Assigned To</p>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ selectedTask.assigned_employee?.name ?? 'Unassigned' }}</p>
                                </div>
                                <div class="col-span-2 bg-gray-50 dark:bg-slate-700 rounded-xl p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-semibold uppercase tracking-wider">Task Progress</p>
                                    <div class="relative w-full">
                                        <div class="overflow-hidden h-2 mb-2 text-xs flex rounded-full bg-gray-200 dark:bg-slate-600">
                                            <div :style="'width: ' + progressPercent(selectedTask.status) + '%'" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500 transition-all duration-500"></div>
                                        </div>
                                        <div class="flex justify-between text-[10px] text-gray-500 dark:text-gray-400 font-medium px-1">
                                            <span :class="{'text-indigo-600 dark:text-indigo-400 font-bold': progressPercent(selectedTask.status) >= 5}">Pending</span>
                                            <span :class="{'text-indigo-600 dark:text-indigo-400 font-bold': progressPercent(selectedTask.status) >= 35}">In Progress</span>
                                            <span :class="{'text-indigo-600 dark:text-indigo-400 font-bold': progressPercent(selectedTask.status) >= 70}">Review</span>
                                            <span :class="{'text-indigo-600 dark:text-indigo-400 font-bold': progressPercent(selectedTask.status) === 100}">Done</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="selectedTask.notes" class="col-span-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-3 border border-yellow-100 dark:border-yellow-800/30">
                                    <p class="text-xs text-yellow-800 dark:text-yellow-400 font-bold mb-1 flex items-center gap-1.5 uppercase tracking-wide">
                                        <ChatBubbleBottomCenterTextIcon class="h-4 w-4" />
                                        Task Notes
                                    </p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ selectedTask.notes }}</p>
                                </div>
                                <div v-if="selectedTask.document_path && selectedTask.document_path.length" class="col-span-2 bg-indigo-50/50 dark:bg-indigo-900/20 p-3 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                                    <p class="text-xs text-indigo-900 dark:text-indigo-300 font-bold mb-2 uppercase tracking-wide flex items-center gap-1.5">
                                        <PaperClipIcon class="h-4 w-4" />
                                        Attached Files
                                    </p>
                                    <div class="space-y-1.5">
                                        <template v-for="(path, ix) in selectedTask.document_path" :key="ix">
                                            <a :href="'/storage/' + path" target="_blank" class="flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors bg-white dark:bg-slate-800 px-2.5 py-1.5 rounded-md shadow-sm border border-indigo-50 dark:border-indigo-900/50 w-full group">
                                                <PaperClipIcon class="h-4 w-4 text-indigo-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-300 transition-colors" />
                                                <span class="truncate font-medium">Download Document {{ ix + 1 }}</span>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                                <div v-if="selectedTask.completed_at" class="col-span-2 bg-green-50 dark:bg-green-900/20 rounded-xl p-3 border border-green-200 dark:border-green-800">
                                    <p class="text-xs text-green-600 dark:text-green-400 font-semibold flex items-center gap-1">
                                        <CheckCircleIcon class="h-3.5 w-3.5" />
                                        Completed {{ formatDate(selectedTask.completed_at) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-3">
                                <button v-if="selectedTask.status !== 'Done'" @click="advanceTaskStage(selectedTask)" :disabled="editForm.processing" class="w-full py-2.5 rounded-xl bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600 transition-colors shadow-sm flex items-center justify-center gap-2 disabled:opacity-50 mb-1">
                                    <span v-if="selectedTask.status === 'In Review'">Approve & Mark as Done</span>
                                    <span v-else>Advance to {{ STATUS_ORDER[STATUS_ORDER.indexOf(selectedTask.status) + 1] }}</span>
                                    &rarr;
                                </button>
                                <button @click="showDetailModal = false; openEditModal(selectedTask)" class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-slate-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors flex items-center justify-center gap-2">
                                    <PencilSquareIcon class="h-4 w-4" /> Edit
                                </button>
                                <button @click="showDetailModal = false" class="flex-1 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>


        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- DELETE CONFIRM MODAL                                               -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false" />
                    <div class="relative w-full max-w-sm bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 ring-1 ring-black/5 text-center">
                        <div class="mx-auto w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-4">
                            <TrashIcon class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Delete Task?</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            This will permanently delete the task for
                            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ selectedTask?.client?.company_name }}</span>.
                        </p>
                        <div class="flex gap-3">
                            <button @click="showDeleteModal = false" class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-slate-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">Cancel</button>
                            <button @click="confirmDelete" class="flex-1 py-2.5 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-700 transition-colors">Delete</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AdminLayout>
</template>
