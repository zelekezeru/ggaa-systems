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
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-transparent bg-amber-100 dark:bg-amber-500/20 px-3 py-1.5 text-sm font-medium text-amber-700 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-500/30 focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors disabled:opacity-50 cursor-pointer"
    >
        <slot />
    </button>
</template>
