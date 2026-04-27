<script setup>
import { Squares2X2Icon, ListBulletIcon } from '@heroicons/vue/24/outline';

defineProps({
    modelValue: {
        type: String,
        default: 'list',
        validator: (value) => ['list', 'grid'].includes(value)
    }
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex items-center bg-white dark:bg-slate-800 rounded-lg p-1 shadow-sm border border-gray-200 dark:border-slate-700">
        <button 
            @click="$emit('update:modelValue', 'grid')" 
            :class="['p-1.5 rounded-md transition-colors', modelValue === 'grid' ? 'bg-gray-100 dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white']"
        >
            <Squares2X2Icon class="h-5 w-5" />
        </button>
        <button 
            @click="$emit('update:modelValue', 'list')" 
            :class="['p-1.5 rounded-md transition-colors', modelValue === 'list' ? 'bg-gray-100 dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white']"
        >
            <ListBulletIcon class="h-5 w-5" />
        </button>
    </div>
</template>
