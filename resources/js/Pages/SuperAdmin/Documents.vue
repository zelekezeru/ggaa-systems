<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import TabGroup from '@/Components/TabGroup.vue';
import PrimaryActionBtn from '@/Components/PrimaryActionBtn.vue';
import CancelBtn from '@/Components/CancelBtn.vue';
import SubmitBtn from '@/Components/SubmitBtn.vue';
import ModalWrapper from '@/Components/ModalWrapper.vue';
import Table from '@/Components/Table.vue';
import TableHead from '@/Components/TableHead.vue';
import TableBody from '@/Components/TableBody.vue';
import TableRow from '@/Components/TableRow.vue';
import TableHeaderCell from '@/Components/TableHeaderCell.vue';
import TableDataCell from '@/Components/TableDataCell.vue';
import GridContainer from '@/Components/GridContainer.vue';
import GridCard from '@/Components/GridCard.vue';
import { 
    DocumentIcon, 
    InboxArrowDownIcon, 
    QrCodeIcon, 
    FolderIcon, 
    MapPinIcon,
    PlusIcon, 
    ArrowPathRoundedSquareIcon,
    CalendarIcon,
    CurrencyDollarIcon,
    CheckCircleIcon,
    PencilSquareIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XMarkIcon,
    ExclamationCircleIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const props = defineProps({
    documents: Array,
    documentTypes: Array,
    shelves: Array,
    clients: Array,
});

const activeMainTab = ref('Documents');
const mainTabs = computed(() => [
    { name: 'Documents', label: 'Physical Documents', count: props.documents.length },
    { name: 'Shelves', label: 'Shelving Grid system', count: props.shelves.length },
    { name: 'Types', label: 'Document Categories', count: props.documentTypes.length },
]);

const selectedShelfId = ref(props.shelves[0]?.id ?? null);
const selectedShelf = computed(() => props.shelves.find(s => s.id === selectedShelfId.value));

// Modals State
const isDocModalOpen = ref(false);
const isTypeModalOpen = ref(false);
const isShelfModalOpen = ref(false);
const isScanModalOpen = ref(false);

const docForm = useForm({
    title: '',
    document_type_id: '',
    client_id: '',
    grace_days: 30,
    charge_per_day: 50.00,
    received_at: new Date().toISOString().split('T')[0],
    notes: '',
    images: [],
});

const isEditDocModalOpen = ref(false);
const editingDocId = ref(null);
const editDocForm = useForm({
    title: '',
    document_type_id: '',
    client_id: '',
    grace_days: 30,
    charge_per_day: 50.00,
    received_at: '',
    notes: '',
    images: [],
});

const openEditModal = (doc) => {
    editingDocId.value = doc.id;
    editDocForm.title = doc.title;
    editDocForm.document_type_id = doc.document_type_id;
    editDocForm.client_id = doc.client_id;
    editDocForm.grace_days = doc.grace_days;
    editDocForm.charge_per_day = doc.charge_per_day;
    editDocForm.received_at = doc.received_at ? doc.received_at.split('T')[0] : '';
    editDocForm.notes = doc.notes ?? '';
    editDocForm.images = [];
    isEditDocModalOpen.value = true;
};

const handleNewImagesUpload = (e) => {
    docForm.images = Array.from(e.target.files);
};

const handleEditImagesUpload = (e) => {
    editDocForm.images = Array.from(e.target.files);
};

const submitEditDoc = () => {
    editDocForm.post(route('admin.documents.update', editingDocId.value), {
        onSuccess: () => {
            isEditDocModalOpen.value = false;
            editDocForm.reset();
        }
    });
};

const typeForm = useForm({
    name: '',
    description: '',
});

const shelfForm = useForm({
    label: '',
    total_rows: 5,
    total_columns: 5,
    description: '',
});

// Analytics Reports computations
const totalDocsCount = computed(() => props.documents.length);
const placedDocsCount = computed(() => props.documents.filter(d => d.status === 'placed').length);
const delayedDocsCount = computed(() => props.documents.filter(d => d.delay_days > 0).length);
const totalAccumulatedSurcharge = computed(() => props.documents.reduce((sum, d) => sum + d.accumulated_charge, 0));
const averageStayDays = computed(() => {
    if (props.documents.length === 0) return 0;
    const sum = props.documents.reduce((acc, d) => acc + d.duration_of_stay, 0);
    return Math.round(sum / props.documents.length);
});

// Custom Notification Toast System (replaces native blocking alert dialogs)
const toastConfig = ref({
    show: false,
    message: '',
    type: 'info', // 'success', 'warning', 'error', 'info'
});

const showToast = (message, type = 'info') => {
    toastConfig.value.message = message;
    toastConfig.value.type = type;
    toastConfig.value.show = true;
    setTimeout(() => {
        toastConfig.value.show = false;
    }, 4000);
};

// Step-by-Step Scanner Simulation State
const scanStep = ref(1); // Step 1: Scan Doc, Step 2: Scan Section
const scannedDocQr = ref('');
const scannedSectionQr = ref('');
const targetDoc = computed(() => props.documents.find(d => d.qr_code === scannedDocQr.value));
const targetSection = computed(() => {
    for (let s of props.shelves) {
        let sec = s.sections.find(sec => sec.qr_code === scannedSectionQr.value);
        if (sec) return sec;
    }
    return null;
});

const openScanPlacement = (target = null, type = 'document') => {
    isScanModalOpen.value = true;
    
    if (type === 'section' && target) {
        // Pre-select the shelf slot QR, but start the user at Step 1 (select/scan the document first)
        scannedDocQr.value = '';
        scannedSectionQr.value = target.qr_code;
        scanStep.value = 1;
    } else if (type === 'document' && target) {
        // Pre-select the document QR and jump to Step 2 (align slot QR)
        scannedDocQr.value = target.qr_code;
        scannedSectionQr.value = '';
        scanStep.value = 2;
    } else {
        // Clean start from Step 1
        scannedDocQr.value = '';
        scannedSectionQr.value = '';
        scanStep.value = 1;
    }
};

const executeScanPlacement = () => {
    router.post(route('admin.documents.place'), {
        document_qr: scannedDocQr.value,
        section_qr: scannedSectionQr.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isScanModalOpen.value = false;
            scannedDocQr.value = '';
            scannedSectionQr.value = '';
            scanStep.value = 1;
        }
    });
};

// Check-out / Retrieval checkout Modal State
const isRetrievalModalOpen = ref(false);
const retrievalDoc = ref(null);
const retrievalForm = useForm({
    retrieval_voucher: '',
    retrieval_notes: '',
    process_penalty_payment: true,
});

const openRetrievalModal = (doc) => {
    retrievalDoc.value = doc;
    retrievalForm.retrieval_voucher = 'VCH-' + Math.floor(Math.random() * 900000 + 100000);
    retrievalForm.retrieval_notes = '';
    retrievalForm.process_penalty_payment = doc.accumulated_charge > 0;
    isRetrievalModalOpen.value = true;
};

const executeRetrieval = () => {
    retrievalForm.post(route('admin.documents.retrieve', retrievalDoc.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isRetrievalModalOpen.value = false;
            retrievalDoc.value = null;
            retrievalForm.reset();
        }
    });
};

const submitDoc = () => {
    docForm.post(route('admin.documents.store'), {
        onSuccess: () => {
            isDocModalOpen.value = false;
            docForm.reset();
        }
    });
};

const submitType = () => {
    typeForm.post(route('admin.documents.types.store'), {
        onSuccess: () => {
            isTypeModalOpen.value = false;
            typeForm.reset();
        }
    });
};

const submitShelf = () => {
    shelfForm.post(route('admin.shelves.store'), {
        onSuccess: () => {
            isShelfModalOpen.value = false;
            shelfForm.reset();
        }
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'received': return 'bg-sky-50 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400 border-sky-100 dark:border-sky-800';
        case 'placed': return 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-100 dark:border-green-800';
        case 'retrieved': return 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-100 dark:border-amber-800';
        default: return 'bg-slate-50 dark:bg-slate-900/30 text-slate-700 dark:text-slate-400 border-slate-100 dark:border-slate-800';
    }
};

const isEditShelfModalOpen = ref(false);
const editShelfForm = useForm({
    label: '',
    alternative_name: '',
    description: '',
});

const openEditShelfModal = (shelf) => {
    editShelfForm.label = shelf.label;
    editShelfForm.alternative_name = shelf.alternative_name ?? '';
    editShelfForm.description = shelf.description ?? '';
    isEditShelfModalOpen.value = true;
};

const submitEditShelf = () => {
    editShelfForm.post(route('admin.shelves.update', selectedShelf.value.id), {
        onSuccess: () => {
            isEditShelfModalOpen.value = false;
        }
    });
};

const isSectionDetailModalOpen = ref(false);
const selectedDetailSection = ref(null);

const openSectionDetailModal = (section) => {
    selectedDetailSection.value = section;
    isSectionDetailModalOpen.value = true;
};

const isDocDetailModalOpen = ref(false);
const selectedDetailDoc = ref(null);

const openDocDetailModal = (doc) => {
    selectedDetailDoc.value = doc;
    isDocDetailModalOpen.value = true;
};

// Selection and Print QRs State
const selectedPrintSections = ref([]);
const selectedPrintShelf = ref(null);
const checkedSectionsIds = ref([]);

const toggleSectionChecked = (secId) => {
    if (checkedSectionsIds.value.includes(secId)) {
        checkedSectionsIds.value = checkedSectionsIds.value.filter(id => id !== secId);
    } else {
        checkedSectionsIds.value.push(secId);
    }
};

const checkAllSectionsOfShelf = (shelf) => {
    checkedSectionsIds.value = shelf.sections.map(s => s.id);
};

const uncheckAllSections = () => {
    checkedSectionsIds.value = [];
};

const toggleRowSelection = (rowNum) => {
    if (!selectedShelf.value) return;
    const rowSections = selectedShelf.value.sections.filter(s => s.row === rowNum);
    const rowSectionIds = rowSections.map(s => s.id);
    const allChecked = rowSectionIds.every(id => checkedSectionsIds.value.includes(id));
    
    if (allChecked) {
        checkedSectionsIds.value = checkedSectionsIds.value.filter(id => !rowSectionIds.includes(id));
    } else {
        const uniqueNewIds = rowSectionIds.filter(id => !checkedSectionsIds.value.includes(id));
        checkedSectionsIds.value.push(...uniqueNewIds);
    }
};

const toggleColumnSelection = (colNum) => {
    if (!selectedShelf.value) return;
    const colSections = selectedShelf.value.sections.filter(s => s.column === colNum);
    const colSectionIds = colSections.map(s => s.id);
    const allChecked = colSectionIds.every(id => checkedSectionsIds.value.includes(id));
    
    if (allChecked) {
        checkedSectionsIds.value = checkedSectionsIds.value.filter(id => !colSectionIds.includes(id));
    } else {
        const uniqueNewIds = colSectionIds.filter(id => !checkedSectionsIds.value.includes(id));
        checkedSectionsIds.value.push(...uniqueNewIds);
    }
};

const printSelectedQRs = (shelf) => {
    const list = shelf.sections.filter(s => checkedSectionsIds.value.includes(s.id));
    if (list.length === 0) {
        showToast('Please check at least one section grid block to print.', 'warning');
        return;
    }
    selectedPrintShelf.value = shelf;
    selectedPrintSections.value = list;
    
    // Wait for all dynamic images to be fully loaded into the DOM before opening the print preview dialog
    setTimeout(() => {
        const printArea = document.getElementById('qr-print-area');
        if (!printArea) {
            window.print();
            return;
        }
        const images = printArea.getElementsByTagName('img');
        const promises = [];
        for (let i = 0; i < images.length; i++) {
            const img = images[i];
            if (!img.complete) {
                promises.push(new Promise((resolve) => {
                    img.onload = resolve;
                    img.onerror = resolve;
                }));
            }
        }
        if (promises.length > 0) {
            Promise.all(promises).then(() => {
                window.print();
            });
        } else {
            window.print();
        }
    }, 200);
};

const isGeneratingPDF = ref(false);

const downloadPDFSheet = async (shelf) => {
    const list = shelf.sections.filter(s => checkedSectionsIds.value.includes(s.id));
    if (list.length === 0) {
        showToast('Please check at least one section grid block to generate PDF.', 'warning');
        return;
    }
    isGeneratingPDF.value = true;
    selectedPrintShelf.value = shelf;
    selectedPrintSections.value = list;

    // Wait 500ms to guarantee Vue reactivity has fully constructed and rendered the new QR images in the DOM
    setTimeout(() => {
        const element = document.getElementById('qr-print-area');
        if (!element) {
            isGeneratingPDF.value = false;
            return;
        }

        // Temporarily reveal the print area off-screen so html2canvas renders it with complete styling
        const originalStyle = element.getAttribute('style') || '';
        element.classList.remove('hidden');
        element.style.display = 'block';
        element.style.position = 'absolute';
        element.style.left = '-9999px';
        element.style.top = '0';
        element.style.width = '800px';
        element.style.background = 'white';

        const images = element.getElementsByTagName('img');
        const promises = [];
        for (let i = 0; i < images.length; i++) {
            const img = images[i];
            if (!img.complete) {
                promises.push(new Promise((resolve) => {
                    img.onload = resolve;
                    img.onerror = resolve;
                }));
            }
        }

        const generate = () => {
            const opt = {
                margin:       0.4,
                filename:     `${shelf.label.replace(/\s+/g, '_')}_QRs.pdf`,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, allowTaint: true, logging: false },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };

            const runPdfExport = () => {
                window.html2pdf().from(element).set(opt).save().then(() => {
                    element.setAttribute('style', originalStyle);
                    element.classList.add('hidden');
                    isGeneratingPDF.value = false;
                }).catch(() => {
                    element.setAttribute('style', originalStyle);
                    element.classList.add('hidden');
                    isGeneratingPDF.value = false;
                });
            };

            if (window.html2pdf) {
                runPdfExport();
            } else {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
                script.onload = runPdfExport;
                script.onerror = () => {
                    element.setAttribute('style', originalStyle);
                    element.classList.add('hidden');
                    isGeneratingPDF.value = false;
                    showToast('Failed to load PDF engine helper.', 'error');
                };
                document.head.appendChild(script);
            }
        };

        if (promises.length > 0) {
            Promise.all(promises).then(generate);
        } else {
            generate();
        }
    }, 500);
};

const downloadQRAsPNG = async (qrCodeText, labelText) => {
    try {
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrCodeText)}`;
        const response = await fetch(qrUrl);
        const blob = await response.blob();
        const blobUrl = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = `${labelText.replace(/\s+/g, '_')}_QR.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(blobUrl);
    } catch (error) {
        window.open(`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrCodeText)}`, '_blank');
    }
};
</script>

<template>
    <Head title="Physical Document Vault" />
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
            
            <PageHeader title="Physical Document & Shelf Vault" description="Organize, trace physical binders, scan QR placement grids, and monitor delay storage charges.">
                <div class="flex flex-wrap gap-2.5">
                    <PrimaryActionBtn @click="isDocModalOpen = true" class="flex items-center gap-1">
                        <InboxArrowDownIcon class="h-4 w-4" />
                        Receive Document
                    </PrimaryActionBtn>
                    <PrimaryActionBtn @click="openScanPlacement()" class="bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-700 flex items-center gap-1">
                        <QrCodeIcon class="h-4 w-4" />
                        Scan Placement
                    </PrimaryActionBtn>
                </div>
            </PageHeader>

            <div class="mb-6">
                <TabGroup v-model="activeMainTab" :tabs="mainTabs" />
            </div>

            <!-- PHYSICAL DOCUMENTS LIST -->
            <div v-if="activeMainTab === 'Documents'" class="space-y-6">
                <!-- Analytics Dashboard Reports -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="bg-white dark:bg-slate-800 p-5 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm flex items-center gap-4 transition-colors">
                        <div class="h-12 w-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                            <FolderIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-wide">Total Binders</p>
                            <h4 class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ totalDocsCount }}</h4>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-5 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm flex items-center gap-4 transition-colors">
                        <div class="h-12 w-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <MapPinIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-wide">Active Shelved</p>
                            <h4 class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ placedDocsCount }}</h4>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-5 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm flex items-center gap-4 transition-colors">
                        <div class="h-12 w-12 rounded-2xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <CalendarIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-wide">Avg Stay Days</p>
                            <h4 class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ averageStayDays }} days</h4>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-5 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm flex items-center gap-4 transition-colors">
                        <div class="h-12 w-12 rounded-2xl bg-red-50 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400">
                            <ArrowPathRoundedSquareIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-wide">Delayed Binders</p>
                            <h4 class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ delayedDocsCount }}</h4>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-5 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm flex items-center gap-4 transition-colors">
                        <div class="h-12 w-12 rounded-2xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                            <CurrencyDollarIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-wide">Storage Fees</p>
                            <h4 class="text-lg font-black text-slate-900 dark:text-white mt-0.5">{{ totalAccumulatedSurcharge.toFixed(2) }} <span class="text-xs font-bold">Birr</span></h4>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden transition-colors duration-200">
                    <Table>
                        <TableHead>
                            <tr>
                                <TableHeaderCell class="pl-6">Document details</TableHeaderCell>
                                <TableHeaderCell>Client</TableHeaderCell>
                                <TableHeaderCell>Status</TableHeaderCell>
                                <TableHeaderCell>Shelf position</TableHeaderCell>
                                <TableHeaderCell>Stay / Delay</TableHeaderCell>
                                <TableHeaderCell>Accumulated surcharge</TableHeaderCell>
                                <TableHeaderCell class="pr-6 text-right">Actions</TableHeaderCell>
                            </tr>
                        </TableHead>
                        <TableBody>
                            <TableRow v-for="doc in documents" :key="doc.id">
                                <TableDataCell class="pl-6">
                                    <div class="flex items-start gap-3 py-2">
                                        <div class="h-10 w-10 flex-shrink-0 rounded-xl bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800 flex items-center justify-center text-blue-600 dark:text-blue-400 mt-0.5">
                                            <DocumentIcon class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white">{{ doc.title }}</div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex flex-wrap items-center gap-1.5 mt-0.5">
                                                <span>{{ doc.unique_document_id }}</span>
                                                <span>•</span>
                                                <span class="text-indigo-500 dark:text-indigo-400">{{ doc.document_type?.name }}</span>
                                                <span>•</span>
                                                <span 
                                                    @click="openDocDetailModal(doc)"
                                                    class="font-mono text-indigo-650 dark:text-indigo-455 bg-indigo-55 dark:bg-indigo-900/30 px-1.5 py-0.5 rounded cursor-pointer hover:underline flex items-center gap-0.5"
                                                    title="Click to view full Document details & QR code"
                                                >
                                                    🔍 QR: {{ doc.qr_code }}
                                                </span>
                                            </div>
                                            <!-- Scan/Photo Attachments multiple Image gallery -->
                                            <div v-if="doc.image_paths && doc.image_paths.length" class="flex gap-1.5 mt-2 overflow-x-auto pb-1 max-w-[240px]">
                                                <div v-for="(img, idx) in doc.image_paths" :key="idx" class="relative group/thumb cursor-pointer h-9 w-9 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 flex-shrink-0">
                                                    <a :href="img" target="_blank" title="View Full scan/photo attachment">
                                                        <img :src="img" class="h-full w-full object-cover group-hover/thumb:scale-110 transition-transform" />
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </TableDataCell>
                                <TableDataCell>
                                    <Link :href="route('super-admin.clients.show', doc.client_id)" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ doc.client?.company_name }}
                                    </Link>
                                    <div class="text-[10px] text-slate-400 font-semibold">TIN: {{ doc.client?.tin_number }}</div>
                                </TableDataCell>
                                <TableDataCell>
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border', getStatusBadge(doc.status)]">
                                        {{ doc.status }}
                                    </span>
                                </TableDataCell>
                                <TableDataCell>
                                    <div v-if="doc.shelf_section" class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-medium">
                                        <MapPinIcon class="h-4 w-4 text-green-500" />
                                        <span>{{ doc.shelf_section.label }}</span>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 italic font-semibold">Not Shelved</span>
                                </TableDataCell>
                                <TableDataCell>
                                    <div class="text-slate-900 dark:text-white font-semibold text-xs">Stay: <span class="font-bold text-blue-600 dark:text-blue-400">{{ doc.duration_of_stay }} days</span></div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">Recv: {{ new Date(doc.received_at).toLocaleDateString() }}</div>
                                    <div class="text-[9px] text-slate-400">Grace ends: {{ new Date(doc.delay_charge_starts_at).toLocaleDateString() }}</div>
                                </TableDataCell>
                                <TableDataCell>
                                    <div v-if="doc.delay_days > 0" class="text-red-600 dark:text-red-400 font-bold text-sm">
                                        {{ doc.accumulated_charge.toFixed(2) }} <span class="text-xs">Birr</span>
                                        <span class="block text-[9px] font-medium text-red-400 mt-0.5">{{ doc.delay_days }} delay days</span>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 italic font-semibold">No Charge</span>
                                </TableDataCell>
                                <TableDataCell class="pr-6 text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <PrimaryActionBtn @click="openEditModal(doc)" class="py-1 px-2.5 text-xs bg-slate-50 hover:bg-slate-100 text-slate-700 dark:bg-slate-900/20 dark:text-slate-400 border border-slate-200 dark:border-slate-800">
                                            Edit
                                        </PrimaryActionBtn>
                                        <PrimaryActionBtn v-if="doc.status === 'received'" @click="openScanPlacement(doc)" class="py-1 px-2.5 text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800">
                                            Shelve QR
                                        </PrimaryActionBtn>
                                        <PrimaryActionBtn v-if="doc.status === 'placed'" @click="openRetrievalModal(doc)" class="py-1 px-2.5 text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400 border border-amber-100 dark:border-amber-800">
                                            Retrieve
                                        </PrimaryActionBtn>
                                    </div>
                                </TableDataCell>
                            </TableRow>
                            <TableRow v-if="documents.length === 0">
                                <td colspan="7" class="py-12 text-center text-sm text-slate-400 dark:text-slate-500 italic">
                                    No physical documents registered in the system yet.
                                </td>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- SHELVES GRID VISUALIZATION -->
            <div v-if="activeMainTab === 'Shelves'" class="space-y-6">
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Shelves Sidebar -->
                    <div class="w-full md:w-64 flex-shrink-0 bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm transition-colors duration-200">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-700">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Shelves</h3>
                            <button @click="isShelfModalOpen = true" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                <PlusIcon class="h-5 w-5" />
                            </button>
                        </div>
                        <div class="space-y-1.5">
                            <button 
                                v-for="shelf in shelves" 
                                :key="shelf.id"
                                @click="selectedShelfId = shelf.id"
                                class="w-full text-left px-3 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center justify-between"
                                :class="selectedShelfId === shelf.id ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-100/50 dark:border-blue-800/50' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700/50 border border-transparent'"
                            >
                                <span class="truncate">{{ shelf.label }}</span>
                                <span class="text-[10px] bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded font-mono">{{ shelf.sections.length }} slots</span>
                            </button>
                        </div>
                    </div>

                    <!-- Shelf Grid Content -->
                    <div class="flex-1">
                        <div v-if="selectedShelf" class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 p-6 shadow-sm transition-colors duration-200">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-4 border-b border-slate-100 dark:border-slate-700">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ selectedShelf.label }} Layout</h2>
                                        <button @click="openEditShelfModal(selectedShelf)" class="text-xs font-bold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded-lg border border-blue-100 dark:border-blue-800">
                                            <PencilSquareIcon class="h-3 w-3" />
                                            Edit Shelf
                                        </button>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ selectedShelf.description || 'Physical office file shelving unit.' }}</p>
                                    <p v-if="selectedShelf.alternative_name" class="text-[11px] text-slate-500 mt-1 font-semibold flex items-center gap-1">
                                        <span>Legacy / Existing Name:</span>
                                        <span class="bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 px-1.5 py-0.2 rounded border border-amber-100 dark:border-amber-800">{{ selectedShelf.alternative_name }}</span>
                                    </p>
                                </div>
                                <div class="flex flex-col gap-3 w-full sm:w-auto items-end">
                                    <div class="flex flex-wrap items-center gap-2 justify-end">
                                        <button @click="checkAllSectionsOfShelf(selectedShelf)" class="text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-all">Select All</button>
                                        <span class="text-slate-300 dark:text-slate-700">|</span>
                                        <button @click="uncheckAllSections" class="text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:underline transition-all">Clear Selection</button>
                                    </div>
                                    
                                    <div class="flex flex-wrap gap-2 justify-end">
                                        <button 
                                            @click="printSelectedQRs(selectedShelf)" 
                                            class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors"
                                        >
                                            <QrCodeIcon class="h-4 w-4" />
                                            Print Selected Sheet ({{ checkedSectionsIds.length }})
                                        </button>
                                        
                                        <button 
                                            @click="downloadPDFSheet(selectedShelf)" 
                                            :disabled="isGeneratingPDF"
                                            class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors"
                                        >
                                            <DocumentIcon class="h-4 w-4" />
                                            <span v-if="isGeneratingPDF">Generating PDF...</span>
                                            <span v-else>Download PDF Sheet</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- BATCH ROW/COLUMN SELECTOR QUICK TOOLBAR -->
                            <div class="mb-6 p-4 bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Section Row Quick Batch Selectors</div>
                                    <div class="flex flex-wrap gap-1.5">
                                        <button 
                                            v-for="r in selectedShelf.total_rows" 
                                            :key="'row-sel-'+r"
                                            @click="toggleRowSelection(r)"
                                            class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-[10px] font-bold text-slate-600 dark:text-slate-350 border border-slate-150 dark:border-slate-700 shadow-sm transition-all"
                                        >
                                            Row {{ r }}
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Section Column Quick Batch Selectors</div>
                                    <div class="flex flex-wrap gap-1.5">
                                        <button 
                                            v-for="c in selectedShelf.total_columns" 
                                            :key="'col-sel-'+c"
                                            @click="toggleColumnSelection(c)"
                                            class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-[10px] font-bold text-slate-600 dark:text-slate-350 border border-slate-150 dark:border-slate-700 shadow-sm transition-all"
                                        >
                                            Col {{ c }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <div class="grid gap-3 min-w-[600px]" :style="{ gridTemplateColumns: `repeat(${selectedShelf.total_columns}, minmax(0, 1fr))` }">
                                    <template v-for="r in selectedShelf.total_rows" :key="'row-'+r">
                                        <template v-for="c in selectedShelf.total_columns" :key="'col-'+c">
                                            <div 
                                                v-if="selectedShelf.sections.find(s => s.row === r && s.column === c)"
                                                class="border border-slate-100 dark:border-slate-700 rounded-2xl p-3 min-h-[140px] flex flex-col justify-between transition-all hover:shadow-md relative"
                                                :class="selectedShelf.sections.find(s => s.row === r && s.column === c)?.documents.length ? 'bg-green-50/20 dark:bg-green-900/10 border-green-200/50 dark:border-green-800/30' : 'bg-slate-50/30 dark:bg-slate-900/10'"
                                            >
                                                <!-- Checkbox Selection for QR Print Sheet -->
                                                <div class="absolute top-2.5 right-2.5">
                                                    <input 
                                                        type="checkbox" 
                                                        :checked="checkedSectionsIds.includes(selectedShelf.sections.find(s => s.row === r && s.column === c)?.id)"
                                                        @change="toggleSectionChecked(selectedShelf.sections.find(s => s.row === r && s.column === c)?.id)"
                                                        class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-955 h-3.5 w-3.5 cursor-pointer"
                                                        title="Select this slot section for batch PDF printing"
                                                    />
                                                </div>

                                                <div class="flex items-center justify-between pr-5">
                                                    <span class="text-[10px] font-bold text-slate-400 font-mono uppercase">R{{ r }} - C{{ c }}</span>
                                                    <span 
                                                        @click="openSectionDetailModal(selectedShelf.sections.find(s => s.row === r && s.column === c))"
                                                        class="text-[9px] text-blue-600 dark:text-blue-400 hover:underline cursor-pointer font-bold font-mono bg-blue-50 dark:bg-blue-900/30 px-1.5 py-0.5 rounded flex items-center gap-0.5" 
                                                        title="View Slot Details & QR Preview"
                                                    >
                                                        🔍 QR
                                                    </span>
                                                </div>

                                                <div class="mt-2 flex-1 flex flex-col justify-center">
                                                    <div v-if="selectedShelf.sections.find(s => s.row === r && s.column === c)?.documents.length" class="space-y-1">
                                                        <div 
                                                            v-for="doc in selectedShelf.sections.find(s => s.row === r && s.column === c)?.documents"
                                                            :key="doc.id"
                                                            class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-1.5 rounded-lg shadow-sm text-[10px] font-bold text-slate-700 dark:text-slate-300 truncate"
                                                            :title="doc.title"
                                                        >
                                                            📂 {{ doc.unique_document_id }}
                                                        </div>
                                                    </div>
                                                    <span v-else class="text-[10px] text-slate-300 dark:text-slate-600 text-center italic">Empty Slot</span>
                                                </div>

                                                <div class="mt-2 text-right">
                                                    <button 
                                                        @click="openScanPlacement(selectedShelf.sections.find(s => s.row === r && s.column === c), 'section')"
                                                        class="text-[9px] font-bold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                                    >
                                                        + Shelve Document
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div v-else class="bg-white dark:bg-slate-800 rounded-3xl p-12 text-center text-slate-400 italic">
                            Please create a shelving unit first to view layout grids.
                        </div>
                    </div>
                </div>
            </div>

            <!-- DOCUMENT TYPES VIEW -->
            <div v-if="activeMainTab === 'Types'" class="space-y-6">
                <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm p-6 transition-colors duration-200">
                    <div class="flex justify-between items-center mb-6 border-b border-slate-100 dark:border-slate-700 pb-3">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Document Categories</h2>
                        <PrimaryActionBtn @click="isTypeModalOpen = true">
                            Add Category
                        </PrimaryActionBtn>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <GridCard v-for="type in documentTypes" :key="type.id" class="p-6">
                            <div class="h-10 w-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800 text-blue-600 dark:text-blue-400 flex items-center justify-center mb-4">
                                <FolderIcon class="h-5 w-5" />
                            </div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ type.name }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">{{ type.description || 'No description provided.' }}</p>
                        </GridCard>
                    </div>
                </div>
            </div>

        </div>

        <!-- RECEIVE DOCUMENT MODAL -->
        <ModalWrapper :show="isDocModalOpen" title="Receive New Document" @close="isDocModalOpen = false">
            <template #close-btn>
                <button @click="isDocModalOpen = false" class="text-slate-400 hover:text-slate-600"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <form id="doc-form" @submit.prevent="submitDoc" class="space-y-4" enctype="multipart/form-data">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Document title</label>
                    <input v-model="docForm.title" type="text" required placeholder="e.g. 2026 Audit Receipt Pack" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Received Date</label>
                        <input v-model="docForm.received_at" type="date" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Document Category</label>
                        <select v-model="docForm.document_type_id" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="" disabled>Select category</option>
                            <option v-for="type in documentTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Attach to client</label>
                    <select v-model="docForm.client_id" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="" disabled>Select client</option>
                        <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.company_name }}</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Grace period (Days)</label>
                        <input v-model="docForm.grace_days" type="number" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Delay charge / Day (Birr)</label>
                        <input v-model="docForm.charge_per_day" type="number" step="0.01" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Scan/Photo Attachments (Multiple Images)</label>
                    <input type="file" multiple accept="image/*" @change="handleNewImagesUpload" class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-800 dark:file:text-blue-400" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Notes / Description</label>
                    <textarea v-model="docForm.notes" rows="3" placeholder="Provide any extra tracking details..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                </div>
            </form>
            <template #footer>
                <CancelBtn @click="isDocModalOpen = false">Cancel</CancelBtn>
                <SubmitBtn form="doc-form" :disabled="docForm.processing">Onboard Document</SubmitBtn>
            </template>
        </ModalWrapper>

        <!-- EDIT PHYSICAL DOCUMENT MODAL -->
        <ModalWrapper :show="isEditDocModalOpen" title="Edit Physical Document Details" @close="isEditDocModalOpen = false">
            <template #close-btn>
                <button @click="isEditDocModalOpen = false" class="text-slate-400 hover:text-slate-600"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <form id="edit-doc-form" @submit.prevent="submitEditDoc" class="space-y-4" enctype="multipart/form-data">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Document title</label>
                    <input v-model="editDocForm.title" type="text" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Received Date (Modifiable)</label>
                        <input v-model="editDocForm.received_at" type="date" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Document Category</label>
                        <select v-model="editDocForm.document_type_id" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option v-for="type in documentTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Attach to client</label>
                    <select v-model="editDocForm.client_id" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.company_name }}</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Grace period (Days)</label>
                        <input v-model="editDocForm.grace_days" type="number" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Delay charge / Day (Birr)</label>
                        <input v-model="editDocForm.charge_per_day" type="number" step="0.01" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Upload Additional Scan/Photos</label>
                    <input type="file" multiple accept="image/*" @change="handleEditImagesUpload" class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-800 dark:file:text-blue-400" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Notes / Description</label>
                    <textarea v-model="editDocForm.notes" rows="3" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                </div>
            </form>
            <template #footer>
                <CancelBtn @click="isEditDocModalOpen = false">Cancel</CancelBtn>
                <SubmitBtn form="edit-doc-form" :disabled="editDocForm.processing">Save Changes</SubmitBtn>
            </template>
        </ModalWrapper>

        <!-- RETRIEVAL VOUCHER & CHECKOUT MODAL -->
        <ModalWrapper :show="isRetrievalModalOpen" title="Physical Document Retrieval Checkout" @close="isRetrievalModalOpen = false">
            <template #close-btn>
                <button @click="isRetrievalModalOpen = false" class="text-slate-400 hover:text-slate-600"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <form id="retrieval-form" @submit.prevent="executeRetrieval" class="space-y-4">
                <div class="bg-amber-50 dark:bg-amber-955/20 border border-amber-100 dark:border-amber-900 p-4 rounded-2xl">
                    <h5 class="text-xs font-black uppercase text-amber-800 dark:text-amber-400 tracking-wide">Retrieval Target</h5>
                    <div class="text-sm font-bold text-slate-900 dark:text-white mt-1">{{ retrievalDoc?.title }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">ID: <span class="font-mono font-bold">{{ retrievalDoc?.unique_document_id }}</span> • Owner: {{ retrievalDoc?.client?.company_name }}</div>
                </div>

                <div v-if="retrievalDoc?.accumulated_charge > 0" class="bg-red-50 dark:bg-red-955/20 border border-red-100 dark:border-red-900 p-4 rounded-2xl">
                    <h5 class="text-xs font-black uppercase text-red-800 dark:text-red-400 tracking-wide">Delay Penalty Notice</h5>
                    <div class="text-lg font-black text-red-600 dark:text-red-400 mt-1">
                        {{ retrievalDoc.accumulated_charge.toFixed(2) }} Birr
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">Accumulated over {{ retrievalDoc.delay_days }} penalty days (grace limit of {{ retrievalDoc.grace_days }} days exceeded).</div>
                    
                    <label class="flex items-center gap-2 mt-3 cursor-pointer">
                        <input v-model="retrievalForm.process_penalty_payment" type="checkbox" class="rounded text-red-600 focus:ring-red-500 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950" />
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Process payment dynamically in active financial ledger</span>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Recipient Voucher / Reference Number</label>
                    <input v-model="retrievalForm.retrieval_voucher" type="text" required placeholder="e.g. VCH-78239" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Retrieval Hand-over details / notes</label>
                    <textarea v-model="retrievalForm.retrieval_notes" rows="3" required placeholder="Specify who retrieved this document, firm status details, or hand-over descriptions..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                </div>
            </form>
            <template #footer>
                <CancelBtn @click="isRetrievalModalOpen = false">Cancel</CancelBtn>
                <SubmitBtn form="retrieval-form" :disabled="retrievalForm.processing" class="bg-amber-600 hover:bg-amber-700 text-white">
                    Confirm & Retrieve File
                </SubmitBtn>
            </template>
        </ModalWrapper>

        <!-- ADD DOCUMENT TYPE MODAL -->
        <ModalWrapper :show="isTypeModalOpen" title="Add Document Category" @close="isTypeModalOpen = false">
            <template #close-btn>
                <button @click="isTypeModalOpen = false" class="text-slate-400 hover:text-slate-600"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <form id="type-form" @submit.prevent="submitType" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Category Name</label>
                    <input v-model="typeForm.name" type="text" required placeholder="e.g. Audit Binder" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Description</label>
                    <textarea v-model="typeForm.description" rows="3" placeholder="Brief explanation of this type..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                </div>
            </form>
            <template #footer>
                <CancelBtn @click="isTypeModalOpen = false">Cancel</CancelBtn>
                <SubmitBtn form="type-form" :disabled="typeForm.processing">Create Category</SubmitBtn>
            </template>
        </ModalWrapper>

        <!-- ADD SHELF MODAL -->
        <ModalWrapper :show="isShelfModalOpen" title="Create Office Shelf Grid" @close="isShelfModalOpen = false">
            <template #close-btn>
                <button @click="isShelfModalOpen = false" class="text-slate-400 hover:text-slate-600"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <form id="shelf-form" @submit.prevent="submitShelf" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Shelf Label ID</label>
                    <input v-model="shelfForm.label" type="text" required placeholder="e.g. Shelf C" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Grid Rows (Height)</label>
                        <input v-model="shelfForm.total_rows" type="number" min="1" max="10" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Grid Columns (Width)</label>
                        <input v-model="shelfForm.total_columns" type="number" min="1" max="10" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Description</label>
                    <textarea v-model="shelfForm.description" rows="3" placeholder="Specify physical room / location..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                </div>
            </form>
            <template #footer>
                <CancelBtn @click="isShelfModalOpen = false">Cancel</CancelBtn>
                <SubmitBtn form="shelf-form" :disabled="shelfForm.processing">Generate Shelf Grid</SubmitBtn>
            </template>
        </ModalWrapper>

        <!-- SCAN TO PLACE STEP-BY-STEP QR SIMULATOR MODAL -->
        <ModalWrapper :show="isScanModalOpen" title="Scan-to-Place Shelving Assistant" max-width="sm:max-w-md" @close="isScanModalOpen = false">
            <template #close-btn>
                <button @click="isScanModalOpen = false" class="text-slate-400 hover:text-slate-600"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <div class="p-2 text-center">
                <!-- Interactive High-Fidelity Scanning Viewfinder -->
                <div class="relative w-full h-48 bg-slate-950 rounded-2xl overflow-hidden border border-slate-800 shadow-inner flex flex-col items-center justify-center mb-5">
                    <!-- REC Indicator -->
                    <div class="absolute top-3 left-3 flex items-center gap-1.5 bg-slate-900/90 px-2 py-0.5 rounded-lg border border-slate-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-red-600 animate-ping"></span>
                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest font-mono">LIVE FEED</span>
                    </div>

                    <!-- Scan Mode Type Badge -->
                    <div class="absolute top-3 right-3 bg-blue-900/50 dark:bg-blue-950/60 px-2 py-0.5 rounded-lg border border-blue-800 text-[8px] font-bold text-blue-400 uppercase tracking-widest">
                        {{ scanStep === 1 ? 'Scan File QR' : 'Scan Slot QR' }}
                    </div>

                    <!-- Targeting Box -->
                    <div class="relative w-32 h-32 border border-slate-800 rounded-2xl flex items-center justify-center transition-all duration-300"
                         :class="{
                             'border-green-500 bg-green-950/10': (scanStep === 1 && scannedDocQr) || (scanStep === 2 && scannedSectionQr),
                             'border-blue-500 bg-blue-950/5': (!scannedDocQr && scanStep === 1) || (!scannedSectionQr && scanStep === 2)
                         }">
                        <!-- Crosshair Corners -->
                        <div class="absolute -top-0.5 -left-0.5 w-3.5 h-3.5 border-t-2 border-l-2 rounded-tl"
                             :class="(scanStep === 1 && scannedDocQr) || (scanStep === 2 && scannedSectionQr) ? 'border-green-500' : 'border-blue-500'"></div>
                        <div class="absolute -top-0.5 -right-0.5 w-3.5 h-3.5 border-t-2 border-r-2 rounded-tr"
                             :class="(scanStep === 1 && scannedDocQr) || (scanStep === 2 && scannedSectionQr) ? 'border-green-500' : 'border-blue-500'"></div>
                        <div class="absolute -bottom-0.5 -left-0.5 w-3.5 h-3.5 border-b-2 border-l-2 rounded-bl"
                             :class="(scanStep === 1 && scannedDocQr) || (scanStep === 2 && scannedSectionQr) ? 'border-green-500' : 'border-blue-500'"></div>
                        <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 border-b-2 border-r-2 rounded-br"
                             :class="(scanStep === 1 && scannedDocQr) || (scanStep === 2 && scannedSectionQr) ? 'border-green-500' : 'border-blue-500'"></div>

                        <!-- Active laser scanner anim (only when not loaded) -->
                        <div v-if="!(scanStep === 1 && scannedDocQr) && !(scanStep === 2 && scannedSectionQr)" 
                             class="absolute w-full h-0.5 bg-blue-500/80 shadow-[0_0_6px_rgba(59,130,246,0.8)] top-0 left-0 animate-scanner-laser"></div>

                        <!-- Icons depending on status -->
                        <div v-if="(scanStep === 1 && scannedDocQr) || (scanStep === 2 && scannedSectionQr)" class="text-center animate-in zoom-in duration-300">
                            <CheckCircleIcon class="h-14 w-14 text-green-500 mx-auto" />
                            <span class="text-[9px] font-black text-green-400 uppercase tracking-widest block mt-1">MATCH FOUND</span>
                        </div>
                        <QrCodeIcon v-else class="h-14 w-14 text-slate-800 animate-pulse" />
                    </div>

                    <!-- Status Bar Overlay -->
                    <div class="absolute bottom-2.5 font-mono text-[9px] uppercase tracking-wider"
                         :class="(scanStep === 1 && scannedDocQr) || (scanStep === 2 && scannedSectionQr) ? 'text-green-400 font-bold' : 'text-blue-400'">
                        {{ (scanStep === 1 && scannedDocQr) || (scanStep === 2 && scannedSectionQr) ? 'Target locked & Decoded' : 'Align QR code in viewfinder' }}
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Step 1: Scan Document QR -->
                    <div v-if="scanStep === 1" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm">Step 1: Scan Physical Document QR Code</h3>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">Select a received physical document below to simulate dynamic QR scanner lens alignment.</p>
                        
                        <div class="mt-4">
                            <select v-model="scannedDocQr" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Choose document to scan --</option>
                                <option 
                                    v-for="doc in documents.filter(d => d.status === 'received' || d.status === 'retrieved')" 
                                    :key="doc.id" 
                                    :value="doc.qr_code"
                                >
                                    {{ doc.title }} ({{ doc.unique_document_id }})
                                </option>
                            </select>
                        </div>

                        <div class="mt-6">
                            <PrimaryActionBtn 
                                @click="scanStep = 2" 
                                :disabled="!scannedDocQr"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold"
                            >
                                Next: Align Shelf Grid QR &rarr;
                            </PrimaryActionBtn>
                        </div>
                    </div>

                    <!-- Step 2: Scan Shelf Section QR -->
                    <div v-if="scanStep === 2" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm">Step 2: Scan Shelf Section Grid QR Code</h3>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">Simulate scanning the QR code sticker label attached to the shelf cabinet slot.</p>
                        
                        <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800 text-left">
                            <div class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Document selected</div>
                            <div class="text-xs font-bold text-slate-900 dark:text-white mt-1">{{ targetDoc?.title || 'Selected document' }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">{{ targetDoc?.unique_document_id }} • QR Code: <span class="font-mono font-bold">{{ scannedDocQr || targetDoc?.qr_code }}</span></div>
                        </div>

                        <div class="mt-4 text-left">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Target Shelf Section Slot</label>
                            <select v-model="scannedSectionQr" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Choose shelf section slot --</option>
                                <optgroup v-for="shelf in shelves" :key="'og-'+shelf.id" :label="shelf.label">
                                    <option 
                                        v-for="sec in shelf.sections" 
                                        :key="sec.id" 
                                        :value="sec.qr_code"
                                    >
                                        {{ sec.label }} (QR: {{ sec.qr_code.split('-').pop() }})
                                    </option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="mt-6 flex gap-3">
                            <CancelBtn @click="scanStep = 1" class="flex-1">Back</CancelBtn>
                            <PrimaryActionBtn 
                                @click="executeScanPlacement" 
                                :disabled="!scannedSectionQr"
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold"
                            >
                                Confirm Shelving
                            </PrimaryActionBtn>
                        </div>
                    </div>
                </div>
            </div>
        </ModalWrapper>

        <!-- EDIT OFFICE SHELF DETAILS MODAL -->
        <ModalWrapper :show="isEditShelfModalOpen" title="Edit Office Shelf Details" @close="isEditShelfModalOpen = false">
            <template #close-btn>
                <button @click="isEditShelfModalOpen = false" class="text-slate-400 hover:text-slate-600"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <form id="edit-shelf-form" @submit.prevent="submitEditShelf" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Shelf Label / Name</label>
                    <input v-model="editShelfForm.label" type="text" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Legacy / Currently Used Existing Name</label>
                    <input v-model="editShelfForm.alternative_name" type="text" placeholder="e.g. Vintage Wood Shelf Floor 2" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Description / Location notes</label>
                    <textarea v-model="editShelfForm.description" rows="3" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                </div>
            </form>
            <template #footer>
                <CancelBtn @click="isEditShelfModalOpen = false">Cancel</CancelBtn>
                <SubmitBtn form="edit-shelf-form" :disabled="editShelfForm.processing">Save Changes</SubmitBtn>
            </template>
        </ModalWrapper>

        <!-- SHELF SECTION SLOT DETAIL & QR PREVIEW MODAL -->
        <ModalWrapper :show="isSectionDetailModalOpen" title="Shelf Section Slot & QR Code" @close="isSectionDetailModalOpen = false">
            <template #close-btn>
                <button @click="isSectionDetailModalOpen = false" class="text-slate-400 hover:text-slate-655"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <div class="space-y-6 p-2 text-center" v-if="selectedDetailSection">
                <!-- Large QR Code Render -->
                <div class="flex flex-col items-center justify-center p-6 bg-slate-50 dark:bg-slate-900/60 rounded-3xl border border-slate-100 dark:border-slate-800/80">
                    <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(selectedDetailSection.qr_code)}`" 
                         class="h-44 w-44 object-contain bg-white p-3 rounded-2xl shadow-md border border-slate-200 dark:border-slate-700" />
                    <div class="text-xs font-mono font-bold text-slate-500 mt-4 bg-slate-100 dark:bg-slate-850 px-3 py-1 rounded-full border border-slate-200/40 dark:border-slate-700/40">{{ selectedDetailSection.qr_code }}</div>
                </div>

                <!-- Section Metadata details -->
                <div class="grid grid-cols-2 gap-4 text-left">
                    <div class="bg-slate-50/50 dark:bg-slate-900/40 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Shelf Unit</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ selectedShelf?.label }}</span>
                    </div>
                    <div class="bg-slate-50/50 dark:bg-slate-900/40 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Slot / Room Label</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ selectedDetailSection.label }}</span>
                    </div>
                    <div class="bg-slate-50/50 dark:bg-slate-900/40 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Coordinate</span>
                        <span class="text-xs font-mono font-black text-indigo-600 dark:text-indigo-400 mt-0.5 block">Row {{ selectedDetailSection.row }} • Col {{ selectedDetailSection.column }}</span>
                    </div>
                    <div class="bg-slate-50/50 dark:bg-slate-900/40 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Files count</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ selectedDetailSection.documents?.length || 0 }} files shelved</span>
                    </div>
                </div>

                <!-- Documents list inside the section -->
                <div class="text-left" v-if="selectedDetailSection.documents && selectedDetailSection.documents.length">
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2.5">Currently Stored Documents</h4>
                    <div class="space-y-2">
                        <div v-for="doc in selectedDetailSection.documents" :key="doc.id" class="p-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-center justify-between shadow-sm">
                            <div>
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ doc.title }}</div>
                                <div class="text-[9px] font-mono text-slate-400 mt-0.5">{{ doc.unique_document_id }}</div>
                            </div>
                            <Link :href="route('super-admin.clients.show', doc.client_id)" class="text-[10px] font-bold text-blue-650 dark:text-blue-450 hover:underline">
                                {{ doc.client?.company_name || 'Client' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <CancelBtn @click="isSectionDetailModalOpen = false">Close</CancelBtn>
                <button 
                    v-if="selectedDetailSection"
                    @click="downloadQRAsPNG(selectedDetailSection.qr_code, selectedDetailSection.label)"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-750 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors"
                >
                    💾 Download QR PNG
                </button>
            </template>
        </ModalWrapper>

        <!-- PHYSICAL DOCUMENT DETAIL & QR PREVIEW MODAL -->
        <ModalWrapper :show="isDocDetailModalOpen" title="Physical Document & QR Code Details" @close="isDocDetailModalOpen = false">
            <template #close-btn>
                <button @click="isDocDetailModalOpen = false" class="text-slate-400 hover:text-slate-655"><XMarkIcon class="h-5 w-5" /></button>
            </template>
            <div class="space-y-6 p-2 text-center" v-if="selectedDetailDoc">
                <!-- Large QR Code Render -->
                <div class="flex flex-col items-center justify-center p-6 bg-slate-50 dark:bg-slate-900/60 rounded-3xl border border-slate-100 dark:border-slate-800/80">
                    <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(selectedDetailDoc.qr_code)}`" 
                         class="h-44 w-44 object-contain bg-white p-3 rounded-2xl shadow-md border border-slate-200 dark:border-slate-700" />
                    <div class="text-xs font-mono font-bold text-slate-500 mt-4 bg-slate-100 dark:bg-slate-850 px-3 py-1 rounded-full border border-slate-200/40 dark:border-slate-700/40">{{ selectedDetailDoc.qr_code }}</div>
                </div>

                <!-- Document Details metadata grid -->
                <div class="grid grid-cols-2 gap-4 text-left">
                    <div class="bg-slate-50/50 dark:bg-slate-900/40 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Document Title</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ selectedDetailDoc.title }}</span>
                    </div>
                    <div class="bg-slate-50/50 dark:bg-slate-900/40 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Unique ID</span>
                        <span class="text-xs font-mono font-bold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ selectedDetailDoc.unique_document_id }}</span>
                    </div>
                    <div class="bg-slate-50/50 dark:bg-slate-900/40 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Client Owner</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ selectedDetailDoc.client?.company_name }}</span>
                    </div>
                    <div class="bg-slate-50/50 dark:bg-slate-900/40 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Status</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5 block capitalize">{{ selectedDetailDoc.status }}</span>
                    </div>
                    <div class="bg-slate-50/50 dark:bg-slate-900/40 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Storage Location</span>
                        <span class="text-xs font-bold text-green-600 dark:text-green-400 mt-0.5 block">
                            {{ selectedDetailDoc.shelf_section ? selectedDetailDoc.shelf_section.label : 'Not Shelved' }}
                        </span>
                    </div>
                    <div class="bg-slate-50/50 dark:bg-slate-900/40 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Daily Stay Charge</span>
                        <span class="text-xs font-mono font-bold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ selectedDetailDoc.charge_per_day }} Birr / day</span>
                    </div>
                </div>

                <!-- Multiple scan photo attachment previews inside the detail modal -->
                <div class="text-left" v-if="selectedDetailDoc.image_paths && selectedDetailDoc.image_paths.length">
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2.5">Scan / Photo Attachments</h4>
                    <div class="grid grid-cols-4 gap-3">
                        <div v-for="(img, idx) in selectedDetailDoc.image_paths" :key="idx" class="relative group/modal-thumb cursor-pointer h-16 w-full rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-950 flex-shrink-0">
                            <a :href="img" target="_blank" title="View Full Image">
                                <img :src="img" class="h-full w-full object-cover group-hover/modal-thumb:scale-110 transition-transform" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <CancelBtn @click="isDocDetailModalOpen = false">Close</CancelBtn>
                <button 
                    v-if="selectedDetailDoc"
                    @click="downloadQRAsPNG(selectedDetailDoc.qr_code, selectedDetailDoc.title)"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-750 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors"
                >
                    💾 Download QR PNG
                </button>
            </template>
        </ModalWrapper>

        <!-- PRINT LAYOUT (HIDDEN UNTIL PRINTING) -->
        <div class="hidden print:block print:bg-white print:text-black print:min-h-screen print:p-6" id="qr-print-area">
            <div class="mb-6 pb-4 border-b border-gray-300 text-center">
                <h1 class="text-xl font-black text-gray-800">{{ selectedPrintShelf?.label }} - Office Shelving Sections</h1>
                <p class="text-xs text-gray-500 mt-1">Legacies names: {{ selectedPrintShelf?.alternative_name || 'None' }} • Generated on {{ new Date().toLocaleString() }}</p>
            </div>
            <div class="grid grid-cols-3 gap-6">
                <div v-for="sec in selectedPrintSections" :key="sec.id" class="border-2 border-dashed border-gray-400 rounded-2xl p-6 flex flex-col items-center justify-center text-center bg-white break-inside-avoid shadow-sm min-h-[220px]">
                    <div class="text-xs font-black text-indigo-900 uppercase tracking-wider mb-3">{{ sec.label }}</div>
                    <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=${encodeURIComponent(sec.qr_code)}`" class="h-32 w-32 object-contain" />
                    <div class="text-[10px] font-mono font-bold text-gray-500 mt-3 bg-gray-100 px-2 py-0.5 rounded">{{ sec.qr_code }}</div>
                </div>
            </div>
        </div>

        <!-- CUSTOM NOTIFICATION TOAST POPUP -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="toastConfig.show" class="fixed bottom-5 right-5 z-[1000000] max-w-sm w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-4 flex items-start gap-3 pointer-events-auto transition-colors duration-200">
                <div class="flex-shrink-0 mt-0.5">
                    <CheckCircleIcon v-if="toastConfig.type === 'success'" class="h-5 w-5 text-green-500" />
                    <ExclamationTriangleIcon v-else-if="toastConfig.type === 'warning'" class="h-5 w-5 text-amber-500" />
                    <ExclamationCircleIcon v-else-if="toastConfig.type === 'error'" class="h-5 w-5 text-red-500" />
                    <InformationCircleIcon v-else class="h-5 w-5 text-blue-500" />
                </div>
                <div class="flex-1">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ toastConfig.message }}</p>
                </div>
                <button @click="toastConfig.show = false" class="text-slate-400 hover:text-slate-650 flex-shrink-0">
                    <XMarkIcon class="h-4 w-4" />
                </button>
            </div>
        </Transition>

    </AdminLayout>
</template>

<style scoped>
@keyframes scanner-laser {
    0% { top: 0%; opacity: 0.8; }
    50% { top: 100%; opacity: 0.8; }
    100% { top: 0%; opacity: 0.8; }
}

.animate-scanner-laser {
    animation: scanner-laser 3s infinite linear;
}

.print-visible-block {
    display: block !important;
    visibility: visible !important;
    position: fixed !important;
    left: 0 !important;
    top: 0 !important;
    width: 100% !important;
    height: 100% !important;
    z-index: 999999 !important;
    background: white !important;
    color: black !important;
    overflow-y: auto !important;
}

@media print {
    body * {
        visibility: hidden !important;
    }
    #qr-print-area, #qr-print-area * {
        visibility: visible !important;
    }
    #qr-print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: block !important;
        background: white !important;
        color: black !important;
    }
    .break-inside-avoid {
        break-inside: avoid;
    }
}
</style>
