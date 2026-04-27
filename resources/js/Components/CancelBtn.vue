<script setup>
defineProps({
    disabled: { type: Boolean, default: false },
    type: { type: String, default: 'button' },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-slate-300 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors disabled:opacity-50 cursor-pointer"
    >
        <slot />
    </button>
</template>
