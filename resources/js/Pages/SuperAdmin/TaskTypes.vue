<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useI18n } from 'vue-i18n';
import {
    DocumentDuplicateIcon,
    PlusIcon,
    XMarkIcon,
    PencilSquareIcon,
    TrashIcon,
    CalendarDaysIcon,
    DocumentTextIcon,
    ArrowPathIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline';
import { CheckBadgeIcon } from '@heroicons/vue/24/solid';
import PageHeader        from '@/Components/PageHeader.vue';
import ModalWrapper      from '@/Components/ModalWrapper.vue';
import DeleteConfirmationModal from '@/Components/DeleteConfirmationModal.vue';
import CancelBtn         from '@/Components/CancelBtn.vue';
import SubmitBtn         from '@/Components/SubmitBtn.vue';
import Table             from '@/Components/Table.vue';
import TableHead         from '@/Components/TableHead.vue';
import TableBody         from '@/Components/TableBody.vue';
import TableRow          from '@/Components/TableRow.vue';
import TableHeaderCell   from '@/Components/TableHeaderCell.vue';
import TableDataCell     from '@/Components/TableDataCell.vue';
import RowActions        from '@/Components/RowActions.vue';
import PrimaryActionBtn  from '@/Components/PrimaryActionBtn.vue';

const { t } = useI18n({ useScope: 'global' });

const props = defineProps({
    templates: { type: Array, default: () => [] },
    stats:     { type: Object, default: () => ({}) },
});

// ─── FREQUENCY CONFIG ─────────────────────────────────────────────────────────
const FREQ_CONFIG = {
    Monthly:   { label: 'Monthly',   bg: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',   dot: 'bg-blue-500',   icon: ArrowPathIcon },
    Quarterly: { label: 'Quarterly', bg: 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300', dot: 'bg-violet-500', icon: CalendarDaysIcon },
    Annually:  { label: 'Annually',  bg: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',  dot: 'bg-amber-500',  icon: ClockIcon },
};

function freqConfig(freq) {
    return FREQ_CONFIG[freq] ?? { label: freq, bg: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400', icon: ClockIcon };
}

// ─── MODAL STATE ──────────────────────────────────────────────────────────────
const isModalOpen = ref(false);
const isEditing   = ref(false);

const form = useForm({
    id:                null,
    name:              '',
    frequency:         'Monthly',
    due_date_offset:   20,
    requires_document: true,
});

function openCreateModal() {
    isEditing.value = false;
    form.reset();
    form.frequency         = 'Monthly';
    form.due_date_offset   = 20;
    form.requires_document = true;
    isModalOpen.value = true;
}

function openEditModal(template) {
    isEditing.value = true;
    form.id                = template.id;
    form.name              = template.name;
    form.frequency         = template.frequency;
    form.due_date_offset   = template.due_date_offset;
    form.requires_document = !!template.requires_document;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 200);
}

function submitForm() {
    if (isEditing.value) {
        form.put(route('super-admin.task-types.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('super-admin.task-types.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

// ─── DELETE ───────────────────────────────────────────────────────────────────
const isDeleteModalOpen = ref(false);
const templateToDelete  = ref(null);
const isDeleting        = ref(false);

function confirmDelete(template) {
    templateToDelete.value = template;
    isDeleteModalOpen.value = true;
}

function executeDelete() {
    if (!templateToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('super-admin.task-types.destroy', templateToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            isDeleting.value = false;
            templateToDelete.value = null;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
}

// ─── ORDINAL ──────────────────────────────────────────────────────────────────
function ordinal(n) {
    n = parseInt(n);
    if (n > 3 && n < 21) return `${n}th`;
    const s = ['th', 'st', 'nd', 'rd'];
    return `${n}${s[n % 10] ?? 'th'}`;
}
</script>

<template>
    <Head title="Task Types" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">

            <!-- ── Page Header ──────────────────────────────────────────────── -->
            <PageHeader :title="t('task_types')" :description="t('task_types_desc')">
                <PrimaryActionBtn @click="openCreateModal">
                    <PlusIcon class="h-4 w-4 mr-1.5 -ml-1" />
                    {{ t('add_task_type') }}
                </PrimaryActionBtn>
            </PageHeader>

            <!-- ── Stats Strip ──────────────────────────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 shadow-sm p-4 flex items-center gap-3">
                    <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg flex-shrink-0">
                        <DocumentDuplicateIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ stats.total }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-0.5">Total Types</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 shadow-sm p-4 flex items-center gap-3">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex-shrink-0">
                        <ArrowPathIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ stats.monthly }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-0.5">Monthly</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 shadow-sm p-4 flex items-center gap-3">
                    <div class="p-2 bg-violet-50 dark:bg-violet-900/30 rounded-lg flex-shrink-0">
                        <CalendarDaysIcon class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ stats.quarterly }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-0.5">Quarterly</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 shadow-sm p-4 flex items-center gap-3">
                    <div class="p-2 bg-amber-50 dark:bg-amber-900/30 rounded-lg flex-shrink-0">
                        <ClockIcon class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ stats.annually }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-0.5">Annually</p>
                    </div>
                </div>
            </div>

            <!-- ── Table ────────────────────────────────────────────────────── -->
            <Table>
                <TableHead>
                    <tr>
                        <TableHeaderCell class="pl-4 sm:pl-6">{{ t('task_type_name') }}</TableHeaderCell>
                        <TableHeaderCell>{{ t('frequency') }}</TableHeaderCell>
                        <TableHeaderCell>{{ t('due_offset') }}</TableHeaderCell>
                        <TableHeaderCell>{{ t('requires_document') }}</TableHeaderCell>
                        <TableHeaderCell>{{ t('linked_tasks') }}</TableHeaderCell>
                        <TableHeaderCell>{{ t('action') }}</TableHeaderCell>
                    </tr>
                </TableHead>
                <TableBody>
                    <TableRow v-for="template in templates" :key="template.id" class="group">
                        <!-- Name cell -->
                        <TableDataCell class="pl-4 sm:pl-6">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 flex-shrink-0 rounded-xl flex items-center justify-center border"
                                     :class="{
                                         'bg-blue-50 border-blue-200 dark:bg-blue-900/30 dark:border-blue-800':   template.frequency === 'Monthly',
                                         'bg-violet-50 border-violet-200 dark:bg-violet-900/30 dark:border-violet-800': template.frequency === 'Quarterly',
                                         'bg-amber-50 border-amber-200 dark:bg-amber-900/30 dark:border-amber-800':  template.frequency === 'Annually',
                                     }">
                                    <component
                                        :is="freqConfig(template.frequency).icon"
                                        class="h-5 w-5"
                                        :class="{
                                            'text-blue-600 dark:text-blue-400':   template.frequency === 'Monthly',
                                            'text-violet-600 dark:text-violet-400': template.frequency === 'Quarterly',
                                            'text-amber-600 dark:text-amber-400':  template.frequency === 'Annually',
                                        }"
                                    />
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white">{{ template.name }}</p>
                                    <p class="text-[11px] text-slate-400 font-medium">ID #{{ template.id }}</p>
                                </div>
                            </div>
                        </TableDataCell>

                        <!-- Frequency badge -->
                        <TableDataCell>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                  :class="freqConfig(template.frequency).bg">
                                <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" :class="freqConfig(template.frequency).dot" />
                                {{ freqConfig(template.frequency).label }}
                            </span>
                        </TableDataCell>

                        <!-- Due offset -->
                        <TableDataCell>
                            <div class="flex items-center gap-1.5 text-sm text-slate-700 dark:text-slate-300">
                                <CalendarDaysIcon class="h-4 w-4 text-slate-400 flex-shrink-0" />
                                <span class="font-semibold">{{ ordinal(template.due_date_offset) }}</span>
                                <span class="text-slate-400 text-xs">of period</span>
                            </div>
                        </TableDataCell>

                        <!-- Requires document -->
                        <TableDataCell>
                            <span v-if="template.requires_document"
                                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                <CheckBadgeIcon class="h-3.5 w-3.5" />
                                Required
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                                Optional
                            </span>
                        </TableDataCell>

                        <!-- Linked tasks count -->
                        <TableDataCell>
                            <span class="inline-flex items-center gap-1 text-sm font-semibold"
                                  :class="template.tasks_count > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500'">
                                {{ template.tasks_count }}
                                <span class="font-normal text-xs text-slate-400">task{{ template.tasks_count !== 1 ? 's' : '' }}</span>
                            </span>
                        </TableDataCell>

                        <!-- Actions -->
                        <TableDataCell class="text-right pr-4 sm:pr-6">
                            <RowActions
                                :edit-label="t('edit')"
                                :delete-label="t('delete')"
                                @edit="openEditModal(template)"
                                @delete="confirmDelete(template)"
                            />
                        </TableDataCell>
                    </TableRow>

                    <!-- Empty state -->
                    <tr v-if="templates.length === 0">
                        <td colspan="6">
                            <div class="flex flex-col items-center justify-center py-16 gap-3">
                                <div class="p-4 rounded-2xl bg-gray-100 dark:bg-slate-700">
                                    <DocumentDuplicateIcon class="h-10 w-10 text-gray-400 dark:text-slate-400" />
                                </div>
                                <p class="text-sm font-semibold text-gray-500 dark:text-slate-400">No task types defined yet</p>
                                <p class="text-xs text-gray-400 dark:text-slate-500">Create your first task type to start automating task generation.</p>
                                <button @click="openCreateModal"
                                    class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors">
                                    <PlusIcon class="h-4 w-4" />
                                    {{ t('add_task_type') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </TableBody>
            </Table>

        </div>


        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- CREATE / EDIT MODAL                                                -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <ModalWrapper
            :show="isModalOpen"
            :title="isEditing ? t('edit_task_type') : t('add_task_type')"
            max-width="sm:max-w-lg"
            @close="closeModal"
        >
            <template #close-btn>
                <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </template>

            <form id="task-type-form" @submit.prevent="submitForm" class="space-y-5">

                <!-- Name -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ t('task_type_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="e.g. Monthly VAT Filing"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-400"
                    />
                    <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-600 font-medium">{{ form.errors.name }}</p>
                </div>

                <!-- Frequency -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        {{ t('frequency') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="freq in ['Monthly', 'Quarterly', 'Annually']"
                            :key="freq"
                            type="button"
                            @click="form.frequency = freq"
                            class="relative flex flex-col items-center gap-1.5 py-3 px-2 rounded-xl border-2 text-xs font-semibold transition-all duration-150"
                            :class="form.frequency === freq
                                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 shadow-sm'
                                : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-600'"
                        >
                            <component :is="freqConfig(freq).icon" class="h-5 w-5" />
                            {{ freq }}
                        </button>
                    </div>
                    <p v-if="form.errors.frequency" class="mt-1.5 text-xs text-red-600 font-medium">{{ form.errors.frequency }}</p>
                </div>

                <!-- Due Date Offset -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                            {{ t('due_offset') }} <span class="text-red-500">*</span>
                        </label>
                        <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400 tabular-nums">
                            {{ ordinal(form.due_date_offset) }} of {{ form.frequency === 'Monthly' ? 'month' : form.frequency === 'Quarterly' ? 'quarter' : 'year' }}
                        </span>
                    </div>
                    <input
                        type="range"
                        v-model.number="form.due_date_offset"
                        min="1"
                        max="28"
                        class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-indigo-600"
                    />
                    <div class="flex justify-between mt-1.5 text-[10px] text-slate-400 font-bold uppercase tracking-tighter">
                        <span>1st</span>
                        <span>14th</span>
                        <span>28th</span>
                    </div>
                    <p v-if="form.errors.due_date_offset" class="mt-1.5 text-xs text-red-600 font-medium">{{ form.errors.due_date_offset }}</p>
                </div>

                <!-- Requires Document toggle -->
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl ring-1 ring-inset ring-slate-100 dark:ring-slate-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg" :class="form.requires_document ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-700'">
                            <DocumentTextIcon class="h-4 w-4" :class="form.requires_document ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400'" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ t('requires_document') }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Employee must upload a file to complete this task</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="form.requires_document = !form.requires_document"
                        :class="[
                            form.requires_document ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600',
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
                        ]"
                        :aria-checked="form.requires_document"
                        role="switch"
                    >
                        <span
                            :class="[form.requires_document ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"
                        />
                    </button>
                </div>

                <!-- Summary preview card -->
                <div class="rounded-xl border border-dashed border-indigo-200 dark:border-indigo-800 bg-indigo-50/50 dark:bg-indigo-900/10 p-4">
                    <p class="text-xs font-bold text-indigo-500 dark:text-indigo-400 uppercase tracking-wider mb-1">Preview</p>
                    <p class="text-sm text-slate-700 dark:text-slate-300">
                        Tasks of type <strong class="text-slate-900 dark:text-white">{{ form.name || '…' }}</strong>
                        will be generated <strong>{{ form.frequency }}</strong>
                        and due on the <strong>{{ ordinal(form.due_date_offset) }}</strong> of each period.
                        Document upload is <strong>{{ form.requires_document ? 'required' : 'optional' }}</strong>.
                    </p>
                </div>

            </form>

            <template #footer>
                <CancelBtn @click="closeModal">{{ t('cancel') }}</CancelBtn>
                <SubmitBtn form="task-type-form" :disabled="form.processing">
                    {{ form.processing ? '...' : (isEditing ? t('save') : t('create')) }}
                </SubmitBtn>
            </template>
        </ModalWrapper>


        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- DELETE MODAL                                                        -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <DeleteConfirmationModal
            :show="isDeleteModalOpen"
            :title="t('delete_task_type')"
            :isDeleting="isDeleting"
            @close="isDeleteModalOpen = false"
            @confirm="executeDelete"
        >
            {{ t('are_you_sure_you_want_to_delete') }}
            <strong class="text-slate-900 dark:text-white">{{ templateToDelete?.name }}</strong>?
            <template v-if="templateToDelete?.tasks_count > 0">
                <br /><span class="text-amber-600 dark:text-amber-400 font-medium">⚠ This type has {{ templateToDelete?.tasks_count }} active task{{ templateToDelete?.tasks_count !== 1 ? 's' : '' }} and cannot be deleted.</span>
            </template>
            <template v-else>
                {{ t('this_will_remove_this_configuration_permanently') }}
            </template>
        </DeleteConfirmationModal>

    </AdminLayout>
</template>
