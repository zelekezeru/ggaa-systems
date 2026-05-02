<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const notifications = computed(() => page.props.auth?.notifications ?? []);

const mobileMenuOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-[#0B1120] font-sans text-slate-900 dark:text-slate-100 flex flex-col transition-colors duration-300">

        <!-- Modern Floating Navbar -->
        <header class="sticky top-0 z-50 pt-4 px-4 sm:px-6 lg:px-8">
            <div class="w-full mx-auto backdrop-blur-2xl bg-white/70 dark:bg-slate-900/70 border border-slate-200/50 dark:border-slate-800/50 shadow-xl shadow-slate-200/20 dark:shadow-none rounded-2xl min-h-16 py-2 md:py-0 flex items-center justify-between px-4 sm:px-6 transition-all duration-300">

                <!-- Left: brand + nav -->
                <div class="flex items-center gap-4 sm:gap-8">
                    <!-- Mobile Hamburger -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-slate-500 hover:text-indigo-600 focus:outline-none transition-colors">
                        <Bars3Icon v-if="!mobileMenuOpen" class="w-6 h-6" />
                        <XMarkIcon v-else class="w-6 h-6" />
                    </button>

                    <div class="flex items-center gap-2 relative group flex-shrink-0">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-500/30 group-hover:shadow-indigo-500/50 transition-all duration-300 group-hover:scale-105">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span class="text-xl font-black bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-700 dark:from-white dark:to-slate-300 tracking-tight hidden sm:block">
                            {{ $t('workspace') }}
                        </span>
                    </div>

                    <nav class="hidden md:flex items-center space-x-2 border-l border-slate-200 dark:border-slate-800 pl-6 ml-2">
                        <!-- My Board -->
                        <Link
                            :href="route('employee.workspace')"
                            class="relative px-4 py-2 rounded-xl text-sm font-bold transition-all duration-300 overflow-hidden group"
                            :class="[
                                $page.url === '/employee/workspace' || $page.url.startsWith('/employee/workspace') || $page.url === '/workspace'
                                    ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/80 dark:bg-indigo-500/10 shadow-sm ring-1 ring-indigo-100 dark:ring-indigo-500/20'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50'
                            ]"
                        >
                            <span class="relative z-10">{{ $t('my_board') }}</span>
                            <div v-if="$page.url === '/employee/workspace' || $page.url.startsWith('/employee/workspace') || $page.url === '/workspace'" 
                                 class="absolute bottom-0 left-1/2 -translate-x-1/2 w-8 h-1 bg-indigo-600 dark:bg-indigo-400 rounded-t-full shadow-[0_-2px_10px_rgba(79,70,229,0.5)]"></div>
                        </Link>
                        <!-- Daily Tasks -->
                        <Link
                            :href="route('employee.daily-tasks.index')"
                            class="relative px-4 py-2 rounded-xl text-sm font-bold transition-all duration-300 overflow-hidden group"
                            :class="[
                                $page.url === '/employee/daily-tasks' || $page.url.startsWith('/employee/daily-tasks') || $page.url === '/daily-tasks'
                                    ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/80 dark:bg-indigo-500/10 shadow-sm ring-1 ring-indigo-100 dark:ring-indigo-500/20'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50'
                            ]"
                        >
                            <span class="relative z-10">{{ $t('daily_tasks') }}</span>
                            <div v-if="$page.url === '/employee/daily-tasks' || $page.url.startsWith('/employee/daily-tasks') || $page.url === '/daily-tasks'" 
                                 class="absolute bottom-0 left-1/2 -translate-x-1/2 w-8 h-1 bg-indigo-600 dark:bg-indigo-400 rounded-t-full shadow-[0_-2px_10px_rgba(79,70,229,0.5)]"></div>
                        </Link>
                        
                        
                        <!-- Performance -->
                        <Link
                            :href="route('employee.performance')"
                            class="relative px-4 py-2 rounded-xl text-sm font-bold transition-all duration-300 overflow-hidden group"
                            :class="[
                                $page.url === '/employee/performance' || $page.url.startsWith('/employee/performance') || $page.url === '/performance'
                                    ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/80 dark:bg-indigo-500/10 shadow-sm ring-1 ring-indigo-100 dark:ring-indigo-500/20'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50'
                            ]"
                        >
                            <span class="relative z-10">{{ $t('performance') }}</span>
                            <div v-if="$page.url === '/employee/performance' || $page.url.startsWith('/employee/performance') || $page.url === '/performance'" 
                                 class="absolute bottom-0 left-1/2 -translate-x-1/2 w-8 h-1 bg-indigo-600 dark:bg-indigo-400 rounded-t-full shadow-[0_-2px_10px_rgba(79,70,229,0.5)]"></div>
                        </Link>
                        
                        <!-- Team Projects -->
                        <Link
                            :href="route('employee.team-projects.index')"
                            class="relative px-4 py-2 rounded-xl text-sm font-bold transition-all duration-300 overflow-hidden group"
                            :class="[
                                $page.url === '/employee/team-projects' || $page.url.startsWith('/employee/team-projects') || $page.url === '/team-projects'
                                    ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/80 dark:bg-indigo-500/10 shadow-sm ring-1 ring-indigo-100 dark:ring-indigo-500/20'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50'
                            ]"
                        >
                            <span class="relative z-10">{{ $t('team_projects') }}</span>
                            <div v-if="$page.url === '/employee/team-projects' || $page.url.startsWith('/employee/team-projects') || $page.url === '/team-projects'" 
                                 class="absolute bottom-0 left-1/2 -translate-x-1/2 w-8 h-1 bg-indigo-600 dark:bg-indigo-400 rounded-t-full shadow-[0_-2px_10px_rgba(79,70,229,0.5)]"></div>
                        </Link>
                    </nav>
                </div>

                <!-- Right: bell + username + sign out -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <!-- Notification Bell -->
                    <NotificationBell />
                    
                    <!-- Theme Toggle -->
                    <ThemeToggle />

                    <div class="hidden sm:flex items-center gap-3 pl-4 border-l border-slate-200 dark:border-slate-800">
                        <div class="flex flex-col items-end">
                            <span class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ user?.name }}</span>
                            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">{{ user?.roles?.[0]?.name || 'Employee' }}</span>
                        </div>
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-500 p-[2px] shadow-sm transform hover:scale-105 transition-transform duration-300 cursor-pointer">
                            <div class="w-full h-full rounded-[10px] bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-sm font-black text-slate-700 dark:text-slate-200 shadow-inner">
                                {{ user?.name?.charAt(0) || 'U' }}
                            </div>
                        </div>
                    </div>

                    <!-- Language Switch Component -->
                    <div class="scale-90 ml-1">
                        <LanguageSwitcher />
                    </div>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="p-2 ml-1 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-xl transition-all duration-300"
                        :title="$t('logout')"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Absolute Mobile Dropdown Menu -->
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-4">
                <div v-show="mobileMenuOpen" class="absolute top-24 left-4 right-4 md:hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl rounded-2xl overflow-hidden z-50">
                    <nav class="flex flex-col p-4 w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <Link :href="route('employee.workspace')" class="px-4 py-3 text-sm font-bold w-full transition-colors" :class="$page.url.includes('/workspace') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 hover:text-indigo-600'" @click="mobileMenuOpen = false">{{ $t('my_board') }}</Link>
                        <Link :href="route('employee.daily-tasks.index')" class="px-4 py-3 text-sm font-bold w-full transition-colors" :class="$page.url.includes('/daily-tasks') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 hover:text-indigo-600'" @click="mobileMenuOpen = false">{{ $t('daily_tasks') }}</Link>
                        <Link :href="route('employee.performance')" class="px-4 py-3 text-sm font-bold w-full transition-colors" :class="$page.url.includes('/performance') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 hover:text-indigo-600'" @click="mobileMenuOpen = false">{{ $t('performance') }}</Link>
                        <Link :href="route('employee.team-projects.index')" class="px-4 py-3 text-sm font-bold w-full transition-colors" :class="$page.url.includes('/team-projects') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 hover:text-indigo-600'" @click="mobileMenuOpen = false">{{ $t('team_projects') }}</Link>
                    </nav>
                </div>
            </transition>
        </header>

        <!-- Page content -->
        <main class="flex-1 w-full max-w-none mx-auto pt-8 px-4 sm:px-6 lg:px-8 pb-12 z-10 flex flex-col">
            <!-- Flash success -->
            <transition enter-active-class="transition ease-out duration-300" enter-from-class="transform opacity-0 -translate-y-4" enter-to-class="transform opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="transform opacity-100 translate-y-0" leave-to-class="transform opacity-0 -translate-y-4">
                <div v-if="$page.props.flash?.success" class="mb-6 mx-auto w-full max-w-2xl">
                    <div class="p-4 rounded-2xl bg-emerald-50/80 dark:bg-emerald-500/10 border border-emerald-200/50 dark:border-emerald-500/20 shadow-lg shadow-emerald-500/10 backdrop-blur-sm flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <span class="text-sm font-bold text-emerald-800 dark:text-emerald-300">{{ $page.props.flash.success }}</span>
                    </div>
                </div>
            </transition>

            <slot />
        </main>
        
        <!-- Decorative Background Elements -->
        <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
            <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-indigo-500/5 dark:bg-indigo-500/10 blur-[120px]"></div>
            <div class="absolute top-[40%] -right-[10%] w-[40%] h-[40%] rounded-full bg-blue-500/5 dark:bg-blue-500/10 blur-[100px]"></div>
            <div class="absolute -bottom-[20%] left-[20%] w-[60%] h-[60%] rounded-full bg-purple-500/5 dark:bg-purple-500/5 blur-[150px]"></div>
        </div>
    </div>
</template>
