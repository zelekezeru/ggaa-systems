<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put(route('password.change.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Set your password" />

        <!-- Heading -->
        <div class="mb-7">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50">
                <svg class="h-6 w-6 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Set your password</h2>
            <p class="mt-1 text-sm text-gray-500">
                For your security, please replace the default password before continuing.
            </p>
        </div>

        <!-- Status message -->
        <div v-if="status" class="mb-5 flex items-center gap-2 rounded-xl bg-blue-50 border border-blue-200 px-4 py-3 text-sm font-medium text-blue-700">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="password" value="New password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autofocus
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirm new password" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex justify-center items-center py-2.5 px-4 rounded-xl bg-blue-900 text-white text-sm font-semibold hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition disabled:opacity-50"
            >
                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Save password &amp; continue
            </button>

            <div class="text-center">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-xs font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:underline transition"
                >
                    Sign out
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
