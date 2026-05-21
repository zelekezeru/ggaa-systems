<script setup>
import { PencilSquareIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline';

defineProps({
    editLabel:   { type: String, default: '' },
    deleteLabel: { type: String, default: '' },
    showLabel:   { type: String, default: '' },
    showLabels:  { type: Boolean, default: false }
});

defineEmits(['edit', 'delete', 'show']);
</script>

<template>
    <div class="flex items-center justify-end gap-2">
        <!-- Show Details Button -->
        <button
            v-if="$attrs.onShow"
            @click.stop="$emit('show')"
            class="flex items-center gap-1.5 p-2 rounded-xl text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-all active:scale-90"
            :title="showLabel || $t('view') || 'View Details'"
        >
            <EyeIcon class="h-5 w-5" />
            <span v-if="showLabels" class="text-xs font-bold uppercase tracking-tight">{{ showLabel || $t('view') || 'View Details' }}</span>
        </button>

        <!-- Edit Button -->
        <button
            @click.stop="$emit('edit')"
            class="flex items-center gap-1.5 p-2 rounded-xl text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-all active:scale-90"
            :title="editLabel || $t('edit')"
        >
            <PencilSquareIcon class="h-5 w-5" />
            <span v-if="showLabels" class="text-xs font-bold uppercase tracking-tight">{{ editLabel || $t('edit') }}</span>
        </button>

        <!-- Delete Button -->
        <button
            @click.stop="$emit('delete')"
            class="flex items-center gap-1.5 p-2 rounded-xl text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all active:scale-90"
            :title="deleteLabel || $t('delete')"
        >
            <TrashIcon class="h-5 w-5" />
            <span v-if="showLabels" class="text-xs font-bold uppercase tracking-tight">{{ deleteLabel || $t('delete') }}</span>
        </button>
    </div>
</template>
