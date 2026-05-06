<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import NotificationBell from '@/Components/NotificationBell.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userPermissions = computed(() => user.value?.permissions ?? []);
const canViewBilling = computed(() => userPermissions.value.includes('view finance billing'));
const canManageInvoices = computed(() => userPermissions.value.includes('manage service invoices'));
const canViewLedgerProgress = computed(() => userPermissions.value.includes('view ledger progress'));
const canViewProjects = computed(() => userPermissions.value.includes('view team projects'));
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col">

        <!-- Sticky top header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">

                <!-- Left: brand + nav -->
                <div class="flex items-center gap-8">
                    <div class="flex items-center gap-2.5">
                        <ApplicationLogo class="h-8 w-8 fill-current text-blue-900" />
                        <div>
                            <div class="text-sm font-bold text-blue-900 leading-none">GGAA Systems</div>
                            <div class="text-xs text-gray-400 leading-none mt-0.5">{{ $t('finance_portal') }}</div>
                        </div>
                    </div>

                    <nav class="hidden md:flex items-center space-x-1">
                        <Link
                            v-if="canViewBilling"
                            :href="route('finance.billing')"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="$page.url.startsWith('/finance/billing') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                        >
                            {{ $t('billing') || 'Billing' }}
                        </Link>
                        <Link
                            v-if="canManageInvoices"
                            :href="route('finance.invoices.index')"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="$page.url.startsWith('/finance/invoices') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                        >
                            {{ $t('invoices') || 'Invoices' }}
                        </Link>
                        <Link
                            v-if="canViewLedgerProgress"
                            :href="route('finance.ledger-progress')"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="$page.url.startsWith('/finance/ledger-progress') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                        >
                            {{ $t('ledger_progress') || 'Ledger Progress' }}
                        </Link>
                        <Link
                            v-if="canViewProjects"
                            :href="route('team-projects.index')"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="$page.url.startsWith('/team-projects') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                        >
                            {{ $t('team_projects') || 'Team Projects' }}
                        </Link>
                    </nav>
                </div>

                <!-- Right: flash + role badge + user + sign out -->
                <div class="flex items-center gap-4">
                    <span
                        v-if="$page.props.flash?.success"
                        class="hidden sm:inline text-xs text-green-600 font-medium bg-green-50 px-3 py-1 rounded-full"
                    >
                        {{ $page.props.flash.success }}
                    </span>

                    <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                        {{ $t('finance_admin') }}
                    </span>

                    <div class="text-sm font-medium text-gray-700">{{ user?.name }}</div>

                    <!-- Notification Bell -->
                    <NotificationBell />

                    <!-- Language Switch Component -->
                    <LanguageSwitcher />

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="text-xs text-gray-400 hover:text-red-500 transition-colors"
                    >
                        {{ $t('logout') }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1 overflow-x-auto p-4 sm:p-6 lg:p-8">
            <slot />
        </main>
    </div>
</template>
