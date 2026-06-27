<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    BanknotesIcon, AcademicCapIcon,
    ChevronLeftIcon, ChevronRightIcon,
    ArrowLeftIcon, SunIcon, MoonIcon,
    CheckCircleIcon, ExclamationTriangleIcon,
    CalculatorIcon, DocumentCheckIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const isDark = ref(true);
const currentStep = ref(0); // 0: Overview, 1: Lab 1, 2: Lab 2, 3: Lab 3, 4: Graduation
const userName = ref('Abebe Kebede');

// Load settings
onMounted(() => {
    const savedTheme = localStorage.getItem('training-theme');
    if (savedTheme === 'light') {
        isDark.value = false;
    }
    const savedLocale = localStorage.getItem('locale') || 'en';
    locale.value = savedLocale;
});

function toggleTheme() {
    isDark.value = !isDark.value;
    localStorage.setItem('training-theme', isDark.value ? 'dark' : 'light');
}

function setLanguage(lang) {
    locale.value = lang;
    localStorage.setItem('locale', lang);
}

// ==========================================
// LAB 1 STATE: COGS RECONCILER
// ==========================================
const inputCogs = ref('');
const lab1Error = ref('');
const lab1Passed = ref(false);

function validateLab1() {
    lab1Error.value = '';
    // Formula: Starting (150,000) + Purchases (230,000) - Ending (100,000) = 280,000
    if (parseInt(inputCogs.value) === 280000) {
        lab1Passed.value = true;
    } else {
        if (locale.value === 'en') {
            lab1Error.value = "❌ Incorrect COGS! Re-calculate: Starting Stock + Purchases - Ending Stock.";
        } else {
            lab1Error.value = "❌ የተሳሳተ የዕቃ መግዣ ወጪ! ቀመር፡ የመጀመሪያ ክምችት + አዲስ ግዢ - የመጨረሻ ክምችት።";
        }
    }
}

// ==========================================
// LAB 2 STATE: PROFIT TAX CALCULATOR
// ==========================================
const inputTax = ref('');
const lab2Error = ref('');
const lab2Passed = ref(false);

function validateLab2() {
    lab2Error.value = '';
    // Net Profit: 300,000
    // Tax Slab: (300,000 * 0.35) - 24,600 = 105,000 - 24,600 = 80,400
    if (parseInt(inputTax.value) === 80400) {
        lab2Passed.value = true;
    } else {
        if (locale.value === 'en') {
            lab2Error.value = "❌ Incorrect Tax return value. Apply formula: (Net Profit * 0.35) - 24,600.";
        } else {
            lab2Error.value = "❌ የተሳሳተ የትርፍ ግብር ስሌት። ቀመር፡ (የተጣራ ትርፍ * 0.35) - 24,600።";
        }
    }
}

// ==========================================
// LAB 3 STATE: INVOICE SETTLEMENT
// ==========================================
const selectedDeposit = ref('');
const invoiceSettled = ref(false);
const lab3Passed = computed(() => {
    return invoiceSettled.value;
});

function settleInvoice() {
    if (selectedDeposit.value === 'deposit2') {
        invoiceSettled.value = true;
    }
}

// ==========================================
// STATE SAVING ON GRADUATION
// ==========================================
watch(currentStep, (newStep) => {
    if (newStep === 4) {
        localStorage.setItem('training-completed-finances', 'true');
    }
});

// Bilingual content dictionary
const content = {
    en: {
        backBtn: "Back to Tracks",
        headerTitle: "Finance Academy",
        headerSubtitle: "Bookkeeping & Tax Simulator",
        stepNames: ["Overview", "Lab 1: COGS", "Lab 2: Tax Slab", "Lab 3: Settle", "Graduation 🎓"],
        
        overview: {
            title: "Welcome to GGAA Finance Academy!",
            desc1: "Finance specialists verify ledger integrity, audit Cost of Goods Sold (COGS), compute corporate profit taxes, and reconcile bank deposit references to settle outstanding service bills.",
            desc2: "This interactive simulator contains three practical labs that test your precision with inventory reconciliation, profit tax rules, and invoice settlement matching.",
            startBtn: "Start Finance Training",
        },
        lab1: {
            guideTitle: "Lab 1: COGS Inventory Audit",
            intro: "Draft ledgers submitted by accountants sometimes contain errors in the Cost of Goods Sold (COGS). As a finance auditor, you must verify the inventory figures using the standard calculation.",
            instructionTitle: "Your Laboratory Objective:",
            inst1: "1. Inspect the stock summary table for Zenith PLC on the right.",
            inst2: "2. Recompute the correct COGS: Starting Stock + Purchases - Ending Stock.",
            inst3: "3. Input the corrected COGS value in the field below and click 'Validate COGS'.",
            cogsLabel: "Enter Reconciled COGS (ETB):",
            validateBtn: "Validate COGS",
            successMsg: "🎉 Success! COGS reconciled at 280,000.00 ETB. Stock ledger mismatch resolved.",
        },
        lab2: {
            guideTitle: "Lab 2: Corporate Profit Tax Engine",
            intro: "GGAA uses the official Category A/B Ethiopian profit tax slab for corporate businesses. Profit tax is assessed at a flat 35% of Net profit minus a standard offset of 24,600 ETB.",
            instructionTitle: "Your Laboratory Objective:",
            inst1: "1. Review Awash Coffee's Net Profit (300,000 ETB) in the board on the right.",
            inst2: "2. Calculate the estimated profit tax: (Net Profit * 0.35) - 24,600.",
            inst3: "3. Input the exact tax result below and click 'Submit Tax Return'.",
            taxLabel: "Enter Estimated Profit Tax (ETB):",
            submitBtn: "Submit Tax Return",
            successMsg: "🎉 Success! Corporate profit tax validated at 80,400.00 ETB. Government return matches.",
        },
        lab3: {
            guideTitle: "Lab 3: Invoice Bank Matching",
            intro: "When clients pay service fees, bank transfers record transaction references. You must match the deposit with the outstanding client invoice to settle their bills and unlock their next bookkeeping month.",
            instructionTitle: "Your Laboratory Objective:",
            inst1: "1. Look at outstanding invoice #INV-2026-081 for Nile Textile (45,000 ETB).",
            inst2: "2. Inspect the bank deposits list on the right and select the matching payment.",
            inst3: "3. Click 'Match & Settle Invoice' to complete the transaction.",
            depositLabel: "Select Matching Bank Deposit:",
            settleBtn: "Match & Settle Invoice",
            settledBadge: "FULLY SETTLED & RECONCILED",
            unsettledBadge: "UNPAID INVOICE",
            successMsg: "🎉 Success! Payment matched and invoice settled. The client ledger has been unlocked.",
        },
        graduation: {
            title: "Congratulations, Finance Auditor!",
            subtitle: "You have completed all three bookkeeping and tax audit simulation labs.",
            badgeLabel: "Unlocked Profile Badge:",
            badgeTitle: "🎓 Tax Guru",
            badgeDesc: "Passed corporate ledger and bank settlement assessments with zero variance.",
            certTitle: "CERTIFIED FINANCE AUDITOR",
            certSubtitle: "GGAA Systems Finance & Audit Academy",
            certBody: "This certifies that the holder has passed the rigorous operations simulator, demonstrated proficiency in capacity scheduling, GPS errand logging, and tax ID validation.",
            certDate: "Certified on: June 2026",
            certNameLabel: "Change Certificate Name:",
            homeBtn: "Return to Training Hub",
        }
    },
    am: {
        backBtn: "ወደ ስልጠናዎች ተመለስ",
        headerTitle: "የፋይናንስና የሂሳብ አያያዝ ስልጠና",
        headerSubtitle: "የሂሳብ እና የታክስ ማስመሰያ ገጽ",
        stepNames: ["ማጠቃለያ", "ላብ 1: COGS", "ላብ 2: ትርፍ ግብር", "ላብ 3: ማስታረቅ", "ምረቃ 🎓"],
        
        overview: {
            title: "እንኳን ወደ ፋይናንስ የስልጠና ክፍል በደህና መጡ!",
            desc1: "የፋይናንስ ባለሙያዎች የሂሳብ መዛግብትን ትክክለኛነት ያረጋግጣሉ፣ የዕቃ መግዣ ወጪን (COGS) ኦዲት ያደርጋሉ፣ የንግድ ትርፍ ግብርን ያሰላሉ፣ እና የባንክ ክፍያዎችን ከክፍያ መጠየቂያዎች ጋር ያገናኛሉ።",
            desc2: "ይህ ማስመሰያ የሂሳብ ማስታረቅ፣ የታክስ ህጎች እና የክፍያ መጠየቂያ አሰፋፈር ላይ ያለዎትን ብቃት በተግባር የሚፈትን 3 ላብራቶሪዎችን የያዘ ነው።",
            startBtn: "የፋይናንስ ስልጠናውን ጀምር",
        },
        lab1: {
            guideTitle: "ላብ 1: የዕቃ መግዣ ወጪ (COGS) ኦዲት",
            intro: "አካውንታንቶች የሚሞሏቸው ወርሃዊ መዛግብት አንዳንድ ጊዜ በዕቃ መግዣ ወጪ (COGS) ላይ ስህተት ሊኖራቸው ይችላል። እንደ ፋይናንስ ኦዲተር መደበኛውን ስሌት በመጠቀም ወጪውን ማረጋገጥ አለብዎት።",
            instructionTitle: "የተግባር ልምምድ መመሪያዎች:",
            inst1: "1. በቀኝ በኩል ያለውን የ Zenith PLC የዕቃዎች ክምችት ማጠቃለያ ይመልከቱ።",
            inst2: "2. ትክክለኛውን የዕቃ መግዣ ወጪ ያሰሉ፡ የመጀመሪያ ክምችት + አዲስ ግዢ - የመጨረሻ ክምችት።",
            inst3: "3. ያሰሉትን ቁጥር በቅጹ ላይ ሞልተው 'የተሰላውን ወጪ አረጋግጥ' የሚለውን ይጫኑ።",
            cogsLabel: "የተረጋገጠውን COGS ያስገቡ (ETB):",
            validateBtn: "የተሰላውን ወጪ አረጋግጥ",
            successMsg: "🎉 እንኳን ደስ አለዎት! የዕቃ መግዣ ወጪው 280,000.00 ብር ተብሎ በትክክል ተረጋግጧል። የሂሳብ ልዩነቱ ተፈትቷል።",
        },
        lab2: {
            guideTitle: "ላብ 2: የንግድ ትርፍ ግብር ማስያ",
            intro: "ጂጂኤኤ የኢትዮጵያን ይፋዊ የትርፍ ግብር ስሌት ዘዴ ይጠቀማል። የትርፍ ግብር የሚሰላው ከተጣራ ትርፍ ላይ 35% ተወስዶ የመቀነሻውን offset (24,600 ብር) በመቀነስ ነው።",
            instructionTitle: "የተግባር ልምምድ መመሪያዎች:",
            inst1: "1. በቀኝ በኩል የ Awash Coffee የተጣራ ትርፍ (300,000 ብር) ይመልከቱ።",
            inst2: "2. የትርፍ ግብሩን ያሰሉ፡ (የተጣራ ትርፍ * 0.35) - 24,600።",
            inst3: "3. ያገኙትን የግብር መጠን በቅጹ ላይ ሞልተው 'ግብሩን አስገባ' የሚለውን ይጫኑ።",
            taxLabel: "የተሰላውን የትርፍ ግብር ያስገቡ (ETB):",
            submitBtn: "ግብሩን አስገባ",
            successMsg: "🎉 እንኳን ደስ አለዎት! የንግድ ትርፍ ግብሩ 80,400.00 ብር ተብሎ በትክክል ተረጋግጧል። ከመንግስት ሪፖርት ጋር ይጣጣማል።",
        },
        lab3: {
            guideTitle: "ላብ 3: ክፍያዎችን ከደረሰኝ ጋር ማስታረቅ",
            intro: "ደንበኞች ክፍያ ሲፈጽሙ በባንክ በኩል የማስተላለፊያ ቁጥር ይላካል። የደንበኛውን ቀጣይ ወር ሂሳብ ለመክፈት ክፍያውን ካልተከፈለ የክፍያ መጠየቂያ (Invoice) ጋር ማያያዝ አለብዎት።",
            instructionTitle: "የተግባር ልምምድ መመሪያዎች:",
            inst1: "1. የ Nile Textile Corp ያልተከፈለ ክፍያ #INV-2026-081 (45,000 ብር) ይመልከቱ።",
            inst2: "2. በቀኝ በኩል ከተዘረዘሩት የባንክ ገቢዎች ውስጥ ትክክለኛውን ክፍያ ይምረጡ።",
            inst3: "3. 'ክፍያውን አያይዝና አረጋግጥ' የሚለውን በመጫን ሂሳቡን ይዝጉ።",
            depositLabel: "ትክክለኛውን የባንክ ክፍያ ይምረጡ:",
            settleBtn: "ክፍያውን አያይዝና አረጋግጥ",
            settledBadge: "ክፍያው ተፈጽሞ ሂሳቡ ተዘግቷል",
            unsettledBadge: "ያልተከፈለ ክፍያ",
            successMsg: "🎉 እንኳን ደስ አለዎት! ክፍያው ከደረሰኙ ጋር ተገናኝቶ ሂሳቡ ተዘግቷል። የደንበኛው ሂሳብ ተከፍቷል።",
        },
        graduation: {
            title: "እንኳን ደስ አለዎት፣ የፋይናንስ ኦዲተር!",
            subtitle: "ሁሉንም 3 የፋይናንስና የታክስ ኦዲት ላብራቶሪዎችን በተሳካ ሁኔታ አጠናቀዋል።",
            badgeLabel: "ያገኙት የክብር ባጅ:",
            badgeTitle: "🎓 የታክስ ሊቅ",
            badgeDesc: "የንግድ ድርጅቶች ሂሳብ እና የባንክ ክፍያዎችን ያለ ምንም ልዩነት ያረጋገጠ።",
            certTitle: "የፋይናንስ ኦዲተር ሰርተፊኬት",
            certSubtitle: "ጂጂኤኤ ሲስተምስ የፋይናንስ አካዳሚ",
            certBody: "ይህ ሰርተፊኬት ባለቤቱ የፋይናንስ ኦዲተር ማስመሰያ ፈተናዎችን ማለፉን፣ በዕቃ መግዣ ወጪ (COGS)፣ በትርፍ ግብር ስሌት እና በባንክ ክፍያዎች ማስታረቅ ላይ ሙሉ ብቃት ማሳየቱን ያረጋግጣል።",
            certDate: "የተሰጠበት ቀን: ሰኔ 2026",
            certNameLabel: "በሰርተፊኬቱ ላይ ያለውን ስም ይቀይሩ:",
            homeBtn: "ወደ ስልጠናዎች ማእከል ተመለስ",
        }
    }
};
</script>

<template>
    <Head :title="`${content[locale].headerTitle} - GGAA Systems`" />

    <div class="min-h-screen font-sans flex flex-col justify-between overflow-x-hidden transition-colors duration-300 selection:bg-emerald-600 selection:text-white"
         :class="isDark ? 'bg-[#050b0a] text-slate-100' : 'bg-slate-50 text-slate-800'"
    >
        <!-- Background Blur Particles -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] blur-[150px] rounded-full transition-opacity duration-300"
                 :class="isDark ? 'bg-emerald-600/10 opacity-100' : 'bg-emerald-500/5 opacity-40'"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[35%] h-[35%] blur-[150px] rounded-full transition-opacity duration-300"
                 :class="isDark ? 'bg-teal-600/10 opacity-100' : 'bg-teal-500/5 opacity-40'"></div>
        </div>

        <!-- Top Navigation Bar -->
        <header class="w-full py-4 px-6 lg:px-12 flex justify-between items-center border-b backdrop-blur-md sticky top-0 z-50 transition-colors"
                :class="isDark ? 'border-slate-850 bg-[#050b0a]/80' : 'border-slate-200 bg-white/80'"
        >
            <Link href="/training" class="flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-emerald-500 transition-colors group">
                <ArrowLeftIcon class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                {{ content[locale].backBtn }}
            </Link>
            
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-650 flex items-center justify-center font-black font-outfit shadow-lg shadow-emerald-500/20 text-white">
                    F
                </div>
                <div>
                    <span class="text-base font-black tracking-wider uppercase font-outfit" :class="isDark ? 'text-white' : 'text-slate-900'">
                        GGAA <span class="text-emerald-500">Systems</span>
                    </span>
                    <span class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest leading-none">
                        {{ content[locale].headerSubtitle }}
                    </span>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Lang Switcher -->
                <div class="flex bg-slate-500/10 p-0.5 rounded-xl border border-slate-500/10">
                    <button @click="setLanguage('en')" class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase transition-all"
                            :class="locale === 'en' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-slate-200'">EN</button>
                    <button @click="setLanguage('am')" class="px-2.5 py-1 rounded-lg text-[10px] font-bold transition-all"
                            :class="locale === 'am' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-slate-200'">አማ</button>
                </div>

                <!-- Theme Switcher -->
                <button @click="toggleTheme" class="p-2 rounded-xl border transition-all"
                        :class="isDark ? 'border-slate-800 text-amber-400 hover:bg-slate-900' : 'border-slate-200 text-slate-700 hover:bg-slate-200'"
                >
                    <SunIcon v-if="isDark" class="h-4 w-4" />
                    <MoonIcon v-else class="h-4 w-4" />
                </button>
            </div>
        </header>

        <!-- Main Body: Course Curriculum Steps -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 md:px-8 py-6 relative z-10 flex flex-col justify-center">
            
            <!-- Step Navigation Tabs -->
            <div class="flex justify-center flex-wrap gap-2 mb-8 bg-slate-950/20 backdrop-blur-md p-1.5 rounded-2xl border border-slate-500/10 max-w-2xl mx-auto w-full">
                <button v-for="(stepName, index) in content[locale].stepNames" :key="index"
                        @click="index <= 3 || lab3Passed ? currentStep = index : null"
                        class="px-4 py-2 text-xs font-bold font-outfit rounded-xl transition-all flex items-center gap-1.5"
                        :class="[
                            currentStep === index 
                                ? 'bg-emerald-600 text-white shadow-md' 
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-550/5',
                            (index > 0 && index <= 3 && !lab3Passed && index > currentStep + 1) ? 'opacity-40 cursor-not-allowed' : ''
                        ]"
                >
                    <span class="h-4 w-4 rounded-full flex items-center justify-center text-[10px] font-black border"
                          :class="currentStep >= index ? 'border-white text-white' : 'border-slate-500 text-slate-500'"
                    >
                        {{ index + 1 }}
                    </span>
                    {{ stepName }}
                </button>
            </div>

            <!-- STEP 0: OVERVIEW -->
            <div v-if="currentStep === 0" class="max-w-3xl mx-auto text-center space-y-6 py-12 animate-fade-in">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 text-emerald-450 text-xs font-black uppercase tracking-widest border border-emerald-500/20">
                    <AcademicCapIcon class="h-5 w-5" />
                    {{ locale === 'en' ? 'Track 3: Bookkeeping & Financial Audit Operations' : 'ክፍል 3፡ የፋይናንስና የሂሳብ አያያዝ ማረጋገጫ ስልጠና' }}
                </span>
                
                <h1 class="text-4xl sm:text-6xl font-black font-outfit tracking-tighter leading-tight"
                    :class="isDark ? 'text-white' : 'text-slate-950'"
                >
                    {{ content[locale].overview.title }}
                </h1>
                
                <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                    {{ content[locale].overview.desc1 }}
                </p>
                <p class="text-base leading-relaxed font-medium" :class="isDark ? 'text-slate-450' : 'text-slate-550'">
                    {{ content[locale].overview.desc2 }}
                </p>

                <div class="pt-6">
                    <button @click="currentStep = 1" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-xl shadow-emerald-600/30 hover:scale-105 active:scale-95 transition-all flex items-center gap-2 text-sm uppercase tracking-wider font-outfit mx-auto">
                        {{ content[locale].overview.startBtn }}
                        <ChevronRightIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- SPLIT SCREEN LAYOUT FOR LABS (1, 2, 3) -->
            <div v-if="currentStep > 0 && currentStep < 4" class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-stretch py-2 min-h-[500px]">
                
                <!-- LEFT SIDE: LAB GUIDE & STATUS -->
                <div class="flex flex-col justify-between space-y-6 rounded-[32px] p-6 lg:p-8 border backdrop-blur-md"
                     :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-white border-slate-200 shadow-lg'"
                >
                    <div class="space-y-4">
                        <span class="text-xs font-black uppercase text-emerald-500 tracking-widest block font-outfit">
                            {{ locale === 'en' ? `Lab Exercise ${currentStep} of 3` : `ተግባራዊ ልምምድ ${currentStep} ከ 3` }}
                        </span>
                        
                        <h2 class="text-2xl lg:text-3xl font-black font-outfit tracking-tight"
                            :class="isDark ? 'text-white' : 'text-slate-900'"
                        >
                            {{ content[locale][`lab${currentStep}`].guideTitle }}
                        </h2>
                        
                        <p class="text-sm font-medium leading-relaxed" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                            {{ content[locale][`lab${currentStep}`].intro }}
                        </p>
                        
                        <!-- Objective panel -->
                        <div class="p-5 rounded-2xl space-y-2.5" :class="isDark ? 'bg-slate-950/40 border border-slate-850' : 'bg-slate-50 border border-slate-200'">
                            <h4 class="text-xs font-black uppercase tracking-wider" :class="isDark ? 'text-slate-400' : 'text-slate-700'">
                                {{ content[locale][`lab${currentStep}`].instructionTitle }}
                            </h4>
                            <ul class="space-y-2 text-xs font-semibold leading-relaxed" :class="isDark ? 'text-slate-400' : 'text-slate-600'">
                                <li>{{ content[locale][`lab${currentStep}`].inst1 }}</li>
                                <li>{{ content[locale][`lab${currentStep}`].inst2 }}</li>
                                <li>{{ content[locale][`lab${currentStep}`].inst3 }}</li>
                            </ul>
                        </div>

                        <!-- LAB 1 COGS FORM -->
                        <div v-if="currentStep === 1" class="space-y-2.5 pt-2">
                            <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].lab1.cogsLabel }}</label>
                            <div class="flex gap-2">
                                <input type="number" v-model.number="inputCogs" placeholder="e.g. 280000" 
                                       class="flex-1 bg-slate-500/10 border px-4 py-2.5 rounded-xl font-bold font-mono text-sm outline-none transition-all"
                                       :class="isDark ? 'border-slate-800 text-white focus:border-emerald-500' : 'border-slate-250 text-slate-900 focus:border-emerald-500'"
                                       :disabled="lab1Passed"
                                >
                                <button v-if="!lab1Passed" @click="validateLab1" 
                                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all"
                                >
                                    {{ content[locale].lab1.validateBtn }}
                                </button>
                            </div>
                            <p v-if="lab1Error" class="text-xs font-bold text-rose-500">{{ lab1Error }}</p>
                        </div>

                        <!-- LAB 2 TAX SLAB FORM -->
                        <div v-if="currentStep === 2" class="space-y-2.5 pt-2">
                            <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].lab2.taxLabel }}</label>
                            <div class="flex gap-2">
                                <input type="number" v-model.number="inputTax" placeholder="e.g. 80400" 
                                       class="flex-1 bg-slate-500/10 border px-4 py-2.5 rounded-xl font-bold font-mono text-sm outline-none transition-all"
                                       :class="isDark ? 'border-slate-800 text-white focus:border-emerald-500' : 'border-slate-250 text-slate-900 focus:border-emerald-500'"
                                       :disabled="lab2Passed"
                                >
                                <button v-if="!lab2Passed" @click="validateLab2" 
                                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all"
                                >
                                    {{ content[locale].lab2.submitBtn }}
                                </button>
                            </div>
                            <p v-if="lab2Error" class="text-xs font-bold text-rose-500">{{ lab2Error }}</p>
                        </div>

                    </div>

                    <!-- SUCCESS DIALOG & PROGRESS BUTTON -->
                    <div class="pt-4 border-t" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                        <div v-if="(currentStep === 1 && lab1Passed) || (currentStep === 2 && lab2Passed) || (currentStep === 3 && lab3Passed)" 
                             class="p-4 mb-4 rounded-2xl border text-xs font-bold text-emerald-500 bg-emerald-500/10 border-emerald-500/20"
                        >
                            {{ content[locale][`lab${currentStep}`].successMsg }}
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <button @click="currentStep--" 
                                    class="px-4 py-2.5 rounded-xl border text-xs font-bold uppercase transition-all"
                                    :class="isDark ? 'border-slate-800 text-slate-400 hover:bg-slate-900' : 'border-slate-250 text-slate-600 hover:bg-slate-100'"
                            >
                                {{ locale === 'en' ? 'Back' : 'ተመለስ' }}
                            </button>
                            
                            <button @click="currentStep++" 
                                    :disabled="!(currentStep === 1 ? lab1Passed : currentStep === 2 ? lab2Passed : lab3Passed)"
                                    class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5"
                            >
                                {{ locale === 'en' ? 'Next Lab' : 'ቀጣይ ላብ' }}
                                <ChevronRightIcon class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: SIMULATED OPERATIONS SANDBOX -->
                <div class="rounded-[32px] p-6 lg:p-8 border flex flex-col justify-center relative overflow-hidden animate-fade-in"
                     :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-250 shadow-md'"
                >
                    
                    <!-- LAB 1 SANDBOX: COGS AUDIT -->
                    <div v-if="currentStep === 1" class="space-y-4 w-full">
                        <div class="flex justify-between items-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Zenith PLC Inventory (Meskeram 2026)</span>
                        </div>

                        <!-- Inventory Table Card -->
                        <div class="p-4 border rounded-2xl space-y-3" :class="isDark ? 'bg-slate-950/20 border-slate-850' : 'bg-slate-50 border-slate-200'">
                            <div class="grid grid-cols-3 gap-2 text-center text-xs font-bold">
                                <div>
                                    <span class="text-slate-500 block uppercase text-[9px] mb-1">Starting Stock</span>
                                    <span :class="isDark ? 'text-white' : 'text-slate-900'">150,000.00</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block uppercase text-[9px] mb-1">Purchases</span>
                                    <span :class="isDark ? 'text-white' : 'text-slate-900'">230,000.00</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block uppercase text-[9px] mb-1">Ending Stock</span>
                                    <span :class="isDark ? 'text-white' : 'text-slate-900'">100,000.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Formula code helper -->
                        <div class="p-4 bg-emerald-500/10 border border-emerald-500/25 text-emerald-450 rounded-2xl text-xs font-mono text-center leading-relaxed">
                            COGS = Starting Stock + Purchases - Ending Stock
                        </div>

                        <div v-if="lab1Passed" class="p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 text-xs font-black uppercase rounded-xl text-center animate-pulse">
                            {{ locale === 'en' ? 'COGS verified!' : 'የዕቃ መግዣ ወጪ ተረጋግጧል!' }}
                        </div>
                    </div>

                    <!-- LAB 2 SANDBOX: TAX CALCULATOR -->
                    <div v-if="currentStep === 2" class="space-y-4 w-full">
                        <div class="flex justify-between items-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Awash Coffee Exports (Corporate Return)</span>
                        </div>

                        <!-- Net Profit Details card -->
                        <div class="p-4 border rounded-2xl space-y-3 font-semibold" :class="isDark ? 'bg-slate-950/20 border-slate-850' : 'bg-slate-50 border-slate-200'">
                            <div class="flex justify-between items-center text-xs">
                                <span :class="isDark ? 'text-slate-400' : 'text-slate-650'">Sales Revenue:</span>
                                <span :class="isDark ? 'text-white' : 'text-slate-900'">700,000.00 ETB</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span :class="isDark ? 'text-slate-400' : 'text-slate-650'">Total COGS & Expenses:</span>
                                <span :class="isDark ? 'text-white' : 'text-slate-900'">400,000.00 ETB</span>
                            </div>
                            <div class="flex justify-between items-center text-xs border-t pt-2" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <span :class="isDark ? 'text-slate-350' : 'text-slate-700'">Net Profit (NIBT):</span>
                                <span class="font-extrabold text-emerald-500 text-sm">300,000.00 ETB</span>
                            </div>
                        </div>

                        <!-- Formula code helper -->
                        <div class="p-4 bg-emerald-500/10 border border-emerald-500/25 text-emerald-450 rounded-2xl text-xs font-mono text-center leading-relaxed">
                            Tax = (Net Profit * 0.35) - 24,600 ETB
                        </div>

                        <div v-if="lab2Passed" class="p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 text-xs font-black uppercase rounded-xl text-center animate-pulse">
                            {{ locale === 'en' ? 'Estimated Profit Tax Verified!' : 'የንግድ ትርፍ ግብር በትክክል ተረጋግጧል!' }}
                        </div>
                    </div>

                    <!-- LAB 3 SANDBOX: INVOICE SETTLEMENT & MATCHING -->
                    <div v-if="currentStep === 3" class="space-y-4 w-full">
                        <div class="flex justify-between items-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Nile Textile Outstanding Invoice</span>
                        </div>

                        <!-- Unpaid Invoice Card -->
                        <div class="p-4 border rounded-2xl space-y-3"
                             :class="[
                                 invoiceSettled 
                                     ? 'bg-emerald-500/5 border-emerald-500/25 shadow-emerald-500/5' 
                                     : (isDark ? 'bg-slate-950/20 border-slate-850' : 'bg-slate-50 border-slate-250 shadow-sm')
                             ]"
                        >
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="text-[10px] text-slate-500 uppercase">Invoice Ref</span>
                                    <h4 class="text-sm font-black" :class="isDark ? 'text-white' : 'text-slate-900'">#INV-2026-081</h4>
                                </div>
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider border"
                                      :class="[
                                          invoiceSettled 
                                              ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/25 animate-pulse' 
                                              : 'bg-rose-500/10 text-rose-500 border-rose-500/20'
                                      ]"
                                >
                                    {{ invoiceSettled ? content[locale].lab3.settledBadge : content[locale].lab3.unsettledBadge }}
                                </span>
                            </div>
                            <div class="flex justify-between text-xs pt-1 border-t" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <span class="text-slate-500 font-bold uppercase">Amount Due:</span>
                                <span class="font-extrabold text-emerald-500">45,000.00 ETB</span>
                            </div>
                        </div>

                        <!-- Radio list of incoming deposits -->
                        <div v-if="!invoiceSettled" class="space-y-3 pt-1">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">
                                {{ content[locale].lab3.depositLabel }}
                            </span>
                            
                            <label class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer hover:bg-slate-500/10 transition-colors"
                                   :class="selectedDeposit === 'deposit1' ? 'border-emerald-500' : 'border-slate-850'"
                            >
                                <input type="radio" v-model="selectedDeposit" value="deposit1" class="mt-0.5 accent-emerald-500">
                                <div class="text-xs">
                                    <span class="font-bold text-slate-350 block" :class="isDark ? 'text-slate-200' : 'text-slate-800'">Deposit: 10,000.00 ETB</span>
                                    <span class="text-[10px] text-slate-550 block">Ref: CBE-88229 | Client: Abay Textile</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer hover:bg-slate-500/10 transition-colors"
                                   :class="selectedDeposit === 'deposit2' ? 'border-emerald-500' : 'border-slate-850'"
                            >
                                <input type="radio" v-model="selectedDeposit" value="deposit2" class="mt-0.5 accent-emerald-500">
                                <div class="text-xs">
                                    <span class="font-bold text-slate-350 block" :class="isDark ? 'text-slate-200' : 'text-slate-800'">Deposit: 45,000.00 ETB</span>
                                    <span class="text-[10px] text-slate-500 font-bold block text-indigo-400">Ref: CBE-99201 | Nile Textile (INV-081 Payment)</span>
                                </div>
                            </label>

                            <button @click="settleInvoice"
                                    :disabled="selectedDeposit !== 'deposit2'"
                                    class="w-full py-3 bg-emerald-650 hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all"
                            >
                                {{ content[locale].lab3.settleBtn }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- STEP 4: GRADUATION -->
            <div v-if="currentStep === 4" class="max-w-4xl mx-auto py-4 space-y-8 animate-fade-in relative z-10">
                <!-- Pure-CSS Confetti Particles -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden">
                    <div class="confetti-particle bg-emerald-500 top-0 left-[10%]"></div>
                    <div class="confetti-particle bg-teal-500 top-0 left-[30%]"></div>
                    <div class="confetti-particle bg-indigo-500 top-0 left-[50%]"></div>
                    <div class="confetti-particle bg-purple-500 top-0 left-[70%]"></div>
                    <div class="confetti-particle bg-yellow-500 top-0 left-[90%]"></div>
                </div>

                <div class="text-center space-y-4 max-w-2xl mx-auto">
                    <span class="inline-flex items-center gap-1 bg-emerald-500/10 border border-emerald-500/25 text-emerald-450 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest animate-pulse">
                        <SparklesIcon class="h-4 w-4" />
                        {{ content[locale].graduation.title }}
                    </span>
                    <h2 class="text-4xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                        {{ locale === 'en' ? 'Auditor Certified!' : 'የፋይናንስ ኦዲተር ብቃት ማረጋገጫ ተሰጥቶዎታል!' }}
                    </h2>
                    <p class="text-base font-semibold" :class="isDark ? 'text-slate-400' : 'text-slate-650'">
                        {{ content[locale].graduation.subtitle }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-8 items-center">
                    
                    <!-- Left Column: Certificate Badge details & User Name Input -->
                    <div class="md:col-span-2 space-y-6">
                        <div class="p-6 rounded-[28px] border space-y-4" :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-white border-slate-200 shadow-lg'">
                            <span class="text-xs font-black uppercase text-slate-500 block">{{ content[locale].graduation.badgeLabel }}</span>
                            <div class="flex items-center gap-4">
                                <span class="h-16 w-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-650 flex items-center justify-center text-3xl shadow-lg">🎓</span>
                                <div>
                                    <h4 class="font-black text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ content[locale].graduation.badgeTitle }}</h4>
                                    <p class="text-xs text-slate-550 font-semibold">{{ content[locale].graduation.badgeDesc }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Name Edit Input -->
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].graduation.certNameLabel }}</label>
                            <input type="text" v-model="userName" 
                                   class="w-full bg-slate-500/10 border px-4 py-2.5 rounded-xl font-bold text-sm outline-none transition-all"
                                   :class="isDark ? 'border-slate-800 text-white focus:border-emerald-500' : 'border-slate-250 text-slate-900 focus:border-emerald-500'"
                            >
                        </div>

                        <Link href="/training" 
                              class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white text-center font-black rounded-2xl text-xs uppercase tracking-wider transition-all block shadow-lg shadow-emerald-600/10 hover:-translate-y-0.5"
                        >
                            {{ content[locale].graduation.homeBtn }}
                        </Link>
                    </div>

                    <!-- Right Column: Official Certificate Mockup -->
                    <div class="md:col-span-3">
                        <div class="rounded-[36px] border-8 p-8 relative overflow-hidden shadow-2xl text-center space-y-6"
                             :class="isDark 
                                 ? 'bg-[#050e0d] border-emerald-500/30 text-slate-350 shadow-emerald-500/5' 
                                 : 'bg-white border-emerald-100 text-slate-700 shadow-xl'"
                        >
                            <!-- Seals & Decoration -->
                            <div class="absolute top-4 left-4 h-10 w-10 border-t-2 border-l-2 border-emerald-500/20"></div>
                            <div class="absolute top-4 right-4 h-10 w-10 border-t-2 border-r-2 border-emerald-500/20"></div>
                            <div class="absolute bottom-4 left-4 h-10 w-10 border-b-2 border-l-2 border-emerald-500/20"></div>
                            <div class="absolute bottom-4 right-4 h-10 w-10 border-b-2 border-r-2 border-emerald-500/20"></div>

                            <div class="space-y-2">
                                <span class="text-[9px] uppercase font-black text-emerald-500 tracking-widest">Certificate of Finance Excellence</span>
                                <h3 class="text-xl sm:text-2xl font-black font-outfit tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-400 uppercase">
                                    {{ content[locale].graduation.certTitle }}
                                </h3>
                                <p class="text-xs uppercase font-extrabold text-slate-500">{{ content[locale].graduation.certSubtitle }}</p>
                            </div>

                            <div class="py-4 border-y border-slate-500/10">
                                <span class="text-xs text-slate-500 block uppercase mb-1">PROUDLY PRESENTED TO</span>
                                <span class="text-2xl sm:text-3xl font-black font-outfit" :class="isDark ? 'text-white' : 'text-slate-900'">
                                    {{ userName }}
                                </span>
                            </div>

                            <p class="text-[11px] leading-relaxed font-semibold max-w-md mx-auto">
                                {{ content[locale].graduation.certBody }}
                            </p>

                            <div class="flex justify-between items-center text-[10px] font-bold text-slate-500 pt-4 uppercase">
                                <span>{{ content[locale].graduation.certDate }}</span>
                                <span class="text-emerald-455 font-extrabold">GGAA Audit Academy</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </main>

        <!-- Footer Operations Title -->
        <footer class="w-full py-4 border-t transition-colors text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest"
                :class="isDark ? 'border-slate-850 bg-[#050b0a]' : 'border-slate-200 bg-slate-100'"
        >
            GGAA Systems Portal • finance simulator
        </footer>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap');
.font-outfit {
    font-family: 'Outfit', sans-serif;
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Custom pure-CSS Confetti Particles falling/rising */
.confetti-particle {
    position: absolute;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    animation: confetti-fall 4s linear infinite;
    opacity: 0.7;
}

@keyframes confetti-fall {
    0% { transform: translateY(-50px) rotate(0deg); opacity: 0.7; }
    50% { opacity: 0.9; }
    100% { transform: translateY(600px) rotate(360deg); opacity: 0; }
}

.confetti-particle:nth-child(2) { animation-delay: 0.8s; width: 10px; height: 10px; }
.confetti-particle:nth-child(3) { animation-delay: 1.5s; width: 6px; height: 6px; }
.confetti-particle:nth-child(4) { animation-delay: 2.2s; width: 9px; height: 9px; }
.confetti-particle:nth-child(5) { animation-delay: 3s; width: 7px; height: 7px; }
</style>
