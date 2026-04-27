<script setup>
defineProps({
    disabled: { type: Boolean, default: false },
    type: { type: String, default: 'submit' },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-transparent bg-blue-600 dark:bg-blue-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 cursor-pointer"
    >
        <slot />
    </button>
</template>
