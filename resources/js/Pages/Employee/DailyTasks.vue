<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import {
    PlusIcon, XMarkIcon, EnvelopeIcon, BuildingOfficeIcon, BuildingLibraryIcon,
    ArrowRightCircleIcon, UserGroupIcon, EllipsisHorizontalCircleIcon, BoltIcon,
    CheckCircleIcon, PlayCircleIcon, ClockIcon, DocumentCheckIcon, CalendarDaysIcon,
    ChevronLeftIcon, ChevronRightIcon
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as SolidCheckCircleIcon } from '@heroicons/vue/24/solid';

const { t } = useI18n();

const props = defineProps({
    employee: { type: Object, required: true },
    dailyTasks: { type: Array, required: true },
    currentDate: { type: String, required: true }, // Passed from Laravel (YYYY-MM-DD)
});

// --- DATE MANAGEMENT ENGINE ---
const activeDate = ref(props.currentDate);
const todayStr = new Date().toISOString().split('T')[0];

// Generate a 7-day sliding window around the active date
const dateWindow = computed(() => {
    const centerDate = new Date(activeDate.value);
    const dates = [];
    for (let i = -3; i <= 3; i++) {
        const d = new Date(centerDate);
        d.setDate(centerDate.getDate() + i);
        dates.push({
            full: d.toISOString().split('T')[0],
            dayName: d.toLocaleDateString(undefined, { weekday: 'short' }),
            dayNum: d.getDate(),
            isToday: d.toISOString().split('T')[0] === todayStr
        });
    }
    return dates;
});

// Watcher: When user selects a new date, silently fetch that day's tasks
const changeDate = (newDateStr) => {
    activeDate.value = newDateStr;
    router.get(route('employee.daily-tasks.index'), { date: newDateStr }, {
        preserveState: true,
        preserveScroll: true,
        only: ['dailyTasks', 'currentDate'] // Partial reload for maximum speed
    });
};

const goToToday = () => changeDate(todayStr);

// --- TASK STATE & FORM LOGIC ---
const DAILY_TYPE_ICONS = {
    mail_delivery: EnvelopeIcon, client_visit: BuildingOfficeIcon, tax_commission: BuildingLibraryIcon,
    errand: ArrowRightCircleIcon, internal_meeting: UserGroupIcon, other: EllipsisHorizontalCircleIcon,
};

const activeTasks = computed(() => props.dailyTasks.filter(dt => dt.status !== 'done' && dt.status !== 'cancelled'));
const completedTasks = computed(() => props.dailyTasks.filter(dt => dt.status === 'done'));

const progressPercentage = computed(() => {
    if (!props.dailyTasks.length) return 0;
    const completable = props.dailyTasks.filter(dt => dt.status !== 'cancelled').length;
    if (completable === 0) return 0;
    return Math.round((completedTasks.value.length / completable) * 100);
});

function advanceDailyStatus(dt) {
    const next = { pending: 'in_progress', in_progress: 'done' }[dt.status];
    if (!next) return;
    dt.status = next; 
    router.patch(route('employee.daily-tasks.status', dt.id), { status: next }, { preserveScroll: true, preserveState: true });
}

const showForm = ref(false);
const form = useForm({
    title: '', type: 'other', description: '', priority: 'normal',
    scheduled_date: activeDate.value, // Default to the date they are currently viewing
    scheduled_time: '',
});

function submitForm() {
    form.post(route('employee.daily-tasks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('title', 'description', 'scheduled_time'); // Keep date & type context
            showForm.value = false;
        }
    });
}

// Keep form synced if they change days while it's open
watch(activeDate, (newVal) => form.scheduled_date = newVal);
</script>

<template>
    <EmployeeLayout>
        <div class="w-full max-w-none mx-auto space-y-8 pb-12">

            <div class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200/60 dark:border-slate-800 shadow-sm p-6 sm:p-10">
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row gap-8 justify-between items-start md:items-center">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-xs font-bold uppercase tracking-wider mb-2">
                            <CalendarDaysIcon class="w-4 h-4" />
                            {{ activeDate === todayStr ? t('today') || 'Today' : new Date(activeDate).toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' }) }}
                        </div>
                        <h1 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white">
                            {{ activeDate < todayStr ? t('past_log') || 'Past Log' : (activeDate > todayStr ? t('future_plan') || 'Future Plan' : t('daily_tasks') || "Today's Agenda") }}
                        </h1>
                    </div>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 w-full md:w-auto bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                        <div class="flex flex-col w-full sm:w-auto">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ progressPercentage }}% {{ t('completed') || 'Completed' }}</span>
                            <div class="w-full sm:w-32 h-2 mt-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-600 transition-all duration-1000 ease-out rounded-full" :style="`width: ${progressPercentage}%`"></div>
                            </div>
                        </div>
                        <div class="hidden sm:block w-px h-10 bg-slate-200 dark:bg-slate-700 mx-2"></div>
                        <button @click="showForm = true" class="w-full sm:w-auto group relative inline-flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 dark:bg-indigo-600 hover:bg-indigo-600 hover:shadow-lg dark:hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition-all shadow-md hover:-translate-y-0.5">
                            <PlusIcon class="h-5 w-5 transition-transform group-hover:rotate-90" />
                            <span>{{ t('add') || 'Add' }}</span>
                        </button>
                    </div>
                </div>

                <div class="relative z-10 mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-4">
                    <label class="cursor-pointer p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">
                        <CalendarDaysIcon class="w-6 h-6" />
                        <input type="date" class="sr-only" :value="activeDate" @change="e => changeDate(e.target.value)" />
                    </label>

                    <div class="flex flex-1 justify-between overflow-x-auto gap-2 px-2 scrollbar-hide snap-x">
                        <button 
                            v-for="day in dateWindow" 
                            :key="day.full"
                            @click="changeDate(day.full)"
                            class="snap-center flex-1 min-w-[64px] max-w-[80px] flex flex-col items-center justify-center py-3 rounded-2xl transition-all duration-200 relative border"
                            :class="[
                                activeDate === day.full 
                                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-500/20 scale-105' 
                                    : 'bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700'
                            ]"
                        >
                            <span v-if="day.isToday && activeDate !== day.full" class="absolute top-2 right-2 w-1.5 h-1.5 bg-indigo-500 rounded-full"></span>
                            
                            <span class="text-xs font-bold uppercase tracking-wider mb-1" :class="activeDate === day.full ? 'text-indigo-200' : 'text-slate-400'">{{ day.dayName }}</span>
                            <span class="text-xl font-black">{{ day.dayNum }}</span>
                        </button>
                    </div>

                    <button v-if="activeDate !== todayStr" @click="goToToday" class="px-4 py-2 text-sm font-bold text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 dark:text-indigo-400 rounded-xl hover:bg-indigo-100 transition-colors">
                        {{ t('today') || 'Today' }}
                    </button>
                </div>
            </div>

            <!-- TASK PIPELINE LIST -->
            <div class="space-y-4">
                <div class="flex items-center justify-between px-2">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full animate-pulse" :class="activeDate === todayStr ? 'bg-indigo-500' : 'bg-slate-400'"></div>
                        {{ t('active_pipeline') || 'Active Pipeline' }}
                    </h2>
                    <span class="text-sm font-semibold text-slate-500 bg-white dark:bg-slate-800 px-3 py-1 rounded-full border border-slate-200 dark:border-slate-700">
                        {{ activeTasks.length }} {{ t('tasks') || 'Tasks' }}
                    </span>
                </div>

                <div v-if="activeTasks.length" class="space-y-3 relative">
                    <TransitionGroup name="task-list">
                        <div
                            v-for="dt in activeTasks"
                            :key="dt.id"
                            class="group bg-white dark:bg-slate-800 p-4 sm:p-5 rounded-2xl border-l-4 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col sm:flex-row sm:items-center gap-4"
                            :class="[
                                dt.priority === 'urgent' ? 'border-l-rose-500 dark:border-slate-700' : 'border-l-indigo-500 dark:border-slate-700',
                                dt.status === 'in_progress' ? 'ring-1 ring-indigo-500/20 dark:ring-indigo-500/40 bg-indigo-50/30 dark:bg-indigo-900/10' : 'border border-slate-200'
                            ]"
                        >
                            <div class="h-12 w-12 rounded-xl bg-slate-50 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                                <component :is="DAILY_TYPE_ICONS[dt.type] ?? EllipsisHorizontalCircleIcon" class="h-6 w-6 text-slate-600 dark:text-slate-300" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white truncate">{{ dt.title }}</h3>
                                    <span v-if="dt.priority === 'urgent'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400">
                                        <BoltIcon class="w-3 h-3" /> {{ t('urgent') || 'URGENT' }}
                                    </span>
                                </div>
                                <p v-if="dt.description" class="text-sm text-slate-500 dark:text-slate-400 truncate">{{ dt.description }}</p>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-6 sm:w-auto w-full mt-2 sm:mt-0">
                                <div v-if="dt.scheduled_time" class="flex items-center gap-1.5 text-sm font-semibold text-slate-500 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg">
                                    <ClockIcon class="w-4 h-4" />
                                    {{ dt.scheduled_time.slice(0, 5) }}
                                </div>

                                <button
                                    @click="advanceDailyStatus(dt)"
                                    class="relative overflow-hidden flex items-center gap-2 px-4 py-2 rounded-xl font-bold text-sm transition-all duration-200"
                                    :class="dt.status === 'pending' 
                                        ? 'bg-slate-100 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-indigo-900/50 dark:hover:text-indigo-300' 
                                        : 'bg-indigo-600 text-white hover:bg-emerald-500 shadow-md shadow-indigo-500/20 hover:shadow-emerald-500/20'"
                                >
                                    <component :is="dt.status === 'pending' ? PlayCircleIcon : CheckCircleIcon" class="w-5 h-5" />
                                    {{ dt.status === 'pending' ? t('start') || 'Start' : t('mark_done') || 'Complete' }}
                                </button>
                            </div>
                        </div>
                    </TransitionGroup>
                </div>

                <div v-else class="bg-white/50 dark:bg-slate-900/50 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-3xl p-12 text-center transition-all">
                    <div class="w-20 h-20 bg-emerald-50 dark:bg-emerald-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <DocumentCheckIcon class="h-10 w-10 text-emerald-500" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ t('all_caught_up') || "You're all caught up!" }}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-sm mx-auto">
                        {{ t('no_daily_tasks_desc') || "Your active pipeline is clear. Enjoy the rest of your day or add a new task if needed." }}
                    </p>
                </div>
            </div>

            <!-- COMPLETED TASKS -->
            <div v-if="completedTasks.length" class="space-y-4 pt-8">
                <h2 class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-2 flex items-center gap-2">
                    <SolidCheckCircleIcon class="w-4 h-4" />
                    {{ t('completed_log') || 'Completed Log' }}
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 opacity-70 hover:opacity-100 transition-opacity">
                    <TransitionGroup name="task-list">
                        <div
                            v-for="dt in completedTasks"
                            :key="dt.id"
                            class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-center gap-4"
                        >
                            <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center">
                                <SolidCheckCircleIcon class="h-5 w-5 text-emerald-500" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-600 dark:text-slate-300 line-through decoration-slate-300 dark:decoration-slate-600 truncate">
                                    {{ dt.title }}
                                </p>
                            </div>
                        </div>
                    </TransitionGroup>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-400 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-300 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showForm" class="fixed inset-0 z-[100] flex justify-end">
                    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showForm = false" />
                    
                    <Transition
                        enter-active-class="transform transition duration-400 ease-out"
                        enter-from-class="translate-x-full"
                        enter-to-class="translate-x-0"
                        leave-active-class="transform transition duration-300 ease-in"
                        leave-from-class="translate-x-0"
                        leave-to-class="translate-x-full"
                        appear
                    >
                        <div v-if="showForm" class="relative w-full max-w-md bg-white dark:bg-slate-900 shadow-2xl flex flex-col h-full border-l border-slate-200 dark:border-slate-800">
                            <div class="flex items-center justify-between px-8 py-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                                <div>
                                    <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">{{ t('add_errand') || 'New Task' }}</h2>
                                    <p class="text-xs font-medium text-slate-500 mt-1">{{ t('for') || 'For' }}: {{ activeDate === todayStr ? (t('today') || 'Today') : new Date(activeDate).toLocaleDateString() }}</p>
                                </div>
                                <button @click="showForm = false" class="p-2 bg-white dark:bg-slate-800 rounded-full text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700">
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto px-8 py-6 space-y-6">
                                
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-3">{{ t('priority') || 'Priority' }}</label>
                                    <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
                                        <button type="button" @click="form.priority = 'normal'" class="flex-1 py-2.5 text-sm font-bold rounded-lg transition-all" :class="form.priority === 'normal' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">
                                            {{ t('normal') || 'Normal' }}
                                        </button>
                                        <button type="button" @click="form.priority = 'urgent'" class="flex-1 flex justify-center items-center gap-1.5 py-2.5 text-sm font-bold rounded-lg transition-all" :class="form.priority === 'urgent' ? 'bg-rose-500 text-white shadow-sm shadow-rose-500/20' : 'text-slate-500 hover:text-rose-500'">
                                            <BoltIcon class="w-4 h-4" /> {{ t('urgent') || 'Urgent' }}
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">{{ t('title') || 'Title' }} *</label>
                                    <input v-model="form.title" type="text" :placeholder="t('what_needs_done') || 'What needs to be done?'" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-4 py-3.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-shadow" required />
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">{{ t('task_type') || 'Type' }} *</label>
                                        <select v-model="form.type" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-4 py-3.5 text-sm focus:ring-2 focus:ring-indigo-500 shadow-sm cursor-pointer">
                                            <option value="mail_delivery">{{ t('mail_delivery') || 'Mail Delivery' }}</option>
                                            <option value="client_visit">{{ t('client_visit') || 'Client Visit' }}</option>
                                            <option value="tax_commission">{{ t('tax_commission') || 'Tax Commission' }}</option>
                                            <option value="errand">{{ t('errand') || 'Errand' }}</option>
                                            <option value="internal_meeting">{{ t('internal_meeting') || 'Internal Meeting' }}</option>
                                            <option value="other">{{ t('other') || 'Other' }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">{{ t('scheduled_time') || 'Time' }}</label>
                                        <input v-model="form.scheduled_time" type="time" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-4 py-3.5 text-sm focus:ring-2 focus:ring-indigo-500 shadow-sm" />
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">{{ t('description') || 'Description' }}</label>
                                    <textarea v-model="form.description" rows="4" :placeholder="t('add_extra_details') || 'Add any extra details here...'" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-4 py-3.5 text-sm focus:ring-2 focus:ring-indigo-500 shadow-sm resize-none"></textarea>
                                </div>
                            </form>

                            <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/80 backdrop-blur-md">
                                <button 
                                    @click="submitForm"
                                    :disabled="form.processing || !form.title" 
                                    class="w-full flex items-center justify-center gap-2 py-4 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:hover:bg-indigo-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/20 transition-all hover:-translate-y-0.5 text-sm"
                                >
                                    <component :is="form.processing ? EllipsisHorizontalCircleIcon : PlusIcon" class="w-5 h-5" :class="{'animate-spin': form.processing}" />
                                    {{ form.processing ? t('saving') || 'Saving...' : t('add_errand') || 'Create Task' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </EmployeeLayout>
</template>

<style>
/* Hide scrollbar for the date carousel but keep functionality */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* CSS for the TransitionGroup animation */
.task-list-move,
.task-list-enter-active,
.task-list-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.task-list-enter-from,
.task-list-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}
.task-list-leave-active {
    position: absolute;
    width: 100%;
}
</style>