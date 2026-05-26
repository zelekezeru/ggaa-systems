<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    UserIcon, AcademicCapIcon,
    ChevronLeftIcon, ChevronRightIcon,
    BookOpenIcon, ArrowLeftIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const currentSlide = ref(1);
const totalSlides = 9;

// Interactive states
const localDailyTasks = ref([
    { id: 1, title: 'Deliver Audit Response', location: 'Ministry of Revenues - Bole Branch', status: 'in_progress', icon: '✉️' },
    { id: 2, title: 'Client Consultation & Handover', location: 'Awash Bank Headquarters', status: 'pending', icon: '👤' }
]);

const checklistItems = ref([
    { id: 1, text: 'Check the Workspace Kanban Board first thing every morning for overdue/at-risk cards.', checked: false },
    { id: 2, text: 'Log daily errands (Ministry deliveries, audits) in your workspace Errand Log.', checked: false },
    { id: 3, text: 'Confirm your current active capacity remains under your allotted limit to maintain quality work.', checked: false },
    { id: 4, text: 'Upload necessary VAT return slips or tax booklets directly into the task detail modal.', checked: false },
    { id: 5, text: 'Submit completed monthly ledgers to managers for review in a timely fashion.', checked: false }
]);

function advanceDailyStatus(task) {
    if (task.status === 'pending') task.status = 'in_progress';
    else if (task.status === 'in_progress') task.status = 'done';
}

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
    <Head title="Employee Training - GGAA Systems" />

    <div class="min-h-screen bg-[#090d16] text-slate-100 font-sans flex flex-col justify-between overflow-x-hidden selection:bg-indigo-600 selection:text-white">
        
        <!-- Top Navigation Bar -->
        <header class="w-full py-4 px-6 lg:px-12 flex justify-between items-center border-b border-slate-800 bg-[#090d16]/80 backdrop-blur-md sticky top-0 z-50">
            <Link href="/training" class="flex items-center gap-2 text-xs text-slate-400 hover:text-white transition-colors group">
                <ArrowLeftIcon class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                Back to Tracks
            </Link>
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold font-outfit shadow-lg shadow-indigo-500/20 text-white">
                    G
                </div>
                <div>
                    <span class="text-sm font-black tracking-wider uppercase font-outfit text-white">GGAA <span class="text-indigo-400">Systems</span></span>
                    <span class="block text-[8px] font-bold text-slate-500 uppercase tracking-widest leading-none">Employee Workspace Training</span>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <span class="px-2.5 py-1 rounded-full bg-indigo-500/10 text-indigo-400 font-semibold border border-indigo-500/20 hidden sm:inline">Employees Track</span>
            </div>
        </header>

        <!-- Slides Container -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 md:px-8 py-8 flex items-center justify-center relative">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-indigo-500/10 blur-[150px] rounded-full pointer-events-none"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[35%] h-[35%] bg-purple-500/10 blur-[150px] rounded-full pointer-events-none"></div>

            <!-- SLIDE 1: WELCOME & TITLE -->
            <div v-if="currentSlide === 1" class="w-full flex flex-col items-center justify-center text-center py-12 relative z-10 space-y-6 max-w-3xl animate-fade-in">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-500/10 text-indigo-400 text-xs font-black uppercase tracking-widest border border-indigo-500/20">
                    <span class="h-2 w-2 rounded-full bg-indigo-400 animate-ping"></span>
                    Module 1: Productivity & Workflow
                </span>
                
                <h1 class="text-5xl lg:text-7xl font-black font-outfit tracking-tighter text-white leading-tight">
                    The Precision <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-indigo-500 to-purple-400">Employee Workspace</span>
                </h1>
                
                <p class="text-base md:text-lg text-slate-400 font-medium max-w-2xl mx-auto leading-relaxed">
                    Welcome to the GGAA Operations Training. Discover how to manage your daily tasks, optimize account allocations, track complexity parameters, and deliver flawless tax and accounting results with ease.
                </p>

                <div class="pt-8 flex justify-center gap-4">
                    <button @click="nextSlide" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow-xl shadow-indigo-600/30 hover:scale-105 active:scale-95 transition-all flex items-center gap-2 text-sm font-outfit">
                        Enter Workspace Guide
                        <ChevronRightIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- SLIDE 2: ROLE OVERVIEW -->
            <div v-if="currentSlide === 2" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">01 / Focus Areas</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Your Dual Responsibility</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        As a GGAA Accountant or Associate, your workday centers around two pillars designed to guarantee customer compliance and professional growth.
                    </p>
                    
                    <div class="space-y-4 pt-2">
                        <div class="flex gap-4 p-4 rounded-2xl bg-slate-900/50 border border-slate-800">
                            <div class="h-10 w-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 font-bold shrink-0">1</div>
                            <div>
                                <h4 class="font-bold text-white text-sm">Monthly Bookkeeping & Client Portfolios</h4>
                                <p class="text-xs text-slate-400 mt-1">Collecting inventory logs, cash register start/end receipts, VAT details, and submitting accurate monthly financial reports.</p>
                            </div>
                        </div>
                        
                        <div class="flex gap-4 p-4 rounded-2xl bg-slate-900/50 border border-slate-800">
                            <div class="h-10 w-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400 font-bold shrink-0">2</div>
                            <div>
                                <h4 class="font-bold text-white text-sm">Daily Tasks, Errands & Projects</h4>
                                <p class="text-xs text-slate-400 mt-1">Processing official errands, delivering mail to tax institutions, attending audits, and working on client projects within collaborative squads.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="glass-card shadow-2xl rounded-3xl p-6 w-full max-w-md border border-slate-800 space-y-4">
                        <div class="flex justify-between items-center pb-2 border-b border-slate-800">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Key KPI Focus</span>
                            <span class="text-xs font-black text-indigo-400">Target score: &ge;90%</span>
                        </div>
                        <ul class="space-y-3 text-xs text-slate-300">
                            <li class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                                <span><strong>On-Time Performance Rate:</strong> Prompt task updates.</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                                <span><strong>Workload Balance:</strong> Managing complexity weights.</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                                <span><strong>Accuracy & Audit:</strong> Zero verification errors from admins.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- SLIDE 3: KANBAN WORKSPACE (SNAPSHOT MOCK-UP) -->
            <div v-if="currentSlide === 3" class="w-full flex flex-col py-2 relative z-10">
                <div class="text-center max-w-2xl mx-auto mb-6 space-y-1">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">02 / Live Interface</span>
                    <h2 class="text-2xl md:text-3xl font-extrabold font-outfit text-white tracking-tight">The Kanban Workspace</h2>
                    <p class="text-xs text-slate-400 font-medium">Your primary dashboard. Track active files and advance their compliance stages.</p>
                </div>

                <div class="w-full bg-[#0c1221] rounded-2xl border border-slate-800 overflow-hidden shadow-2xl p-4 space-y-4">
                    <div class="flex flex-wrap justify-between items-center gap-3 pb-3 border-b border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-300">Accountant: <strong>Zerihun Abebe</strong></span>
                        </div>
                        <div class="flex items-center gap-4 text-[10px]">
                            <span class="font-bold text-slate-400">Completion: <strong class="text-emerald-400">67%</strong></span>
                            <span class="font-bold text-slate-400">Active Load: <strong class="text-amber-400">45 / 80 Pts</strong></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="bg-slate-900/40 rounded-xl p-2.5 border border-slate-800/80 space-y-2">
                            <div class="flex justify-between items-center text-[10px] uppercase font-bold text-amber-400 tracking-wider pb-1 border-b border-amber-500/20">
                                <span>Pending Docs</span>
                                <span class="px-1.5 py-0.2 bg-amber-500/10 text-amber-400 rounded">1</span>
                            </div>
                            <div class="bg-[#121a2e] rounded-lg p-2.5 border-l-4 border-l-amber-500 border border-slate-800 space-y-1">
                                <span class="text-[8px] bg-slate-850 text-slate-400 px-1 rounded font-bold uppercase">Tax File</span>
                                <h4 class="text-xs font-bold text-white truncate">Zenith PLC</h4>
                                <p class="text-[9px] text-slate-400">Waiting on TIN Certificate</p>
                                <div class="flex justify-between items-center pt-2 text-[8px] text-slate-500 border-t border-slate-800">
                                    <span>Due: 18 Meskeram</span>
                                    <span class="text-amber-500">🟡 Medium Risk</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-900/40 rounded-xl p-2.5 border border-slate-800/80 space-y-2">
                            <div class="flex justify-between items-center text-[10px] uppercase font-bold text-blue-400 tracking-wider pb-1 border-b border-blue-500/20">
                                <span>To Do</span>
                                <span class="px-1.5 py-0.2 bg-blue-500/10 text-blue-400 rounded">2</span>
                            </div>
                            <div class="bg-[#121a2e] rounded-lg p-2.5 border-l-4 border-l-emerald-500 border border-slate-800 space-y-1">
                                <span class="text-[8px] bg-indigo-500/15 text-indigo-400 px-1 rounded font-bold uppercase">Audit Prep</span>
                                <h4 class="text-xs font-bold text-white truncate">Alpha Traders</h4>
                                <div class="flex justify-between items-center pt-2 text-[8px] text-slate-500">
                                    <span>Due: 22 Meskeram</span>
                                    <span class="text-emerald-500">🟢 Low Risk</span>
                                </div>
                            </div>
                            <div class="bg-[#121a2e] rounded-lg p-2.5 border-l-4 border-l-rose-500 border border-slate-800 space-y-1">
                                <span class="text-[8px] bg-indigo-500/15 text-indigo-400 px-1 rounded font-bold uppercase">Monthly Ledger</span>
                                <h4 class="text-xs font-bold text-white truncate">GCS Logistics</h4>
                                <div class="flex justify-between items-center pt-2 text-[8px] text-slate-500">
                                    <span class="text-rose-400 font-bold bg-rose-500/5 px-1 rounded">2d Overdue</span>
                                    <span class="text-rose-500">🔴 High Risk</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-900/40 rounded-xl p-2.5 border border-slate-800/80 space-y-2">
                            <div class="flex justify-between items-center text-[10px] uppercase font-bold text-purple-400 tracking-wider pb-1 border-b border-purple-500/20">
                                <span>Under Review</span>
                                <span class="px-1.5 py-0.2 bg-purple-500/10 text-purple-400 rounded">1</span>
                            </div>
                            <div class="bg-[#121a2e] rounded-lg p-2.5 border-l-4 border-l-emerald-500 border border-slate-800 opacity-90 space-y-1">
                                <span class="text-[8px] bg-slate-850 text-slate-400 px-1 rounded font-bold uppercase">VAT Return</span>
                                <h4 class="text-xs font-bold text-white truncate">Nile Import-Export</h4>
                                <div class="flex justify-between items-center pt-2 text-[8px] text-slate-500">
                                    <span>Submitted: Today</span>
                                    <span class="text-purple-400">Awaiting Admin Signature</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-900/40 rounded-xl p-2.5 border border-slate-800/80 space-y-2">
                            <div class="flex justify-between items-center text-[10px] uppercase font-bold text-emerald-400 tracking-wider pb-1 border-b border-emerald-500/20">
                                <span>Done</span>
                                <span class="px-1.5 py-0.2 bg-emerald-500/10 text-emerald-400 rounded">1</span>
                            </div>
                            <div class="bg-[#121a2e] rounded-lg p-2.5 border-l-4 border-l-emerald-500 border border-slate-800 opacity-75 space-y-1">
                                <span class="text-[8px] bg-emerald-500/10 text-emerald-400 px-1 rounded font-bold uppercase">Income Tax</span>
                                <h4 class="text-xs font-bold text-slate-300 truncate">Haddis Supermarket</h4>
                                <div class="flex justify-between items-center pt-2 text-[8px] text-slate-500">
                                    <span class="text-emerald-400 font-bold">Approved</span>
                                    <span>14 Meskeram</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-xl flex flex-col sm:flex-row justify-between gap-2 text-xs text-indigo-300">
                        <span class="flex items-center gap-1.5">
                            <span class="text-indigo-400">ℹ️</span>
                            <span><strong>Workspace Navigation Rule:</strong> Move cards sequentially: <strong>Pending Docs ➔ To Do ➔ Under Review ➔ Done.</strong></span>
                        </span>
                        <span class="font-semibold text-rose-400">⚠️ Done requires Admin signature; cannot drag directly to Done.</span>
                    </div>
                </div>
            </div>

            <!-- SLIDE 4: WORKLOAD CAPACITY & COMPLEXITY -->
            <div v-if="currentSlide === 4" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">03 / Workload Balance</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Understanding Your Capacity Load</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        To prevent burnout and maintain supreme auditing quality, GGAA utilizes an automated **Capacity Balancing System**. Your workload is measured in **Complexity Points**, not just file counts.
                    </p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-850">
                            <span class="text-xs text-indigo-400 font-bold uppercase">Client Complexity</span>
                            <p class="text-xl font-bold font-outfit text-white mt-1">10–30 Pts</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Based on client transaction volume and business sector risk factors.</p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-850">
                            <span class="text-xs text-indigo-400 font-bold uppercase">Project Shares</span>
                            <p class="text-xl font-bold font-outfit text-white mt-1">5–20 Pts</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Assigned points based on your specific contribution weight in cross-functional squads.</p>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 space-y-4">
                    <div class="glass-card rounded-2xl p-6 border border-slate-850 space-y-6">
                        <h4 class="text-sm font-bold text-white uppercase tracking-wider">Capacity Visualizer Demo</h4>
                        
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-slate-300">Daily Optimal Workload</span>
                                <span class="text-indigo-400 font-bold">55% Load</span>
                            </div>
                            <div class="w-full bg-slate-800 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-full rounded-full" style="width: 55%"></div>
                            </div>
                            <p class="text-[10px] text-slate-500">Normal operations, standard tasks, plenty of room for focus.</p>
                        </div>
                        
                        <div class="space-y-1.5 pt-2 border-t border-slate-850">
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-slate-300">Over-Capacity Warning Trigger</span>
                                <span class="text-rose-400 font-black">92% Critical Load</span>
                            </div>
                            <div class="w-full bg-slate-800 rounded-full h-2">
                                <div class="bg-gradient-to-r from-orange-500 to-rose-600 h-full rounded-full" style="width: 92%"></div>
                            </div>
                            <p class="text-[10px] text-rose-400 font-medium">⚠️ Alerts Manager immediately. Restricts new client assignments automatically.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 5: ATTENTION REQUIRED RADAR -->
            <div v-if="currentSlide === 5" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">04 / Incident Control</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">The Overdue & At-Risk Radar</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        The platform contains an integrated **At-Risk Engine** that highlights clients whose compliance scores are dropping or whose document submission deadlines are dangerously close.
                    </p>

                    <div class="space-y-3">
                        <div class="flex gap-3 items-start">
                            <span class="text-lg shrink-0">🔴</span>
                            <div>
                                <h5 class="text-xs font-bold text-white uppercase tracking-wider">High Risk Alerts</h5>
                                <p class="text-xs text-slate-400 mt-0.5">Tasks overdue by >24 hours, missing legal document attachments, or clients with immediate tax penalty warnings.</p>
                            </div>
                        </div>
                        <div class="flex gap-3 items-start">
                            <span class="text-lg shrink-0">🟡</span>
                            <div>
                                <h5 class="text-xs font-bold text-white uppercase tracking-wider">Medium Risk Alerts</h5>
                                <p class="text-xs text-slate-400 mt-0.5">Filing deadline due within 48 hours, or waiting on client confirmations for longer than 3 standard working days.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm glass-card border border-red-500/20 rounded-2xl p-5 space-y-4 shadow-xl">
                        <div class="flex items-center gap-2 text-xs font-black text-rose-400 uppercase tracking-widest pb-2 border-b border-red-500/10">
                            <span class="h-2 w-2 rounded-full bg-rose-500 animate-ping"></span>
                            Attention Required (Radar Active)
                        </div>
                        
                        <div class="p-3 bg-red-950/20 border-l-4 border-l-red-500 border border-red-950/30 rounded-r-lg space-y-1">
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] font-bold text-slate-400">Tana Industrial Plc</span>
                                <span class="text-[8px] bg-red-500/20 text-red-400 px-1.5 rounded font-black">🔴 HIGH RISK</span>
                            </div>
                            <h4 class="text-xs font-bold text-white">Meskeram VAT Ledger Form</h4>
                            <p class="text-[9px] text-red-400"> Fines will accrue in 2 days. Draft not created yet.</p>
                        </div>

                        <div class="p-3 bg-yellow-950/10 border-l-4 border-l-yellow-500 border border-yellow-950/20 rounded-r-lg space-y-1">
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] font-bold text-slate-400">Gibe Beverages</span>
                                <span class="text-[8px] bg-yellow-500/20 text-yellow-400 px-1.5 rounded font-black">🟡 MEDIUM RISK</span>
                            </div>
                            <h4 class="text-xs font-bold text-white">Manual Receipt Entry Auditing</h4>
                            <p class="text-[9px] text-yellow-400">Waiting on Client to deliver physical receipt booklet #3.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 6: DAILY TASKS & ERRANDS -->
            <div v-if="currentSlide === 6" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">05 / Field Operations</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Daily Errand Management</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        Accountants often perform dynamic errands on-site or at government offices. Use the **Daily Tasks Tracker** on your workspace dashboard to log these activities, ensuring transparency and capacity balancing.
                    </p>
                    
                    <div class="p-4 bg-indigo-950/20 rounded-2xl border border-indigo-950/40 space-y-3">
                        <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-widest">Errand Life-Cycle Stages</h4>
                        <div class="grid grid-cols-3 gap-2 text-center text-[10px] font-bold">
                            <div class="p-2 bg-amber-500/10 border border-amber-500/20 text-amber-400 rounded-lg">Pending</div>
                            <div class="p-2 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-lg">In Progress</div>
                            <div class="p-2 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg">Done</div>
                        </div>
                        <p class="text-[10px] text-slate-400">Simply click the status badge of the errand card to instantly advance its state on the go via your mobile or tablet browser!</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 space-y-4">
                    <div class="glass-card rounded-2xl p-5 border border-slate-800 space-y-3">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-2">Workspace: Interactive Errands Panel</h4>
                        
                        <div v-for="task in localDailyTasks" :key="task.id" 
                             @click="advanceDailyStatus(task)"
                             class="flex items-center justify-between p-3 bg-slate-900/60 border border-slate-800 rounded-xl cursor-pointer hover:bg-slate-800 hover:scale-[1.02] transition-all"
                        >
                            <div class="flex items-center gap-3">
                                <span class="h-8 w-8 rounded-lg bg-indigo-500/10 flex items-center justify-center text-sm">{{ task.icon }}</span>
                                <div>
                                    <h5 class="text-xs font-bold text-white">{{ task.title }}</h5>
                                    <p class="text-[9px] text-slate-400">{{ task.location }}</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 text-[9px] font-bold rounded-full border transition-all"
                                  :class="[
                                      task.status === 'pending' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 
                                      task.status === 'in_progress' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' : 
                                      'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                                  ]"
                            >
                                {{ task.status === 'pending' ? 'Pending' : task.status === 'in_progress' ? 'In Progress' : 'Done' }}
                            </span>
                        </div>
                        <p class="text-[9px] text-center text-indigo-400 font-semibold mt-2">💡 Click on the errand cards to advance their state!</p>
                    </div>
                </div>
            </div>

            <!-- SLIDE 7: SQUAD COLLABORATION & PROJECTS -->
            <div v-if="currentSlide === 7" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">06 / Teamwork</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Squad Audits & Team Projects</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        Major corporate clients often require squad effort rather than single-accoutant assignments. The **Team Projects Portal** enhances squad operations.
                    </p>
                    
                    <div class="space-y-3 font-medium">
                        <div class="flex gap-3">
                            <span class="text-indigo-400">🎯</span>
                            <p class="text-xs text-slate-300"><strong>Team Leader:</strong> Directs tasks, monitors sub-projects, reviews draft ledgers, submits reports to admins.</p>
                        </div>
                        <div class="flex gap-3">
                            <span class="text-indigo-400">📊</span>
                            <p class="text-xs text-slate-300"><strong>Complexity Share:</strong> Project load points are split proportionally among members based on specific workload distributions.</p>
                        </div>
                        <div class="flex gap-3">
                            <span class="text-indigo-400">💬</span>
                            <p class="text-xs text-slate-300"><strong>Embedded Messages & Vault:</strong> Every team project has its own workspace thread and file repository for real-time squad communication.</p>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 space-y-4">
                    <div class="glass-card rounded-2xl p-5 border border-slate-800 space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-800 pb-2.5">
                            <div>
                                <span class="text-[9px] bg-indigo-500/20 text-indigo-400 font-bold px-1.5 py-0.5 rounded">Project Squad</span>
                                <h4 class="text-sm font-bold text-white mt-1">Abay Textile Audit 2026</h4>
                            </div>
                            <span class="text-[10px] text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full font-bold">Active</span>
                        </div>
                        
                        <div class="space-y-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Squad Members</span>
                            <div class="flex gap-2">
                                <span class="h-6 w-6 rounded-full bg-indigo-600 flex items-center justify-center text-[9px] font-bold text-white ring-2 ring-slate-800" title="Zerihun - Leader">ZA</span>
                                <span class="h-6 w-6 rounded-full bg-purple-600 flex items-center justify-center text-[9px] font-bold text-white ring-2 ring-slate-800" title="Lydia - 40% Share">LA</span>
                                <span class="h-6 w-6 rounded-full bg-blue-600 flex items-center justify-center text-[9px] font-bold text-white ring-2 ring-slate-800" title="Kidus - 30% Share">KK</span>
                            </div>
                        </div>

                        <div class="space-y-2 border-t border-slate-800 pt-3">
                            <div class="flex justify-between text-[10px] font-bold text-slate-400">
                                <span>Project To-Dos</span>
                                <span>2 / 3 Tasks Done</span>
                            </div>
                            <div class="space-y-1">
                                <label class="flex items-center gap-2 text-xs text-slate-300">
                                    <input type="checkbox" checked disabled class="accent-indigo-500 rounded">
                                    <span class="line-through text-slate-500">Collect bank statements</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-300">
                                    <input type="checkbox" checked disabled class="accent-indigo-500 rounded">
                                    <span class="line-through text-slate-500">Verify warehouse inventories</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-300">
                                    <input type="checkbox" disabled class="accent-indigo-500 rounded">
                                    <span>Compile and reconcile depreciation logs</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 8: PERFORMANCE & REWARDS -->
            <div v-if="currentSlide === 8" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">07 / Gamification</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Performance Mappings & Gamified Badges</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        At GGAA, excellence does not go unnoticed. Your workspace calculates a monthly **Weighted Performance Score** based on deadlines and task complexities. Slay your targets to unlock exclusive awards!
                    </p>
                    
                    <div class="space-y-3.5 text-xs text-slate-300 font-medium">
                        <p>🎯 <strong>On-Time Done (Before/On Due Date):</strong> Yields 100% complexity points to your performance tally.</p>
                        <p>⏰ <strong>Late Done (After Due Date):</strong> Yields 50% complexity points to your performance tally.</p>
                        <p>🏆 <strong>Monthly Achievements:</strong> Consistent high scores unlock special profile badges observable by senior admins.</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm glass-card border border-indigo-500/20 rounded-3xl p-6 space-y-4">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider text-center">Your Profile Achievements</h4>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-3 bg-slate-900/60 border border-slate-850 rounded-2xl text-center space-y-1">
                                <span class="text-2xl">⚡</span>
                                <h5 class="text-xs font-bold text-white">Speed Demon</h5>
                                <p class="text-[8px] text-indigo-400 font-bold uppercase">5 Tasks Done Early</p>
                            </div>

                            <div class="p-3 bg-slate-900/60 border border-slate-850 rounded-2xl text-center space-y-1">
                                <span class="text-2xl">🛡️</span>
                                <h5 class="text-xs font-bold text-white">Ironclad Auditor</h5>
                                <p class="text-[8px] text-indigo-400 font-bold uppercase">100% Tax Accuracy</p>
                            </div>

                            <div class="p-3 bg-slate-900/60 border border-slate-850 rounded-2xl text-center space-y-1">
                                <span class="text-2xl">🤝</span>
                                <h5 class="text-xs font-bold text-white">Squad Anchor</h5>
                                <p class="text-[8px] text-indigo-400 font-bold uppercase">Co-Led 3 Squad Audits</p>
                            </div>

                            <div class="p-3 bg-slate-900/60 border border-slate-850 rounded-2xl text-center space-y-1 opacity-50 border-dashed animate-pulse">
                                <span class="text-2xl">👑</span>
                                <h5 class="text-xs font-bold text-white">Grandmaster</h5>
                                <p class="text-[8px] text-slate-500 font-bold uppercase">98% MTD Score</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 9: INTERACTIVE CHECKLIST & NEXT STEPS -->
            <div v-if="currentSlide === 9" class="w-full flex flex-col items-center justify-center py-6 text-center relative z-10 space-y-4 max-w-2xl">
                <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">08 / Graduation</span>
                <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Your Employee Work Checklist</h2>
                <p class="text-slate-400 leading-relaxed font-medium text-sm">
                    Keep this standard operational checklist handy as you perform your daily responsibilities on GGAA-Systems:
                </p>
                
                <div class="p-6 glass-card rounded-3xl border border-slate-850 text-left w-full max-w-lg mx-auto space-y-3 text-xs text-slate-300">
                    <div v-for="item in checklistItems" :key="item.id" 
                         @click="toggleCheck(item)"
                         class="flex items-start gap-3 cursor-pointer select-none py-1 rounded hover:bg-slate-800/35 px-2 transition-colors"
                    >
                        <input type="checkbox" :checked="item.checked" class="accent-indigo-500 h-4 w-4 shrink-0 rounded mt-0.5 cursor-pointer">
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
        <footer class="w-full py-6 px-6 lg:px-12 flex justify-between items-center border-t border-slate-800 bg-[#070b13] sticky bottom-0 z-50">
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500 font-medium">GGAA Systems Operations Slide Deck</span>
            </div>
            
            <div class="flex items-center gap-6">
                <button @click="prevSlide" class="p-2.5 rounded-xl border border-slate-800 hover:bg-slate-900/60 active:scale-95 transition-all text-slate-400 hover:text-white">
                    <ChevronLeftIcon class="h-4 w-4" />
                </button>
                
                <span class="text-xs font-bold tracking-wider font-outfit text-slate-400">
                    Slide <span class="text-indigo-400">{{ currentSlide }}</span> of <span>{{ totalSlides }}</span>
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

.glow-indigo {
    box-shadow: 0 0 40px rgba(99, 102, 241, 0.15);
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
