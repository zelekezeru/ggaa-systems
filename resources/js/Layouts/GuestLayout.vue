<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

const year = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-screen">
        <!-- Left brand panel -->
        <div class="hidden lg:flex lg:w-5/12 xl:w-1/2 bg-blue-900 flex-col justify-between p-12 relative overflow-hidden">
            <!-- Decorative circles -->
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-blue-800 opacity-50"></div>
            <div class="absolute -bottom-32 -left-16 w-80 h-80 rounded-full bg-blue-800 opacity-40"></div>

            <!-- Logo + brand name -->
            <div class="relative flex items-center gap-3">
                <ApplicationLogo class="h-10 w-10 fill-current text-white" />
                <span class="text-white font-bold text-xl tracking-tight">GGAA Systems</span>
            </div>

            <!-- Hero text -->
            <div class="relative">
                <div class="inline-flex items-center gap-2 bg-blue-800 rounded-full px-4 py-1.5 mb-6">
                    <span class="h-2 w-2 rounded-full bg-green-400"></span>
                    <span class="text-blue-100 text-xs font-medium tracking-wide uppercase">{{ $t('compliance_portal') }}</span>
                </div>
                <h1 class="text-4xl font-bold text-white leading-tight mb-4">
                    {{ $t('manage_compliance_confidence') }}
                </h1>
                <p class="text-blue-200 text-base leading-relaxed max-w-sm">
                    {{ $t('secure_portal_desc') }}
                </p>

                <!-- Feature list -->
                <ul class="mt-8 space-y-3">
                    <li v-for="item in [$t('realtime_task_tracking'), $t('secure_document_uploads'), $t('deadline_alerts')]" :key="item"
                        class="flex items-center gap-3 text-blue-100 text-sm">
                        <span class="flex-shrink-0 h-5 w-5 rounded-full bg-blue-700 flex items-center justify-center">
                            <svg class="h-3 w-3 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        {{ item }}
                    </li>
                </ul>
            </div>

            <!-- Footer -->
            <div class="relative text-blue-400 text-xs">
                &copy; {{ year }} GGAA Systems. {{ $t('all_rights_reserved') }}
            </div>
        </div>

        <!-- Right form panel -->
        <div class="flex flex-1 flex-col justify-center py-12 px-6 sm:px-10 lg:px-14 bg-gray-50">
            <!-- Mobile logo -->
            <div class="lg:hidden mb-8 flex justify-center">
                <Link href="/" class="flex items-center gap-2">
                    <ApplicationLogo class="h-9 w-9 fill-current text-blue-900" />
                    <span class="font-bold text-blue-900 text-lg tracking-tight">GGAA Systems</span>
                </Link>
            </div>

            <div class="w-full max-w-md mx-auto">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
