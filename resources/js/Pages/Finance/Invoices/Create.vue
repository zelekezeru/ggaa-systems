<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useRoleLayout } from '@/Composables/useRoleLayout';
import { 
    ChevronLeftIcon, CheckIcon, CalendarIcon, 
    UserIcon, CurrencyDollarIcon, DocumentTextIcon,
    MagnifyingGlassIcon, ListBulletIcon, SparklesIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { ref, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const props = defineProps({
    clients: Array,
});

const { t } = useI18n();
const { currentLayout } = useRoleLayout();

const form = useForm({
    client_id: '',
    period_start: '',
    period_end: '',
    amount: '',
    description: '',
    due_date: '',
    send_now: false,
    services: [],
});

const availableServices = ref([]);
const isLoadingServices = ref(false);
const autoCalculateAmount = ref(true);

const fetchServices = async () => {
    if (!form.client_id || !form.period_start || !form.period_end) return;
    
    isLoadingServices.value = true;
    try {
        const response = await axios.post(route('finance.invoices.services-rendered'), {
            client_id: form.client_id,
            period_start: form.period_start,
            period_end: form.period_end,
        });
        availableServices.value = [...response.data.tasks, ...response.data.ledgers];
        
        // Auto-select all found services
        form.services = availableServices.value.map(s => ({
            id: s.id,
            type: s.type,
            name: s.name,
            completed_at: s.completed_at
        }));
        
        // If auto-calculate is on and it's a retainer client, set the amount
        if (autoCalculateAmount.value) {
            const client = props.clients.find(c => c.id === form.client_id);
            if (client?.retainer_fee) {
                form.amount = client.retainer_fee;
            }
        }
    } catch (error) {
        console.error('Failed to fetch services', error);
    } finally {
        isLoadingServices.value = false;
    }
};

watch([() => form.client_id, () => form.period_start, () => form.period_end], () => {
    fetchServices();
});

const submit = () => {
    form.post(route('finance.invoices.store'), {
        onSuccess: () => form.reset(),
    });
};

const removeService = (index) => {
    form.services.splice(index, 1);
};

const formatDate = (date) => {
    if (!date) return '';
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date));
};
</script>

<template>
    <Head :title="t('create_invoice')" />

    <component :is="currentLayout">
        <div class="max-w-4xl mx-auto pb-20">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400 mb-6">
                <Link :href="route('finance.invoices.index')" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ t('invoices') }}</Link>
                <span>/</span>
                <span class="font-bold text-gray-900 dark:text-white">{{ t('create_new') || 'Create New' }}</span>
            </nav>

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ t('new_invoice') || 'New Invoice' }}</h1>
                    <p class="text-gray-500 dark:text-slate-400 font-medium mt-1">{{ t('create_invoice_subtitle') || 'Generate a professional invoice for rendered services.' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <button 
                        @click="submit"
                        :disabled="form.processing"
                        class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-8 py-3 rounded-2xl font-bold shadow-xl shadow-blue-600/20 transition-all active:scale-95 flex items-center gap-2"
                    >
                        <CheckIcon class="h-5 w-5" v-if="!form.processing" />
                        <div v-else class="h-5 w-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        {{ t('save_invoice') || 'Save Invoice' }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Form -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] border border-gray-100 dark:border-slate-700 shadow-xl p-8">
                        <h2 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-wider mb-6 flex items-center gap-2">
                            <DocumentTextIcon class="h-5 w-5 text-blue-600" />
                            {{ t('invoice_details') || 'Invoice Details' }}
                        </h2>

                        <div class="space-y-6">
                            <!-- Client Selection -->
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ t('select_client') || 'Select Client' }}</label>
                                <div class="relative">
                                    <UserIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <select 
                                        v-model="form.client_id"
                                        class="w-full pl-12 pr-4 py-4 bg-gray-50 dark:bg-slate-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white font-bold appearance-none"
                                    >
                                        <option value="">{{ t('choose_a_client') || 'Choose a client...' }}</option>
                                        <option v-for="client in clients" :key="client.id" :value="client.id">
                                            {{ client.company_name }} {{ client.retainer_fee ? `(Retainer: ETB ${client.retainer_fee})` : '' }}
                                        </option>
                                    </select>
                                </div>
                                <div v-if="form.errors.client_id" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.client_id }}</div>
                            </div>

                            <!-- Billing Period -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ t('period_start') || 'Period Start' }}</label>
                                    <div class="relative">
                                        <CalendarIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                        <input 
                                            v-model="form.period_start"
                                            type="date"
                                            class="w-full pl-12 pr-4 py-4 bg-gray-50 dark:bg-slate-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white font-bold"
                                        />
                                    </div>
                                    <div v-if="form.errors.period_start" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.period_start }}</div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ t('period_end') || 'Period End' }}</label>
                                    <div class="relative">
                                        <CalendarIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                        <input 
                                            v-model="form.period_end"
                                            type="date"
                                            class="w-full pl-12 pr-4 py-4 bg-gray-50 dark:bg-slate-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white font-bold"
                                        />
                                    </div>
                                    <div v-if="form.errors.period_end" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.period_end }}</div>
                                </div>
                            </div>

                            <!-- Services Snapshots (Rendered) -->
                            <div v-if="availableServices.length > 0 || isLoadingServices" class="pt-4 border-t border-gray-100 dark:border-slate-700">
                                <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-4 flex items-center justify-between">
                                    {{ t('services_rendered') || 'Services Rendered' }}
                                    <span v-if="isLoadingServices" class="animate-pulse text-blue-500">{{ t('loading') }}...</span>
                                </label>
                                
                                <div class="space-y-3">
                                    <div 
                                        v-for="(service, idx) in form.services" 
                                        :key="idx"
                                        class="flex items-center justify-between p-4 bg-blue-50/50 dark:bg-blue-900/10 rounded-2xl border border-blue-100/50 dark:border-blue-800/20 group"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-white dark:bg-slate-800 rounded-lg shadow-sm">
                                                <SparklesIcon v-if="service.type === 'task'" class="h-4 w-4 text-amber-500" />
                                                <ListBulletIcon v-else class="h-4 w-4 text-blue-500" />
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ service.name }}</div>
                                                <div class="text-[10px] text-gray-500 uppercase tracking-tighter">{{ formatDate(service.completed_at) }}</div>
                                            </div>
                                        </div>
                                        <button @click="removeService(idx)" class="p-1 hover:bg-rose-100 dark:hover:bg-rose-900/30 rounded-lg text-gray-400 hover:text-rose-500 transition-colors">
                                            <XMarkIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ t('description') }} ({{ t('optional') }})</label>
                                <textarea 
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full px-4 py-4 bg-gray-50 dark:bg-slate-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white font-medium"
                                    :placeholder="t('invoice_description_placeholder') || 'Additional notes or summary of work...'"
                                ></textarea>
                                <div v-if="form.errors.description" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.description }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar / Financials -->
                <div class="space-y-6">
                    <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl p-8 text-white">
                        <h2 class="text-lg font-black uppercase tracking-wider mb-6 flex items-center gap-2">
                            <BanknotesIcon class="h-5 w-5 text-emerald-400" />
                            {{ t('financials') || 'Financials' }}
                        </h2>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ t('invoice_amount') || 'Invoice Amount' }} (ETB)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-500">ETB</span>
                                    <input 
                                        v-model="form.amount"
                                        type="number"
                                        step="0.01"
                                        class="w-full pl-14 pr-4 py-4 bg-white/5 border-white/10 rounded-2xl text-xl font-black focus:ring-2 focus:ring-emerald-500 text-white"
                                        placeholder="0.00"
                                    />
                                </div>
                                <div v-if="form.errors.amount" class="text-rose-400 text-xs mt-1 font-bold">{{ form.errors.amount }}</div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ t('due_date') }}</label>
                                <div class="relative">
                                    <CalendarIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                                    <input 
                                        v-model="form.due_date"
                                        type="date"
                                        class="w-full pl-12 pr-4 py-4 bg-white/5 border-white/10 rounded-2xl text-sm font-bold focus:ring-2 focus:ring-emerald-500 text-white"
                                    />
                                </div>
                                <div v-if="form.errors.due_date" class="text-rose-400 text-xs mt-1 font-bold">{{ form.errors.due_date }}</div>
                            </div>

                            <div class="pt-4 space-y-4">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative">
                                        <input type="checkbox" v-model="form.send_now" class="sr-only peer">
                                        <div class="w-12 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </div>
                                    <span class="text-sm font-bold text-slate-300 group-hover:text-white transition-colors">{{ t('send_immediately') || 'Send Immediately' }}</span>
                                </label>
                                <p class="text-[10px] text-slate-500 font-medium leading-relaxed">
                                    {{ t('send_immediately_help') || 'If enabled, the invoice will be marked as "Sent" and issued immediately upon saving. Otherwise, it stays in "Draft" mode.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-[2rem] border border-blue-100 dark:border-blue-900/30">
                        <h3 class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <MagnifyingGlassIcon class="h-4 w-4" />
                            {{ t('quick_tips') || 'Quick Tips' }}
                        </h3>
                        <ul class="space-y-2 text-[10px] text-blue-800/70 dark:text-blue-300/70 font-medium leading-relaxed">
                            <li>• {{ t('tip_1') || 'Billing period affects which tasks and ledgers are automatically fetched.' }}</li>
                            <li>• {{ t('tip_2') || 'Retainer fees are automatically populated if the client has one set.' }}</li>
                            <li>• {{ t('tip_3') || 'Draft invoices can be reviewed and edited before sending.' }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
