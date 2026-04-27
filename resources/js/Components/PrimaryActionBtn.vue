<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline';
</script>

<template>
    <button class="flex items-center justify-center gap-2 bg-blue-600 dark:bg-blue-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 dark:hover:bg-blue-600 shadow-sm transition-colors cursor-pointer disabled:opacity-50">
        <slot name="icon">
            <PlusIcon class="h-5 w-5" />
        </slot>
        <slot />
    </button>
</template>
