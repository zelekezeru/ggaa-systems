<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
    ShieldCheckIcon, 
    PlusIcon, 
    XMarkIcon, 
    CheckCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

import PageHeader from '@/Components/PageHeader.vue';
import PrimaryActionBtn from '@/Components/PrimaryActionBtn.vue';
import ModalWrapper from '@/Components/ModalWrapper.vue';
import CancelBtn from '@/Components/CancelBtn.vue';
import SubmitBtn from '@/Components/SubmitBtn.vue';
import RowActions from '@/Components/RowActions.vue';
import DeleteConfirmationModal from '@/Components/DeleteConfirmationModal.vue';
import Table from '@/Components/Table.vue';
import TableHead from '@/Components/TableHead.vue';
import TableBody from '@/Components/TableBody.vue';
import TableRow from '@/Components/TableRow.vue';
import TableHeaderCell from '@/Components/TableHeaderCell.vue';
import TableDataCell from '@/Components/TableDataCell.vue';
import TabGroup from '@/Components/TabGroup.vue';

const props = defineProps({
    roles: Array,
    permissions: Array,
    users: Array
});

const isModalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    name: '',
    permissions: []
});

const openModal = (role = null) => {
    if (role) {
        isEditing.value = true;
        form.id = role.id;
        form.name = role.name;
        form.permissions = role.permissions.map(p => p.name);
    } else {
        isEditing.value = false;
        form.reset();
        form.permissions = [];
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 200);
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('super-admin.roles.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('super-admin.roles.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

// Delete logic
const isDeleteModalOpen = ref(false);
const roleToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (role) => {
    roleToDelete.value = role;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!roleToDelete.value) return;
    isDeleting.value = true;
    form.delete(route('super-admin.roles.destroy', roleToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            isDeleting.value = false;
            roleToDelete.value = null;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
};

const togglePermission = (name) => {
    const index = form.permissions.indexOf(name);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(name);
    }
};

// Filtering State
const activeTab = ref('Roles');
const tabOptions = computed(() => [
    { name: 'Roles', label: t('roles'), count: props.roles.length },
    { name: 'Permissions', label: t('permissions'), count: props.permissions.length },
    { name: 'Users', label: t('users'), count: props.users.length }
]);

</script>

<template>
    <Head :title="$t('role_management')" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
            <PageHeader :title="$t('role_management')" :description="$t('manage_roles')">
                <PrimaryActionBtn @click="openModal()" class="flex-1 sm:flex-none">
                    {{ $t('add_role') }}
                </PrimaryActionBtn>
            </PageHeader>

            <TabGroup 
                v-model="activeTab" 
                :tabs="tabOptions" 
            />

            <div class="mt-8">
                <Table v-if="activeTab === 'Roles'">
                    <TableHead>
                        <tr>
                            <TableHeaderCell class="pl-4 sm:pl-6">{{ $t('role_name') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('permissions') }}</TableHeaderCell>
                            <TableHeaderCell class="text-right pr-4 sm:pr-6">{{ $t('action') }}</TableHeaderCell>
                        </tr>
                    </TableHead>
                    <TableBody>
                        <TableRow v-for="role in roles" :key="role.id">
                            <TableDataCell class="pl-4 sm:pl-6 font-bold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-3">
                                    <ShieldCheckIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    {{ role.name }}
                                </div>
                            </TableDataCell>
                            <TableDataCell>
                                <div class="flex flex-wrap gap-1.5 max-w-2xl">
                                    <span 
                                        v-for="perm in role.permissions" 
                                        :key="perm.id"
                                        class="inline-flex items-center rounded-md bg-slate-100 dark:bg-slate-800/50 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 uppercase tracking-tighter"
                                    >
                                        {{ perm.name.replace(/-/g, ' ') }}
                                    </span>
                                    <span v-if="role.permissions.length === 0" class="text-xs text-slate-400 italic">No permissions assigned</span>
                                </div>
                            </TableDataCell>
                            <TableDataCell class="text-right pr-4 sm:pr-6">
                                <RowActions 
                                    v-if="role.name !== 'Super Admin'"
                                    @edit="openModal(role)"
                                    @delete="confirmDelete(role)"
                                />
                                <span v-else class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest bg-slate-100 dark:bg-slate-800 inline-block px-2 py-1 rounded-md border border-slate-200 dark:border-slate-700">READ ONLY</span>
                            </TableDataCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <div v-else-if="activeTab === 'Permissions'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="perm in permissions" :key="perm.id" class="relative group bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm hover:border-blue-500 dark:hover:border-blue-600 transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                                    <CheckCircleIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="font-bold text-slate-900 dark:text-white capitalize">{{ perm.name.replace(/-/g, ' ') }}</span>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-[10px] font-black uppercase tracking-widest text-slate-400">
                            <span>System Scope</span>
                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-900 rounded">Active</span>
                        </div>
                    </div>
                </div>

                <Table v-else-if="activeTab === 'Users'">
                    <TableHead>
                        <tr>
                            <TableHeaderCell class="pl-4 sm:pl-6">{{ $t('full_name') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('email_id') }}</TableHeaderCell>
                            <TableHeaderCell>{{ t('roles') }}</TableHeaderCell>
                        </tr>
                    </TableHead>
                    <TableBody>
                        <TableRow v-for="user in users" :key="user.id">
                            <TableDataCell class="pl-4 sm:pl-6 font-bold text-slate-900 dark:text-white">
                                {{ user.name }}
                            </TableDataCell>
                            <TableDataCell class="text-slate-500 dark:text-slate-400">
                                {{ user.email }}
                            </TableDataCell>
                            <TableDataCell>
                                <div class="flex flex-wrap gap-1">
                                    <span 
                                        v-for="role in user.roles" 
                                        :key="role.id"
                                        class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-[10px] font-bold rounded uppercase tracking-tighter ring-1 ring-inset ring-blue-600/20"
                                    >
                                        {{ role.name }}
                                    </span>
                                </div>
                            </TableDataCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Role Modal -->
        <ModalWrapper
            :show="isModalOpen"
            :title="isEditing ? $t('edit_role') : $t('add_role')"
            max-width="sm:max-w-3xl"
            @close="closeModal"
        >
            <template #close-btn>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 transition-colors">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </template>

            <form id="role-form" @submit.prevent="submitForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('role_name') }}</label>
                    <input 
                        v-model="form.name" 
                        type="text" 
                        required 
                        :disabled="isEditing && form.name === 'Super Admin'"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm disabled:opacity-50" 
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.name }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-4 flex items-center justify-between">
                        {{ $t('assigned_permissions') }}
                        <span class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded shadow-sm ring-1 ring-blue-600/20">{{ form.permissions.length }} Selected</span>
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <div 
                            v-for="perm in permissions" 
                            :key="perm.id"
                            @click="togglePermission(perm.name)"
                            class="group relative flex items-start p-3 rounded-xl border cursor-pointer transition-all duration-200"
                            :class="[
                                form.permissions.includes(perm.name) 
                                ? 'bg-blue-50 dark:bg-blue-600/10 border-blue-200 dark:border-blue-600 ring-1 ring-blue-600/10 dark:ring-blue-600/20' 
                                : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-800'
                            ]"
                        >
                            <div class="flex h-5 items-center">
                                <div 
                                    class="h-4 w-4 rounded-md border flex items-center justify-center transition-colors shadow-sm"
                                    :class="[
                                        form.permissions.includes(perm.name) 
                                        ? 'bg-blue-600 border-blue-600 text-white' 
                                        : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-600'
                                    ]"
                                >
                                    <CheckCircleIcon v-if="form.permissions.includes(perm.name)" class="h-3 w-3" />
                                </div>
                            </div>
                            <div class="ml-3 text-sm flex-1">
                                <label class="font-bold text-slate-900 dark:text-white capitalize cursor-pointer group-hover:text-blue-700 dark:group-hover:text-blue-400">
                                    {{ perm.name.replace(/-/g, ' ') }}
                                </label>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-tighter">System Scope</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <CancelBtn @click="closeModal">{{ $t('cancel') }}</CancelBtn>
                <SubmitBtn form="role-form" :disabled="form.processing">
                    {{ form.processing ? '...' : (isEditing ? $t('update_profile') : $t('create_account')) }}
                </SubmitBtn>
            </template>
        </ModalWrapper>

        <DeleteConfirmationModal
            :show="isDeleteModalOpen"
            :title="$t('delete') + ' ' + $t('role_management')"
            :isDeleting="isDeleting"
            @close="isDeleteModalOpen = false"
            @confirm="executeDelete"
        >
            Are you sure you want to delete the role <strong class="text-slate-900 dark:text-white">{{ roleToDelete?.name }}</strong>? Users assigned to this role will lose their permissions immediately.
        </DeleteConfirmationModal>
    </AdminLayout>
</template>
