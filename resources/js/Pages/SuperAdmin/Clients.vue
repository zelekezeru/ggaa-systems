<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { PlusIcon, XMarkIcon, BriefcaseIcon, TagIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';

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
import SearchableMultiSelect from '@/Components/SearchableMultiSelect.vue';
import TabGroup from '@/Components/TabGroup.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const props = defineProps({
    clients: Array,
    branches: Array,
    employees: Array,
    serviceTypes: Array,
});

const viewMode = ref('list');

// Filtering State
const activeTab = ref('All');
const tabOptions = computed(() => [
    { name: 'All', label: t('all'), count: props.clients.length },
    { name: 'Active', label: t('active'), count: props.clients.filter(c => c.status === 'Active').length },
    { name: 'Incomplete', label: t('incomplete'), count: props.clients.filter(c => c.status === 'Incomplete').length },
    { name: 'Risk', label: t('risk'), count: props.clients.filter(c => c.status === 'Risk').length }
]);

const selectedServiceType = ref('All');

const filteredClients = computed(() => {
    let list = props.clients;
    
    if (activeTab.value !== 'All') {
        list = list.filter(c => c.status === activeTab.value);
    }
    
    if (selectedServiceType.value !== 'All') {
        list = list.filter(c => c.service_types.some(st => st.slug === selectedServiceType.value));
    }
    
    return list;
});

// CRUD Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    company_name: '',
    tin_number: '',
    sector: '',
    service_type_ids: [],
    branch_id: '',
    assigned_employee_id: '',
    complexity_score: 1,
    status: 'Incomplete',
    logo: null,
});

const openModal = (client = null) => {
    if (client) {
        isEditing.value = true;
        form.id = client.id;
        form.company_name = client.company_name;
        form.tin_number = client.tin_number;
        form.sector = client.sector;
        form.service_type_ids = client.service_types.map(st => st.id);
        form.branch_id = client.branch_id;
        form.assigned_employee_id = client.assigned_employee_id || '';
        form.complexity_score = client.complexity_score;
        form.status = client.status;
    } else {
        isEditing.value = false;
        form.reset();
        form.service_type_ids = [];
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 200);
};

const submitForm = () => {
    if (isEditing.value) {
        form.post(route('admin.clients.update', form.id), {
            forceFormData: true,
            onBefore: () => form.setData('_method', 'put'),
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.clients.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

// No longer need manual toggle logic with SearchableMultiSelect
// const toggleServiceType = (id) => { ... }

// Delete Modal State
const isDeleteModalOpen = ref(false);
const clientToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (client) => {
    clientToDelete.value = client;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!clientToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('admin.clients.destroy', clientToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            isDeleting.value = false;
            clientToDelete.value = null;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
};

const getStatusColor = (status) => {
    switch (status) {
        case 'Active': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800';
        case 'Risk': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800';
        default: return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border-amber-200 dark:border-amber-800';
    }
};
</script>

<template>
    <Head title="Clients Directory" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
            <PageHeader :title="$t('customers')" :description="$t('clients_desc')">
                <ViewModeToggle v-model="viewMode" />
                <PrimaryActionBtn @click="openModal()" class="flex-1 sm:flex-none">
                    {{ $t('onboard_client') }}
                </PrimaryActionBtn>
            </PageHeader>

            <div class="mt-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-slate-700 pb-px mb-6 overflow-x-auto custom-scrollbar">
                <TabGroup 
                    v-model="activeTab" 
                    :tabs="tabOptions" 
                    no-border
                />

                <div class="flex items-center gap-2 pb-2 sm:pb-0">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ $t('filter_by_service') }}:</span>
                    <select v-model="selectedServiceType" class="text-sm rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 py-1.5 focus:ring-blue-500">
                        <option value="All">{{ $t('all') }}</option>
                        <option v-for="st in serviceTypes" :key="st.id" :value="st.slug">{{ st.name }}</option>
                    </select>
                </div>
            </div>

            <!-- GRID VIEW -->
            <GridContainer v-if="viewMode === 'grid'">
                <GridCard v-for="client in filteredClients" :key="client.id" class="p-6">
                    <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                        <RowActions
                            @edit="openModal(client)"
                            @delete="confirmDelete(client)"
                        />
                    </div>

                    <div class="flex items-center space-x-4 pr-16 mb-4">
                        <div class="h-12 w-12 flex-shrink-0 bg-blue-100 dark:bg-blue-900/50 rounded-2xl overflow-hidden flex items-center justify-center text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800 text-lg shadow-sm">
                            <img v-if="client.logo_url" :src="client.logo_url" :alt="client.company_name" class="h-full w-full object-cover" />
                            <span v-else>{{ client.company_name.charAt(0) }}</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate">{{ client.company_name }}</h3>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-tighter">TIN: {{ client.tin_number }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-1.5 mb-4">
                        <span :class="['inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border', getStatusColor(client.status)]">
                            {{ client.status }}
                        </span>
                        <span v-for="st in client.service_types" :key="st.id" class="inline-flex items-center px-2 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 text-[10px] font-bold uppercase border border-blue-100 dark:border-blue-800">
                            <TagIcon class="h-3 w-3 mr-1" />
                            {{ st.name }}
                        </span>
                    </div>

                    <div class="space-y-2 mt-4 pt-4 border-t border-gray-100 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-tight">{{ $t('branch') }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ client.branch?.name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-tight">{{ $t('assignee') }}</span>
                            <div class="flex items-center gap-2">
                                <div v-if="client.assigned_employee" class="h-5 w-5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden flex items-center justify-center text-[10px] font-bold">
                                    <img v-if="client.assigned_employee.profile_photo_url" :src="client.assigned_employee.profile_photo_url" class="h-full w-full object-cover" />
                                    <span v-else>{{ client.assigned_employee.name.charAt(0) }}</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ client.assigned_employee?.name || $t('unassigned') }}</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-tight">{{ $t('complexity_score') }}</span>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ client.complexity_score }}<span class="text-slate-400 font-normal">/10</span></span>
                        </div>
                    </div>
                </GridCard>
                <div v-if="filteredClients.length === 0" class="col-span-full py-12 text-center text-sm text-gray-500 dark:text-slate-400 bg-white dark:bg-slate-800 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700">
                    {{ $t('no_results_found') }}
                </div>
            </GridContainer>

            <!-- LIST VIEW -->
            <div v-else>
                <Table>
                    <TableHead>
                        <tr>
                            <TableHeaderCell class="pl-4 sm:pl-6">{{ $t('company_name') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('required_services') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('staff') }} & {{ $t('branch') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('status') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('complexity') }}</TableHeaderCell>
                            <TableHeaderCell>{{ $t('action') }}</TableHeaderCell>
                        </tr>
                    </TableHead>
                    <TableBody>
                        <TableRow v-for="client in filteredClients" :key="client.id">
                            <TableDataCell class="pl-4 sm:pl-6">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 bg-blue-100 dark:bg-blue-900/50 rounded-xl overflow-hidden flex items-center justify-center text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800 shadow-sm">
                                        <img v-if="client.logo_url" :src="client.logo_url" :alt="client.company_name" class="h-full w-full object-cover" />
                                        <span v-else>{{ client.company_name.charAt(0) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ client.company_name }}</div>
                                        <div class="text-[11px] text-gray-500 dark:text-slate-400 uppercase font-bold tracking-tighter">TIN: {{ client.tin_number }} • {{ client.sector }}</div>
                                    </div>
                                </div>
                            </TableDataCell>
                            <TableDataCell>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="st in client.service_types" :key="st.id" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-[10px] font-bold uppercase tracking-tight">
                                        {{ st.name }}
                                    </span>
                                </div>
                            </TableDataCell>
                            <TableDataCell>
                                <div class="font-bold text-gray-900 dark:text-white">{{ client.branch?.name }}</div>
                                <div class="text-xs text-gray-500 dark:text-slate-400">{{ client.assigned_employee?.name || $t('unassigned') }}</div>
                            </TableDataCell>
                            <TableDataCell>
                                <span :class="['inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border uppercase', getStatusColor(client.status)]">
                                    {{ client.status }}
                                </span>
                            </TableDataCell>
                            <TableDataCell>
                                <span class="font-bold text-gray-900 dark:text-white">{{ client.complexity_score }}</span><span class="text-gray-400 text-[10px] font-bold">/10</span>
                            </TableDataCell>
                            <TableDataCell class="pr-4 sm:pr-6 text-right">
                                <RowActions
                                    @edit="openModal(client)"
                                    @delete="confirmDelete(client)"
                                />
                            </TableDataCell>
                        </TableRow>
                        <tr v-if="filteredClients.length === 0">
                            <td colspan="6" class="py-12 text-center text-sm text-gray-500 dark:text-slate-400 bg-white dark:bg-slate-800">{{ $t('no_results_found') }}</td>
                        </tr>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- CLIENT MODAL -->
        <ModalWrapper
            :show="isModalOpen"
            :title="isEditing ? $t('update_profile') : $t('onboard_client')"
            max-width="sm:max-w-2xl"
            @close="closeModal"
        >
            <template #close-btn>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 transition-colors">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </template>

            <form id="client-form" @submit.prevent="submitForm" class="space-y-6">
                <div class="flex items-center space-x-6 mb-6 pb-6 border-b border-slate-100 dark:border-slate-800">
                    <div class="h-20 w-20 rounded-2xl bg-slate-100 dark:bg-slate-800 flex-shrink-0 overflow-hidden border-2 border-dashed border-slate-300 dark:border-slate-700 flex items-center justify-center">
                        <img v-if="form.logo" :src="URL.createObjectURL(form.logo)" class="h-full w-full object-cover" />
                        <img v-else-if="isEditing && clients.find(c => c.id === form.id)?.logo_url" :src="clients.find(c => c.id === form.id).logo_url" class="h-full w-full object-cover" />
                        <span v-else class="text-slate-400 text-2xl font-bold uppercase">{{ form.company_name ? form.company_name.charAt(0) : '?' }}</span>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('logo') || 'Client Logo' }}</label>
                        <input type="file" @input="form.logo = $event.target.files[0]" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/20 dark:file:text-blue-400" />
                        <p class="mt-1 text-[10px] text-slate-400">JPG, PNG or WEBP. Max 2MB.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('company_name') }}</label>
                        <input v-model="form.company_name" type="text" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        <p v-if="form.errors.company_name" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.company_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('tin_number') }}</label>
                        <input v-model="form.tin_number" type="text" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        <p v-if="form.errors.tin_number" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.tin_number }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <SearchableMultiSelect
                            v-model="form.service_type_ids"
                            :options="serviceTypes"
                            :label="$t('required_services')"
                            :placeholder="$t('search_placeholder')"
                        />
                        <p v-if="form.errors.service_type_ids" class="mt-2 text-xs text-red-600 font-bold">{{ form.errors.service_type_ids }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('sector') }}</label>
                        <input v-model="form.sector" type="text" required placeholder="e.g. Manufacturing" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('assign_branch') }}</label>
                        <select v-model="form.branch_id" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="" disabled>{{ $t('select_branch') }}</option>
                            <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('account_manager') }}</label>
                        <select v-model="form.assigned_employee_id" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">-- {{ $t('unassigned') }} --</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ $t('initial_status') }}</label>
                        <select v-model="form.status" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="Incomplete">{{ $t('incomplete') }}</option>
                            <option value="Active">{{ $t('active') }}</option>
                            <option value="Risk">{{ $t('risk') }}</option>
                        </select>
                    </div>
                </div>

                <div class="p-5 bg-slate-50 dark:bg-slate-900/50 rounded-3xl ring-1 ring-inset ring-slate-100 dark:ring-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $t('account_complexity') }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $t('complexity_desc') }}</p>
                        </div>
                        <span class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">{{ form.complexity_score }}</span>
                    </div>
                    <input type="range" v-model="form.complexity_score" min="1" max="10" class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-blue-600">
                </div>
            </form>

            <template #footer>
                <CancelBtn @click="closeModal">{{ $t('cancel') }}</CancelBtn>
                <SubmitBtn form="client-form" :disabled="form.processing">
                    {{ form.processing ? '...' : (isEditing ? $t('update_client') : $t('onboard_client')) }}
                </SubmitBtn>
            </template>
        </ModalWrapper>

        <!-- DELETE MODAL -->
        <DeleteConfirmationModal
            :show="isDeleteModalOpen"
            :title="$t('wipe_client_partition')"
            :isDeleting="isDeleting"
            @close="isDeleteModalOpen = false"
            @confirm="executeDelete"
        >
            {{ $t('delete_confirm_msg') }} <strong class="text-gray-900 dark:text-white">{{ clientToDelete?.company_name }}</strong>? {{ $t('delete_warning_msg') }}
        </DeleteConfirmationModal>
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(59, 130, 246, 0.2);
  border-radius: 10px;
}
</style>
