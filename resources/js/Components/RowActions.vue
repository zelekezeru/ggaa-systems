<script setup>
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

defineProps({
    editLabel:   { type: String, default: '' },
    deleteLabel: { type: String, default: '' },
    showLabels:  { type: Boolean, default: false }
});

defineEmits(['edit', 'delete']);
</script>

<template>
    <div class="flex items-center justify-end gap-2">
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
