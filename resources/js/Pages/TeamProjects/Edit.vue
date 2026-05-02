<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ArrowLeftIcon, RectangleGroupIcon, ClockIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    project: Object,
    branches: Array,
    clients: Array,
    staffOptions: Array,
    maxCapacity: Number,
});

const form = useForm({
    title: props.project.title,
    description: props.project.description || '',
    client_id: props.project.client_id || '',
    branch_id: props.project.branch_id,
    team_leader_id: props.project.team_leader_id,
    priority: props.project.priority,
    start_date: props.project.start_date ? props.project.start_date.split('T')[0] : '',
    due_date: props.project.due_date ? props.project.due_date.split('T')[0] : '',
    complexity_score: props.project.complexity_score,
});

const filteredClients = computed(() =>
    form.branch_id ? props.clients.filter(c => c.branch_id == form.branch_id) : props.clients
);

const submit = () => form.put(route('team-projects.update', props.project.id));
</script>

<template>
    <Head :title="'Edit ' + project.title" />
    <AdminLayout>
        <div class="p-8 max-w-5xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <Link :href="route('team-projects.show', project.id)" class="p-2 rounded-xl bg-white dark:bg-slate-800 text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 shadow-sm border border-slate-100 dark:border-slate-700/50 transition-all active:scale-95">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Edit Project</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Update project parameters and leadership.</p>
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

                <!-- Team Leadership Card -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 p-8 space-y-6 transition-colors">
                    <div class="flex items-center gap-3 border-b border-slate-50 dark:border-slate-700 pb-4 mb-2">
                        <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
                            <UserGroupIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-white">Leadership</h2>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">Team Leader <span class="text-rose-500">*</span></label>
                        <select v-model="form.team_leader_id" required class="w-full px-4 py-3 rounded-2xl border border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium">
                            <option v-for="s in staffOptions" :key="s.id" :value="s.id">
                                {{ s.name }} — {{ s.position_label || 'Staff' }} (load: {{ s.capacity_load }}/{{ maxCapacity }})
                            </option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase tracking-widest leading-relaxed">Changing the leader will automatically demote the previous leader to a regular team member.</p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-4 p-4">
                    <Link :href="route('team-projects.show', project.id)" class="px-6 py-3 rounded-2xl text-sm font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="px-8 py-3 rounded-2xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-600/30 hover:bg-blue-700 active:scale-95 transition-all disabled:opacity-50">
                        Update Project
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
