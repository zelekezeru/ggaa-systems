<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
    PlusIcon, 
    XMarkIcon, 
    BriefcaseIcon, 
    TagIcon, 
    Squares2X2Icon,
    EyeIcon,
    EyeSlashIcon,
    TrashIcon,
    PlusCircleIcon,
    BuildingLibraryIcon,
    UserIcon,
    LockClosedIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';

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
    legalStructures: Array,
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
    trade_license_number: '',
    address: '',
    tax_center: '',
    legal_structure_id: '',
    owner_name: '',
    phone: '',
    email: '',
    etrade_email: '',
    etrade_password: '',
    venture: '',
    year_established: '',
    sector: '',
    service_type_ids: [],
    branch_id: '',
    assigned_employee_id: '',
    complexity_score: 1,
    status: 'Incomplete',
    logo: null,
    bank_accounts: [],
});

// 5-Step Wizard State
const currentFormStep = ref(1);
const formSteps = [
    { step: 1, label: 'Company Info' },
    { step: 2, label: 'Legal & Licences' },
    { step: 3, label: 'Owner & Contact' },
    { step: 4, label: 'Secure Portal' },
    { step: 5, label: 'Bank Accounts' }
];

const nextStep = () => {
    if (currentFormStep.value < 5) {
        currentFormStep.value++;
    }
};

const prevStep = () => {
    if (currentFormStep.value > 1) {
        currentFormStep.value--;
    }
};

const addBankAccountRow = () => {
    form.bank_accounts.push({
        bank_name: '',
        account_type: 'Savings',
        account_number: '',
        balance: 0,
    });
};

const removeBankAccountRow = (index) => {
    form.bank_accounts.splice(index, 1);
};

// Custom Legal Structure Inline Modal
const isNewStructureModalOpen = ref(false);
const newStructureForm = useForm({
    name: '',
    description: '',
});
const submitNewStructure = async () => {
    if (!newStructureForm.name) return;
    try {
        const response = await axios.post(route('admin.legal-structures.store'), {
            name: newStructureForm.name,
            description: newStructureForm.description,
        });
        props.legalStructures.push(response.data);
        form.legal_structure_id = response.data.id;
        isNewStructureModalOpen.value = false;
        newStructureForm.reset();
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to create legal structure.');
    }
};

// Secure ETrade Password Reveal Dialog State
const isRevealModalOpen = ref(false);
const revealPasswordVerification = ref('');
const revealErrorText = ref('');
const revealedPasswordText = ref('');
const selectedRevealClientId = ref(null);

const openRevealModal = (clientId) => {
    selectedRevealClientId.value = clientId;
    revealPasswordVerification.value = '';
    revealErrorText.value = '';
    revealedPasswordText.value = '';
    isRevealModalOpen.value = true;
};

const executeReveal = async () => {
    if (!revealPasswordVerification.value) {
        revealErrorText.value = 'Please insert your staff account password.';
        return;
    }
    try {
        const response = await axios.post(route('admin.clients.reveal-etrade', selectedRevealClientId.value), {
            password: revealPasswordVerification.value
        });
        revealedPasswordText.value = response.data.password || 'No password registered.';
        revealErrorText.value = '';
    } catch (err) {
        revealErrorText.value = err.response?.data?.error || 'Verification failed.';
    }
};

const openModal = (client = null) => {
    currentFormStep.value = 1;
    if (client) {
        isEditing.value = true;
        form.id = client.id;
        form.company_name = client.company_name;
        form.tin_number = client.tin_number;
        form.trade_license_number = client.trade_license_number || '';
        form.address = client.address || '';
        form.tax_center = client.tax_center || '';
        form.legal_structure_id = client.legal_structure_id || '';
        form.owner_name = client.owner_name || '';
        form.phone = client.phone || '';
        form.email = client.email || '';
        form.etrade_email = client.etrade_email || '';
        form.etrade_password = '';
        form.venture = client.venture || '';
        form.year_established = client.year_established || '';
        form.sector = client.sector;
        form.service_type_ids = client.service_types.map(st => st.id);
        form.branch_id = client.branch_id;
        form.assigned_employee_id = client.assigned_employee_id || '';
        form.complexity_score = client.complexity_score;
        form.status = client.status;
        form.bank_accounts = client.bank_accounts ? JSON.parse(JSON.stringify(client.bank_accounts)) : [];
    } else {
        isEditing.value = false;
        form.reset();
        form.service_type_ids = [];
        form.bank_accounts = [];
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
        })).post(route('admin.clients.update', form.id), {
            forceFormData: true,
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

const createLogoUrl = (file) => {
    return file ? URL.createObjectURL(file) : '';
};

const getStatusColor = (status) => {
    switch (status) {
        case 'Active': return 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400 border-green-200 dark:border-green-800';
        case 'Incomplete': return 'bg-slate-50 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400 border-slate-200 dark:border-slate-800';
        case 'Risk': return 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400 border-red-200 dark:border-red-800';
        default: return 'bg-slate-50 text-slate-700 border-slate-200';
    }
};

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
        onFinish: () => {
            isDeleting.value = false;
        }
    });
};
const currentUserId = computed(() => usePage().props.auth.user.id);
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
                            @show="$inertia.get(route('super-admin.clients.show', client.id))"
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
                            <Link :href="route('super-admin.clients.show', client.id)" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate">{{ client.company_name }}</h3>
                            </Link>
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

                        <!-- Elegant Client Bank Accounts Summary -->
                        <div class="mt-3 pt-3 border-t border-dashed border-gray-100 dark:border-slate-700/60">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                    🏦 Banking details
                                </span>
                                <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-1.5 py-0.5 rounded-md">
                                    {{ client.bank_accounts?.reduce((sum, a) => sum + Number(a.balance), 0).toLocaleString() }} Birr
                                </span>
                            </div>
                            <div v-if="client.bank_accounts && client.bank_accounts.length" class="space-y-1 max-h-[75px] overflow-y-auto pr-1">
                                <div v-for="acct in client.bank_accounts" :key="acct.id" class="flex items-center justify-between text-[10px] bg-slate-50 dark:bg-slate-900/50 p-1 px-1.5 rounded-lg border border-slate-100 dark:border-slate-800">
                                    <span class="font-bold text-slate-700 dark:text-slate-350 truncate max-w-[90px]">{{ acct.bank_name }}</span>
                                    <span class="font-mono text-slate-455 truncate max-w-[65px]" :title="acct.account_number">{{ acct.account_number.slice(-4) }}</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ Number(acct.balance).toLocaleString() }} Br</span>
                                </div>
                            </div>
                            <p v-else class="text-[9px] text-slate-400 italic text-center py-1">No bank accounts registered</p>
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
                                        <Link :href="route('super-admin.clients.show', client.id)" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                            <div class="font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ client.company_name }}</div>
                                        </Link>
                                        <div class="text-[11px] text-gray-500 dark:text-slate-400 uppercase font-bold tracking-tighter">TIN: {{ client.tin_number }} • {{ client.sector }}</div>
                                        <!-- Inline Bank Accounts snapshots -->
                                        <div v-if="client.bank_accounts && client.bank_accounts.length" class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                            <div v-for="acct in client.bank_accounts" :key="acct.id" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-slate-50 dark:bg-slate-900 border border-slate-150 dark:border-slate-800 text-[9px] font-black text-slate-600 dark:text-slate-400 shadow-sm">
                                                <span>🏦 {{ acct.bank_name }}</span>
                                                <span class="font-mono text-slate-400">({{ acct.account_number.slice(-4) }})</span>
                                                <span class="text-slate-800 dark:text-slate-200">{{ Number(acct.balance).toLocaleString() }} Br</span>
                                            </div>
                                        </div>
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
                                    @show="$inertia.get(route('super-admin.clients.show', client.id))"
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

        <!-- CLIENT WIZARD MODAL -->
        <ModalWrapper
            :show="isModalOpen"
            :title="isEditing ? 'Edit Client Profile Wizard' : 'Onboard New Corporate Client Wizard'"
            max-width="sm:max-w-3xl"
            @close="closeModal"
        >
            <template #close-btn>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 transition-colors">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </template>

            <!-- Multi-Step Indicators -->
            <div class="mb-6 flex justify-between items-center bg-slate-50 dark:bg-slate-900/60 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/50">
                <div v-for="s in formSteps" :key="s.step" class="flex-1 flex flex-col items-center relative">
                    <div 
                        class="h-8 w-8 rounded-full flex items-center justify-center font-bold text-xs transition-all duration-200 cursor-pointer"
                        :class="currentFormStep >= s.step ? 'bg-blue-600 text-white shadow-md' : 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500'"
                        @click="currentFormStep = s.step"
                    >
                        {{ s.step }}
                    </div>
                    <span class="text-[9px] font-bold text-slate-400 mt-1 uppercase tracking-tighter hidden md:inline">{{ s.label }}</span>
                </div>
            </div>

            <form id="client-form" @submit.prevent="submitForm" class="space-y-6">
                <!-- STEP 1: CORE PROFILE -->
                <div v-if="currentFormStep === 1" class="space-y-6">
                    <div class="bg-blue-50/30 dark:bg-blue-950/10 p-4 rounded-2xl border border-blue-100/50 dark:border-blue-800/30 mb-4">
                        <h4 class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider mb-1">Step 1: Core Company Profile</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Insert basic identifiers, assignee allocations, and regional service branches.</p>
                    </div>

                    <div class="flex items-center space-x-6 pb-6 border-b border-slate-100 dark:border-slate-850">
                        <div class="h-20 w-20 rounded-2xl bg-slate-100 dark:bg-slate-850 flex-shrink-0 overflow-hidden border-2 border-dashed border-slate-300 dark:border-slate-700 flex items-center justify-center">
                            <img v-if="form.logo" :src="createLogoUrl(form.logo)" class="h-full w-full object-cover" />
                            <img v-else-if="isEditing && clients.find(c => c.id === form.id)?.logo_url" :src="clients.find(c => c.id === form.id).logo_url" class="h-full w-full object-cover" />
                            <span v-else class="text-slate-450 text-2xl font-bold uppercase">{{ form.company_name ? form.company_name.charAt(0) : '?' }}</span>
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1">Company Branding Logo</label>
                            <input type="file" @input="form.logo = $event.target.files[0]" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-800 dark:file:text-blue-400" />
                            <p class="mt-1 text-[10px] text-slate-400">JPG, PNG or WEBP. Max 2MB. Optional.</p>
                            <p v-if="form.errors.logo" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.logo }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Registered Company Name</label>
                            <input v-model="form.company_name" type="text" required placeholder="e.g. Global Trade PLC" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.company_name" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.company_name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">TIN Number (Taxpayer ID)</label>
                            <input v-model="form.tin_number" type="text" required placeholder="e.g. 0039281726" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.tin_number" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.tin_number }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Business Sector / Area</label>
                            <input v-model="form.sector" type="text" required placeholder="e.g. Manufacturing, Import/Export" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.sector" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.sector }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Sector Venture / Subsector</label>
                            <input v-model="form.venture" type="text" placeholder="e.g. Leather Processing, Coffee Export" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.venture" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.venture }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Year Established</label>
                            <input v-model="form.year_established" type="number" placeholder="e.g. 2018" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.year_established" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.year_established }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Allocated Service Branch</label>
                            <select v-model="form.branch_id" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="" disabled>-- Select Regional Branch --</option>
                                <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                            </select>
                            <p v-if="form.errors.branch_id" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.branch_id }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Assigned Staff Account Manager</label>
                            <select v-model="form.assigned_employee_id" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">-- {{ $t('unassigned') }} --</option>
                                <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }} <template v-if="emp.email">({{ emp.email }})</template></option>
                            </select>
                            <p v-if="form.errors.assigned_employee_id" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.assigned_employee_id }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Operational Onboarding Status</label>
                            <select v-model="form.status" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="Incomplete">Incomplete / Setup</option>
                                <option value="Active">Active Client</option>
                                <option value="Risk">Compliance / Risk Queue</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.status }}</p>
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
                        <p v-if="form.errors.complexity_score" class="mt-2 text-xs text-red-600 font-bold">{{ form.errors.complexity_score }}</p>
                    </div>
                </div>

                <!-- STEP 2: LEGAL & LICENSING -->
                <div v-if="currentFormStep === 2" class="space-y-6">
                    <div class="bg-blue-50/30 dark:bg-blue-950/10 p-4 rounded-2xl border border-blue-100/50 dark:border-blue-800/30 mb-4">
                        <h4 class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider mb-1">Step 2: Legal & Licensing Details</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Record license credentials, physical coordinates, tax jurisdiction, and dynamic organizational legal structures.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Trade License Number</label>
                            <input v-model="form.trade_license_number" type="text" placeholder="e.g. TR-2026-00392" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.trade_license_number" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.trade_license_number }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Assigned Tax Center</label>
                            <input v-model="form.tax_center" type="text" placeholder="e.g. Large Taxpayers Office (LTO)" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.tax_center" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.tax_center }}</p>
                        </div>

                        <div class="flex items-end gap-2 sm:col-span-2">
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Corporate Legal Structure</label>
                                <select v-model="form.legal_structure_id" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">-- Select Organizational Type --</option>
                                    <option v-for="ls in legalStructures" :key="ls.id" :value="ls.id">{{ ls.name }}</option>
                                </select>
                            </div>
                            <button type="button" @click="isNewStructureModalOpen = true" class="px-3 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-400 font-bold text-xs border border-blue-100 dark:border-blue-800 flex items-center justify-center h-[38px]" title="Add custom legal structure option">
                                <PlusIcon class="h-4 w-4 mr-0.5" />
                                Add Custom
                            </button>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Physical Office Address</label>
                            <textarea v-model="form.address" rows="3" placeholder="e.g. Bole Subcity, Woreda 03, House No. 882, Addis Ababa, Ethiopia" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm resize-none"></textarea>
                            <p v-if="form.errors.address" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.address }}</p>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: OWNER & CONTACTS -->
                <div v-if="currentFormStep === 3" class="space-y-6">
                    <div class="bg-blue-50/30 dark:bg-blue-950/10 p-4 rounded-2xl border border-blue-100/50 dark:border-blue-800/30 mb-4">
                        <h4 class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider mb-1">Step 3: Executive Ownership & Primary Contacts</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Input ultimate beneficial owners, secure contact channels, and mandate client portal communication links.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Owner / General Manager Full Name</label>
                            <input v-model="form.owner_name" type="text" placeholder="e.g. Kebede Alula" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.owner_name" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.owner_name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Primary Contact Phone</label>
                            <input v-model="form.phone" type="text" placeholder="e.g. +251-911-000000" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.phone }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">Portal Verification Email</label>
                            <input v-model="form.email" type="email" required placeholder="e.g. portal@clientdomain.com" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.email }}</p>
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
                    </div>
                </div>

                <!-- STEP 4: SECURE PORTALS / ETRADE -->
                <div v-if="currentFormStep === 4" class="space-y-6">
                    <div class="bg-blue-50/30 dark:bg-blue-950/10 p-4 rounded-2xl border border-blue-100/50 dark:border-blue-800/30 mb-4">
                        <h4 class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider mb-1">Step 4: Government ETrade Portal Access (Secure Vault)</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Store highly sensitive licensing portal access keys. Values are securely encrypted at rest inside standard AES-256 vault partitions.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">ETrade Registered Username / Email</label>
                            <input v-model="form.etrade_email" type="text" placeholder="e.g. etrade.username" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.etrade_email" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.etrade_email }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase tracking-wider mb-1.5">ETrade Secure Vault Password</label>
                            <input v-model="form.etrade_password" type="password" placeholder="••••••••••••••" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            <p v-if="form.errors.etrade_password" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.etrade_password }}</p>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: BANK ACCOUNTS -->
                <div v-if="currentFormStep === 5" class="space-y-6">
                    <div class="flex items-center justify-between bg-blue-50/30 dark:bg-blue-950/10 p-4 rounded-2xl border border-blue-100/50 dark:border-blue-800/30 mb-4">
                        <div>
                            <h4 class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider mb-1">Step 5: Client Bank Account Registry</h4>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Map corporate liquid balances, ledger account formats, and active banking channels.</p>
                        </div>
                        <button type="button" @click="addBankAccountRow" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-xs flex items-center gap-1 shadow-sm">
                            <PlusCircleIcon class="h-4 w-4" />
                            Add Account
                        </button>
                    </div>

                    <div v-if="form.bank_accounts.length === 0" class="py-8 text-center text-xs text-slate-400 dark:text-slate-500 italic bg-slate-50/50 dark:bg-slate-900/10 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
                        No financial bank accounts added yet. Click "+ Add Account" to assign bank connections.
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="(acct, index) in form.bank_accounts" :key="index" class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 flex flex-wrap items-end gap-3 shadow-sm relative pr-10">
                            <div class="absolute top-2.5 right-2.5">
                                <button type="button" @click="removeBankAccountRow(index)" class="text-red-500 hover:text-red-700 p-1" title="Delete account row">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>

                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Bank Name</label>
                                <input v-model="acct.bank_name" type="text" required placeholder="e.g. Awash Bank" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-xs focus:ring-blue-500 focus:border-blue-500" />
                            </div>

                            <div class="w-[120px]">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Account Type</label>
                                <select v-model="acct.account_type" required class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-xs focus:ring-blue-500 focus:border-blue-500">
                                    <option value="Savings">Savings</option>
                                    <option value="Checking">Checking / Current</option>
                                    <option value="Escrow">Escrow</option>
                                    <option value="Loan">Loan Account</option>
                                </select>
                            </div>

                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Account Number</label>
                                <input v-model="acct.account_number" type="text" required placeholder="e.g. 10002938182" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-xs focus:ring-blue-500 focus:border-blue-500" />
                            </div>

                            <div class="w-[120px]">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Current Balance</label>
                                <input v-model="acct.balance" type="number" step="0.01" required placeholder="0.00" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-xs focus:ring-blue-500 focus:border-blue-500" />
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="w-full flex justify-between gap-3">
                    <div>
                        <CancelBtn v-slot:default v-if="currentFormStep === 1" @click="closeModal">{{ $t('cancel') }}</CancelBtn>
                        <button v-else type="button" @click="prevStep" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition-all">Back</button>
                    </div>
                    <div class="flex gap-2">
                        <button v-if="currentFormStep < 5" type="button" @click="nextStep" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-all">Next Step</button>
                        <SubmitBtn v-else form="client-form" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Save Profile' : 'Onboard Client') }}
                        </SubmitBtn>
                    </div>
                </div>
            </template>
        </ModalWrapper>

        <!-- INLINE NEW LEGAL STRUCTURE MICRO MODAL -->
        <ModalWrapper :show="isNewStructureModalOpen" title="Create Custom Legal Structure Option" @close="isNewStructureModalOpen = false">
            <template #close-btn>
                <button @click="isNewStructureModalOpen = false" class="text-slate-400 hover:text-slate-600"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <form id="new-structure-form" @submit.prevent="submitNewStructure" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Structure Option Name</label>
                    <input v-model="newStructureForm.name" type="text" required placeholder="e.g. Joint Venture (JV)" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Short description</label>
                    <textarea v-model="newStructureForm.description" rows="2" placeholder="e.g. Business agreement in which parties agree to develop a new entity..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                </div>
            </form>
            <template #footer>
                <CancelBtn @click="isNewStructureModalOpen = false">Cancel</CancelBtn>
                <SubmitBtn form="new-structure-form" :disabled="newStructureForm.processing">Create Option</SubmitBtn>
            </template>
        </ModalWrapper>

        <!-- SECURE ETRADE VAULT REVEAL MODAL -->
        <ModalWrapper :show="isRevealModalOpen" title="Decrypt Secure Credentials Vault" @close="isRevealModalOpen = false">
            <template #close-btn>
                <button @click="isRevealModalOpen = false" class="text-slate-400 hover:text-slate-600"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <div class="space-y-4">
                <div class="bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900 p-4 rounded-2xl text-xs text-indigo-700 dark:text-indigo-400 leading-relaxed font-semibold">
                    🔑 High Security Verification: To decrypt and view this taxpayer's ETrade credentials, confirm your personal staff/super-admin account password.
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Your Account Password</label>
                    <input v-model="revealPasswordVerification" type="password" required placeholder="Type account password" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    <p v-if="revealErrorText" class="mt-1.5 text-xs text-red-600 font-bold">{{ revealErrorText }}</p>
                </div>

                <div v-if="revealedPasswordText" class="p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                    <span class="block text-[10px] font-bold text-slate-450 uppercase mb-1">Decrypted Portal Password</span>
                    <div class="flex items-center justify-center gap-2">
                        <span class="font-mono text-base font-black text-slate-900 dark:text-white bg-white dark:bg-slate-950 px-4 py-1.5 rounded-xl border border-slate-100 dark:border-slate-800/50 shadow-sm select-all">
                            {{ revealedPasswordText }}
                        </span>
                    </div>
                </div>
            </div>
            <template #footer>
                <CancelBtn @click="isRevealModalOpen = false">Cancel</CancelBtn>
                <button @click="executeReveal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs shadow-sm transition-all">Verify & Reveal</button>
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
