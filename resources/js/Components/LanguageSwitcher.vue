<script setup>
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const props = defineProps({
    variant: {
        type: String,
        default: 'default' // 'default' (inline buttons), 'sidebar' (full width buttons), 'responsive' (larger padding)
    }
});

const setLanguage = (lang) => {
    locale.value = lang;
    localStorage.setItem('locale', lang);
    document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = lang;
};

const languages = [
    { code: 'en', label: 'EN', fullLabel: 'English' },
    { code: 'am', label: 'አማ', fullLabel: 'አማርኛ' },
];
</script>

<template>
    <!-- Default / Inline Variant -->
    <div v-if="variant === 'default'" class="flex items-center p-1 bg-gray-100 dark:bg-slate-800/50 rounded-xl border border-gray-100 dark:border-slate-700">
        <button 
            v-for="lang in languages" 
            :key="lang.code"
            @click="setLanguage(lang.code)"
            class="flex items-center gap-1.5 px-3 py-1 rounded-lg text-[10px] font-bold transition-all duration-200"
            :class="locale === lang.code 
                ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' 
                : 'text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300'"
        >
            <span>{{ lang.flag }}</span>
            <span class="uppercase" :class="{'text-[9px] translate-y-px': lang.code === 'am'}">{{ lang.label }}</span>
        </button>
    </div>

    <!-- Sidebar / Full Width Variant -->
    <div v-else-if="variant === 'sidebar'" class="flex items-center p-1 bg-gray-50 dark:bg-slate-700/30 rounded-2xl border border-gray-100 dark:border-slate-700">
        <button 
            v-for="lang in languages" 
            :key="lang.code"
            @click="setLanguage(lang.code)"
            class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-[10px] font-black transition-all duration-300"
            :class="locale === lang.code 
                ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-md ring-1 ring-slate-200/50 dark:ring-slate-600' 
                : 'text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300'"
        >
            <span class="text-base">{{ lang.flag }}</span>
            <span class="uppercase tracking-widest leading-none" :class="{'text-[9px]': lang.code === 'am'}">{{ lang.code === 'en' || lang.code === 'ar' ? lang.code : 'አማ' }}</span>
        </button>
    </div>

    <!-- Responsive / Expanded Variant -->
    <div v-else-if="variant === 'responsive'" class="flex gap-3">
        <button 
            v-for="lang in languages" 
            :key="lang.code"
            @click="setLanguage(lang.code)"
            class="flex-1 flex flex-col items-center gap-2 p-3 rounded-2xl border transition-all" 
            :class="locale === lang.code 
                ? 'bg-blue-50 border-blue-200 dark:bg-blue-500/10 dark:border-blue-500/30 font-bold' 
                : 'bg-white dark:bg-slate-800 border-gray-100 dark:border-slate-700'"
        >
            <span class="text-2xl">{{ lang.flag }}</span>
            <span class="text-[10px] font-bold uppercase tracking-tighter" :class="locale === lang.code ? 'text-blue-700 dark:text-blue-400' : 'text-slate-500'">
                {{ lang.label }}
            </span>
        </button>
    </div>
</template>
