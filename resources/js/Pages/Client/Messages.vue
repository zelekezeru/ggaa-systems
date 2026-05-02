<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import { PaperClipIcon, PaperAirplaneIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline';
import { ref, nextTick, onMounted, computed } from 'vue';

const props = defineProps({
    messages: Array,
    client:   Object,
});

const page    = usePage();
const myId    = computed(() => page.props.auth.user?.id);

// ── Form state ────────────────────────────────────────────────────────────────
const body       = ref('');
const attachment = ref(null);
const fileInput  = ref(null);
const sending    = ref(false);
const thread     = ref(null);

const formatTime = (iso) => {
    const d = new Date(iso);
    return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const scrollToBottom = () => {
    nextTick(() => {
        if (thread.value) thread.value.scrollTop = thread.value.scrollHeight;
    });
};

onMounted(scrollToBottom);

const pickFile = () => fileInput.value?.click();

const onFileChange = (e) => {
    attachment.value = e.target.files[0] ?? null;
};

const send = () => {
    if (!body.value.trim() && !attachment.value) return;
    sending.value = true;

    const form = new FormData();
    if (body.value.trim()) form.append('body', body.value.trim());
    if (attachment.value) form.append('attachment', attachment.value);

    router.post(route('client.messages.store'), form, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            body.value = '';
            attachment.value = null;
            if (fileInput.value) fileInput.value.value = '';
            scrollToBottom();
        },
        onFinish: () => { sending.value = false; },
    });
};

const onKeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
    }
};
</script>

<template>
    <Head title="Messages — Client Portal" />
    <ClientLayout>
        <div class="flex flex-col h-[calc(100vh-64px)] max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            <!-- Header -->
            <div class="flex items-center gap-3 mb-4">
                <Link :href="route('client.dashboard')" class="rounded-full p-2 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                    <ArrowLeftIcon class="h-5 w-5 text-gray-600 dark:text-slate-400" />
                </Link>
                <div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white">Messages</h1>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Your conversation with your assigned accountant</p>
                </div>
            </div>

            <!-- Thread -->
            <div
                ref="thread"
                class="flex-1 overflow-y-auto bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm p-4 space-y-4 scroll-smooth transition-colors"
            >
                <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center py-16">
                    <div class="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center mb-4 transition-colors">
                        <PaperAirplaneIcon class="h-8 w-8 text-blue-400 dark:text-blue-500" />
                    </div>
                    <p class="text-gray-500 dark:text-slate-400 text-sm font-medium">No messages yet. Start the conversation below!</p>
                </div>

                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="flex"
                    :class="message.is_mine ? 'justify-end' : 'justify-start'"
                >
                    <!-- Avatar (firm) -->
                    <div v-if="!message.is_mine" class="flex-shrink-0 mr-2 self-end mb-4">
                        <div class="w-8 h-8 rounded-full bg-blue-900 dark:bg-blue-700 flex items-center justify-center text-white text-xs font-bold border border-white/10 shadow-sm">
                            {{ (message.sender?.name ?? 'F')[0].toUpperCase() }}
                        </div>
                    </div>

                    <div class="max-w-[85%] sm:max-w-[75%] space-y-1">
                        <p v-if="!message.is_mine" class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500 ml-1 mb-0.5">{{ message.sender?.name ?? 'Firm' }}</p>

                        <div
                            class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed shadow-sm transition-all"
                            :class="message.is_mine
                                ? 'bg-blue-900 dark:bg-blue-600 text-white rounded-br-sm'
                                : 'bg-gray-100 dark:bg-slate-900 text-gray-900 dark:text-slate-200 rounded-bl-sm'"
                        >
                            {{ message.body }}
                            <div v-if="message.attachment" class="mt-2 pt-2 border-t border-white/10 dark:border-white/5">
                                <a
                                    :href="message.attachment"
                                    target="_blank"
                                    class="flex items-center gap-1.5 text-xs font-bold hover:underline transition-all"
                                    :class="message.is_mine ? 'text-blue-200' : 'text-blue-600 dark:text-blue-400'"
                                >
                                    <PaperClipIcon class="h-3.5 w-3.5" />
                                    View attachment
                                </a>
                            </div>
                        </div>

                        <p class="text-[10px] text-gray-400 dark:text-slate-500 px-1" :class="message.is_mine ? 'text-right' : 'text-left'">
                            {{ formatTime(message.created_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Compose box -->
            <div class="mt-3 bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm p-3 transition-colors">
                <!-- Attachment preview -->
                <div v-if="attachment" class="flex items-center gap-2 mb-2 px-3 py-2 bg-blue-50 dark:bg-blue-900/30 rounded-xl text-xs text-blue-700 dark:text-blue-400 border border-blue-100 dark:border-blue-800/50">
                    <PaperClipIcon class="h-4 w-4 shrink-0" />
                    <span class="truncate flex-1 font-semibold">{{ attachment.name }}</span>
                    <button @click="attachment = null; fileInput.value = ''" class="text-blue-400 hover:text-red-500 transition-colors p-1">✕</button>
                </div>

                <div class="flex items-end gap-2">
                    <textarea
                        v-model="body"
                        @keydown="onKeydown"
                        rows="2"
                        placeholder="Type your message… (Enter to send, Shift+Enter for new line)"
                        class="flex-1 resize-none rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    />

                    <div class="flex flex-col gap-1.5 pb-0.5">
                        <button
                            @click="pickFile"
                            type="button"
                            class="rounded-full p-2.5 text-gray-400 hover:text-gray-700 dark:hover:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                            title="Attach file"
                        >
                            <PaperClipIcon class="h-5 w-5" />
                        </button>
                        <input ref="fileInput" type="file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" @change="onFileChange" />

                        <button
                            @click="send"
                            :disabled="sending || (!body.trim() && !attachment)"
                            class="rounded-full p-2.5 bg-blue-900 dark:bg-blue-600 text-white hover:bg-blue-800 dark:hover:bg-blue-500 disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-md active:scale-95"
                            title="Send"
                        >
                            <PaperAirplaneIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
