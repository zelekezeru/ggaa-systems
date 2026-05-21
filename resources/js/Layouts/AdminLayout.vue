<script setup>
import { computed, watch, ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import { 
    Squares2X2Icon, 
    BuildingOfficeIcon, 
    UserGroupIcon, 
    UsersIcon, 
    ChartBarIcon, 
    ArrowLeftOnRectangleIcon, 
    Bars3Icon, 
    XMarkIcon,
    WrenchScrewdriverIcon,
    ShieldCheckIcon,
    ClipboardDocumentListIcon,
    DocumentDuplicateIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    RectangleGroupIcon,
    BookOpenIcon,
    BanknotesIcon
} from '@heroicons/vue/24/outline';

import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const toast = useToast();
const { locale, t } = useI18n({ useScope: 'global' });

const isSidebarOpen = ref(false);

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
    if (flash?.warning) toast.warning(flash.warning);
}, { deep: true });

// Direction & language are managed centrally in app.js (sets html[dir] + html[lang]
// based on locale, with RTL support for Arabic).

const openDropdowns = ref({
    task_management: page.url.startsWith('/super-admin/tasks') || page.url.startsWith('/super-admin/task-types'),
    finance_management: page.url.startsWith('/ledger') || page.url.startsWith('/finance')
});

const toggleDropdown = (key) => {
    openDropdowns.value[key] = !openDropdowns.value[key];
};

const navigation = computed(() => [
    { name: t('dashboard'), href: route('super-admin.dashboard'), icon: Squares2X2Icon, current: page.url === '/super-admin/dashboard' },
    { name: t('branches'), href: route('super-admin.branches'), icon: BuildingOfficeIcon, current: page.url.startsWith('/super-admin/branches') },
    { 
        name: t('task_management') || 'Task Management', 
        key: 'task_management',
        icon: ClipboardDocumentListIcon, 
        current: page.url.startsWith('/super-admin/tasks') || page.url.startsWith('/super-admin/task-types'),
        children: [
            { name: t('tasks') || 'Tasks', href: route('super-admin.tasks.index'), current: page.url.startsWith('/super-admin/tasks') && !page.url.startsWith('/super-admin/task-types') },
            { name: t('task_types') || 'Task Types', href: route('super-admin.task-types.index'), current: page.url.startsWith('/super-admin/task-types') },
            { name: t('daily_tasks') || 'Daily Tasks', href: route('admin.daily-tasks.index'), current: page.url.startsWith('/super-admin/daily-tasks') },
        ]
    },
    { name: t('team_projects') || 'Team Projects', href: route('team-projects.index'), icon: RectangleGroupIcon, current: page.url.startsWith('/team-projects') },
    { 
        name: t('finance') || 'Finance', 
        key: 'finance_management',
        icon: BanknotesIcon,
        current: page.url.startsWith('/ledger') || page.url.startsWith('/finance'),
        children: [
            { name: t('financial_ledger') || "Financial Ledger", href: route('ledger.index'), current: page.url.startsWith('/ledger') },
            { name: t('billing') || 'Billing & Revenue', href: route('finance.billing'), current: page.url.startsWith('/finance/billing') },
            { name: t('invoices') || 'Invoices', href: route('finance.invoices.index'), current: page.url.startsWith('/finance/invoices') },
            { name: t('ledger_progress') || 'Ledger Progress', href: route('finance.ledger-progress'), current: page.url.startsWith('/finance/ledger-progress') },
        ]
    },
    { name: t('customers') || 'Customers', href: route('super-admin.clients'), icon: UsersIcon, current: page.url.startsWith('/super-admin/clients') },
    { name: t('documents') || 'Documents', href: route('super-admin.documents'), icon: DocumentDuplicateIcon, current: page.url.startsWith('/super-admin/documents') },
    { name: t('staff'), href: route('super-admin.staff'), icon: UserGroupIcon, current: page.url.startsWith('/super-admin/staff') },
    { name: t('reports'), href: route('super-admin.reports'), icon: ChartBarIcon, current: page.url.startsWith('/super-admin/reports') },
    { name: t('service_types') || 'Service configs', href: route('super-admin.service-types'), icon: WrenchScrewdriverIcon, current: page.url.startsWith('/super-admin/service-types') },
    { name: t('role_management'), href: route('super-admin.roles.index'), icon: ShieldCheckIcon, current: page.url.startsWith('/super-admin/roles') },
]);
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
        
        <!-- Mobile Sidebar Overlay -->
        <div v-if="isSidebarOpen" class="fixed inset-0 z-50 lg:hidden ring-0 bg-slate-900/50 backdrop-blur-sm" @click="isSidebarOpen = false"></div>

        <!-- Sidebar Display -->
        <aside 
            class="fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-slate-800 border-r border-gray-200 dark:border-slate-700 transition-transform duration-300 lg:translate-x-0 overflow-y-auto"
            :class="[isSidebarOpen ? 'translate-x-0' : '-translate-x-full']"
        >
            <div class="flex flex-col h-full">
                <!-- Sidebar Header -->
                <div class="flex items-center justify-between h-20 px-6 border-b border-gray-100 dark:border-slate-700">
                    <Link :href="route('super-admin.dashboard')" class="flex items-center gap-3">
                        <ApplicationLogo class="h-9 w-9 text-blue-600 dark:text-blue-400" />
                        <div>
                            <div class="text-base font-bold text-slate-900 dark:text-white leading-tight">GGAA Systems</div>
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold leading-tight">Premium ERP</div>
                        </div>
                    </Link>
                    <button class="lg:hidden text-slate-500 hover:text-slate-900 dark:hover:text-white" @click="isSidebarOpen = false">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>

                <!-- Navigation Sidebar Links -->
                <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar">
                    <template v-for="item in navigation" :key="item.name">
                        <!-- Dropdown Item -->
                        <div v-if="item.children" class="flex flex-col space-y-1">
                            <button
                                @click="toggleDropdown(item.key)"
                                class="w-full group flex items-center justify-between px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200"
                                :class="[
                                    item.current 
                                        ? 'bg-blue-50 dark:bg-blue-600/10 text-blue-600 dark:text-blue-400 shadow-sm' 
                                        : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700/50 hover:text-slate-900 dark:hover:text-white'
                                ]"
                            >
                                <div class="flex items-center gap-3.5">
                                    <component 
                                        :is="item.icon" 
                                        class="h-5 w-5 flex-shrink-0 transition-colors" 
                                        :class="[item.current ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300']"
                                    />
                                    {{ item.name }}
                                </div>
                                <ChevronUpIcon v-if="openDropdowns[item.key]" class="h-4 w-4" />
                                <ChevronDownIcon v-else class="h-4 w-4" />
                            </button>
                            
                            <!-- Dropdown Children -->
                            <div v-show="openDropdowns[item.key]" class="pl-11 pr-2 space-y-1">
                                <Link
                                    v-for="child in item.children"
                                    :key="child.name"
                                    :href="child.href"
                                    class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                                    :class="[
                                        child.current
                                            ? 'text-blue-600 dark:text-blue-400 bg-blue-50/50 dark:bg-blue-600/10 font-semibold'
                                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50'
                                    ]"
                                >
                                    {{ child.name }}
                                </Link>
                            </div>
                        </div>

                        <!-- Regular Item -->
                        <Link
                            v-else
                            :href="item.href"
                            class="group flex items-center gap-3.5 px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200"
                            :class="[
                                item.current 
                                    ? 'bg-blue-50 dark:bg-blue-600/10 text-blue-600 dark:text-blue-400 shadow-sm' 
                                    : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700/50 hover:text-slate-900 dark:hover:text-white'
                            ]"
                        >
                            <component 
                                :is="item.icon" 
                                class="h-5 w-5 flex-shrink-0 transition-colors" 
                                :class="[item.current ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300']"
                            />
                            {{ item.name }}
                        </Link>
                    </template>
                </nav>

                <!-- Sidebar Footer -->
                <div class="p-4 border-t border-gray-100 dark:border-slate-700 space-y-2">
                    <!-- User Info Mini Card -->
                    <div class="flex items-center gap-3.5 px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-700/30 ring-1 ring-inset ring-gray-100 dark:ring-slate-700">
                        <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-700 dark:text-blue-400 font-bold border border-blue-200 dark:border-blue-800">
                            {{ user?.name?.charAt(0) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ user?.name }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate uppercase tracking-tighter">Super Admin</p>
                        </div>
                    </div>

                    <!-- Config Quick Actions -->
                    <div class="flex items-center justify-between px-2 pt-2 gap-4">
                        <ThemeToggle />
                        <LanguageSwitcher variant="sidebar" class="flex-1" />
                    </div>

                    <!-- Logout Button -->
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="w-full group flex items-center gap-3 px-4 py-3 text-sm font-semibold text-slate-500 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 rounded-xl transition-all duration-200"
                    >
                        <ArrowLeftOnRectangleIcon class="h-5 w-5 text-slate-400 group-hover:text-red-500 transition-colors" />
                        {{ $t('logout') }}
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Sidebar Main Content Area -->
        <div class="lg:pl-72 flex flex-col min-h-screen">
            <!-- Header for Mobile Devices -->
            <header class="lg:hidden sticky top-0 z-40 bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border-b border-gray-200 dark:border-slate-700 h-16 flex items-center justify-between px-4 sm:px-6 shadow-sm transition-colors duration-200">
                <div class="flex items-center gap-3">
                    <ApplicationLogo class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                    <span class="text-sm font-bold text-slate-900 dark:text-white">GGAA Systems</span>
                </div>
                <div class="flex items-center gap-2">
                    <NotificationBell />
                    <button class="p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors" @click="isSidebarOpen = true">
                        <Bars3Icon class="h-6 w-6" />
                    </button>
                </div>
            </header>

            <!-- Main Page Content -->
            <main class="flex-1 p-0 transition-opacity duration-300" :class="[isSidebarOpen ? 'opacity-40 pointer-events-none' : 'opacity-100']">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.05);
}
</style>
