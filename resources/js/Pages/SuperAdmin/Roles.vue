<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ShieldCheckIcon, PlusIcon, XMarkIcon, CheckCircleIcon, KeyIcon, UserCircleIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';
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

const { t } = useI18n();
const page = usePage();

const props = defineProps({ roles: Array, permissions: Array, users: Array });

const activeTab = ref('Roles');
const tabOptions = computed(() => [
    { name: 'Roles', label: t('roles'), count: props.roles.length },
    { name: 'Permissions', label: t('permissions'), count: props.permissions.length },
    { name: 'Users', label: t('users'), count: props.users.length },
]);

// ── Role CRUD ────────────────────────────────────────────────────────────────
const isRoleModalOpen = ref(false);
const isEditingRole = ref(false);
const roleForm = useForm({ id: null, name: '', permissions: [] });

const openRoleModal = (role = null) => {
    if (role) {
        isEditingRole.value = true;
        roleForm.id = role.id;
        roleForm.name = role.name;
        roleForm.permissions = role.permissions.map(p => p.name);
    } else {
        isEditingRole.value = false;
        roleForm.reset();
        roleForm.permissions = [];
    }
    isRoleModalOpen.value = true;
};

const closeRoleModal = () => { isRoleModalOpen.value = false; setTimeout(() => roleForm.reset(), 200); };

const submitRoleForm = () => {
    if (isEditingRole.value) {
        roleForm.put(route('super-admin.roles.update', roleForm.id), { preserveScroll: true, onSuccess: closeRoleModal });
    } else {
        roleForm.post(route('super-admin.roles.store'), { preserveScroll: true, onSuccess: closeRoleModal });
    }
};

const togglePermission = (name) => {
    const i = roleForm.permissions.indexOf(name);
    i > -1 ? roleForm.permissions.splice(i, 1) : roleForm.permissions.push(name);
};

const deleteRoleModal = ref(false);
const roleToDelete = ref(null);
const isDeletingRole = ref(false);
const confirmDeleteRole = (role) => { roleToDelete.value = role; deleteRoleModal.value = true; };
const executeDeleteRole = () => {
    isDeletingRole.value = true;
    roleForm.delete(route('super-admin.roles.destroy', roleToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteRoleModal.value = false; isDeletingRole.value = false; roleToDelete.value = null; },
        onFinish: () => { isDeletingRole.value = false; },
    });
};

// ── Permission CRUD ──────────────────────────────────────────────────────────
const isPermModalOpen = ref(false);
const isEditingPerm = ref(false);
const permForm = useForm({ id: null, name: '' });

const openPermModal = (perm = null) => {
    if (perm) {
        isEditingPerm.value = true;
        permForm.id = perm.id;
        permForm.name = perm.name;
    } else {
        isEditingPerm.value = false;
        permForm.reset();
    }
    isPermModalOpen.value = true;
};

const closePermModal = () => { isPermModalOpen.value = false; setTimeout(() => permForm.reset(), 200); };

const submitPermForm = () => {
    if (isEditingPerm.value) {
        permForm.put(route('super-admin.permissions.update', permForm.id), { preserveScroll: true, onSuccess: closePermModal });
    } else {
        permForm.post(route('super-admin.permissions.store'), { preserveScroll: true, onSuccess: closePermModal });
    }
};

const deletePermModal = ref(false);
const permToDelete = ref(null);
const isDeletingPerm = ref(false);
const confirmDeletePerm = (perm) => { permToDelete.value = perm; deletePermModal.value = true; };
const executeDeletePerm = () => {
    isDeletingPerm.value = true;
    permForm.delete(route('super-admin.permissions.destroy', permToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { deletePermModal.value = false; isDeletingPerm.value = false; permToDelete.value = null; },
        onFinish: () => { isDeletingPerm.value = false; },
    });
};

// ── User Role Management ─────────────────────────────────────────────────────
const isUserModalOpen = ref(false);
const userForm = useForm({ id: null, name: '', roles: [] });

const openUserModal = (user) => {
    userForm.id = user.id;
    userForm.name = user.name;
    userForm.roles = user.roles.map(r => r.name);
    isUserModalOpen.value = true;
};

const closeUserModal = () => { isUserModalOpen.value = false; setTimeout(() => userForm.reset(), 200); };

const submitUserForm = () => {
    userForm.put(route('super-admin.users.roles', userForm.id), { preserveScroll: true, onSuccess: closeUserModal });
};

const toggleUserRole = (name) => {
    const i = userForm.roles.indexOf(name);
    i > -1 ? userForm.roles.splice(i, 1) : userForm.roles.push(name);
};

const deleteUserModal = ref(false);
const userToDelete = ref(null);
const isDeletingUser = ref(false);
const confirmDeleteUser = (user) => { userToDelete.value = user; deleteUserModal.value = true; };
const executeDeleteUser = () => {
    isDeletingUser.value = true;
    userForm.delete(route('super-admin.users.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteUserModal.value = false; isDeletingUser.value = false; userToDelete.value = null; },
        onFinish: () => { isDeletingUser.value = false; },
    });
};

const currentUserId = computed(() => page.props.auth?.user?.id);
</script>

<template>
    <Head :title="$t('role_management')" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 min-h-screen bg-gray-50 dark:bg-slate-900">

            <PageHeader :title="$t('role_management')" :description="$t('manage_roles')">
                <PrimaryActionBtn v-if="activeTab === 'Roles'" @click="openRoleModal()">
                    <PlusIcon class="h-4 w-4 mr-1.5" /> {{ $t('add_role') }}
                </PrimaryActionBtn>
                <PrimaryActionBtn v-else-if="activeTab === 'Permissions'" @click="openPermModal()">
                    <PlusIcon class="h-4 w-4 mr-1.5" /> Add Permission
                </PrimaryActionBtn>
            </PageHeader>

            <TabGroup v-model="activeTab" :tabs="tabOptions" />

            <div class="mt-8">

                <!-- ── ROLES TAB ── -->
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
                                    <div class="p-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/30">
                                        <ShieldCheckIcon class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    {{ role.name }}
                                </div>
                            </TableDataCell>
                            <TableDataCell>
                                <div class="flex flex-wrap gap-1.5 max-w-2xl">
                                    <span v-for="perm in role.permissions" :key="perm.id"
                                        class="inline-flex items-center rounded-md bg-slate-100 dark:bg-slate-800/50 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 uppercase tracking-tighter">
                                        {{ perm.name.replace(/-/g, ' ') }}
                                    </span>
                                    <span v-if="role.permissions.length === 0" class="text-xs text-slate-400 italic">No permissions</span>
                                </div>
                            </TableDataCell>
                            <TableDataCell class="text-right pr-4 sm:pr-6">
                                <RowActions v-if="role.name !== 'Super Admin'" @edit="openRoleModal(role)" @delete="confirmDeleteRole(role)" />
                                <span v-else class="text-[10px] font-black text-slate-400 uppercase bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-md border border-slate-200 dark:border-slate-700">READ ONLY</span>
                            </TableDataCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- ── PERMISSIONS TAB ── -->
                <div v-else-if="activeTab === 'Permissions'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div v-for="perm in permissions" :key="perm.id"
                        class="group relative bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm hover:border-blue-400 dark:hover:border-blue-600 hover:shadow-md transition-all duration-200">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="p-2 bg-violet-50 dark:bg-violet-900/30 rounded-xl shrink-0">
                                    <KeyIcon class="h-4 w-4 text-violet-600 dark:text-violet-400" />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900 dark:text-white capitalize text-sm truncate">{{ perm.name.replace(/-/g, ' ') }}</p>
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">
                                        {{ perm.roles_count }} role{{ perm.roles_count !== 1 ? 's' : '' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                <button @click="openPermModal(perm)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors"
                                    title="Edit">
                                    <PencilSquareIcon class="h-4 w-4" />
                                </button>
                                <button @click="confirmDeletePerm(perm)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                    :class="perm.roles_count > 0 ? 'opacity-40 cursor-not-allowed' : ''"
                                    :title="perm.roles_count > 0 ? 'Assigned to roles — remove first' : 'Delete'">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                            <code class="text-[10px] font-mono text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-900/50 px-2 py-0.5 rounded">{{ perm.name }}</code>
                            <span class="text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded"
                                :class="perm.roles_count > 0 ? 'text-green-600 bg-green-50 dark:bg-green-900/20' : 'text-slate-400 bg-slate-100 dark:bg-slate-900/50'">
                                {{ perm.roles_count > 0 ? 'Active' : 'Unused' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ── USERS TAB ── -->
                <Table v-else-if="activeTab === 'Users'">
                    <TableHead>
                        <tr>
                            <TableHeaderCell class="pl-4 sm:pl-6">{{ $t('full_name') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('email_id') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('roles') }}</TableHeaderCell>
                            <TableHeaderCell class="text-right pr-4 sm:pr-6">{{ $t('action') }}</TableHeaderCell>
                        </tr>
                    </TableHead>
                    <TableBody>
                        <TableRow v-for="user in users" :key="user.id">
                            <TableDataCell class="pl-4 sm:pl-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-white text-xs font-black shrink-0">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-sm">{{ user.name }}</p>
                                        <p v-if="user.id === currentUserId" class="text-[10px] text-indigo-500 font-bold uppercase tracking-wider">You</p>
                                    </div>
                                </div>
                            </TableDataCell>
                            <TableDataCell class="text-slate-500 dark:text-slate-400 text-sm">{{ user.email }}</TableDataCell>
                            <TableDataCell>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="role in user.roles" :key="role.id"
                                        class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-[10px] font-bold rounded uppercase tracking-tighter ring-1 ring-inset ring-blue-600/20">
                                        {{ role.name }}
                                    </span>
                                    <span v-if="!user.roles.length" class="text-xs text-slate-400 italic">No roles</span>
                                </div>
                            </TableDataCell>
                            <TableDataCell class="text-right pr-4 sm:pr-6">
                                <RowActions @edit="openUserModal(user)" @delete="confirmDeleteUser(user)" />
                            </TableDataCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- ══ ROLE MODAL ══════════════════════════════════════════════════════ -->
        <ModalWrapper :show="isRoleModalOpen" :title="isEditingRole ? $t('edit_role') : $t('add_role')" max-width="sm:max-w-3xl" @close="closeRoleModal">
            <template #close-btn>
                <button @click="closeRoleModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 transition-colors">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </template>
            <form id="role-form" @submit.prevent="submitRoleForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('role_name') }}</label>
                    <input v-model="roleForm.name" type="text" required
                        :disabled="isEditingRole && roleForm.name === 'Super Admin'"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm disabled:opacity-50" />
                    <p v-if="roleForm.errors.name" class="mt-1 text-xs text-red-600 font-bold">{{ roleForm.errors.name }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-3 flex items-center justify-between">
                        {{ $t('assigned_permissions') }}
                        <span class="text-[10px] font-black text-blue-600 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded ring-1 ring-blue-600/20">{{ roleForm.permissions.length }} Selected</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                        <div v-for="perm in permissions" :key="perm.id"
                            @click="togglePermission(perm.name)"
                            class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                            :class="roleForm.permissions.includes(perm.name)
                                ? 'bg-blue-50 dark:bg-blue-600/10 border-blue-300 dark:border-blue-600 ring-1 ring-blue-600/20'
                                : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-blue-300'">
                            <div class="h-4 w-4 rounded border flex items-center justify-center shrink-0 transition-colors"
                                :class="roleForm.permissions.includes(perm.name) ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-600'">
                                <CheckCircleIcon v-if="roleForm.permissions.includes(perm.name)" class="h-3 w-3" />
                            </div>
                            <span class="text-sm font-medium text-slate-900 dark:text-white capitalize">{{ perm.name.replace(/-/g, ' ') }}</span>
                        </div>
                    </div>
                </div>
            </form>
            <template #footer>
                <CancelBtn @click="closeRoleModal">{{ $t('cancel') }}</CancelBtn>
                <SubmitBtn form="role-form" :disabled="roleForm.processing">
                    {{ roleForm.processing ? '...' : (isEditingRole ? 'Update Role' : 'Create Role') }}
                </SubmitBtn>
            </template>
        </ModalWrapper>

        <!-- ══ PERMISSION MODAL ════════════════════════════════════════════════ -->
        <ModalWrapper :show="isPermModalOpen" :title="isEditingPerm ? 'Edit Permission' : 'Add Permission'" max-width="sm:max-w-md" @close="closePermModal">
            <template #close-btn>
                <button @click="closePermModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 transition-colors"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <form id="perm-form" @submit.prevent="submitPermForm" class="space-y-5">
                <div class="flex items-center gap-3 p-4 rounded-xl bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800">
                    <KeyIcon class="h-6 w-6 text-violet-600 dark:text-violet-400 shrink-0" />
                    <div>
                        <p class="text-sm font-bold text-violet-900 dark:text-violet-300">Permission Key</p>
                        <p class="text-xs text-violet-600 dark:text-violet-400 mt-0.5">Use lowercase letters and hyphens only. Example: <code class="font-mono">view-reports</code></p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Permission Name</label>
                    <input v-model="permForm.name" type="text" required placeholder="e.g. view-reports"
                        class="w-full font-mono rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm" />
                    <p v-if="permForm.errors.name" class="mt-1 text-xs text-red-600 font-bold">{{ permForm.errors.name }}</p>
                </div>
            </form>
            <template #footer>
                <CancelBtn @click="closePermModal">{{ $t('cancel') }}</CancelBtn>
                <SubmitBtn form="perm-form" :disabled="permForm.processing" class="bg-violet-600 hover:bg-violet-700">
                    {{ permForm.processing ? '...' : (isEditingPerm ? 'Rename Permission' : 'Create Permission') }}
                </SubmitBtn>
            </template>
        </ModalWrapper>

        <!-- ══ USER ROLES MODAL ════════════════════════════════════════════════ -->
        <ModalWrapper :show="isUserModalOpen" :title="`Edit Roles — ${userForm.name}`" max-width="sm:max-w-xl" @close="closeUserModal">
            <template #close-btn>
                <button @click="closeUserModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 transition-colors"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <form id="user-form" @submit.prevent="submitUserForm" class="space-y-5">
                <div class="flex items-center gap-3 p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800">
                    <UserCircleIcon class="h-6 w-6 text-indigo-600 dark:text-indigo-400 shrink-0" />
                    <div>
                        <p class="text-sm font-bold text-indigo-900 dark:text-indigo-300">{{ userForm.name }}</p>
                        <p class="text-xs text-indigo-500 mt-0.5">{{ userForm.roles.length }} role{{ userForm.roles.length !== 1 ? 's' : '' }} currently assigned</p>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">Assign Roles</h3>
                    <div class="space-y-2">
                        <div v-for="role in roles" :key="role.id"
                            @click="toggleUserRole(role.name)"
                            class="flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all"
                            :class="userForm.roles.includes(role.name)
                                ? 'bg-indigo-50 dark:bg-indigo-600/10 border-indigo-300 dark:border-indigo-600 ring-1 ring-indigo-600/20'
                                : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-indigo-300'">
                            <div class="flex items-center gap-3">
                                <div class="p-1.5 rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                                    <ShieldCheckIcon class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ role.name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ role.permissions.length }} permissions</p>
                                </div>
                            </div>
                            <div class="h-5 w-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                :class="userForm.roles.includes(role.name) ? 'bg-indigo-600 border-indigo-600' : 'border-slate-300 dark:border-slate-600'">
                                <div v-if="userForm.roles.includes(role.name)" class="w-2 h-2 rounded-full bg-white" />
                            </div>
                        </div>
                    </div>
                    <p v-if="userForm.errors.roles" class="mt-2 text-xs text-red-600 font-bold">{{ userForm.errors.roles }}</p>
                </div>
            </form>
            <template #footer>
                <CancelBtn @click="closeUserModal">{{ $t('cancel') }}</CancelBtn>
                <SubmitBtn form="user-form" :disabled="userForm.processing">
                    {{ userForm.processing ? '...' : $t('save_role_assignment') }}
                </SubmitBtn>
            </template>
        </ModalWrapper>

        <!-- ══ DELETE MODALS ══════════════════════════════════════════════════ -->
        <DeleteConfirmationModal :show="deleteRoleModal" :title="`Delete Role`" :isDeleting="isDeletingRole"
            @close="deleteRoleModal = false" @confirm="executeDeleteRole">
            Delete role <strong class="text-slate-900 dark:text-white">{{ roleToDelete?.name }}</strong>? Users assigned to this role will lose its permissions immediately.
        </DeleteConfirmationModal>

        <DeleteConfirmationModal :show="deletePermModal" :title="`Delete Permission`" :isDeleting="isDeletingPerm"
            @close="deletePermModal = false" @confirm="executeDeletePerm">
            <template v-if="permToDelete?.roles_count > 0">
                <span class="text-red-600 font-bold">⚠ Cannot delete</span> — <strong>{{ permToDelete?.name }}</strong> is still assigned to {{ permToDelete?.roles_count }} role(s). Remove it from those roles first.
            </template>
            <template v-else>
                Permanently delete permission <strong class="font-mono text-slate-900 dark:text-white">{{ permToDelete?.name }}</strong>? This cannot be undone.
            </template>
        </DeleteConfirmationModal>

        <DeleteConfirmationModal :show="deleteUserModal" :title="`Remove User`" :isDeleting="isDeletingUser"
            @close="deleteUserModal = false" @confirm="executeDeleteUser">
            Remove <strong class="text-slate-900 dark:text-white">{{ userToDelete?.name }}</strong> from the system? All their data and role assignments will be permanently deleted.
        </DeleteConfirmationModal>

    </AdminLayout>
</template>
