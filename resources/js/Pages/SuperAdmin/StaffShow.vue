<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import TabGroup from '@/Components/TabGroup.vue';
import Table from '@/Components/Table.vue';
import TableHead from '@/Components/TableHead.vue';
import TableBody from '@/Components/TableBody.vue';
import TableRow from '@/Components/TableRow.vue';
import TableHeaderCell from '@/Components/TableHeaderCell.vue';
import TableDataCell from '@/Components/TableDataCell.vue';
import SubmitBtn from '@/Components/SubmitBtn.vue';
import CancelBtn from '@/Components/CancelBtn.vue';
import ModalWrapper from '@/Components/ModalWrapper.vue';
import SearchableMultiSelect from '@/Components/SearchableMultiSelect.vue';
import { 
    BriefcaseIcon, 
    TagIcon, 
    CheckCircleIcon,
    AcademicCapIcon,
    BoltIcon,
    PresentationChartLineIcon,
    FolderIcon,
    AdjustmentsHorizontalIcon,
    UserIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    staff: Object,
    tasks: Array,
    branches: Array,
    serviceTypes: Array,
    positions: Object,
    performance: Object,
});

const activeTab = ref('Overview');
const tabs = computed(() => [
    { name: 'Overview', label: 'Employee Profile' },
    { name: 'Capacity', label: 'Capacity & Workload' },
    { name: 'Tasks', label: 'Assigned Tasks', count: props.tasks.length },
]);

const getCapacityColor = (percentage) => {
    if (percentage < 50) return 'bg-green-500 text-green-600';
    if (percentage < 85) return 'bg-yellow-500 text-yellow-600';
    return 'bg-red-500 text-red-600';
};

const getTaskStatusColor = (status) => {
    switch (status) {
        case 'done': return 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800';
        case 'in_progress': return 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800';
        default: return 'bg-slate-50 dark:bg-slate-900/30 text-slate-700 dark:text-slate-400 border-slate-200 dark:border-slate-800';
    }
};

// Edit Profile Form
const isEditModalOpen = ref(false);
const form = useForm({
    name: props.staff.name,
    email: props.staff.email,
    branch_id: props.staff.branch_id || '',
    service_type_ids: props.staff.service_types.map(st => st.id),
    position: props.staff.staff_profile?.position || 'employee',
    position_title: props.staff.staff_profile?.position_title || '',
    employment_type: props.staff.staff_profile?.employment_type || 'full_time',
    is_active: props.staff.staff_profile?.is_active ?? true,
    max_capacity: props.staff.staff_profile?.max_capacity ?? 30,
    academic_experience: props.staff.staff_profile?.academic_experience || '',
    training_experience: props.staff.staff_profile?.training_experience || '',
    performance_experience: props.staff.staff_profile?.performance_experience || '',
});

const submitEdit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(route('admin.staff.update', props.staff.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
        }
    });
};
</script>

<template>
    <Head :title="staff.name" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
            
            <div class="flex items-center gap-3 mb-4">
                <Link :href="route('super-admin.staff')" class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest">&larr; Back to staff directory</Link>
            </div>

            <!-- Profile Banner -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 transition-colors duration-200">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 bg-blue-100 dark:bg-blue-900/50 rounded-2xl flex items-center justify-center font-bold text-blue-700 dark:text-blue-300 text-2xl border border-blue-200 dark:border-blue-800 shadow-sm overflow-hidden">
                        <img v-if="staff.profile_photo_url" :src="staff.profile_photo_url" :alt="staff.name" class="h-full w-full object-cover" />
                        <span v-else>{{ staff.name.charAt(0) }}</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-black text-slate-900 dark:text-white">{{ staff.name }}</h1>
                            <span :class="['inline-flex items-center rounded-lg px-2.5 py-0.5 text-xs font-bold border uppercase tracking-wider', staff.staff_profile?.is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-slate-50 text-slate-700 border-slate-200']">
                                {{ staff.staff_profile?.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 uppercase font-bold tracking-wider">{{ staff.staff_profile?.position_title || positions[staff.staff_profile?.position] }} • {{ staff.email }}</p>
                    </div>
                </div>
                <div>
                    <PrimaryActionBtn @click="isEditModalOpen = true" class="flex items-center gap-1">
                        <AdjustmentsHorizontalIcon class="h-4 w-4" />
                        Edit Profile Details
                    </PrimaryActionBtn>
                </div>
            </div>

            <div class="mb-6">
                <TabGroup v-model="activeTab" :tabs="tabs" />
            </div>

            <!-- OVERVIEW TAB -->
            <div v-if="activeTab === 'Overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main columns -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Academic details -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-700 flex items-center gap-1.5">
                            <AcademicCapIcon class="h-4.5 w-4.5 text-blue-500" />
                            Academic Qualifications
                        </h2>
                        <div class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">
                            {{ staff.staff_profile?.academic_experience || 'No academic information recorded. Click Edit Profile to add.' }}
                        </div>
                    </div>

                    <!-- Training details -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-700 flex items-center gap-1.5">
                            <BoltIcon class="h-4.5 w-4.5 text-amber-500" />
                            Professional Training Logs
                        </h2>
                        <div class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">
                            {{ staff.staff_profile?.training_experience || 'No professional training records registered.' }}
                        </div>
                    </div>

                    <!-- Performance and Career track details -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-700 flex items-center gap-1.5">
                            <PresentationChartLineIcon class="h-4.5 w-4.5 text-indigo-500" />
                            Career Accomplishments & Track Record
                        </h2>
                        <div class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">
                            {{ staff.staff_profile?.performance_experience || 'No career track history recorded.' }}
                        </div>
                    </div>
                </div>

                <!-- Sidebar Details -->
                <div class="space-y-6">
                    <!-- Service specialties list -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-700">Specializations</h2>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="st in staff.service_types" :key="st.id" class="px-2.5 py-1 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold border border-blue-100 dark:border-blue-800/50 uppercase tracking-tighter">
                                {{ st.name }}
                            </span>
                            <span v-if="!staff.service_types?.length" class="text-xs text-slate-400 italic">No specialties assigned.</span>
                        </div>
                    </div>

                    <!-- Profile statistics and info details -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200 space-y-4">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white pb-2 border-b border-slate-100 dark:border-slate-700 uppercase tracking-wider">Employment specs</h2>
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Office Division</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ staff.branch?.name || 'Unassigned' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Employment Mode</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300 capitalize">{{ staff.staff_profile?.employment_type?.replace('_', ' ') }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hire date</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ staff.staff_profile?.hire_date ? new Date(staff.staff_profile.hire_date).toLocaleDateString() : 'Unspecified' }}</span>
                        </div>
                    </div>

                    <!-- Performance Scorecard widget -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white pb-2 border-b border-slate-100 dark:border-slate-700 uppercase tracking-wider mb-4">Weighted performance</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                                <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider">Current Month</span>
                                <span class="block text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ performance.current_month }}%</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                                <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider">Prev Month</span>
                                <span class="block text-2xl font-black text-slate-500 dark:text-slate-400 mt-1">{{ performance.previous_month }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CAPACITY TAB -->
            <div v-if="activeTab === 'Capacity'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Circular load meter dial representation -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm text-center transition-colors duration-200">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-6">Workload Allocation shares</h3>
                    <div class="relative inline-flex items-center justify-center mb-6">
                        <div class="h-36 w-36 rounded-full border-[10px] border-slate-100 dark:border-slate-700 flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-900 shadow-inner">
                            <span class="text-4xl font-black text-slate-950 dark:text-white">{{ staff.capacity_points }}</span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">points allocated</span>
                        </div>
                    </div>
                    <div class="space-y-3 px-4">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold text-slate-400 uppercase tracking-wider">Configured Maximum Capacity</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ staff.max_capacity_limit }} points</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold text-slate-400 uppercase tracking-wider">Remaining Capacity Allocation</span>
                            <span class="font-bold text-blue-600 dark:text-blue-400">{{ staff.remaining_capacity }} points</span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 shadow-inner mt-4">
                            <div class="h-2 rounded-full transition-all duration-500 shadow-sm" 
                                :class="getCapacityColor(staff.capacity_percent)" 
                                :style="{ width: Math.min(staff.capacity_percent, 100) + '%' }">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Workload breakdown details -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-700">Capacity & allocation shares logic</h2>
                    <div class="prose prose-sm dark:prose-invert max-w-none text-slate-600 dark:text-slate-300 space-y-4">
                        <p>Workforce allocation uses complexity scores of assigned clients to guarantee balanced workloads across team divisions:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Each client carries a complexity score rating ranging from <strong class="text-blue-500">1 to 10 points</strong>.</li>
                            <li>Allocations prevent staff burnout by blocking assignment additions that exceed maximum configured capacities.</li>
                            <li>Admins can override capacity limits individually to allow specialized workloads or senior managers to accept larger files.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- ASSIGNED TASKS TAB -->
            <div v-if="activeTab === 'Tasks'" class="space-y-6">
                <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden transition-colors duration-200">
                    <Table>
                        <TableHead>
                            <tr>
                                <TableHeaderCell class="pl-6">Task title</TableHeaderCell>
                                <TableHeaderCell>Client</TableHeaderCell>
                                <TableHeaderCell>Status</TableHeaderCell>
                                <TableHeaderCell>Complexity points</TableHeaderCell>
                                <TableHeaderCell class="pr-6">Due date</TableHeaderCell>
                            </tr>
                        </TableHead>
                        <TableBody>
                            <TableRow v-for="task in tasks" :key="task.id">
                                <TableDataCell class="pl-6">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ task.title }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wider font-semibold">Template: {{ task.template?.title || 'Custom Task' }}</div>
                                </TableDataCell>
                                <TableDataCell>
                                    <Link :href="route('super-admin.clients.show', task.client_id)" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ task.client?.company_name }}
                                    </Link>
                                </TableDataCell>
                                <TableDataCell>
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border', getTaskStatusColor(task.status)]">
                                        {{ task.status }}
                                    </span>
                                </TableDataCell>
                                <TableDataCell>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ task.complexity_score || 2 }} points</span>
                                </TableDataCell>
                                <TableDataCell class="pr-6">
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ new Date(task.due_date).toLocaleDateString() }}</span>
                                </TableDataCell>
                            </TableRow>
                            <TableRow v-if="tasks.length === 0">
                                <td colspan="5" class="py-12 text-center text-sm text-slate-400 italic">No tasks assigned to this employee.</td>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

        </div>

        <!-- EDIT PROFILE MODAL -->
        <ModalWrapper :show="isEditModalOpen" title="Edit Employee Profile" max-width="sm:max-w-2xl" @close="isEditModalOpen = false">
            <template #close-btn>
                <button @click="isEditModalOpen = false" class="text-slate-400 hover:text-slate-600"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <form id="edit-form" @submit.prevent="submitEdit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Full Name</label>
                        <input v-model="form.name" type="text" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Email Address</label>
                        <input v-model="form.email" type="email" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Office Division</label>
                        <select v-model="form.branch_id" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Staff Position</label>
                        <select v-model="form.position" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option v-for="(label, key) in positions" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Position title</label>
                        <input v-model="form.position_title" type="text" placeholder="e.g. Senior Tax Advisor" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Capacity points override</label>
                        <input v-model="form.max_capacity" type="number" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <div>
                    <SearchableMultiSelect
                        v-model="form.service_type_ids"
                        :options="serviceTypes"
                        label="Specializations"
                        placeholder="Search specializations..."
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Academic Credentials</label>
                    <textarea v-model="form.academic_experience" rows="3" placeholder="List academic background..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Professional Training & Certifications</label>
                    <textarea v-model="form.training_experience" rows="3" placeholder="List training and key credentials..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Career accomplishments</label>
                    <textarea v-model="form.performance_experience" rows="3" placeholder="Summarize professional experience track..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                </div>
            </form>
            <template #footer>
                <CancelBtn @click="isEditModalOpen = false">Cancel</CancelBtn>
                <SubmitBtn form="edit-form" :disabled="form.processing">Save Profile Changes</SubmitBtn>
            </template>
        </ModalWrapper>

    </AdminLayout>
</template>
