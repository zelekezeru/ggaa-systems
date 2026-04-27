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
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('forgot_password_title')" />

        <!-- Heading -->
        <div class="mb-7">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50">
                <svg class="h-6 w-6 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6.75 6.75 0 01-7.029 6.74 1.5 1.5 0 01-1.453-1.005L8.75 12.75m7-7.5H12m0 0V9m0-4.5a3 3 0 00-3 3m0 0v4.5M6 12H3.75m2.25 0a3 3 0 003 3" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $t('forgot_password_title') }}</h2>
            <p class="mt-2 text-sm text-gray-500 leading-relaxed">
                {{ $t('forgot_password_desc') }}
            </p>
        </div>

        <!-- Status message -->
        <div v-if="status" class="mb-5 flex items-center gap-2 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm font-medium text-green-700">
            <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" :value="$t('email_id')" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
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
                {{ $t('send_reset_link') }}
            </button>

            <p class="text-center text-sm text-gray-500">
                {{ $t('remembered_it') }}
                <Link :href="route('login')" class="font-medium text-blue-600 hover:text-blue-500 transition">
                    {{ $t('back_to_sign_in') }}
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
