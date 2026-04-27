<script setup>
import { ref, watch } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { XMarkIcon, PaperClipIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    task: Object,
    open: Boolean,
});

const emit = defineEmits(['close']);

// Form for uploading reports/documents
const form = useForm({
    attachment: null,
    comment: '',
});

const submitReport = () => {
    // Post the form data to our new controller endpoint
    form.post(route('employee.tasks.upload', props.task?.id), {
        preserveScroll: true,
        // Inertia handles the file upload progress automatically!
        onSuccess: () => {
            form.reset();
            toast.success(props.$t('report_submitted_success') || "Report submitted for review!");
            emit('close');
        },
        onError: (errors) => {
            toast.error(errors.attachment || props.$t('upload_failed_msg') || "Failed to upload document.");
        }
    });
};
</script>

<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-[100]" @close="emit('close')">
            <TransitionChild as="template" enter="ease-in-out duration-500" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-500" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <TransitionChild as="template" enter="transform transition ease-in-out duration-500 sm:duration-700" enter-from="translate-x-full" enter-to="translate-x-0" leave="transform transition ease-in-out duration-500 sm:duration-700" leave-from="translate-x-0" leave-to="translate-x-full">
                            <DialogPanel class="pointer-events-auto w-screen max-w-md">
                                <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                                    <div class="bg-blue-900 px-4 py-6 sm:px-6">
                                        <div class="flex items-center justify-between">
                                            <DialogTitle class="text-base font-semibold leading-6 text-white">
                                                {{ $t('task_details') }}: {{ task?.template?.name }}
                                            </DialogTitle>
                                            <div class="ml-3 flex h-7 items-center">
                                                <button type="button" class="rounded-md bg-blue-900 text-blue-200 hover:text-white" @click="emit('close')">
                                                    <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mt-1">
                                            <p class="text-sm text-blue-300">{{ task?.client?.company_name }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="relative flex-1 px-4 py-6 sm:px-6">
                                        <div class="rounded-lg bg-gray-50 p-4 mb-6 border border-gray-200">
                                            <div class="flex justify-between items-center mb-2">
                                                <span class="text-xs font-bold uppercase text-gray-500">{{ $t('deadline') }}</span>
                                                <span class="text-sm font-medium text-gray-900">{{ task?.due_date }}</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                <div class="bg-blue-600 h-1.5 rounded-full" :style="{ width: task?.status === 'Done' ? '100%' : '50%' }"></div>
                                            </div>
                                        </div>

                                        <form @submit.prevent="submitReport" class="space-y-4">
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">{{ $t('submit_final_report') }}</label>
                                                <input 
                                                    type="file" 
                                                    @input="form.attachment = $event.target.files[0]" 
                                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                                    accept=".pdf,.jpg,.png,.xlsx"
                                                />
                                                <p v-if="form.errors.attachment" class="mt-2 text-sm text-red-600">{{ form.errors.attachment }}</p>
                                            </div>

                                            <button 
                                                type="submit" 
                                                :disabled="form.processing || !form.attachment" 
                                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                                            >
                                                <span v-if="form.processing">{{ $t('uploading') }} ({{ form.progress ? form.progress.percentage : 0 }}%)...</span>
                                                <span v-else>{{ $t('complete_send_review') }}</span>
                                            </button>
                                        </form>

                                        <div class="mt-10">
                                            <h3 class="text-sm font-medium text-gray-900">{{ $t('communication_history') }}</h3>
                                            <div class="mt-4 flow-root">
                                                <ul role="list" class="-mb-8">
                                                    <li v-for="msg in task?.messages" :key="msg.id">
                                                        <div class="relative pb-8">
                                                            <div class="relative flex space-x-3">
                                                                <div>
                                                                    <span class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                                                        <ChatBubbleLeftRightIcon class="h-5 w-5 text-gray-500" />
                                                                    </span>
                                                                </div>
                                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                                    <p class="text-sm text-gray-500">{{ msg.body }}</p>
                                                                    <div class="whitespace-nowrap text-right text-xs text-gray-400">{{ msg.created_at }}</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>