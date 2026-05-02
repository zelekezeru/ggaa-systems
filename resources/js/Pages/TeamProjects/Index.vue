<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { RectangleGroupIcon, PlusIcon, MagnifyingGlassIcon, UserGroupIcon, CalendarIcon, ClockIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
const debounce = (fn, wait) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), wait);
    };
};

const props = defineProps({
    projects: Object,
    stats: Object,
    filters: Object,
    canManage: Boolean,
});

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');

const refetch = debounce(() => {
    router.get(route('team-projects.index'), { search: search.value, status: status.value }, {
        preserveState: true, preserveScroll: true, replace: true,
    });
}, 350);

watch([search, status], refetch);

const statusBadge = (s) => ({
    planning:    'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    in_review:   'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    completed:   'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    cancelled:   'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
}[s] || 'bg-slate-100 text-slate-700');

const priorityBadge = (p) => ({
    low:    'bg-slate-100 text-slate-600',
    normal: 'bg-blue-50 text-blue-700',
    high:   'bg-orange-100 text-orange-700',
    urgent: 'bg-rose-100 text-rose-700',
}[p] || 'bg-slate-100 text-slate-600');

const fmtDate = (d) => d ? new Date(d).toLocaleDateString() : '—';

const page = usePage();
const currentLayout = computed(() => {
    const roles = page.props.auth.user.roles || [];
    if (roles.includes('Client')) return ClientLayout;
    if (roles.includes('Employee')) return EmployeeLayout;
    return AdminLayout;
});
</script>

<template>
    <Head title="Team Projects" />
    <component :is="currentLayout">
        <div class="p-8 max-w-7xl mx-auto space-y-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="bg-blue-600 p-3 rounded-2xl shadow-lg shadow-blue-600/20">
                        <RectangleGroupIcon class="h-8 w-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Team Projects</h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mt-1">Manage cross-staff collaboration and project lifecycles.</p>
                    </div>
                </div>
                <Link v-if="canManage && !$page.props.auth.user.roles.includes('Client')" :href="route('team-projects.create')" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 shadow-md transition-all active:scale-95">
                    <PlusIcon class="h-5 w-5" /> New Project
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 transition-colors">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Projects</div>
                    <div class="text-3xl font-bold mt-2 text-slate-900 dark:text-white">{{ stats.total }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 transition-colors">
                    <div class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">Active</div>
                    <div class="text-3xl font-bold mt-2 text-blue-600 dark:text-blue-400">{{ stats.active }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 transition-colors">
                    <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest">Completed</div>
                    <div class="text-3xl font-bold mt-2 text-emerald-600 dark:text-emerald-400">{{ stats.completed }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 transition-colors">
                    <div class="text-[10px] font-bold text-rose-500 uppercase tracking-widest">Overdue</div>
                    <div class="text-3xl font-bold mt-2 text-rose-600 dark:text-rose-400">{{ stats.overdue }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col md:flex-row gap-4">
                <div class="relative flex-1 group">
                    <MagnifyingGlassIcon class="absolute left-4 top-3 h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                    <input v-model="search" type="text" placeholder="Search projects by title or client…"
                        class="w-full pl-12 pr-4 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
                </div>
                <select v-model="status" class="px-6 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm font-medium">
                    <option value="">All Statuses</option>
                    <option value="planning">Planning</option>
                    <option value="in_progress">In Progress</option>
                    <option value="in_review">In Review</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <!-- List -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden transition-colors">
                <div v-if="projects.data.length === 0" class="p-20 text-center">
                    <RectangleGroupIcon class="h-12 w-12 text-slate-200 dark:text-slate-700 mx-auto mb-4" />
                    <p class="text-slate-500 dark:text-slate-400 font-medium">No projects found matching your criteria.</p>
                </div>
                <ul v-else class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    <li v-for="p in projects.data" :key="p.id" class="group relative transition-colors">
                        <Link v-if="canManage" :href="route('team-projects.edit', p.id)" class="absolute top-4 right-4 z-10 p-2 rounded-xl bg-white dark:bg-slate-800 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 shadow-sm border border-slate-100 dark:border-slate-700 opacity-0 group-hover:opacity-100 transition-all active:scale-95">
                            <PencilSquareIcon class="h-4 w-4" />
                        </Link>
                        <Link :href="route('team-projects.show', p.id)" class="block p-6 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                            <div class="grid lg:grid-cols-12 gap-6 items-center">
                                <div class="lg:col-span-5">
                                    <div class="flex items-center gap-3">
                                        <div class="font-bold text-lg text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ p.title }}</div>
                                    </div>
                                    <div class="text-xs font-bold text-slate-400 dark:text-slate-500 mt-1 uppercase tracking-wider flex items-center gap-2">
                                        {{ p.client?.company_name || 'Internal project' }} 
                                        <span class="h-1 w-1 bg-slate-300 rounded-full"></span>
                                        {{ p.branch?.name }}
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Team Leader</div>
                                    <div class="text-sm font-bold text-slate-700 dark:text-slate-200 flex items-center gap-2">
                                        <div class="h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-900/40 text-[10px] flex items-center justify-center text-blue-700 dark:text-blue-400">
                                            {{ p.team_leader?.name?.charAt(0) }}
                                        </div>
                                        {{ p.team_leader?.name || '—' }}
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Schedule</div>
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                                        <CalendarIcon class="h-4 w-4 text-slate-400" />
                                        {{ fmtDate(p.due_date) }}
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 text-right">Status & Priority</div>
                                    <div class="flex flex-wrap gap-2 justify-end">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] uppercase font-bold tracking-wider shadow-sm" :class="statusBadge(p.status)">
                                            {{ p.status.replace('_', ' ') }}
                                        </span>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] uppercase font-bold tracking-wider shadow-sm" :class="priorityBadge(p.priority)">
                                            {{ p.priority }}
                                        </span>
                                    </div>
                                </div>
                                <div class="lg:col-span-1 text-right">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Progress</div>
                                    <div class="text-lg font-black text-blue-600 dark:text-blue-400">{{ p.progress_percent || 0 }}%</div>
                                </div>

                                <!-- Progress bar overlay -->
                                <div class="lg:col-span-12 mt-2 h-1.5 bg-slate-100 dark:bg-slate-700/50 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)] transition-all duration-1000" :style="{ width: (p.progress_percent || 0) + '%' }"></div>
                                </div>
                            </div>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </component>
</template>
