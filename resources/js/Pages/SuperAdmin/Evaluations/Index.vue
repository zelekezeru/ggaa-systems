<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useI18n } from 'vue-i18n';
import {
    ClipboardDocumentCheckIcon,
    MagnifyingGlassIcon,
    ChartBarIcon,
    CheckBadgeIcon,
    ClockIcon,
    UserGroupIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n({ useScope: 'global' });

const props = defineProps({
    staff:   { type: Array,  default: () => [] },
    filters: { type: Object, default: () => ({}) },
    stats:   { type: Object, default: () => ({}) },
    canManageMetrics: { type: Boolean, default: false },
});

const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

const month = ref(props.filters.month ?? new Date().getMonth() + 1);
const year  = ref(props.filters.year ?? new Date().getFullYear());
const search = ref('');

const years = computed(() => {
    const y = new Date().getFullYear();
    return [y - 2, y - 1, y, y + 1];
});

function applyPeriod() {
    router.get(route('super-admin.evaluations.index'), { month: month.value, year: year.value }, {
        preserveState: true, replace: true,
    });
}
watch([month, year], applyPeriod);

const filteredStaff = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return props.staff;
    return props.staff.filter(s =>
        s.name.toLowerCase().includes(q) ||
        (s.position || '').toLowerCase().includes(q) ||
        (s.role || '').toLowerCase().includes(q)
    );
});

function scoreColor(score) {
    if (score === null || score === undefined) return 'text-slate-400';
    if (score >= 80) return 'text-emerald-600 dark:text-emerald-400';
    if (score >= 70) return 'text-blue-600 dark:text-blue-400';
    if (score >= 60) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
}

function statusBadge(status) {
    if (status === 'finalized') return { label: t('finalized'), cls: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' };
    if (status === 'draft')     return { label: t('in_progress'), cls: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' };
    return { label: t('not_started'), cls: 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400' };
}

// Localized grade label derived from the score (mirrors EvaluationService::grade)
function gradeLabel(score) {
    if (score === null || score === undefined) return '';
    if (score >= 90) return t('grade_outstanding');
    if (score >= 80) return t('grade_exceeds');
    if (score >= 70) return t('grade_meets');
    if (score >= 60) return t('grade_needs_improvement');
    return t('grade_unsatisfactory');
}

function openStaff(id) {
    router.get(route('super-admin.evaluations.show', id), { month: month.value, year: year.value });
}
</script>

<template>
    <Head title="Staff Evaluations" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-indigo-100 dark:bg-indigo-900/40">
                        <ClipboardDocumentCheckIcon class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('staff_performance_evaluations') }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ t('evaluations_subtitle') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <select v-model.number="month" class="py-2 px-3 text-sm bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option v-for="(m, i) in MONTHS" :key="m" :value="i + 1">{{ m }}</option>
                    </select>
                    <select v-model.number="year" class="py-2 px-3 text-sm bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 flex items-center gap-4 shadow-sm">
                    <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg"><UserGroupIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400" /></div>
                    <div><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p><p class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ t('staff_count') }}</p></div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 flex items-center gap-4 shadow-sm">
                    <div class="p-2 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg"><CheckBadgeIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400" /></div>
                    <div><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.finalized }}</p><p class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ t('finalized') }}</p></div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 flex items-center gap-4 shadow-sm">
                    <div class="p-2 bg-amber-50 dark:bg-amber-900/30 rounded-lg"><ClockIcon class="h-5 w-5 text-amber-600 dark:text-amber-400" /></div>
                    <div><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pending }}</p><p class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ t('pending') }}</p></div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 flex items-center gap-4 shadow-sm">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg"><ChartBarIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" /></div>
                    <div><p class="text-2xl font-bold" :class="scoreColor(stats.average_score)">{{ stats.average_score ?? '—' }}<span v-if="stats.average_score" class="text-base">%</span></p><p class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ t('avg_score') }}</p></div>
                </div>
            </div>

            <!-- Search -->
            <div class="relative max-w-md">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                <input v-model="search" type="text" :placeholder="t('search_staff_placeholder')" class="w-full pl-9 pr-3 py-2 text-sm bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>

            <!-- Staff table -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-800/60">
                        <tr>
                            <th class="py-3 pl-5 pr-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ t('staff_member') }}</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ t('position_role') }}</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ t('status') }}</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ t('score') }}</th>
                            <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 pr-5">{{ t('action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        <tr v-if="filteredStaff.length === 0">
                            <td colspan="5" class="py-16 text-center text-gray-400 dark:text-gray-500">
                                <ClipboardDocumentCheckIcon class="h-12 w-12 mx-auto mb-3 opacity-40" />
                                <p class="text-sm font-medium">{{ t('no_staff_found') }}</p>
                            </td>
                        </tr>
                        <tr v-for="s in filteredStaff" :key="s.id" class="hover:bg-gray-50/80 dark:hover:bg-slate-700/40 transition-colors cursor-pointer" @click="openStaff(s.id)">
                            <td class="py-3.5 pl-5 pr-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-sm font-bold text-indigo-700 dark:text-indigo-300 flex-shrink-0 overflow-hidden">
                                        <img v-if="s.avatar" :src="s.avatar" class="h-full w-full object-cover" />
                                        <span v-else>{{ s.name.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ s.name }}</p>
                                        <p class="text-xs text-gray-400">{{ s.branch || '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3.5">
                                <p class="text-sm text-gray-700 dark:text-gray-200">{{ s.position }}</p>
                                <p class="text-[11px] text-gray-400 uppercase tracking-wide">{{ s.role }}</p>
                            </td>
                            <td class="px-3 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" :class="statusBadge(s.status).cls">{{ statusBadge(s.status).label }}</span>
                            </td>
                            <td class="px-3 py-3.5">
                                <div v-if="s.overall_score !== null && s.overall_score !== undefined" class="flex items-baseline gap-1.5">
                                    <span class="text-lg font-black" :class="scoreColor(s.overall_score)">{{ s.overall_score }}%</span>
                                    <span class="text-[10px] font-medium text-gray-400">{{ gradeLabel(s.overall_score) }}</span>
                                </div>
                                <span v-else class="text-sm text-gray-300 dark:text-gray-600">—</span>
                            </td>
                            <td class="px-3 py-3.5 pr-5 text-right">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                    {{ s.status === 'finalized' ? t('view') : t('evaluate') }}
                                    <ArrowRightIcon class="h-3.5 w-3.5" />
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
