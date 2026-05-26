<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    ShieldCheckIcon, AcademicCapIcon,
    ChevronLeftIcon, ChevronRightIcon,
    BookOpenIcon, ArrowLeftIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const currentSlide = ref(1);
const totalSlides = 9;

// Interactive states
const unlockToEdit = ref(false);

const checklistItems = ref([
    { id: 1, text: 'Check the Branch KPIs weekly to monitor aggregate completion and compliance rates.', checked: false },
    { id: 2, text: 'Rebalance workloads by shifting client portfolios away from employees in the red zone (≥90%).', checked: false },
    { id: 3, text: 'Log complete physical coordinates (Shelf and Section mappings) for all physical folders.', checked: false },
    { id: 4, text: 'Verify submitted monthly ledgers and print official PDF reports containing verified QR links.', checked: false }
]);

function toggleCheck(item) {
    item.checked = !item.checked;
}

function nextSlide() {
    if (currentSlide.value < totalSlides) currentSlide.value++;
}

function prevSlide() {
    if (currentSlide.value > 1) currentSlide.value--;
}

function goToSlide(n) {
    if (n >= 1 && n <= totalSlides) currentSlide.value = n;
}

const handleKeydown = (e) => {
    if (e.key === 'ArrowRight' || e.key === ' ') {
        e.preventDefault();
        nextSlide();
    } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        prevSlide();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Head title="Admin Training - GGAA Systems" />

    <div class="min-h-screen bg-[#060a12] text-slate-100 font-sans flex flex-col justify-between overflow-x-hidden selection:bg-blue-600 selection:text-white">
        
        <!-- Top Navigation Bar -->
        <header class="w-full py-4 px-6 lg:px-12 flex justify-between items-center border-b border-slate-800 bg-[#060a12]/80 backdrop-blur-md sticky top-0 z-50">
            <Link href="/training" class="flex items-center gap-2 text-xs text-slate-400 hover:text-white transition-colors group">
                <ArrowLeftIcon class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                Back to Tracks
            </Link>
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 flex items-center justify-center font-bold font-outfit shadow-lg shadow-blue-500/20 text-white">
                    M
                </div>
                <div>
                    <span class="text-sm font-black tracking-wider uppercase font-outfit text-white">GGAA <span class="text-blue-400">Systems</span></span>
                    <span class="block text-[8px] font-bold text-slate-500 uppercase tracking-widest leading-none">Admin & Manager Control Course</span>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <span class="px-2.5 py-1 rounded-full bg-blue-500/10 text-blue-400 font-semibold border border-blue-500/20 hidden sm:inline">Admins & Managers</span>
            </div>
        </header>

        <!-- Slides Container -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 md:px-8 py-8 flex items-center justify-center relative">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-500/10 blur-[150px] rounded-full pointer-events-none"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[35%] h-[35%] bg-indigo-500/10 blur-[150px] rounded-full pointer-events-none"></div>

            <!-- SLIDE 1: WELCOME & TITLE -->
            <div v-if="currentSlide === 1" class="w-full flex flex-col items-center justify-center text-center py-12 relative z-10 space-y-6 max-w-3xl animate-fade-in">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-500/10 text-blue-400 text-xs font-black uppercase tracking-widest border border-blue-500/20">
                    <span class="h-2 w-2 rounded-full bg-blue-400 animate-ping"></span>
                    Module 2: Strategic Control Center
                </span>
                
                <h1 class="text-5xl lg:text-7xl font-black font-outfit tracking-tighter text-white leading-tight">
                    Admin Oversight & <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-blue-500 to-indigo-400">Resource Control</span>
                </h1>
                
                <p class="text-base md:text-lg text-slate-400 font-medium max-w-2xl mx-auto leading-relaxed">
                    Welcome to the GGAA Managerial & Administrative Guide. Learn to onboard new accounts, manage staff workloads via complexity modeling, organize physical/digital archives, and audit verified monthly ledgers.
                </p>

                <div class="pt-8 flex justify-center gap-4">
                    <button @click="nextSlide" class="px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow-xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all flex items-center gap-2 text-sm font-outfit">
                        Enter Manager Control
                        <ChevronRightIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- SLIDE 2: THE ADMIN DASHBOARD OVERVIEW -->
            <div v-if="currentSlide === 2" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">01 / Operations Pulse</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">The Admin Dashboard</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        The platform aggregates real-time metrics across your branch. These KPIs give senior staff an instant status check on firm performance and potential workflow bottlenecks.
                    </p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800 space-y-1">
                            <span class="text-[10px] text-blue-400 font-bold uppercase">Compliance Rate</span>
                            <h4 class="text-2xl font-bold text-white font-outfit">94.8%</h4>
                            <p class="text-[9px] text-slate-400">Percentage of monthly ledgers verified without revisions.</p>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800 space-y-1">
                            <span class="text-[10px] text-blue-400 font-bold uppercase">On-Time Completion</span>
                            <h4 class="text-2xl font-bold text-white font-outfit">89.2%</h4>
                            <p class="text-[9px] text-slate-400">Proportion of tasks completed on or before the due date.</p>
                        </div>
                    </div>
                </div>
                
                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm glass-card border border-slate-800 rounded-3xl p-5 space-y-4">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Branch Aggregate Status</h4>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-slate-900/40 border border-slate-850 rounded-xl">
                                <span class="text-xs text-slate-300">Active Onboarded Clients</span>
                                <span class="text-sm font-bold text-white">142 Firms</span>
                            </div>
                            
                            <div class="flex justify-between items-center p-3 bg-slate-900/40 border border-slate-850 rounded-xl">
                                <span class="text-xs text-slate-300">Staff Utilized Capacity</span>
                                <span class="text-sm font-bold text-blue-400">62% (Healthy)</span>
                            </div>

                            <div class="flex justify-between items-center p-3 bg-slate-900/40 border border-slate-850 rounded-xl">
                                <span class="text-xs text-slate-300">Total Pending Audits</span>
                                <span class="text-sm font-bold text-amber-400">8 Files</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 3: CLIENT ONBOARDING & PROFILES -->
            <div v-if="currentSlide === 3" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">02 / CRM Setup</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Onboarding & Client Profiles</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        When onboarding new business accounts, Admins capture key parameters that govern how tax calculations, workflows, and capacities behave.
                    </p>

                    <div class="space-y-3 font-medium text-slate-300 text-xs">
                        <div class="flex gap-2">
                            <span class="text-blue-400">📌</span>
                            <p><strong>TIN Mappings:</strong> Storing unique 10-digit Tax Identification Numbers to satisfy governmental tax portal requirements.</p>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-blue-400">🏢</span>
                            <p><strong>Legal Structure Assignment:</strong> Classifying businesses as PLC, Sole Proprietorship, Share Company, etc. This dictates standard tax filing formats.</p>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-blue-400">💳</span>
                            <p><strong>Linked Bank Channels:</strong> Associating corporate banking details with client records for monthly reconciliation tracking.</p>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm glass-card border border-slate-800 rounded-3xl p-6 space-y-4 shadow-xl">
                        <div class="flex justify-between items-center border-b border-slate-800 pb-2.5">
                            <span class="text-[9px] bg-blue-500/10 text-blue-400 font-bold px-2 py-0.5 rounded">Onboarding Console</span>
                            <span class="text-xs text-slate-400">Client ID: #C-9024</span>
                        </div>

                        <div class="space-y-3 text-xs text-slate-300">
                            <div>
                                <span class="text-[10px] text-slate-500 block">Company Name</span>
                                <span class="font-bold text-white text-sm">Sheger Foods PLC</span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-[10px] text-slate-500 block">TIN Number</span>
                                    <span class="font-bold text-white">0048291039</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-500 block">Legal Structure</span>
                                    <span class="font-bold text-white">PLC</span>
                                </div>
                            </div>

                            <div>
                                <span class="text-[10px] text-slate-500 block">Assigned Bank Channels</span>
                                <div class="flex gap-1.5 mt-1">
                                    <span class="px-2 py-0.5 bg-slate-900 border border-slate-800 rounded text-[9px] font-semibold text-slate-300">Commercial Bank (CBE)</span>
                                    <span class="px-2 py-0.5 bg-slate-900 border border-slate-800 rounded text-[9px] font-semibold text-slate-300">Awash Bank</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 4: STRATEGIC CAPACITY BALANCING -->
            <div v-if="currentSlide === 4" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">03 / Workload Optimization</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Active Workload Balancing</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        One of the Admin's most important functions is allocating clients and project shares to employees. The system calculates real-time workload percentages dynamically.
                    </p>

                    <div class="p-4 bg-blue-950/20 border border-blue-950/40 rounded-2xl text-xs space-y-2">
                        <h4 class="font-bold text-blue-400 uppercase tracking-widest text-[10px]">Optimal Allocation Guidelines</h4>
                        <p class="text-slate-300">Assign higher-complexity clients (e.g. PLC Audits worth 25 points) only to staff with significant remaining capacity.</p>
                        <p class="text-slate-400">If an employee’s capacity load bar turns red (≥90%), refrain from assigning further accounts to maintain quality and prevent backlogs.</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 space-y-4">
                    <div class="glass-card rounded-2xl p-5 border border-slate-800 space-y-4">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider">Staff Allocation Console</h4>
                        
                        <div class="space-y-3.5 text-xs">
                            <div class="space-y-1">
                                <div class="flex justify-between items-end">
                                    <span class="font-bold text-slate-200">Selamawit Tadesse (Senior Associate)</span>
                                    <span class="text-blue-400 font-bold">45 / 80 Pts (56%)</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-2">
                                    <div class="bg-blue-500 h-full rounded-full" style="width: 56%"></div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="flex justify-between items-end">
                                    <span class="font-bold text-slate-200">Kidus Yohannes (Junior Associate)</span>
                                    <span class="text-emerald-400 font-bold">20 / 60 Pts (33%)</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-2">
                                    <div class="bg-emerald-500 h-full rounded-full" style="width: 33%"></div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="flex justify-between items-end">
                                    <span class="font-bold text-slate-200">Tewodros Kassahun (Accountant)</span>
                                    <span class="text-rose-400 font-black">74 / 80 Pts (92%)</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-2">
                                    <div class="bg-rose-500 h-full rounded-full" style="width: 92%"></div>
                                </div>
                                <p class="text-[9px] text-rose-400">⚠️ Allocation locked automatically: High Workload alert.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 5: DOCUMENT ARCHIVING & PHYSICAL ARCHIVES -->
            <div v-if="currentSlide === 5" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">04 / Record Vaults</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Digital & Physical File Archiving</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        GGAA maintains both digital uploads and physical folders. The platform links them by tracking concrete storage coordinates for every business record.
                    </p>

                    <div class="space-y-3">
                        <div class="flex gap-3 items-start">
                            <span class="text-sm">📁</span>
                            <div>
                                <h5 class="text-xs font-bold text-white uppercase tracking-wider">Digital Attachment Vault</h5>
                                <p class="text-[11px] text-slate-400 mt-0.5">Scanned TIN certificates, business licenses, and VAT receipts are saved directly in secure cloud vaults.</p>
                            </div>
                        </div>
                        <div class="flex gap-3 items-start">
                            <span class="text-sm">🗄️</span>
                            <div>
                                <h5 class="text-xs font-bold text-white uppercase tracking-wider">Physical Archival Maps</h5>
                                <p class="text-[11px] text-slate-400 mt-0.5">Admins log physical coordinates: **Shelf name** (e.g. Shelf A) and **Shelf Section** (e.g. Section 4). This guarantees folders are retrieved manually in under 60 seconds.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm glass-card border border-slate-800 rounded-3xl p-5 space-y-4 shadow-xl">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider">Archival Coordinates Map</h4>
                        
                        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-850 space-y-2">
                            <span class="text-[9px] text-slate-500 uppercase font-black block">Digital Reference</span>
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-bold text-white">License_Scan_2026.pdf</span>
                                <span class="text-[10px] text-blue-400 font-bold">View Scan</span>
                            </div>
                        </div>

                        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-850 space-y-2">
                            <span class="text-[9px] text-slate-500 uppercase font-black block">Physical Coordinate Mapping</span>
                            <div class="grid grid-cols-2 gap-4 text-center">
                                <div class="p-2 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                                    <span class="text-[8px] text-blue-400 block uppercase">Cabinet / Shelf</span>
                                    <span class="text-sm font-bold text-white">Shelf B</span>
                                </div>
                                <div class="p-2 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                                    <span class="text-[8px] text-blue-400 block uppercase">Filing Section</span>
                                    <span class="text-sm font-bold text-white">Section 3</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 6: REUSABLE WORKFLOW TEMPLATES -->
            <div v-if="currentSlide === 6" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">05 / Standardized Workflows</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Task Templates & Autopilot</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        To maintain standard operations across all accounts, Admins utilize reusable **Task Templates**. Workflows are generated automatically based on these rules.
                    </p>

                    <div class="space-y-3 font-medium text-slate-300 text-xs">
                        <p>📅 <strong>Due Date Offsets:</strong> Automatically calculates target deadlines relative to the month-end (e.g. offset = 15 days means the task is due on Meskeram 15).</p>
                        <p>🔧 <strong>Service Mappings:</strong> Tasks are automatically generated and assigned when specific service subscriptions are activated for a client.</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 space-y-4">
                    <div class="glass-card rounded-2xl p-5 border border-slate-800 space-y-4">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider">Workflow Configuration</h4>
                        
                        <div class="space-y-3 text-xs">
                            <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-850">
                                <span class="text-[10px] text-slate-500 block uppercase">Template Title</span>
                                <span class="font-bold text-white">Monthly VAT Return & Filing</span>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-850">
                                    <span class="text-[10px] text-slate-500 block uppercase">Due Offset</span>
                                    <span class="font-bold text-white">10 Days from Month End</span>
                                </div>
                                <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-850">
                                    <span class="text-[10px] text-slate-500 block uppercase">Task Complexity</span>
                                    <span class="font-bold text-white">15 Points</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 7: INVISIBLE SECURITY -->
            <div v-if="currentSlide === 7" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">06 / Security Architecture</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Invisible Security Layers</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        The platform implements a state-of-the-art **Row-Level Security Structure** through Laravel's global database query scopes, ensuring strict data compartmentalization.
                    </p>

                    <div class="space-y-3 text-xs text-slate-300 font-medium">
                        <p>🔒 <strong>Branch Manager Scopes:</strong> Automatically restricts managers to files belonging only to clients inside their branch.</p>
                        <p>🔒 <strong>Employee Scopes:</strong> Restricts standard accountants to view only their assigned portfolios.</p>
                        <p>🔒 <strong>Client Portal Isolation:</strong> Isolates clients to only view their own profile and verified ledgers.</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm glass-card border border-blue-500/20 rounded-3xl p-5 space-y-3 shadow-xl">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider text-center">Row-Level Access Visualizer</h4>
                        
                        <div class="space-y-2.5 text-[10px]">
                            <div class="p-2.5 bg-blue-500/10 border border-blue-500/20 rounded-xl text-center">
                                <span class="font-bold text-white uppercase block">Super Admin & Finance Admin</span>
                                <span class="text-slate-400 mt-0.5 block">Full Global Visibility (All Branches)</span>
                            </div>

                            <div class="flex gap-2">
                                <div class="flex-1 p-2 bg-slate-900 border border-slate-800 rounded-xl text-center">
                                    <span class="font-bold text-white block">Manager: Bole Branch</span>
                                    <span class="text-slate-500 mt-0.5 block">Only Bole Clients</span>
                                </div>
                                <div class="flex-1 p-2 bg-slate-900 border border-slate-800 rounded-xl text-center">
                                    <span class="font-bold text-white block">Manager: Megenagna</span>
                                    <span class="text-slate-500 mt-0.5 block">Only Megenagna Clients</span>
                                </div>
                            </div>

                            <div class="p-2 bg-slate-950/40 border border-dashed border-slate-800 rounded-xl text-center">
                                <span class="font-bold text-white block">Client Portal Portal</span>
                                <span class="text-slate-500 mt-0.5 block">Only Verified Ledger Records Belonging to Client ID</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 8: LEDGER VERIFICATIONS & AUDITING -->
            <div v-if="currentSlide === 8" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">07 / Audit Protocol</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Ledger Audits & Sign-offs</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        When employees submit monthly financial ledgers, they enter a **Submitted** draft state. Admins review the entries and sign off on verified records.
                    </p>

                    <div class="space-y-3 text-xs text-slate-300 font-medium">
                        <p>👁️ <strong>Verification Locking:</strong> Approved ledgers are locked automatically. The client can only view **Verified** statements in their portal.</p>
                        <p>🔑 <strong>Unlock-to-Edit Switch:</strong> If corrections are needed, Admins toggle the secure switch to unlock the entries and make adjustments.</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm glass-card border border-blue-500/20 rounded-3xl p-5 space-y-4 shadow-xl">
                        <div class="flex justify-between items-center pb-2 border-b border-slate-800">
                            <span class="text-xs font-bold text-white uppercase tracking-wider">Ledger Audit Panel</span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold transition-colors"
                                  :class="unlockToEdit ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'"
                            >
                                {{ unlockToEdit ? 'UNLOCKED' : 'VERIFIED' }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center p-3 bg-slate-900 border border-slate-800 rounded-xl">
                            <div>
                                <h5 class="text-xs font-bold text-white">Unlock-to-Edit Mode</h5>
                                <p class="text-[9px] text-slate-400 mt-0.5">Unlocks verified monthly record for editing</p>
                            </div>
                            
                            <div @click="unlockToEdit = !unlockToEdit" 
                                 class="w-10 h-6 rounded-full p-1 flex items-center cursor-pointer transition-colors duration-200"
                                 :class="unlockToEdit ? 'bg-blue-600 justify-end' : 'bg-gray-700 justify-start'"
                            >
                                <div class="h-4 w-4 bg-white rounded-full flex items-center justify-center text-[8px] text-blue-600 font-bold shadow-md">
                                    {{ unlockToEdit ? '🔓' : '🔒' }}
                                </div>
                            </div>
                        </div>
                        <p class="text-[9px] text-center text-blue-400 font-semibold">💡 Click on the toggle switch to experience the locking engine!</p>
                    </div>
                </div>
            </div>

            <!-- SLIDE 9: ADMIN CHECKLIST & NEXT STEPS -->
            <div v-if="currentSlide === 9" class="w-full flex flex-col items-center justify-center py-6 text-center relative z-10 space-y-4 max-w-2xl animate-fade-in">
                <span class="text-xs font-black tracking-widest text-blue-400 uppercase">08 / Graduation</span>
                <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Your Admin Checklist</h2>
                <p class="text-slate-400 leading-relaxed font-medium text-sm">
                    Implement these strategic actions to maximize branch performance and ensure flawless record compliance:
                </p>
                
                <div class="p-6 glass-card rounded-3xl border border-slate-850 text-left w-full max-w-lg mx-auto space-y-3 text-xs text-slate-300">
                    <div v-for="item in checklistItems" :key="item.id" 
                         @click="toggleCheck(item)"
                         class="flex items-start gap-3 cursor-pointer select-none py-1 rounded hover:bg-slate-800/35 px-2 transition-colors"
                    >
                        <input type="checkbox" :checked="item.checked" class="accent-blue-500 h-4 w-4 shrink-0 rounded mt-0.5 cursor-pointer">
                        <span :class="{'line-through text-slate-500': item.checked}">{{ item.text }}</span>
                    </div>
                </div>

                <div class="pt-6">
                    <button @click="goToSlide(1)" class="px-6 py-3 bg-slate-900 border border-slate-800 hover:bg-slate-850 text-slate-300 font-bold rounded-2xl transition-all inline-flex items-center gap-2 text-xs">
                        🔄 Restart Presentation
                    </button>
                </div>
            </div>
        </main>

        <!-- Slides Navigation Controls -->
        <footer class="w-full py-6 px-6 lg:px-12 flex justify-between items-center border-t border-slate-800 bg-[#04060d] sticky bottom-0 z-50">
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500 font-medium">GGAA Systems Operations Slide Deck</span>
            </div>
            
            <div class="flex items-center gap-6">
                <button @click="prevSlide" class="p-2.5 rounded-xl border border-slate-800 hover:bg-slate-900/60 active:scale-95 transition-all text-slate-400 hover:text-white">
                    <ChevronLeftIcon class="h-4 w-4" />
                </button>
                
                <span class="text-xs font-bold tracking-wider font-outfit text-slate-400">
                    Slide <span class="text-blue-400">{{ currentSlide }}</span> of <span>{{ totalSlides }}</span>
                </span>
                
                <button @click="nextSlide" class="p-2.5 rounded-xl border border-slate-800 hover:bg-slate-900/60 active:scale-95 transition-all text-slate-400 hover:text-white">
                    <ChevronRightIcon class="h-4 w-4" />
                </button>
            </div>
            
            <div class="text-[10px] text-slate-500 uppercase font-black hidden md:block">
                Built with Precision.
            </div>
        </footer>

    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap');

.font-outfit {
    font-family: 'Outfit', sans-serif;
}

.glass-card {
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.glow-blue {
    box-shadow: 0 0 40px rgba(59, 130, 246, 0.15);
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
