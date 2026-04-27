<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { PlusIcon, XMarkIcon, WrenchIcon, BoltIcon } from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

import PageHeader from '@/Components/PageHeader.vue';
import PrimaryActionBtn from '@/Components/PrimaryActionBtn.vue';
import DeleteConfirmationModal from '@/Components/DeleteConfirmationModal.vue';
import ModalWrapper from '@/Components/ModalWrapper.vue';
import Table from '@/Components/Table.vue';
import TableHead from '@/Components/TableHead.vue';
import TableBody from '@/Components/TableBody.vue';
import TableRow from '@/Components/TableRow.vue';
import TableHeaderCell from '@/Components/TableHeaderCell.vue';
import TableDataCell from '@/Components/TableDataCell.vue';
import RowActions from '@/Components/RowActions.vue';
import CancelBtn from '@/Components/CancelBtn.vue';
import SubmitBtn from '@/Components/SubmitBtn.vue';

const props = defineProps({
    serviceTypes: Array,
});

// CRUD Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    name: '',
    description: '',
    complexity_weight: 1,
    is_active: true,
});

const openModal = (type = null) => {
    if (type) {
        isEditing.value = true;
        form.id = type.id;
        form.name = type.name;
        form.description = type.description;
        form.complexity_weight = type.complexity_weight;
        form.is_active = !!type.is_active;
    } else {
        isEditing.value = false;
        form.reset();
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 200);
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.service-types.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.service-types.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

// Delete Modal State
const isDeleteModalOpen = ref(false);
const typeToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (type) => {
    typeToDelete.value = type;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!typeToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('admin.service-types.destroy', typeToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            isDeleting.value = false;
            typeToDelete.value = null;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
};
</script>

<template>
    <Head title="Service Configurations" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
            <PageHeader :title="$t('service_types')" :description="$t('service_types_desc')">
                <PrimaryActionBtn @click="openModal()" class="flex-1 sm:flex-none">
                    {{ $t('add_service_type') }}
                </PrimaryActionBtn>
            </PageHeader>

            <div class="mt-8">
                <Table>
                    <TableHead>
                        <tr>
                            <TableHeaderCell class="pl-4 sm:pl-6">{{ $t('service_name') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('description') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('complexity') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('status') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('action') }}</TableHeaderCell>
                        </tr>
                    </TableHead>
                    <TableBody>
                        <TableRow v-for="type in serviceTypes" :key="type.id">
                            <TableDataCell class="pl-4 sm:pl-6">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center text-amber-600 dark:text-amber-400 font-bold border border-amber-200 dark:border-amber-800">
                                        <BoltIcon class="h-5 w-5" />
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ type.name }}</div>
                                        <div class="text-[11px] text-slate-400 uppercase tracking-tighter">{{ type.slug }}</div>
                                    </div>
                                </div>
                            </TableDataCell>
                            <TableDataCell>
                                <div class="max-w-xs truncate text-slate-500 dark:text-slate-400">{{ type.description || $t('no_description') }}</div>
                            </TableDataCell>
                            <TableDataCell>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold ring-1 ring-inset ring-blue-600/20">
                                    {{ $t('complexity') }}: {{ type.complexity_weight }}
                                </span>
                            </TableDataCell>
                            <TableDataCell>
                                <span :class="[type.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400', 'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold']">
                                    {{ type.is_active ? $t('active') : $t('archived') }}
                                </span>
                            </TableDataCell>
                            <TableDataCell class="pr-4 sm:pr-6 text-right">
                                <RowActions
                                    :edit-label="$t('edit')"
                                    :delete-label="$t('delete')"
                                    @edit="openModal(type)"
                                    @delete="confirmDelete(type)"
                                />
                            </TableDataCell>
                        </TableRow>
                        <tr v-if="serviceTypes.length === 0">
                            <td colspan="5" class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">{{ $t('no_results_found') }}</td>
                        </tr>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- MODAL -->
        <ModalWrapper
            :show="isModalOpen"
            :title="isEditing ? $t('edit_service_type') : $t('add_service_type')"
            max-width="sm:max-w-md"
            @close="closeModal"
        >
            <template #close-btn>
                <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </template>

            <form id="service-type-form" @submit.prevent="submitForm" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('service_name') }}</label>
                    <input v-model="form.name" type="text" required placeholder="e.g. Monthly VAT Filing" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-all" />
                    <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-600 font-medium">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('description') }}</label>
                    <textarea v-model="form.description" rows="3" placeholder="Brief explanation of the service..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-all"></textarea>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Complexity Weight: <span class="text-blue-600 dark:text-blue-400">{{ form.complexity_weight }}</span></label>
                        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Affects Staff Load</span>
                    </div>
                    <input type="range" v-model="form.complexity_weight" min="1" max="10" class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-blue-600">
                    <div class="flex justify-between mt-1 text-[10px] text-slate-400 font-bold uppercase tracking-tighter">
                        <span>Low Impact</span>
                        <span>High Impact</span>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl ring-1 ring-inset ring-slate-100 dark:ring-slate-700">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Active Status</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Available for future client assignment</p>
                    </div>
                    <button type="button" @click="form.is_active = !form.is_active"
                        :class="[form.is_active ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700', 'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out font-semibold']">
                        <span :class="[form.is_active ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                    </button>
                </div>
            </form>

            <template #footer>
                <CancelBtn @click="closeModal">{{ $t('cancel') }}</CancelBtn>
                <SubmitBtn form="service-type-form" :disabled="form.processing">
                    {{ form.processing ? '...' : (isEditing ? $t('save') : $t('save')) }}
                </SubmitBtn>
            </template>
        </ModalWrapper>

        <!-- DELETE MODAL -->
        <DeleteConfirmationModal
            :show="isDeleteModalOpen"
            title="Remove Service Type"
            :isDeleting="isDeleting"
            @close="isDeleteModalOpen = false"
            @confirm="executeDelete"
        >
            {{ t('are_you_sure_you_want_to_delete')}} <strong class="text-slate-900 dark:text-white">{{ typeToDelete?.name }}</strong>? This will remove this configuration permanently.
        </DeleteConfirmationModal>
    </AdminLayout>
</template>
