<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useRoleLayout } from '@/Composables/useRoleLayout';
import { ArrowLeftIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    branches: Array,
    clients: Array,
    staffOptions: Array,
    maxCapacity: Number,
});

const { currentLayout } = useRoleLayout();

const form = useForm({
    title: '',
    description: '',
    client_id: '',
    branch_id: '',
    team_leader_id: '',
    priority: 'normal',
    start_date: '',
    due_date: '',
    complexity_score: 3,
    members: [],
});

const filteredClients = computed(() =>
    form.branch_id ? props.clients.filter(c => c.branch_id == form.branch_id) : props.clients
);

const memberPool = computed(() => props.staffOptions.filter(s => s.id != form.team_leader_id));

const addMember = () => {
    form.members.push({ user_id: '', complexity_share: form.complexity_score });
};

const removeMember = (i) => form.members.splice(i, 1);

const submit = () => form.post(route('team-projects.store'));
</script>

<template>
    <Head title="New Team Project" />
    <component :is="currentLayout">
        <div class="p-8 max-w-5xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <Link :href="route('team-projects.index')" class="p-2 rounded-xl bg-white dark:bg-slate-800 text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 shadow-sm border border-slate-100 dark:border-slate-700/50 transition-all active:scale-95">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Create Team Project</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Define scope, timeline, and assembly the project team.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8 pb-12">
                <!-- Basic Info Card -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 p-8 space-y-6 transition-colors">
                    <div class="flex items-center gap-3 border-b border-slate-50 dark:border-slate-700 pb-4 mb-2">
                        <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/30">
                            <RectangleGroupIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-white">Project Information</h2>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Project Title <span class="text-rose-500">*</span></label>
                            <input v-model="form.title" type="text" required placeholder="e.g., Annual Tax Compliance 2026" class="w-full px-4 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder:text-slate-300" />
                            <p v-if="form.errors.title" class="text-xs text-rose-600 mt-1 font-bold">{{ form.errors.title }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                            <textarea v-model="form.description" rows="4" placeholder="Briefly explain the project objectives and scope..." class="w-full px-4 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder:text-slate-300"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Branch <span class="text-rose-500">*</span></label>
                            <select v-model="form.branch_id" required class="w-full px-4 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium">
                                <option value="">Select branch</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Client <span class="text-slate-400 font-medium">(Optional)</span></label>
                            <select v-model="form.client_id" class="w-full px-4 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium">
                                <option value="">Internal project</option>
                                <option v-for="c in filteredClients" :key="c.id" :value="c.id">{{ c.company_name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Timeline & Priority Card -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 p-8 space-y-6 transition-colors">
                    <div class="flex items-center gap-3 border-b border-slate-50 dark:border-slate-700 pb-4 mb-2">
                        <div class="p-2 rounded-lg bg-orange-50 dark:bg-orange-900/30">
                            <ClockIcon class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                        </div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-white">Timeline & Complexity</h2>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Priority <span class="text-rose-500">*</span></label>
                            <select v-model="form.priority" class="w-full px-4 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium">
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Complexity (1-10) <span class="text-rose-500">*</span></label>
                            <input v-model.number="form.complexity_score" type="number" min="1" max="10" required class="w-full px-4 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Start Date</label>
                            <input v-model="form.start_date" type="date" class="w-full px-4 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Due Date <span class="text-rose-500">*</span></label>
                            <input v-model="form.due_date" type="date" required class="w-full px-4 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                        </div>
                    </div>
                </div>

                <!-- Team Assembly Card -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 p-8 space-y-6 transition-colors">
                    <div class="flex items-center gap-3 border-b border-slate-50 dark:border-slate-700 pb-4 mb-2">
                        <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
                            <UserGroupIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-white">Team Assembly</h2>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Team Leader <span class="text-rose-500">*</span></label>
                        <select v-model="form.team_leader_id" required class="w-full px-4 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium">
                            <option value="">Select team leader</option>
                            <option v-for="s in staffOptions" :key="s.id" :value="s.id">
                                {{ s.name }} — {{ s.position_label || 'Staff' }} (load: {{ s.capacity_load }}/{{ maxCapacity }})
                            </option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase tracking-widest leading-relaxed">The team leader is the primary contact for the client and oversees project execution.</p>
                    </div>

                    <div class="space-y-4 pt-4">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Team Members</label>
                            <button type="button" @click="addMember" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-bold hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                <PlusIcon class="h-4 w-4" /> Add Member
                            </button>
                        </div>

                        <div v-if="form.members.length === 0" class="text-center py-10 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                            <p class="text-sm text-slate-400 font-medium italic">No additional members yet.</p>
                        </div>

                        <div v-for="(m, i) in form.members" :key="i" class="flex flex-col sm:flex-row gap-3 items-start sm:items-center bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-700 transition-all">
                            <div class="flex-1 w-full">
                                <select v-model="m.user_id" required class="w-full px-4 py-2 rounded-xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium">
                                    <option value="">Select staff member</option>
                                    <option v-for="s in memberPool" :key="s.id" :value="s.id">
                                        {{ s.name }} — {{ s.position_label }} (load: {{ s.capacity_load }}/{{ maxCapacity }})
                                    </option>
                                </select>
                            </div>
                            <div class="w-full sm:w-32">
                                <input v-model.number="m.complexity_share" type="number" min="1" max="10" required
                                    class="w-full px-4 py-2 rounded-xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-center"
                                    placeholder="Share" />
                            </div>
                            <button type="button" @click="removeMember(i)" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors">
                                <TrashIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-4 p-4">
                    <Link :href="route('team-projects.index')" class="px-6 py-3 rounded-2xl text-sm font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="px-8 py-3 rounded-2xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-600/30 hover:bg-blue-700 active:scale-95 transition-all disabled:opacity-50">
                        Create Project
                    </button>
                </div>
            </form>
        </div>
    </component>
</template>
