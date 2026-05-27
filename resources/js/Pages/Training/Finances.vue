<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    BanknotesIcon, AcademicCapIcon,
    ChevronLeftIcon, ChevronRightIcon,
    ArrowLeftIcon, SunIcon, MoonIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const isDark = ref(true);
const currentSlide = ref(1);
const totalSlides = 9;

// Interactive states: live tax calculator
const mockSales = ref(300000);
const mockCogs = ref(120000);
const mockExpenses = ref(30000);

const computedNetProfit = computed(() => mockSales.value - mockCogs.value - mockExpenses.value);
const computedProfitTax = computed(() => {
    const nibt = computedNetProfit.value;
    if (nibt <= 0) return 0;
    return Math.max(0, (nibt * 0.35) - 24600);
});

onMounted(() => {
    const savedTheme = localStorage.getItem('training-theme');
    if (savedTheme === 'light') {
        isDark.value = false;
    }
    const savedLocale = localStorage.getItem('locale') || 'am';
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

function nextSlide() {
    if (currentSlide.value < totalSlides) currentSlide.value++;
}

function prevSlide() {
    if (currentSlide.value > 1) currentSlide.value--;
}

function goToSlide(n) {
    if (n >= 1 && n <= totalSlides) currentSlide.value = n;
}

const checklistItems = ref([
    { id: 1, text_en: 'Reconcile invoice numbers to confirm that cash machine receipts correspond directly to physical Z-reports.', text_am: 'የደረሰኝ ቁጥሮችን በመፈተሽ የሽያጭ መመዝገቢያ ማሽን ደረሰኞች ከእውነተኛ ወርሃዊ ሪፖርት ጋር መጣጣማቸውን ያረጋግጡ::', checked: false },
    { id: 2, text_en: 'Audit COGS starting inventory against the ending stock values of the previous verified month.', text_am: 'የመጀመሪያ ክምችት እቃዎች ዋጋ ካለፈው ወር ከተረጋገጠው የመጨረሻ እቃ ክምችት ዋጋ ጋር እኩል መሆኑን ያረጋግጡ::', checked: false },
    { id: 3, text_en: 'Verify that electricity, rent, electric (EEU), and depreciation calculations comply with local guidelines.', text_am: 'የቤት ኪራይ፣ የኤሌክትሪክ ክፍያዎች እና የንብረት እርጅና (Depreciation) ስሌቶች ትክክል መሆናቸውን ያረጋግጡ::', checked: false },
    { id: 4, text_en: 'Lock and verify the ledger record, then print the officially signed PDF report.', text_am: 'ወርሃዊ ሂሳቡን ካረጋገጡ በኋላ ቆልፈው በQR ኮድ የተጠበቀ ይፋዊ የፒዲኤፍ ሪፖርት ያትሙ::', checked: false }
]);

function toggleCheck(item) {
    item.checked = !item.checked;
}

const fmt = (v) => new Intl.NumberFormat('en-ET', { minimumFractionDigits: 2 }).format(v);

// Simple and Friendly bilingual slide content
const slides = {
    en: {
        slide1: {
            badge: "Part 3: Finance & Bookkeeping Track",
            title: "Reconciling Ledgers & Invoicing",
            subtitle: "Welcome! This simple guide is designed for our finance specialists. Learn how to double-check monthly ledger details, calculate business taxes, manage billing cycles, and print official PDF reports."
        },
        slide2: {
            badge: "Part 2: What You Will Handle",
            title: "Your Financial Domain",
            subtitle: "At GGAA, you are the final check. Your work is to ensure client bills are collected and financial calculations are 100% correct before government filing:",
            box1: "💳 1. Service Bills & Invoicing",
            box1Desc: "Generating client invoices for our professional services, tracking cash receipts, and managing outstanding accounts.",
            box2: "🧮 2. Ledger Verification",
            box2Desc: "Auditing draft bookkeeping sheets compiled by our accountants. Reviewing cash machine ranges, VAT details, bank balances, and locking files.",
            targetTitle: "SLA Commitments:",
            target1: "Document Range Checks: Confirming receipt number boundaries strictly match physical reports.",
            target2: "Tax Filing Calendar: Verifying and locking ledgers before the 30th of each Ethiopian month.",
            target3: "Outstanding Dues: Checking overdue invoices every single week."
        },
        slide3: {
            badge: "Part 3: Bills & Invoices",
            title: "Creating Service Invoices",
            subtitle: "Our system has a built-in billing manager. You can easily issue invoices for specific accounting services, keeping records organized.",
            point1: "Simple Invoicing stages: Unissued Draft ➔ Sent (Pending Payment) ➔ Fully Settled (Paid).",
            point2: "Payment Mapping: Every bank deposit records bank channels, dates, and transaction references."
        },
        slide4: {
            badge: "Part 4: Sales & Cost of Goods Sold",
            title: "Reconciling Sales & Inventory (COGS)",
            subtitle: "In the ledger entry console, calculations update instantly as you enter values, helping you spot errors immediately.",
            col1: "1. Sales & Receipt Logging",
            cSales: "Cash Register Sales",
            mSales: "Manual Receipt Sales",
            cRange: "Cash Receipts: 10001 ➔ 10320 (319 items)",
            mRange: "Manual Receipts: 4001 ➔ 4050 (49 items)",
            col2: "2. Cost of Buying Stock (COGS)",
            begStock: "Starting Stock",
            purchases: "New Purchases",
            endStock: "Ending Stock",
            formulaLabel: "Computed COGS:",
            formulaDesc: "🧮 Simple Formula: Starting Stock + New Purchases - Ending Stock = Cost of Stock Sold (COGS). Updates live!"
        },
        slide5: {
            badge: "Part 5: Expenses & VAT",
            title: "Operational Expenses & VAT Audits",
            subtitle: "Our ledgers track 17 standard operating expenses and VAT figures, making sure everything is aligned with physical tax vouchers.",
            p1: "🏢 Expenses tracked: rent, salaries, transport, electricity, printing, bank charges, and asset depreciation.",
            p2: "📈 VAT Logs: Sales VAT (15%), Purchase VAT (15%), and Withholding Tax details are audited to match government filings."
        },
        slide6: {
            badge: "Part 6: Tax Calculator",
            title: "The Ethiopian Profit Tax Engine",
            subtitle: "For standard Category A/B corporate businesses, profit tax is assessed at 35% minus a standard deduction. Our system computes this automatically!",
            formulaLabel: "Official Tax Formula:",
            formulaCode: "Tax = (Net Profit * 0.35) - 24,600 ETB",
            widgetTitle: "Try It Yourself: Interactive Tax Calculator",
            widgetInstruction: "Enter mock figures below to see the government profit tax calculate live:",
            t1: "Sales Income:",
            t2: "Cost of Stock (COGS):",
            t3: "Running Expenses:",
            nibtLabel: "Take-home Earnings (Net Profit):",
            taxLabel: "Estimated Profit Tax (35% Slab):",
            widgetTip: "💡 Type your own numbers above to experience the dynamic tax calculation!"
        },
        slide7: {
            badge: "Part 7: Bank Reconciliation",
            title: "Multi-Bank Balances Ledger",
            subtitle: "Most clients use multiple corporate bank accounts. We reconcile bank statements by recording unique balance movements inside the ledger.",
            point1: "LC Margin Release: Reconciling letter-of-credit margins released during import processes.",
            point2: "Movements: Tracking corporate loans, transfers, and correction reversals for each bank account."
        },
        slide8: {
            badge: "Part 8: Security & PDF Export",
            title: "Verifiable PDF Statements & QR Codes",
            subtitle: "Once you lock a ledger as Verified, the system generates a signed PDF report embedded with a secure verification QR code.",
            p1: "🔒 Locked Books: Verified records are locked automatically to prevent typos. The client can only view Verified statements in their portal.",
            p2: "📲 Scan QR Code: Banks or tax auditors can scan the printed QR code to instantly verify its authenticity on our live secure portal."
        },
        slide9: {
            badge: "Part 9: Graduation",
            title: "Your Finance Audit Checklist",
            subtitle: "Follow these simple operational steps every month to keep your financial ledger records 100% compliant:"
        }
    },
    am: {
        slide1: {
            badge: "ክፍል 3፡ የፋይናንስ እና ሂሳብ ስልጠና",
            title: "ወርሃዊ ሂሳብ ማረጋገጥና ክፍያዎች",
            subtitle: "እንኳን ደህና መጡ! ይህ መመሪያ ለፋይናንስ ባለሙያዎቻችን የተዘጋጀ ነው። ወርሃዊ የሂሳብ መዛግብትን እንዴት እንደሚገመግሙ፣ የመንግስት ታክስን እንደሚሰሉ፣ ክፍያዎችን እንደሚያስተዳድሩ እና ሪፖርቶችን እንደሚያትሙ ይማሩ።"
        },
        slide2: {
            badge: "ክፍል 2፡ የፋይናንስ ኃላፊነት",
            title: "የእርስዎ የፋይናንስ ዘርፍ",
            subtitle: "በጂጂኤኤ የፋይናንስ ቡድን የመጨረሻው የጥራት ተቆጣጣሪ ነው። የእርስዎ ስራ ደንበኞች በሰዓቱ መክፈላቸውን እና የታክስ ስሌቶች 100% ትክክል መሆናቸውን ማረጋገጥ ነው፡",
            box1: "💳 1. የክፍያ መጠየቂያ እና ሂሳቦች",
            box1Desc: "ለደንበኞች ለአገልግሎታችን ክፍያ መጠየቂያ ደረሰኝ (Invoices) ማዘጋጀት፣ የክፍያ ደረሰኞችን መሰብሰብ እና ያልተከፈሉ ሂሳቦችን መከታተል።",
            box2: "🧮 2. ወርሃዊ መዛግብትን ማረጋገጥ",
            box2Desc: "በአካውንታንቶች የተሞሉ ወርሃዊ መዛግብትን መገምገም። የሽያጭ ደረሰኞችን፣ የVAT ሪፖርቶችን፣ የባንክ ሂሳቦችን ፈትሾ ማጽደቅ።",
            targetTitle: "የስራ መመዘኛ ግቦች (SLA):",
            target1: "የደረሰኝ ቁጥሮች ማጣራት፡ የደረሰኝ ቁጥሮች ከእውነተኛ የሽያጭ መመዝገቢያ ማሽን ሪፖርቶች ጋር መጣጣማቸውን ማረጋገጥ።",
            target2: "የታክስ የቀን መቁጠሪያ፡ በየኢትዮጵያ ወር እስከ 30ኛው ቀን ድረስ መዛግብትን መርምሮ መቆለፍ።",
            target3: "ያልተከፈሉ ሂሳቦች፡ በየሳምንቱ ያልተከፈሉ የደንበኛ ሂሳቦችን መከታተል።"
        },
        slide3: {
            badge: "ክፍል 3፡ የክፍያ መጠየቂያዎች",
            title: "የክፍያ መጠየቂያ ደረሰኞች (Invoices)",
            subtitle: "የእኛ ሲስተም ለአጠቃቀም ቀላል የሆነ የክፍያ መጠየቂያ ማስተዳደሪያ አለው። ለተለያዩ አገልግሎቶች በቀላሉ ደረሰኝ ማውጣትና ፋይሎችን ማደራጀት ይችላሉ።",
            point1: "ቀላል የክፍያ መጠየቂያ ደረጃዎች፡ ያልተላከ ረቂቅ ➔ የተላከ (ክፍያ የሚጠበቅበት) ➔ ሙሉ በሙሉ የተከፈለ።",
            point2: "የክፍያ ምዝገባ፡ እያንዳንዱ የባንክ ክፍያ ሲፈጸም የባንኩ ስም፣ ቀን እና የግብይት ቁጥር (Transaction Reference) በሲስተሙ ይመዘገባል።"
        },
        slide4: {
            badge: "ክፍል 4፡ ሽያጭ እና ወጪ",
            title: "ሽያጭ እና የዕቃ መግዣ ወጪ (COGS) ማስታረቅ",
            subtitle: "ወርሃዊ ሂሳብ በሚሞላበት ገጽ ላይ ቁጥሮችን ሲያስገቡ ስሌቶቹ በቅጽበት ይሰራሉ፣ ይህም ስህተት ከተፈጠረ ወዲያውኑ ለማየት ይረዳል።",
            col1: "1. የሽያጭ ደረሰኞች ምዝገባ",
            cSales: "የማሽን ሽያጭ",
            mSales: "የእጅ ደረሰኝ ሽያጭ",
            cRange: "የማሽን ደረሰኞች፡ ከ10001 ➔ 10320 (319 ቁርጥ)",
            mRange: "የእጅ ደረሰኞች፡ ከ4001 ➔ 4050 (49 ቁርጥ)",
            col2: "2. የዕቃ መግዣ ወጪ (COGS)",
            begStock: "የመጀመሪያ ክምችት",
            purchases: "አዲስ ግዢ",
            endStock: "የመጨረሻ ክምችት",
            formulaLabel: "የዕቃ መግዣ ወጪ (COGS):",
            formulaDesc: "🧮 ቀላል ስሌት፡ የመጀመሪያ ክምችት + አዲስ ግዢ - የመጨረሻ ክምችት = የዕቃ መግዣ ወጪ (COGS)። በራሱ ይሰላል!"
        },
        slide5: {
            badge: "ክፍል 5፡ ወጪዎች እና ተጨማሪ እሴት ታክስ (VAT)",
            title: "የስራ ማስኬጃ ወጪዎች እና የVAT ምዝገባ",
            subtitle: "ወርሃዊ ሂሳቡ 17 መደበኛ የስራ ማስኬጃ ወጪዎችን እና የVAT መረጃዎችን በጥንቃቄ እንዲመዘገቡ ያደርጋል::",
            p1: "🏢 የሚመዘገቡ ወጪዎች፡ የቤት ኪራይ፣ ደመወዝ፣ የትራንስፖርት፣ የኤሌክትሪክ፣ የባንክ አገልግሎት ክፍያ እና የንብረት እርጅና ወጪዎች።",
            p2: "📈 የVAT ምዝገባ፡ የሽያጭ VAT (15%)፣ የግዢ VAT (15%) እና ዊዝሆልዲንግ ታክስ ከመንግስት ታክስ ሪፖርት ጋር እንዲጣጣም ይደረጋል።"
        },
        slide6: {
            badge: "ክፍል 6፡ የግብር ስሌት",
            title: "የኢትዮጵያ የንግድ ትርፍ ግብር ስሌት",
            subtitle: "ለደረጃ ሀ/ለ ንግድ ድርጅቶች፣ የትርፍ ግብር የሚሰላው ከተጣራ ትርፍ ላይ 35% ተወስዶ የመቀነሻ ቀመርን በመጠቀም ነው። ይህንን ሲስተሙ በራሱ ያሰላል!",
            formulaLabel: "ይፋዊ የትርፍ ግብር ቀመር፡",
            formulaCode: "ትርፍ ግብር = (የተጣራ ትርፍ * 0.35) - 24,600 ብር",
            widgetTitle: "እራስዎ ይሞክሩት፡ የግብር ማስያ መተግበሪያ",
            widgetInstruction: "የታክስ ስሌቱ እንዴት እንደሚቀያየር ከታች ያሉትን ቁጥሮች በመቀየር ይሞክሩ፡",
            t1: "ጠቅላላ የሽያጭ ገቢ፡",
            t2: "የዕቃ መግዣ ወጪ (COGS)፡",
            t3: "ጠቅላላ ወጪዎች፡",
            nibtLabel: "የተጣራ ትርፍ (Net Profit)፡",
            taxLabel: "የንግድ ትርፍ ግብር (35%)፡",
            widgetTip: "💡 የግብር ስሌቱ ሲቀያየር ለመመልከት ከላይ ያሉትን ቁጥሮች ይቀይሩ!"
        },
        slide7: {
            badge: "ክፍል 7፡ ባንክ ማስታረቅ",
            title: "የተለያዩ የባንክ ሂሳቦችን ማስታረቅ",
            subtitle: "አብዛኛዎቹ ደንበኞች ከአንድ በላይ የባንክ አካውንት ይጠቀማሉ። ወርሃዊ ሂሳቡን ስናስታርቅ የእያንዳንዱን ባንክ እንቅስቃሴ እንመዘግባለን።",
            point1: "LC ማርጅን መለቀቅ፡ በገቢ ንግድ (Import) ወቅት የተለቀቁ የLC ማርጅን ሂሳቦችን ማስታረቅ።",
            point2: "እንቅስቃሴዎች፡ ብድሮች፣ የባንክ ዝውውሮች እና ማስተካከያዎችን ለእያንዳንዱ የባንክ አካውንት መመዝገብ።"
        },
        slide8: {
            badge: "ክፍል 8፡ ደህንነት እና ፒዲኤፍ ሪፖርት",
            title: "የታተሙ ፒዲኤፍ ሪፖርቶች እና የQR ደህንነት",
            subtitle: "አንድ ወርሃዊ ሂሳብ በማናጀር ከጸደቀ በኋላ፣ ሲስተሙ በQR ኮድ የተጠበቀ ይፋዊ የፒዲኤፍ ሪፖርት ያመነጫል።",
            p1: "🔒 መቆለፊያ፡ ስህተቶችን ለመከላከል የጸደቁ ሂሳቦች በራሳቸው ይቆለፋሉ። ደንበኞች በፖርታላቸው ማየት የሚችሉት የጸደቁትን መዛግብቶች ብቻ ነው።",
            p2: "📲 QR ኮድ መፈተሻ፡ ባንኮች ወይም የታክስ ኦዲተሮች በሪፖርቱ ላይ ያለውን QR ኮድ በመቃኘት ትክክለኛነቱን በጂጂኤኤ ድረ-ገጽ ላይ ማረጋገጥ ይችላሉ።"
        },
        slide9: {
            badge: "ክፍል 9፡ ማጠቃለያ",
            title: "የፋይናንስ ኦዲት እለታዊ የስራ ዝርዝር",
            subtitle: "ወርሃዊ ሂሳቦች 100% ትክክል እና ደህንነታቸው የተጠበቀ እንዲሆን በየወሩ ይህንን ቀላል የስራ ዝርዝር ይከተሉ፡"
        }
    }
};
</script>

<template>
    <Head :title="`${slides[locale].slide1.title} - GGAA Systems`" />

    <div class="min-h-screen font-sans flex flex-col justify-between overflow-x-hidden transition-colors duration-300 selection:bg-emerald-600 selection:text-white"
         :class="isDark ? 'bg-[#050b0a] text-slate-100' : 'bg-slate-50 text-slate-800'"
    >
        
        <!-- Top Navigation Bar -->
        <header class="w-full py-4 px-6 lg:px-12 flex justify-between items-center border-b backdrop-blur-md sticky top-0 z-50 transition-colors"
                :class="isDark ? 'border-slate-800 bg-[#050b0a]/80' : 'border-slate-200 bg-white/80'"
        >
            <Link href="/training" class="flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-emerald-500 transition-colors group">
                <ArrowLeftIcon class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                {{ locale === 'en' ? 'Back to Tracks' : 'ወደ ስልጠናዎች ተመለስ' }}
            </Link>
            
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-650 flex items-center justify-center font-black font-outfit shadow-lg shadow-emerald-500/20 text-white">
                    F
                </div>
                <div>
                    <span class="text-base font-black tracking-wider uppercase font-outfit" :class="isDark ? 'text-white' : 'text-slate-900'">GGAA <span class="text-emerald-500">Systems</span></span>
                    <span class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest leading-none">{{ locale === 'en' ? 'Finance & Bookkeeping Course' : 'የፋይናንስና የሂሳብ አያያዝ ስልጠና' }}</span>
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

        <!-- Slides Container (Smoother, larger fonts, conversational) -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 md:px-8 py-8 flex items-center justify-center relative">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] blur-[150px] rounded-full pointer-events-none transition-opacity duration-300"
                 :class="[isDark ? 'bg-emerald-500/10 opacity-100' : 'bg-emerald-500/5 opacity-40']"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[35%] h-[35%] blur-[150px] rounded-full pointer-events-none transition-opacity duration-300"
                 :class="[isDark ? 'bg-teal-500/10 opacity-100' : 'bg-teal-500/5 opacity-40']"></div>

            <!-- SLIDE 1: WELCOME & TITLE -->
            <div v-if="currentSlide === 1" class="w-full flex flex-col items-center justify-center text-center py-12 relative z-10 space-y-6 max-w-3xl animate-fade-in">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-black uppercase tracking-widest border border-emerald-500/20">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                    {{ slides[locale].slide1.badge }}
                </span>
                
                <h1 class="text-5xl lg:text-7xl font-black font-outfit tracking-tighter leading-tight"
                    :class="isDark ? 'text-white' : 'text-slate-955'"
                >
                    {{ slides[locale].slide1.title }}
                </h1>
                
                <p class="text-xl md:text-2xl font-medium max-w-2xl mx-auto leading-relaxed"
                   :class="isDark ? 'text-slate-300' : 'text-slate-650'"
                >
                    {{ slides[locale].slide1.subtitle }}
                </p>

                <div class="pt-8 flex justify-center gap-4">
                    <button @click="nextSlide" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-xl shadow-emerald-600/30 hover:scale-105 active:scale-95 transition-all flex items-center gap-2 text-sm uppercase tracking-wider font-outfit">
                        {{ locale === 'en' ? 'Get Started' : 'ጀምር' }}
                        <ChevronRightIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- SLIDE 2: FINANCIAL SCOPE OVERVIEW -->
            <div v-if="currentSlide === 2" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10 animate-fade-in">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">{{ slides[locale].slide2.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide2.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-600'">
                        {{ slides[locale].slide2.subtitle }}
                    </p>
                    
                    <div class="space-y-4 pt-2">
                        <div class="flex gap-4 p-4 rounded-3xl border transition-colors"
                             :class="isDark ? 'bg-slate-900/50 border-slate-800' : 'bg-white border-slate-200 shadow-sm'"
                        >
                            <div class="h-10 w-10 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 font-bold shrink-0">1</div>
                            <div>
                                <h4 class="font-bold text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide2.box1 }}</h4>
                                <p class="text-sm mt-1" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ slides[locale].slide2.box1Desc }}</p>
                            </div>
                        </div>
                        
                        <div class="flex gap-4 p-4 rounded-3xl border transition-colors"
                             :class="isDark ? 'bg-slate-900/50 border-slate-800' : 'bg-white border-slate-200 shadow-sm'"
                        >
                            <div class="h-10 w-10 rounded-2xl bg-teal-500/10 flex items-center justify-center text-teal-400 font-bold shrink-0">2</div>
                            <div>
                                <h4 class="font-bold text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide2.box2 }}</h4>
                                <p class="text-sm mt-1" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ slides[locale].slide2.box2Desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="shadow-2xl rounded-[32px] p-8 w-full max-w-md border space-y-4 font-medium"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800 text-slate-300' : 'bg-white border-slate-250 text-slate-700 shadow-lg'"
                    >
                        <h3 class="text-lg font-black uppercase tracking-wider pb-2 border-b"
                            :class="isDark ? 'text-slate-400 border-slate-800' : 'text-slate-900 border-slate-200'"
                        >
                            {{ slides[locale].slide2.targetTitle }}
                        </h3>
                        <ul class="space-y-3.5 text-sm">
                            <li class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span>{{ slides[locale].slide2.target1 }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span>{{ slides[locale].slide2.target2 }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span>{{ slides[locale].slide2.target3 }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- SLIDE 3: BILLING & INVOICING -->
            <div v-if="currentSlide === 3" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">{{ slides[locale].slide3.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide3.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide3.subtitle }}
                    </p>

                    <div class="space-y-4 text-sm" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                        <p class="font-medium">💼 {{ slides[locale].slide3.point1 }}</p>
                        <p class="font-medium">🏦 {{ slides[locale].slide3.point2 }}</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm rounded-[32px] p-6 border space-y-4 shadow-xl"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800 text-slate-300' : 'bg-white border-slate-250'"
                    >
                        <div class="flex justify-between items-center pb-2 border-b" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                            <span class="text-[9px] bg-emerald-500/10 text-emerald-400 font-bold px-2 py-0.5 rounded uppercase">{{ locale === 'en' ? 'Service Invoice' : 'የክፍያ መጠየቂያ' }}</span>
                            <span class="text-xs" :class="isDark ? 'text-slate-450' : 'text-slate-500'">#INV-2026-081</span>
                        </div>
                        
                        <div class="space-y-2.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500 uppercase">{{ locale === 'en' ? 'Client' : 'ደንበኛ' }}:</span>
                                <span class="font-bold text-sm" :class="isDark ? 'text-white' : 'text-slate-900'">Nile Textile Corp</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 uppercase">{{ locale === 'en' ? 'Service' : 'አገልግሎት' }}:</span>
                                <span class="font-bold text-sm" :class="isDark ? 'text-white' : 'text-slate-900'">Annual Auditing</span>
                            </div>
                            <div class="flex justify-between border-t pt-2 font-bold" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <span class="text-slate-500">{{ locale === 'en' ? 'Grand Total' : 'ጠቅላላ ሂሳብ' }}:</span>
                                <span class="text-emerald-500 text-base">45,000.00 ETB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 4: RECONCILIATIONS: SALES & COGS -->
            <div v-if="currentSlide === 4" class="w-full flex flex-col py-2 relative z-10">
                <div class="text-center max-w-2xl mx-auto mb-6 space-y-1">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">{{ slides[locale].slide4.badge }}</span>
                    <h2 class="text-3xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide4.title }}
                    </h2>
                    <p class="text-base font-medium" :class="isDark ? 'text-slate-450' : 'text-slate-650'">
                        {{ slides[locale].slide4.subtitle }}
                    </p>
                </div>

                <div class="w-full rounded-3xl border overflow-hidden shadow-2xl p-6 space-y-4"
                     :class="isDark ? 'bg-[#081013] border-slate-800' : 'bg-white border-slate-200'"
                >
                    <div class="flex flex-wrap justify-between items-center gap-3 pb-3 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                        <span class="text-xs font-bold" :class="isDark ? 'text-slate-200' : 'text-slate-800'">Nile Textile Corp (Meskeram 2026)</span>
                        <span class="px-2.5 py-1 bg-amber-500/10 text-amber-500 border border-amber-500/20 rounded-lg text-xs font-black">DRAFT</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Box 1 -->
                        <div class="p-4 rounded-2xl border space-y-3" :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-slate-50 border-slate-250 shadow-sm'">
                            <h4 class="text-sm font-black text-emerald-500 uppercase border-b pb-1" :class="isDark ? 'border-slate-800' : 'border-slate-200'">{{ slides[locale].slide4.col1 }}</h4>
                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <span class="text-slate-500 block uppercase">{{ slides[locale].slide4.cSales }}</span>
                                    <span class="font-black text-base" :class="isDark ? 'text-white' : 'text-slate-900'">240,500.00 ETB</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block uppercase">{{ slides[locale].slide4.mSales }}</span>
                                    <span class="font-black text-base" :class="isDark ? 'text-white' : 'text-slate-900'">62,000.00 ETB</span>
                                </div>
                            </div>
                            <p class="text-xs pt-1.5" :class="isDark ? 'text-slate-400' : 'text-slate-500'">{{ slides[locale].slide4.cRange }}</p>
                        </div>

                        <!-- Box 2 -->
                        <div class="p-4 rounded-2xl border space-y-3" :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-slate-50 border-slate-250 shadow-sm'">
                            <h4 class="text-sm font-black text-emerald-500 uppercase border-b pb-1" :class="isDark ? 'border-slate-800' : 'border-slate-200'">{{ slides[locale].slide4.col2 }}</h4>
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <span class="text-slate-500 block uppercase">{{ slides[locale].slide4.begStock }}</span>
                                    <span class="font-black" :class="isDark ? 'text-white' : 'text-slate-800'">120,000.00</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block uppercase">{{ slides[locale].slide4.purchases }}</span>
                                    <span class="font-black" :class="isDark ? 'text-white' : 'text-slate-800'">90,000.00</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block uppercase">{{ slides[locale].slide4.endStock }}</span>
                                    <span class="font-black" :class="isDark ? 'text-white' : 'text-slate-800'">85,000.00</span>
                                </div>
                            </div>
                            <div class="flex justify-between text-xs pt-2 border-t" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <span class="text-slate-500 font-bold uppercase">{{ slides[locale].slide4.formulaLabel }}</span>
                                <span class="font-black text-emerald-500">125,000.00 ETB</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-500 font-bold">
                        {{ slides[locale].slide4.formulaDesc }}
                    </div>
                </div>
            </div>

            <!-- SLIDE 5: EXPENSES & VAT -->
            <div v-if="currentSlide === 5" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">{{ slides[locale].slide5.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide5.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide5.subtitle }}
                    </p>

                    <div class="space-y-4">
                        <div class="flex gap-4 p-4 rounded-2xl" :class="isDark ? 'bg-slate-900/40 border border-slate-800' : 'bg-white border shadow-sm'">
                            <span class="text-2xl shrink-0">🏢</span>
                            <div>
                                <h5 class="font-bold text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide5.p1 }}</h5>
                            </div>
                        </div>
                        
                        <div class="flex gap-4 p-4 rounded-2xl" :class="isDark ? 'bg-slate-900/40 border border-slate-800' : 'bg-white border shadow-sm'">
                            <span class="text-2xl shrink-0">📈</span>
                            <div>
                                <h5 class="font-bold text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide5.p2 }}</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm rounded-[32px] p-6 border space-y-4 shadow-xl"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800 text-slate-300' : 'bg-white border-slate-250'"
                    >
                        <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ locale === 'en' ? 'VAT Auditing Sample' : 'የVAT ማጠቃለያ ማሳያ' }}</h4>
                        
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span>Sales VAT (15%):</span>
                                <span class="font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">36,075.00 ETB</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Purchase VAT (15%):</span>
                                <span class="font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">13,500.00 ETB</span>
                            </div>
                            <div class="flex justify-between border-t pt-2" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <span class="font-bold uppercase text-emerald-500">Net VAT Obligation:</span>
                                <span class="font-black text-emerald-500 text-sm">22,575.00 ETB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 6: ETHIOPIAN TAX COMPUTATION (Bilingual live tax calculator!) -->
            <div v-if="currentSlide === 6" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10 animate-fade-in">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">{{ slides[locale].slide6.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide6.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide6.subtitle }}
                    </p>

                    <div class="p-5 rounded-2xl border text-sm space-y-2 transition-all"
                         :class="isDark ? 'bg-emerald-950/20 border-emerald-950/40 text-emerald-400' : 'bg-emerald-50 border-emerald-100 text-emerald-850'"
                    >
                        <h4 class="font-black uppercase text-xs tracking-wider">{{ slides[locale].slide6.formulaLabel }}</h4>
                        <p class="font-mono text-sm">{{ slides[locale].slide6.formulaCode }}</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <!-- Interactive Tax Calculator Board -->
                    <div class="w-full max-w-sm rounded-[36px] p-6 border space-y-5 shadow-2xl transition-all"
                         :class="isDark ? 'bg-[#0a1412] border-slate-800' : 'bg-white border-slate-250'"
                    >
                        <h4 class="text-sm font-black uppercase text-center" :class="isDark ? 'text-slate-300' : 'text-slate-950'">
                            {{ slides[locale].slide6.widgetTitle }}
                        </h4>
                        <p class="text-[11px] text-slate-500 text-center">{{ slides[locale].slide6.widgetInstruction }}</p>
                        
                        <div class="space-y-3.5 text-xs font-medium">
                            <div class="flex items-center justify-between">
                                <span :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ slides[locale].slide6.t1 }}</span>
                                <input type="number" v-model.number="mockSales" class="w-28 bg-slate-500/10 border px-3 py-1.5 rounded-xl text-right font-black outline-none transition-all"
                                       :class="isDark ? 'border-slate-800 text-white focus:border-emerald-500' : 'border-slate-250 text-slate-900 focus:border-emerald-500'"
                                >
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ slides[locale].slide6.t2 }}</span>
                                <input type="number" v-model.number="mockCogs" class="w-28 bg-slate-500/10 border px-3 py-1.5 rounded-xl text-right font-black outline-none transition-all"
                                       :class="isDark ? 'border-slate-800 text-white focus:border-emerald-500' : 'border-slate-250 text-slate-900 focus:border-emerald-500'"
                                >
                            </div>

                            <div class="flex items-center justify-between">
                                <span :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ slides[locale].slide6.t3 }}</span>
                                <input type="number" v-model.number="mockExpenses" class="w-28 bg-slate-500/10 border px-3 py-1.5 rounded-xl text-right font-black outline-none transition-all"
                                       :class="isDark ? 'border-slate-800 text-white focus:border-emerald-500' : 'border-slate-250 text-slate-900 focus:border-emerald-500'"
                                >
                            </div>

                            <div class="flex justify-between border-t pt-3" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <span :class="isDark ? 'text-slate-300' : 'text-slate-700'">{{ slides[locale].slide6.nibtLabel }}</span>
                                <span class="font-black text-sm text-emerald-500">{{ fmt(computedNetProfit) }} ETB</span>
                            </div>

                            <div class="flex justify-between border-t border-dashed pt-3" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <span class="font-bold" :class="isDark ? 'text-slate-200' : 'text-slate-800'">{{ slides[locale].slide6.taxLabel }}</span>
                                <span class="font-black text-base text-yellow-500">{{ fmt(computedProfitTax) }} ETB</span>
                            </div>
                        </div>

                        <p class="text-xs text-center text-slate-500 font-bold">{{ slides[locale].slide6.widgetTip }}</p>
                    </div>
                </div>
            </div>

            <!-- SLIDE 7: MULTI-BANK BALANCES LEDGER -->
            <div v-if="currentSlide === 7" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">{{ slides[locale].slide7.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide7.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide7.subtitle }}
                    </p>

                    <div class="grid grid-cols-2 gap-4 text-xs font-semibold">
                        <div class="p-3 border rounded-2xl" :class="isDark ? 'bg-slate-900 border-slate-850' : 'bg-white border-slate-200 shadow-sm'">
                            <p class="text-slate-500 uppercase block text-[9px] tracking-wider mb-1">LC Margin</p>
                            <p :class="isDark ? 'text-slate-300' : 'text-slate-700'">{{ slides[locale].slide7.point1 }}</p>
                        </div>
                        <div class="p-3 border rounded-2xl" :class="isDark ? 'bg-slate-900 border-slate-850' : 'bg-white border-slate-200 shadow-sm'">
                            <p class="text-slate-500 uppercase block text-[9px] tracking-wider mb-1">Loan & Transfers</p>
                            <p :class="isDark ? 'text-slate-300' : 'text-slate-700'">{{ slides[locale].slide7.point2 }}</p>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 space-y-4">
                    <div class="rounded-3xl p-6 border space-y-4 shadow-xl"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200'"
                    >
                        <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ locale === 'en' ? 'Linked Accounts' : 'የተያያዙ የባንክ አካውንቶች' }}</h4>
                        
                        <div class="p-3 border rounded-2xl" :class="isDark ? 'bg-slate-900 border-slate-850 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-700'">
                            <div class="flex justify-between font-bold text-xs">
                                <span :class="isDark ? 'text-slate-200' : 'text-slate-900'">Commercial Bank (CBE)</span>
                                <span :class="isDark ? 'text-white' : 'text-slate-950'">410,500.00 ETB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 8: REPORT VERIFICATIONS & PDF VAULT EXPORT -->
            <div v-if="currentSlide === 8" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">{{ slides[locale].slide8.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide8.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide8.subtitle }}
                    </p>

                    <div class="space-y-4 text-sm" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                        <p class="font-medium">{{ slides[locale].slide8.p1 }}</p>
                        <p class="font-medium">{{ slides[locale].slide8.p2 }}</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm bg-white text-slate-900 rounded-[32px] p-6 shadow-2xl space-y-4 border-t-8 border-t-emerald-600">
                        <div class="flex justify-between items-start border-b pb-3">
                            <div>
                                <span class="text-[8px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-black">OFFICIAL AUDIT SHEET</span>
                                <h4 class="text-xs font-black font-outfit uppercase mt-1">Gedion Girma Accountant</h4>
                            </div>
                            <div class="w-12 h-12 bg-slate-200 border flex items-center justify-center text-[9px] font-bold">
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
                <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">{{ slides[locale].slide9.badge }}</span>
                <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-955'">
                    {{ slides[locale].slide9.title }}
                </h2>
                <p class="text-lg font-medium" :class="isDark ? 'text-slate-400' : 'text-slate-650'">
                    {{ slides[locale].slide9.subtitle }}
                </p>
                
                <div class="p-6 rounded-[32px] border text-left w-full max-w-lg mx-auto space-y-3 text-sm"
                     :class="isDark ? 'bg-slate-900/60 border-slate-800 text-slate-350' : 'bg-white border-slate-250 text-slate-700 shadow-md'"
                >
                    <div v-for="item in checklistItems" :key="item.id" 
                         @click="toggleCheck(item)"
                         class="flex items-start gap-3 cursor-pointer select-none py-2 rounded-xl hover:bg-slate-500/10 px-3 transition-colors"
                    >
                        <input type="checkbox" :checked="item.checked" class="accent-emerald-650 h-5 w-5 shrink-0 rounded mt-0.5 cursor-pointer">
                        <span :class="{'line-through text-slate-500': item.checked}">
                            {{ locale === 'en' ? item.text_en : item.text_am }}
                        </span>
                    </div>
                </div>

                <div class="pt-6">
                    <button @click="goToSlide(1)" class="px-6 py-3 bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 font-bold rounded-2xl transition-all inline-flex items-center gap-2 text-xs uppercase tracking-widest">
                        🔄 {{ locale === 'en' ? 'Restart Guide' : 'ድጋሚ ጀምር' }}
                    </button>
                </div>
            </div>
        </main>

        <!-- Slides Navigation Controls -->
        <footer class="w-full py-6 px-6 lg:px-12 flex justify-between items-center border-t sticky bottom-0 z-50 transition-colors"
                :class="isDark ? 'border-slate-800 bg-[#070b13]' : 'border-slate-200 bg-slate-100'"
        >
            <span class="text-xs font-bold" :class="isDark ? 'text-slate-500' : 'text-slate-660'">
                {{ locale === 'en' ? 'GGAA Finance Course' : 'ጂጂኤኤ የፋይናንስ ስልጠና' }}
            </span>
            
            <div class="flex items-center gap-6">
                <button @click="prevSlide" class="p-2.5 rounded-xl border hover:scale-105 active:scale-95 transition-all text-slate-400 hover:text-emerald-500"
                        :class="isDark ? 'border-slate-800 hover:bg-slate-900' : 'border-slate-200 hover:bg-white'"
                >
                    <ChevronLeftIcon class="h-4 w-4" />
                </button>
                
                <span class="text-sm font-bold tracking-wider font-outfit" :class="isDark ? 'text-slate-400' : 'text-slate-700'">
                    {{ locale === 'en' ? 'Slide' : 'ስላይድ' }} <span class="text-emerald-500">{{ currentSlide }}</span> of <span>{{ totalSlides }}</span>
                </span>
                
                <button @click="nextSlide" class="p-2.5 rounded-xl border hover:scale-105 active:scale-95 transition-all text-slate-400 hover:text-emerald-500"
                        :class="isDark ? 'border-slate-800 hover:bg-slate-900' : 'border-slate-200 hover:bg-white'"
                >
                    <ChevronRightIcon class="h-4 w-4" />
                </button>
            </div>
            
            <div class="text-[11px] uppercase font-black hidden md:block" :class="isDark ? 'text-slate-660' : 'text-slate-400'">
                GGAA Systems
            </div>
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
</style>
