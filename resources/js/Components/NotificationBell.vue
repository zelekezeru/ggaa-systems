<script setup>
import { BellIcon, CheckIcon } from '@heroicons/vue/24/outline';
import { BellAlertIcon } from '@heroicons/vue/24/solid';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page  = usePage();
const count = computed(() => page.props.unread_notifications_count ?? 0);

const open          = ref(false);
const loading       = ref(false);
const notifications = ref([]);
const panel         = ref(null);
const modalOpen     = ref(false);
const selectedNotification = ref(null);

// ── Fetch on open ─────────────────────────────────────────────────────────────
const fetchNotifications = async () => {
    loading.value = true;
    try {
        const res = await axios.get(route('notifications.index'));
        notifications.value = res.data;
    } finally {
        loading.value = false;
    }
};

const toggle = () => {
    open.value = !open.value;
    if (open.value) fetchNotifications();
};

// ── Mark all read ─────────────────────────────────────────────────────────────
const markAllRead = async () => {
    await axios.post(route('notifications.read-all'), {}, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
    });
    notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date().toISOString() }));
    // Reload to update the shared badge count
    window.location.reload();
};

// ── Mark single read ──────────────────────────────────────────────────────────
const markRead = async (notification) => {
    if (!notification.read_at) {
        await axios.post(route('notifications.read', notification.id), {}, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
        });
        notification.read_at = new Date().toISOString();
    }
    // Navigate if URL is in data
    if (notification.data?.url) {
        window.location.href = notification.data.url;
    }
};

// ── Open message modal ────────────────────────────────────────────────────────
const openMessageModal = (notification) => {
    selectedNotification.value = notification;
    modalOpen.value = true;
    markRead(notification); // Mark as read when opening
};

// ── Click-outside to close ────────────────────────────────────────────────────
const onClickOutside = (e) => {
    if (panel.value && !panel.value.contains(e.target)) {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside));

// ── Notification icon helper ──────────────────────────────────────────────────
const notifIcon = (type) => {
    const map = {
        VatDeadlineReminder: '🗓️',
        NewMessage:          '💬',
        TaskAssigned:        '📋',
        PaymentDue:          '💳',
    };
    return map[type] ?? '🔔';
};
</script>

<template>
    <div class="relative" ref="panel">
        <!-- Bell button -->
        <button
            @click="toggle"
            class="relative rounded-full p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
            aria-label="Notifications"
        >
            <BellAlertIcon v-if="count > 0" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
            <BellIcon v-else class="h-6 w-6" />

            <!-- Badge -->
            <span
                v-if="count > 0"
                class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold leading-none ring-2 ring-white dark:ring-gray-900"
            >
                {{ count > 99 ? '99+' : count }}
            </span>
        </button>

        <!-- Dropdown panel -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1"
        >
            <div
                v-if="open"
                class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-black/10 dark:ring-white/10 overflow-hidden z-50 origin-top-right"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Notifications</h3>
                    <button
                        v-if="notifications.some(n => !n.read_at)"
                        @click="markAllRead"
                        class="flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 font-medium transition-colors"
                    >
                        <CheckIcon class="h-3.5 w-3.5" />
                        Mark all read
                    </button>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent" />
                </div>

                <!-- Empty state -->
                <div v-else-if="notifications.length === 0" class="py-10 text-center">
                    <BellIcon class="mx-auto h-8 w-8 text-gray-300 dark:text-gray-600 mb-2" />
                    <p class="text-sm text-gray-400 dark:text-gray-500">You're all caught up!</p>
                </div>

                <!-- Notification list -->
                <ul v-else class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                    <li
                        v-for="n in notifications"
                        :key="n.id"
                        @click="n.type === 'NewMessage' ? openMessageModal(n) : markRead(n)"
                        class="flex items-start gap-3 px-4 py-3 cursor-pointer transition-colors"
                        :class="n.read_at
                            ? 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                            : 'bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30'"
                    >
                        <!-- Icon -->
                        <span class="text-xl flex-shrink-0 mt-0.5">{{ notifIcon(n.type) }}</span>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 dark:text-white font-medium leading-snug">
                                {{ n.data?.message ?? n.data?.subject ?? n.type }}
                            </p>
                            <p v-if="n.data?.client_name" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                                {{ n.data.client_name }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ n.created_at }}</p>
                        </div>

                        <!-- Unread dot -->
                        <span v-if="!n.read_at" class="flex-shrink-0 mt-1.5 w-2 h-2 rounded-full bg-blue-500" />
                    </li>
                </ul>
            </div>
        </Transition>
    </div>

    <!-- Message Modal -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="modalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="w-full max-w-md mx-4 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Message</h3>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="px-6 py-4">
                    <div v-if="selectedNotification" class="space-y-4">
                        <!-- Message Details -->
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xl">{{ notifIcon(selectedNotification.type) }}</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedNotification.data?.sender_name || 'System' }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ selectedNotification.created_at }}</span>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ selectedNotification.data?.message || selectedNotification.data?.subject }}</p>
                            <div v-if="selectedNotification.data?.details" class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                {{ selectedNotification.data.details }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <button @click="modalOpen = false" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
