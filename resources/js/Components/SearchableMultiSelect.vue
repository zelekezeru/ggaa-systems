<script setup>
import { ref, computed } from 'vue';
import { MagnifyingGlassIcon, CheckIcon, ChevronDownIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    options: {
        type: Array,
        required: true
    },
    placeholder: {
        type: String,
        default: 'Select services...'
    },
    label: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref(null);

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    return props.options.filter(opt => 
        opt.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const selectedNames = computed(() => {
    return props.options
        .filter(opt => props.modelValue.includes(opt.id))
        .map(opt => opt.name);
});

const toggleOption = (id) => {
    const newValue = [...props.modelValue];
    const index = newValue.indexOf(id);
    if (index > -1) {
        newValue.splice(index, 1);
    } else {
        newValue.push(id);
    }
    emit('update:modelValue', newValue);
};

const removeOption = (id) => {
    const newValue = props.modelValue.filter(val => val !== id);
    emit('update:modelValue', newValue);
};

// Close dropdown when clicking outside
if (typeof window !== 'undefined') {
    window.addEventListener('click', (e) => {
        if (containerRef.value && !containerRef.value.contains(e.target)) {
            isOpen.value = false;
        }
    });
}
</script>

<template>
    <div ref="containerRef" class="relative">
        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ label || $t('options') }}</label>
        
        <div 
            @click="isOpen = !isOpen"
            class="min-h-[44px] w-full rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1.5 flex flex-wrap gap-1.5 items-center cursor-pointer hover:border-slate-300 dark:hover:border-slate-600 transition-all shadow-sm"
            :class="{ 'border-blue-500 ring-2 ring-blue-500/10 dark:border-blue-400': isOpen }"
        >
            <div 
                v-for="id in modelValue" 
                :key="id"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-[11px] font-bold uppercase tracking-tight border border-blue-100 dark:border-blue-800"
            >
                {{ options.find(o => o.id === id)?.name }}
                <button @click.stop="removeOption(id)" class="hover:text-blue-900 dark:hover:text-blue-200">
                    <XMarkIcon class="h-3 w-3" />
                </button>
            </div>

            <span v-if="modelValue.length === 0" class="text-slate-400 text-sm pl-1">{{ placeholder || $t('select_options') }}</span>

            <div class="ml-auto pointer-events-none">
                <ChevronDownIcon class="h-4 w-4 text-slate-400" :class="{ 'rotate-180 transition-transform': isOpen }" />
            </div>
        </div>

        <!-- Dropdown Panel -->
        <div 
            v-if="isOpen"
            class="absolute z-[60] mt-2 w-full bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden animate-in fade-in zoom-in duration-200"
        >
            <!-- Search Area -->
            <div class="p-3 border-b border-slate-100 dark:border-slate-700">
                <div class="relative">
                    <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                    <input 
                        v-model="searchQuery"
                        type="text"
                        :placeholder="$t('search')"
                        class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-900/50 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white"
                        @click.stop
                    />
                </div>
            </div>

            <!-- Options List -->
            <ul class="max-h-60 overflow-y-auto p-2 custom-scrollbar">
                <li 
                    v-for="opt in filteredOptions" 
                    :key="opt.id"
                    @click.stop="toggleOption(opt.id)"
                    class="group flex items-center justify-between px-3 py-2.5 rounded-xl cursor-pointer transition-all"
                    :class="[
                        modelValue.includes(opt.id) 
                            ? 'bg-blue-50 dark:bg-blue-600/10 text-blue-700 dark:text-blue-400 font-bold' 
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700/50'
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <div 
                            class="h-5 w-5 rounded-md border-2 flex items-center justify-center transition-colors"
                            :class="[
                                modelValue.includes(opt.id)
                                    ? 'bg-blue-600 border-blue-600 shadow-sm'
                                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 group-hover:border-slate-300'
                            ]"
                        >
                            <CheckIcon v-if="modelValue.includes(opt.id)" class="h-3 w-3 text-white stroke-[3px]" />
                        </div>
                        <span class="text-sm truncate">{{ opt.name }}</span>
                    </div>
                </li>
                <li v-if="filteredOptions.length === 0" class="p-4 text-center text-xs text-slate-400 italic">
                    {{ $t('no_matching_results') }}
                </li>
            </ul>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
}
</style>
