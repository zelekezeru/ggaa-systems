<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userPermissions = computed(() => user.value?.permissions ?? []);
const canViewBilling = computed(() => userPermissions.value.includes('view finance billing'));
const canManageInvoices = computed(() => userPermissions.value.includes('manage service invoices'));
const canViewLedgerProgress = computed(() => userPermissions.value.includes('view ledger progress'));
const canViewProjects = computed(() => userPermissions.value.includes('view team projects'));
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900 flex flex-col transition-colors duration-300">

        <!-- Sticky top header -->
        <header class="bg-white dark:bg-slate-800 shadow-sm border-b border-gray-200 dark:border-slate-700 sticky top-0 z-50 transition-colors duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">

                <!-- Left: brand + nav -->
                <div class="flex items-center gap-8">
                    <div class="flex items-center gap-2.5">
                        <ApplicationLogo class="h-8 w-8 fill-current text-blue-900 dark:text-blue-400" />
                        <div>
                            <div class="text-sm font-bold text-blue-900 dark:text-white leading-none">GGAA Systems</div>
                            <div class="text-xs text-gray-400 dark:text-slate-500 leading-none mt-0.5">{{ $t('finance_portal') }}</div>
                        </div>
                    </div>

                    <nav class="hidden md:flex items-center space-x-1">
                        <Link
                            v-if="canViewBilling"
                            :href="route('finance.billing')"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="$page.url.startsWith('/finance/billing') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-white'"
                        >
                            {{ $t('billing') || 'Billing' }}
                        </Link>
                        <Link
                            v-if="canManageInvoices"
                            :href="route('finance.invoices.index')"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="$page.url.startsWith('/finance/invoices') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-white'"
                        >
                            {{ $t('invoices') || 'Invoices' }}
                        </Link>
                        <Link
                            v-if="canViewLedgerProgress"
                            :href="route('finance.ledger-progress')"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="$page.url.startsWith('/finance/ledger-progress') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-white'"
                        >
                            {{ $t('ledger_progress') || 'Ledger Progress' }}
                        </Link>
                        <Link
                            v-if="canViewProjects"
                            :href="route('team-projects.index')"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="$page.url.startsWith('/team-projects') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-white'"
                        >
                            {{ $t('team_projects') || 'Team Projects' }}
                        </Link>
                    </nav>
                </div>

                <!-- Right: flash + role badge + user + sign out -->
                <div class="flex items-center gap-4">
                    <span
                        v-if="$page.props.flash?.success"
                        class="hidden sm:inline text-xs text-green-600 dark:text-green-400 font-medium bg-green-50 dark:bg-green-900/20 px-3 py-1 rounded-full border border-green-100 dark:border-green-900/30"
                    >
                        {{ $page.props.flash.success }}
                    </span>

                    <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-green-50 dark:bg-green-900/20 px-3 py-1 text-xs font-semibold text-green-700 dark:text-green-400 border border-green-100 dark:border-green-900/30">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                        {{ $t('finance_admin') }}
                    </span>

                    <div class="text-sm font-medium text-gray-700 dark:text-slate-300">{{ user?.name }}</div>

                    <!-- Notification Bell -->
                    <NotificationBell />

                    <!-- Theme Toggle -->
                    <ThemeToggle />

                    <!-- Language Switch Component -->
                    <LanguageSwitcher />

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="text-xs text-gray-400 dark:text-slate-500 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                    >
                        {{ $t('logout') }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1 overflow-x-auto p-4 sm:p-6 lg:p-8 bg-gray-50 dark:bg-slate-900 transition-colors duration-300">
            <slot />
        </main>
    </div>
</template>
