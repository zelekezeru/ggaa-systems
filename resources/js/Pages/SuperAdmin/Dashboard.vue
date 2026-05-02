<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
    ChartBarIcon, 
    UsersIcon, 
    BuildingOfficeIcon, 
    ArrowTrendingUpIcon,
    CheckBadgeIcon,
    ExclamationTriangleIcon,
    BriefcaseIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    branches: Array, // Array of branch objects with compliance stats
    employees: Array, // Array of employee objects with weighted capacity scores
});

// Aggregate stats for the header
const globalStats = computed(() => {
    const totalBranches = props.branches.length;
    const avgCompliance = totalBranches > 0 
        ? Math.round(props.branches.reduce((acc, b) => acc + (b.compliance_rate || 0), 0) / totalBranches) 
        : 0;
    const totalStaff = props.employees.length;
    const totalClients = props.employees.reduce((acc, e) => acc + (e.clients_count || 0), 0);

    return [
        { name: 'Total Branches', value: totalBranches, icon: BuildingOfficeIcon, color: 'text-blue-600', bg: 'bg-blue-100 dark:bg-blue-900/30' },
        { name: 'Global Compliance', value: `${avgCompliance}%`, icon: CheckBadgeIcon, color: 'text-emerald-600', bg: 'bg-emerald-100 dark:bg-emerald-900/30' },
        { name: 'Total Workforce', value: totalStaff, icon: UserGroupIcon, color: 'text-indigo-600', bg: 'bg-indigo-100 dark:bg-indigo-900/30' },
        { name: 'Active Clients', value: totalClients, icon: BriefcaseIcon, color: 'text-orange-600', bg: 'bg-orange-100 dark:bg-orange-900/30' },
    ];
});

const getCapacityColor = (percentage) => {
    if (percentage >= 90) return 'bg-rose-500 shadow-rose-500/50';
    if (percentage >= 75) return 'bg-amber-500 shadow-amber-500/50';
    return 'bg-indigo-500 shadow-indigo-500/50';
};

const getComplianceStatus = (rate) => {
    if (rate >= 90) return { label: 'Optimal', color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20' };
    if (rate >= 70) return { label: 'Stable', color: 'text-blue-600 bg-blue-50 dark:bg-blue-900/20' };
    return { label: 'Attention', color: 'text-rose-600 bg-rose-50 dark:bg-rose-900/20' };
};
</script>

<template>
    <Head title="Command Center" />

    <AdminLayout>
        <div class="p-8 max-w-[1600px] mx-auto space-y-10">
            <!-- Header section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $t('firm_overview') }}
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium mt-1">
                        Real-time operational health and workforce distribution.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex -space-x-3 overflow-hidden">
                        <div v-for="i in 5" :key="i" class="inline-block h-10 w-10 rounded-full ring-4 ring-white dark:ring-slate-900 bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500">
                            {{ String.fromCharCode(64 + i) }}
                        </div>
                    </div>
                    <div class="h-10 w-[1px] bg-slate-200 dark:bg-slate-800 mx-2"></div>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-2xl font-bold text-sm transition-all active:scale-95 shadow-lg shadow-blue-600/20">
                        Export Report
                    </button>
                </div>
            </div>

            <!-- Global Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="stat in globalStats" :key="stat.name" class="relative group bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden transition-all hover:shadow-xl hover:-translate-y-1">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-slate-50 dark:bg-slate-700/30 rounded-full transition-transform group-hover:scale-150"></div>
                    <div class="relative flex items-center gap-4">
                        <div :class="[stat.bg, stat.color, 'p-3 rounded-2xl']">
                            <component :is="stat.icon" class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ stat.name }}</p>
                            <p class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ stat.value }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                <!-- Branch Performance Section -->
                <div class="xl:col-span-7 space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <BuildingOfficeIcon class="h-6 w-6 text-blue-600" />
                            Branch Performance
                        </h2>
                        <Link :href="route('super-admin.branches')" class="text-sm font-bold text-blue-600 hover:underline">View All</Link>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div v-for="branch in branches" :key="branch.id" class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700/50 shadow-sm transition-all hover:ring-2 hover:ring-blue-500/20">
                            <div class="flex items-start justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-2xl bg-slate-50 dark:bg-slate-900/50 flex items-center justify-center border border-slate-100 dark:border-slate-700">
                                        <BuildingOfficeIcon class="h-6 w-6 text-slate-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white">{{ branch.name }}</h3>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Operational Node</span>
                                    </div>
                                </div>
                                <span :class="[getComplianceStatus(branch.compliance_rate).color, 'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest']">
                                    {{ getComplianceStatus(branch.compliance_rate).label }}
                                </span>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-end justify-between">
                                    <div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Compliance Rate</div>
                                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ branch.compliance_rate }}%</div>
                                    </div>
                                    <div class="h-8 w-24">
                                        <!-- Mock sparkline -->
                                        <div class="flex items-end gap-1 h-full">
                                            <div v-for="i in 8" :key="i" class="flex-1 bg-blue-100 dark:bg-blue-900/30 rounded-t-sm" :style="{ height: (20 + Math.random() * 80) + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                    <div class="bg-blue-600 h-full rounded-full transition-all duration-1000 shadow-[0_0_8px_rgba(37,99,235,0.4)]" :style="{ width: branch.compliance_rate + '%' }"></div>
                                </div>
                            </div>

                            <div class="mt-6 pt-6 border-t border-slate-50 dark:border-slate-700/50 flex items-center justify-between">
                                <div class="flex -space-x-2">
                                    <div v-for="i in 3" :key="i" class="h-7 w-7 rounded-full bg-slate-200 dark:bg-slate-700 border-2 border-white dark:border-slate-800"></div>
                                </div>
                                <Link :href="route('super-admin.branches.show', branch.id)" class="text-xs font-bold text-slate-400 hover:text-blue-600 transition-colors">Details &rarr;</Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Workforce Allocation ranking -->
                <div class="xl:col-span-5 space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <UsersIcon class="h-6 w-6 text-indigo-600" />
                            Workforce Ranking
                        </h2>
                        <div class="flex items-center gap-1.5 px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
                            <ArrowTrendingUpIcon class="h-4 w-4 text-emerald-500" />
                            <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Live Load</span>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-slate-50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/20">
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Professional</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Clients</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Capacity Load</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                                    <tr v-for="employee in employees" :key="employee.id" class="group hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-600 flex items-center justify-center font-bold text-slate-500 dark:text-slate-400 group-hover:scale-110 transition-transform">
                                                    {{ employee.name.charAt(0) }}
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-900 dark:text-white">{{ employee.name }}</div>
                                                    <div class="text-[10px] text-slate-400 font-medium">{{ employee.branch.name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center font-black text-slate-900 dark:text-white">
                                            {{ employee.clients_count }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex-1 h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                                    <div 
                                                        class="h-full rounded-full transition-all duration-700 shadow-sm" 
                                                        :class="getCapacityColor((employee.capacity_points / 30) * 100)"
                                                        :style="{ width: Math.min((employee.capacity_points / 30) * 100, 100) + '%' }">
                                                    </div>
                                                </div>
                                                <span class="text-xs font-black text-slate-700 dark:text-slate-200 w-8">{{ Math.round((employee.capacity_points / 30) * 100) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 bg-slate-50/50 dark:bg-slate-900/20 text-center">
                            <Link :href="route('super-admin.staff')" class="text-xs font-bold text-slate-400 hover:text-blue-600 transition-colors">Manage Full Workforce List</Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.glass {
    @apply backdrop-blur-md bg-white/70 dark:bg-slate-800/70 border border-white/20 dark:border-slate-700/20;
}
</style>