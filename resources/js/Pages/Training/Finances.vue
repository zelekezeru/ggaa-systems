<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    BanknotesIcon, AcademicCapIcon,
    ChevronLeftIcon, ChevronRightIcon,
    ArrowLeftIcon, SunIcon, MoonIcon,
    CheckCircleIcon, ExclamationTriangleIcon,
    CalculatorIcon, ShieldCheckIcon,
    SparklesIcon, ForwardIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const isDark = ref(true);
const currentStep = ref(0); // 0: Overview, 1: Tour Bills/Invoices, 2: Lab 1 (COGS), 3: Tour Ledger Tax Slab, 4: Lab 2 (Tax), 5: Lab 3 (Match), 6: Graduation
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
// LAB 1 STATE: COGS RECONCILER (OPTIONAL)
// ==========================================
const inputCogs = ref('');
const lab1Error = ref('');
const lab1Passed = ref(false);
const lab1Skipped = ref(false);

function checkLab1() {
    lab1Error.value = '';
    // Available = 150k + 230k = 380k. COGS = 380k - 100k = 280,000 ETB
    const cleanVal = inputCogs.value.replace(/[^0-9]/g, '');
    if (cleanVal === '280000') {
        lab1Passed.value = true;
    } else {
        if (locale.value === 'en') {
            lab1Error.value = "❌ Incorrect COGS. Calculation: (150,000 + 230,000) - 100,000 = 280,000 ETB.";
        } else {
            lab1Error.value = "❌ የተሳሳተ ስሌት። ማሳሰቢያ፡ (150,000 + 230,000) - 100,000 = 280,000 ብር።";
        }
    }
}

function skipLab1() {
    lab1Skipped.value = true;
    lab1Passed.value = true;
    inputCogs.value = '280,000';
}

// ==========================================
// LAB 2 STATE: TAX ENGINE SLAB (OPTIONAL)
// ==========================================
const inputTax = ref('');
const lab2Error = ref('');
const lab2Passed = ref(false);
const lab2Skipped = ref(false);

function checkLab2() {
    lab2Error.value = '';
    // Formula: (300,000 * 0.35) - 24,600 = 80,400 ETB
    const cleanVal = inputTax.value.replace(/[^0-9]/g, '');
    if (cleanVal === '80400') {
        lab2Passed.value = true;
    } else {
        if (locale.value === 'en') {
            lab2Error.value = "❌ Incorrect Tax. Slab: (300,000 * 0.35) - 24,600 = 80,400 ETB.";
        } else {
            lab2Error.value = "❌ የተሳሳተ የታክስ መጠን። ቀመር፡ (300,000 * 0.35) - 24,600 = 80,400 ብር።";
        }
    }
}

function skipLab2() {
    lab2Skipped.value = true;
    lab2Passed.value = true;
    inputTax.value = '80,400';
}

// ==========================================
// LAB 3 STATE: INVOICE SETTLEMENT (OPTIONAL)
// ==========================================
const selectedDeposit = ref(''); // 'd1' (correct), 'd2'
const lab3Passed = ref(false);
const lab3Skipped = ref(false);

function matchInvoice() {
    if (selectedDeposit.value === 'd1') {
        lab3Passed.value = true;
    }
}

function skipLab3() {
    lab3Skipped.value = true;
    lab3Passed.value = true;
    selectedDeposit.value = 'd1';
}

// ==========================================
// GRADUATION & BADGE SAVING
// ==========================================
watch(currentStep, (newStep) => {
    if (newStep === 6) {
        localStorage.setItem('training-completed-finances', 'true');
    }
});

// Bilingual content translation
const content = {
    en: {
        backBtn: "Back to Tracks",
        headerTitle: "Finance Academy",
        headerSubtitle: "Entry Introduction & Portal Tour",
        stepNames: ["Introduction", "Invoicing Tour", "Lab 1: COGS", "Tax Slab Tour", "Lab 2: Tax Engine", "Lab 3: Bank Match", "Graduation 🎓"],
        skipPracticeBtn: "Skip Practice & Continue ➔",
        
        overview: {
            title: "Welcome to GGAA Finance Portal Tour!",
            desc1: "This presentation is an entry introduction designed for accountants and bookkeepers using the GGAA system for the first time. We will tour the financial modules, invoicing stages, and tax engine parameters.",
            desc2: "Hands-on finance sandboxes are available on relevant slides to practice calculations. You can complete the exercises or click 'Skip Practice' to continue reading.",
            startBtn: "Start Presentation Tour",
        },
        tourBills: {
            title: "Chapter 1: Bills & Invoices Tour",
            desc1: "The finance center handles invoicing states, bank account balances, and automated matching. Key attributes you will manage include:",
            pt1Title: "🧾 1. Invoicing Stages",
            pt1Desc: "Track invoices through Draft, Sent, Unpaid, and Paid states to avoid outstanding debt.",
            pt2Title: "💰 2. CBE Bank Deposit Settlement",
            pt2Desc: "Match incoming Bank deposit references and descriptions to outstanding invoice IDs to settle payments.",
            pt3Title: "🔒 3. PDF Verification & QR Seals",
            pt3Desc: "Generate audited reports embedded with secure QR verification seals for government inspectors.",
            nextBtn: "Go to Lab 1 (COGS Audit)",
        },
        lab1: {
            guideTitle: "Lab 1 (Optional): COGS Audit",
            intro: "Double-entry errors in starting/ending inventories block ledger submissions. You must calculate Cost of Goods Sold (COGS) to reconcile the ledger.",
            instructionTitle: "Objective (Optional):",
            inst1: "1. Review the inventory figures in the visual simulator.",
            inst2: "2. Formula: COGS = (Beginning Inventory + Purchases) - Ending Inventory.",
            inst3: "3. Input the correct COGS value in the field, or click 'Skip Practice'.",
            panelTitle: "Zenith PLC Ledger Simulator",
            begInv: "Beginning Inventory:",
            purchases: "This Year Purchases:",
            endInv: "Ending Inventory:",
            cogsLabel: "Enter Calculated COGS (ETB):",
            validateBtn: "Reconcile & Save",
            successMsg: "🎉 Success! COGS is correctly reconciled at 280,000.00 ETB. Ledger is balanced.",
        },
        tourTax: {
            title: "Chapter 2: Financial Ledgers & Tax Slabs",
            desc1: "GGAA automates tax calculations directly inside the Monthly Ledger model. To ensure tax returns comply with Ethiopian regulations, the system integrates the following attributes:",
            pt1Title: "📈 1. P&L Item Parsing",
            pt1Desc: "Standard P&L formulas (Gross Profit, Total Expenses, and Net Profits) are recomputed in PHP using Eloquent Accessors, avoiding spreadsheet formula breaks.",
            pt2Title: "⚖️ 2. Ethiopian Business Income Tax Slab",
            pt2Desc: "Computes corporate profit tax using the official Category A/B slab formula: (Net Profit * tax_rate%) - 24,600 (deducting the top-bracket offset; default rate is 35%).",
            pt3Title: "📂 3. Bank CSV Statement Imports",
            pt3Desc: "Imports commercial bank statements to parse closing balances, LC margins released, and loans.",
            nextBtn: "Go to Lab 2 (Tax Slab Engine)",
        },
        lab2: {
            guideTitle: "Lab 2 (Optional): Tax Slab Calculator",
            intro: "The system enforces the corporate tax slab: (Net Profit * Tax Rate) - Bracket Deduction. Standard rate is 35% with a deduction of 24,600 ETB.",
            instructionTitle: "Objective (Optional):",
            inst1: "1. Client Net Profit is 300,000 ETB.",
            inst2: "2. Slab: (300,000 * 0.35) - 24,600 = 80,400 ETB.",
            inst3: "3. Enter the profit tax payable, or click 'Skip Practice'.",
            panelTitle: "Ethiopian Tax Slab Engine",
            netProfit: "Client Net Profit (NIBT):",
            taxRate: "Corporate Tax Rate:",
            deduction: "Standard Bracket Deduction:",
            taxLabel: "Enter Profit Tax Payable (ETB):",
            validateBtn: "Submit Tax Return",
            successMsg: "🎉 Success! Corporate profit tax calculated correctly at 80,400.00 ETB. Document submitted.",
        },
        lab3: {
            guideTitle: "Lab 3 (Optional): CBE Bank Matching",
            intro: "Unpaid invoices are settled by matching transaction references on CBE bank statements.",
            instructionTitle: "Objective (Optional):",
            inst1: "1. Locate the Unpaid Invoice (INV-2026-004) for Abay Trading (75,000 ETB).",
            inst2: "2. Match it with the correct bank deposit amount and reference in the simulator.",
            inst3: "3. Click 'Match & Settle', or click 'Skip Practice' to graduate.",
            panelTitle: "Bank Reconciliation console",
            invoiceTitle: "Outstanding Invoice:",
            invoiceClient: "Client: Abay Trading",
            invoiceAmount: "Amount: 75,000.00 ETB",
            depLabel: "Select Matching Bank Deposit:",
            dep1: "Ref: TXN90231 — Amount: 75,000.00 ETB (Note: Abay Trading Settlement)",
            dep2: "Ref: TXN80124 — Amount: 12,000.00 ETB (Note: Petty Cash)",
            matchBtn: "Match & Settle Invoice",
            successMsg: "🎉 Success! Bank deposit references matched. Invoice marked as PAID.",
        },
        graduation: {
            title: "Congratulations, Finance Auditor!",
            subtitle: "You have completed the entire GGAA Finance Portal Tour.",
            badgeLabel: "Unlocked Profile Badge:",
            badgeTitle: "📈 Tax Guru",
            badgeDesc: "Successfully completed financial ledger and slab calculation introduction course.",
            certTitle: "CERTIFIED FINANCE AUDITOR",
            certSubtitle: "GGAA Systems Finance & Audit Academy",
            certBody: "This certifies that the holder has completed the introductory presentation tour for finance specialists, demonstrating knowledge of invoice statuses, COGS inventory audits, Ethiopian corporate profit tax slabs, and bank matching reconciliations.",
            certDate: "Certified on: June 2026",
            certNameLabel: "Change Certificate Name:",
            homeBtn: "Return to Training Hub",
        }
    },
    am: {
        backBtn: "ወደ ስልጠናዎች ተመለስ",
        headerTitle: "የፋይናንስ ማሰልጠኛ ክፍል",
        headerSubtitle: "የእለታዊ ስራዎች ማስመሰያ ገጽ",
        stepNames: ["መግቢያ", "ደረሰኞች ቱር", "ላብ 1: COGS", "የታክስ ስሌት ቱር", "ላብ 2: ታክስ ማስሊያ", "ላብ 3: ባንክ ማዛመድ", "ምረቃ 🎓"],
        skipPracticeBtn: "ልምምዱን እለፍና ቀጥል ➔",
        
        overview: {
            title: "እንኳን ወደ ፋይናንስ የስራ ገጽ ጉብኝት በደህና መጡ!",
            desc1: "ይህ ስላይድ ገለጻ የሂሳብ ባለሙያዎች እና አካውንታንቶች የፋይናንስ ሲስተሙን ለመጀመሪያ ጊዜ ሲጠቀሙ የሚረዱት መግቢያ ነው። የደረሰኞችን ደረጃዎች፣ ወርሃዊ ፎርሞችን እና የታክስ መለኪያዎችን እናስረዳለን።",
            desc2: "በጉብኝቱ ወቅት የተለያዩ ስሌቶችን የሚለማመዱባቸው ተግባራዊ ሳጥኖች ያገኛሉ። እነሱን መስራት ወይም 'ልምምዱን እለፍ' የሚለውን በመጫን ገለጻውን ማንበብ ይችላሉ።",
            startBtn: "የገለጻ ጉብኝቱን ጀምር",
        },
        tourBills: {
            title: "ምዕራፍ 1፡ የደረሰኞች እና ክፍያዎች ጉብኝት",
            desc1: "የፋይናንስ ክፍሉ ደረሰኞችን ማውጣት፣ የባንክ ሂሳቦችን እና ክፍያዎችን ማመሳሰልን ይቆጣጠራል፡",
            pt1Title: "🧾 1. የደረሰኞች ደረጃ (Invoicing Stages)",
            pt1Desc: "ደንበኞች ክፍያ ሳይፈጽሙ እንዳይቀሩ ደረሰኞችን ረቂቅ፣ የተላከ፣ ያልተከፈለ እና የተከፈለ በማለት ደረጃቸውን ይከታተላል።",
            pt2Title: "💰 2. የCBE ባንክ ሪፖርት ማመሳሰል",
            pt2Desc: "በባንክ በኩል የገባውን የማመሳከሪያ ቁጥር (Ref Number) እና የገንዘብ መጠን ካልተከፈሉ ደረሰኞች ጋር በማዛመድ ክፍያውን ያጸድቃል።",
            pt3Title: "🔒 3. የPDF ሪፖርት እና QR ማኅተም",
            pt3Desc: "ለገቢዎች ቁጥጥር የሚሆኑ የጸደቁ ሰነዶችን በደህንነት የ QR ማኅተም በማካተት በ PDF ያዘጋጃል።",
            nextBtn: "ወደ ላብ 1 ይለፉ (COGS መፍትሄ)",
        },
        lab1: {
            guideTitle: "ላብ 1 (አማራጭ): የሸቀጥ ሽያጭ ወጪ (COGS) ኦዲት",
            intro: "የክምችት እቃዎች (Inventory) ስሌት ላይ የሚፈጠር ስህተት የሂሳብ ሰነዶችን እንዳይላኩ ይከለክላል። የሸቀጥ ሽያጭ ወጪን (COGS) በትክክል ማስላት አለብዎት።",
            instructionTitle: "መመሪያ (አማራጭ):",
            inst1: "1. በሳጥኑ ውስጥ ያሉትን የእቃዎች መረጃ ይመልከቱ።",
            inst2: "2. ቀመር፡ COGS = (የመጀመሪያ እቃ ክምችት + ግዢዎች) - የመጨረሻ እቃ ክምችት።",
            inst3: "3. ትክክለኛውን የCOGS መጠን በሳጥኑ ውስጥ ያስገቡ፣ ወይም 'ልምምዱን እለፍ' የሚለውን ይጫኑ።",
            panelTitle: "የZenith PLC የሂሳብ መዝገብ ማሳያ",
            begInv: "የመጀመሪያ እቃ ክምችት:",
            purchases: "በወሩ የተገዙ እቃዎች:",
            endInv: "የመጨረሻ እቃ ክምችት:",
            cogsLabel: "የተሰላውን COGS ያስገቡ (ብር):",
            validateBtn: "አረጋግጥና አስቀምጥ",
            successMsg: "🎉 እንኳን ደስ አለዎት! የሸቀጥ ሽያጭ ወጪ (COGS) 280,000.00 ብር ተብሎ በትክክል ተሰልቷል። ሂሳቡ ተስተካክሏል።",
        },
        tourTax: {
            title: "ምዕራፍ 2፡ ወርሃዊ መዛግብት እና የታክስ ቀመር",
            desc1: "ጂጂኤኤ በወርሃዊ መዛግብት ውስጥ የታክስ ስሌቶችን በራስ-ሰር ይሰራል፡",
            pt1Title: "📈 1. የትርፍ እና ኪሳራ (P&L) ስሌቶች",
            pt1Desc: "ጠቅላላ ሽያጭ፣ የሸቀጥ ወጪ፣ እና የተጣራ ትርፍ ስሌቶች በPHP Accessors አማካኝነት በሲስተሙ ውስጥ በራስ-ሰር ይሰላሉ።",
            pt2Title: "⚖️ 2. የኢትዮጵያ የኮርፖሬት ትርፍ ታክስ ቀመር",
            pt2Desc: "በኢትዮጵያ የታክስ ህግ መሰረት የኮርፖሬት ትርፍ ታክስን ይሰላል፡ (የተጣራ ትርፍ * 35%) - 24,600 (ከተቀናሽ ጋር፤ መደበኛው መጠን 35% ነው)።",
            pt3Title: "📂 3. የባንክ CSV መግለጫ ማስገባት",
            pt3Desc: "የንግድ ባንክ መግለጫዎችን CSV ፋይል ቀጥታ በማስገባት የተረጋገጠ ሂሳብን፣ ብድርን እና ማስተላለፊያዎችን በራሱ ይለያል።",
            nextBtn: "ወደ ላብ 2 ይለፉ (ታክስ ማስሊያ)",
        },
        lab2: {
            guideTitle: "ላብ 2 (አማራጭ): የታክስ ቀመር ማስሊያ",
            intro: "የኮርፖሬት ትርፍ ታክስ ስሌት ቀመር፡ (የተጣራ ትርፍ * 35%) - 24,600 ብር ተቀናሽ።",
            instructionTitle: "መመሪያ (አማራጭ):",
            inst1: "1. የደንበኛው የተጣራ ትርፍ 300,000 ብር ነው።",
            inst2: "2. ስሌት፡ (300,000 * 0.35) - 24,600 = 80,400 ብር።",
            inst3: "3. የሚከፈለውን የትርፍ ታክስ ያስገቡ፣ ወይም 'ልምምዱን እለፍ' የሚለውን ይጫኑ።",
            panelTitle: "የኢትዮጵያ የታክስ ቀመር ማስሊያ",
            netProfit: "የተጣራ የንግድ ትርፍ:",
            taxRate: "የታክስ መቶኛ (Rate):",
            deduction: "ህጋዊ ተቀናሽ (Deduction):",
            taxLabel: "የሚከፈለውን ትርፍ ታክስ ያስገቡ (ብር):",
            validateBtn: "ታክስ ሪፖርት አቅርብ",
            successMsg: "🎉 እንኳን ደስ አለዎት! የሚከፈለው ትርፍ ታክስ 80,400.00 ብር ተብሎ በትክክል ተሰልቷል። ታክስ ሪፖርት ተልኳል።",
        },
        lab3: {
            guideTitle: "ላብ 3 (አማራጭ): የባንክ ሪፖርት ማመሳሰል",
            intro: "ያልተከፈሉ ደረሰኞችን ለማጽደቅ በባንክ በኩል የገባውን የክፍያ ማመሳከሪያ መረጃ ማዛመድ አለብዎት።",
            instructionTitle: "መመሪያ (አማራጭ):",
            inst1: "1. የ Abay Trading ያልተከፈለ ደረሰኝ (75,000 ብር) ይመልከቱ።",
            inst2: "2. በባንክ በኩል ከገባው ትክክለኛ የገንዘብ መጠን እና ማመሳከሪያ ቁጥር ጋር ያዛምዱት።",
            inst3: "3. 'ማዛመጃውን አረጋግጥ' የሚለውን ይጫኑ፣ ወይም ለመጨረስ 'ልምምዱን እለፍ' ይጫኑ።",
            panelTitle: "የባንክ ክፍያዎች ማመሳሰሪያ ሰሌዳ",
            invoiceTitle: "ያልተከፈለ ደረሰኝ መረጃ:",
            invoiceClient: "ደንበኛ: Abay Trading",
            invoiceAmount: "የገንዘብ መጠን: 75,000.00 ብር",
            depLabel: "የባንክ መዝገብ ምረጥ:",
            dep1: "Ref: TXN90231 — Amount: 75,000.00 ብር (ማስታወሻ: Abay Trading Settlement)",
            dep2: "Ref: TXN80124 — Amount: 12,000.00 ብር (ማስታወሻ: Petty Cash)",
            matchBtn: "ማዛመጃውን አረጋግጥና ክፈል።",
            successMsg: "🎉 እንኳን ደስ አለዎት! የባንክ ክፍያ ማመሳከሪያው ተዛምዷል። ደረሰኙ 'ተከፍሏል' ተብሎ ተመዝግቧል።",
        },
        graduation: {
            title: "እንኳን ደስ አለዎት፣ የፋይናንስ ኦዲተር!",
            subtitle: "የፋይናንስ ስራዎች እና የታክስ ቀመሮች ገለጻ ጉብኝትን በተሳካ ሁኔታ አጠናቀዋል።",
            badgeLabel: "ያገኙት የክብር ባጅ:",
            badgeTitle: "📈 የታክስ ጌታ",
            badgeDesc: "የፋይናንስ መዛግብትን እና የታክስ ቀመሮችን በሚገባ የተረዳ።",
            certTitle: "የፋይናንስ ኦዲተር ሰርተፊኬት",
            certSubtitle: "ጂጂኤኤ ሲስተምስ ፋይናንስ አካዳሚ",
            certBody: "ይህ ሰርተፊኬት ባለቤቱ የፋይናንስ ስራዎች መግቢያ ገለጻዎችን ማጠናቀቁን፣ በሽያጭ ወጪ ክምችት፣ በትርፍ ታክስ ስሌት እና በባንክ ክፍያዎች ማመሳከር ላይ ሙሉ እውቀት ማሳየቱን ያረጋግጣል።",
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
         :class="isDark ? 'bg-[#040810] text-slate-100' : 'bg-slate-50 text-slate-800'"
    >
        <!-- Background Blur Particles -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] blur-[150px] rounded-full transition-opacity duration-300"
                 :class="isDark ? 'bg-emerald-600/10 opacity-100' : 'bg-emerald-500/5 opacity-40'"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[35%] h-[35%] blur-[150px] rounded-full transition-opacity duration-300"
                 :class="isDark ? 'bg-teal-650/10 opacity-100' : 'bg-teal-500/5 opacity-40'"></div>
        </div>

        <!-- Top Navigation Bar -->
        <header class="w-full py-4 px-6 lg:px-12 flex justify-between items-center border-b backdrop-blur-md sticky top-0 z-50 transition-colors"
                :class="isDark ? 'border-slate-855 bg-[#040810]/80' : 'border-slate-200 bg-white/80'"
        >
            <Link href="/training" class="flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-emerald-505 transition-colors group">
                <ArrowLeftIcon class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                {{ content[locale].backBtn }}
            </Link>
            
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center font-black font-outfit shadow-lg shadow-emerald-500/20 text-white">
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

        <!-- Main Body: Course Steps -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 md:px-8 py-6 relative z-10 flex flex-col justify-center">
            
            <!-- Step Navigation Tabs -->
            <div class="flex justify-center flex-wrap gap-2 mb-8 bg-slate-950/20 backdrop-blur-md p-1.5 rounded-2xl border border-slate-500/10 max-w-2xl mx-auto w-full">
                <button v-for="(stepName, index) in content[locale].stepNames" :key="index"
                        @click="currentStep = index"
                        class="px-3.5 py-2 text-xs font-bold font-outfit rounded-xl transition-all flex items-center gap-1.5"
                        :class="[
                            currentStep === index 
                                ? 'bg-emerald-600 text-white shadow-md' 
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-550/5'
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
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-black uppercase tracking-widest border border-emerald-500/20 font-outfit">
                    <AcademicCapIcon class="h-5 w-5" />
                    {{ locale === 'en' ? 'Finance Entry Presentation' : 'የፋይናንስ መግቢያ የስላይድ ገለጻ' }}
                </span>
                
                <h1 class="text-4xl sm:text-6xl font-black font-outfit tracking-tighter leading-tight"
                    :class="isDark ? 'text-white' : 'text-slate-955'"
                >
                    {{ content[locale].overview.title }}
                </h1>
                
                <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-300' : 'text-slate-650'">
                    {{ content[locale].overview.desc1 }}
                </p>
                <p class="text-base leading-relaxed font-medium" :class="isDark ? 'text-slate-450' : 'text-slate-550'">
                    {{ content[locale].overview.desc2 }}
                </p>

                <div class="pt-6">
                    <button @click="currentStep = 1" class="px-8 py-4 bg-emerald-650 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-xl shadow-emerald-600/30 hover:scale-105 active:scale-95 transition-all flex items-center gap-2 text-sm uppercase tracking-wider font-outfit mx-auto">
                        {{ content[locale].overview.startBtn }}
                        <ChevronRightIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- STEP 1: TOUR INVOICING -->
            <div v-if="currentStep === 1" class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-stretch py-2 min-h-[500px] animate-fade-in">
                <div class="flex flex-col justify-between space-y-6 rounded-[32px] p-6 lg:p-8 border backdrop-blur-md"
                     :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-white border-slate-200 shadow-lg'"
                >
                    <div class="space-y-4">
                        <span class="text-xs font-black uppercase text-emerald-450 tracking-widest block font-outfit">Chapter 1 of 3</span>
                        <h2 class="text-3xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                            {{ content[locale].tourBills.title }}
                        </h2>
                        <p class="text-sm font-medium leading-relaxed" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                            {{ content[locale].tourBills.desc1 }}
                        </p>

                        <div class="space-y-4 pt-2">
                            <div class="p-4 rounded-2xl border" :class="isDark ? 'bg-slate-950/20 border-slate-850' : 'bg-slate-50 border-slate-200'">
                                <h4 class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">{{ content[locale].tourBills.pt1Title }}</h4>
                                <p class="text-xs mt-1" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ content[locale].tourBills.pt1Desc }}</p>
                            </div>
                            <div class="p-4 rounded-2xl border" :class="isDark ? 'bg-slate-950/20 border-slate-850' : 'bg-slate-50 border-slate-200'">
                                <h4 class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">{{ content[locale].tourBills.pt2Title }}</h4>
                                <p class="text-xs mt-1" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ content[locale].tourBills.pt2Desc }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t flex justify-between" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                        <button @click="currentStep = 0" class="px-4 py-2 text-xs font-bold uppercase border rounded-xl" :class="isDark ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-600'">
                            {{ locale === 'en' ? 'Back' : 'ተመለስ' }}
                        </button>
                        <button @click="currentStep = 2" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5">
                            {{ content[locale].tourBills.nextBtn }}
                            <ChevronRightIcon class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Right Side Visual mockup -->
                <div class="rounded-[32px] p-6 border flex items-center justify-center" :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200 shadow-md'">
                    <div class="w-full max-w-md border rounded-[28px] p-6 space-y-4" :class="isDark ? 'bg-[#0a0f1d] border-slate-855' : 'bg-slate-50 border-slate-200 shadow-sm'">
                        <h4 class="text-xs font-black uppercase text-slate-500 tracking-wider pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">Invoice Ledger Mockup</h4>
                        <div class="h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-between px-4 text-xs font-bold">
                            <span>Pending Matches: 1 Transaction</span>
                            <span class="text-emerald-500">✓ In-sync</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: TOUR FINANCIAL LEDGERS & TAX CALCULATIONS -->
            <div v-if="currentStep === 3" class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-stretch py-2 min-h-[500px] animate-fade-in">
                <div class="flex flex-col justify-between space-y-6 rounded-[32px] p-6 lg:p-8 border backdrop-blur-md"
                     :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-white border-slate-200 shadow-lg'"
                >
                    <div class="space-y-4">
                        <span class="text-xs font-black uppercase text-emerald-450 tracking-widest block font-outfit">Chapter 2 of 3</span>
                        <h2 class="text-3xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                            {{ content[locale].tourTax.title }}
                        </h2>
                        <p class="text-sm font-medium leading-relaxed" :class="isDark ? 'text-slate-350' : 'text-slate-655'">
                            {{ content[locale].tourTax.desc1 }}
                        </p>

                        <div class="space-y-3.5 pt-1">
                            <div class="p-3 border rounded-2xl bg-slate-550/5" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <h4 class="text-xs font-black text-emerald-450 uppercase">{{ content[locale].tourTax.pt1Title }}</h4>
                                <p class="text-xs mt-0.5" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ content[locale].tourTax.pt1Desc }}</p>
                            </div>
                            <div class="p-3 border rounded-2xl bg-slate-550/5" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <h4 class="text-xs font-black text-emerald-450 uppercase">{{ content[locale].tourTax.pt2Title }}</h4>
                                <p class="text-xs mt-0.5" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ content[locale].tourTax.pt2Desc }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t flex justify-between" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                        <button @click="currentStep = 2" class="px-4 py-2 text-xs font-bold uppercase border rounded-xl" :class="isDark ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-600'">
                            {{ locale === 'en' ? 'Back' : 'ተመለስ' }}
                        </button>
                        <button @click="currentStep = 4" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5">
                            {{ content[locale].tourTax.nextBtn }}
                            <ChevronRightIcon class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Right Side Visual mockup -->
                <div class="rounded-[32px] p-6 border flex items-center justify-center" :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200 shadow-md'">
                    <div class="w-full max-w-md border rounded-[28px] p-6 space-y-4" :class="isDark ? 'bg-[#0a0f1d] border-slate-855' : 'bg-slate-50 border-slate-200 shadow-sm'">
                        <h4 class="text-xs font-black uppercase text-slate-500 tracking-wider pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">Tax Engine Formula Registry</h4>
                        <div class="p-3 bg-emerald-500/10 border border-emerald-500/25 rounded-xl text-xs font-bold text-center space-y-1">
                            <span class="text-emerald-450 block text-[9px] uppercase">Corporate profit tax Formula</span>
                            <span :class="isDark ? 'text-white' : 'text-slate-900'">(Net Profit × 35%) − 24,600 ETB</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SPLIT SCREEN LAYOUT FOR CHALLENGES (2, 4, 5) -->
            <div v-if="currentStep === 2 || currentStep === 4 || currentStep === 5" class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-stretch py-2 min-h-[500px]">
                
                <!-- LEFT SIDE: LAB INSTRUCTIONS -->
                <div class="flex flex-col justify-between space-y-6 rounded-[32px] p-6 lg:p-8 border backdrop-blur-md"
                     :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-white border-slate-200 shadow-lg'"
                >
                    <div class="space-y-4">
                        <span class="text-xs font-black uppercase text-emerald-400 tracking-widest block font-outfit">Lab Exercise (Optional)</span>
                        <h2 class="text-2xl lg:text-3xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                            {{ content[locale][currentStep === 2 ? 'lab1' : (currentStep === 4 ? 'lab2' : 'lab3')].guideTitle }}
                        </h2>
                        
                        <p class="text-sm font-medium leading-relaxed" :class="isDark ? 'text-slate-350' : 'text-slate-655'">
                            {{ content[locale][currentStep === 2 ? 'lab1' : (currentStep === 4 ? 'lab2' : 'lab3')].intro }}
                        </p>
                        
                        <!-- Objective panel -->
                        <div class="p-5 rounded-2xl space-y-2.5" :class="isDark ? 'bg-slate-950/40 border border-slate-855' : 'bg-slate-50 border border-slate-200'">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-500">
                                {{ content[locale][currentStep === 2 ? 'lab1' : (currentStep === 4 ? 'lab2' : 'lab3')].instructionTitle }}
                            </h4>
                            <ul class="space-y-2 text-xs font-semibold leading-relaxed" :class="isDark ? 'text-slate-400' : 'text-slate-650'">
                                <li>{{ content[locale][currentStep === 2 ? 'lab1' : (currentStep === 4 ? 'lab2' : 'lab3')].inst1 }}</li>
                                <li>{{ content[locale][currentStep === 2 ? 'lab1' : (currentStep === 4 ? 'lab2' : 'lab3')].inst2 }}</li>
                                <li>{{ content[locale][currentStep === 2 ? 'lab1' : (currentStep === 4 ? 'lab2' : 'lab3')].inst3 }}</li>
                            </ul>
                        </div>

                        <!-- LAB 1 COGS FIELD -->
                        <div v-if="currentStep === 2" class="space-y-3 pt-1">
                            <div class="space-y-1">
                                <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].lab1.cogsLabel }}</label>
                                <input type="text" v-model="inputCogs" placeholder="e.g. 280,000" 
                                       class="w-full bg-slate-500/10 border px-4 py-2 rounded-xl font-bold font-mono text-xs outline-none transition-all"
                                       :class="isDark ? 'border-slate-800 text-white focus:border-emerald-500' : 'border-slate-250 text-slate-900 focus:border-emerald-500'"
                                       :disabled="lab1Passed"
                                >
                            </div>
                            <button v-if="!lab1Passed" @click="checkLab1" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all">
                                {{ content[locale].lab1.validateBtn }}
                            </button>
                            <p v-if="lab1Error" class="text-xs font-bold text-rose-500">{{ lab1Error }}</p>
                        </div>

                        <!-- LAB 2 TAX SLAB FIELD -->
                        <div v-if="currentStep === 4" class="space-y-3 pt-1">
                            <div class="space-y-1">
                                <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].lab2.taxLabel }}</label>
                                <input type="text" v-model="inputTax" placeholder="e.g. 80,400" 
                                       class="w-full bg-slate-500/10 border px-4 py-2 rounded-xl font-bold font-mono text-xs outline-none transition-all"
                                       :class="isDark ? 'border-slate-800 text-white focus:border-emerald-500' : 'border-slate-250 text-slate-900 focus:border-emerald-500'"
                                       :disabled="lab2Passed"
                                >
                            </div>
                            <button v-if="!lab2Passed" @click="checkLab2" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all">
                                {{ content[locale].lab2.validateBtn }}
                            </button>
                            <p v-if="lab2Error" class="text-xs font-bold text-rose-500">{{ lab2Error }}</p>
                        </div>

                        <!-- LAB 3 BANK MATCH FIELD -->
                        <div v-if="currentStep === 5" class="space-y-3 pt-1">
                            <div class="space-y-1">
                                <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].lab3.depLabel }}</label>
                                <select v-model="selectedDeposit" class="w-full bg-slate-500/10 border px-4 py-2.5 rounded-xl font-bold text-xs outline-none transition-all" :class="isDark ? 'border-slate-800 text-white focus:border-emerald-500' : 'border-slate-250 text-slate-900 focus:border-emerald-500'" :disabled="lab3Passed">
                                    <option value="" disabled>{{ locale === 'en' ? 'Select matching deposit transaction' : 'የሚዛመደውን ክፍያ ይምረጡ' }}</option>
                                    <option value="d1">{{ content[locale].lab3.dep1 }}</option>
                                    <option value="d2">{{ content[locale].lab3.dep2 }}</option>
                                </select>
                            </div>
                            <button v-if="!lab3Passed" @click="matchInvoice" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all">
                                {{ content[locale].lab3.matchBtn }}
                            </button>
                        </div>
                    </div>

                    <!-- REUSABLE PROGRESS BUTTON -->
                    <div class="pt-4 border-t" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                        <div v-if="(currentStep === 2 && lab1Passed && !lab1Skipped) || (currentStep === 4 && lab2Passed && !lab2Skipped) || (currentStep === 5 && lab3Passed && !lab3Skipped)" 
                             class="p-4 mb-4 rounded-2xl border text-xs font-bold text-emerald-500 bg-emerald-500/10 border-emerald-500/20"
                        >
                            {{ content[locale][currentStep === 2 ? 'lab1' : (currentStep === 4 ? 'lab2' : 'lab3')].successMsg }}
                        </div>

                        <!-- Optional Skip button for interactive stages -->
                        <div v-if="(currentStep === 2 && !lab1Passed) || (currentStep === 4 && !lab2Passed) || (currentStep === 5 && !lab3Passed)" 
                             class="mb-3.5"
                        >
                            <button @click="currentStep === 2 ? skipLab1() : (currentStep === 4 ? skipLab2() : skipLab3())" 
                                    class="w-full py-2 bg-indigo-500/10 hover:bg-indigo-500/25 text-indigo-400 font-extrabold text-[10px] uppercase tracking-wider rounded-xl transition-all flex items-center justify-center gap-1.5 border border-indigo-500/20"
                            >
                                <ForwardIcon class="h-4 w-4" />
                                {{ content[locale].skipPracticeBtn }}
                            </button>
                        </div>

                        <div class="flex justify-between items-center">
                            <button @click="currentStep--" 
                                    class="px-4 py-2.5 rounded-xl border text-xs font-bold uppercase transition-all"
                                    :class="isDark ? 'border-slate-800 text-slate-400 hover:bg-slate-900' : 'border-slate-250 text-slate-600 hover:bg-slate-100'"
                            >
                                {{ locale === 'en' ? 'Back' : 'ተመለስ' }}
                            </button>
                            
                            <button @click="currentStep++" 
                                    :disabled="currentStep === 2 ? !lab1Passed : currentStep === 4 ? !lab2Passed : !lab3Passed"
                                    class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5"
                            >
                                {{ currentStep === 3 ? content[locale].tourTax.nextBtn : (locale === 'en' ? 'Next Slide' : 'ቀጣይ ስላይድ') }}
                                <ChevronRightIcon class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: SIMULATED OPERATIONS SANDBOX -->
                <div class="rounded-[32px] p-6 lg:p-8 border flex flex-col justify-center relative overflow-hidden"
                     :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-250 shadow-md'"
                >
                    
                    <!-- LAB 1: COGS SIMULATOR -->
                    <div v-if="currentStep === 2" class="space-y-4 w-full text-xs font-semibold leading-relaxed">
                        <div class="flex justify-between items-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest text-left">{{ content[locale].lab1.panelTitle }}</span>
                            <span class="px-2.5 py-0.5 bg-slate-500/10 text-slate-400 rounded text-[9px] font-bold">Meskeram 2026</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ content[locale].lab1.begInv }}</span>
                            <span :class="isDark ? 'text-white' : 'text-slate-900'">150,000.00 ETB</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ content[locale].lab1.purchases }}</span>
                            <span :class="isDark ? 'text-white' : 'text-slate-900'">230,000.00 ETB</span>
                        </div>
                        <div class="flex justify-between pb-2 border-b" :class="isDark ? 'border-slate-855' : 'border-slate-200'">
                            <span class="text-slate-500">{{ content[locale].lab1.endInv }}</span>
                            <span :class="isDark ? 'text-white' : 'text-slate-900'">100,000.00 ETB</span>
                        </div>
                    </div>

                    <!-- LAB 2: TAX ENGINE -->
                    <div v-if="currentStep === 4" class="space-y-4 w-full text-xs font-semibold leading-relaxed">
                        <div class="flex justify-between items-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest text-left">{{ content[locale].lab2.panelTitle }}</span>
                            <span class="px-2.5 py-0.5 bg-emerald-500/10 text-emerald-500 rounded text-[9px] font-bold">Formula Engine Active</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ content[locale].lab2.netProfit }}</span>
                            <span :class="isDark ? 'text-white' : 'text-slate-900'">300,000.00 ETB</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ content[locale].lab2.taxRate }}</span>
                            <span class="text-blue-500">35 %</span>
                        </div>
                        <div class="flex justify-between pb-2 border-b" :class="isDark ? 'border-slate-855' : 'border-slate-200'">
                            <span class="text-slate-500">{{ content[locale].lab2.deduction }}</span>
                            <span class="text-rose-500">24,600.00 ETB</span>
                        </div>
                    </div>

                    <!-- LAB 3: BANK MATCH CONSOLE -->
                    <div v-if="currentStep === 5" class="space-y-4 w-full text-xs font-semibold">
                        <div class="flex justify-between items-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest text-left">{{ content[locale].lab3.panelTitle }}</span>
                            <span class="text-xs font-bold text-amber-500">UNPAID STAGE</span>
                        </div>

                        <div class="p-3.5 border rounded-xl space-y-1 text-left bg-slate-500/5">
                            <span class="text-[9px] uppercase font-black text-slate-500">{{ content[locale].lab3.invoiceTitle }}</span>
                            <h5 class="text-xs font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">INV-2026-004</h5>
                            <p class="text-[11px]">{{ content[locale].lab3.invoiceClient }}</p>
                            <p class="text-[11px] font-black text-emerald-500">{{ content[locale].lab3.invoiceAmount }}</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- STEP 6: GRADUATION -->
            <div v-if="currentStep === 6" class="max-w-4xl mx-auto py-4 space-y-8 animate-fade-in relative z-10">
                <!-- Pure-CSS Confetti Particles -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden">
                    <div class="confetti-particle bg-emerald-500 top-0 left-[10%]"></div>
                    <div class="confetti-particle bg-teal-500 top-0 left-[30%]"></div>
                    <div class="confetti-particle bg-blue-500 top-0 left-[50%]"></div>
                    <div class="confetti-particle bg-indigo-550 top-0 left-[70%]"></div>
                    <div class="confetti-particle bg-purple-500 top-0 left-[90%]"></div>
                </div>

                <div class="text-center space-y-4 max-w-2xl mx-auto">
                    <span class="inline-flex items-center gap-1 bg-emerald-500/10 border border-emerald-500/25 text-emerald-450 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest animate-pulse">
                        <SparklesIcon class="h-4 w-4" />
                        {{ content[locale].graduation.title }}
                    </span>
                    <h2 class="text-4xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                        {{ locale === 'en' ? 'Tour Completed!' : 'የፋይናንስ ጉብኝቱ ተጠናቋል!' }}
                    </h2>
                    <p class="text-base font-semibold" :class="isDark ? 'text-slate-400' : 'text-slate-655'">
                        {{ content[locale].graduation.subtitle }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-8 items-center">
                    <!-- Left Column: Certificate Badge details & User Name Input -->
                    <div class="md:col-span-2 space-y-6">
                        <div class="p-6 rounded-[28px] border space-y-4" :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-white border-slate-200 shadow-lg'">
                            <span class="text-xs font-black uppercase text-slate-500 block">{{ content[locale].graduation.badgeLabel }}</span>
                            <div class="flex items-center gap-4">
                                <span class="h-16 w-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-3xl shadow-lg">📈</span>
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
                              class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white text-center font-black rounded-2xl text-xs uppercase tracking-wider block shadow-lg shadow-emerald-600/10 transition-all"
                        >
                            {{ content[locale].graduation.homeBtn }}
                        </Link>
                    </div>

                    <!-- Right Column: Official Certificate Mockup -->
                    <div class="md:col-span-3">
                        <div class="rounded-[36px] border-8 p-8 relative overflow-hidden shadow-2xl text-center space-y-6"
                             :class="isDark 
                                 ? 'bg-[#040810] border-emerald-500/30 text-slate-350 shadow-emerald-500/5' 
                                 : 'bg-white border-emerald-100 text-slate-700 shadow-xl'"
                        >
                            <!-- Seals & Decoration -->
                            <div class="absolute top-4 left-4 h-10 w-10 border-t-2 border-l-2 border-emerald-500/20"></div>
                            <div class="absolute top-4 right-4 h-10 w-10 border-t-2 border-r-2 border-emerald-500/20"></div>
                            <div class="absolute bottom-4 left-4 h-10 w-10 border-b-2 border-l-2 border-emerald-500/20"></div>
                            <div class="absolute bottom-4 right-4 h-10 w-10 border-b-2 border-r-2 border-emerald-500/20"></div>

                            <div class="space-y-2">
                                <span class="text-[9px] uppercase font-black text-emerald-500 tracking-widest">Certificate of Expertise</span>
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
                                <span class="text-emerald-450 font-extrabold">GGAA Audit Academy</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </main>

        <!-- Footer Operations Title -->
        <footer class="w-full py-4 border-t transition-colors text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest"
                :class="isDark ? 'border-slate-850 bg-[#040810]' : 'border-slate-200 bg-slate-100'"
        >
            GGAA Systems Portal • auditing simulator
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
