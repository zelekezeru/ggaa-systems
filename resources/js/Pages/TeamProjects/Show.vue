<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { useRoleLayout } from '@/Composables/useRoleLayout';
import {
    ArrowLeftIcon, RectangleGroupIcon, UserGroupIcon, ClipboardDocumentListIcon,
    PaperClipIcon, ChatBubbleLeftRightIcon, BuildingOffice2Icon,
    PlusIcon, TrashIcon, ArrowUpTrayIcon, CheckIcon, ArrowPathIcon,
    PencilSquareIcon, ClockIcon, CalendarIcon, ArrowDownTrayIcon, BookOpenIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    project: Object,
    isMember: Boolean,
    isLeader: Boolean,
    isClient: Boolean,
    canManage: Boolean,
    staffOptions: Array,
    ledgers: { type: Array, default: () => [] },
});

const tabs = [
    { key: 'overview',      label: 'Overview',       icon: RectangleGroupIcon },
    { key: 'plan',          label: 'Plan & Todos',   icon: ClipboardDocumentListIcon },
    { key: 'team',          label: 'Team',           icon: UserGroupIcon },
    { key: 'files',         label: 'Files',          icon: PaperClipIcon },
    { key: 'ledger',        label: 'Financial Ledger', icon: BookOpenIcon, hidden: () => !props.project.client_id || props.ledgers.length === 0 },
    { key: 'team_chat',     label: 'Team Chat',      icon: ChatBubbleLeftRightIcon },
    { key: 'client_thread', label: 'Client Thread',  icon: BuildingOffice2Icon, hidden: () => !props.project.client_id },
];

const filteredTabs = computed(() => {
    return tabs.filter(t => {
        if (t.hidden && t.hidden()) return false;
        if (props.isClient) {
            // Client sees Overview, Plan, Team, Files, Client Thread
            // Hides Team Chat
            return t.key !== 'team_chat';
        }
        return true;
    });
});

const activeTab = ref('overview');

const { currentLayout } = useRoleLayout();

const statusBadge = (s) => ({
    planning:    'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    in_review:   'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    completed:   'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    cancelled:   'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
}[s] || 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200');

const todoStatusBadge = (s) => ({
    todo:        'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
    in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    done:        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
}[s] || 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400');

const fmtDate = (d) => d ? new Date(d).toLocaleDateString() : '—';

// ── Status transition ────────────────────────────────────────────────────────
const transitionForm = useForm({ status: '' });
const transition = (newStatus) => {
    transitionForm.status = newStatus;
    transitionForm.post(route('team-projects.transition', props.project.id), {
        preserveScroll: true,
    });
};

// ── Plan / Todos ────────────────────────────────────────────────────────────
const todoForm = useForm({ title: '', description: '', assigned_to: '', due_date: '' });
const submitTodo = () => {
    todoForm.post(route('team-projects.todos.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => todoForm.reset(),
    });
};
const updateTodoStatus = (todo, status) => {
    router.patch(route('team-projects.todos.update', [props.project.id, todo.id]),
        { status }, { preserveScroll: true });
};
const deleteTodo = (todo) => {
    if (!confirm('Remove this todo?')) return;
    router.delete(route('team-projects.todos.destroy', [props.project.id, todo.id]),
        { preserveScroll: true });
};

// ── Members ─────────────────────────────────────────────────────────────────
const memberForm = useForm({ user_id: '', complexity_share: 1, role_in_team: 'member' });
const addMember = () => {
    memberForm.post(route('team-projects.members.add', props.project.id), {
        preserveScroll: true,
        onSuccess: () => memberForm.reset(),
    });
};
const removeMember = (userId) => {
    if (!confirm('Remove this member from the team?')) return;
    router.delete(route('team-projects.members.remove', [props.project.id, userId]),
        { preserveScroll: true });
};
const promoteToLeader = (userId) => {
    if (!confirm('Promote this member to team leader? The current leader will become a regular member.')) return;
    router.post(route('team-projects.change-leader', props.project.id),
        { user_id: userId }, { preserveScroll: true });
};

// ── Files ───────────────────────────────────────────────────────────────────
const fileInput = ref(null);
const fileForm = useForm({ file: null });
const uploadFile = (e) => {
    const f = e.target.files[0];
    if (!f) return;
    fileForm.file = f;
    fileForm.post(route('team-projects.files.store', props.project.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { fileForm.reset(); if (fileInput.value) fileInput.value.value = ''; },
    });
};
const deleteFile = (fileId) => {
    if (!confirm($t('delete_this_file'))) return;
    router.delete(route('team-projects.files.destroy', [props.project.id, fileId]),
        { preserveScroll: true });
};

// ── Team Chat ───────────────────────────────────────────────────────────────
const chatForm = useForm({ body: '', attachment: null });
const sendChat = () => {
    chatForm.post(route('team-projects.messages.store', props.project.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => chatForm.reset(),
    });
};

// ── Client Thread ───────────────────────────────────────────────────────────
const clientMessages = ref([]);
const clientForm = useForm({ body: '', attachment: null });
const loadClientThread = async () => {
    if (!props.project.client_id) return;
    const r = await fetch(route('team-projects.client-thread', props.project.id));
    const data = await r.json();
    clientMessages.value = data.messages;
};
const sendClientMessage = () => {
    clientForm.post(route('team-projects.client-message', props.project.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { clientForm.reset(); loadClientThread(); },
    });
};

// progress
const progress = computed(() => props.project.progress_percent ?? 0);

const daysRemaining = computed(() => {
    if (!props.project.due_date) return 0;
    const due = new Date(props.project.due_date);
    const now = new Date();
    const diff = due - now;
    return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)));
});
</script>

<template>
    <Head :title="project.title" />
    <component :is="currentLayout">
        <div class="p-8 max-w-7xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="flex items-start gap-4">
                    <Link :href="route('team-projects.index')" class="p-2.5 rounded-2xl bg-white dark:bg-slate-800 text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 shadow-sm border border-slate-100 dark:border-slate-700/50 transition-all active:scale-95">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ project.title }}</h1>
                            <span class="px-3 py-1 rounded-full text-[10px] uppercase font-bold tracking-widest shadow-sm" :class="statusBadge(project.status)">
                                {{ project.status.replace('_',' ') }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mt-2 flex items-center gap-2 flex-wrap">
                            <BuildingOffice2Icon class="h-4 w-4 text-blue-500" />
                            {{ project.client?.company_name || 'Internal project' }}
                            <span class="h-1 w-1 bg-slate-300 rounded-full"></span>
                            Branch: {{ project.branch?.name }}
                            <span class="h-1 w-1 bg-slate-300 rounded-full"></span>
                            Leader: <span class="font-bold text-slate-900 dark:text-slate-200">{{ project.team_leader?.name }}</span>
                        </p>
                    </div>
                </div>
                <div v-if="canManage" class="flex flex-wrap gap-2 lg:justify-end">
                    <Link :href="route('team-projects.edit', project.id)"
                        class="p-2.5 rounded-2xl bg-white dark:bg-slate-800 text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 shadow-sm border border-slate-100 dark:border-slate-700/50 transition-all active:scale-95">
                        <PencilSquareIcon class="h-5 w-5" />
                    </Link>
                    <button v-if="project.status === 'planning'" @click="transition('in_progress')"
                        class="px-5 py-2.5 text-xs font-bold rounded-2xl bg-blue-600 text-white hover:bg-blue-700 shadow-md transition-all active:scale-95">Start Project</button>
                    <button v-if="project.status === 'in_progress'" @click="transition('in_review')"
                        class="px-5 py-2.5 text-xs font-bold rounded-2xl bg-amber-500 text-white hover:bg-amber-600 shadow-md transition-all active:scale-95">Move to Review</button>
                    <button v-if="['in_progress','in_review'].includes(project.status)" @click="transition('completed')"
                        class="px-5 py-2.5 text-xs font-bold rounded-2xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-md transition-all active:scale-95">Complete Project</button>
                    <button v-if="!['completed','cancelled'].includes(project.status)" @click="transition('cancelled')"
                        class="px-5 py-2.5 text-xs font-bold rounded-2xl border border-rose-100 dark:border-rose-900/30 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all active:scale-95">Cancel</button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Left Column: Tabs and Content -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden transition-colors">
                        <div class="flex flex-wrap border-b border-slate-50 dark:border-slate-700 p-2 gap-1 bg-slate-50/50 dark:bg-slate-900/20">
                            <button v-for="t in filteredTabs" :key="t.key"
                                @click="activeTab = t.key; if (t.key === 'client_thread') loadClientThread()"
                                class="px-5 py-2.5 text-xs font-bold rounded-xl flex items-center gap-2 transition-all"
                                :class="activeTab === t.key
                                    ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm ring-1 ring-slate-100 dark:ring-slate-600'
                                    : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'">
                                <component :is="t.icon" class="h-4 w-4" /> {{ t.label }}
                            </button>
                        </div>

                        <!-- ── Overview ── -->
                        <div v-if="activeTab === 'overview'" class="p-8 space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-500">
                            <div>
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Project Scope</h3>
                                <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700/50">
                                    <p class="whitespace-pre-wrap text-slate-700 dark:text-slate-200 leading-relaxed">{{ project.description || 'No detailed description provided for this project.' }}</p>
                                </div>
                            </div>
                            <div class="grid sm:grid-cols-2 gap-6">
                                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700/50">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Project Origin</div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        <div class="h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-900/40 text-[10px] flex items-center justify-center text-blue-700 dark:text-blue-400">
                                            {{ project.creator?.name?.charAt(0) }}
                                        </div>
                                        Initiated by {{ project.creator?.name }}
                                    </div>
                                </div>
                                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700/50">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Creation Date</div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        <CalendarIcon class="h-4 w-4 text-slate-400" />
                                        {{ fmtDate(project.created_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Plan & Todos ── -->
                        <div v-if="activeTab === 'plan'" class="p-8 space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
                            <div v-if="isMember || canManage" class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-700/50 space-y-4">
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">New Task</h3>
                                <div class="grid md:grid-cols-12 gap-3">
                                    <input v-model="todoForm.title" type="text" placeholder="Task objective..."
                                        class="md:col-span-5 px-4 py-2.5 rounded-xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                                    <select v-model="todoForm.assigned_to" class="md:col-span-3 px-4 py-2.5 rounded-xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                        <option value="">Unassigned</option>
                                        <option v-for="m in project.active_members" :key="m.user_id" :value="m.user_id">{{ m.user.name }}</option>
                                    </select>
                                    <input v-model="todoForm.due_date" type="date"
                                        class="md:col-span-2 px-4 py-2.5 rounded-xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                                    <button @click="submitTodo" :disabled="todoForm.processing || !todoForm.title"
                                        class="md:col-span-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 disabled:opacity-50 flex items-center justify-center gap-1.5 shadow-md active:scale-95 transition-all">
                                        <PlusIcon class="h-4 w-4" /> Add
                                    </button>
                                </div>
                            </div>

                            <div v-if="project.todos.length === 0" class="text-center py-20">
                                <ClipboardDocumentListIcon class="h-12 w-12 text-slate-200 dark:text-slate-700 mx-auto mb-4" />
                                <p class="text-slate-500 dark:text-slate-400 font-medium">No tasks have been added to the plan yet.</p>
                            </div>
                            <ul v-else class="space-y-3">
                                <li v-for="todo in project.todos" :key="todo.id"
                                    class="group flex items-start gap-4 p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/50 shadow-sm hover:border-blue-200 dark:hover:border-blue-900/50 transition-all">
                                    <button @click="updateTodoStatus(todo, todo.status === 'done' ? 'todo' : 'done')"
                                        class="mt-1 h-6 w-6 rounded-lg border-2 flex items-center justify-center transition-all"
                                        :class="todo.status === 'done' ? 'bg-emerald-500 border-emerald-500 scale-110 shadow-lg shadow-emerald-500/30' : 'border-slate-200 dark:border-slate-700 hover:border-blue-400'">
                                        <CheckIcon v-if="todo.status === 'done'" class="h-4 w-4 text-white font-black" />
                                    </button>
                                    <div class="flex-1">
                                        <div class="font-bold text-slate-800 dark:text-white" :class="todo.status === 'done' ? 'line-through opacity-50' : ''">
                                            {{ todo.title }}
                                        </div>
                                        <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 mt-1 uppercase tracking-widest flex items-center gap-3">
                                            <span v-if="todo.assignee" class="flex items-center gap-1"><UserGroupIcon class="h-3 w-3"/> {{ todo.assignee.name }}</span>
                                            <span v-if="todo.due_date" class="flex items-center gap-1"><CalendarIcon class="h-3 w-3"/> {{ fmtDate(todo.due_date) }}</span>
                                            <span class="px-2 py-0.5 rounded-full shadow-sm" :class="todoStatusBadge(todo.status)">
                                                {{ todo.status.replace('_',' ') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <select :value="todo.status" @change="updateTodoStatus(todo, $event.target.value)"
                                            class="text-[10px] font-bold uppercase tracking-widest px-2 py-1 rounded-lg border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-500 dark:text-white focus:ring-0 focus:border-blue-500 transition-all">
                                            <option value="todo">To do</option>
                                            <option value="in_progress">In progress</option>
                                            <option value="done">Done</option>
                                        </select>
                                        <button v-if="canManage" @click="deleteTodo(todo)" class="p-1.5 text-slate-300 hover:text-rose-500 transition-colors">
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- ── Team ── -->
                        <div v-if="activeTab === 'team'" class="p-8 space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
                            <div v-if="canManage" class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-700/50 space-y-4">
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Enlist Team Member</h3>
                                <div class="grid md:grid-cols-12 gap-3">
                                    <select v-model="memberForm.user_id" class="md:col-span-7 px-4 py-2.5 rounded-xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                        <option value="">Select staff member…</option>
                                        <option v-for="s in staffOptions" :key="s.id" :value="s.id">
                                            {{ s.name }} <template v-if="s.email">({{ s.email }})</template> — {{ s.position_label }} (load: {{ s.capacity_load }})
                                        </option>
                                    </select>
                                    <input v-model.number="memberForm.complexity_share" type="number" min="1" max="10"
                                        class="md:col-span-3 px-4 py-2.5 rounded-xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                                        placeholder="Complexity Share" />
                                    <button @click="addMember" :disabled="memberForm.processing || !memberForm.user_id"
                                        class="md:col-span-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 disabled:opacity-50 shadow-md active:scale-95 transition-all">
                                        Add
                                    </button>
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div v-for="m in project.active_members" :key="m.id" class="flex items-center gap-4 p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-sm transition-all">
                                    <div class="h-12 w-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-black text-lg border border-blue-100 dark:border-blue-800/50 shadow-inner">
                                        {{ m.user.name.charAt(0) }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-bold text-slate-800 dark:text-white">{{ m.user.name }}</div>
                                        <div class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">
                                            Share: {{ m.complexity_share }} · 
                                            <span v-if="m.role_in_team === 'leader'" class="text-blue-600 dark:text-blue-400">Project Leader</span>
                                            <span v-else>Member</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button v-if="canManage && m.role_in_team !== 'leader'" @click="promoteToLeader(m.user_id)"
                                            title="Promote to Leader"
                                            class="p-2 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-xl transition-all">
                                            <ArrowPathIcon class="h-5 w-5" />
                                        </button>
                                        <button v-if="canManage && m.role_in_team !== 'leader'" @click="removeMember(m.user_id)"
                                            title="Remove from Team"
                                            class="p-2 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition-all">
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Files ── -->
                        <div v-if="activeTab === 'files'" class="p-8 space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
                            <div v-if="isMember || canManage" class="flex justify-center p-8 bg-slate-50 dark:bg-slate-900/20 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700 transition-colors">
                                <div class="text-center">
                                    <div class="h-12 w-12 rounded-2xl bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 mx-auto flex items-center justify-center mb-4">
                                        <ArrowUpTrayIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white mb-1">Upload Project Documentation</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 font-medium">PDF, JPG, PNG or ZIP files up to 20MB</p>
                                    <label class="px-6 py-2.5 rounded-2xl bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 cursor-pointer shadow-lg shadow-blue-600/20 active:scale-95 transition-all">
                                        Browse Files
                                        <input ref="fileInput" type="file" class="hidden" @change="uploadFile" />
                                    </label>
                                </div>
                            </div>

                            <div v-if="project.files.length" class="grid sm:grid-cols-2 gap-4">
                                <div v-for="f in project.files" :key="f.id" class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center gap-4 transition-all hover:border-blue-200 dark:hover:border-blue-900/50">
                                    <div class="h-10 w-10 rounded-xl bg-slate-50 dark:bg-slate-900/50 flex items-center justify-center">
                                        <PaperClipIcon class="h-5 w-5 text-slate-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <a :href="f.url" target="_blank" class="block font-bold text-sm text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 truncate transition-colors">{{ f.original_name }}</a>
                                        <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">
                                            {{ f.uploader?.name }} · {{ fmtDate(f.created_at) }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <a :href="route('team-projects.files.download', [project.id, f.id])" 
                                           class="p-2 text-slate-400 hover:text-blue-600 transition-colors"
                                           title="Download">
                                            <ArrowDownTrayIcon class="h-5 w-5" />
                                        </a>
                                        <button v-if="canManage || f.uploaded_by === $page.props.auth.user.id"
                                            @click="deleteFile(f.id)" class="p-2 text-slate-300 hover:text-rose-500 transition-colors"
                                            title="Delete">
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-20 bg-slate-50 dark:bg-slate-900/10 rounded-3xl">
                                <PaperClipIcon class="h-12 w-12 text-slate-200 dark:text-slate-800 mx-auto mb-4" />
                                <p class="text-slate-400 dark:text-slate-600 font-medium">No artifacts or files attached yet.</p>
                            </div>
                        </div>

                        <!-- ── Financial Ledger ── -->
                        <div v-if="activeTab === 'ledger'" class="p-8 space-y-4 animate-in fade-in slide-in-from-bottom-2 duration-500">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <BookOpenIcon class="h-5 w-5" />
                                    Financial Ledger Entries
                                </h3>
                                <Link v-if="project.client_id" :href="route('ledger.show', project.client_id)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400">
                                    <PencilSquareIcon class="h-3.5 w-3.5" /> Open Ledger
                                </Link>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Monthly P&amp;L entries for this client. Team members assigned to this project can collaborate on ledger entry.
                            </p>

                            <div v-if="ledgers.length === 0" class="text-center py-12 text-slate-400">
                                No ledger entries yet for this client.
                            </div>

                            <div v-else class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                                        <tr>
                                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Period</th>
                                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Status</th>
                                            <th class="text-right px-4 py-3 text-xs font-semibold uppercase">Net Profit</th>
                                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Submitted By</th>
                                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Verified By</th>
                                            <th class="text-right px-4 py-3 text-xs font-semibold uppercase">Download</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                        <tr v-for="l in ledgers" :key="l.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                            <td class="px-4 py-3 font-bold">{{ l.eth_month }} {{ l.eth_year }}</td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold capitalize"
                                                    :class="{
                                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': l.status === 'verified',
                                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': l.status === 'submitted',
                                                        'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': l.status === 'draft',
                                                    }">{{ l.status }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-right tabular-nums" :class="l.net_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                                                {{ new Intl.NumberFormat('en-ET', { minimumFractionDigits: 2 }).format(l.net_profit) }}
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-500">{{ l.submitted_by || '—' }}</td>
                                            <td class="px-4 py-3 text-xs text-slate-500">{{ l.verified_by || '—' }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <a v-if="l.status === 'verified'" :href="route('ledger.download.pdf', l.id)" target="_blank"
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100">
                                                    <ArrowDownTrayIcon class="h-3 w-3" /> PDF
                                                </a>
                                                <span v-else class="text-xs text-slate-300">—</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ── Team Chat ── -->
                        <div v-if="activeTab === 'team_chat'" class="p-8 space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
                            <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                                <div v-for="msg in project.messages" :key="msg.id"
                                    class="flex gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700/50 transition-colors">
                                    <div class="h-10 w-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 flex items-center justify-center font-black text-sm shadow-inner border border-blue-100 dark:border-blue-800/50">
                                        {{ msg.sender?.name?.charAt(0) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <strong class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">{{ msg.sender?.name }}</strong>
                                            <span class="text-[10px] font-bold text-slate-400">{{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                                        </div>
                                        <div class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">{{ msg.body }}</div>
                                        <a v-if="msg.attachment_url" :href="msg.attachment_url" target="_blank" class="mt-2 inline-flex items-center gap-1.5 text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest hover:underline">
                                            <PaperClipIcon class="h-3 w-3" /> Attachment
                                        </a>
                                    </div>
                                </div>
                                <div v-if="project.messages.length === 0" class="text-center py-20">
                                    <ChatBubbleLeftRightIcon class="h-12 w-12 text-slate-200 dark:text-slate-800 mx-auto mb-4" />
                                    <p class="text-slate-400 dark:text-slate-600 font-medium">Internal team channel is empty.</p>
                                </div>
                            </div>
                            <form v-if="isMember || canManage" @submit.prevent="sendChat" class="flex gap-3 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-inner">
                                <input v-model="chatForm.body" type="text" placeholder="Share updates with the team..."
                                    class="flex-1 bg-white dark:bg-slate-800 px-5 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                                <button type="submit" :disabled="chatForm.processing || !chatForm.body"
                                    class="px-6 py-3 rounded-2xl bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 disabled:opacity-50 shadow-lg shadow-blue-600/20 active:scale-95 transition-all">Send</button>
                            </form>
                        </div>

                        <!-- ── Client Thread ── -->
                        <div v-if="activeTab === 'client_thread'" class="p-8 space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
                            <div class="p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900/30 flex items-start gap-4">
                                <BuildingOffice2Icon class="h-6 w-6 text-amber-600 dark:text-amber-400 shrink-0" />
                                <div>
                                    <div class="text-xs font-black text-amber-900 dark:text-amber-200 uppercase tracking-widest mb-1">Communication Protocol</div>
                                    <p class="text-xs text-amber-700 dark:text-amber-400 font-medium leading-relaxed">Only the designated project leader ({{ project.team_leader?.name }}) can post updates to the client from this project. All staff members can view the history.</p>
                                </div>
                            </div>
                            
                            <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                                <div v-for="msg in clientMessages" :key="msg.id"
                                    class="flex gap-4 p-4 rounded-2xl transition-colors"
                                    :class="msg.is_client ? 'bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700' : 'bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100/50 dark:border-blue-900/20'">
                                    <div class="h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm shadow-inner border"
                                        :class="msg.is_client 
                                            ? 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-600' 
                                            : 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 border-blue-100 dark:border-blue-800'">
                                        {{ msg.sender?.name?.charAt(0) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <strong class="text-xs font-black uppercase tracking-widest" :class="msg.is_client ? 'text-slate-900 dark:text-white' : 'text-blue-700 dark:text-blue-400'">{{ msg.sender?.name }}</strong>
                                            <span class="text-[10px] font-bold text-slate-400">{{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                                        </div>
                                        <div class="text-sm leading-relaxed whitespace-pre-wrap" :class="msg.is_client ? 'text-slate-700 dark:text-slate-200' : 'text-slate-800 dark:text-slate-200 font-medium'">{{ msg.body }}</div>
                                        <a v-if="msg.attachment" :href="msg.attachment" target="_blank" class="mt-2 inline-flex items-center gap-1.5 text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest hover:underline">
                                            <PaperClipIcon class="h-3 w-3" /> Download Attachment
                                        </a>
                                    </div>
                                </div>
                                <div v-if="clientMessages.length === 0" class="text-center py-20">
                                    <BuildingOffice2Icon class="h-12 w-12 text-slate-200 dark:text-slate-800 mx-auto mb-4" />
                                    <p class="text-slate-400 dark:text-slate-600 font-medium">No client communication history for this project.</p>
                                </div>
                            </div>
                            <form v-if="isLeader || canManage" @submit.prevent="sendClientMessage" class="flex gap-3 bg-white dark:bg-slate-800 p-4 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-lg">
                                <input v-model="clientForm.body" type="text" placeholder="Update the client on project progress..."
                                    class="flex-1 bg-slate-50 dark:bg-slate-900 px-5 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                                <button type="submit" :disabled="clientForm.processing || !clientForm.body"
                                    class="px-6 py-3 rounded-2xl bg-blue-900 dark:bg-blue-600 text-white font-bold text-xs hover:bg-black dark:hover:bg-blue-500 disabled:opacity-50 shadow-lg active:scale-95 transition-all">Publish</button>
                            </form>
                            <div v-else class="p-4 text-center rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700">
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest italic">Restricted: Only the Team Leader can post to the client portal.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Project Metadata -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Progress Card -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 p-8 transition-colors">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Project Progress</h3>
                            <span class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ progress }}%</span>
                        </div>
                        <div class="h-3 bg-slate-100 dark:bg-slate-900 rounded-full overflow-hidden mb-8 shadow-inner">
                            <div class="h-full bg-blue-600 shadow-[0_0_12px_rgba(37,99,235,0.4)] transition-all duration-1000" :style="{ width: progress + '%' }"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700 text-center">
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tasks</div>
                                <div class="text-lg font-black text-slate-900 dark:text-white">
                                    {{ project.todos?.filter(t => t.status === 'done').length || 0 }}/{{ project.todos?.length || 0 }}
                                </div>
                            </div>
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700 text-center">
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Timeline</div>
                                <div class="text-lg font-black text-slate-900 dark:text-white">
                                    {{ daysRemaining }} <span class="text-[10px] uppercase text-slate-400">Days</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700">
                                    <CalendarIcon class="h-5 w-5 text-slate-400" />
                                </div>
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Deadline</div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ fmtDate(project.due_date) }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700">
                                    <ArrowPathIcon class="h-5 w-5 text-slate-400" />
                                </div>
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Priority Level</div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white uppercase">{{ project.priority }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700">
                                    <RectangleGroupIcon class="h-5 w-5 text-slate-400" />
                                </div>
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Complexity Score</div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ project.complexity_score }}/10</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Client Info Card -->
                    <div v-if="project.client" class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 p-8 transition-colors">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="h-12 w-12 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 flex items-center justify-center shrink-0 shadow-inner">
                                <BuildingOffice2Icon class="h-6 w-6" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ project.client.company_name }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium truncate">{{ project.client.sector }}</p>
                            </div>
                        </div>
                        <div class="space-y-4 pt-4 border-t border-slate-50 dark:border-slate-700">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">TIN Number</span>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ project.client.tin_number }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Client Status</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" 
                                    :class="project.client.status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                    {{ project.client.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Team Status Card -->
                    <div class="bg-slate-900 dark:bg-blue-900/20 rounded-3xl p-8 text-white shadow-xl shadow-blue-900/20 transition-colors">
                        <h3 class="text-[10px] font-black text-blue-300 uppercase tracking-widest mb-6">Collaboration Pulse</h3>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-2xl bg-white/10 flex items-center justify-center font-bold text-lg">
                                    {{ project.active_members?.length || 0 }}
                                </div>
                                <div class="text-sm font-bold">Active Contributors</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-2xl bg-white/10 flex items-center justify-center font-bold text-lg">
                                    {{ project.todos?.filter(t => t.status === 'done').length || 0 }}
                                </div>
                                <div class="text-sm font-bold">Milestones Reached</div>
                            </div>
                            <div class="pt-4 border-t border-white/10">
                                <div class="text-[10px] font-black text-blue-300 uppercase tracking-widest mb-2">Team Sentiment</div>
                                <div class="flex gap-1.5">
                                    <div v-for="i in 5" :key="i" class="h-1 flex-1 rounded-full bg-blue-400/30" :class="i <= 4 ? 'bg-blue-400' : ''"></div>
                                </div>
                                <div class="text-[10px] mt-2 font-medium opacity-70 italic">Productivity is high this week.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
