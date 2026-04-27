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
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-transparent bg-red-600 dark:bg-red-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 dark:hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors disabled:opacity-50 cursor-pointer"
    >
        <slot />
    </button>
</template>
