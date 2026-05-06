<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import ClientLayout from '@/Layouts/ClientLayout.vue';
import FinanceLayout from '@/Layouts/FinanceLayout.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { CheckIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    notifications: { type: Object, required: true },
    unreadCount:   { type: Number, default: 0 },
});

const page = usePage();
const roles = computed(() => page.props.auth?.roles || []);

// Pick the right layout based on the user's role.
const layout = computed(() => {
    if (roles.value.includes('Super Admin') || roles.value.includes('Branch Manager')) return AdminLayout;
    if (roles.value.includes('Finance Admin')) return FinanceLayout;
    if (roles.value.includes('Employee')) return EmployeeLayout;
    if (roles.value.includes('Client')) return ClientLayout;
    return AuthenticatedLayout;
});

function markRead(id) {
    router.post(route('notifications.read', id), {}, { preserveScroll: true });
}
function markAllRead() {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
}
function dismiss(id) {
    router.delete(route('notifications.destroy', id), { preserveScroll: true });
}
function follow(n) {
    if (!n.read_at) markRead(n.id);
    if (n.url) router.visit(n.url);
}
function fmt(iso) {
    return new Date(iso).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Head title="Notifications" />
    <component :is="layout">
        <div class="p-4 sm:p-6 lg:p-8 max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <PageHeader title="Notifications" :description="`${unreadCount} unread`" />
                <button
                    v-if="unreadCount > 0"
                    @click="markAllRead"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-sm font-semibold ring-1 ring-blue-200 dark:ring-blue-800/40"
                >
                    <CheckIcon class="h-4 w-4" />
                    Mark all read
                </button>
            </div>

            <div v-if="notifications.data.length === 0" class="rounded-2xl bg-white dark:bg-slate-800 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm p-12 text-center">
                <div class="text-5xl mb-3">📭</div>
                <p class="text-slate-500 dark:text-slate-400">Your inbox is empty.</p>
            </div>

            <ul v-else class="rounded-2xl bg-white dark:bg-slate-800 ring-1 ring-slate-100 dark:ring-slate-700 shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-700">
                <li
                    v-for="n in notifications.data"
                    :key="n.id"
                    class="group flex items-start gap-4 p-4 transition cursor-pointer"
                    :class="!n.read_at ? 'bg-blue-50/40 dark:bg-blue-900/10 hover:bg-blue-50 dark:hover:bg-blue-900/20' : 'hover:bg-slate-50 dark:hover:bg-slate-700/30'"
                    @click="follow(n)"
                >
                    <div class="text-3xl flex-shrink-0 leading-none">{{ n.icon }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <p class="font-semibold text-slate-900 dark:text-white" :class="!n.read_at ? '' : 'text-slate-600 dark:text-slate-300'">
                                {{ n.title }}
                            </p>
                            <span v-if="!n.read_at" class="inline-block w-2 h-2 rounded-full bg-blue-500" />
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ n.body }}</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2">{{ fmt(n.created_at) }}</p>
                    </div>
                    <button
                        @click.stop="dismiss(n.id)"
                        class="opacity-0 group-hover:opacity-100 p-2 text-slate-400 hover:text-red-500 transition"
                        title="Dismiss"
                    >
                        <TrashIcon class="h-4 w-4" />
                    </button>
                </li>
            </ul>

            <!-- Pagination -->
            <div v-if="notifications.last_page > 1" class="flex justify-center gap-2 pt-2">
                <Link
                    v-for="link in notifications.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    class="px-3 py-1.5 text-sm rounded-lg ring-1 transition"
                    :class="link.active
                        ? 'bg-blue-600 text-white ring-blue-600'
                        : link.url
                            ? 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 ring-slate-200 dark:ring-slate-700 hover:ring-blue-400'
                            : 'text-slate-300 ring-transparent cursor-not-allowed'"
                />
            </div>
        </div>
    </component>
</template>
