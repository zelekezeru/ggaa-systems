<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    BanknotesIcon, AcademicCapIcon,
    ChevronLeftIcon, ChevronRightIcon,
    BookOpenIcon, ArrowLeftIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const currentSlide = ref(1);
const totalSlides = 9;

// Interactive states: live tax calculator
const mockSales = ref(302500);
const mockCogs = ref(125000);
const mockExpenses = ref(32450);

const computedNetProfit = computed(() => mockSales.value - mockCogs.value - mockExpenses.value);
const computedProfitTax = computed(() => {
    const nibt = computedNetProfit.value;
    if (nibt <= 0) return 0;
    return Math.max(0, (nibt * 0.35) - 24600);
});

const checklistItems = ref([
    { id: 1, text: 'Reconcile cash machine ranges to confirm start and end numbers correspond directly to physical booklet Z-reports.', checked: false },
    { id: 2, text: 'Audit COGS starting inventory against the ending stock values booked in the previous verified month.', checked: false },
    { id: 3, text: 'Check the 17 operational expenses, verifying that transport, electric, rent, and depreciation calculations comply with local guidelines.', checked: false },
    { id: 4, text: 'Sign off and lock the ledger entries, changing their status to Verified, then generate secure verifiable PDF client reports.', checked: false }
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

// Helper formatting
const fmt = (v) => new Intl.NumberFormat('en-ET', { minimumFractionDigits: 2 }).format(v);
</script>

<template>
    <Head title="Finance Training - GGAA Systems" />

    <div class="min-h-screen bg-[#050b0a] text-slate-100 font-sans flex flex-col justify-between overflow-x-hidden selection:bg-emerald-600 selection:text-white">
        
        <!-- Top Navigation Bar -->
        <header class="w-full py-4 px-6 lg:px-12 flex justify-between items-center border-b border-slate-800 bg-[#050b0a]/80 backdrop-blur-md sticky top-0 z-50">
            <Link href="/training" class="flex items-center gap-2 text-xs text-slate-400 hover:text-white transition-colors group">
                <ArrowLeftIcon class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                Back to Tracks
            </Link>
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center font-bold font-outfit shadow-lg shadow-emerald-500/20 text-white">
                    F
                </div>
                <div>
                    <span class="text-sm font-black tracking-wider uppercase font-outfit text-white">GGAA <span class="text-emerald-400">Systems</span></span>
                    <span class="block text-[8px] font-bold text-slate-500 uppercase tracking-widest leading-none">Finance & Bookkeeping Course</span>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20 hidden sm:inline">Finance Track</span>
            </div>
        </header>

        <!-- Slides Container -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 md:px-8 py-8 flex items-center justify-center relative">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-emerald-500/10 blur-[150px] rounded-full pointer-events-none"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[35%] h-[35%] bg-teal-500/10 blur-[150px] rounded-full pointer-events-none"></div>

            <!-- SLIDE 1: WELCOME & TITLE -->
            <div v-if="currentSlide === 1" class="w-full flex flex-col items-center justify-center text-center py-12 relative z-10 space-y-6 max-w-3xl animate-fade-in">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-black uppercase tracking-widest border border-emerald-500/20">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                    Module 3: Ledger Verification & Billings
                </span>
                
                <h1 class="text-5xl lg:text-7xl font-black font-outfit tracking-tighter text-white leading-tight">
                    Financial Ledger & <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-400">Billing Operations</span>
                </h1>
                
                <p class="text-base md:text-lg text-slate-400 font-medium max-w-2xl mx-auto leading-relaxed">
                    Welcome to the GGAA Finance & Bookkeeping Training. Reconcile monthly draft ledgers, compute corporate business income taxes, manage invoice cycles, and generate verifiable client PDF reports.
                </p>

                <div class="pt-8 flex justify-center gap-4">
                    <button @click="nextSlide" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-xl shadow-emerald-600/30 hover:scale-105 active:scale-95 transition-all flex items-center gap-2 text-sm font-outfit">
                        Enter Ledger Guides
                        <ChevronRightIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- SLIDE 2: FINANCIAL SCOPE OVERVIEW -->
            <div v-if="currentSlide === 2" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">01 / Scope of Work</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Your Financial Domain</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        At GGAA, the finance team acts as the final gatekeeper. Your primary responsibilities are billing clients accurately and auditing monthly tax/bookkeeping computations before official filing.
                    </p>
                    
                    <div class="space-y-4 pt-2 font-medium">
                        <div class="flex gap-4 p-4 rounded-2xl bg-slate-900/50 border border-slate-800">
                            <div class="h-10 w-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 font-bold shrink-0">1</div>
                            <div>
                                <h4 class="font-bold text-white text-sm">Service Billings & Invoicing</h4>
                                <p class="text-xs text-slate-400 mt-1">Generating client service bills based on complexity contracts, collecting payments, and managing tax invoice logs.</p>
                            </div>
                        </div>
                        
                        <div class="flex gap-4 p-4 rounded-2xl bg-slate-900/50 border border-slate-800">
                            <div class="h-10 w-10 rounded-xl bg-teal-500/10 flex items-center justify-center text-teal-400 font-bold shrink-0">2</div>
                            <div>
                                <h4 class="font-bold text-white text-sm">Monthly Ledger Verification</h4>
                                <p class="text-xs text-slate-400 mt-1">Reconciling drafts compiled by employees. Reviewing cash machine ranges, VAT reports, bank statements, and locking verified records.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="glass-card shadow-2xl rounded-3xl p-6 w-full max-w-md border border-slate-850 space-y-4">
                        <div class="flex justify-between items-center pb-2 border-b border-slate-800">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Finance SLA Targets</span>
                            <span class="text-xs font-black text-emerald-400">Compliance Focused</span>
                        </div>
                        <ul class="space-y-3 text-xs text-slate-300">
                            <li class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                <span><strong>Audit Accuracy:</strong> Strict checking of starting document ranges.</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                <span><strong>Tax Portal Deadlines:</strong> Sign off before the 30th of each Ethiopian month.</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                <span><strong>Receivables Oversight:</strong> Tracking pending invoices weekly.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- SLIDE 3: BILLING & INVOICING -->
            <div v-if="currentSlide === 3" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">02 / Revenue Channels</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Billing & Service Invoices</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        The platform contains an integrated invoicing system. You can create invoices mapped to specific accounting services (e.g. Audit, Reconciliations, Corporate Filing).
                    </p>

                    <div class="space-y-3 text-xs text-slate-300">
                        <div class="flex gap-2">
                            <span class="text-emerald-400">⚙️</span>
                            <p><strong>Invoice Status Lifecycle:</strong> Mapped through three concrete stages: <strong>Draft ➔ Pending Payment ➔ Paid.</strong></p>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-emerald-400">💳</span>
                            <p><strong>Payment Record Mapping:</strong> Every payment transaction registers physical payment dates, transaction references, and recipient bank channels.</p>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm glass-card border border-slate-800 rounded-3xl p-5 space-y-4 shadow-xl">
                        <div class="flex justify-between items-center pb-2 border-b border-slate-800">
                            <div>
                                <span class="text-[8px] bg-emerald-500/10 text-emerald-400 font-bold px-1.5 py-0.5 rounded">Invoice Console</span>
                                <h4 class="text-xs font-bold text-white mt-1">Invoice #INV-2026-081</h4>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">Pending Payment</span>
                        </div>

                        <div class="space-y-2.5 text-xs text-slate-300">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Client:</span>
                                <span class="font-bold text-white">Nile Textile Corp</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Service rendered:</span>
                                <span class="font-bold text-white">Annual Auditing Services</span>
                            </div>
                            <div class="flex justify-between border-t border-slate-800 pt-2 font-bold">
                                <span class="text-slate-400">Grand Total:</span>
                                <span class="text-white text-sm">45,000.00 ETB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 4: THE BOOKKEEPING ENTRY ENGINE (PART 1 & 2) -->
            <div v-if="currentSlide === 4" class="w-full flex flex-col py-2 relative z-10">
                <div class="text-center max-w-2xl mx-auto mb-6 space-y-1">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">03 / Bookkeeping Core</span>
                    <h2 class="text-2xl md:text-3xl font-extrabold font-outfit text-white tracking-tight">Reconciliation: Sales & Inventory Formulas</h2>
                    <p class="text-xs text-slate-400 font-medium">Entering data in `Entry.vue`. Precision calculations run in real-time as you key values.</p>
                </div>

                <div class="w-full bg-[#081013] rounded-2xl border border-slate-800 overflow-hidden shadow-2xl p-4 space-y-4">
                    <div class="flex flex-wrap justify-between items-center gap-3 pb-3 border-b border-slate-800">
                        <span class="text-xs font-bold text-slate-200">Reconciliation Form ➔ <strong>Nile Textile Corp (Meskeram 2026)</strong></span>
                        <span class="px-2 py-0.5 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded text-[9px] font-bold">DRAFT - IN PROGRESS</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-900/50 border border-slate-850 rounded-xl space-y-3">
                            <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-widest border-b border-slate-800 pb-1">1. Sales & Document Mapping</h4>
                            <div class="grid grid-cols-2 gap-3 text-[10px]">
                                <div>
                                    <span class="text-slate-500 block">Cash Machine Sales</span>
                                    <span class="font-bold text-white text-sm">240,500.00 ETB</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Manual Receipt Sales</span>
                                    <span class="font-bold text-white text-sm">62,000.00 ETB</span>
                                </div>
                            </div>
                            <div class="p-2.5 bg-slate-950/40 rounded-lg border border-slate-850 text-[9px] text-slate-400 space-y-1">
                                <div class="flex justify-between">
                                    <span>Cash Receipts Start/End:</span>
                                    <span class="font-bold text-white">10001 ➔ 10320 (319 Receipts)</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Manual Receipts Start/End:</span>
                                    <span class="font-bold text-white">4001 ➔ 4050 (49 Receipts)</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-900/50 border border-slate-850 rounded-xl space-y-3">
                            <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-widest border-b border-slate-800 pb-1">2. Cost of Goods Sold (COGS)</h4>
                            <div class="grid grid-cols-3 gap-2 text-[10px]">
                                <div>
                                    <span class="text-slate-500 block">Beginning Stock</span>
                                    <span class="font-bold text-white">120,000.00</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Purchases</span>
                                    <span class="font-bold text-white">90,000.00</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Ending Stock</span>
                                    <span class="font-bold text-white">85,000.00</span>
                                </div>
                            </div>
                            
                            <div class="flex justify-between text-[10px] pt-1.5 border-t border-slate-800">
                                <span class="text-slate-400">Formula Computed COGS:</span>
                                <span class="font-bold text-emerald-400">125,000.00 ETB</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-2.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-[10px] text-emerald-400 font-medium">
                        🧮 <strong>COGS Formula:</strong> Beginning Inventory + Purchases - Ending Inventory = Cost of Goods Sold (COGS). Computes instantly as keys change.
                    </div>
                </div>
            </div>

            <!-- SLIDE 5: EXPENSES & VAT MAPPINGS (PART 3 & 4) -->
            <div v-if="currentSlide === 5" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">04 / Expense & Tax Auditing</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Expenses & VAT Mappings</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        The ledger tracks **17 Standard Operational Expenses** along with rigorous VAT reporting fields. Make sure each expense is entered accurately against physical tax vouchers.
                    </p>

                    <div class="grid grid-cols-2 gap-4 text-xs font-semibold">
                        <div class="p-3 bg-slate-900/50 rounded-xl border border-slate-800">
                            <span class="text-emerald-400 uppercase block text-[9px] tracking-wider mb-1">Key Expense Categories</span>
                            <p class="text-slate-300">Office Rent, Salaries, Pension (7% firm contribution), Transport, EEU (Electric Utilities), Machine Fixed Assets, Bank charges.</p>
                        </div>
                        <div class="p-3 bg-slate-900/50 rounded-xl border border-slate-800">
                            <span class="text-emerald-400 uppercase block text-[9px] tracking-wider mb-1">VAT Reconciliation</span>
                            <p class="text-slate-300">Sales VAT (15%), Purchase VAT (15%), and Withholding Tax logs are audited to match the monthly tax return submissions.</p>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm glass-card border border-slate-850 rounded-3xl p-5 space-y-3.5 shadow-xl">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider">Expense Audit Board</h4>
                        
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Total Booked Expenses:</span>
                                <span class="font-bold text-red-400">32,450.00 ETB</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Sales VAT (15%):</span>
                                <span class="font-bold text-slate-200">36,075.00 ETB</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Purchase VAT (15%):</span>
                                <span class="font-bold text-slate-200">13,500.00 ETB</span>
                            </div>
                            <div class="flex justify-between border-t border-slate-800 pt-2 text-slate-300">
                                <span>Net VAT Payable:</span>
                                <span class="font-bold text-emerald-400">22,575.00 ETB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 6: ETHIOPIAN TAX COMPUTATION -->
            <div v-if="currentSlide === 6" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">05 / Fiscal Computation</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">The Ethiopian Income Tax Engine</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        The system calculates the client's **Ethiopian Business Income Tax** dynamically. These formulas are baked into the Eloquent model (`MonthlyLedger.php`) to ensure perfect compliance.
                    </p>

                    <div class="p-4 bg-emerald-950/20 border border-emerald-950/40 rounded-2xl space-y-3 text-xs">
                        <h4 class="font-bold text-emerald-400 uppercase tracking-widest text-[10px]">Official Corporate Tax Law</h4>
                        <p class="text-slate-300 leading-relaxed">
                            For Category A/B corporate taxpayers in Ethiopia, business income tax is assessed at <strong>30% - 35%</strong> of Net Income Before Tax (NIBT).
                        </p>
                        <p class="text-slate-400 border-t border-slate-800 pt-2 font-mono text-[10px]">
                            Code Formula: Net Profit &gt; 0 ➔ Tax = (Net Profit * 0.35) - 24,600 ETB
                        </p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <!-- Interactive Tax Calculator Board -->
                    <div class="w-full max-w-sm glass-card border border-emerald-500/20 rounded-3xl p-6 space-y-4 shadow-xl">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider text-center">Interactive Tax calculator Widget</h4>
                        
                        <div class="space-y-3 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Total Sales:</span>
                                <input type="number" v-model.number="mockSales" class="w-24 bg-slate-900 border border-slate-850 px-2 py-0.5 rounded text-right text-white font-bold outline-none ring-1 ring-emerald-500/25">
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Cost of Goods (COGS):</span>
                                <input type="number" v-model.number="mockCogs" class="w-24 bg-slate-900 border border-slate-850 px-2 py-0.5 rounded text-right text-white font-bold outline-none">
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Admin Expenses:</span>
                                <input type="number" v-model.number="mockExpenses" class="w-24 bg-slate-900 border border-slate-850 px-2 py-0.5 rounded text-right text-white font-bold outline-none">
                            </div>
                            <div class="flex justify-between border-t border-slate-800 pt-2 font-bold">
                                <span class="text-slate-400">Net Profit (NIBT):</span>
                                <span class="text-emerald-400">{{ fmt(computedNetProfit) }} ETB</span>
                            </div>
                            <div class="flex justify-between border-t border-dashed border-slate-800 pt-2 font-black text-sm">
                                <span class="text-slate-300">Est. Profit Tax (35%):</span>
                                <span class="text-yellow-400">{{ fmt(computedProfitTax) }} ETB</span>
                            </div>
                        </div>
                        <p class="text-[9px] text-center text-slate-500 font-semibold">💡 Try typing your own numbers to watch the tax update dynamically!</p>
                    </div>
                </div>
            </div>

            <!-- SLIDE 7: BANK BALANCES LEDGER RECONCILIATIONS -->
            <div v-if="currentSlide === 7" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">06 / Reconciliations</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Multi-Bank Ledger Tracking</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        Businesses maintain accounts across multiple banks. GGAA reconciles month-end bank statements by recording unique balance movements directly inside the ledger portal.
                    </p>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-850">
                            <strong class="text-white block mb-0.5">LC Margin Releases:</strong>
                            <span class="text-slate-400">Tracking letter-of-credit margins released during import processes.</span>
                        </div>
                        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-850">
                            <strong class="text-white block mb-0.5">Disbursement & Transfers:</strong>
                            <span class="text-slate-400">Mapping corporate loans, transfer-ins, and correction reversals per account.</span>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 space-y-4">
                    <div class="glass-card rounded-2xl p-5 border border-slate-800 space-y-3">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider">Linked Bank Ledger</h4>
                        
                        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-850 space-y-1.5 text-xs">
                            <div class="flex justify-between font-bold">
                                <span class="text-slate-200">Commercial Bank (CBE)</span>
                                <span class="text-white">410,500.00 ETB</span>
                            </div>
                            <div class="flex justify-between text-[10px] text-slate-500">
                                <span>Account No: 1000281093</span>
                                <span class="text-emerald-400">LC Margin: 50,000.00 ETB</span>
                            </div>
                        </div>

                        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-850 space-y-1.5 text-xs">
                            <div class="flex justify-between font-bold">
                                <span class="text-slate-200">Awash Bank</span>
                                <span class="text-white">182,000.00 ETB</span>
                            </div>
                            <div class="flex justify-between text-[10px] text-slate-500">
                                <span>Account No: 0132098124</span>
                                <span class="text-amber-400">Loan: 100,000.00 ETB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 8: REPORT VERIFICATIONS & PDF VAULT EXPORT -->
            <div v-if="currentSlide === 8" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">07 / Secure Deliverables</span>
                    <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Verifiable PDF Reports & QR Security</h2>
                    <p class="text-slate-400 leading-relaxed font-medium">
                        Once a monthly ledger is verified by senior accounting staff, the platform compiles an **Official PDF Statement** embedded with a tamper-proof verification QR code.
                    </p>

                    <div class="space-y-3 text-xs font-medium text-slate-300">
                        <p>🔒 <strong>Draft ➔ Submitted ➔ Verified:</strong> Ledgers remain read-only for clients until signed off by the auditor.</p>
                        <p>📲 <strong>Scan & Verify QR:</strong> Government tax auditors or banks can scan the report's printed QR code to instantly verify its authenticity on the live GGAA secure portal.</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm bg-white text-slate-900 rounded-3xl p-5 shadow-2xl space-y-4 border-t-8 border-t-emerald-600">
                        <div class="flex justify-between items-start border-b pb-3">
                            <div>
                                <span class="text-[8px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-black">OFFICIAL AUDIT SHEET</span>
                                <h4 class="text-xs font-black font-outfit uppercase mt-1">Gedion Girma Accountant</h4>
                            </div>
                            <div class="w-12 h-12 bg-slate-200 border flex items-center justify-center text-[9px] font-bold" title="Verifiable QR Code">
                                [ QR ]
                            </div>
                        </div>
                        
                        <div class="space-y-2 text-[10px] leading-snug">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Client TIN:</span>
                                <span class="font-bold">0048291039</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Month / Year:</span>
                                <span class="font-bold">Meskeram 2026</span>
                            </div>
                            <div class="flex justify-between border-t pt-2 font-bold text-slate-800">
                                <span>Computed Tax Obligation:</span>
                                <span class="text-emerald-700">26,167.50 ETB</span>
                            </div>
                        </div>
                        
                        <p class="text-[8px] text-center text-slate-400 font-bold border-t pt-2 uppercase">Verified Secure Document ➔ Scan QR to Reconcile</p>
                    </div>
                </div>
            </div>

            <!-- SLIDE 9: FINANCE CHECKLIST & NEXT STEPS -->
            <div v-if="currentSlide === 9" class="w-full flex flex-col items-center justify-center py-6 text-center relative z-10 space-y-4 max-w-2xl animate-fade-in">
                <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">08 / Graduation</span>
                <h2 class="text-3xl lg:text-4xl font-extrabold font-outfit text-white tracking-tight">Your Finance Audit Checklist</h2>
                <p class="text-slate-400 leading-relaxed font-medium text-sm">
                    Follow these step-by-step procedures to ensure complete financial reconciliation and compliance across all accounts:
                </p>
                
                <div class="p-6 glass-card rounded-3xl border border-slate-850 text-left w-full max-w-lg mx-auto space-y-3 text-xs text-slate-300">
                    <div v-for="item in checklistItems" :key="item.id" 
                         @click="toggleCheck(item)"
                         class="flex items-start gap-3 cursor-pointer select-none py-1 rounded hover:bg-slate-800/35 px-2 transition-colors"
                    >
                        <input type="checkbox" :checked="item.checked" class="accent-emerald-500 h-4 w-4 shrink-0 rounded mt-0.5 cursor-pointer">
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
        <footer class="w-full py-6 px-6 lg:px-12 flex justify-between items-center border-t border-slate-800 bg-[#030706] sticky bottom-0 z-50">
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500 font-medium">GGAA Systems Operations Slide Deck</span>
            </div>
            
            <div class="flex items-center gap-6">
                <button @click="prevSlide" class="p-2.5 rounded-xl border border-slate-800 hover:bg-slate-900/60 active:scale-95 transition-all text-slate-400 hover:text-white">
                    <ChevronLeftIcon class="h-4 w-4" />
                </button>
                
                <span class="text-xs font-bold tracking-wider font-outfit text-slate-400">
                    Slide <span class="text-emerald-400">{{ currentSlide }}</span> of <span>{{ totalSlides }}</span>
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

.glow-emerald {
    box-shadow: 0 0 40px rgba(16, 185, 129, 0.15);
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
