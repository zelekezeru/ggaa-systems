<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    UserIcon, AcademicCapIcon,
    ChevronLeftIcon, ChevronRightIcon,
    ArrowLeftIcon, SunIcon, MoonIcon,
    CheckCircleIcon, ExclamationTriangleIcon,
    MapPinIcon, ClockIcon, DocumentTextIcon,
    SparklesIcon, ForwardIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const isDark = ref(true);
const currentStep = ref(0); // 0: Overview, 1: Tour Workspace, 2: Lab 1 (Prioritize), 3: Tour Ledger Prefill, 4: Lab 2 (Errand), 5: Lab 3 (TIN Verify), 6: Graduation
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
// LAB 1 STATE: PRIORITIZE & DELEGATE (OPTIONAL)
// ==========================================
const lab1TaskState = ref('pending'); // 'pending' or 'review'
const lab1Delegated = ref(false);
const lab1Skipped = ref(false);

const lab1Stress = computed(() => {
    let base = 35; 
    if (lab1TaskState.value === 'pending') base += 30; 
    if (!lab1Delegated.value) base += 15; 
    return base;
});

const lab1Passed = computed(() => {
    return lab1Skipped.value || (lab1TaskState.value === 'review' && lab1Stress.value < 50);
});

function skipLab1() {
    lab1Skipped.value = true;
    lab1TaskState.value = 'review';
    lab1Delegated.value = true;
}

// ==========================================
// LAB 2 STATE: ERRAND TRIP TRACKING (OPTIONAL)
// ==========================================
const lab2State = ref('ready'); // 'ready', 'traveling', 'arrived', 'completed'
const travelProgress = ref(0);
const lab2Skipped = ref(false);
let travelInterval = null;

const lab2Passed = computed(() => {
    return lab2Skipped.value || lab2State.value === 'completed';
});

function startTravel() {
    if (lab2State.value !== 'ready') return;
    lab2State.value = 'traveling';
    travelProgress.value = 0;
    
    travelInterval = setInterval(() => {
        if (travelProgress.value < 100) {
            travelProgress.value += 20;
        } else {
            clearInterval(travelInterval);
            lab2State.value = 'arrived';
        }
    }, 200);
}

function completeErrand() {
    if (lab2State.value !== 'arrived') return;
    lab2State.value = 'completed';
}

function skipLab2() {
    lab2Skipped.value = true;
    lab2State.value = 'completed';
}

// ==========================================
// LAB 3 STATE: TIN CERTIFICATE VALIDATION (OPTIONAL)
// ==========================================
const inputTin = ref('');
const inputLegal = ref('');
const lab3Error = ref('');
const lab3Passed = ref(false);
const lab3Skipped = ref(false);

function validateLab3() {
    lab3Error.value = '';
    if (inputTin.value.trim() === '0928301840' && inputLegal.value === 'PLC') {
        lab3Passed.value = true;
    } else {
        if (locale.value === 'en') {
            lab3Error.value = '❌ Invalid credentials. Please inspect the certificate carefully!';
        } else {
            lab3Error.value = '❌ የተሳሳተ መረጃ ነው። እባክዎ ምስክር ወረቀቱን በጥንቃቄ ይመልከቱ!';
        }
    }
}

function skipLab3() {
    lab3Skipped.value = true;
    lab3Passed.value = true;
    inputTin.value = '0928301840';
    inputLegal.value = 'PLC';
}

// ==========================================
// GRADUATION & STATE SAVING
// ==========================================
watch(currentStep, (newStep) => {
    if (newStep === 6) {
        localStorage.setItem('training-completed-employees', 'true');
    }
});

// Localization content
const content = {
    en: {
        backBtn: "Back to Tracks",
        headerTitle: "Employee Academy",
        headerSubtitle: "Entry Introduction & Portal Tour",
        stepNames: ["Introduction", "Workspace Tour", "Lab 1: Prioritize", "Ledger Prefill Tour", "Lab 2: Errand Log", "Lab 3: TIN Verify", "Graduation 🎓"],
        skipPracticeBtn: "Skip Practice & Continue ➔",
        
        overview: {
            title: "Welcome to GGAA Employee Portal Tour!",
            desc1: "This slide presentation serves as an entry introduction for anyone using the GGAA system for the first time. We will tour the entire employee workspace and explain the exact attributes and operational flows you will handle.",
            desc2: "Along the tour, you will find optional hands-on sandboxes to practice the tools. You can complete the exercises or click 'Skip Practice' to continue reading the presentation.",
            startBtn: "Start Presentation Tour",
        },
        tourWorkspace: {
            title: "Chapter 1: Employee Workspace Tour",
            desc1: "Your employee dashboard collects your active client portfolios, daily tasks, and outdoor errands in a single place. Key components of this screen include:",
            pt1Title: "📋 1. Personal Task Board",
            pt1Desc: "A Kanban board tracking your current bookkeeping draft files. Tasks are divided into Pending Docs, To Do, Under Review, and Done.",
            pt2Title: "⚡ 2. Workload Stress Meter",
            pt2Desc: "GGAA measures your total workload in points rather than client count. This prevents burnout and ensures high-quality calculations.",
            pt3Title: "🚗 3. Outdoor Errand Log",
            pt3Desc: "A mobile-responsive tool allowing you to record travel times, coordinates, and delivery verification when visiting government tax offices.",
            nextBtn: "Go to Lab 1 (Prioritization)",
        },
        lab1: {
            guideTitle: "Lab 1 (Optional): Prioritize & Delegate",
            intro: "Urgent government compliance deadlines are flagged in red. In this sandbox, you must submit the urgent Zenith PLC task for manager review and delegate the low-priority Haddis Shop task to bring your stress points into the green/safe zone (under 50 points).",
            instructionTitle: "Objective (Optional):",
            inst1: "1. Click 'Move to Review' on the red-flagged Zenith PLC card.",
            inst2: "2. Click 'Delegate' on the Haddis Shop card to reduce stress points.",
            inst3: "3. Or click the 'Skip Practice' button below to bypass this exercise.",
            panelTitle: "Workload Dashboard Simulator",
            stressLabel: "Workload Stress Meter:",
            optimal: "Too Chill (Need tasks)",
            happy: "Happy & Safe (Green Zone)",
            busy: "Busy & Focused",
            stressed: "Overloaded! Action required!",
            boardTitle: "Task Board",
            pendingCol: "Pending Docs",
            reviewCol: "Under Review",
            moveBtn: "Move to Review",
            delegateBtn: "Delegate",
            delegatedBadge: "Delegated Out",
            redBadge: "🚨 High Risk - Due in 2h",
            yellowBadge: "Low Priority",
            successMsg: "🎉 Success! You prioritized the red task and delegated the minor work. Stress load is now at a healthy 50 Pts. You can proceed!",
        },
        tourLedger: {
            title: "Chapter 2: Ledger Entries & Prefills",
            desc1: "The Monthly Ledger is the heart of GGAA bookkeeping. To simplify data entry and eliminate typos, the system computes smart pre-fill predictions when creating a new month:",
            pt1Title: "📦 1. Carry-Forward Inventory",
            pt1Desc: "The system automatically carry-forwards the previous month's ending inventory as the new month's beginning inventory.",
            pt2Title: "🔢 2. Continuous Document Numbers",
            pt2Desc: "Cash register machine and manual receipt numbers automatically start at the previous month's ending number + 1.",
            pt3Title: "🧾 3. Purchases & VAT Integration",
            pt3Desc: "Purchases and purchase VAT figures are automatically calculated and pre-filled from uploaded raw materials receipts.",
            nextBtn: "Go to Lab 2 (Errand Log)",
        },
        lab2: {
            guideTitle: "Lab 2 (Optional): Errand Log Simulation",
            intro: "When visiting government offices (like the Ministry of Revenues) or delivering client folders, you must log your travel to track miles and schedule delivery verifications.",
            instructionTitle: "Objective (Optional):",
            inst1: "1. Click 'Start Errand Trip' to begin traveling and watch the progress.",
            inst2: "2. Once arrived, click 'Confirm Delivery' to complete the log.",
            inst3: "3. Or click 'Skip Practice' to continue the tour.",
            mobileTitle: "GGAA Mobile Errand Log",
            errandTitle: "Submit Audit Folder",
            errandLoc: "Ministry of Revenues - Bole Branch",
            stateReady: "Ready to start",
            stateTraveling: "GPS Route Active - Traveling...",
            stateArrived: "Arrived at destination",
            stateDone: "Errand Log Succeeded!",
            startBtn: "Start Errand Trip",
            deliverBtn: "Confirm Delivery",
            successMsg: "🎉 Success! Errand marked as complete and logged. Manager has been notified.",
        },
        lab3: {
            guideTitle: "Lab 3 (Optional): TIN Verification",
            intro: "Correct Tax Identification Numbers (TIN) are critical for e-filing. Below is a mock Ministry of Trade registration certificate. You must enter the TIN and legal structure to verify.",
            instructionTitle: "Objective (Optional):",
            inst1: "1. Find the 10-digit TIN and Legal Structure on the certificate.",
            inst2: "2. Type the TIN and select the correct Legal Structure in the inputs.",
            inst3: "3. Or click 'Skip Practice' to proceed to graduation.",
            tinLabel: "Enter 10-Digit TIN:",
            legalLabel: "Select Legal Structure:",
            validateBtn: "Validate & Save",
            successMsg: "🎉 Success! TIN matched and legal status validated. Client files updated.",
        },
        graduation: {
            title: "Congratulations, Graduate!",
            subtitle: "You have completed the entire GGAA Employee Portal Tour.",
            badgeLabel: "Unlocked Profile Badge:",
            badgeTitle: "⚡ Speed Demon",
            badgeDesc: "Successfully completed employee operations and pre-fill introduction course.",
            certTitle: "OPERATIONS SPECIALIST CERTIFICATE",
            certSubtitle: "GGAA Systems Compliance Academy",
            certBody: "This certifies that the holder has completed the introductory presentation tour for employee operations, demonstrating knowledge of task boards, errand logs, and ledger pre-fill attributes.",
            certDate: "Certified on: June 2026",
            certNameLabel: "Change Certificate Name:",
            homeBtn: "Return to Training Hub",
        }
    },
    am: {
        backBtn: "ወደ ስልጠናዎች ተመለስ",
        headerTitle: "የሰራተኞች የስልጠና ክፍል",
        headerSubtitle: "የእለታዊ ስራዎች ማስመሰያ ገጽ",
        stepNames: ["መግቢያ", "የስራ ገጽ ጉብኝት", "ላብ 1: ቅድሚያ መስጠት", "የሂሳብ መሙያ ቱር", "ላብ 2: የውጭ ስራ", "ላብ 3: TIN ማረጋገጥ", "ምረቃ 🎓"],
        skipPracticeBtn: "ልምምዱን እለፍና ቀጥል ➔",
        
        overview: {
            title: "እንኳን ወደ ሰራተኞች የስራ ገጽ ጉብኝት በደህና መጡ!",
            desc1: "ይህ የስላይድ ገለጻ የጂጂኤኤ ሲስተምን ለመጀመሪያ ጊዜ ለሚጠቀሙ ባለሙያዎች የተዘጋጀ መግቢያ ነው። የስራ ክፍሎችን እንጎበኛለን፣ እንዲሁም የሲስተሙን አጠቃላይ መለያዎች እና የአሰራር ሂደቶችን እናስረዳለን።",
            desc2: "በጉብኝቱ ወቅት ሲስተሙን የሚለማመዱባቸው ተግባራዊ ሳጥኖች ያገኛሉ። እነሱን መስራት ወይም 'ልምምዱን እለፍ' የሚለውን በመጫን ገለጻውን ማንበብ ይችላሉ።",
            startBtn: "የገለጻ ጉብኝቱን ጀምር",
        },
        tourWorkspace: {
            title: "ምዕራፍ 1፡ የሰራተኞች የስራ ገጽ ጉብኝት",
            desc1: "የሰራተኞች ዳሽቦርድ የእርስዎን ደንበኞች፣ እለታዊ ስራዎች እና የውጭ ጉዞዎችን በአንድ ቦታ ይሰበስባል። የዚህ ገጽ ዋና ዋና ክፍሎች፡",
            pt1Title: "📋 1. የግል ስራ ሰሌዳ (Kanban)",
            pt1Desc: "የእርስዎን ወርሃዊ የሂሳብ መዛግብት ደረጃዎች የሚከታተል ሰሌዳ ነው። ስራዎች በሚጠበቁ፣ በሚሰሩ፣ ለግምገማ በቀረቡ እና በተጠናቀቁ ተከፍለዋል።",
            pt2Title: "⚡ 2. የስራ ጫና መለኪያ",
            pt2Desc: "ጂጂኤኤ የስራ ጫናን የሚለካው በደንበኞች ብዛት ሳይሆን በነጥብ ነው። ይህ ሰራተኞች ከአቅም በላይ እንዳይጫኑ ይረዳል።",
            pt3Title: "🚗 3. የውጭ ስራዎች መመዝገቢያ",
            pt3Desc: "ወደ መንግስት ታክስ መስሪያ ቤቶች በሚሄዱበት ጊዜ የጉዞ ሰዓት እና አቀማመጥ መረጃን በስልክዎ ለመመዝገብ የሚረዳ መሣሪያ ነው።",
            nextBtn: "ወደ ላብ 1 ይለፉ (ቅድሚያ መስጠት)",
        },
        lab1: {
            guideTitle: "ላብ 1 (አማራጭ): ቅድሚያ መስጠት እና ስራ ማደላደል",
            intro: "አስቸኳይ የመንግስት ታክስ ቀነ-ገደቦች በቀይ ምልክት ይደረግባቸዋል። በዚህ ልምምድ ውስጥ አስቸኳዩን Zenith PLC ስራ ለግምገማ መላክ እና የHaddis Shop ስራን ለረዳት በማስተላለፍ የስራ ጫናዎን ጤናማ (ከ50 በታች) ማድረግ አለብዎት።",
            instructionTitle: "መመሪያ (አማራጭ):",
            inst1: "1. በቀይ ካርዱ ላይ 'ለግምገማ ላክ' የሚለውን ይጫኑ።",
            inst2: "2. በ Haddis Shop ካርድ ላይ 'ለረዳት አስተላልፍ' የሚለውን ይጫኑ።",
            inst3: "3. ወይም ይህንን ልምምድ ለመዝለል ከታች 'ልምምዱን እለፍ' የሚለውን ይጫኑ።",
            panelTitle: "የእለት ስራዎች ሰሌዳ ማሳያ",
            stressLabel: "የስራ ጫና እና ጭንቀት መጠን:",
            optimal: "በጣም ቀዝቃዛ (ስራ ይፈልጋል)",
            happy: "ጤናማ እና ደስተኛ (Green Zone)",
            busy: "ስራ በዝቷል ግን ጤናማ",
            stressed: "ከአቅም በላይ ጫና! እርዳታ ይጠይቁ!",
            boardTitle: "የግል ስራ ሰሌዳ",
            pendingCol: "የሚጠበቁ ሰነዶች",
            reviewCol: "ለግምገማ የቀረቡ",
            moveBtn: "ለግምገማ ላክ",
            delegateBtn: "ለረዳት ስጥ",
            delegatedBadge: "ለረዳት የተሰጠ",
            redBadge: "🚨 ከፍተኛ አደጋ - በ2 ሰዓት ውስጥ",
            yellowBadge: "ዝቅተኛ ቅድሚያ",
            successMsg: "🎉 እንኳን ደስ አለዎት! ቀይ ባንዲራ ያለበትን ስራ ቅድሚያ በመስጠት እና ቀላል ስራውን ለረዳት በማስተላለፍ የስራ ጫናዎን 50 ነጥብ (ጤናማ) አድርገዋል። ወደ ቀጣዩ መሄድ ይችላሉ!",
        },
        tourLedger: {
            title: "ምዕራፍ 2፡ ወርሃዊ ሂሳብ እና አውቶማቲክ መሙያ",
            desc1: "ወርሃዊ ሂሳብ (Monthly Ledger) የጂጂኤኤ ሂሳብ አያያዝ ዋና አካል ነው። መረጃዎችን በቀላሉ ለመሙላት ሲስተሙ የሚከተሉትን መረጃዎች በራሱ ይሞላል፡",
            pt1Title: "📦 1. ያለፈው ወር እቃ ክምችት (Carry-Forward)",
            pt1Desc: "ሲስተሙ ባለፈው ወር የተረጋገጠውን የመጨረሻ እቃ ክምችት ለሚቀጥለው ወር መጀመሪያ እቃ ክምችት አድርጎ በራሱ ይወስዳል።",
            pt2Title: "🔢 2. ተከታታይ የደረሰኝ ቁጥሮች",
            pt2Desc: "የማሽን እና የእጅ ደረሰኝ መጀመሪያ ቁጥሮች ባለፈው ወር ካለቀው ቁጥር + 1 ተደርገው በራሳቸው ይሞላሉ።",
            pt3Title: "🧾 3. የግዢ ሰነዶች እና VAT",
            pt3Desc: "በወሩ የተመዘገቡ የግዢ ሰነዶች ድምር እና የVAT ሂሳብ በራስ-ሰር ተደምረው በሂሳብ መዝገብ ገጹ ላይ ይቀመጣሉ።",
            nextBtn: "ወደ ላብ 2 ይለፉ (የውጭ ስራ)",
        },
        lab2: {
            guideTitle: "ላብ 2 (አማራጭ): የውጭ ስራ ምዝገባ",
            intro: "ወደ መንግስት መስሪያ ቤቶች (ገቢዎች) ሲሄዱ ወይም የደንበኛ ወረቀት ለማድረስ ሲወጡ የትራንስፖርት ወጪዎችን ለማስላት ጉዞዎን በስልክዎ መመዝገብ አለብዎት።",
            instructionTitle: "መመሪያ (አማራጭ):",
            inst1: "1. 'ጉዞ ጀምር' የሚለውን ይጫኑ እና መኪናው እስኪደርስ ይጠብቁ።",
            inst2: "2. ሲደርስ 'ማድረስህን አረጋግጥ' የሚለውን ይጫኑ።",
            inst3: "3. ወይም ይህንን ልምምድ ለመዝለል 'ልምምዱን እለፍ' የሚለውን ይጫኑ።",
            mobileTitle: "የጂጂኤኤ እለታዊ የጉዞ መዝገብ",
            errandTitle: "የኦዲት ዶክመንት ማስገባት",
            errandLoc: "ገቢዎች ሚኒስቴር - ቦሌ ቅርንጫፍ",
            stateReady: "ለመጀመር ዝግጁ ነው",
            stateTraveling: "የጉዞ መስመር ላይ - በጉዞ ላይ...",
            stateArrived: "መድረሻ ቦታ ደርሷል",
            stateDone: "የውጭ ስራ ምዝገባ በተሳካ ሁኔታ ተጠናቋል!",
            startBtn: "ጉዞ ጀምር",
            deliverBtn: "ማድረስህን አረጋግጥ",
            successMsg: "🎉 እንኳን ደስ አለዎት! ጉዞው ተመዝግቦ ተጠናቋል። ማናጀሩ መረጃው ደርሶታል።",
        },
        lab3: {
            guideTitle: "ላብ 3 (አማራጭ): TIN እና ፈቃድ ማረጋገጥ",
            intro: "ትክክለኛ የግብር ከፋይ ቁጥር (TIN) መመዝገብ በጣም አስፈላጊ ነው። ከታች ያለውን የንግድ ፈቃድ በማየት TIN ቁጥሩን እና የህግ መዋቅሩን በቅጹ ላይ ያስገቡ።",
            instructionTitle: "መመሪያ (አማራጭ):",
            inst1: "1. በንግድ ፈቃዱ ላይ ባለ 10 አሃዝ TIN እና የህግ መዋቅሩን ይለዩ።",
            inst2: "2. ቁጥሩን አስገብተው የህግ መዋቅሩን PLC ብለው ይምረጡ።",
            inst3: "3. ወይም ለመዝለል ከታች 'ልምምዱን እለፍ' የሚለውን ይጫኑ።",
            tinLabel: "ባለ 10 አሃዝ TIN ያስገቡ:",
            legalLabel: "የህግ መዋቅር ይምረጡ:",
            validateBtn: "አረጋግጥና አስቀምጥ",
            successMsg: "🎉 እንኳን ደስ አለዎት! የግብር ከፋይ መለያ ቁጥር እና የህግ መዋቅሩ በትክክል ተረጋግጧል። የደንበኛው ማህደር ተስተካክሏል።",
        },
        graduation: {
            title: "እንኳን ደስ አለዎት! ተመርቀዋል!",
            subtitle: "የሰራተኞች የስራ ማስፈጸሚያ ጉብኝት ስልጠናን በተሳካ ሁኔታ አጠናቀዋል።",
            badgeLabel: "ያገኙት የክብር ባጅ:",
            badgeTitle: "⚡ የፍጥነት ንጉስ",
            badgeDesc: "የሰራተኞች እለታዊ ስራዎች እና የሂሳብ መሙያ መግቢያ ስልጠናን ያጠናቀቀ።",
            certTitle: "የተግባር ስራዎች ባለሙያ ሰርተፊኬት",
            certSubtitle: "ጂጂኤኤ ሲስተምስ የስልጠና አካዳሚ",
            certBody: "ይህ ሰርተፊኬት ባለቤቱ የሰራተኞችን የስራ ማስፈጸሚያ ገለጻ እና እለታዊ የጉዞ ምዝገባ፣ የሂሳብ መዛግብት እና አውቶማቲክ መሙያ መግቢያ ስልጠናን በተሳካ ሁኔታ ማጠናቀቁን ያረጋግጣል።",
            certDate: "የተሰጠበት ቀን: ሰኔ 2026",
            certNameLabel: "በሰርተፊኬቱ ላይ ያለውን ስም ይቀይሩ:",
            homeBtn: "ወደ ስልጠናዎች ማእከል ተመለስ",
        }
    }
};

</script>

<template>
    <Head :title="`${content[locale].headerTitle} - GGAA Systems`" />

    <div class="min-h-screen font-sans flex flex-col justify-between overflow-x-hidden transition-colors duration-300 selection:bg-indigo-600 selection:text-white"
         :class="isDark ? 'bg-[#080d16] text-slate-100' : 'bg-slate-50 text-slate-800'"
    >
        <!-- Background Blur Particles -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] blur-[150px] rounded-full transition-opacity duration-300"
                 :class="isDark ? 'bg-indigo-600/10 opacity-100' : 'bg-indigo-500/5 opacity-40'"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[35%] h-[35%] blur-[150px] rounded-full transition-opacity duration-300"
                 :class="isDark ? 'bg-purple-600/10 opacity-100' : 'bg-purple-500/5 opacity-40'"></div>
        </div>

        <!-- Top Navigation Bar -->
        <header class="w-full py-4 px-6 lg:px-12 flex justify-between items-center border-b backdrop-blur-md sticky top-0 z-50 transition-colors"
                :class="isDark ? 'border-slate-850 bg-[#080d16]/80' : 'border-slate-200 bg-white/80'"
        >
            <Link href="/training" class="flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-indigo-500 transition-colors group">
                <ArrowLeftIcon class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                {{ content[locale].backBtn }}
            </Link>
            
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-black font-outfit shadow-lg shadow-indigo-500/20 text-white">
                    G
                </div>
                <div>
                    <span class="text-base font-black tracking-wider uppercase font-outfit" :class="isDark ? 'text-white' : 'text-slate-900'">
                        GGAA <span class="text-indigo-500">Systems</span>
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
                            :class="locale === 'en' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200'">EN</button>
                    <button @click="setLanguage('am')" class="px-2.5 py-1 rounded-lg text-[10px] font-bold transition-all"
                            :class="locale === 'am' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200'">አማ</button>
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
                        @click="currentStep = index"
                        class="px-3.5 py-2 text-xs font-bold font-outfit rounded-xl transition-all flex items-center gap-1.5"
                        :class="[
                            currentStep === index 
                                ? 'bg-indigo-600 text-white shadow-md' 
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
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-500/10 text-indigo-400 text-xs font-black uppercase tracking-widest border border-indigo-500/20 font-outfit">
                    <AcademicCapIcon class="h-5 w-5" />
                    {{ locale === 'en' ? 'Employee Entry Presentation' : 'የሰራተኛ መግቢያ የስላይድ ገለጻ' }}
                </span>
                
                <h1 class="text-4xl sm:text-6xl font-black font-outfit tracking-tighter leading-tight"
                    :class="isDark ? 'text-white' : 'text-slate-955'"
                >
                    {{ content[locale].overview.title }}
                </h1>
                
                <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                    {{ content[locale].overview.desc1 }}
                </p>
                <p class="text-base leading-relaxed font-medium" :class="isDark ? 'text-slate-400' : 'text-slate-500'">
                    {{ content[locale].overview.desc2 }}
                </p>

                <div class="pt-6">
                    <button @click="currentStep = 1" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow-xl shadow-indigo-600/30 hover:scale-105 active:scale-95 transition-all flex items-center gap-2 text-sm uppercase tracking-wider font-outfit mx-auto">
                        {{ content[locale].overview.startBtn }}
                        <ChevronRightIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- STEP 1: TOUR WORKSPACE -->
            <div v-if="currentStep === 1" class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-stretch py-2 min-h-[500px] animate-fade-in">
                <div class="flex flex-col justify-between space-y-6 rounded-[32px] p-6 lg:p-8 border backdrop-blur-md"
                     :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-white border-slate-200 shadow-lg'"
                >
                    <div class="space-y-4">
                        <span class="text-xs font-black uppercase text-indigo-400 tracking-widest block font-outfit">Chapter 1 of 4</span>
                        <h2 class="text-3xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                            {{ content[locale].tourWorkspace.title }}
                        </h2>
                        <p class="text-sm font-medium leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-650'">
                            {{ content[locale].tourWorkspace.desc1 }}
                        </p>

                        <div class="space-y-4 pt-2">
                            <div class="p-4 rounded-2xl border" :class="isDark ? 'bg-slate-950/20 border-slate-850' : 'bg-slate-50 border-slate-200'">
                                <h4 class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">{{ content[locale].tourWorkspace.pt1Title }}</h4>
                                <p class="text-xs mt-1" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ content[locale].tourWorkspace.pt1Desc }}</p>
                            </div>
                            <div class="p-4 rounded-2xl border" :class="isDark ? 'bg-slate-950/20 border-slate-850' : 'bg-slate-50 border-slate-200'">
                                <h4 class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">{{ content[locale].tourWorkspace.pt2Title }}</h4>
                                <p class="text-xs mt-1" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ content[locale].tourWorkspace.pt2Desc }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t flex justify-between" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                        <button @click="currentStep = 0" class="px-4 py-2 text-xs font-bold uppercase border rounded-xl" :class="isDark ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-600'">
                            {{ locale === 'en' ? 'Back' : 'ተመለስ' }}
                        </button>
                        <button @click="currentStep = 2" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5">
                            {{ content[locale].tourWorkspace.nextBtn }}
                            <ChevronRightIcon class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Right Side Visual mockup -->
                <div class="rounded-[32px] p-6 border flex items-center justify-center" :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200 shadow-md'">
                    <div class="w-full max-w-md border rounded-[28px] p-6 space-y-4" :class="isDark ? 'bg-[#0f1524] border-slate-850' : 'bg-slate-50 border-slate-200 shadow-sm'">
                        <h4 class="text-xs font-black uppercase text-slate-500 tracking-wider pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">Employee Dashboard Mockup</h4>
                        <div class="h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/25 flex items-center justify-between px-4 text-xs font-bold">
                            <span>Stress Level: 50 Pts (Optimal)</span>
                            <span class="text-emerald-500">🟢 Healthy</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 border rounded-xl space-y-1 bg-slate-500/5">
                                <span class="text-[9px] uppercase font-black text-slate-500">Task Board</span>
                                <span class="text-xs font-bold block" :class="isDark ? 'text-white' : 'text-slate-900'">3 Active Clients</span>
                            </div>
                            <div class="p-3 border rounded-xl space-y-1 bg-slate-500/5">
                                <span class="text-[9px] uppercase font-black text-slate-500">Errand Log</span>
                                <span class="text-xs font-bold block" :class="isDark ? 'text-white' : 'text-slate-900'">1 Pending Trip</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SPLIT SCREEN LAYOUT FOR LABS & TOUR IN BETWEEN -->
            <div v-if="currentStep >= 2 && currentStep <= 5" class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-stretch py-2 min-h-[500px]">
                
                <!-- LEFT SIDE: LAB GUIDE / PRESENTATION TEXT -->
                <div class="flex flex-col justify-between space-y-6 rounded-[32px] p-6 lg:p-8 border backdrop-blur-md"
                     :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-white border-slate-200 shadow-lg'"
                >
                    
                    <!-- Content for Lab 1 (Step 2) -->
                    <div v-if="currentStep === 2" class="space-y-4">
                        <span class="text-xs font-black uppercase text-indigo-400 tracking-widest block font-outfit">Lab Exercise 1 (Optional)</span>
                        <h2 class="text-3xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                            {{ content[locale].lab1.guideTitle }}
                        </h2>
                        <p class="text-sm font-medium leading-relaxed" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                            {{ content[locale].lab1.intro }}
                        </p>
                        <div class="p-5 rounded-2xl space-y-2.5" :class="isDark ? 'bg-slate-950/40 border border-slate-850' : 'bg-slate-50 border border-slate-200'">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-500">{{ content[locale].lab1.instructionTitle }}</h4>
                            <ul class="space-y-2 text-xs font-semibold leading-relaxed" :class="isDark ? 'text-slate-400' : 'text-slate-600'">
                                <li>{{ content[locale].lab1.inst1 }}</li>
                                <li>{{ content[locale].lab1.inst2 }}</li>
                                <li>{{ content[locale].lab1.inst3 }}</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Content for Ledger Tour (Step 3) -->
                    <div v-if="currentStep === 3" class="space-y-4">
                        <span class="text-xs font-black uppercase text-indigo-400 tracking-widest block font-outfit">Chapter 2 of 4</span>
                        <h2 class="text-3xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                            {{ content[locale].tourLedger.title }}
                        </h2>
                        <p class="text-sm font-medium leading-relaxed" :class="isDark ? 'text-slate-355' : 'text-slate-650'">
                            {{ content[locale].tourLedger.desc1 }}
                        </p>

                        <div class="space-y-3.5 pt-1">
                            <div class="p-3 border rounded-2xl bg-slate-550/5" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <h4 class="text-xs font-black text-indigo-400 uppercase">{{ content[locale].tourLedger.pt1Title }}</h4>
                                <p class="text-xs mt-0.5" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ content[locale].tourLedger.pt1Desc }}</p>
                            </div>
                            <div class="p-3 border rounded-2xl bg-slate-550/5" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <h4 class="text-xs font-black text-indigo-400 uppercase">{{ content[locale].tourLedger.pt2Title }}</h4>
                                <p class="text-xs mt-0.5" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ content[locale].tourLedger.pt2Desc }}</p>
                            </div>
                            <div class="p-3 border rounded-2xl bg-slate-550/5" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <h4 class="text-xs font-black text-indigo-400 uppercase">{{ content[locale].tourLedger.pt3Title }}</h4>
                                <p class="text-xs mt-0.5" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ content[locale].tourLedger.pt3Desc }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Content for Errand Lab (Step 4) -->
                    <div v-if="currentStep === 4" class="space-y-4">
                        <span class="text-xs font-black uppercase text-indigo-400 tracking-widest block font-outfit">Lab Exercise 2 (Optional)</span>
                        <h2 class="text-3xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                            {{ content[locale].lab2.guideTitle }}
                        </h2>
                        <p class="text-sm font-medium leading-relaxed" :class="isDark ? 'text-slate-350' : 'text-slate-655'">
                            {{ content[locale].lab2.intro }}
                        </p>
                        <div class="p-5 rounded-2xl space-y-2.5" :class="isDark ? 'bg-slate-950/40 border border-slate-850' : 'bg-slate-50 border border-slate-200'">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-500">{{ content[locale].lab2.instructionTitle }}</h4>
                            <ul class="space-y-2 text-xs font-semibold leading-relaxed" :class="isDark ? 'text-slate-400' : 'text-slate-600'">
                                <li>{{ content[locale].lab2.inst1 }}</li>
                                <li>{{ content[locale].lab2.inst2 }}</li>
                                <li>{{ content[locale].lab2.inst3 }}</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Content for TIN Lab (Step 5) -->
                    <div v-if="currentStep === 5" class="space-y-4">
                        <span class="text-xs font-black uppercase text-indigo-400 tracking-widest block font-outfit">Lab Exercise 3 (Optional)</span>
                        <h2 class="text-3xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                            {{ content[locale].lab3.guideTitle }}
                        </h2>
                        <p class="text-sm font-medium leading-relaxed" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                            {{ content[locale].lab3.intro }}
                        </p>
                        
                        <div class="space-y-3 pt-1">
                            <div class="space-y-1">
                                <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].lab3.tinLabel }}</label>
                                <input type="text" v-model="inputTin" placeholder="e.g. 0928301840" 
                                       class="w-full bg-slate-500/10 border px-4 py-2 rounded-xl font-bold font-mono text-xs outline-none transition-all"
                                       :class="isDark ? 'border-slate-800 text-white focus:border-indigo-500' : 'border-slate-250 text-slate-900 focus:border-indigo-500'"
                                       :disabled="lab3Passed"
                                >
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].lab3.legalLabel }}</label>
                                <select v-model="inputLegal" 
                                        class="w-full bg-slate-500/10 border px-4 py-2 rounded-xl font-bold text-xs outline-none transition-all"
                                        :class="isDark ? 'border-slate-800 text-white focus:border-indigo-500' : 'border-slate-250 text-slate-900 focus:border-indigo-500'"
                                        :disabled="lab3Passed"
                                >
                                    <option value="" disabled>{{ locale === 'en' ? 'Select Legal Type' : 'የህግ መዋቅር ይምረጡ' }}</option>
                                    <option value="PLC">PLC (Private Limited Company)</option>
                                    <option value="Sole">Sole Proprietorship</option>
                                </select>
                            </div>
                            <button v-if="!lab3Passed" @click="validateLab3" 
                                    class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all animate-pulse"
                            >
                                {{ content[locale].lab3.validateBtn }}
                            </button>
                            <p v-if="lab3Error" class="text-xs font-bold text-rose-500">{{ lab3Error }}</p>
                        </div>
                    </div>

                    <!-- REUSABLE SKIP PRACTICE / STEP ACTIONS PANEL -->
                    <div class="pt-4 border-t" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                        
                        <!-- Success feedback for completed labs -->
                        <div v-if="(currentStep === 2 && lab1Passed && !lab1Skipped) || (currentStep === 4 && lab2Passed && !lab2Skipped) || (currentStep === 5 && lab3Passed && !lab3Skipped)" 
                             class="p-3.5 mb-3 rounded-xl border text-xs font-semibold text-emerald-500 bg-emerald-500/10 border-emerald-500/20"
                        >
                            {{ content[locale][`lab${currentStep === 2 ? 1 : (currentStep === 4 ? 2 : 3)}`].successMsg }}
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
                                    class="px-4 py-2 rounded-xl border text-xs font-bold uppercase transition-all"
                                    :class="isDark ? 'border-slate-800 text-slate-400 hover:bg-slate-900' : 'border-slate-250 text-slate-650 hover:bg-slate-100'"
                            >
                                {{ locale === 'en' ? 'Back' : 'ተመለስ' }}
                            </button>
                            
                            <button @click="currentStep++" 
                                    :disabled="currentStep === 2 ? !lab1Passed : currentStep === 4 ? !lab2Passed : currentStep === 5 ? !lab3Passed : false"
                                    class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5"
                            >
                                {{ currentStep === 3 ? content[locale].tourLedger.nextBtn : (locale === 'en' ? 'Next Slide' : 'ቀጣይ ስላይድ') }}
                                <ChevronRightIcon class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: SIMULATED OPERATIONS SANDBOX -->
                <div class="rounded-[32px] p-6 lg:p-8 border flex flex-col justify-center relative overflow-hidden"
                     :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-250 shadow-md'"
                >
                    
                    <!-- LAB 1: TASK BOARD -->
                    <div v-if="currentStep === 2" class="space-y-4 w-full">
                        <div class="flex justify-between items-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest text-left">{{ content[locale].lab1.panelTitle }}</span>
                            <span class="text-xs font-bold" :class="isDark ? 'text-slate-400' : 'text-slate-700'">80 Pts Capacity Limit</span>
                        </div>

                        <!-- STRESS METER WIDGET -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-xs font-black uppercase">
                                <span :class="isDark ? 'text-slate-350' : 'text-slate-750'">{{ content[locale].lab1.stressLabel }}</span>
                                <span :class="[
                                    lab1Stress >= 60 ? 'text-rose-500' : 
                                    lab1Stress >= 50 ? 'text-amber-500' : 'text-emerald-500'
                                ]" class="font-outfit text-base font-extrabold">
                                    {{ lab1Stress }} / 80 Pts
                                </span>
                            </div>
                            <div class="w-full bg-slate-500/15 rounded-full h-3 overflow-hidden p-0.5 border" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                                <div class="h-full rounded-full transition-all duration-500 bg-gradient-to-r"
                                     :class="[
                                         lab1Stress >= 60 ? 'from-orange-500 to-rose-600' : 
                                         lab1Stress >= 50 ? 'from-yellow-400 to-amber-500' : 'from-emerald-500 to-teal-500'
                                     ]"
                                     :style="`width: ${Math.round((lab1Stress / 80) * 100)}%`"
                                ></div>
                            </div>
                            <div class="text-[10px] font-bold text-center uppercase py-1.5 rounded-lg"
                                 :class="[
                                     lab1Stress >= 60 ? (isDark ? 'text-rose-400 bg-rose-500/10' : 'text-rose-700 bg-rose-100') : 
                                     lab1Stress >= 50 ? (isDark ? 'text-amber-400 bg-amber-500/10' : 'text-amber-700 bg-amber-100') :
                                     (isDark ? 'text-emerald-400 bg-emerald-500/10' : 'text-emerald-700 bg-emerald-100')
                                 ]"
                            >
                                {{ 
                                    lab1Stress >= 60 ? content[locale].lab1.stressed : 
                                    lab1Stress >= 50 ? content[locale].lab1.busy : 
                                    content[locale].lab1.happy 
                                }}
                            </div>
                        </div>

                        <!-- KANBAN COLUMNS -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Pending Docs Column -->
                            <div class="p-3 rounded-2xl border space-y-3 min-h-[160px]" :class="isDark ? 'bg-slate-950/20 border-slate-800' : 'bg-slate-50 border-slate-200'">
                                <span class="text-[10px] font-black uppercase text-blue-500 border-b pb-1 block text-left">{{ content[locale].lab1.pendingCol }}</span>
                                
                                <div v-if="lab1TaskState === 'pending'" class="p-3 border border-l-4 border-l-rose-500 rounded-xl space-y-2 shadow-sm" :class="isDark ? 'bg-[#121926] border-slate-850' : 'bg-white border-slate-250'">
                                    <div class="flex justify-between items-center gap-2">
                                        <span class="text-xs font-bold text-left" :class="isDark ? 'text-white' : 'text-slate-900'">Zenith PLC</span>
                                        <span class="text-[8px] font-black text-rose-500 uppercase shrink-0">{{ content[locale].lab1.redBadge }}</span>
                                    </div>
                                    <button @click="lab1TaskState = 'review'" class="w-full py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-[9px] font-black uppercase tracking-wider transition-all">
                                        {{ content[locale].lab1.moveBtn }}
                                    </button>
                                </div>

                                <div class="p-3 border border-l-4 border-l-slate-400 rounded-xl space-y-2 shadow-sm" :class="isDark ? 'bg-[#121926] border-slate-850' : 'bg-white border-slate-250'">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-bold text-left" :class="isDark ? 'text-white' : 'text-slate-900'">Haddis Shop</span>
                                        <span class="text-[8px] font-black text-slate-500 uppercase">{{ content[locale].lab1.yellowBadge }}</span>
                                    </div>
                                    <button v-if="!lab1Delegated" @click="lab1Delegated = true" class="w-full py-1.5 bg-slate-500/10 hover:bg-slate-500/20 text-slate-400 rounded-lg text-[9px] font-black uppercase transition-all">
                                        {{ content[locale].lab1.delegateBtn }}
                                    </button>
                                    <span v-else class="block text-center py-1 bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 rounded-lg text-[8px] font-black uppercase">
                                        {{ content[locale].lab1.delegatedBadge }}
                                    </span>
                                </div>
                            </div>

                            <!-- Under Review Column -->
                            <div class="p-3 rounded-2xl border space-y-3 min-h-[160px]" :class="isDark ? 'bg-slate-950/20 border-slate-800' : 'bg-slate-50 border-slate-200'">
                                <span class="text-[10px] font-black uppercase text-purple-500 border-b pb-1 block text-left">{{ content[locale].lab1.reviewCol }}</span>
                                <div v-if="lab1TaskState === 'review'" class="p-3 border border-l-4 border-l-purple-500 rounded-xl space-y-2 shadow-sm animate-fade-in" :class="isDark ? 'bg-[#121926] border-slate-850' : 'bg-white border-slate-250'">
                                    <span class="text-xs font-bold text-left block" :class="isDark ? 'text-white' : 'text-slate-900'">Zenith PLC</span>
                                    <p class="text-[9px] text-slate-550 block">Waiting review...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: MOCK LEDGER PREFILL VISUALIZER -->
                    <div v-if="currentStep === 3" class="space-y-4 w-full flex flex-col justify-center">
                        <div class="flex justify-between items-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest text-left">Sync Prefill Simulator</span>
                            <span class="text-xs text-indigo-400 font-bold">Zenith PLC (Meskeram)</span>
                        </div>

                        <!-- Prefilled form mockup -->
                        <div class="space-y-2.5 text-xs font-medium">
                            <div class="p-3 border rounded-xl space-y-1.5" :class="isDark ? 'bg-[#101726] border-slate-850' : 'bg-slate-50 border-slate-250'">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">A) Beginning Inventory</span>
                                    <span class="text-indigo-400 font-bold">100,000.00 ETB</span>
                                </div>
                                <p class="text-[9px] text-slate-500 text-left">✓ Carried from Nehase ending inventory (auto-predict)</p>
                            </div>
                            <div class="p-3 border rounded-xl space-y-1.5" :class="isDark ? 'bg-[#101726] border-slate-850' : 'bg-slate-50 border-slate-250'">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Cash Machine Start Number</span>
                                    <span class="text-indigo-400 font-bold">#20941</span>
                                </div>
                                <p class="text-[9px] text-slate-500 text-left">✓ Continues from Nehase ending number + 1 (seq range)</p>
                            </div>
                            <div class="p-3 border rounded-xl space-y-1.5" :class="isDark ? 'bg-[#101726] border-slate-850' : 'bg-slate-50 border-slate-250'">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">B) Purchases (raw materials)</span>
                                    <span class="text-indigo-400 font-bold">45,300.00 ETB</span>
                                </div>
                                <p class="text-[9px] text-slate-500 text-left">✓ Summed from 5 uploaded purchase receipts</p>
                            </div>
                        </div>
                    </div>

                    <!-- LAB 2: ERRAND TRIP TRACKER -->
                    <div v-if="currentStep === 4" class="space-y-6 w-full flex flex-col items-center justify-center">
                        <div class="w-full max-w-xs rounded-[28px] border-4 border-slate-800 overflow-hidden shadow-2xl relative" :class="isDark ? 'bg-[#0f1524]' : 'bg-white'">
                            <div class="bg-slate-800 py-1.5 px-4 flex justify-between items-center text-[9px] font-bold text-slate-400">
                                <span>GGAA Mobile GPS</span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            </div>

                            <div class="p-5 space-y-4">
                                <div class="border rounded-xl p-3.5 space-y-3" :class="isDark ? 'bg-slate-950/40 border-slate-850' : 'bg-slate-50 border-slate-200'">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">🚗</span>
                                        <div class="text-left">
                                            <h5 class="text-xs font-black text-left" :class="isDark ? 'text-white' : 'text-slate-950'">{{ content[locale].lab2.errandTitle }}</h5>
                                            <p class="text-[9px] text-slate-500">{{ content[locale].lab2.errandLoc }}</p>
                                        </div>
                                    </div>
                                    <div class="border-t pt-2 text-[10px]" :class="isDark ? 'border-slate-855' : 'border-slate-200'">
                                        <span class="text-slate-500 font-black block text-left">Status:</span>
                                        <span class="font-black uppercase block text-left" :class="lab2State === 'completed' ? 'text-emerald-500' : 'text-indigo-400'">
                                            {{ 
                                                lab2State === 'ready' ? content[locale].lab2.stateReady :
                                                lab2State === 'traveling' ? content[locale].lab2.stateTraveling :
                                                lab2State === 'arrived' ? content[locale].lab2.stateArrived :
                                                content[locale].lab2.stateDone
                                            }}
                                        </span>
                                    </div>

                                    <div v-if="lab2State === 'traveling'" class="space-y-1 animate-fade-in">
                                        <div class="w-full bg-slate-500/15 rounded-full h-2 overflow-hidden border">
                                            <div class="h-full bg-indigo-500 rounded-full transition-all duration-200" :style="`width: ${travelProgress}%`"></div>
                                        </div>
                                    </div>

                                    <button v-if="lab2State === 'ready'" @click="startTravel" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs uppercase transition-all">
                                        {{ content[locale].lab2.startBtn }}
                                    </button>
                                    <button v-if="lab2State === 'arrived'" @click="completeErrand" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs uppercase transition-all animate-bounce">
                                        {{ content[locale].lab2.deliverBtn }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LAB 3: TIN & REGISTRATION VERIFICATION -->
                    <div v-if="currentStep === 5" class="space-y-4 w-full flex flex-col justify-center">
                        <div class="rounded-3xl border-4 p-5 space-y-3 relative overflow-hidden text-left" :class="isDark ? 'bg-slate-900 border-amber-500/20 text-slate-350 shadow-amber-500/5' : 'bg-amber-50/50 border-amber-300 text-slate-800'">
                            <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full border-4 border-red-500/20 flex items-center justify-center rotate-12 text-[8px] text-red-500/20 font-black uppercase text-center">Ministry of Trade</div>
                            <div class="text-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-amber-250'">
                                <h3 class="text-xs font-black uppercase text-amber-500">Ministry of Trade</h3>
                                <p class="text-[8px] uppercase text-slate-500">Registration Certificate</p>
                            </div>
                            <div class="space-y-2 text-[11px] font-semibold">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Name:</span>
                                    <span class="font-extrabold" :class="isDark ? 'text-white' : 'text-slate-900'">Awash Coffee Exports PLC</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">TIN (10-Digit):</span>
                                    <span class="font-black font-mono text-indigo-500 bg-indigo-500/10 px-2 py-0.5 rounded">0928301840</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Type:</span>
                                    <span class="font-bold text-amber-500">Private Limited Company (PLC)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- STEP 6: GRADUATION -->
            <div v-if="currentStep === 6" class="max-w-4xl mx-auto py-4 space-y-8 animate-fade-in relative z-10">
                <!-- Pure-CSS Confetti Particles -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden">
                    <div class="confetti-particle bg-indigo-500 top-0 left-[10%]"></div>
                    <div class="confetti-particle bg-purple-500 top-0 left-[30%]"></div>
                    <div class="confetti-particle bg-emerald-500 top-0 left-[50%]"></div>
                    <div class="confetti-particle bg-yellow-500 top-0 left-[70%]"></div>
                    <div class="confetti-particle bg-rose-500 top-0 left-[90%]"></div>
                </div>

                <div class="text-center space-y-4 max-w-2xl mx-auto">
                    <span class="inline-flex items-center gap-1 bg-emerald-500/10 border border-emerald-500/25 text-emerald-455 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest animate-pulse">
                        <SparklesIcon class="h-4 w-4" />
                        {{ content[locale].graduation.title }}
                    </span>
                    <h2 class="text-4xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                        {{ locale === 'en' ? 'Tour Completed!' : 'የገለጻ ጉብኝቱ ተጠናቋል!' }}
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
                                <span class="h-16 w-16 rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-3xl shadow-lg">⚡</span>
                                <div>
                                    <h4 class="font-black text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ content[locale].graduation.badgeTitle }}</h4>
                                    <p class="text-xs text-slate-500 font-semibold">{{ content[locale].graduation.badgeDesc }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Name Edit Input -->
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].graduation.certNameLabel }}</label>
                            <input type="text" v-model="userName" 
                                   class="w-full bg-slate-500/10 border px-4 py-2.5 rounded-xl font-bold text-sm outline-none transition-all"
                                   :class="isDark ? 'border-slate-800 text-white focus:border-indigo-500' : 'border-slate-250 text-slate-900 focus:border-indigo-500'"
                            >
                        </div>

                        <Link href="/training" 
                              class="w-full py-4 bg-indigo-650 hover:bg-indigo-700 text-white text-center font-black rounded-2xl text-xs uppercase tracking-wider block shadow-lg shadow-indigo-600/10 transition-all"
                        >
                            {{ content[locale].graduation.homeBtn }}
                        </Link>
                    </div>

                    <!-- Right Column: Official Certificate Mockup -->
                    <div class="md:col-span-3">
                        <div class="rounded-[36px] border-8 p-8 relative overflow-hidden shadow-2xl text-center space-y-6"
                             :class="isDark 
                                 ? 'bg-[#0f1626] border-indigo-650/40 text-slate-350 shadow-indigo-500/5' 
                                 : 'bg-white border-indigo-100 text-slate-700 shadow-xl'"
                        >
                            <!-- Seals & Decoration -->
                            <div class="absolute top-4 left-4 h-10 w-10 border-t-2 border-l-2 border-indigo-500/30"></div>
                            <div class="absolute top-4 right-4 h-10 w-10 border-t-2 border-r-2 border-indigo-500/30"></div>
                            <div class="absolute bottom-4 left-4 h-10 w-10 border-b-2 border-l-2 border-indigo-500/30"></div>
                            <div class="absolute bottom-4 right-4 h-10 w-10 border-b-2 border-r-2 border-indigo-500/30"></div>

                            <div class="space-y-2">
                                <span class="text-[9px] uppercase font-black text-indigo-500 tracking-widest">Certificate of Training</span>
                                <h3 class="text-xl sm:text-2xl font-black font-outfit tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400 uppercase">
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
                                <span class="text-indigo-400 font-extrabold">GGAA Audit Academy</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </main>

        <!-- Footer Operations Title -->
        <footer class="w-full py-4 border-t transition-colors text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest"
                :class="isDark ? 'border-slate-850 bg-[#080d16]' : 'border-slate-200 bg-slate-100'"
        >
            GGAA Systems Portal • operations simulator
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
