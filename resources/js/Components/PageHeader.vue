<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
});
</script>

<template>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ title }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">{{ description }}</p>
        </div>
        
        <div class="flex items-center gap-4 w-full sm:w-auto">
            <slot />
        </div>
    </div>
</template>
