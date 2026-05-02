<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <GuestLayout>
        <Head title="Client Portal" />

        <div class="space-y-8 py-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Client Portal Login</h1>
                <p class="mt-3 text-sm text-slate-600 max-w-xl">
                    Welcome to the client portal. Use your portal credentials to sign in and access your client dashboard, tasks, and messages.
                </p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500 mb-4">
                    Click the button below to go to the secure client sign-in page.
                </p>
                <Link
                    :href="route('portal.login')"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800 transition"
                >
                    Go to login
                </Link>
            </div>

            <div class="text-sm text-slate-500">
                <p>
                    If you already have an account, you can also <Link :href="route('login')" class="text-blue-600 hover:text-blue-500">sign in directly</Link>.
                </p>
            </div>
        </div>
    </GuestLayout>
</template>
