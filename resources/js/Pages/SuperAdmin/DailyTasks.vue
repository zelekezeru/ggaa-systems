<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    PlusIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    TrashIcon,
    XMarkIcon,
    EnvelopeIcon,
    BuildingOfficeIcon,
    BuildingLibraryIcon,
    ArrowRightCircleIcon,
    UserGroupIcon,
    EllipsisHorizontalCircleIcon,
    BoltIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    tasks:     { type: Array,  required: true },
    employees: { type: Array,  required: true },
    branches:  { type: Array,  required: true },
    stats:     { type: Object, required: true },
    filters:   { type: Object, required: true },
});

// ── Date navigation ───────────────────────────────────────────────────────────
const currentDate = ref(props.filters.date ?? new Date().toISOString().split('T')[0]);

function navigate(days) {
    const d = new Date(currentDate.value);
    d.setDate(d.getDate() + days);
    currentDate.value = d.toISOString().split('T')[0];
    applyFilters();
}

function goToday() {
    currentDate.value = new Date().toISOString().split('T')[0];
    applyFilters();
}

const isToday = computed(() => currentDate.value === new Date().toISOString().split('T')[0]);

function formattedDate(str) {
    return new Date(str + 'T00:00:00').toLocaleDateString(undefined, {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });
}

// ── Filters ───────────────────────────────────────────────────────────────────
const filterEmployee = ref(props.filters.employee_id ?? '');
const filterStatus   = ref(props.filters.status   ?? '');
const filterPriority = ref(props.filters.priority ?? '');

function applyFilters() {
    router.get(route('admin.daily-tasks.index'), {
        date:        currentDate.value,
        employee_id: filterEmployee.value || undefined,
        status:      filterStatus.value   || undefined,
        priority:    filterPriority.value || undefined,
    }, { preserveState: true, replace: true });
}

// ── Create form ───────────────────────────────────────────────────────────────
const showForm = ref(false);

const form = useForm({
    title:          '',
    description:    '',
    type:           'other',
    assigned_to:    '',
    branch_id:      props.branches[0]?.id ?? '',
    scheduled_date: currentDate.value,
    scheduled_time: '',
    priority:       'normal',
    notes:          '',
});

function submitForm() {
    form.post(route('admin.daily-tasks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
}

// ── Delete ────────────────────────────────────────────────────────────────────
function deleteTask(id) {
    if (! confirm(t('delete_confirm_msg') + '?')) return;
    router.delete(route('admin.daily-tasks.destroy', id), { preserveScroll: true });
}

// ── Status update (admin view) ────────────────────────────────────────────────
function setStatus(task, status) {
    router.patch(route('admin.daily-tasks.status', task.id), { status }, {
        preserveScroll: true,
        preserveState:  true,
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const TYPE_ICONS = {
    mail_delivery:    EnvelopeIcon,
    client_visit:     BuildingOfficeIcon,
    tax_commission:   BuildingLibraryIcon,
    errand:           ArrowRightCircleIcon,
    internal_meeting: UserGroupIcon,
    other:            EllipsisHorizontalCircleIcon,
};

function typeIcon(type) {
    return TYPE_ICONS[type] ?? EllipsisHorizontalCircleIcon;
}

const STATUS_STYLES = {
    pending:     'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    done:        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    cancelled:   'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
};

function statusStyle(status) {
    return STATUS_STYLES[status] ?? STATUS_STYLES.pending;
}

function avatarInitial(name) {
    return name?.charAt(0).toUpperCase() ?? '?';
}

function formatTime(t) {
    if (!t) return '';
    const [h, m] = t.split(':');
    const d = new Date(); d.setHours(+h, +m);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex flex-wrap gap-4 justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('daily_task_management') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('daily_task_management_desc') }}</p>
                </div>
                <button
                    @click="showForm = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow transition-colors"
                >
                    <PlusIcon class="h-4 w-4" />
                    {{ t('assign_task') }}
                </button>
            </div>

            <!-- Date navigation -->
            <div class="flex items-center gap-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 w-fit shadow-sm">
                <button @click="navigate(-1)" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                    <ChevronLeftIcon class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                </button>
                <span class="text-sm font-bold text-gray-800 dark:text-white min-w-[240px] text-center">
                    {{ formattedDate(currentDate) }}
                </span>
                <button @click="navigate(1)" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                    <ChevronRightIcon class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                </button>
                <button
                    v-if="!isToday"
                    @click="goToday"
                    class="ml-2 text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline"
                >
                    {{ t('today') }}
                </button>
            </div>

            <!-- Stats row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-gray-400">{{ t('total') }}</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.total }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-yellow-200 dark:border-yellow-900/40 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-yellow-500">{{ t('pending') }}</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.pending }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-green-200 dark:border-green-900/40 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-green-600">{{ t('done') }}</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.done }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-red-200 dark:border-red-900/40 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-red-500">{{ t('urgent') }}</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.urgent }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <select
                    v-model="filterEmployee"
                    @change="applyFilters"
                    class="text-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl px-3 py-2"
                >
                    <option value="">{{ t('all_employees') }}</option>
                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                </select>

                <select
                    v-model="filterStatus"
                    @change="applyFilters"
                    class="text-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl px-3 py-2"
                >
                    <option value="">{{ t('all_statuses') }}</option>
                    <option value="pending">{{ t('pending') }}</option>
                    <option value="in_progress">{{ t('in_progress') }}</option>
                    <option value="done">{{ t('done') }}</option>
                    <option value="cancelled">{{ t('cancelled') }}</option>
                </select>

                <select
                    v-model="filterPriority"
                    @change="applyFilters"
                    class="text-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl px-3 py-2"
                >
                    <option value="">{{ t('all_priorities') }}</option>
                    <option value="urgent">{{ t('urgent') }}</option>
                    <option value="normal">{{ t('normal') }}</option>
                </select>
            </div>

            <!-- Task list -->
            <div v-if="tasks.length" class="space-y-3">
                <div
                    v-for="task in tasks"
                    :key="task.id"
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm flex flex-wrap gap-4 items-center transition-all hover:shadow-md"
                    :class="task.priority === 'urgent' ? 'border-l-4 border-l-red-500' : 'border-l-4 border-l-gray-200 dark:border-l-gray-700'"
                >
                    <!-- Type icon -->
                    <div class="h-10 w-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0">
                        <component :is="typeIcon(task.type)" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    </div>

                    <!-- Title + meta -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-gray-900 dark:text-white">{{ task.title }}</span>
                            <span v-if="task.priority === 'urgent'" class="inline-flex items-center gap-1 text-xs font-bold text-red-600 dark:text-red-400 uppercase">
                                <BoltIcon class="h-3 w-3" /> {{ t('urgent') }}
                            </span>
                        </div>
                        <p v-if="task.description" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ task.description }}</p>
                        <div class="flex items-center gap-3 mt-1 flex-wrap">
                            <!-- Employee avatar -->
                            <div class="flex items-center gap-1.5">
                                <div class="h-5 w-5 rounded-full bg-blue-600 flex items-center justify-center text-[10px] font-bold text-white">
                                    {{ avatarInitial(task.assigned_to?.name) }}
                                </div>
                                <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">{{ task.assigned_to?.name }}</span>
                            </div>
                            <!-- Time -->
                            <span v-if="task.scheduled_time" class="text-xs text-gray-400">
                                {{ formatTime(task.scheduled_time) }}
                            </span>
                            <!-- Type label -->
                            <span class="text-xs text-gray-400 capitalize">{{ t(task.type) }}</span>
                        </div>
                    </div>

                    <!-- Status badge + actions -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full capitalize" :class="statusStyle(task.status)">
                            {{ t(task.status) }}
                        </span>

                        <!-- Quick status advance -->
                        <div class="flex gap-1">
                            <button
                                v-if="task.status === 'pending'"
                                @click="setStatus(task, 'in_progress')"
                                class="text-xs px-2 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 font-semibold hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors"
                            >
                                {{ t('start') }}
                            </button>
                            <button
                                v-if="task.status === 'in_progress'"
                                @click="setStatus(task, 'done')"
                                class="text-xs px-2 py-1 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 font-semibold hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors"
                            >
                                {{ t('mark_done') }}
                            </button>
                        </div>

                        <button
                            @click="deleteTask(task.id)"
                            class="p-1.5 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                        >
                            <TrashIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-else
                class="bg-white dark:bg-gray-900 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl p-16 text-center"
            >
                <BoltIcon class="h-10 w-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400 font-medium">{{ t('no_daily_tasks') }}</p>
                <button @click="showForm = true" class="mt-4 text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline">
                    {{ t('assign_task') }}
                </button>
            </div>
        </div>

        <!-- ── Create task slide-over ──────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-x-full opacity-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-full opacity-0"
            >
                <div v-if="showForm" class="fixed inset-0 z-50 flex justify-end">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="showForm = false" />

                    <!-- Panel -->
                    <div class="relative w-full max-w-md bg-white dark:bg-gray-900 shadow-2xl flex flex-col h-full overflow-y-auto">
                        <!-- Panel header -->
                        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-slate-900 dark:bg-slate-950">
                            <h2 class="text-lg font-black text-white">{{ t('assign_task') }}</h2>
                            <button @click="showForm = false" class="text-slate-400 hover:text-white transition-colors">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Form body -->
                        <form @submit.prevent="submitForm" class="flex-1 px-6 py-6 space-y-5">

                            <!-- Title -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                    {{ t('title') }} *
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    :placeholder="t('task_title_placeholder')"
                                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                                />
                                <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                            </div>

                            <!-- Task type -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                    {{ t('task_type') }} *
                                </label>
                                <select
                                    v-model="form.type"
                                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                >
                                    <option value="mail_delivery">{{ t('mail_delivery') }}</option>
                                    <option value="client_visit">{{ t('client_visit') }}</option>
                                    <option value="tax_commission">{{ t('tax_commission') }}</option>
                                    <option value="errand">{{ t('errand') }}</option>
                                    <option value="internal_meeting">{{ t('internal_meeting') }}</option>
                                    <option value="other">{{ t('other') }}</option>
                                </select>
                            </div>

                            <!-- Assign to -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                    {{ t('assigned_to') }} *
                                </label>
                                <select
                                    v-model="form.assigned_to"
                                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                >
                                    <option value="" disabled>{{ t('select_employee') }}</option>
                                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                                </select>
                                <p v-if="form.errors.assigned_to" class="text-xs text-red-500 mt-1">{{ form.errors.assigned_to }}</p>
                            </div>

                            <!-- Branch (only if Super Admin sees multiple) -->
                            <div v-if="branches.length > 1">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                    {{ t('branch') }} *
                                </label>
                                <select
                                    v-model="form.branch_id"
                                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                >
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>

                            <!-- Date + Time -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                        {{ t('scheduled_date') }} *
                                    </label>
                                    <input
                                        v-model="form.scheduled_date"
                                        type="date"
                                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                        {{ t('scheduled_time') }} <span class="font-normal normal-case text-gray-400">({{ t('optional') }})</span>
                                    </label>
                                    <input
                                        v-model="form.scheduled_time"
                                        type="time"
                                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                    />
                                </div>
                            </div>

                            <!-- Priority -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                                    {{ t('priority') }}
                                </label>
                                <div class="flex rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                    <button
                                        type="button"
                                        @click="form.priority = 'normal'"
                                        class="flex-1 py-2.5 text-sm font-bold transition-colors"
                                        :class="form.priority === 'normal'
                                            ? 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900'
                                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                    >
                                        {{ t('normal') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.priority = 'urgent'"
                                        class="flex-1 py-2.5 text-sm font-bold transition-colors"
                                        :class="form.priority === 'urgent'
                                            ? 'bg-red-600 text-white'
                                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                    >
                                        ⚡ {{ t('urgent') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                    {{ t('description') }} <span class="font-normal normal-case text-gray-400">({{ t('optional') }})</span>
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none resize-none"
                                />
                            </div>

                            <!-- Submit -->
                            <div class="flex gap-3 pt-2">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold rounded-xl transition-colors text-sm"
                                >
                                    {{ form.processing ? t('saving') : t('assign_task') }}
                                </button>
                                <button
                                    type="button"
                                    @click="showForm = false"
                                    class="px-5 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-sm"
                                >
                                    {{ t('cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>
