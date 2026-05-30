<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    task: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    notes: props.task?.notes ?? '',
    attachments: null,
});

function submit() {
    form.post(route('employee.tasks.details', props.task.id), {
        forceFormData: true,
        onSuccess: () => {
            form.reset('attachments');
            emit('close');
        },
    });
}

const riskColors = {
    '🟢': 'text-green-600',
    '🟡': 'text-yellow-500',
    '🔴': 'text-red-500',
};

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
}

function progressPercent(status) {
    if (status === 'Waiting on Client') return 5;
    if (status === 'To Do') return 35;
    if (status === 'In Review') return 70;
    if (status === 'Done') return 100;
    return 0;
}

const STATUS_ORDER = ['Waiting on Client', 'To Do', 'In Review', 'Done'];

const nextStageLabel = computed(() => {
    if (props.task.status === 'Waiting on Client') return 'Move to In Progress';
    if (props.task.status === 'To Do') return 'Submit for Review';
    return null;
});

function moveToNextStage() {
    const currentIdx = STATUS_ORDER.indexOf(props.task.status);
    if (currentIdx === -1 || currentIdx >= 2) return; 

    const nextStatus = STATUS_ORDER[currentIdx + 1];
    
    router.patch(route('employee.tasks.status', props.task.id), { status: nextStatus }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit('close');
        }
    });
}
</script>

<template>
    <!-- Backdrop -->
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @click.self="$emit('close')"
        >
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$emit('close')" />

            <!-- Modal panel -->
            <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">

                <!-- Header strip — colour-coded by risk -->
                <div
                    class="h-1.5 w-full"
                    :class="{
                        'bg-green-400': task.risk_level === '🟢',
                        'bg-yellow-400': task.risk_level === '🟡',
                        'bg-red-500':   task.risk_level === '🔴',
                    }"
                />

                <div class="p-6">
                    <!-- Title row -->
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">
                                {{ task.client?.company_name }}
                            </p>
                            <h2 class="text-xl font-bold text-gray-900 leading-tight">
                                {{ task.template?.name ?? $t('task') }}
                            </h2>
                        </div>
                        <button
                            @click="$emit('close')"
                            class="shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Meta grid -->
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 mb-0.5">{{ $t('due_date') }}</p>
                            <p class="text-sm font-semibold text-gray-800">{{ formatDate(task.due_date) }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 mb-0.5">{{ $t('status') }}</p>
                            <p class="text-sm font-semibold text-gray-800">{{ task.status }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 mb-0.5">{{ $t('client_risk') }}</p>
                            <p class="text-sm font-semibold">
                                <span :class="riskColors[task.risk_level] ?? ''">{{ task.risk_level }}</span>
                                {{ task.risk_level === '🟢' ? $t('low') : task.risk_level === '🟡' ? $t('medium') : $t('high') }}
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 mb-0.5">{{ $t('complexity') }}</p>
                            <p class="text-sm font-semibold text-gray-800">{{ $t('score') }} {{ task.client?.complexity_score }}</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-4 mb-6">
                        <p class="text-xs text-gray-500 mb-2 font-semibold uppercase tracking-wider">{{ $t('task_progress') || 'Task Progress' }}</p>
                        <div class="relative w-full">
                            <div class="overflow-hidden h-2 mb-2 text-xs flex rounded-full bg-gray-200">
                                <div :style="'width: ' + progressPercent(task.status) + '%'" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500 transition-all duration-500"></div>
                            </div>
                            <div class="flex justify-between text-[10px] text-gray-500 font-medium px-1">
                                <span :class="{'text-indigo-600 font-bold': progressPercent(task.status) >= 5}">Pending</span>
                                <span :class="{'text-indigo-600 font-bold': progressPercent(task.status) >= 35}">In Progress</span>
                                <span :class="{'text-indigo-600 font-bold': progressPercent(task.status) >= 70}">Review</span>
                                <span :class="{'text-indigo-600 font-bold': progressPercent(task.status) === 100}">Done</span>
                            </div>
                        </div>
                    </div>

                    <!-- Attached Files — always visible regardless of status -->
                    <div v-if="task.document_path && task.document_path.length" class="mb-5 bg-indigo-50/50 p-3 rounded-lg border border-indigo-100">
                        <p class="text-xs font-bold text-indigo-900 mb-2 uppercase tracking-wide flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                            {{ $t('attached_files') || 'Attached Files' }}
                            <span class="text-indigo-500 font-medium normal-case tracking-normal">({{ task.document_path.length }})</span>
                        </p>
                        <div class="space-y-1.5">
                            <template v-for="(path, ix) in task.document_path" :key="ix">
                                <a :href="route('tasks.documents.download', { task: task.id, path: path })" target="_blank" class="flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 transition-colors bg-white px-2.5 py-1.5 rounded-md shadow-sm border border-indigo-50 group">
                                    <svg class="h-4 w-4 flex-shrink-0 text-indigo-400 group-hover:text-indigo-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="truncate">{{ path.split('/').pop() || `File ${ix + 1}` }}</span>
                                </a>
                            </template>
                        </div>
                    </div>

                    <!-- Task Notes & Documents Form — only for active tasks -->
                    <template v-if="task.status !== 'Done'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('task_notes') || 'Task Notes' }} <span class="text-gray-400 font-normal">({{ $t('optional') }})</span>
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            :placeholder="$t('manager_notes_placeholder') || 'Provide any notes...'"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none mb-4"
                        />
                        <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">{{ form.errors.notes }}</p>

                        <!-- Upload Documents Zone -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $t('upload_documents') || 'Upload Documents' }}
                            </label>
                            <input
                                type="file"
                                multiple
                                @input="form.attachments = $event.target.files"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-colors"
                            />
                            <p v-if="form.errors.attachments" class="mt-1 text-xs text-red-600">{{ form.errors.attachments }}</p>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <button
                                v-if="nextStageLabel"
                                type="button"
                                @click="moveToNextStage"
                                class="w-full py-2.5 rounded-xl bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600 transition-colors shadow-sm mb-2"
                            >
                                {{ nextStageLabel }} &rarr;
                            </button>
                            <button
                                type="button"
                                @click="$emit('close')"
                                class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                            >
                                {{ $t('cancel') }}
                            </button>
                            <button
                                type="button"
                                @click="submit"
                                :disabled="form.processing"
                                class="flex-1 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                            >
                                <span v-if="form.processing">{{ $t('saving') }}</span>
                                <span v-else>{{ $t('save_details') || 'Save Details' }}</span>
                            </button>
                        </div>
                    </template>

                    <!-- Already done state -->
                    <template v-else>
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                            <p class="text-green-700 font-semibold text-sm text-center">{{ $t('completed') }} {{ formatDate(task.completed_at) }}</p>
                            <p v-if="task.notes" class="text-gray-600 text-xs mt-2 whitespace-pre-wrap border-t border-green-100 pt-2">{{ task.notes }}</p>
                        </div>
                        <button
                            @click="$emit('close')"
                            class="mt-4 w-full py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            {{ $t('close') }}
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </Teleport>
</template>
