<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import TabGroup from '@/Components/TabGroup.vue';
import Table from '@/Components/Table.vue';
import TableHead from '@/Components/TableHead.vue';
import TableBody from '@/Components/TableBody.vue';
import TableRow from '@/Components/TableRow.vue';
import TableHeaderCell from '@/Components/TableHeaderCell.vue';
import TableDataCell from '@/Components/TableDataCell.vue';
import GridContainer from '@/Components/GridContainer.vue';
import GridCard from '@/Components/GridCard.vue';
import { 
    BriefcaseIcon, 
    TagIcon, 
    CheckCircleIcon,
    DocumentIcon,
    ChatBubbleLeftRightIcon,
    FolderArrowDownIcon,
    CalendarIcon,
    PaperClipIcon,
    MapPinIcon,
    ExclamationTriangleIcon,
    PaperAirplaneIcon,
    EyeIcon,
    EyeSlashIcon,
    BuildingOfficeIcon,
    BuildingLibraryIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';
import ModalWrapper from '@/Components/ModalWrapper.vue';
import CancelBtn from '@/Components/CancelBtn.vue';

const props = defineProps({
    client: Object,
    tasks: Array,
    physicalDocuments: Array,
    conversations: Array,
    projectFiles: Array,
});

// Secure ETrade Password Reveal Dialog State
const isRevealModalOpen = ref(false);
const revealPasswordVerification = ref('');
const revealErrorText = ref('');
const revealedPasswordText = ref('');

const openRevealModal = () => {
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
        const response = await axios.post(route('admin.clients.reveal-etrade', props.client.id), {
            password: revealPasswordVerification.value
        });
        revealedPasswordText.value = response.data.password || 'No password registered.';
        revealErrorText.value = '';
    } catch (err) {
        revealErrorText.value = err.response?.data?.error || 'Verification failed.';
    }
};

const activeTab = ref('Details');
const tabs = computed(() => [
    { name: 'Details', label: 'Company Profile' },
    { name: 'Tasks', label: 'Task Reportings', count: props.tasks.length },
    { name: 'Documents', label: 'Physical Documents', count: props.physicalDocuments.length },
    { name: 'Files', label: 'Project Files', count: props.projectFiles.length },
    { name: 'Chat', label: 'Direct Messages', count: props.conversations.length },
]);

const getStatusColor = (status) => {
    switch (status) {
        case 'Active': return 'bg-green-500/10 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800';
        case 'Risk': return 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800';
        default: return 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800';
    }
};

const getTaskStatusColor = (status) => {
    switch (status) {
        case 'done': return 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800';
        case 'in_progress': return 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800';
        default: return 'bg-slate-50 dark:bg-slate-900/30 text-slate-700 dark:text-slate-400 border-slate-200 dark:border-slate-800';
    }
};

// Message Submit Form
const messageForm = useForm({
    body: '',
});

const page = usePage();
const canSendMessage = computed(() => {
    const user = page.props.auth.user;
    const roles = user.roles || [];
    
    // Super Admin and Branch Manager can always send messages
    if (roles.includes('Super Admin') || roles.includes('Branch Manager')) {
        return true;
    }
    
    // Check if the user is the assigned staff to the client
    return props.client.assigned_employee_id === user.id;
});

const submitMessage = () => {
    if (!messageForm.body.trim()) return;
    messageForm.post(route('employee.messages.store', props.client.id), {
        preserveScroll: true,
        onSuccess: () => {
            messageForm.reset('body');
        }
    });
};
</script>

<template>
    <Head :title="client.company_name" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
            
            <div class="flex items-center gap-3 mb-4">
                <Link :href="route('super-admin.clients')" class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest">&larr; Back to clients</Link>
            </div>

            <!-- Client Header banner -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 transition-colors duration-200">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 bg-blue-100 dark:bg-blue-900/50 rounded-2xl flex items-center justify-center font-bold text-blue-700 dark:text-blue-300 text-2xl border border-blue-200 dark:border-blue-800 shadow-sm overflow-hidden">
                        <img v-if="client.logo_url" :src="client.logo_url" :alt="client.company_name" class="h-full w-full object-cover" />
                        <span v-else>{{ client.company_name.charAt(0) }}</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-3.5">
                            <h1 class="text-2xl font-black text-slate-900 dark:text-white">{{ client.company_name }}</h1>
                            <span :class="['inline-flex items-center rounded-lg px-2.5 py-0.5 text-xs font-bold border uppercase tracking-wider', getStatusColor(client.status)]">
                                {{ client.status }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 uppercase font-bold tracking-wider">TIN Number: {{ client.tin_number }} • Sector: {{ client.sector }}</p>
                    </div>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 px-4 py-3 rounded-2xl border border-slate-100 dark:border-slate-800 font-medium text-xs text-slate-400 flex items-center gap-1.5 self-stretch md:self-auto justify-center">
                    <BriefcaseIcon class="h-4 w-4 text-blue-500" />
                    <span>Manager: <strong>{{ client.assigned_employee?.name || 'Unassigned' }}</strong></span>
                </div>
            </div>

            <div class="mb-6">
                <TabGroup v-model="activeTab" :tabs="tabs" />
            </div>            <!-- DETAILS TAB -->
            <div v-if="activeTab === 'Details'" class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                <div class="lg:col-span-2 space-y-6">
                    <!-- Core Corporate & Legal Registry -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200">
                        <h2 class="text-xs font-bold text-slate-450 uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 dark:border-slate-700 flex items-center gap-1.5">
                            <BuildingLibraryIcon class="h-4 w-4 text-blue-500" />
                            Corporate & Legal Registry
                        </h2>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">TIN Number</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white mt-1">{{ client.tin_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Trade License Number</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white mt-1">{{ client.trade_license_number || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Legal Structure</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white mt-1">{{ client.legal_structure?.name || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Assigned Tax Center</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white mt-1">{{ client.tax_center || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Venture / Business Area</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white mt-1">{{ client.sector }} <span v-if="client.venture" class="text-slate-400 font-normal">({{ client.venture }})</span></dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Year Established</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white mt-1">{{ client.year_established || 'N/A' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Physical coordinates / Address</dt>
                                <dd class="text-sm font-semibold text-slate-700 dark:text-slate-350 mt-1 leading-relaxed">{{ client.address || 'No physical address registered.' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Ownership & Contact Profiles -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200">
                        <h2 class="text-xs font-bold text-slate-450 uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 dark:border-slate-700 flex items-center gap-1.5">
                            <BuildingOfficeIcon class="h-4 w-4 text-blue-500" />
                            Ownership & Contact Profiles
                        </h2>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Owner / GM Name</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white mt-1">{{ client.owner_name || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Primary Contact Phone</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white mt-1">{{ client.phone || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Portal Email Address</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white mt-1">{{ client.email || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Branch Division</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white mt-1">{{ client.branch?.name || 'Unassigned' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Bank Accounts Registry -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200">
                        <h2 class="text-xs font-bold text-slate-450 uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <PlusCircleIcon class="h-4 w-4 text-blue-500" />
                                Bank Accounts Registry
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 px-2 py-0.5 rounded bg-slate-50 dark:bg-slate-900 border border-slate-150 dark:border-slate-850">
                                {{ client.bank_accounts?.length || 0 }} Accounts
                            </span>
                        </h2>

                        <div v-if="!client.bank_accounts?.length" class="text-xs text-slate-400 italic py-3 text-center">
                            No financial bank accounts registered for this corporate client.
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="acct in client.bank_accounts" :key="acct.id" class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm">
                                <div>
                                    <div class="font-bold text-sm text-slate-900 dark:text-white">{{ acct.bank_name }}</div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">{{ acct.account_type }} • {{ acct.account_number }}</div>
                                </div>
                                <div class="text-sm font-black text-slate-800 dark:text-slate-100">
                                    {{ Number(acct.balance).toFixed(2) }} Birr
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center text-xs font-bold text-slate-500">
                                <span>Total Aggregate Balance</span>
                                <span class="text-sm font-black text-emerald-600 dark:text-emerald-400">
                                    {{ client.bank_accounts?.reduce((sum, acct) => sum + Number(acct.balance), 0).toFixed(2) }} Birr
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Secure Credentials Vault -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200">
                        <h2 class="text-xs font-bold text-slate-450 uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 dark:border-slate-700 flex items-center gap-1.5">
                            <LockClosedIcon class="h-4 w-4 text-indigo-500" />
                            Credentials Vault
                        </h2>
                        
                        <div class="space-y-4">
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-tight">ETrade Username / Email</span>
                                <span class="text-sm font-semibold text-slate-900 dark:text-white block mt-1 truncate" :title="client.etrade_email || 'Not registered'">
                                    {{ client.etrade_email || 'Not registered' }}
                                </span>
                            </div>

                            <div v-if="client.has_etrade_password" class="flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50 p-3 rounded-2xl border border-slate-100 dark:border-slate-800">
                                <div>
                                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-tight">Password Vault</span>
                                    <span class="font-mono text-xs text-slate-500 font-bold block mt-0.5">••••••••••••••</span>
                                </div>
                                <button @click="openRevealModal" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 font-bold text-[10px] border border-indigo-100 dark:border-indigo-800 rounded-xl flex items-center gap-1 shadow-sm transition-all">
                                    <EyeIcon class="h-3.5 w-3.5" />
                                    Reveal
                                </button>
                            </div>
                            <div v-else class="text-xs text-slate-400 italic">No ETrade password stored in the secure vault.</div>
                        </div>
                    </div>

                    <!-- Workload Complexity Score -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200 text-center">
                        <h2 class="text-xs font-bold text-slate-400 mb-4 uppercase tracking-wider">Workload complexity score</h2>
                        <div class="relative inline-flex items-center justify-center mb-4">
                            <div class="h-28 w-28 rounded-full border-8 border-slate-100 dark:border-slate-700 flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-900 shadow-inner">
                                <span class="text-3xl font-black text-blue-600 dark:text-blue-400">{{ client.complexity_score }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">points</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed px-4">This score indicates the workload overhead required to handle this account in capacity calculations.</p>
                    </div>

                    <!-- Service scopes -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200">
                        <h2 class="text-xs font-bold text-slate-450 uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 dark:border-slate-700">Service scopes</h2>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="st in client.service_types" :key="st.id" class="px-3 py-1 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold border border-blue-100 dark:border-blue-800/50 uppercase tracking-wider">
                                {{ st.name }}
                            </span>
                            <span v-if="!client.service_types?.length" class="text-xs text-slate-400 italic">No services linked to this client.</span>
                        </div>
                    </div>
                </div>
            </div>

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

            <!-- TASKS TAB -->
            <div v-if="activeTab === 'Tasks'" class="space-y-6">
                <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden transition-colors duration-200">
                    <Table>
                        <TableHead>
                            <tr>
                                <TableHeaderCell class="pl-6">Task title</TableHeaderCell>
                                <TableHeaderCell>Status</TableHeaderCell>
                                <TableHeaderCell>Assigned Employee</TableHeaderCell>
                                <TableHeaderCell>Due date</TableHeaderCell>
                                <TableHeaderCell>Uploaded Vault Files</TableHeaderCell>
                            </tr>
                        </TableHead>
                        <TableBody>
                            <TableRow v-for="task in tasks" :key="task.id">
                                <TableDataCell class="pl-6">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ task.title }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wider font-semibold">Template: {{ task.template?.title || 'Custom Task' }}</div>
                                </TableDataCell>
                                <TableDataCell>
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border', getTaskStatusColor(task.status)]">
                                        {{ task.status }}
                                    </span>
                                </TableDataCell>
                                <TableDataCell>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ task.assigned_employee?.name || 'Unassigned' }}</div>
                                    <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">{{ task.assigned_employee?.email }}</div>
                                </TableDataCell>
                                <TableDataCell>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ new Date(task.due_date).toLocaleDateString() }}</span>
                                </TableDataCell>
                                <TableDataCell>
                                    <div class="space-y-1">
                                        <a 
                                            v-for="(path, idx) in task.document_path" 
                                            :key="idx"
                                            :href="route('tasks.documents.download', { task: task.id, path: path })"
                                            target="_blank"
                                            class="flex items-center gap-1.5 text-xs text-blue-600 dark:text-blue-400 hover:underline font-bold"
                                        >
                                            <PaperClipIcon class="h-3.5 w-3.5" />
                                            <span>Secure Vault File {{ idx + 1 }}</span>
                                        </a>
                                        <span v-if="!task.document_path?.length" class="text-xs text-slate-400 italic">No files uploaded.</span>
                                    </div>
                                </TableDataCell>
                            </TableRow>
                            <TableRow v-if="tasks.length === 0">
                                <td colspan="5" class="py-12 text-center text-sm text-slate-400 italic">No tasks or reportings registered for this client.</td>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- PHYSICAL DOCUMENTS TAB -->
            <div v-if="activeTab === 'Documents'" class="space-y-6">
                <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden transition-colors duration-200">
                    <Table>
                        <TableHead>
                            <tr>
                                <TableHeaderCell class="pl-6">Document details</TableHeaderCell>
                                <TableHeaderCell>Status</TableHeaderCell>
                                <TableHeaderCell>Shelving location</TableHeaderCell>
                                <TableHeaderCell>Stay / Delay</TableHeaderCell>
                                <TableHeaderCell>Accumulated surcharge</TableHeaderCell>
                            </tr>
                        </TableHead>
                        <TableBody>
                            <TableRow v-for="doc in physicalDocuments" :key="doc.id">
                                <TableDataCell class="pl-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                            <DocumentIcon class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white">{{ doc.title }}</div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">
                                                <span>{{ doc.unique_document_id }}</span>
                                                <span class="mx-1">•</span>
                                                <span>Type: {{ doc.document_type?.name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </TableDataCell>
                                <TableDataCell>
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider', doc.status === 'placed' ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 border-green-200' : 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400 border-sky-200']">
                                        {{ doc.status }}
                                    </span>
                                </TableDataCell>
                                <TableDataCell>
                                    <div v-if="doc.shelf_section" class="flex items-center gap-1 text-xs text-slate-700 dark:text-slate-300 font-semibold">
                                        <MapPinIcon class="h-4 w-4 text-green-500" />
                                        <span>{{ doc.shelf_section.label }}</span>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 italic font-semibold">Not Shelved</span>
                                </TableDataCell>
                                <TableDataCell>
                                    <div class="text-xs font-semibold text-slate-900 dark:text-white">Stay: {{ doc.duration_of_stay }} days</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">Grace period: {{ doc.grace_days }} days</div>
                                </TableDataCell>
                                <TableDataCell>
                                    <div v-if="doc.delay_days > 0" class="text-sm font-bold text-red-600 dark:text-red-400">
                                        {{ doc.accumulated_charge.toFixed(2) }} Birr
                                        <span class="block text-[9px] text-red-400 font-medium">{{ doc.delay_days }} delay days</span>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 italic font-semibold">No Charge</span>
                                </TableDataCell>
                            </TableRow>
                            <TableRow v-if="physicalDocuments.length === 0">
                                <td colspan="5" class="py-12 text-center text-sm text-slate-400 italic">No physical office files recorded for this client.</td>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- PROJECT SHARED FILES TAB -->
            <div v-if="activeTab === 'Files'" class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <GridCard v-for="file in projectFiles" :key="file.id" class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="h-10 w-10 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                <FolderArrowDownIcon class="h-5 w-5" />
                            </div>
                            <a :href="file.url" target="_blank" download class="text-[10px] font-bold text-blue-600 dark:text-blue-400 hover:underline uppercase tracking-wider flex items-center gap-1">
                                Download File &rarr;
                            </a>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mt-4 truncate" :title="file.name">{{ file.name }}</h3>
                        <p class="text-[10px] text-slate-400 mt-1 font-semibold uppercase tracking-wider">Project: {{ file.project?.title }}</p>
                        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center text-[10px] font-semibold text-slate-400">
                            <span>Uploaded by: {{ file.uploader?.name }}</span>
                            <span>{{ new Date(file.created_at).toLocaleDateString() }}</span>
                        </div>
                    </GridCard>
                    <div v-if="projectFiles.length === 0" class="col-span-full py-12 text-center text-sm text-slate-400 italic bg-white dark:bg-slate-800 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-3xl">
                        No team collaborative shared files uploaded yet.
                    </div>
                </div>
            </div>

            <!-- CHAT / CONVERSATION TAB -->
            <div v-if="activeTab === 'Chat'" class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/30 dark:bg-slate-800/40">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Direct Message Thread</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Secure live employee-client communication channel.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest">Active Sync</span>
                    </div>
                </div>

                <!-- Messages area -->
                <div class="p-6 h-[420px] overflow-y-auto space-y-6 bg-slate-50/40 dark:bg-slate-900/10 custom-scrollbar">
                    <div 
                        v-for="msg in conversations" 
                        :key="msg.id"
                        class="flex items-end gap-3.5 max-w-[75%]"
                        :class="msg.sender_id === $page.props.auth.user.id ? 'ml-auto flex-row-reverse' : 'mr-auto'"
                    >
                        <!-- Circular Initial Avatar -->
                        <div class="h-9 w-9 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-xs shadow-sm"
                            :class="msg.sender_id === $page.props.auth.user.id ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'"
                        >
                            {{ msg.sender?.name?.charAt(0) || '?' }}
                        </div>

                        <!-- Message block -->
                        <div class="flex flex-col" :class="msg.sender_id === $page.props.auth.user.id ? 'items-end' : 'items-start'">
                            <!-- Time stamp -->
                            <div class="text-[9px] font-bold text-slate-400 mb-1 flex items-center gap-1.5 uppercase tracking-tight">
                                <span>{{ msg.sender?.name }}</span>
                                <span>•</span>
                                <span>{{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                            </div>
                            <!-- Bubble -->
                            <div 
                                class="p-4 rounded-3xl text-sm leading-relaxed shadow-sm transition-all"
                                :class="msg.sender_id === $page.props.auth.user.id 
                                    ? 'bg-gradient-to-tr from-blue-600 to-indigo-500 text-white rounded-br-none' 
                                    : 'bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-bl-none'"
                            >
                                {{ msg.body }}
                            </div>
                        </div>
                    </div>
                    <div v-if="conversations.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 italic text-sm">
                        <ChatBubbleLeftRightIcon class="h-10 w-10 mb-2.5 text-slate-300" />
                        <span>No conversation history registered with this client portal yet.</span>
                    </div>
                </div>

                <!-- Chat entry -->
                <div v-if="canSendMessage" class="p-4 bg-white dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700 shadow-md">
                    <form @submit.prevent="submitMessage" class="flex items-center gap-3">
                        <div class="flex-1 relative flex items-center">
                            <input 
                                v-model="messageForm.body" 
                                type="text" 
                                placeholder="Type your reply here..." 
                                class="w-full pl-5 pr-12 py-3 rounded-2xl border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white shadow-inner sm:text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-slate-900 transition-all border" 
                            />
                            <button type="button" class="absolute right-4 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                                <PaperClipIcon class="h-5 w-5" />
                            </button>
                        </div>
                        <button 
                            type="submit" 
                            :disabled="messageForm.processing || !messageForm.body.trim()" 
                            class="h-11 w-11 rounded-2xl bg-blue-600 hover:bg-blue-700 disabled:bg-slate-100 dark:disabled:bg-slate-700 disabled:text-slate-400 dark:disabled:text-slate-500 text-white font-bold shadow-md hover:shadow-lg active:scale-95 transition-all flex items-center justify-center flex-shrink-0"
                        >
                            <PaperAirplaneIcon class="h-5 w-5" />
                        </button>
                    </form>
                </div>
                <div v-else class="p-5 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 text-center text-xs text-slate-500 dark:text-slate-400 font-semibold italic">
                    Only the assigned staff member, branch managers, or super admins can send messages to this client.
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
