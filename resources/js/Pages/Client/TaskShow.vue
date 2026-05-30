<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import {
    ChevronLeftIcon,
    CloudArrowUpIcon,
    ChatBubbleLeftRightIcon,
    DocumentIcon,
    PaperClipIcon,
    ArrowDownTrayIcon,
    CheckCircleIcon as CheckCircleOutline,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    task: Object
});

const form = useForm({ files: null });
const isDragging = ref(false);

const PROGRESS = {
    'Waiting on Client': 5,
    'To Do':             35,
    'In Review':         70,
    'Done':              100,
};

const STATUS_LABEL = {
    'Waiting on Client': 'Waiting on Your Documents',
    'To Do':             'In Progress',
    'In Review':         'Under Review',
    'Done':              'Completed',
};

const STATUS_COLOR = {
    'Waiting on Client': 'bg-amber-100 text-amber-800 border-amber-200',
    'To Do':             'bg-blue-100 text-blue-800 border-blue-200',
    'In Review':         'bg-purple-100 text-purple-800 border-purple-200',
    'Done':              'bg-emerald-100 text-emerald-800 border-emerald-200',
};

function handleDrop(e) {
    isDragging.value = false;
    const files = e.dataTransfer?.files;
    if (files?.length) submitFiles(files);
}

function handleFileInput(e) {
    const files = e.target.files;
    if (files?.length) submitFiles(files);
}

function submitFiles(files) {
    form.files = files;
    form.post(route('client.tasks.upload', props.task.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { form.reset(); },
    });
}
</script>

<template>
    <Head :title="task.template.name" />

    <ClientLayout>
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Back -->
            <Link :href="route('client.dashboard')" class="inline-flex items-center text-sm text-slate-500 hover:text-blue-600 group transition-colors">
                <ChevronLeftIcon class="h-4 w-4 mr-1.5 group-hover:-translate-x-1 transition-transform" />
                Back to Dashboard
            </Link>

            <!-- Header card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                <!-- Risk colour strip -->
                <div class="h-1.5 w-full"
                     :class="{
                         'bg-amber-400': task.status === 'Waiting on Client',
                         'bg-blue-500':  task.status === 'To Do',
                         'bg-purple-500': task.status === 'In Review',
                         'bg-emerald-500': task.status === 'Done',
                     }" />
                <div class="p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                        <div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border"
                                  :class="STATUS_COLOR[task.status] ?? 'bg-gray-100 text-gray-600 border-gray-200'">
                                {{ STATUS_LABEL[task.status] ?? task.status }}
                            </span>
                        </div>
                        <span class="text-sm text-slate-400 font-medium">
                            Due: {{ new Date(task.due_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">{{ task.template.name }}</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm">{{ task.template.description || 'Please provide the required documentation for this task.' }}</p>

                    <!-- Task notes (from accountant) -->
                    <div v-if="task.notes" class="mt-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/30 rounded-xl p-4">
                        <p class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wide mb-1">Note from your accountant</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ task.notes }}</p>
                    </div>

                    <!-- Progress bar -->
                    <div class="mt-5">
                        <div class="flex justify-between text-[10px] font-semibold text-gray-400 mb-1 px-0.5">
                            <span :class="{'text-blue-600 dark:text-blue-400': PROGRESS[task.status] >= 5}">Pending</span>
                            <span :class="{'text-blue-600 dark:text-blue-400': PROGRESS[task.status] >= 35}">In Progress</span>
                            <span :class="{'text-blue-600 dark:text-blue-400': PROGRESS[task.status] >= 70}">Review</span>
                            <span :class="{'text-emerald-600 dark:text-emerald-400': PROGRESS[task.status] === 100}">Done</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700"
                                 :class="{
                                     'bg-amber-400': task.status === 'Waiting on Client',
                                     'bg-blue-500':  task.status === 'To Do',
                                     'bg-purple-500': task.status === 'In Review',
                                     'bg-emerald-500': task.status === 'Done',
                                 }"
                                 :style="{ width: (PROGRESS[task.status] ?? 0) + '%' }" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                <!-- LEFT: Upload + Comments -->
                <div class="lg:col-span-3 space-y-6">

                    <!-- Upload zone -->
                    <div v-if="task.status !== 'Done'" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                            <CloudArrowUpIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            Upload Documents
                        </h3>
                        <div
                            class="border-2 border-dashed rounded-2xl p-8 text-center transition-colors"
                            :class="isDragging
                                ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20'
                                : 'border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-600'"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                        >
                            <input type="file" multiple id="file-upload" class="hidden" @change="handleFileInput" accept=".pdf,.jpg,.jpeg,.png,.xlsx,.docx" />
                            <label for="file-upload" class="cursor-pointer block">
                                <CloudArrowUpIcon class="h-10 w-10 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                                <p class="text-sm font-semibold text-slate-700 dark:text-white">
                                    <span class="text-blue-600 dark:text-blue-400">Click to browse</span> or drag &amp; drop
                                </p>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">PDF, JPG, PNG, XLSX, DOCX — up to 10 MB each</p>
                            </label>
                        </div>
                        <div v-if="form.processing" class="mt-3 flex items-center justify-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Uploading…
                        </div>
                        <p v-if="form.errors.files" class="mt-2 text-xs text-red-600 text-center">{{ form.errors.files }}</p>
                    </div>

                    <!-- Comments -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-5 flex items-center gap-2">
                            <ChatBubbleLeftRightIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            Activity &amp; Comments
                        </h3>
                        <div class="space-y-5">
                            <div v-for="comment in task.comments" :key="comment.id" class="flex gap-3 group">
                                <div class="h-9 w-9 rounded-full bg-slate-100 dark:bg-slate-700 flex-shrink-0 flex items-center justify-center font-bold text-slate-500 dark:text-slate-400 text-sm">
                                    {{ comment.user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-3.5 border border-transparent group-hover:border-slate-100 dark:group-hover:border-slate-700 transition-colors">
                                        <p class="text-xs font-bold text-slate-700 dark:text-white mb-1">{{ comment.user.name }}</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ comment.body ?? comment.content }}</p>
                                        <div v-if="comment.attachment_url" class="mt-2">
                                            <a :href="comment.attachment_url" target="_blank"
                                               class="inline-flex items-center gap-1.5 text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                                <PaperClipIcon class="h-3.5 w-3.5" />
                                                View attachment
                                            </a>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-slate-400 mt-1 block ml-1">{{ new Date(comment.created_at).toLocaleString() }}</span>
                                </div>
                            </div>
                            <div v-if="!task.comments?.length" class="text-center py-8 text-slate-400 dark:text-slate-500 italic text-sm">
                                No comments yet.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Files sidebar -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5 sticky top-6">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                            <PaperClipIcon class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            Uploaded Files
                            <span v-if="task.document_path?.length"
                                  class="ml-auto text-xs font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 px-2 py-0.5 rounded-full">
                                {{ task.document_path.length }}
                            </span>
                        </h3>

                        <div v-if="task.document_path && task.document_path.length" class="space-y-2">
                            <a v-for="(file, idx) in task.document_path" :key="idx"
                               :href="route('tasks.documents.download', { task: task.id, path: file })"
                               target="_blank"
                               class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700 hover:border-blue-200 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all group">
                                <DocumentIcon class="h-5 w-5 text-slate-400 group-hover:text-blue-500 flex-shrink-0 transition-colors" />
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 truncate flex-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ file.split('/').pop() }}
                                </span>
                                <ArrowDownTrayIcon class="h-4 w-4 text-slate-300 group-hover:text-blue-500 flex-shrink-0 transition-colors" />
                            </a>
                        </div>

                        <div v-else class="flex flex-col items-center justify-center py-10 text-slate-400 dark:text-slate-500">
                            <CloudArrowUpIcon class="h-10 w-10 mb-2 opacity-30" />
                            <p class="text-xs font-medium italic">No files yet</p>
                            <p v-if="task.status !== 'Done'" class="text-[10px] mt-1 text-slate-300">Upload documents using the form</p>
                        </div>

                        <!-- Completed notice -->
                        <div v-if="task.status === 'Done' && task.completed_at"
                             class="mt-4 flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-3">
                            <CheckCircleIcon class="h-5 w-5 text-emerald-500 flex-shrink-0" />
                            <div>
                                <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400">Task Completed</p>
                                <p class="text-[10px] text-emerald-600 dark:text-emerald-500">
                                    {{ new Date(task.completed_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
