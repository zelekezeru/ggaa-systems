<script setup>
import { computed } from 'vue';

const props = defineProps({
    tabs: {
        type: Array, // Array of strings or objects { name: String, label: String, count?: Number }
        required: true
    },
    modelValue: {
        type: [String, Number],
        required: true
    },
    variant: {
        type: String,
        default: 'underline' // 'underline' or 'pills'
    },
    noBorder: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const formattedTabs = computed(() => {
    return props.tabs.map(tab => {
        if (typeof tab === 'string') {
            return { name: tab, label: tab };
        }
        return tab;
    });
});

const selectTab = (name) => {
    emit('update:modelValue', name);
};
</script>

<template>
    <div :class="[(variant === 'underline' && !noBorder) ? 'border-b border-gray-200 dark:border-slate-800 mb-6' : '']">
        <nav :class="[variant === 'underline' ? (noBorder ? '' : '-mb-px') : 'bg-slate-100 dark:bg-slate-800/50 p-1 rounded-xl inline-flex', 'flex space-x-2 overflow-x-auto scrollbar-hide transition-all']">
            <button
                v-for="tab in formattedTabs"
                :key="tab.name"
                @click="selectTab(tab.name)"
                :class="[
                    variant === 'underline' 
                    ? (modelValue === tab.name 
                        ? 'border-blue-600 text-blue-600 dark:text-blue-400' 
                        : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-700')
                    : (modelValue === tab.name
                        ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm'
                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'),
                    variant === 'underline' ? 'border-b-2 py-4 px-1' : 'py-2 px-4 rounded-lg',
                    'group relative whitespace-nowrap text-xs font-black uppercase tracking-widest transition-all duration-300 flex items-center gap-2 outline-none'
                ]"
            >
                <slot :name="`tab-${tab.name}`" :tab="tab">
                    {{ tab.label }}
                </slot>

                <!-- Optional Count Badge -->
                <span 
                    v-if="tab.count !== undefined"
                    :class="[
                        modelValue === tab.name 
                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400' 
                        : 'bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-600'
                    , 'ml-1 rounded-full px-2 py-0.5 text-[10px] font-bold transition-colors']"
                >
                    {{ tab.count }}
                </span>

                <!-- Underline Glow Effect -->
                <div 
                    v-if="variant === 'underline' && modelValue === tab.name"
                    class="absolute bottom-[-2px] left-0 right-0 h-0.5 bg-blue-600 dark:bg-blue-500 rounded-full shadow-[0_0_10px_rgba(37,99,235,0.4)]"
                ></div>
            </button>
        </nav>
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
