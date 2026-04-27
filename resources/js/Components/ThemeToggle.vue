<template>
    <button
        @click="toggleDark()"
        class="relative inline-flex items-center justify-center p-2 rounded-full text-gray-500 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 dark:text-gray-400 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-900"
        :aria-label="$t('toggle_dark_mode')"
    >
        <!-- Sun icon for Dark Mode (click to switch to light) -->
        <SunIcon 
            v-if="isDark" 
            class="h-5 w-5" 
            aria-hidden="true" 
        />
        
        <!-- Moon icon for Light Mode (click to switch to dark) -->
        <MoonIcon 
            v-else 
            class="h-5 w-5" 
            aria-hidden="true" 
        />
    </button>
</template>

<script setup>
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline';
import { useTheme } from '@/Composables/useTheme';

const { isDark, toggleDark } = useTheme();
</script>
