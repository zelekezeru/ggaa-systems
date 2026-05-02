<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200 flex flex-col">

        <!-- Sticky top header -->
        <header class="bg-blue-900 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Left: brand -->
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2.5">
                        <ApplicationLogo class="h-8 w-8 fill-current text-white" />
                        <div>
                            <div class="text-sm font-bold text-white leading-none">GGAA Systems</div>
                            <div class="text-xs text-blue-200 leading-none mt-0.5">{{ $t('client_portal') }}</div>
                        </div>
                    </div>

                    <!-- Main Nav -->
                    <nav class="hidden md:flex items-center gap-4 ml-4">
                        <Link 
                            :href="route('client.dashboard')" 
                            :class="[route().current('client.dashboard') ? 'text-white bg-blue-800/50' : 'text-blue-100 hover:text-white', 'px-3 py-2 rounded-xl text-sm font-bold transition-all']"
                        >
                            Dashboard
                        </Link>
                        <Link 
                            :href="route('client.messages.index')" 
                            :class="[route().current('client.messages.index') ? 'text-white bg-blue-800/50' : 'text-blue-100 hover:text-white', 'px-3 py-2 rounded-xl text-sm font-bold transition-all']"
                        >
                            Messages
                        </Link>
                        <Link 
                            :href="route('client.team-projects.index')" 
                            :class="[route().current('client.team-projects.*') ? 'text-white bg-blue-800/50' : 'text-blue-100 hover:text-white', 'px-3 py-2 rounded-xl text-sm font-bold transition-all']"
                        >
                            Projects
                        </Link>
                    </nav>
                </div>

                <!-- Right: flash + user + sign out -->
                <div class="flex items-center gap-4">
                    <span
                        v-if="$page.props.flash?.success"
                        class="hidden sm:inline text-xs text-green-300 font-medium bg-blue-800 px-3 py-1 rounded-full"
                    >
                        {{ $page.props.flash.success }}
                    </span>

                    <div class="hidden sm:block text-sm font-medium text-blue-100">{{ user?.name }}</div>

                    <div class="flex items-center gap-2">
                        <ThemeToggle />
                        <!-- Notification Bell -->
                        <NotificationBell />
                        <!-- Language Switch Component -->
                        <LanguageSwitcher />
                    </div>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="text-xs text-blue-300 hover:text-white transition-colors"
                    >
                        {{ $t('logout') }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Mobile Nav -->
        <div class="md:hidden bg-blue-800/50 border-b border-blue-700/50 backdrop-blur-md">
            <div class="max-w-5xl mx-auto px-4 flex items-center justify-around py-2">
                <Link :href="route('client.dashboard')" class="text-xs font-bold text-blue-100 p-2">Dashboard</Link>
                <Link :href="route('client.messages.index')" class="text-xs font-bold text-blue-100 p-2">Messages</Link>
                <Link :href="route('client.team-projects.index')" class="text-xs font-bold text-blue-100 p-2">Projects</Link>
            </div>
        </div>

        <!-- Page content -->
        <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>
    </div>
</template>
