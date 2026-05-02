<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { PaperClipIcon, PaperAirplaneIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    taskId:        { type: Number, required: true },
    currentUserId: { type: Number, default: null },
    canModerate:   { type: Boolean, default: false },
});

const comments = ref([]);
const loading = ref(false);
const body = ref('');
const attachment = ref(null);
const fileInput = ref(null);
const sending = ref(false);
const thread = ref(null);

const formatTime = (iso) => new Date(iso).toLocaleString('en-US', {
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
});

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get(route('tasks.comments.index', props.taskId));
        comments.value = data;
        nextTick(() => { if (thread.value) thread.value.scrollTop = thread.value.scrollHeight; });
    } finally {
        loading.value = false;
    }
}

function pickFile() { fileInput.value?.click(); }
function onFileChange(e) { attachment.value = e.target.files[0] || null; }

async function send() {
    if (!body.value.trim() && !attachment.value) return;
    sending.value = true;

    const fd = new FormData();
    if (body.value.trim()) fd.append('body', body.value.trim());
    if (attachment.value)  fd.append('attachment', attachment.value);

    router.post(route('tasks.comments.store', props.taskId), fd, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: async () => {
            body.value = '';
            attachment.value = null;
            if (fileInput.value) fileInput.value.value = '';
            await load();
        },
        onFinish: () => { sending.value = false; },
    });
}

async function remove(commentId) {
    if (!confirm('Delete this comment?')) return;
    router.delete(route('tasks.comments.destroy', [props.taskId, commentId]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => load(),
    });
}

onMounted(load);
watch(() => props.taskId, load);
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Thread -->
        <div ref="thread" class="flex-1 overflow-y-auto px-1 py-2 space-y-3 max-h-[420px]">
            <div v-if="loading && comments.length === 0" class="text-center text-sm text-slate-400 py-6">
                Loading…
            </div>
            <div v-else-if="comments.length === 0" class="text-center py-8 text-slate-400">
                <div class="text-3xl mb-2">💬</div>
                <p class="text-sm">No comments yet. Start the conversation.</p>
            </div>

            <div v-for="c in comments" :key="c.id" class="flex items-start gap-3 group">
                <!-- Avatar -->
                <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-xs font-bold text-blue-700 dark:text-blue-300 flex-shrink-0">
                    {{ c.user?.name?.charAt(0) || '?' }}
                </div>
                <!-- Bubble -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ c.user?.name || 'Unknown' }}</span>
                        <span class="text-[11px] text-slate-400">{{ formatTime(c.created_at) }}</span>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-700/40 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 whitespace-pre-wrap break-words">
                        {{ c.body }}
                        <div v-if="c.attachment_url" class="mt-2">
                            <a
                                :href="c.attachment_url"
                                target="_blank"
                                class="inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:underline"
                            >
                                <PaperClipIcon class="h-3.5 w-3.5" /> View attachment
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Delete (own or admin) -->
                <button
                    v-if="canModerate || (currentUserId && c.user?.id === currentUserId)"
                    @click="remove(c.id)"
                    class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-500 transition p-1"
                    title="Delete comment"
                >
                    <TrashIcon class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Composer -->
        <form @submit.prevent="send" class="border-t border-slate-100 dark:border-slate-700 pt-3 mt-3">
            <div class="flex items-end gap-2">
                <textarea
                    v-model="body"
                    rows="2"
                    placeholder="Write a comment, share an update, ask a question…"
                    class="flex-1 resize-none rounded-lg border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500"
                />
                <input
                    ref="fileInput"
                    type="file"
                    class="hidden"
                    @change="onFileChange"
                    accept=".pdf,.jpg,.jpeg,.png,.xlsx,.docx,.txt"
                />
                <button
                    type="button"
                    @click="pickFile"
                    class="h-10 w-10 flex items-center justify-center rounded-lg ring-1 ring-slate-200 dark:ring-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-300"
                    :title="attachment?.name || 'Attach file'"
                >
                    <PaperClipIcon class="h-5 w-5" />
                </button>
                <button
                    type="submit"
                    :disabled="sending || (!body.trim() && !attachment)"
                    class="h-10 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold flex items-center gap-1.5 transition"
                >
                    <PaperAirplaneIcon class="h-4 w-4" />
                    <span class="text-sm">Send</span>
                </button>
            </div>
            <p v-if="attachment" class="mt-2 text-xs text-slate-500">
                📎 {{ attachment.name }}
                <button type="button" @click="attachment = null; fileInput && (fileInput.value = '')" class="ml-2 text-red-500 hover:text-red-700">remove</button>
            </p>
        </form>
    </div>
</template>
