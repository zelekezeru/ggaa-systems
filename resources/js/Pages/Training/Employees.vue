<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    UserIcon, AcademicCapIcon,
    ChevronLeftIcon, ChevronRightIcon,
    ArrowLeftIcon, SunIcon, MoonIcon,
    CheckCircleIcon, ExclamationTriangleIcon,
    MapPinIcon, ClockIcon, DocumentTextIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';
import Toast from 'vue-toastification';

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
// LAB 1 STATE: PRIORITIZE & DELEGATE
// ==========================================
const lab1TaskState = ref('pending'); // 'pending' or 'review'
const lab1Delegated = ref(false);

const lab1Stress = computed(() => {
    let base = 35; // base stress from normal tasks
    if (lab1TaskState.value === 'pending') base += 30; // red task is high stress
    if (!lab1Delegated.value) base += 15; // low task is extra stress
    return base;
});

const lab1Passed = computed(() => {
    return lab1TaskState.value === 'review' && lab1Stress.value < 50;
});

// ==========================================
// LAB 2 STATE: ERRAND TRIP TRACKING
// ==========================================
const lab2State = ref('ready'); // 'ready', 'traveling', 'arrived', 'completed'
const travelProgress = ref(0);
let travelInterval = null;

const lab2Passed = computed(() => {
    return lab2State.value === 'completed';
});

function startTravel() {
    if (lab2State.value !== 'ready') return;
    lab2State.value = 'traveling';
    travelProgress.value = 0;
    
    travelInterval = setInterval(() => {
        if (travelProgress.value < 100) {
            travelProgress.value += 10;
        } else {
            clearInterval(travelInterval);
            lab2State.value = 'arrived';
        }
    }, 300);
}

function completeErrand() {
    if (lab2State.value !== 'arrived') return;
    lab2State.value = 'completed';
}

// ==========================================
// LAB 3 STATE: TIN CERTIFICATE VALIDATION
// ==========================================
const inputTin = ref('');
const inputLegal = ref('');
const lab3Error = ref('');
const lab3Passed = ref(false);

function validateLab3() {
    lab3Error.value = '';
    // Expected values matching the mock certificate
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

// ==========================================
// GRADUATION & STATE SAVING
// ==========================================
watch(currentStep, (newStep) => {
    if (newStep === 4) {
        localStorage.setItem('training-completed-employees', 'true');
    }
});

// Localization content
const content = {
    en: {
        backBtn: "Back to Tracks",
        headerTitle: "Employee Academy",
        headerSubtitle: "Interactive Operations Simulator",
        stepNames: ["Overview", "Lab 1: Prioritize", "Lab 2: Errand Log", "Lab 3: TIN Verify", "Graduation 🎓"],
        
        overview: {
            title: "Welcome to GGAA Employee Academy!",
            desc1: "This simulator will guide you through the exact daily operational flows required by our branch offices. Rather than just reading, you will participate in three active laboratory exercises to prove your readiness.",
            desc2: "Learn to handle your personal task dashboard, track outdoor errand logs on the mobile simulation, and validate business licenses with local tax guidelines.",
            startBtn: "Start Practical Training",
        },
        lab1: {
            guideTitle: "Lab 1: Prioritize & Delegate",
            intro: "In GGAA, managers track your workload in points. Your total stress load must remain in the Green Zone (under 50 points) to avoid burnout. Red-flagged tasks indicate urgent compliance deadlines and must be submitted for review first.",
            instructionTitle: "Your Laboratory Objective:",
            inst1: "1. Identify the red-flagged task (Zenith PLC) and click 'Move to Review' to submit it.",
            inst2: "2. Your stress level will still be high. Click 'Delegate to Assistant' on the low-priority task (Haddis Shop) to distribute the load.",
            inst3: "3. When stress is in the green zone and the red task is submitted, click 'Next Lab'.",
            panelTitle: "Mock Workload Dashboard",
            stressLabel: "Workload Stress Meter:",
            optimal: "Too Chill (Need tasks)",
            happy: "Happy & Safe (Green Zone)",
            busy: "Busy & Focused",
            stressed: "Overloaded! Action required!",
            boardTitle: "Personal Task Board",
            pendingCol: "Pending Docs",
            reviewCol: "Under Review",
            moveBtn: "Move to Review",
            delegateBtn: "Delegate to Assistant",
            delegatedBadge: "Delegated Out",
            redBadge: "🚨 High Risk - Due in 2h",
            yellowBadge: "Low Priority",
            successMsg: "🎉 Success! You prioritized the red-flagged task and delegated the minor work. Stress load is now at a healthy 50 Pts. You can proceed!",
        },
        lab2: {
            guideTitle: "Lab 2: Errand Log Simulation",
            intro: "Whenever you go outdoors for physical tasks (like submitting folders at the Ministry of Revenues or withdrawing tax logs at a bank), you must log the errand status. This helps managers track travel and calculate mileage reimbursements.",
            instructionTitle: "Your Laboratory Objective:",
            inst1: "1. Review the active outdoor errand on the mobile screen mockup on the right.",
            inst2: "2. Click 'Start Errand Trip' to begin. Wait for the simulated GPS tracker to arrive at the destination.",
            inst3: "3. Once arrived, click 'Confirm Delivery' to complete the task.",
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
            guideTitle: "Lab 3: TIN & License Verification",
            intro: "Before filing tax reports, you must verify the client's information against their official government-issued license. Typographical errors in Tax Identification Numbers (TIN) lead to severe compliance rejections.",
            instructionTitle: "Your Laboratory Objective:",
            inst1: "1. Inspect the official Ministry of Trade registration certificate displayed on the right.",
            inst2: "2. Identify the 10-digit Tax Identification Number (TIN) and the Legal Structure.",
            inst3: "3. Fill in the data input fields below the guide and click 'Validate and Save'.",
            tinLabel: "Enter 10-Digit TIN:",
            legalLabel: "Select Legal Structure:",
            validateBtn: "Validate & Save",
            successMsg: "🎉 Success! TIN matched and legal status validated. Client files updated.",
        },
        graduation: {
            title: "Congratulations, Graduate!",
            subtitle: "You have successfully completed all three operational simulation challenges.",
            badgeLabel: "Unlocked Profile Badge:",
            badgeTitle: "⚡ Speed Demon",
            badgeDesc: "Completed operations training with zero verification errors.",
            certTitle: "OPERATIONS SPECIALIST CERTIFICATE",
            certSubtitle: "GGAA Systems Compliance Academy",
            certBody: "This certifies that the holder has passed the rigorous operations simulator, demonstrated proficiency in capacity scheduling, GPS errand logging, and tax ID validation.",
            certDate: "Certified on: June 2026",
            certNameLabel: "Change Certificate Name:",
            homeBtn: "Return to Training Hub",
        }
    },
    am: {
        backBtn: "ወደ ስልጠናዎች ተመለስ",
        headerTitle: "የሰራተኞች የስልጠና ክፍል",
        headerSubtitle: "የእለታዊ ስራዎች ማስመሰያ ገጽ",
        stepNames: ["ማጠቃለያ", "ላብ 1: ቅድሚያ መስጠት", "ላብ 2: የውጭ ስራ", "ላብ 3: TIN ማረጋገጥ", "ምረቃ 🎓"],
        
        overview: {
            title: "እንኳን ወደ ሰራተኞች የስልጠና ክፍል በደህና መጡ!",
            desc1: "ይህ ሲስተም በቅርንጫፍ ቢሮዎቻችን ውስጥ በየቀኑ የሚሰሩ ስራዎችን በተግባር እንዲለማመዱ የሚረዳ ነው። ስልጠናው ዝም ብሎ ንባብ ሳይሆን የተግባር ብቃቶን የሚያረጋግጡበት 3 ላብራቶሪዎችን ያካተተ ነው።",
            desc2: "በዚህ ስልጠና ላይ የግል ስራ ሰሌዳዎን ማስተዳደር፣ በስልክዎ የውጭ ስራዎችን መከታተል፣ እና የደንበኞችን የግብር ቁጥር መፈተሽ ይማራሉ።",
            startBtn: "የተግባር ስልጠናውን ጀምር",
        },
        lab1: {
            guideTitle: "ላብ 1: ቅድሚያ መስጠት እና ስራ ማደላደል",
            intro: "በጂጂኤኤ ሲስተም ውስጥ ማናጀሮች የእርስዎን የስራ ጫና በነጥቦች ይለካሉ። ከአቅም በላይ ጫና እንዳይኖርብዎት ጠቅላላ ነጥብዎ ሁልጊዜ ከአረንጓዴ (ከ50 በታች) መሆን አለበት። ቀይ ባንዲራ ያለባቸው ስራዎች አስቸኳይ በመሆናቸው ቀድመው ማለቅ አለባቸው።",
            instructionTitle: "የተግባር ልምምድ መመሪያዎች:",
            inst1: "1. በቀኝ በኩል ካለው ገበታ ላይ ቀይ ባንዲራ ያለበትን ስራ (Zenith PLC) ፈልገው 'ለግምገማ ላክ' የሚለውን ይጫኑ።",
            inst2: "2. አሁንም የስራ ጫናዎ ከፍተኛ ስለሆነ ዝቅተኛ ጠቀሜታ ያለውን ስራ (Haddis Shop) 'ለረዳት አስተላልፍ' የሚለውን ይጫኑ።",
            inst3: "3. የስራ ጫናዎ አረንጓዴ ውስጥ ሲገባ እና ቀዩ ስራ ለግምገማ ሲላክ 'ቀጣይ ላብ' የሚለውን ይጫኑ።",
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
            delegateBtn: "ለረዳት አስተላልፍ",
            delegatedBadge: "ለረዳት የተሰጠ",
            redBadge: "🚨 ከፍተኛ አደጋ - በ2 ሰዓት ውስጥ",
            yellowBadge: "ዝቅተኛ ቅድሚያ",
            successMsg: "🎉 እንኳን ደስ አለዎት! ቀይ ባንዲራ ያለበትን ስራ ቅድሚያ በመስጠት እና ቀላል ስራውን ለረዳት በማስተላለፍ የስራ ጫናዎን 50 ነጥብ (ጤናማ) አድርገዋል። ወደ ቀጣዩ መሄድ ይችላሉ!",
        },
        lab2: {
            guideTitle: "ላብ 2: የውጭ ስራዎች መከታተያ",
            intro: "ለስራ ወደ ውጭ ሲወጡ (ለምሳሌ ገቢዎች ሚኒስቴር ወረቀት ለማስገባት ወይም ባንክ ለመሄድ) ጉዞዎን መመዝገብ አለብዎት። ይህ ማናጀሩ የእርስዎን የትራንስፖርት ወጪ በትክክል ለማስላት ይረዳዋል።",
            instructionTitle: "የተግባር ልምምድ መመሪያዎች:",
            inst1: "1. በቀኝ በኩል ባለው የስልክ ማሳያ ላይ ያለውን የውጭ ስራ ይመልከቱ።",
            inst2: "2. 'ጉዞ ጀምር' የሚለውን ይጫኑ። መኪናው መንገዱን እስኪያጠናቅቅ ይጠብቁ።",
            inst3: "3. መድረሱን ሲያረጋግጥ 'ማድረስህን አረጋግጥ' የሚለውን በመጫን ስራውን ያጠናቅቁ።",
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
            guideTitle: "ላብ 3: TIN እና ፈቃድ ማረጋገጥ",
            intro: "ታክስ ከመሙላትዎ በፊት የደንበኛውን መረጃ ከመንግስት የንግድ ፈቃድ ጋር ማመሳከር አለብዎት። በግብር ከፋይ መለያ ቁጥር (TIN) ላይ የሚፈጠር ስህተት ከፍተኛ የገንዘብ ቅጣት እና የሪፖርት ውድቅ መሆን ያስከትላል።",
            instructionTitle: "የተግባር ልምምድ መመሪያዎች:",
            inst1: "1. በቀኝ በኩል የሚታየውን የንግድ ሚኒስቴር የምዝገባ ምስክር ወረቀት ይመልከቱ።",
            inst2: "2. ባለ 10 አሃዝ የግብር ከፋይ ቁጥር (TIN) እና የህግ መዋቅሩን (Legal Structure) ይለዩ።",
            inst3: "3. ከተግባር መመሪያው በታች ያሉትን ክፍተቶች በትክክል ይሙሉ እና 'አረጋግጥ' የሚለውን ይጫኑ።",
            tinLabel: "ባለ 10 አሃዝ TIN ያስገቡ:",
            legalLabel: "የህግ መዋቅር ይምረጡ:",
            validateBtn: "አረጋግጥና አስቀምጥ",
            successMsg: "🎉 እንኳን ደስ አለዎት! የግብር ከፋይ መለያ ቁጥር እና የህግ መዋቅሩ በትክክል ተረጋግጧል። የደንበኛው ማህደር ተስተካክሏል።",
        },
        graduation: {
            title: "እንኳን ደስ አለዎት! ተመርቀዋል!",
            subtitle: "ሁሉንም 3 ተግባራዊ የሲስተም አጠቃቀም ፈተናዎችን በተሳካ ሁኔታ አጠናቀዋል።",
            badgeLabel: "ያገኙት የክብር ባጅ:",
            badgeTitle: "⚡ የፍጥነት ንጉስ",
            badgeDesc: "ስልጠናውን ያለ ምንም ስህተት ያጠናቀቀ ባለሙያ።",
            certTitle: "የተግባር ስራዎች ባለሙያ ሰርተፊኬት",
            certSubtitle: "ጂጂኤኤ ሲስተምስ የስልጠና አካዳሚ",
            certBody: "ይህ ሰርተፊኬት ባለቤቱ የተግባራዊ ስራዎች ማስመሰያ ፈተናዎችን ያለ ምንም ስህተት በማለፉ፣ በስራ ጫና ማደላደል፣ በጉዞ ምዝገባ እና በግብር ሰነዶች አሞላል ላይ ሙሉ ብቃት ማሳየቱን ያረጋግጣል።",
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
                        @click="index <= 3 || lab3Passed ? currentStep = index : null"
                        class="px-4 py-2 text-xs font-bold font-outfit rounded-xl transition-all flex items-center gap-1.5"
                        :class="[
                            currentStep === index 
                                ? 'bg-indigo-600 text-white shadow-md' 
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
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-500/10 text-indigo-400 text-xs font-black uppercase tracking-widest border border-indigo-500/20">
                    <AcademicCapIcon class="h-5 w-5" />
                    {{ locale === 'en' ? 'Track 1: Employees Workspace Operations' : 'ክፍል 1፡ የሰራተኞች የስራ ማስፈጸሚያ ስልጠና' }}
                </span>
                
                <h1 class="text-4xl sm:text-6xl font-black font-outfit tracking-tighter leading-tight"
                    :class="isDark ? 'text-white' : 'text-slate-950'"
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

            <!-- SPLIT SCREEN LAYOUT FOR LABS (1, 2, 3) -->
            <div v-if="currentStep > 0 && currentStep < 4" class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-stretch py-2 min-h-[500px]">
                
                <!-- LEFT SIDE: LAB GUIDE & STATUS -->
                <div class="flex flex-col justify-between space-y-6 rounded-[32px] p-6 lg:p-8 border backdrop-blur-md"
                     :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-white border-slate-200 shadow-lg'"
                >
                    <div class="space-y-4">
                        <span class="text-xs font-black uppercase text-indigo-400 tracking-widest block font-outfit">
                            {{ locale === 'en' ? `Lab Exercise ${currentStep} of 3` : `ተግባራዊ ልምምድ ${currentStep} ከ 3` }}
                        </span>
                        
                        <h2 class="text-2xl lg:text-3xl font-black font-outfit tracking-tight"
                            :class="isDark ? 'text-white' : 'text-slate-900'"
                        >
                            {{ content[locale][`lab${currentStep}`].guideTitle }}
                        </h2>
                        
                        <p class="text-sm font-medium leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-650'">
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

                        <!-- LAB 3 DATA FORM -->
                        <div v-if="currentStep === 3" class="space-y-3 pt-2">
                            <div class="space-y-1">
                                <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].lab3.tinLabel }}</label>
                                <input type="text" v-model="inputTin" placeholder="e.g. 0928301840" 
                                       class="w-full bg-slate-500/10 border px-4 py-2.5 rounded-xl font-bold font-mono text-sm outline-none transition-all"
                                       :class="isDark ? 'border-slate-800 text-white focus:border-indigo-500' : 'border-slate-250 text-slate-900 focus:border-indigo-500'"
                                       :disabled="lab3Passed"
                                >
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].lab3.legalLabel }}</label>
                                <select v-model="inputLegal" 
                                        class="w-full bg-slate-500/10 border px-4 py-2.5 rounded-xl font-bold text-sm outline-none transition-all"
                                        :class="isDark ? 'border-slate-800 text-white focus:border-indigo-500' : 'border-slate-250 text-slate-900 focus:border-indigo-500'"
                                        :disabled="lab3Passed"
                                >
                                    <option value="" disabled>{{ locale === 'en' ? 'Select Legal Type' : 'የህግ መዋቅር ይምረጡ' }}</option>
                                    <option value="PLC">PLC (Private Limited Company)</option>
                                    <option value="Sole">Sole Proprietorship</option>
                                    <option value="Share">Share Company</option>
                                </select>
                            </div>
                            <button v-if="!lab3Passed" @click="validateLab3" 
                                    class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all"
                            >
                                {{ content[locale].lab3.validateBtn }}
                            </button>
                            <p v-if="lab3Error" class="text-xs font-bold text-rose-500">{{ lab3Error }}</p>
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
                                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5"
                            >
                                {{ locale === 'en' ? 'Next Lab' : 'ቀጣይ ላብ' }}
                                <ChevronRightIcon class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: SIMULATED OPERATIONS SANDBOX -->
                <div class="rounded-[32px] p-6 lg:p-8 border flex flex-col justify-center relative overflow-hidden"
                     :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-250 shadow-md'"
                >
                    
                    <!-- LAB 1 SANDBOX: TASK BOARD -->
                    <div v-if="currentStep === 1" class="space-y-6 w-full">
                        <div class="flex justify-between items-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ content[locale].lab1.panelTitle }}</span>
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
                            <div class="text-[10px] font-bold text-center uppercase tracking-wide py-1.5 rounded-lg"
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
                        <div class="space-y-4 pt-2">
                            <h4 class="text-xs font-black uppercase text-slate-500">{{ content[locale].lab1.boardTitle }}</h4>
                            <div class="grid grid-cols-2 gap-4">
                                
                                <!-- Pending Docs Column -->
                                <div class="p-3 rounded-2xl border space-y-3 min-h-[160px]" :class="isDark ? 'bg-slate-950/20 border-slate-800' : 'bg-slate-50 border-slate-200'">
                                    <span class="text-[10px] font-black uppercase text-blue-500 border-b pb-1 block">{{ content[locale].lab1.pendingCol }}</span>
                                    
                                    <!-- Zenith (Red Urgent) Card -->
                                    <div v-if="lab1TaskState === 'pending'"
                                         class="p-3 border border-l-4 border-l-rose-500 rounded-xl space-y-2 shadow-sm transition-all"
                                         :class="isDark ? 'bg-[#121926] border-slate-850' : 'bg-white border-slate-250'"
                                    >
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">Zenith PLC</span>
                                            <span class="text-[8px] font-black text-rose-500 uppercase">{{ content[locale].lab1.redBadge }}</span>
                                        </div>
                                        <p class="text-[10px]" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Value: 30 Pts. Audit file submission.</p>
                                        <button @click="lab1TaskState = 'review'" 
                                                class="w-full py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-[9px] font-black uppercase tracking-wider transition-all"
                                        >
                                            {{ content[locale].lab1.moveBtn }}
                                        </button>
                                    </div>

                                    <!-- Haddis Shop (Low Priority) Card -->
                                    <div class="p-3 border border-l-4 border-l-slate-400 rounded-xl space-y-2 shadow-sm transition-all"
                                         :class="isDark ? 'bg-[#121926] border-slate-850' : 'bg-white border-slate-250'"
                                    >
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">Haddis Shop</span>
                                            <span class="text-[8px] font-black text-slate-500 uppercase">{{ content[locale].lab1.yellowBadge }}</span>
                                        </div>
                                        <p class="text-[10px]" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Value: 15 Pts. Receipt logs.</p>
                                        
                                        <button v-if="!lab1Delegated" @click="lab1Delegated = true" 
                                                class="w-full py-1.5 bg-slate-500/10 hover:bg-slate-500/20 text-slate-400 hover:text-slate-200 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all"
                                        >
                                            {{ content[locale].lab1.delegateBtn }}
                                        </button>
                                        <span v-else class="block text-center py-1 bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 rounded-lg text-[8px] font-black uppercase tracking-wider">
                                            {{ content[locale].lab1.delegatedBadge }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Under Review Column -->
                                <div class="p-3 rounded-2xl border space-y-3 min-h-[160px]" :class="isDark ? 'bg-slate-950/20 border-slate-800' : 'bg-slate-50 border-slate-200'">
                                    <span class="text-[10px] font-black uppercase text-purple-500 border-b pb-1 block">{{ content[locale].lab1.reviewCol }}</span>
                                    
                                    <!-- Zenith (Review State) Card -->
                                    <div v-if="lab1TaskState === 'review'"
                                         class="p-3 border border-l-4 border-l-purple-500 rounded-xl space-y-2 shadow-sm transition-all animate-fade-in"
                                         :class="isDark ? 'bg-[#121926] border-slate-850' : 'bg-white border-slate-250'"
                                    >
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">Zenith PLC</span>
                                            <span class="text-[8px] font-black text-purple-400 uppercase">WAITING REVIEW</span>
                                        </div>
                                        <p class="text-[10px]" :class="isDark ? 'text-slate-450' : 'text-slate-500'">Manager is inspecting files...</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- LAB 2 SANDBOX: ERRAND TRIP TRACKER -->
                    <div v-if="currentStep === 2" class="space-y-6 w-full flex flex-col items-center justify-center">
                        <div class="w-full max-w-sm rounded-[32px] border-4 border-slate-800 overflow-hidden shadow-2xl relative"
                             :class="isDark ? 'bg-[#0f1524]' : 'bg-white'"
                        >
                            <!-- Mobile status bar -->
                            <div class="bg-slate-800 py-1.5 px-4 flex justify-between items-center text-[10px] font-bold text-slate-400">
                                <span>GGAA Mobile OS</span>
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>GPS connected</span>
                                </div>
                            </div>

                            <!-- Mobile Body -->
                            <div class="p-6 space-y-6">
                                <h4 class="text-xs font-black uppercase text-slate-500 tracking-wider text-center">{{ content[locale].lab2.mobileTitle }}</h4>
                                
                                <div class="border rounded-2xl p-4 space-y-4" :class="isDark ? 'bg-slate-950/40 border-slate-850' : 'bg-slate-50 border-slate-200'">
                                    <div class="flex items-center gap-3">
                                        <span class="h-10 w-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 shrink-0">🚗</span>
                                        <div>
                                            <h5 class="text-sm font-black" :class="isDark ? 'text-white' : 'text-slate-950'">{{ content[locale].lab2.errandTitle }}</h5>
                                            <p class="text-[10px] text-slate-500 font-medium">{{ content[locale].lab2.errandLoc }}</p>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5 border-t pt-3" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                        <span class="text-[9px] uppercase font-black text-slate-500">Status</span>
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-black uppercase"
                                                  :class="[
                                                      lab2State === 'ready' ? 'text-amber-500' :
                                                      lab2State === 'traveling' ? 'text-blue-500' : 'text-emerald-500'
                                                  ]"
                                            >
                                                {{ 
                                                    lab2State === 'ready' ? content[locale].lab2.stateReady :
                                                    lab2State === 'traveling' ? content[locale].lab2.stateTraveling :
                                                    lab2State === 'arrived' ? content[locale].lab2.stateArrived :
                                                    content[locale].lab2.stateDone
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Traveling progress bar -->
                                    <div v-if="lab2State === 'traveling'" class="space-y-1.5 animate-fade-in">
                                        <div class="w-full bg-slate-500/15 rounded-full h-2.5 overflow-hidden border" :class="isDark ? 'border-slate-800' : 'border-slate-250'">
                                            <div class="h-full bg-blue-500 rounded-full transition-all duration-300" :style="`width: ${travelProgress}%`"></div>
                                        </div>
                                        <span class="text-[9px] text-slate-500 font-bold block text-right">GPS: {{ travelProgress }}%</span>
                                    </div>

                                    <button v-if="lab2State === 'ready'" @click="startTravel" 
                                            class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all"
                                    >
                                        {{ content[locale].lab2.startBtn }}
                                    </button>

                                    <button v-if="lab2State === 'arrived'" @click="completeErrand" 
                                            class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all animate-bounce"
                                    >
                                        {{ content[locale].lab2.deliverBtn }}
                                    </button>

                                    <div v-if="lab2State === 'completed'" class="p-3 bg-emerald-500/15 border border-emerald-500/20 text-emerald-500 text-center rounded-xl text-[11px] font-black uppercase animate-pulse">
                                        {{ locale === 'en' ? 'Errand Log Succeeded!' : 'የጉዞ መዝገብ በተሳካ ሁኔታ ተጠናቋል!' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LAB 3 SANDBOX: TIN & REGISTRATION VERIFICATION -->
                    <div v-if="currentStep === 3" class="space-y-4 w-full flex flex-col justify-center">
                        <h4 class="text-xs font-black uppercase text-slate-500 tracking-wider pb-1 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            {{ locale === 'en' ? 'OFFICIAL REGISTRATION LICENSE (DOCUMENT SEARCH)' : 'ይፋዊ የንግድ ምዝገባ ምስክር ወረቀት' }}
                        </h4>

                        <!-- Certificate Mock UI -->
                        <div class="rounded-3xl border-4 p-6 space-y-4 shadow-lg select-none relative overflow-hidden"
                             :class="isDark 
                                 ? 'bg-slate-900 border-amber-500/20 text-slate-350 shadow-amber-500/5' 
                                 : 'bg-amber-50/50 border-amber-300 text-slate-800'"
                        >
                            <!-- Seal stamp -->
                            <div class="absolute -right-6 -bottom-6 w-32 h-32 rounded-full border-4 border-red-500/25 flex items-center justify-center rotate-12 font-black uppercase text-[10px] text-red-500/25 text-center p-2">
                                Ministry of Trade & Industry
                            </div>

                            <div class="text-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-amber-250'">
                                <h3 class="text-base font-extrabold uppercase font-outfit text-amber-500">Ministry of Trade</h3>
                                <p class="text-[9px] uppercase font-black text-slate-500">Federal Democratic Republic of Ethiopia</p>
                            </div>

                            <div class="space-y-2.5 text-xs font-semibold leading-relaxed">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">{{ locale === 'en' ? 'Business Name:' : 'የድርጅት ስም:' }}</span>
                                    <span class="font-extrabold" :class="isDark ? 'text-white' : 'text-slate-900'">Awash Coffee Exports PLC</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">TIN Number (10-Digit):</span>
                                    <span class="font-black font-mono text-indigo-500 bg-indigo-500/10 px-2 py-0.5 rounded">0928301840</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">{{ locale === 'en' ? 'Company Type:' : 'የህግ መዋቅር:' }}</span>
                                    <span class="font-bold text-amber-500">Private Limited Company (PLC)</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">{{ locale === 'en' ? 'Capital Registered:' : 'ካፒታል:' }}</span>
                                    <span>500,000.00 ETB</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- STEP 4: GRADUATION -->
            <div v-if="currentStep === 4" class="max-w-4xl mx-auto py-4 space-y-8 animate-fade-in relative z-10">
                <!-- Pure-CSS Confetti Particles -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden">
                    <div class="confetti-particle bg-indigo-500 top-0 left-[10%]"></div>
                    <div class="confetti-particle bg-purple-500 top-0 left-[30%]"></div>
                    <div class="confetti-particle bg-emerald-500 top-0 left-[50%]"></div>
                    <div class="confetti-particle bg-yellow-500 top-0 left-[70%]"></div>
                    <div class="confetti-particle bg-rose-500 top-0 left-[90%]"></div>
                </div>

                <div class="text-center space-y-4 max-w-2xl mx-auto">
                    <span class="inline-flex items-center gap-1 bg-emerald-500/10 border border-emerald-500/25 text-emerald-450 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest animate-pulse">
                        <SparklesIcon class="h-4 w-4" />
                        {{ content[locale].graduation.title }}
                    </span>
                    <h2 class="text-4xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                        {{ locale === 'en' ? 'You are Certified!' : 'የስልጠና ብቃት ማረጋገጫ ተሰጥቶዎታል!' }}
                    </h2>
                    <p class="text-base font-semibold" :class="isDark ? 'text-slate-400' : 'text-slate-600'">
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
                              class="w-full py-4 bg-indigo-650 hover:bg-indigo-700 text-white text-center font-black rounded-2xl text-xs uppercase tracking-wider transition-all block shadow-lg shadow-indigo-600/10 hover:-translate-y-0.5"
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
