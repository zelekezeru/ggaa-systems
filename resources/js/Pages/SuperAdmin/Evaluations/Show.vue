<script setup>
import { ref, computed, reactive } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from 'vue-toastification';
import {
    ChevronLeftIcon, CheckBadgeIcon, BoltIcon, PencilSquareIcon,
    PlusIcon, TrashIcon, LockClosedIcon, ScaleIcon, SparklesIcon,
    ClockIcon, InformationCircleIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';

const toast = useToast();

const props = defineProps({
    staff:      { type: Object, required: true },
    evaluation: { type: Object, required: true },
    rubric:     { type: Array,  default: () => [] },
    available:  { type: Array,  default: () => [] },
    filters:    { type: Object, default: () => ({}) },
    categories: { type: Object, default: () => ({}) },
    canManageMetrics: { type: Boolean, default: false },
    canManage:        { type: Boolean, default: false },
});

const isFinalized = computed(() => props.evaluation.status === 'finalized');

// ── Local reactive copy of score lines (live editing) ──
const lines = reactive(props.evaluation.scores.map(s => ({ ...s })));
const summaryNote = ref(props.evaluation.summary_note ?? '');

// Live normalized score per line
function normalized(line) {
    if (line.is_auto) return line.normalized_score ?? 0;
    if (line.raw_score === null || line.raw_score === '' || line.raw_score === undefined) return 0;
    const raw = Math.min(parseFloat(line.raw_score) || 0, line.max_score);
    return Math.min(100, (raw / Math.max(1, line.max_score)) * 100);
}

const totalWeight = computed(() => lines.reduce((sum, l) => sum + (parseFloat(l.weight) || 0), 0));

// Weight-normalized live overall (mirrors backend finalize math)
const liveOverall = computed(() => {
    if (totalWeight.value <= 0) return 0;
    const weightedSum = lines.reduce((sum, l) => sum + normalized(l) * (parseFloat(l.weight) || 0), 0);
    return Math.round((weightedSum / totalWeight.value) * 100) / 100;
});

// Each line's contribution to the normalized overall
function contribution(line) {
    if (totalWeight.value <= 0) return 0;
    return Math.round(((parseFloat(line.weight) || 0) / totalWeight.value) * normalized(line) * 100) / 100;
}

function weightShare(line) {
    if (totalWeight.value <= 0) return 0;
    return Math.round(((parseFloat(line.weight) || 0) / totalWeight.value) * 1000) / 10;
}

const grade = computed(() => {
    const s = isFinalized.value ? props.evaluation.overall_score : liveOverall.value;
    if (s >= 90) return { label: 'Outstanding', cls: 'text-emerald-600 dark:text-emerald-400' };
    if (s >= 80) return { label: 'Exceeds Expectations', cls: 'text-emerald-600 dark:text-emerald-400' };
    if (s >= 70) return { label: 'Meets Expectations', cls: 'text-blue-600 dark:text-blue-400' };
    if (s >= 60) return { label: 'Needs Improvement', cls: 'text-amber-600 dark:text-amber-400' };
    return { label: 'Unsatisfactory', cls: 'text-rose-600 dark:text-rose-400' };
});

const displayScore = computed(() => isFinalized.value ? props.evaluation.overall_score : liveOverall.value);

// Group lines by category for display
const grouped = computed(() => {
    const groups = {};
    for (const l of lines) {
        (groups[l.category] ??= []).push(l);
    }
    return Object.entries(groups).map(([cat, items]) => ({
        category: cat,
        label: props.categories[cat] ?? cat,
        items,
    }));
});

function catColor(cat) {
    const map = {
        task_performance: 'bg-blue-500', team_project: 'bg-indigo-500', daily_task: 'bg-cyan-500',
        client_satisfaction: 'bg-pink-500', manager_review: 'bg-purple-500', quality_compliance: 'bg-orange-500',
        professionalism: 'bg-teal-500', attendance: 'bg-amber-500', leadership: 'bg-rose-500', custom: 'bg-slate-500',
    };
    return map[cat] ?? 'bg-slate-500';
}

// ── Save / finalize ──
const saving = ref(false);
function buildPayload() {
    return {
        summary_note: summaryNote.value,
        scores: lines.map(l => ({
            id: l.id,
            raw_score: l.is_auto ? l.raw_score : (l.raw_score === '' ? null : l.raw_score),
            justification: l.justification ?? '',
        })),
    };
}

function saveDraft() {
    saving.value = true;
    router.post(route('super-admin.evaluations.scores', props.evaluation.id), buildPayload(), {
        preserveScroll: true,
        onSuccess: () => toast.success('Progress saved'),
        onError: () => toast.error('Could not save — check the scores'),
        onFinish: () => saving.value = false,
    });
}

function finalize() {
    if (!confirm('Finalize and lock this evaluation? Scores can only be changed by reopening it.')) return;
    saving.value = true;
    router.post(route('super-admin.evaluations.finalize', props.evaluation.id), buildPayload(), {
        preserveScroll: true,
        onSuccess: () => toast.success('Evaluation finalized'),
        onError: () => toast.error('Could not finalize'),
        onFinish: () => saving.value = false,
    });
}

function reopen() {
    router.post(route('super-admin.evaluations.reopen', props.evaluation.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Reopened for editing'),
    });
}

// ── Rubric management ──
function updateWeight(item) {
    router.put(route('super-admin.evaluations.metrics.update', { staff: props.staff.id, assignment: item.id }), {
        weight: item.weight,
    }, { preserveScroll: true, preserveState: false });
}

function toggleActive(item) {
    router.put(route('super-admin.evaluations.metrics.update', { staff: props.staff.id, assignment: item.id }), {
        is_active: !item.is_active,
    }, { preserveScroll: true });
}

function detach(item) {
    if (!confirm(`Remove “${item.name}” from this rubric?`)) return;
    router.delete(route('super-admin.evaluations.metrics.detach', { staff: props.staff.id, assignment: item.id }), {
        preserveScroll: true,
        onSuccess: () => toast.success('Metric removed'),
    });
}

// Add-metric picker
const showAddPicker = ref(false);
const addForm = useForm({ evaluation_metric_id: '', weight: 10 });
function attachMetric() {
    const m = props.available.find(a => a.id === addForm.evaluation_metric_id);
    if (m) addForm.weight = m.default_weight;
    addForm.post(route('super-admin.evaluations.metrics.attach', props.staff.id), {
        preserveScroll: true,
        onSuccess: () => { showAddPicker.value = false; addForm.reset(); toast.success('Metric added'); },
    });
}

// Custom metric creator
const showCustom = ref(false);
const customForm = useForm({ name: '', description: '', category: 'custom', default_weight: 10, applies_to_roles: [] });
function createCustom() {
    customForm.post(route('super-admin.evaluation-metrics.store'), {
        preserveScroll: true,
        onSuccess: () => { showCustom.value = false; customForm.reset(); toast.success('Custom metric created'); },
    });
}

const weightWarning = computed(() => Math.abs(totalWeight.value - 100) > 0.01);
</script>

<template>
    <Head :title="`Evaluate — ${staff.name}`" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 max-w-6xl mx-auto space-y-6">

            <Link :href="route('super-admin.evaluations.index', filters)" class="inline-flex items-center text-sm text-slate-500 hover:text-indigo-600 group transition-colors">
                <ChevronLeftIcon class="h-4 w-4 mr-1 group-hover:-translate-x-1 transition-transform" />
                Back to evaluations
            </Link>

            <!-- Header / gauge -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="h-1.5 w-full" :class="displayScore >= 70 ? 'bg-emerald-500' : displayScore >= 60 ? 'bg-amber-400' : 'bg-rose-500'" />
                <div class="p-6 flex flex-wrap items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-2xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-xl font-bold text-indigo-700 dark:text-indigo-300 overflow-hidden">
                            <img v-if="staff.avatar" :src="staff.avatar" class="h-full w-full object-cover" />
                            <span v-else>{{ staff.name.charAt(0) }}</span>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ staff.name }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ staff.position }} · <span class="uppercase text-xs tracking-wide">{{ staff.role }}</span></p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ evaluation.period_label }} · {{ staff.branch || 'No branch' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-6">
                        <div class="text-right">
                            <div class="flex items-baseline gap-1 justify-end">
                                <span class="text-4xl font-black" :class="grade.cls">{{ displayScore.toFixed(1) }}</span>
                                <span class="text-lg font-bold text-gray-400">%</span>
                            </div>
                            <p class="text-xs font-semibold" :class="grade.cls">{{ grade.label }}</p>
                            <p v-if="!isFinalized" class="text-[10px] text-gray-400 mt-0.5">Live preview</p>
                        </div>
                        <div class="relative w-20 h-20">
                            <svg class="transform -rotate-90 w-20 h-20">
                                <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="7" fill="transparent" class="text-slate-100 dark:text-slate-700" />
                                <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="7" fill="transparent"
                                    :stroke-dasharray="34 * 2 * Math.PI"
                                    :stroke-dashoffset="34 * 2 * Math.PI - (displayScore / 100) * 34 * 2 * Math.PI"
                                    class="transition-all duration-700"
                                    :class="displayScore >= 70 ? 'text-emerald-500' : displayScore >= 60 ? 'text-amber-400' : 'text-rose-500'"
                                    stroke-linecap="round" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Status bar -->
                <div class="px-6 py-3 bg-gray-50 dark:bg-slate-800/60 border-t border-gray-100 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3 text-xs">
                        <span v-if="isFinalized" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 font-semibold">
                            <LockClosedIcon class="h-3.5 w-3.5" /> Finalized
                            <span v-if="evaluation.evaluator" class="font-normal opacity-80">by {{ evaluation.evaluator }}</span>
                        </span>
                        <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 font-semibold">
                            <PencilSquareIcon class="h-3.5 w-3.5" /> Draft
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400" :class="weightWarning ? 'text-amber-600 dark:text-amber-400' : ''">
                            <ScaleIcon class="h-3.5 w-3.5" /> Total weight: {{ totalWeight.toFixed(0) }}%
                            <span v-if="weightWarning" class="text-[10px]">(normalized to 100%)</span>
                        </span>
                    </div>
                    <div v-if="canManage" class="flex items-center gap-2">
                        <template v-if="!isFinalized">
                            <button @click="saveDraft" :disabled="saving" class="px-4 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50">Save Draft</button>
                            <button @click="finalize" :disabled="saving" class="px-4 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors disabled:opacity-50 flex items-center gap-1.5"><CheckBadgeIcon class="h-4 w-4" /> Finalize</button>
                        </template>
                        <button v-else-if="$page.props.auth.user.roles?.some(r => ['Super Admin','Operation Manager'].includes(r))" @click="reopen" class="px-4 py-1.5 rounded-lg text-xs font-semibold border border-amber-300 text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors">Reopen</button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- LEFT: score lines -->
                <div class="lg:col-span-2 space-y-5">
                    <div v-for="group in grouped" :key="group.category" class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-slate-700">
                            <span class="w-2 h-2 rounded-full" :class="catColor(group.category)" />
                            <h2 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">{{ group.label }}</h2>
                        </div>
                        <div class="divide-y divide-gray-50 dark:divide-slate-700/50">
                            <div v-for="line in group.items" :key="line.id" class="p-5">
                                <div class="flex items-start justify-between gap-4 mb-2">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ line.metric_name }}</h3>
                                            <span v-if="line.is_auto" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300"><BoltIcon class="h-3 w-3" /> Auto</span>
                                            <span v-else class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300"><PencilSquareIcon class="h-3 w-3" /> Manual</span>
                                        </div>
                                        <p class="text-[11px] text-gray-400 mt-0.5">Weight {{ weightShare(line) }}% of total · contributes {{ contribution(line).toFixed(1) }} pts</p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <span class="text-lg font-black" :class="normalized(line) >= 70 ? 'text-emerald-600 dark:text-emerald-400' : normalized(line) >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-500'">{{ normalized(line).toFixed(0) }}</span>
                                        <span class="text-xs text-gray-400">/100</span>
                                    </div>
                                </div>

                                <!-- Score input -->
                                <div class="flex items-center gap-3 mb-2">
                                    <input
                                        type="range" min="0" :max="line.max_score" step="1"
                                        v-model.number="line.raw_score"
                                        :disabled="line.is_auto || isFinalized || !canManage"
                                        class="flex-1 accent-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed" />
                                    <input
                                        type="number" min="0" :max="line.max_score" step="0.5"
                                        v-model.number="line.raw_score"
                                        :disabled="line.is_auto || isFinalized || !canManage"
                                        class="w-20 px-2 py-1.5 text-sm text-center border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none disabled:bg-gray-50 dark:disabled:bg-slate-800/50 disabled:cursor-not-allowed" />
                                </div>

                                <!-- Justification -->
                                <textarea
                                    v-model="line.justification"
                                    :disabled="isFinalized || !canManage"
                                    rows="1"
                                    :placeholder="line.is_auto ? 'Auto-computed — add an optional remark…' : 'Justify this score (visible in the record)…'"
                                    class="w-full text-xs rounded-lg border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700/50 px-2.5 py-1.5 text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 outline-none resize-none disabled:opacity-70"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Summary note -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Overall Evaluator Summary</label>
                        <textarea v-model="summaryNote" :disabled="isFinalized || !canManage" rows="3" placeholder="Summarize strengths, areas to improve and development goals for the period…" class="w-full text-sm rounded-xl border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700/50 px-3 py-2 text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 outline-none resize-none disabled:opacity-70"></textarea>
                    </div>
                </div>

                <!-- RIGHT: rubric management -->
                <div class="space-y-5">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5 sticky top-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide flex items-center gap-1.5"><ScaleIcon class="h-4 w-4 text-indigo-500" /> Rubric Weights</h2>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full" :class="weightWarning ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'">{{ totalWeight.toFixed(0) }}%</span>
                        </div>

                        <p class="text-[11px] text-gray-400 mb-4 flex items-start gap-1.5">
                            <InformationCircleIcon class="h-4 w-4 flex-shrink-0 mt-px" />
                            Weights are normalized to 100% when scoring, so they need not sum exactly.
                        </p>

                        <div class="space-y-3">
                            <div v-for="item in rubric" :key="item.id" class="group" :class="!item.is_active ? 'opacity-50' : ''">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-200 truncate">{{ item.name }}</span>
                                    <div v-if="canManage && !isFinalized" class="flex items-center gap-1 flex-shrink-0">
                                        <button @click="detach(item)" class="text-gray-300 hover:text-rose-500 transition-colors" title="Remove"><TrashIcon class="h-3.5 w-3.5" /></button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="range" min="0" max="40" step="1" v-model.number="item.weight" @change="updateWeight(item)" :disabled="!canManage || isFinalized" class="flex-1 accent-indigo-600 h-1 disabled:opacity-50" />
                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-300 w-9 text-right">{{ item.weight }}%</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="canManage && !isFinalized" class="mt-5 pt-4 border-t border-gray-100 dark:border-slate-700 space-y-2">
                            <button @click="showAddPicker = !showAddPicker" :disabled="!available.length" class="w-full inline-flex items-center justify-center gap-1.5 text-xs font-semibold py-2 rounded-lg border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                                <PlusIcon class="h-4 w-4" /> Add metric to rubric
                            </button>

                            <!-- Add picker -->
                            <div v-if="showAddPicker" class="bg-gray-50 dark:bg-slate-900/50 rounded-xl p-3 space-y-2 border border-gray-100 dark:border-slate-700">
                                <select v-model="addForm.evaluation_metric_id" class="w-full text-xs rounded-lg border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1.5 text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                                    <option value="">Select a metric…</option>
                                    <option v-for="a in available" :key="a.id" :value="a.id">{{ a.name }} ({{ a.category_label }})</option>
                                </select>
                                <div class="flex items-center gap-2">
                                    <input type="number" min="0.5" max="100" step="0.5" v-model.number="addForm.weight" placeholder="Weight" class="w-20 text-xs rounded-lg border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1.5 text-center text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none" />
                                    <button @click="attachMetric" :disabled="!addForm.evaluation_metric_id" class="flex-1 text-xs font-semibold py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors disabled:opacity-40">Add</button>
                                </div>
                            </div>

                            <button v-if="canManageMetrics" @click="showCustom = !showCustom" class="w-full inline-flex items-center justify-center gap-1.5 text-xs font-semibold py-2 rounded-lg border border-dashed border-gray-300 dark:border-slate-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                                <SparklesIcon class="h-4 w-4" /> Create custom metric
                            </button>

                            <!-- Custom metric form -->
                            <div v-if="showCustom" class="bg-gray-50 dark:bg-slate-900/50 rounded-xl p-3 space-y-2 border border-gray-100 dark:border-slate-700">
                                <input v-model="customForm.name" type="text" placeholder="Metric name" class="w-full text-xs rounded-lg border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1.5 text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none" />
                                <input v-model="customForm.description" type="text" placeholder="Short description" class="w-full text-xs rounded-lg border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1.5 text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none" />
                                <div class="flex items-center gap-2">
                                    <select v-model="customForm.category" class="flex-1 text-xs rounded-lg border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1.5 text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                                        <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                    <input type="number" min="0.5" max="100" step="0.5" v-model.number="customForm.default_weight" class="w-16 text-xs rounded-lg border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1.5 text-center text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none" />
                                </div>
                                <button @click="createCustom" :disabled="!customForm.name" class="w-full text-xs font-semibold py-1.5 rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition-colors disabled:opacity-40">Create &amp; add to catalogue</button>
                            </div>
                        </div>
                    </div>

                    <!-- Finalized meta -->
                    <div v-if="isFinalized" class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl border border-emerald-200 dark:border-emerald-800 p-5">
                        <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-400 mb-1">
                            <CheckBadgeIcon class="h-5 w-5" />
                            <span class="text-sm font-bold">Finalized Record</span>
                        </div>
                        <p class="text-xs text-emerald-600 dark:text-emerald-500">
                            {{ evaluation.evaluator ? 'Evaluated by ' + evaluation.evaluator : '' }}
                            <span v-if="evaluation.finalized_at">· {{ new Date(evaluation.finalized_at).toLocaleDateString() }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
