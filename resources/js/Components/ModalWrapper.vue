<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

defineProps({
    show:    { type: Boolean, required: true },
    title:   { type: String,  default: '' },
    maxWidth:{ type: String,  default: 'sm:max-w-lg' },
});

defineEmits(['close']);
</script>

<template>
    <TransitionRoot appear :show="show" as="template">
        <Dialog as="div" @close="$emit('close')" class="relative z-50">

            <!-- Backdrop -->
            <TransitionChild
                as="template"
                enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-200 ease-in"  leave-from="opacity-100" leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-gray-900/75 dark:bg-slate-900/80 backdrop-blur-sm" />
            </TransitionChild>

            <!-- Panel container -->
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out" enter-from="opacity-0 translate-y-4 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="duration-200 ease-in"  leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-4 sm:scale-95"
                    >
                        <DialogPanel :class="['relative w-full transform overflow-hidden rounded-2xl bg-white dark:bg-slate-800 text-left shadow-xl transition-all sm:my-8 border border-gray-100 dark:border-slate-700', maxWidth]">

                            <!-- Optional header -->
                            <div v-if="title" class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-100 dark:border-slate-700">
                                <DialogTitle as="h3" class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ title }}
                                </DialogTitle>
                                <slot name="close-btn" />
                            </div>

                            <!-- Body -->
                            <div class="px-6 py-5">
                                <slot />
                            </div>

                            <!-- Footer -->
                            <div v-if="$slots.footer" class="flex justify-end gap-3 px-6 py-4 bg-gray-50 dark:bg-slate-700/40 border-t border-gray-100 dark:border-slate-700">
                                <slot name="footer" />
                            </div>

                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>

        </Dialog>
    </TransitionRoot>
</template>
