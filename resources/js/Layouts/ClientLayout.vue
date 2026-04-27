<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col">

        <!-- Sticky top header -->
        <header class="bg-blue-900 sticky top-0 z-50">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">

                <!-- Left: brand -->
                <div class="flex items-center gap-2.5">
                    <ApplicationLogo class="h-8 w-8 fill-current text-white" />
                    <div>
                        <div class="text-sm font-bold text-white leading-none">GGAA Systems</div>
                        <div class="text-xs text-blue-200 leading-none mt-0.5">{{ $t('client_portal') }}</div>
                    </div>
                </div>

                <!-- Right: flash + user + sign out -->
                <div class="flex items-center gap-4">
                    <span
                        v-if="$page.props.flash?.success"
                        class="hidden sm:inline text-xs text-green-300 font-medium bg-blue-800 px-3 py-1 rounded-full"
                    >
                        {{ $page.props.flash.success }}
                    </span>

                    <div class="text-sm font-medium text-blue-100">{{ user?.name }}</div>

                    <!-- Language Switch Component -->
                    <LanguageSwitcher />

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

        <!-- Page content -->
        <main class="flex-1 max-w-5xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>
    </div>
</template>
