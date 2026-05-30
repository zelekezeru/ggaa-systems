<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import TaskDetailModal from '@/Components/TaskDetailModal.vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vue-toastification';
import {
    ClockIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ArrowPathIcon,
    DocumentTextIcon,
    BoltIcon,
    EnvelopeIcon,
    BuildingOfficeIcon,
    BuildingLibraryIcon,
    ArrowRightCircleIcon,
    UserGroupIcon,
    EllipsisHorizontalCircleIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    PaperClipIcon,
    ChatBubbleBottomCenterTextIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n({ useScope: 'global' });
const toast = useToast();

const props = defineProps({
    tasksByStatus:   { type: Object, required: true },
    employee:        { type: Object, required: true },
    capacityPercent: { type: Number, default: 0 },
    dailyTasks:      { type: Array,  default: () => [] },
});

// ─── Column definitions ──────────────────────────────────────────────────────
const COLUMNS = [
    {
        key:         'Waiting on Client',
        label:       t('pending_docs'),
        icon:        DocumentTextIcon,
        headerClass: 'text-amber-700 dark:text-amber-400',
        bgClass:     'bg-gradient-to-b from-amber-50/50 to-amber-50/10 dark:from-amber-900/10 dark:to-transparent border-t-amber-400',
        badgeClass:  'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        dotClass:    'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.6)]',
    },
    {
        key:         'To Do',
        label:       t('in_progress'),
        icon:        ArrowPathIcon,
        headerClass: 'text-blue-700 dark:text-blue-400',
        bgClass:     'bg-gradient-to-b from-blue-50/50 to-blue-50/10 dark:from-blue-900/10 dark:to-transparent border-t-blue-500',
        badgeClass:  'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        dotClass:    'bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.6)]',
    },
    {
        key:         'In Review',
        label:       t('under_review'),
        icon:        ClockIcon,
        headerClass: 'text-purple-700 dark:text-purple-400',
        bgClass:     'bg-gradient-to-b from-purple-50/50 to-purple-50/10 dark:from-purple-900/10 dark:to-transparent border-t-purple-500',
        badgeClass:  'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
        dotClass:    'bg-purple-500 shadow-[0_0_8px_rgba(168,85,247,0.6)]',
    },
    {
        key:         'Done',
        label:       t('done'),
        icon:        CheckCircleIcon,
        headerClass: 'text-emerald-700 dark:text-emerald-400',
        bgClass:     'bg-gradient-to-b from-emerald-50/50 to-emerald-50/10 dark:from-emerald-900/10 dark:to-transparent border-t-emerald-500',
        badgeClass:  'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        dotClass:    'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)]',
    },
];

// Continuous reactive map
const board = ref(
    Object.fromEntries(
        COLUMNS.map(col => [
            col.key,
            (props.tasksByStatus[col.key] ?? []).map(t => ({ ...t })),
        ])
    )
);

const colOpen = ref(
    Object.fromEntries(COLUMNS.map(col => [col.key, true]))
);

// ─── Helpers ──────────────────────────────────────────────────────────────────
function riskTheme(riskLevel) {
    switch (riskLevel) {
        case '🔴': return { 
            border: 'border-l-rose-500', 
            bg: 'bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400', 
            icon: '🔴', label: t('high') 
        };
        case '🟡': return { 
            border: 'border-l-amber-400', 
            bg: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400', 
            icon: '🟡', label: t('medium') 
        };
        default: return { 
            border: 'border-l-emerald-400', 
            bg: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400', 
            icon: '🟢', label: t('low') 
        };
    }
}

function formatDateDisplay(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const diff = Math.ceil((d - new Date()) / 86400000);
    
    if (diff < 0) return { text: `${Math.abs(diff)}d ${t('overdue')}`, class: 'text-rose-600 dark:text-rose-400 font-bold bg-rose-50 dark:bg-rose-900/20' };
    if (diff === 0) return { text: t('due_today'), class: 'text-amber-600 dark:text-amber-400 font-bold bg-amber-50 dark:bg-amber-900/20' };
    if (diff <= 3) return { text: `${t('in')} ${diff} ${t('days')}`, class: 'text-amber-600 dark:text-amber-400 font-semibold bg-amber-50 dark:bg-amber-900/10' };
    
    const day = d.getDate();
    return { 
        text: `${day} ${d.toLocaleString('default', { month: 'short' })}`, 
        class: 'text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800' 
    };
}

// ─── Capacity & Stats ────────────────────────────────────────────────────────
const capacityTheme = computed(() => {
    if (props.capacityPercent >= 90) return 'from-rose-500 to-red-600 shadow-[0_0_12px_rgba(225,29,72,0.5)]';
    if (props.capacityPercent >= 70) return 'from-amber-400 to-orange-500 shadow-[0_0_12px_rgba(245,158,11,0.5)]';
    return 'from-blue-500 to-indigo-600 shadow-[0_0_12px_rgba(79,70,229,0.5)]';
});

const totalTasks = computed(() => COLUMNS.reduce((n, col) => n + (board.value[col.key]?.length ?? 0), 0));
const doneTasks = computed(() => board.value['Done']?.length ?? 0);
const progressPercent = computed(() => totalTasks.value === 0 ? 0 : Math.round((doneTasks.value / totalTasks.value) * 100));

const atRiskTasks = computed(() => {
    return Object.keys(board.value)
        .filter(key => key !== 'Done')
        .flatMap(key => board.value[key] || [])
        .filter(t => t.risk_level === '🔴' || t.risk_level === '🟡')
        .sort((a, b) => new Date(a.due_date || '9999-12-31') - new Date(b.due_date || '9999-12-31'));
});

// ─── Drag Logic ──────────────────────────────────────────────────────────────
const STATUS_ORDER = ['Waiting on Client', 'To Do', 'In Review', 'Done'];

function checkMove(evt) {
    if (!evt.from.dataset.status || !evt.to.dataset.status) return true;
    
    const fromStatus = evt.from.dataset.status;
    const toStatus = evt.to.dataset.status;

    if (fromStatus === toStatus) return true;

    const fromIdx = STATUS_ORDER.indexOf(fromStatus);
    const toIdx = STATUS_ORDER.indexOf(toStatus);

    if (toIdx === fromIdx + 1) {
        if (toStatus === 'Done') {
            toast.warning(t('approval_required_for_completion'));
            return false;
        }
        return true;
    }

    if (toIdx < fromIdx) {
        return true; // Allow moving backward if corrections are needed
    }

    toast.error(t('sequential_move_only'));
    return false;
}

function handleDrag(event, newStatus) {
    if (!event.added) return;
    const task = event.added.element;
    task.status = newStatus;

    router.patch(route('employee.tasks.status', task.id), { status: newStatus }, {
        preserveScroll: true,
        preserveState: true,
    });
}

// ─── Daily tasks panel ────────────────────────────────────────────────────────
const dailyPanelOpen  = ref(true);
const localDailyTasks = ref(props.dailyTasks.map(dt => ({ ...dt })));

const DAILY_TYPE_ICONS = {
    mail_delivery:    EnvelopeIcon,
    client_visit:     BuildingOfficeIcon,
    tax_commission:   BuildingLibraryIcon,
    errand:           ArrowRightCircleIcon,
    internal_meeting: UserGroupIcon,
    other:            EllipsisHorizontalCircleIcon,
};

const DAILY_STATUS_STYLES = {
    pending:     'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    done:        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    cancelled:   'bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500',
};

const pendingDailyCount = computed(() =>
    localDailyTasks.value.filter(dt => dt.status !== 'done' && dt.status !== 'cancelled').length
);

function advanceDailyStatus(dt) {
    const next = { pending: 'in_progress', in_progress: 'done' }[dt.status];
    if (! next) return;
    dt.status = next;
    if (next === 'done') dt.completed_at = new Date().toISOString();
    router.patch(route('employee.daily-tasks.status', dt.id), { status: next }, {
        preserveScroll: true,
        preserveState:  true,
    });
}

// ─── Modals ──────────────────────────────────────────────────────────────────
const selectedTask = ref(null);
function openTask(task) { selectedTask.value = task; }
function closeModal() {
    if (selectedTask.value?.status === 'Done') {
        // Optimistically move card if marked done in modal
        for (const colKey of Object.keys(board.value)) {
            const idx = board.value[colKey].findIndex(t => t.id === selectedTask.value.id);
            if (idx !== -1 && colKey !== 'Done') {
                const [moved] = board.value[colKey].splice(idx, 1);
                moved.status = 'Done';
                board.value['Done'].push(moved);
                break;
            }
        }
    }
    selectedTask.value = null;
}
</script>

<template>
    <EmployeeLayout>
        <div class="w-full max-w-none">
            <!-- ── Page Header ── -->
            <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6 backdrop-blur-xl bg-white/50 dark:bg-slate-900/50 p-6 rounded-3xl border border-white/20 dark:border-slate-800 shadow-sm">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-3">
                        {{ t('my_workspace') }}
                        <span class="px-3 py-1 text-xs font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300 rounded-full border border-indigo-200 dark:border-indigo-500/30">
                            {{ doneTasks }} / {{ totalTasks }} {{ t('done') }}
                        </span>
                    </h1>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-2">
                        {{ t('welcome_back') }}, {{ employee.name.split(' ')[0] }}. {{ t('workload_today') }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 sm:gap-8 w-full md:w-auto">
                    <!-- Progress Circle -->
                    <div class="flex items-center gap-3 bg-white dark:bg-slate-800 p-3 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 w-full sm:w-auto">
                        <div class="relative w-12 h-12 flex items-center justify-center">
                            <svg class="transform -rotate-90 w-12 h-12">
                                <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="4" fill="transparent" class="text-slate-100 dark:text-slate-700" />
                                <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="4" fill="transparent" :stroke-dasharray="20 * 2 * Math.PI" :stroke-dashoffset="20 * 2 * Math.PI - (progressPercent / 100) * 20 * 2 * Math.PI" class="text-emerald-500 transition-all duration-1000 ease-out" />
                            </svg>
                            <span class="absolute text-[10px] font-bold text-slate-700 dark:text-slate-300">{{ progressPercent }}%</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ t('progress') }}</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('completion') }}</p>
                        </div>
                    </div>

                    <!-- Capacity Load -->
                    <div class="flex-1 w-full sm:w-auto sm:min-w-[200px]">
                        <div class="flex justify-between items-end mb-2">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ t('capacity_load') }}</p>
                            <span class="text-sm font-black" :class="capacityPercent >= 90 ? 'text-rose-600 dark:text-rose-400' : capacityPercent >= 70 ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400'">
                                {{ capacityPercent }}%
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden ring-1 ring-inset ring-slate-900/5 dark:ring-white/5">
                            <div class="h-full rounded-full transition-all duration-1000 ease-out bg-gradient-to-r"
                                 :class="capacityTheme"
                                 :style="{ width: `${capacityPercent}%` }" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Attention Required Radar ─────────────────────────────────── -->
            <div v-if="atRiskTasks.length" class="mb-6 bg-gradient-to-r from-red-500/10 to-transparent border border-red-200 dark:border-red-900/50 rounded-2xl p-5 shadow-sm relative overflow-hidden">
                <div class="absolute right-0 top-0 opacity-[0.03] dark:opacity-10 pointer-events-none">
                    <ExclamationTriangleIcon class="w-48 h-48 -mt-8 -mr-8 text-red-500" />
                </div>
                <div class="flex items-center gap-3 mb-4 relative z-10">
                    <span class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                    <h3 class="text-sm font-bold text-red-700 dark:text-red-400 uppercase tracking-widest">{{ t('urgent_overdue') }}</h3>
                </div>
                <div class="flex flex-wrap gap-4 relative z-10">
                    <div v-for="task in atRiskTasks.slice(0, 5)" :key="task.id" @click="openTask(task)" class="bg-white dark:bg-slate-800 border-l-4 border border-red-100 dark:border-slate-700 rounded-lg p-3.5 w-64 shadow-sm cursor-pointer hover:-translate-y-1 hover:shadow-md transition-all" :class="task.risk_level === '🔴' ? 'border-l-red-500' : 'border-l-yellow-400'">
                        <div class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-1 truncate">{{ task.client?.company_name }}</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ task.template?.name ?? t('task') }}</div>
                        <div class="flex items-center justify-between mt-3">
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-md" :class="formatDateDisplay(task.due_date).class">{{ formatDateDisplay(task.due_date).text }}</span>
                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full" :class="task.risk_level === '🔴' ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300'">{{ task.risk_level === '🔴' ? t('high_risk') : t('medium_risk') }}</span>
                        </div>
                    </div>
                    <div v-if="atRiskTasks.length > 5" class="bg-white/50 dark:bg-slate-800/50 border border-dashed border-red-200 dark:border-red-900/50 rounded-lg p-3 flex items-center justify-center font-bold text-xs text-red-600 dark:text-red-400 uppercase tracking-wide cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors w-32" @click="toast.info(t('scroll_at_risk_tasks'))">
                        +{{ atRiskTasks.length - 5 }} {{ t('more') }}
                    </div>
                </div>
            </div>

            <!-- ── Kanban Board ── -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 pb-8">
                
                <div v-for="col in COLUMNS" :key="col.key" class="flex flex-col">
                    
                    <!-- Column Header -->
                    <button 
                         @click="colOpen[col.key] = !colOpen[col.key]"
                         class="mb-4 px-4 py-3 rounded-2xl border-t-4 bg-white/50 dark:bg-slate-800/50 backdrop-blur-md shadow-sm flex items-center justify-between transition-all hover:brightness-95 cursor-pointer outline-none w-full"
                         :class="[col.bgClass, !colOpen[col.key] ? 'opacity-70' : '']">
                        <div class="flex items-center gap-2">
                            <component :is="col.icon" class="w-5 h-5 flex-shrink-0" :class="col.headerClass" />
                            <h3 class="font-bold text-sm tracking-wide uppercase truncate" :class="col.headerClass">{{ col.label }}</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold shadow-inner" :class="col.badgeClass">
                                {{ board[col.key]?.length ?? 0 }}
                            </span>
                            <ChevronUpIcon v-if="colOpen[col.key]" class="h-4 w-4" :class="col.headerClass" />
                            <ChevronDownIcon v-else class="h-4 w-4" :class="col.headerClass" />
                        </div>
                    </button>

                    <!-- Scrollable List -->
                    <div v-show="colOpen[col.key]" class="flex-1 flex flex-col">
                        <draggable
                        v-model="board[col.key]"
                        group="tasks"
                        item-key="id"
                        :data-status="col.key"
                        :move="checkMove"
                        :animation="250"
                        ghost-class="opacity-40 scale-95"
                        chosen-class="rotate-2 scale-105 shadow-2xl cursor-grabbing z-50 ring-2 ring-indigo-500"
                        drag-class="cursor-grabbing"
                        class="flex-1 min-h-[200px] rounded-2xl p-2 bg-slate-100/50 dark:bg-slate-800/30 border border-slate-200/50 dark:border-slate-700/50 backdrop-blur-md overflow-y-auto space-y-3"
                        @change="(e) => handleDrag(e, col.key)"
                    >
                        <template #item="{ element: task }">
                            <div @click="openTask(task)"
                                 class="group relative bg-white dark:bg-slate-800 p-5 rounded-2xl border-l-[6px] shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-grab ring-1 ring-slate-900/5 dark:ring-white/5"
                                 :class="riskTheme(task.risk_level).border">
                                
                                <!-- Card Header -->
                                <div class="flex justify-between items-start mb-3">
                                    <div class="px-2.5 py-1 rounded-md text-[10px] font-black tracking-widest uppercase truncate max-w-[150px]"
                                         :class="col.key === 'Done' ? 'bg-slate-100 text-slate-500 dark:bg-slate-700' : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'">
                                        {{ task.template?.name ?? t('task') }}
                                    </div>
                                    
                                    <!-- Risk Indicator -->
                                    <div v-if="col.key !== 'Done'" class="flex items-center gap-1.5 px-2 py-1 rounded-full text-[10px] font-bold"
                                         :class="riskTheme(task.risk_level).bg">
                                        <span class="w-1.5 h-1.5 rounded-full animate-pulse" :class="col.dotClass" />
                                        {{ riskTheme(task.risk_level).label }}
                                    </div>
                                </div>

                                <!-- Card Body -->
                                <h4 class="text-base font-bold text-slate-900 dark:text-white leading-snug line-clamp-2 mb-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ task.client?.company_name }}
                                </h4>
                                
                                <!-- Progress bar -->
                                <div class="mb-3">
                                    <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="{
                                                'bg-amber-400': col.key === 'Waiting on Client',
                                                'bg-blue-500': col.key === 'To Do',
                                                'bg-purple-500': col.key === 'In Review',
                                                'bg-emerald-500': col.key === 'Done',
                                            }"
                                            :style="{ width: col.key === 'Waiting on Client' ? '5%' : col.key === 'To Do' ? '35%' : col.key === 'In Review' ? '70%' : '100%' }"
                                        />
                                    </div>
                                </div>

                                <!-- Notes preview -->
                                <p v-if="task.notes" class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mb-2 italic">{{ task.notes }}</p>

                                <div class="flex items-center gap-2 mb-3">
                                    <div v-if="task.document_path?.length" class="flex items-center gap-1 text-[10px] font-bold text-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 px-1.5 py-0.5 rounded" :title="t('attachments') || 'Attachments'">
                                        <PaperClipIcon class="h-3 w-3" />
                                        <span>{{ task.document_path.length }} {{ t('files') || 'files' }}</span>
                                    </div>
                                    <div v-if="task.notes" class="flex items-center gap-1 text-[10px] font-bold text-amber-500 bg-amber-50 dark:bg-amber-900/30 px-1.5 py-0.5 rounded" :title="t('has_notes') || 'Has notes'">
                                        <ChatBubbleBottomCenterTextIcon class="h-3 w-3" />
                                        <span>{{ t('notes') || 'Notes' }}</span>
                                    </div>
                                </div>

                                <!-- Card Footer -->
                                <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-700 pt-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-[10px] font-bold text-white shadow-sm ring-2 ring-white dark:ring-slate-800">
                                            {{ task.client?.company_name.charAt(0) }}
                                        </div>
                                    </div>
                                    
                                    <div class="px-2.5 py-1 rounded-lg flex items-center gap-1.5" :class="formatDateDisplay(task.due_date).class">
                                        <ClockIcon class="w-3.5 h-3.5" v-if="col.key !== 'Done'" />
                                        <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-500" v-else />
                                        <span class="text-[11px]">{{ formatDateDisplay(task.due_date).text }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template #footer>
                            <div v-if="!board[col.key]?.length" 
                                 class="h-24 m-2 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 transition-colors bg-slate-50/50 dark:bg-slate-800/50">
                                <DocumentTextIcon class="w-6 h-6 mb-1 opacity-50" />
                                <span class="text-xs font-semibold">{{ t('drop_items_here') }}</span>
                            </div>
                        </template>
                        </draggable>
                    </div>
                </div>
            </div>
        </div>

        <TaskDetailModal v-if="selectedTask" :task="selectedTask" @close="closeModal" />
    </EmployeeLayout>
</template>

<style scoped>
/* Hidden scrollbar but keeps functionality */
.overflow-x-auto {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.overflow-x-auto::-webkit-scrollbar {
    display: none;
}
</style>
