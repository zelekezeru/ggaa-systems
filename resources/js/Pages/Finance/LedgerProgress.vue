<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useRoleLayout } from '@/Composables/useRoleLayout';
import { ChartBarIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { currentLayout } = useRoleLayout();
const { t } = useI18n();

const props = defineProps({
    clients:         Array,
    ethiopianMonths: Array,
    currentEthYear:  Number,
});

const search = ref('');
const selectedYear = ref(props.currentEthYear);
const yearOptions = computed(() => [props.currentEthYear - 2, props.currentEthYear - 1, props.currentEthYear, props.currentEthYear + 1]);

const filtered = computed(() => {
    const q = search.value.toLowerCase();
    if (!q) return props.clients;
    return props.clients.filter(c =>
        c.company_name.toLowerCase().includes(q) ||
        (c.tin_number && c.tin_number.toLowerCase().includes(q))
    );
});

function entryFor(client, month) {
    return client.entries?.[selectedYear.value + '_' + month] ?? null;
}

function statusBg(entry) {
    if (!entry) return 'bg-slate-100 dark:bg-slate-700 text-slate-400';
    if (entry.status === 'verified')  return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    if (entry.status === 'submitted') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
}

function statusGlyph(entry) {
    if (!entry) return '—';
    return { verified: '✓', submitted: '↑', draft: '✎' }[entry.status] ?? '·';
}

// Stats
const stats = computed(() => {
    let verified = 0, submitted = 0, draft = 0, missing = 0;
    for (const c of props.clients) {
        for (const m of props.ethiopianMonths) {
            const e = entryFor(c, m);
            if (!e) missing++;
            else if (e.status === 'verified')  verified++;
            else if (e.status === 'submitted') submitted++;
            else draft++;
        }
    }
    return { verified, submitted, draft, missing };
});
</script>

<template>
    <Head :title="t('ledger_progress') || 'Ledger Progress'" />
    <component :is="currentLayout">
        <div class="max-w-[1600px] mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-5">
                <div class="p-2.5 rounded-xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                    <ChartBarIcon class="h-6 w-6" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('ledger_progress') || 'Ledger Progress' }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('ledger_progress_desc') || 'Read-only view of all clients\' monthly ledger status — for invoicing context' }}</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 uppercase">{{ t('verified') || 'Verified' }}</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.verified }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 uppercase">{{ t('submitted') || 'Submitted' }}</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ stats.submitted }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 uppercase">{{ t('draft') || 'Draft' }}</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.draft }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 uppercase">{{ t('missing') || 'Missing' }}</p>
                    <p class="text-2xl font-bold text-slate-400 mt-1">{{ stats.missing }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-3 mb-4">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="search" type="text" :placeholder="t('search_clients') || 'Search clients...'"
                        class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none" />
                </div>
                <select v-model="selectedYear"
                    class="px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option v-for="y in yearOptions" :key="y" :value="y">ETH {{ y }}</option>
                </select>
            </div>

            <!-- Matrix -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                                <th class="text-left px-4 py-3 text-xs font-semibold uppercase sticky left-0 bg-slate-50 dark:bg-slate-700/50 min-w-[200px]">{{ t('client') || 'Client' }}</th>
                                <th v-for="m in ethiopianMonths" :key="m" class="text-center px-2 py-3 text-xs font-semibold uppercase min-w-[60px]">
                                    {{ m.slice(0, 3) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="c in filtered" :key="c.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-3 sticky left-0 bg-white dark:bg-slate-800">
                                    <div class="font-medium text-slate-900 dark:text-white truncate max-w-[180px]">{{ c.company_name }}</div>
                                    <div class="text-xs text-slate-400">{{ c.tin_number }}</div>
                                </td>
                                <td v-for="m in ethiopianMonths" :key="m" class="px-2 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-md text-xs font-bold"
                                        :class="statusBg(entryFor(c, m))"
                                        :title="t(entryFor(c, m)?.status || 'no_entry')">
                                        {{ statusGlyph(entryFor(c, m)) }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="filtered.length === 0">
                                <td :colspan="ethiopianMonths.length + 1" class="px-4 py-12 text-center text-slate-400">{{ t('no_clients_found') || 'No clients found.' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </component>
</template>
