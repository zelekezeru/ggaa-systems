<script setup>
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import ModalWrapper from '@/Components/ModalWrapper.vue';
import DangerBtn   from '@/Components/DangerBtn.vue';
import CancelBtn   from '@/Components/CancelBtn.vue';

defineProps({
    show:       Boolean,
    title:      String,
    isDeleting: Boolean,
});

defineEmits(['close', 'confirm']);
</script>

<template>
    <ModalWrapper :show="show" @close="$emit('close')">

        <!-- Danger body -->
        <div class="sm:flex sm:items-start">
            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/20 sm:mx-0 sm:h-10 sm:w-10">
                <ExclamationTriangleIcon class="h-6 w-6 text-red-600 dark:text-red-500" />
            </div>
            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ title }}</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">
                    <slot />
                </p>
            </div>
        </div>

        <!-- Footer -->
        <template #footer>
            <CancelBtn @click="$emit('close')">
                {{ $t('cancel') }}
            </CancelBtn>
            <DangerBtn :disabled="isDeleting" @click="$emit('confirm')">
                {{ isDeleting ? ($t('removing') || 'Removing…') : ($t('delete') || 'Delete') }}
            </DangerBtn>
        </template>

    </ModalWrapper>
</template>
