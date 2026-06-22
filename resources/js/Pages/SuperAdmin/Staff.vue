<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { PlusIcon, XMarkIcon, BriefcaseIcon, TagIcon, EnvelopeIcon } from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
import WarningBtn from '@/Components/WarningBtn.vue';
import SearchableMultiSelect from '@/Components/SearchableMultiSelect.vue';
import TabGroup from '@/Components/TabGroup.vue';

const props = defineProps({
    staff: Array,
    branches: Array,
    serviceTypes: Array,
    positions: Object,
    teams: { type: Object, default: () => ({}) },
});

const viewMode = ref('list');

// Filtering State
const activeTab = ref('All');
const tabOptions = computed(() => [
    { name: 'All', label: t('all'), count: props.staff.length },
    { name: 'Over', label: t('over_capacity'), count: props.staff.filter(s => s.capacity_points >= 25).length },
    { name: 'Available', label: t('available'), count: props.staff.filter(s => s.capacity_points <= 15).length },
    { name: 'Announcements', label: t('announcements') || 'Announcements' }
]);

const filteredStaff = computed(() => {
    if (activeTab.value === 'Over') return props.staff.filter(s => s.capacity_points >= 25);
    if (activeTab.value === 'Available') return props.staff.filter(s => s.capacity_points <= 15);
    return props.staff;
});

// CRUD Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    name: '',
    email: '',
    branch_id: '',
    service_type_ids: [],
    password: '',
    password_confirmation: '',
    profile_photo: null,
    user_type: 'staff',          // 'staff' | 'client'
    position: 'junior_accountant', // staff position (org-chart taxonomy)
    position_title: '',
    team: '',
    employment_type: 'full_time',
    hire_date: '',
    client_id: '',                // for user_type === 'client'
});

const openModal = (employee = null) => {
    if (employee) {
        isEditing.value = true;
        form.id = employee.id;
        form.name = employee.name;
        form.email = employee.email;
        form.branch_id = employee.branch_id || '';
        form.service_type_ids = employee.service_types.map(st => st.id);
        form.password = '';
        form.password_confirmation = '';
        form.user_type = 'staff';
        form.position = employee.staff_profile?.position || 'junior_accountant';
        form.position_title = employee.staff_profile?.position_title || '';
        form.team = employee.staff_profile?.team || '';
        form.employment_type = employee.staff_profile?.employment_type || 'full_time';
        form.hire_date = employee.staff_profile?.hire_date || '';
    } else {
        isEditing.value = false;
        form.reset();
        form.service_type_ids = [];
        form.user_type = 'staff';
        form.position = 'junior_accountant';
        form.team = '';
        form.employment_type = 'full_time';
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 200);
};

const submitForm = () => {
    if (isEditing.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('admin.staff.update', form.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.transform((data) => data).post(route('admin.staff.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

// No longer need manual toggle logic with SearchableMultiSelect
// const toggleSpecialty = (id) => { ... }

const isResetting = ref(false);
const resetPassword = () => {
    isResetting.value = true;
    router.post(route('admin.staff.reset-password', form.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isResetting.value = false;
        },
        onError: () => {
            isResetting.value = false;
        }
    });
};

// Delete Modal State
const isDeleteModalOpen = ref(false);
const employeeToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (employee) => {
    employeeToDelete.value = employee;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!employeeToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('admin.staff.destroy', employeeToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            isDeleting.value = false;
            employeeToDelete.value = null;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
};

const getCapacityColor = (percentage) => {
    if (percentage < 50) return 'bg-green-500';
    if (percentage < 85) return 'bg-yellow-500';
    return 'bg-red-500';
};

// --- Announcements: recipient selection + email ---
const selectedIds = ref([]);

const toggleSelect = (id) => {
    const i = selectedIds.value.indexOf(id);
    if (i === -1) selectedIds.value.push(id);
    else selectedIds.value.splice(i, 1);
};

const allSelected = computed(() =>
    props.staff.length > 0 &&
    props.staff.every(s => selectedIds.value.includes(s.id))
);

const toggleSelectAll = () => {
    selectedIds.value = allSelected.value ? [] : props.staff.map(s => s.id);
};

const emailForm = useForm({
    user_ids: [],
    subject: '',
    message: '',
});

const sendEmail = () => {
    emailForm.user_ids = [...selectedIds.value];
    emailForm.post(route('admin.staff.send-email'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            emailForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Staff Directory" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
            <PageHeader :title="$t('staff')" :description="$t('staff_desc')">
                <ViewModeToggle v-if="activeTab !== 'Announcements'" v-model="viewMode" />
                <PrimaryActionBtn v-if="activeTab !== 'Announcements'" @click="openModal()" class="flex-1 sm:flex-none">
                    {{ $t('onboard_employee') }}
                </PrimaryActionBtn>
            </PageHeader>

            <TabGroup 
                v-model="activeTab" 
                :tabs="tabOptions" 
            />

            <GridContainer v-if="activeTab !== 'Announcements' && viewMode === 'grid'">
                <GridCard v-for="employee in filteredStaff" :key="employee.id" class="p-6">
                    <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                        <RowActions
                            @show="$inertia.get(route('super-admin.staff.show', employee.id))"
                            @edit="openModal(employee)"
                            @delete="confirmDelete(employee)"
                        />
                    </div>

                    <div class="flex items-center space-x-4 pr-16 mb-4">
                        <div class="h-12 w-12 flex-shrink-0 bg-blue-100 dark:bg-blue-900/50 rounded-2xl overflow-hidden flex items-center justify-center text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800 text-lg shadow-sm">
                            <img v-if="employee.profile_photo_url" :src="employee.profile_photo_url" :alt="employee.name" class="h-full w-full object-cover" />
                            <span v-else>{{ employee.name.charAt(0) }}</span>
                        </div>
                        <div>
                            <Link :href="route('super-admin.staff.show', employee.id)" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate">{{ employee.name }}</h3>
                            </Link>
                            <p class="text-[11px] text-gray-500 dark:text-slate-400 truncate uppercase font-bold">{{ employee.email }}</p>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-1.5 mb-4">
                        <span class="inline-flex items-center rounded-lg bg-gray-100 dark:bg-slate-700/50 px-2.5 py-1 text-[10px] font-bold text-gray-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 uppercase tracking-tighter">
                            <BriefcaseIcon class="h-3 w-3 mr-1" />
                            {{ employee.branch?.name || $t('unassigned') }}
                        </span>
                        <span v-for="st in employee.service_types" :key="st.id" class="inline-flex items-center rounded-lg bg-blue-50 dark:bg-blue-900/30 px-2.5 py-1 text-[10px] font-bold text-blue-700 dark:text-blue-400 border border-blue-100 dark:border-blue-800 uppercase tracking-tighter">
                            <TagIcon class="h-3 w-3 mr-1" />
                            {{ st.name }}
                        </span>
                    </div>
                    
                    <div class="mt-6">
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-tight">{{ $t('capacity_load') }}</span>
                            <span :class="['text-xs font-bold leading-none', employee.capacity_points > 25 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white']">
                                {{ employee.capacity_points }} <span class="text-gray-400 font-normal">{{ $t('pts') }}</span>
                            </span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2 shadow-inner">
                            <div class="h-2 rounded-full transition-all duration-500 shadow-sm" 
                                :class="getCapacityColor((employee.capacity_points / 30) * 100)" 
                                :style="{ width: Math.min((employee.capacity_points / 30) * 100, 100) + '%' }">
                            </div>
                        </div>
                    </div>
 
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-700 flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-tight">{{ $t('active_clients') }}</span>
                        <span class="inline-flex items-center justify-center bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 h-6 px-2.5 rounded-full text-xs font-bold ring-1 ring-inset ring-blue-600/20">
                            {{ employee.clients_count }}
                        </span>
                    </div>
                </GridCard>
                <div v-if="filteredStaff.length === 0" class="col-span-full py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                    {{ $t('no_employees') }}
                </div>
            </GridContainer>
 
            <div v-else-if="activeTab !== 'Announcements'">
                <Table>
                    <TableHead>
                        <tr>
                            <TableHeaderCell class="pl-4 sm:pl-6">{{ $t('employee_info') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('service_specialties') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('branch') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('active_clients') }}</TableHeaderCell>
                            <TableHeaderCell class="w-1/4">{{ $t('capacity_load') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('action') }}</TableHeaderCell>
                        </tr>
                    </TableHead>
                    <TableBody>
                        <TableRow v-for="employee in filteredStaff" :key="employee.id">
                            <TableDataCell class="pl-4 sm:pl-6">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 bg-blue-100 dark:bg-blue-900/50 rounded-xl overflow-hidden flex items-center justify-center text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800 shadow-sm">
                                        <img v-if="employee.profile_photo_url" :src="employee.profile_photo_url" :alt="employee.name" class="h-full w-full object-cover" />
                                        <span v-else>{{ employee.name.charAt(0) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <Link :href="route('super-admin.staff.show', employee.id)" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                            <div class="font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ employee.name }}</div>
                                        </Link>
                                        <div class="text-[11px] text-gray-500 dark:text-slate-400 font-bold uppercase tracking-tighter">{{ employee.email }}</div>
                                    </div>
                                </div>
                            </TableDataCell>
                            <TableDataCell>
                                <div class="flex flex-wrap gap-1 max-w-[200px]">
                                    <span v-for="st in employee.service_types" :key="st.id" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-[10px] font-bold uppercase tracking-tighter border border-slate-200/50 dark:border-slate-600/50">
                                        {{ st.name }}
                                    </span>
                                    <span v-if="employee.service_types.length === 0" class="text-[10px] text-slate-400 italic">{{ $t('unassigned') }}</span>
                                </div>
                            </TableDataCell>
                            <TableDataCell>
                                <div class="font-bold text-slate-700 dark:text-slate-300">{{ employee.branch?.name || $t('unassigned') }}</div>
                            </TableDataCell>
                            <TableDataCell>
                                <span class="font-bold text-slate-900 dark:text-white">{{ employee.clients_count || 0 }}</span>
                            </TableDataCell>
                            <TableDataCell>
                                <div class="flex items-center gap-3">
                                    <span class="w-8 font-bold text-slate-700 dark:text-slate-300">{{ employee.capacity_points }}</span>
                                    <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2.5 shadow-inner">
                                        <div 
                                            class="h-2.5 rounded-full transition-all duration-300 shadow-sm" 
                                            :class="getCapacityColor((employee.capacity_points / 30) * 100)"
                                            :style="{ width: Math.min((employee.capacity_points / 30) * 100, 100) + '%' }">
                                        </div>
                                    </div>
                                </div>
                            </TableDataCell>
                            <TableDataCell class="pr-4 sm:pr-6 text-right">
                                <RowActions
                                    @show="$inertia.get(route('super-admin.staff.show', employee.id))"
                                    @edit="openModal(employee)"
                                    @delete="confirmDelete(employee)"
                                />
                            </TableDataCell>
                        </TableRow>
                        <tr v-if="filteredStaff.length === 0">
                            <td colspan="6" class="py-12 text-center text-sm text-gray-500 dark:text-slate-400">{{ $t('no_employees') }}</td>
                        </tr>
                    </TableBody>
                </Table>
            </div>

            <!-- ANNOUNCEMENTS COMPOSER -->
            <div v-else class="max-w-4xl">
                <div class="bg-white dark:bg-slate-800 rounded-2xl ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                            <EnvelopeIcon class="h-5 w-5 text-blue-700 dark:text-blue-400" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $t('send_announcement') || 'Send Announcement' }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $t('announcement_desc') || 'Compose a message and email it directly to the selected staff.' }}</p>
                        </div>
                    </div>

                    <form id="announcement-form" @submit.prevent="sendEmail" class="p-6 space-y-6">
                        <!-- Recipients -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                    {{ $t('recipients') || 'Recipients' }}
                                    <span class="text-blue-600 dark:text-blue-400">({{ selectedIds.length }}/{{ staff.length }})</span>
                                </label>
                                <button type="button" @click="toggleSelectAll" class="text-xs font-bold uppercase tracking-wide text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                    {{ allSelected ? ($t('clear_all') || 'Clear all') : ($t('select_all') || 'Select all') }}
                                </button>
                            </div>
                            <div class="max-h-64 overflow-y-auto rounded-xl ring-1 ring-inset ring-slate-200 dark:ring-slate-700 divide-y divide-slate-100 dark:divide-slate-700/50">
                                <label v-for="employee in staff" :key="employee.id" class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                    <input type="checkbox" :checked="selectedIds.includes(employee.id)" @change="toggleSelect(employee.id)" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                    <div class="h-8 w-8 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-700 dark:text-blue-300 text-xs font-bold flex-shrink-0 overflow-hidden">
                                        <img v-if="employee.profile_photo_url" :src="employee.profile_photo_url" :alt="employee.name" class="h-full w-full object-cover" />
                                        <span v-else>{{ employee.name.charAt(0) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">{{ employee.name }}</div>
                                        <div class="text-[11px] text-slate-400 truncate">{{ employee.email }}</div>
                                    </div>
                                </label>
                                <div v-if="staff.length === 0" class="px-4 py-6 text-center text-sm text-slate-400">{{ $t('no_employees') }}</div>
                            </div>
                            <p v-if="emailForm.errors.user_ids" class="mt-1.5 text-xs text-red-600 font-bold">{{ emailForm.errors.user_ids }}</p>
                        </div>

                        <!-- Subject -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('subject') || 'Subject' }}</label>
                            <input v-model="emailForm.subject" type="text" required maxlength="255"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="emailForm.errors.subject" class="mt-1 text-xs text-red-600 font-bold">{{ emailForm.errors.subject }}</p>
                        </div>

                        <!-- Message -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('message') || 'Message' }}</label>
                            <textarea v-model="emailForm.message" required rows="7" maxlength="5000"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                            <p v-if="emailForm.errors.message" class="mt-1 text-xs text-red-600 font-bold">{{ emailForm.errors.message }}</p>
                        </div>

                        <div class="flex justify-end pt-2 border-t border-slate-100 dark:border-slate-700">
                            <SubmitBtn form="announcement-form" :disabled="emailForm.processing || selectedIds.length === 0">
                                {{ emailForm.processing ? ($t('sending') || 'Sending...') : (($t('send_announcement') || 'Send Announcement')) }}
                            </SubmitBtn>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- STAFF MODAL -->
        <ModalWrapper
            :show="isModalOpen"
            :title="isEditing ? $t('edit_employee') : $t('onboard_employee')"
            max-width="sm:max-w-2xl"
            @close="closeModal"
        >
            <template #close-btn>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 transition-colors">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </template>

            <form id="staff-form" @submit.prevent="submitForm" class="space-y-6">
                <div class="flex items-center space-x-6 mb-6 pb-6 border-b border-slate-100 dark:border-slate-800">
                    <div class="h-20 w-20 rounded-2xl bg-slate-100 dark:bg-slate-800 flex-shrink-0 overflow-hidden border-2 border-dashed border-slate-300 dark:border-slate-700 flex items-center justify-center">
                        <img v-if="form.profile_photo" :src="URL.createObjectURL(form.profile_photo)" class="h-full w-full object-cover" />
                        <img v-else-if="isEditing && staff.find(s => s.id === form.id)?.profile_photo_url" :src="staff.find(s => s.id === form.id).profile_photo_url" class="h-full w-full object-cover" />
                        <span v-else class="text-slate-400 text-2xl font-bold uppercase">{{ form.name ? form.name.charAt(0) : '?' }}</span>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('profile_photo') || 'Profile Photo' }}</label>
                        <input type="file" @input="form.profile_photo = $event.target.files[0]" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/20 dark:file:text-blue-400" />
                        <p class="mt-1 text-[10px] text-slate-400">JPG, PNG or WEBP. Max 2MB.</p>
                    </div>
                </div>

                <!-- User type radio (only on create) -->
                <div v-if="!isEditing" class="mb-5">
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Account type</label>
                    <div class="flex gap-3">
                        <label class="flex-1 flex items-center gap-2 px-4 py-3 rounded-xl ring-1 cursor-pointer transition-all"
                            :class="form.user_type === 'staff' ? 'ring-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'ring-slate-200 dark:ring-slate-700'">
                            <input v-model="form.user_type" type="radio" value="staff" class="text-blue-600" />
                            <div>
                                <div class="text-sm font-semibold">Staff member</div>
                                <div class="text-[11px] text-slate-500">Internal user (employee, manager, finance, etc.)</div>
                            </div>
                        </label>
                        <label class="flex-1 flex items-center gap-2 px-4 py-3 rounded-xl ring-1 cursor-pointer transition-all"
                            :class="form.user_type === 'client' ? 'ring-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'ring-slate-200 dark:ring-slate-700'">
                            <input v-model="form.user_type" type="radio" value="client" class="text-blue-600" />
                            <div>
                                <div class="text-sm font-semibold">Client portal user</div>
                                <div class="text-[11px] text-slate-500">External user with access to one client account</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Staff position fields -->
                <div v-if="form.user_type === 'staff'" class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Staff position</label>
                        <select v-model="form.position" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option v-for="(label, key) in positions" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <p v-if="form.errors.position" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.position }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Title (optional)</label>
                        <input v-model="form.position_title" type="text" placeholder="e.g. Senior Tax Specialist"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Operational team (optional)</label>
                        <select v-model="form.team" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">None</option>
                            <option v-for="(label, key) in teams" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Employment type</label>
                        <select v-model="form.employment_type" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="full_time">Full-time</option>
                            <option value="part_time">Part-time</option>
                            <option value="contract">Contract</option>
                        </select>
                    </div>
                    <div v-if="!isEditing">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Hire date (optional)</label>
                        <input v-model="form.hire_date" type="date" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('full_name') }}</label>
                        <input v-model="form.name" type="text" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('email_id') }}</label>
                        <input v-model="form.email" type="email" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('branch') }}</label>
                        <select v-model="form.branch_id" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-all">
                            <option value="" disabled>{{ $t('all') }}</option>
                            <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                        </select>
                        <p v-if="form.errors.branch_id" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.branch_id }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <SearchableMultiSelect
                            v-model="form.service_type_ids"
                            :options="serviceTypes"
                            :label="$t('service_specialties')"
                            :placeholder="$t('search_placeholder') || 'Search...'"
                        />
                    </div>

                    <template v-if="!isEditing">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('password') }}</label>
                            <input v-model="form.password" type="password" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('confirm_password') }}</label>
                            <input v-model="form.password_confirmation" type="password" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        </div>
                    </template>

                    <div v-else class="sm:col-span-2 pt-4 mt-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between p-4 bg-amber-50 dark:bg-amber-900/10 rounded-2xl ring-1 ring-inset ring-amber-200/50 dark:ring-amber-800/50">
                            <div class="max-w-[70%]">
                                <h4 class="text-sm font-bold text-amber-900 dark:text-amber-400">{{ $t('security') }}</h4>
                                <p class="text-[11px] text-amber-700 dark:text-amber-500/80">{{ $t('security_desc') || 'Manage security settings' }}</p>
                            </div>
                            <WarningBtn type="button" @click="resetPassword" :disabled="isResetting" class="shadow-sm">
                                {{ isResetting ? $t('sending') : $t('reset_password') }}
                            </WarningBtn>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <CancelBtn @click="closeModal">{{ $t('cancel') }}</CancelBtn>
                <SubmitBtn form="staff-form" :disabled="form.processing">
                    {{ form.processing ? '...' : (isEditing ? $t('update_profile') : $t('create_account')) }}
                </SubmitBtn>
            </template>
        </ModalWrapper>

        <DeleteConfirmationModal
            :show="isDeleteModalOpen"
            :title="$t('decommission_staff')"
            :isDeleting="isDeleting"
            @close="isDeleteModalOpen = false"
            @confirm="executeDelete"
        >
            Are you sure you want to delete <strong class="text-slate-900 dark:text-white">{{ employeeToDelete?.name }}</strong>? Access will be terminated immediately.
        </DeleteConfirmationModal>
    </AdminLayout>
</template>