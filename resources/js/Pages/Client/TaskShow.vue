<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import { 
    ChevronLeftIcon, 
    CloudArrowUpIcon, 
    ChatBubbleLeftRightIcon, 
    DocumentIcon,
    PaperClipIcon
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    task: Object
});

const form = useForm({
    files: null
});

const uploadFiles = () => {
    form.post(route('employee.tasks.upload', props.task.id), {
        preserveScroll: true,
        onSuccess: () => form.reset()
    });
};
</script>

<template>
    <Head :title="task.template.name" />

    <ClientLayout>
        <div class="max-w-4xl mx-auto">
            <Link :href="route('client.dashboard')" class="inline-flex items-center text-sm text-slate-500 hover:text-blue-600 mb-6 group transition-colors">
                <ChevronLeftIcon class="h-4 w-4 mr-1 group-hover:-translate-x-1 transition-transform" />
                Back to Dashboard
            </Link>

            <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden mb-8 transition-colors">
                <div class="p-8 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border border-blue-100 dark:border-blue-800">
                            {{ task.status }}
                        </span>
                        <span class="text-sm text-slate-400 font-medium">Due Date: {{ new Date(task.due_date).toLocaleDateString() }}</span>
                    </div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">{{ task.template.name }}</h1>
                    <p class="text-slate-500 dark:text-slate-400">{{ task.template.description || 'Please provide the required documentation for this tax follow-up.' }}</p>
                </div>

                <div class="p-8 bg-slate-50/50 dark:bg-slate-800/50">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 flex items-center">
                                <DocumentIcon class="h-5 w-5 mr-2 text-blue-600 dark:text-blue-400" />
                                Instructions
                            </h3>
                            <div class="prose prose-sm text-slate-600 dark:text-slate-400">
                                <p>To complete this task, please upload the following documents:</p>
                                <ul class="list-disc pl-5 space-y-1 mt-2">
                                    <li>Scanned copy of receipts</li>
                                    <li>Monthly summary report</li>
                                    <li>Bank statements (if applicable)</li>
                                </ul>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 flex items-center">
                                <CloudArrowUpIcon class="h-5 w-5 mr-2 text-blue-600 dark:text-blue-400" />
                                File Upload
                            </h3>
                            <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-6 text-center hover:border-blue-400 dark:hover:border-blue-400 transition-colors">
                                <input 
                                    type="file" 
                                    multiple 
                                    id="file-upload" 
                                    class="hidden" 
                                    @input="form.files = $event.target.files; uploadFiles()" 
                                />
                                <label for="file-upload" class="cursor-pointer">
                                    <CloudArrowUpIcon class="h-10 w-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">Click to upload documents</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">PDF, JPG or PNG up to 10MB</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 p-8">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-6 flex items-center">
                            <ChatBubbleLeftRightIcon class="h-5 w-5 mr-2 text-blue-600 dark:text-blue-400" />
                            Activity & Comments
                        </h3>
                        
                        <div class="space-y-6">
                            <div v-for="comment in task.comments" :key="comment.id" class="flex space-x-4 group">
                                <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-700 flex-shrink-0 flex items-center justify-center font-bold text-slate-500 dark:text-slate-400 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30 transition-colors">
                                    {{ comment.user.name.charAt(0) }}
                                </div>
                                <div class="flex-1">
                                    <div class="bg-slate-50 dark:bg-slate-900/50 rounded-2xl p-4 border border-transparent hover:border-slate-100 dark:hover:border-slate-700 transition-all">
                                        <p class="text-sm font-bold text-slate-900 dark:text-white mb-1">{{ comment.user.name }}</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ comment.content }}</p>
                                    </div>
                                    <span class="text-[10px] text-slate-400 mt-1 block ml-2">{{ new Date(comment.created_at).toLocaleString() }}</span>
                                </div>
                            </div>

                            <div v-if="!task.comments.length" class="text-center py-8 text-slate-400 dark:text-slate-500 italic text-sm">
                                No comments yet.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 flex items-center">
                            <PaperClipIcon class="h-5 w-5 mr-2 text-blue-600 dark:text-blue-400" />
                            Uploaded Files
                        </h3>
                        <div v-if="task.document_path && task.document_path.length" class="space-y-2">
                            <div v-for="(file, index) in task.document_path" :key="index" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/50 text-xs border border-slate-100 dark:border-slate-700">
                                <a :href="route('tasks.documents.download', { task: task.id, path: file })" target="_blank" class="truncate flex-1 font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                    {{ file.split('/').pop() }}
                                </a>
                                <CheckCircleIcon class="h-4 w-4 text-green-500 ml-2" />
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-slate-400 dark:text-slate-500 text-xs italic font-medium">
                            No files uploaded yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
