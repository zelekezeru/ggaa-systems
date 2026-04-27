<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { PlusIcon, PencilSquareIcon, TrashIcon, XMarkIcon, BuildingOfficeIcon, UserGroupIcon, BriefcaseIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';

import PageHeader from '@/Components/PageHeader.vue';
import ViewModeToggle from '@/Components/ViewModeToggle.vue';
import PrimaryActionBtn from '@/Components/PrimaryActionBtn.vue';
import DeleteConfirmationModal from '@/Components/DeleteConfirmationModal.vue';
import ModalWrapper from '@/Components/ModalWrapper.vue';
import Table from '@/Components/Table.vue';
import TableHead from '@/Components/TableHead.vue';
import TableBody from '@/Components/TableBody.vue';
import TableRow from '@/Components/TableRow.vue';
import TableHeaderCell from '@/Components/TableHeaderCell.vue';
import TableDataCell from '@/Components/TableDataCell.vue';
import GridContainer from '@/Components/GridContainer.vue';
import GridCard from '@/Components/GridCard.vue';
import RowActions from '@/Components/RowActions.vue';
import CancelBtn from '@/Components/CancelBtn.vue';
import SubmitBtn from '@/Components/SubmitBtn.vue';

const props = defineProps({ 
    branches: Array,
    managers: Array // Assuming you pass a list of users eligible to be managers
});

// View Toggle State
const viewMode = ref('grid'); // 'grid' or 'list'

// Modal & CRUD State
const isModalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    name: '',
    location: '',
    manager_id: '',
    is_active: true,
});

// Smart UI: Color code the branch health (adapted for dark mode)
const getHealthColor = (rate) => {
    if (rate > 90) return 'text-green-700 bg-green-50 border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20';
    if (rate > 75) return 'text-yellow-700 bg-yellow-50 border-yellow-200 dark:bg-yellow-500/10 dark:text-yellow-400 dark:border-yellow-500/20';
    return 'text-red-700 bg-red-50 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20';
};

// Actions
const openModal = (branch = null) => {
    if (branch) {
        isEditing.value = true;
        form.id = branch.id;
        form.name = branch.name;
        form.location = branch.location;
        form.manager_id = branch.manager_id || '';
        form.is_active = branch.is_active;
    } else {
        isEditing.value = false;
        form.reset();
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 200); // Wait for transition to finish
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.branches.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.branches.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};


// Delete Modal State
const isDeleteModalOpen = ref(false);
const branchToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (branch) => {
    branchToDelete.value = branch;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!branchToDelete.value) return;
    
    isDeleting.value = true;
    router.delete(route('admin.branches.destroy', branchToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            isDeleting.value = false;
            branchToDelete.value = null;
        },
        onError: () => {
            isDeleting.value = false;
            // The global toast will catch the error from the controller
        }
    });
};
</script>

<template>
    <Head title="Branch Management" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
            <PageHeader :title="$t('branches')" :description="$t('branch_desc')">
                <ViewModeToggle v-model="viewMode" />
                <PrimaryActionBtn @click="openModal()" class="flex-1 sm:flex-none">
                    {{ $t('add_branch') }}
                </PrimaryActionBtn>
            </PageHeader>

            <GridContainer v-if="viewMode === 'grid'">
                <GridCard v-for="branch in branches" :key="branch.id">
                    
                    <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                        <RowActions
                            :edit-label="$t('edit')"
                            :delete-label="$t('delete')"
                            @edit="openModal(branch)"
                            @delete="confirmDelete(branch)"
                        />
                    </div>

                    <div class="p-6">
                        <div class="flex justify-between items-start">
                            <div class="flex items-center space-x-3 pr-16">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-100 dark:border-blue-800/50">
                                    <BuildingOfficeIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ branch.name }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ branch.location }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 mb-2">
                             <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border inline-block', getHealthColor(branch.compliance_rate)]">
                                {{ branch.compliance_rate }}% {{ $t('health') }}
                            </span>
                        </div>
                        
                        <div class="mt-6 grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 dark:bg-slate-700/50 p-3 rounded-lg border border-gray-100 dark:border-slate-600">
                                <div class="flex items-center text-gray-500 dark:text-slate-400 mb-1">
                                    <UserGroupIcon class="h-4 w-4 mr-1.5" />
                                    <span class="text-xs font-medium">{{ $t('staff') }}</span>
                                </div>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ branch.staff_count || 0 }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-slate-700/50 p-3 rounded-lg border border-gray-100 dark:border-slate-600">
                                <div class="flex items-center text-gray-500 dark:text-slate-400 mb-1">
                                    <BriefcaseIcon class="h-4 w-4 mr-1.5" />
                                    <span class="text-xs font-medium">{{ $t('customers') }}</span>
                                </div>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ branch.clients_count || 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/80 px-6 py-3 border-t border-gray-100 dark:border-slate-700 flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-slate-400">{{ $t('account_manager') }}: <strong class="text-gray-900 dark:text-white">{{ branch.manager?.name || $t('unassigned') }}</strong></span>
                        <span class="flex items-center gap-1 text-xs">
                            <span class="relative flex h-2 w-2">
                              <span v-if="branch.is_active" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                              <span :class="['relative inline-flex rounded-full h-2 w-2', branch.is_active ? 'bg-green-500' : 'bg-gray-400']"></span>
                            </span>
                            {{ branch.is_active ? $t('active') : $t('incomplete') }}
                        </span>
                    </div>
                </GridCard>
                <div v-if="branches.length === 0" class="col-span-full py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                    {{ $t('no_results_found') }}
                </div>
            </GridContainer>

            <div v-else>
                <Table>
                    <TableHead>
                        <tr>
                            <TableHeaderCell class="pl-4 sm:pl-6">{{ $t('branch') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('account_manager') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('staff') }} / {{ $t('customers') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('health') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('status') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('action') }}</TableHeaderCell>
                        </tr>
                    </TableHead>
                    <TableBody>
                        <TableRow v-for="branch in branches" :key="branch.id">
                            <TableDataCell class="pl-4 sm:pl-6">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex items-center justify-center border border-blue-100 dark:border-blue-800/50">
                                            <BuildingOfficeIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div class="ml-4">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ branch.name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-slate-400">{{ branch.location }}</div>
                                        </div>
                                    </div>
                            </TableDataCell>
                            <TableDataCell>
                                {{ branch.manager?.name || $t('unassigned') }}
                            </TableDataCell>
                            <TableDataCell>
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center"><UserGroupIcon class="h-4 w-4 mr-1"/> {{ branch.staff_count || 0 }}</span>
                                    <span class="flex items-center"><BriefcaseIcon class="h-4 w-4 mr-1"/> {{ branch.clients_count || 0 }}</span>
                                </div>
                            </TableDataCell>
                            <TableDataCell>
                                <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border inline-block', getHealthColor(branch.compliance_rate)]">
                                    {{ branch.compliance_rate }}%
                                </span>
                            </TableDataCell>
                            <TableDataCell>
                                <span :class="['inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset', branch.is_active ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20' : 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-500/20']">
                                    {{ branch.is_active ? $t('active') : $t('incomplete') }}
                                </span>
                            </TableDataCell>
                            <TableDataCell class="pr-4 sm:pr-6">
                                <RowActions
                                    :edit-label="$t('edit')"
                                    :delete-label="$t('delete')"
                                    @edit="openModal(branch)"
                                    @delete="confirmDelete(branch)"
                                />
                            </TableDataCell>
                        </TableRow>
                        <tr v-if="branches.length === 0">
                            <td colspan="6" class="py-12 text-center text-sm text-gray-500 dark:text-slate-400">No branches found.</td>
                        </tr>
                    </TableBody>
                </Table>
            </div>
        </div>

        <ModalWrapper
            :show="isModalOpen"
            :title="isEditing ? 'Edit Branch' : 'Create New Branch'"
            max-width="sm:max-w-md"
            @close="closeModal"
        >
            <template #close-btn>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 transition-colors">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </template>

            <form id="branch-form" @submit.prevent="submitForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Branch Name</label>
                    <input v-model="form.name" type="text" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-colors"
                        placeholder="e.g., Main HQ" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Location</label>
                    <input v-model="form.location" type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-colors"
                        placeholder="e.g., Hawassa, Addis Ababa" />
                    <p v-if="form.errors.location" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.location }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Assign Manager</label>
                    <select v-model="form.manager_id" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-colors">
                        <option value="">-- Unassigned --</option>
                        <option v-for="manager in managers" :key="manager.id" :value="manager.id">{{ manager.name }}</option>
                    </select>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <span class="flex flex-col">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Active Status</span>
                        <span class="text-xs text-gray-500 dark:text-slate-400">Can employees log into this branch?</span>
                    </span>
                    <button type="button" @click="form.is_active = !form.is_active"
                        :class="[form.is_active ? 'bg-blue-600' : 'bg-gray-200 dark:bg-slate-700', 'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:focus:ring-offset-slate-900']">
                        <span :class="[form.is_active ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                    </button>
                </div>
            </form>

            <template #footer>
                <CancelBtn type="button" @click="closeModal">{{ $t('cancel') }}</CancelBtn>
                <SubmitBtn type="submit" form="branch-form" :disabled="form.processing">
                    {{ form.processing ? 'Saving…' : (isEditing ? 'Update Branch' : 'Create Branch') }}
                </SubmitBtn>
            </template>
        </ModalWrapper>
        <DeleteConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Branch"
            :isDeleting="isDeleting"
            @close="isDeleteModalOpen = false"
            @confirm="executeDelete"
        >
            Are you sure you want to delete <strong class="text-gray-900 dark:text-white">{{ branchToDelete?.name }}</strong>? This action cannot be undone.
        </DeleteConfirmationModal>
    </AdminLayout>
</template>