<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { PaperClipIcon, PaperAirplaneIcon, ArrowLeftIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline';
import { ref, nextTick, onMounted, computed } from 'vue';

const props = defineProps({
    messages:        Array,
    client:          Object,
    assignedClients: Array,
});

const page = usePage();
const myId = computed(() => page.props.auth.user?.id);

// ── Compose state ─────────────────────────────────────────────────────────────
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

    router.post(route('employee.messages.store', props.client.id), form, {
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

const switchClient = (clientId) => {
    router.get(route('employee.client.messages', clientId));
};
</script>

<template>
    <Head :title="`Messages — ${client.company_name}`" />
    <EmployeeLayout>
        <div class="flex h-[calc(100vh-64px)]">

            <!-- ── Sidebar: Assigned Clients ── -->
            <div class="hidden lg:flex flex-col w-64 border-r border-gray-200 bg-white shrink-0">
                <div class="px-4 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Client Threads</h2>
                </div>
                <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                    <button
                        v-for="c in assignedClients"
                        :key="c.id"
                        @click="switchClient(c.id)"
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 transition-colors flex items-center gap-3"
                        :class="c.id === client.id ? 'bg-blue-50 border-r-2 border-blue-600' : ''"
                    >
                        <div class="w-8 h-8 rounded-full bg-blue-900 flex items-center justify-center text-white text-xs font-bold shrink-0">
                            {{ c.company_name[0].toUpperCase() }}
                        </div>
                        <span class="text-sm font-medium truncate" :class="c.id === client.id ? 'text-blue-900' : 'text-gray-700'">
                            {{ c.company_name }}
                        </span>
                    </button>

                    <div v-if="!assignedClients.length" class="px-4 py-6 text-center text-xs text-gray-400">
                        No clients assigned
                    </div>
                </div>
            </div>

            <!-- ── Main chat area ── -->
            <div class="flex flex-col flex-1 min-w-0 bg-gray-50">

                <!-- Chat header -->
                <div class="flex items-center gap-3 px-4 sm:px-6 py-3 bg-white border-b border-gray-200 shrink-0">
                    <a :href="route('employee.workspace')" class="lg:hidden rounded-full p-1.5 hover:bg-gray-100 transition-colors">
                        <ArrowLeftIcon class="h-5 w-5 text-gray-600" />
                    </a>
                    <div class="w-9 h-9 rounded-full bg-blue-900 flex items-center justify-center text-white text-sm font-bold">
                        {{ client.company_name[0].toUpperCase() }}
                    </div>
                    <div>
                        <h1 class="text-sm font-bold text-gray-900">{{ client.company_name }}</h1>
                        <p class="text-xs text-gray-500">Client conversation thread</p>
                    </div>
                </div>

                <!-- Thread -->
                <div
                    ref="thread"
                    class="flex-1 overflow-y-auto px-4 sm:px-6 py-4 space-y-4 scroll-smooth"
                >
                    <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center py-16">
                        <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center mb-4">
                            <ChatBubbleLeftRightIcon class="h-8 w-8 text-blue-400" />
                        </div>
                        <p class="text-gray-500 text-sm">No messages yet. Send the first message to this client.</p>
                    </div>

                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="flex"
                        :class="message.is_mine ? 'justify-end' : 'justify-start'"
                    >
                        <!-- Client avatar -->
                        <div v-if="!message.is_mine" class="flex-shrink-0 mr-2">
                            <div class="w-8 h-8 rounded-full bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-800 text-xs font-bold">
                                {{ (message.sender?.name ?? 'C')[0].toUpperCase() }}
                            </div>
                        </div>

                        <div class="max-w-[70%] space-y-1">
                            <p v-if="!message.is_mine" class="text-xs text-gray-500 ml-1">{{ message.sender?.name ?? 'Client' }}</p>

                            <div
                                class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed"
                                :class="message.is_mine
                                    ? 'bg-blue-900 text-white rounded-br-sm'
                                    : 'bg-white text-gray-900 rounded-bl-sm border border-gray-200'"
                            >
                                {{ message.body }}
                                <div v-if="message.attachment" class="mt-2">
                                    <a
                                        :href="message.attachment"
                                        target="_blank"
                                        class="flex items-center gap-1.5 text-xs underline"
                                        :class="message.is_mine ? 'text-blue-200' : 'text-blue-600'"
                                    >
                                        <PaperClipIcon class="h-3.5 w-3.5" />
                                        View attachment
                                    </a>
                                </div>
                            </div>

                            <p class="text-[10px] text-gray-400 px-1" :class="message.is_mine ? 'text-right' : 'text-left'">
                                {{ formatTime(message.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Compose box -->
                <div class="px-4 sm:px-6 py-3 bg-white border-t border-gray-200 shrink-0">
                    <div v-if="attachment" class="flex items-center gap-2 mb-2 px-2 py-1.5 bg-blue-50 rounded-lg text-xs text-blue-700">
                        <PaperClipIcon class="h-4 w-4 shrink-0" />
                        <span class="truncate flex-1">{{ attachment.name }}</span>
                        <button @click="attachment = null" class="text-blue-400 hover:text-red-500 transition-colors">✕</button>
                    </div>

                    <div class="flex items-end gap-2">
                        <textarea
                            v-model="body"
                            @keydown="onKeydown"
                            rows="2"
                            :placeholder="`Reply to ${client.company_name}… (Enter to send)`"
                            class="flex-1 resize-none rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />

                        <div class="flex flex-col gap-1.5 pb-0.5">
                            <button @click="pickFile" type="button" class="rounded-full p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors" title="Attach file">
                                <PaperClipIcon class="h-5 w-5" />
                            </button>
                            <input ref="fileInput" type="file" accept=".pdf,.jpg,.jpeg,.png,.xlsx,.docx" class="hidden" @change="onFileChange" />

                            <button
                                @click="send"
                                :disabled="sending || (!body.trim() && !attachment)"
                                class="rounded-full p-2 bg-blue-900 text-white hover:bg-blue-800 disabled:opacity-40 transition-colors"
                                title="Send"
                            >
                                <PaperAirplaneIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </EmployeeLayout>
</template>
