<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import FinanceLayout from '@/Layouts/FinanceLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { BookOpenIcon, MagnifyingGlassIcon, CheckCircleIcon, ClockIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    clients: Array,
    ethiopianMonths: Array,
    currentEthYear: Number,
    canVerify: Boolean,
});
 
const page = usePage();
const currentLayout = computed(() => {
    const roles = page.props.auth.user.roles || [];
    if (roles.includes('Employee')) return EmployeeLayout;
    if (roles.includes('Finance Admin')) return FinanceLayout;
    return AdminLayout;
});

const search = ref('');
const selectedYear = ref(props.currentEthYear);

const yearOptions = computed(() => {
    const base = props.currentEthYear;
    return [base - 2, base - 1, base, base + 1];
});

const filteredClients = computed(() => {
    const q = search.value.toLowerCase();
    if (!q) return props.clients;
    return props.clients.filter(c =>
        c.company_name.toLowerCase().includes(q) ||
        (c.tin_number && c.tin_number.toLowerCase().includes(q))
    );
});

function ledgerKey(month) {
    return selectedYear.value + '_' + month;
}

function getEntry(client, month) {
    return client.ledger_summary?.[ledgerKey(month)] ?? null;
}

function statusColor(entry) {
    if (!entry) return 'bg-gray-100 text-gray-400 dark:bg-slate-700 dark:text-slate-500';
    if (entry.status === 'verified') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    if (entry.status === 'submitted') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
}

function statusLabel(entry) {
    if (!entry) return '—';
    if (entry.status === 'verified') return '✓';
    if (entry.status === 'submitted') return '↑';
    return '✎';
}

function openEntry(client) {
    router.get(route('ledger.show', client.id));
}

const totalClients   = computed(() => props.clients.length);
const completedCount = computed(() => {
    return props.clients.reduce((sum, c) => {
        const done = props.ethiopianMonths.filter(m => {
            const e = getEntry(c, m);
            return e && (e.status === 'submitted' || e.status === 'verified');
        }).length;
        return sum + (done === props.ethiopianMonths.length ? 1 : 0);
    }, 0);
});
</script>

<template>
    <Head :title="t('financial_ledger')" />
    <component :is="currentLayout">
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto">
            <PageHeader :title="t('financial_ledger')" :description="t('monthly_pnl_tracking')">
                <template #icon><BookOpenIcon class="h-6 w-6" /></template>
            </PageHeader>

            <!-- Stats Bar -->
            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700 shadow-sm">
                    <p class="text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wide">{{ t('total_clients') }}</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ totalClients }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700 shadow-sm">
                    <p class="text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wide">{{ t('fully_filed') }} ({{ selectedYear }})</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ completedCount }}</p>
                </div>
                <div class="col-span-2 bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700 shadow-sm flex items-center gap-3">
                    <span class="inline-block w-3 h-3 rounded-full bg-emerald-400"></span><span class="text-xs dark:text-slate-300">{{ t('verified') }}</span>
                    <span class="inline-block w-3 h-3 rounded-full bg-blue-400"></span><span class="text-xs dark:text-slate-300">{{ t('submitted') }}</span>
                    <span class="inline-block w-3 h-3 rounded-full bg-amber-400"></span><span class="text-xs dark:text-slate-300">{{ t('draft') }}</span>
                    <span class="inline-block w-3 h-3 rounded-full bg-gray-300"></span><span class="text-xs dark:text-slate-300">{{ t('missing') }}</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-3 mb-4">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="t('search_clients')"
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                    />
                </div>
                <select
                    v-model="selectedYear"
                    class="px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                >
                    <option v-for="y in yearOptions" :key="y" :value="y">{{ t('year') }} {{ y }}</option>
                </select>
            </div>

            <!-- Matrix Table -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-200 dark:border-slate-700">
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide sticky left-0 bg-gray-50 dark:bg-slate-700/50 min-w-[200px]">
                                    {{ t('client') }}
                                </th>
                                <th
                                    v-for="month in ethiopianMonths"
                                    :key="month"
                                    class="text-center px-2 py-3 text-xs font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide min-w-[72px]"
                                >
                                    {{ t(month.toLowerCase()) }}
                                </th>
                                <th class="text-center px-3 py-3 text-xs font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wide">
                                    {{ t('action') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700/50">
                            <tr
                                v-for="client in filteredClients"
                                :key="client.id"
                                class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors"
                            >
                                <!-- Client Name -->
                                <td class="px-4 py-3 sticky left-0 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                    <div class="font-medium text-gray-900 dark:text-white text-sm truncate max-w-[180px]">
                                        {{ client.company_name }}
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-slate-500">{{ client.tin_number }}</div>
                                </td>

                                <!-- Month Cells -->
                                <td
                                    v-for="month in ethiopianMonths"
                                    :key="month"
                                    class="px-2 py-3 text-center"
                                >
                                    <span
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-bold cursor-pointer transition-all hover:scale-110"
                                        :class="statusColor(getEntry(client, month))"
                                        :title="getEntry(client, month) ? getEntry(client, month).status : 'No entry'"
                                        @click="openEntry(client)"
                                    >
                                        {{ statusLabel(getEntry(client, month)) }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="px-3 py-3 text-center">
                                    <Link
                                        :href="route('ledger.show', client.id)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors"
                                    >
                                        <PencilSquareIcon class="h-3.5 w-3.5" />
                                        {{ t('enter') }}
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="filteredClients.length === 0">
                                <td :colspan="ethiopianMonths.length + 2" class="px-4 py-12 text-center text-gray-400 dark:text-slate-500">
                                    {{ t('no_clients_found') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </component>
</template>
