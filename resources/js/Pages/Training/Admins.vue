<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    ShieldCheckIcon, AcademicCapIcon,
    ChevronLeftIcon, ChevronRightIcon,
    ArrowLeftIcon, SunIcon, MoonIcon,
    CheckCircleIcon, ExclamationTriangleIcon,
    LockClosedIcon, ArchiveBoxIcon, UserGroupIcon,
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
// LAB 1 STATE: CAPACITY ASSIGNER
// ==========================================
const assignTarget = ref(''); // 'abebe' or 'lydia'
const lab1Passed = computed(() => {
    return assignTarget.value === 'abebe';
});

// ==========================================
// LAB 2 STATE: CABINET LOCATOR
// ==========================================
const selectedCabinet = ref('');
const selectedShelf = ref('');
const lab2Error = ref('');
const lab2Passed = ref(false);

function recordArchive() {
    lab2Error.value = '';
    // Nile Import-Export starts with 'N'. Cabinets: 1 (A-G), 2 (H-N), 3 (O-Z)
    // So Cabinet 2 is correct. Shelf coordinate can be any valid selection.
    if (selectedCabinet.value === 'Cabinet 2' && selectedShelf.value) {
        lab2Passed.value = true;
    } else {
        if (selectedCabinet.value !== 'Cabinet 2') {
            if (locale.value === 'en') {
                lab2Error.value = "❌ Incorrect Cabinet. 'Nile' must go to Cabinet 2 (H-N based on alphabetical order).";
            } else {
                lab2Error.value = "❌ የተሳሳተ ካቢኔ። 'Nile' በፊደል ቅደም ተከተል መሰረት ወደ ካቢኔ 2 (H-N) መሄድ አለበት።";
            }
        } else {
            if (locale.value === 'en') {
                lab2Error.value = "❌ Please select a Shelf coordinate.";
            } else {
                lab2Error.value = "❌ እባክዎ የመደርደሪያ ቦታ (Shelf) ይምረጡ።";
            }
        }
    }
}

// ==========================================
// LAB 3 STATE: REVIEW & LOCK LEDGER
// ==========================================
const ledgerState = ref('mismatch'); // 'mismatch', 'rejected', 'corrected', 'locked'
const unlockToEdit = ref(false);

const lab3Passed = computed(() => {
    return ledgerState.value === 'locked';
});

function rejectLedger() {
    if (ledgerState.value === 'mismatch') {
        ledgerState.value = 'rejected';
        // Simulate accountant correcting the mismatch after 2.5 seconds
        setTimeout(() => {
            ledgerState.value = 'corrected';
        }, 2000);
    }
}

// Watch unlock switch
watch(unlockToEdit, (newVal) => {
    if (ledgerState.value === 'corrected' && newVal === true) {
        ledgerState.value = 'locked';
    } else if (ledgerState.value === 'locked' && newVal === false) {
        ledgerState.value = 'corrected';
    }
});

// ==========================================
// STATE SAVING ON GRADUATION
// ==========================================
watch(currentStep, (newStep) => {
    if (newStep === 4) {
        localStorage.setItem('training-completed-admins', 'true');
    }
});

// Bilingual content dictionary
const content = {
    en: {
        backBtn: "Back to Tracks",
        headerTitle: "Manager Academy",
        headerSubtitle: "Branch Operations Simulator",
        stepNames: ["Overview", "Lab 1: Assigner", "Lab 2: Archives", "Lab 3: Audit", "Graduation 🎓"],
        
        overview: {
            title: "Welcome to GGAA Manager Academy!",
            desc1: "As a branch manager, your job is to coordinate team workloads, organize physical client folders, and audit financial ledgers submitted by standard accountants. Mistakes in client capacity or tax data can disrupt branch compliance.",
            desc2: "This interactive simulator contains three practical lab challenges that verify your competence in workload allocation, archiving mapping, and ledger locking.",
            startBtn: "Start Manager Training",
        },
        lab1: {
            guideTitle: "Lab 1: Load Allocator",
            intro: "Senior accountants have a max capacity limit of 80 points. Standard junior accountants also have an 80 Pts limit. The system blocks managers from assigning work that exceeds an accountant's capacity.",
            instructionTitle: "Your Laboratory Objective:",
            inst1: "1. A new client 'Sheger PLC' (Complexity: 25 Pts) needs an accountant.",
            inst2: "2. Try assigning Sheger PLC to Lydia (Junior) and view the allocation block warning.",
            inst3: "3. Re-assign the client to Abebe (Senior) to balance the workload, then click 'Next Lab'.",
            widgetTitle: "Client Assignment Board",
            clientCard: "Incoming Client: Sheger PLC (25 Pts)",
            staffA: "Abebe (Senior) — Current Load: 50 / 80 Pts",
            staffL: "Lydia (Junior) — Current Load: 65 / 80 Pts",
            assignABtn: "Assign to Abebe",
            assignLBtn: "Assign to Lydia",
            errorMsg: "❌ Safety Blocked! Assigning 25 Pts to Lydia (65 Pts) would equal 90/80 Pts, violating her workload limit.",
            successMsg: "🎉 Success! Abebe has been assigned Sheger PLC. His load is now 75/80 Pts, which is busy but safe.",
        },
        lab2: {
            guideTitle: "Lab 2: Physical Archiving Map",
            intro: "We record coordinates (Cabinet and Shelf number) for physical paper files. The cabinets are structured alphabetically: Cabinet 1 (A-G), Cabinet 2 (H-N), and Cabinet 3 (O-Z).",
            instructionTitle: "Your Laboratory Objective:",
            inst1: "1. You have a folder for 'Nile Import-Export' to archive.",
            inst2: "2. Identify the correct alphabetical cabinet based on the name 'Nile'.",
            inst3: "3. Choose the cabinet and shelf coordinates in the form, then click 'Record Archive Location'.",
            panelTitle: "Archive Registry Form",
            cabinetLabel: "Select Cabinet:",
            shelfLabel: "Select Shelf Coordinate:",
            submitBtn: "Record Archive Location",
            successMsg: "🎉 Success! Nile folder has been archived in Cabinet 2 (H-N) on Shelf B-3. Search coordinate saved.",
        },
        lab3: {
            guideTitle: "Lab 3: Ledger Locking Switch",
            intro: "Reconciled ledger books must be verified by the manager before they are published. If you spot a mismatch between invoiced sales and cash register reports, you must reject the ledger for correction. Once corrected, lock the ledger.",
            instructionTitle: "Your Laboratory Objective:",
            inst1: "1. Inspect the draft ledger submitted for 'Abay Trading' on the right.",
            inst2: "2. Click 'Reject & Request Corrections' due to the cash register mismatch.",
            inst3: "3. Wait for the accountant's correction, then toggle the 'Lock-to-Edit' switch to lock it.",
            panelTitle: "Abay Trading Ledger Review",
            salesLabel: "Draft Invoiced Sales:",
            zReportLabel: "Z-Report Machine Scan:",
            warningAlert: "⚠️ Sales mismatch! Books out of sync by 2,000 ETB.",
            rejectBtn: "Reject & Request Corrections",
            waitMsg: "⏳ Requesting corrections from accountant...",
            correctedMsg: "✅ Mismatch resolved! Sales is updated to 242,000.00 ETB. Ready to lock.",
            lockSwitchLabel: "Unlock-to-Edit Mode",
            lockedBadge: "VERIFIED & LOCKED SECURELY",
            unlockedBadge: "UNLOCKED (Review Mode)",
            switchDesc: "Toggle on to lock the ledger for client viewing.",
            successMsg: "🎉 Success! The ledger has been audited, resolved, and locked secure. It is now live on the client portal.",
        },
        graduation: {
            title: "Congratulations, Branch Manager!",
            subtitle: "You have completed all three manager oversight practical labs.",
            badgeLabel: "Unlocked Profile Badge:",
            badgeTitle: "🛡️ Compliance Champion",
            badgeDesc: "Successfully verified branch operations and folder logs with 100% compliance.",
            certTitle: "BRANCH OPERATIONS MANAGER",
            certSubtitle: "GGAA Systems Leadership Academy",
            certBody: "This certifies that the holder has passed the rigorous operations simulator, demonstrated proficiency in capacity scheduling, GPS errand logging, and tax ID validation.",
            certDate: "Certified on: June 2026",
            certNameLabel: "Change Certificate Name:",
            homeBtn: "Return to Training Hub",
        }
    },
    am: {
        backBtn: "ወደ ስልጠናዎች ተመለስ",
        headerTitle: "የአስተዳዳሪዎች ማሰልጠኛ",
        headerSubtitle: "የቅርንጫፍ ስራዎች ማስመሰያ ገጽ",
        stepNames: ["ማጠቃለያ", "ላብ 1: ስራ ማደላደል", "ላብ 2: ማህደር", "ላብ 3: ኦዲት", "ምረቃ 🎓"],
        
        overview: {
            title: "እንኳን ወደ አስተዳዳሪዎች ስልጠና በደህና መጡ!",
            desc1: "እንደ ቅርንጫፍ ማናጀር፣ የእርስዎ ሃላፊነት የቡድንዎን የስራ ጫና ማደላደል፣ አካላዊ የደንበኞች ፋይሎችን ማደራጀት፣ እና በአካውንታንቶች የተሞሉ የሂሳብ መዛግብትን ማረጋገጥ ነው። በስራ ድልድል ወይም በታክስ ስሌት ላይ የሚሰሩ ስህተቶች የቅርንጫፍዎን አፈጻጸም ያበላሻሉ።",
            desc2: "ይህ ማስመሰያ አስተዳዳሪዎች ስራዎችን እንዴት በቀላሉ ማስተዳደር እንዳለባቸው በተግባር የሚያሳዩ 3 ላብራቶሪዎችን የያዘ ነው።",
            startBtn: "የአስተዳዳሪ ስልጠናውን ጀምር",
        },
        lab1: {
            guideTitle: "ላብ 1: የስራ ጫና ማደላደል",
            intro: "አካውንታንቶች ሊሸከሙት የሚችሉት ከፍተኛው የስራ ጫና ገደብ 80 ነጥብ ነው። ሲስተሙ ከአቅም በላይ ስራዎችን ለአካውንታንቶች እንዳይሰጡ በራስ-ሰር ይከላከላል።",
            instructionTitle: "የተግባር ልምምድ መመሪያዎች:",
            inst1: "1. አዲስ ደንበኛ 'ሸገር ኃ/የተ/የግ/ማ' (የስራ ክብደት፡ 25 ነጥብ) መጥቷል።",
            inst2: "2. ይህንን ስራ ለሊዲያ (ጁኒየር) ለመስጠት ይሞክሩ እና የጫና መከላከያ መልዕክቱን ይመልከቱ።",
            inst3: "3. ስራውን ለአበበ (ሲኒየር) በመስጠት በቅርንጫፉ ውስጥ ስራ ያደላድሉ፣ ከዚያ 'ቀጣይ ላብ' የሚለውን ይጫኑ።",
            widgetTitle: "ስራ ማደላደያ ሰሌዳ",
            clientCard: "አዲስ የመጣ ደንበኛ: Sheger PLC (25 ነጥብ)",
            staffA: "አበበ (ሲኒየር) — አሁን ያለበት ጫና፡ 50 / 80 ነጥብ",
            staffL: "ሊዲያ (ጁኒየር) — አሁን ያለባት ጫና፡ 65 / 80 ነጥብ",
            assignABtn: "ለአበበ ስጥ",
            assignLBtn: "ለሊዲያ ስጥ",
            errorMsg: "❌ ስራ ማደላደል አልተቻለም! 25 ነጥብ ለሊዲያ (65 ነጥብ) መስጠት አጠቃላይ ጫናዋን 90 ነጥብ ያደርገዋል ይህም ከገደቡ በላይ ነው።",
            successMsg: "🎉 እንኳን ደስ አለዎት! ስራው በተሳካ ሁኔታ ለአበበ ተሰጥቷል። አሁን አጠቃላይ ጫናው 75 ነጥብ (ጤናማ) ነው።",
        },
        lab2: {
            guideTitle: "ላብ 2: የሰነዶች ማስቀመጫ ካርታ",
            intro: "የደንበኞች አካላዊ ወረቀቶች የተቀመጡበትን ቦታ (Cabinet እና Shelf) መመዝገብ አለብን። ካቢኔዎቹ በፊደል ተከፋፍለዋል፡ ካቢኔ 1 (A-G)፣ ካቢኔ 2 (H-N)፣ እና ካቢኔ 3 (O-Z)።",
            instructionTitle: "የተግባር ልምምድ መመሪያዎች:",
            inst1: "1. 'Nile Import-Export' የሚል የደንበኛ ወረቀት ፋይል አለዎት።",
            inst2: "2. 'Nile' በሚለው ስም መሰረት የሚገባበትን ትክክለኛ ካቢኔ ይለዩ።",
            inst3: "3. በቅጹ ላይ ካቢኔውን እና መደርደሪያውን መርጠው 'የማህደር ቦታ መዝግብ' የሚለውን ይጫኑ።",
            panelTitle: "የማህደር ሰነድ ምዝገባ ቅጽ",
            cabinetLabel: "ካቢኔ ይምረጡ:",
            shelfLabel: "የመደርደሪያ ቦታ (Shelf) ይምረጡ:",
            submitBtn: "የማህደር ቦታ መዝግብ",
            successMsg: "🎉 እንኳን ደስ አለዎት! የNile ሰነድ በካቢኔ 2 (H-N) መደርደሪያ B-3 ላይ ተመዝግቧል።",
        },
        lab3: {
            guideTitle: "ላብ 3: ወርሃዊ ሂሳብ ማረጋገጥና መቆለፍ",
            intro: "አካውንታንቶች ወርሃዊ ሂሳብ ሲያጠናቅቁ ማናጀሩ ማጽደቅ አለበት። በደረሰኝ ሽያጭ እና ማሽን ሪፖርት መካከል ልዩነት ካዩ ሂሳቡን ውድቅ በማድረግ ማሳረም አለብዎት። ከተስተካከለ በኋላ ሂሳቡን መቆለፍ አለብዎት።",
            instructionTitle: "የተግባር ልምምድ መመሪያዎች:",
            inst1: "1. በቀኝ በኩል የቀረበውን የ'Abay Trading' ሂሳብ ይመልከቱ።",
            inst2: "2. በማሽኑ እና በደረሰኝ መካከል ስህተት ስላለ 'ሂሳቡን ውድቅ አድርግ' የሚለውን ይጫኑ።",
            inst3: "3. አካውንታንቱ አስተካክሎ እስኪመልስ ይጠብቁ፣ ከዚያ 'የመቆለፊያ መክፈቻ' ማብሪያውን ያብሩ።",
            panelTitle: "የ Abay Trading ወርሃዊ ሂሳብ ግምገማ",
            salesLabel: "በደረሰኝ የተመዘገበ ሽያጭ:",
            zReportLabel: "የማሽን ሪፖርት (Z-Report):",
            warningAlert: "⚠️ የሽያጭ ልዩነት አለ! በደረሰኝ እና በማሽን መካከል የ2,000 ብር ልዩነት ታይቷል።",
            rejectBtn: "ሂሳቡን ውድቅ አድርግና መልስ",
            waitMsg: "⏳ አካውንታንቱ ስህተቱን እያስተካከለ ነው...",
            correctedMsg: "✅ ስህተቱ ተስተካክሏል! ሽያጩ ወደ 242,000.00 ብር ተስተካክሏል። ለመቆለፍ ዝግጁ ነው።",
            lockSwitchLabel: "የመቆለፊያ ሁነታ",
            lockedBadge: "ሂሳቡ ጸድቆ በደህንነት ተቆልፏል",
            unlockedBadge: "መቆለፊያ ተከፍቷል (በግምገማ ላይ)",
            switchDesc: "ለደንበኛው እንዲታይ የጸደቀውን ሂሳብ ለመቆለፍ ማብሪያውን ይጫኑ።",
            successMsg: "🎉 እንኳን ደስ አለዎት! ሂሳቡ ተገምግሞ፣ ስህተቱ ታርሞ፣ እና በደህንነት ተቆልፏል። አሁን ለደንበኛው በፖርታሉ ላይ ይታያል።",
        },
        graduation: {
            title: "እንኳን ደስ አለዎት፣ ቅርንጫፍ ማናጀር!",
            subtitle: "ሁሉንም 3 የማናጀር ቁጥጥር ላብራቶሪዎችን በተሳካ ሁኔታ አጠናቀዋል።",
            badgeLabel: "ያገኙት የክብር ባጅ:",
            badgeTitle: "🛡️ አስተማማኝ ማናጀር",
            badgeDesc: "ቅርንጫፉን 100% በታማኝነት እና በተገዢነት የመራ።",
            certTitle: "የቅርንጫፍ ስራዎች ማናጀር ሰርተፊኬት",
            certSubtitle: "ጂጂኤኤ ሲስተምስ የአመራር አካዳሚ",
            certBody: "ይህ ሰርተፊኬት ባለቤቱ የማናጀር ብቃት መፈተሻ ማስመሰያዎችን ማለፉን፣ በስራ ድልድል፣ በፊደል ቅደም ተከተል ማህደር እና በኦዲት መዛግብት ማረጋገጥ ላይ ሙሉ ብቃት ማሳየቱን ያረጋግጣል።",
            certDate: "የተሰጠበት ቀን: ሰኔ 2026",
            certNameLabel: "በሰርተፊኬቱ ላይ ያለውን ስም ይቀይሩ:",
            homeBtn: "ወደ ስልጠናዎች ማእከል ተመለስ",
        }
    }
};
</script>

<template>
    <Head :title="`${content[locale].headerTitle} - GGAA Systems`" />

    <div class="min-h-screen font-sans flex flex-col justify-between overflow-x-hidden transition-colors duration-300 selection:bg-blue-600 selection:text-white"
         :class="isDark ? 'bg-[#060a12] text-slate-100' : 'bg-slate-50 text-slate-800'"
    >
        <!-- Background Blur Particles -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] blur-[150px] rounded-full transition-opacity duration-300"
                 :class="isDark ? 'bg-blue-600/10 opacity-100' : 'bg-blue-500/5 opacity-40'"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[35%] h-[35%] blur-[150px] rounded-full transition-opacity duration-300"
                 :class="isDark ? 'bg-indigo-600/10 opacity-100' : 'bg-indigo-500/5 opacity-40'"></div>
        </div>

        <!-- Top Navigation Bar -->
        <header class="w-full py-4 px-6 lg:px-12 flex justify-between items-center border-b backdrop-blur-md sticky top-0 z-50 transition-colors"
                :class="isDark ? 'border-slate-850 bg-[#060a12]/80' : 'border-slate-200 bg-white/80'"
        >
            <Link href="/training" class="flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-blue-500 transition-colors group">
                <ArrowLeftIcon class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                {{ content[locale].backBtn }}
            </Link>
            
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-650 flex items-center justify-center font-black font-outfit shadow-lg shadow-blue-500/20 text-white">
                    M
                </div>
                <div>
                    <span class="text-base font-black tracking-wider uppercase font-outfit" :class="isDark ? 'text-white' : 'text-slate-900'">
                        GGAA <span class="text-blue-500">Systems</span>
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
                            :class="locale === 'en' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'">EN</button>
                    <button @click="setLanguage('am')" class="px-2.5 py-1 rounded-lg text-[10px] font-bold transition-all"
                            :class="locale === 'am' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'">አማ</button>
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
                                ? 'bg-blue-650 text-white shadow-md' 
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
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/10 text-blue-400 text-xs font-black uppercase tracking-widest border border-blue-500/20">
                    <AcademicCapIcon class="h-5 w-5" />
                    {{ locale === 'en' ? 'Track 2: Managers & Branch Leadership Operations' : 'ክፍል 2፡ የአስተዳዳሪዎችና የቅርንጫፍ ማናጀሮች ስልጠና' }}
                </span>
                
                <h1 class="text-4xl sm:text-6xl font-black font-outfit tracking-tighter leading-tight"
                    :class="isDark ? 'text-white' : 'text-slate-950'"
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
                    <button @click="currentStep = 1" class="px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow-xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all flex items-center gap-2 text-sm uppercase tracking-wider font-outfit mx-auto">
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
                        <span class="text-xs font-black uppercase text-blue-400 tracking-widest block font-outfit">
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

                        <!-- LAB 2 DATA FORM -->
                        <div v-if="currentStep === 2" class="space-y-3 pt-2">
                            <div class="space-y-1">
                                <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].lab2.cabinetLabel }}</label>
                                <select v-model="selectedCabinet" 
                                        class="w-full bg-slate-500/10 border px-4 py-2.5 rounded-xl font-bold text-sm outline-none transition-all"
                                        :class="isDark ? 'border-slate-800 text-white focus:border-blue-500' : 'border-slate-250 text-slate-900 focus:border-blue-500'"
                                        :disabled="lab2Passed"
                                >
                                    <option value="" disabled>{{ locale === 'en' ? 'Select Cabinet' : 'ካቢኔ ይምረጡ' }}</option>
                                    <option value="Cabinet 1">Cabinet 1 (Alphabetical: A-G)</option>
                                    <option value="Cabinet 2">Cabinet 2 (Alphabetical: H-N)</option>
                                    <option value="Cabinet 3">Cabinet 3 (Alphabetical: O-Z)</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-black uppercase text-slate-500">{{ content[locale].lab2.shelfLabel }}</label>
                                <select v-model="selectedShelf" 
                                        class="w-full bg-slate-500/10 border px-4 py-2.5 rounded-xl font-bold text-sm outline-none transition-all"
                                        :class="isDark ? 'border-slate-800 text-white focus:border-blue-500' : 'border-slate-250 text-slate-900 focus:border-blue-500'"
                                        :disabled="lab2Passed"
                                >
                                    <option value="" disabled>{{ locale === 'en' ? 'Select Shelf Coordinates' : 'የመደርደሪያ ቦታ ይምረጡ' }}</option>
                                    <option value="Shelf B-1">Shelf B-1</option>
                                    <option value="Shelf B-2">Shelf B-2</option>
                                    <option value="Shelf B-3">Shelf B-3</option>
                                    <option value="Shelf B-4">Shelf B-4</option>
                                </select>
                            </div>
                            <button v-if="!lab2Passed" @click="recordArchive" 
                                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all"
                            >
                                {{ content[locale].lab2.submitBtn }}
                            </button>
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
                                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5"
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
                    
                    <!-- LAB 1 SANDBOX: CAPACITY ASSIGNER -->
                    <div v-if="currentStep === 1" class="space-y-6 w-full">
                        <div class="flex justify-between items-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ content[locale].lab1.widgetTitle }}</span>
                            <span class="text-xs font-bold" :class="isDark ? 'text-slate-450' : 'text-slate-700'">Safe Limit: 80 Pts</span>
                        </div>

                        <!-- Incoming Client Card -->
                        <div class="p-4 border border-l-4 border-l-blue-500 rounded-2xl space-y-2 shadow-sm"
                             :class="isDark ? 'bg-[#101726] border-slate-850' : 'bg-slate-50 border-slate-200'"
                        >
                            <h4 class="text-sm font-black" :class="isDark ? 'text-white' : 'text-slate-900'">
                                {{ content[locale].lab1.clientCard }}
                            </h4>
                        </div>

                        <!-- Staff 1 Abebe -->
                        <div class="p-4 border rounded-2xl space-y-3" :class="isDark ? 'bg-slate-950/20 border-slate-850' : 'bg-slate-50 border-slate-200'">
                            <div class="flex justify-between text-xs font-bold">
                                <span :class="isDark ? 'text-slate-350' : 'text-slate-700'">Abebe (Senior Accountant)</span>
                                <span :class="assignTarget === 'abebe' ? 'text-blue-500 font-extrabold' : 'text-slate-550'">
                                    {{ assignTarget === 'abebe' ? '75 / 80 Pts' : '50 / 80 Pts' }}
                                </span>
                            </div>
                            <div class="w-full bg-slate-500/15 rounded-full h-2.5 overflow-hidden border" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                                <div class="h-full bg-blue-500 rounded-full transition-all duration-500" :style="`width: ${assignTarget === 'abebe' ? 93 : 62}%`"></div>
                            </div>
                            <button v-if="assignTarget !== 'abebe'" @click="assignTarget = 'abebe'" 
                                    class="py-1.5 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                            >
                                {{ content[locale].lab1.assignABtn }}
                            </button>
                        </div>

                        <!-- Staff 2 Lydia -->
                        <div class="p-4 border rounded-2xl space-y-3" :class="isDark ? 'bg-slate-950/20 border-slate-850' : 'bg-slate-50 border-slate-200'">
                            <div class="flex justify-between text-xs font-bold">
                                <span :class="isDark ? 'text-slate-350' : 'text-slate-700'">Lydia (Junior Accountant)</span>
                                <span :class="assignTarget === 'lydia' ? 'text-rose-500 font-extrabold' : 'text-slate-550'">
                                    {{ assignTarget === 'lydia' ? '90 / 80 Pts' : '65 / 80 Pts' }}
                                </span>
                            </div>
                            <div class="w-full bg-slate-500/15 rounded-full h-2.5 overflow-hidden border" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                                <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" 
                                     :class="assignTarget === 'lydia' && 'bg-rose-500'" 
                                     :style="`width: ${assignTarget === 'lydia' ? 100 : 81}%`"
                                ></div>
                            </div>
                            <button v-if="assignTarget !== 'abebe'" @click="assignTarget = 'lydia'" 
                                    class="py-1.5 px-3 bg-slate-500/10 hover:bg-slate-500/20 text-slate-400 hover:text-slate-200 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                            >
                                {{ content[locale].lab1.assignLBtn }}
                            </button>
                        </div>

                        <!-- Feedback Alerts -->
                        <div v-if="assignTarget === 'lydia'" class="p-4 rounded-xl text-xs font-bold text-rose-500 bg-rose-500/10 border border-rose-500/20 animate-bounce">
                            {{ content[locale].lab1.errorMsg }}
                        </div>
                        <div v-if="assignTarget === 'abebe'" class="p-4 rounded-xl text-xs font-bold text-emerald-500 bg-emerald-500/10 border border-emerald-500/20">
                            {{ content[locale].lab1.successMsg }}
                        </div>
                    </div>

                    <!-- LAB 2 SANDBOX: ARCHIVING CABINET -->
                    <div v-if="currentStep === 2" class="space-y-6 w-full text-center">
                        <span class="h-16 w-16 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-3xl mx-auto">🗄️</span>
                        <div>
                            <span class="text-xs font-black uppercase text-slate-500">{{ locale === 'en' ? 'Target Folder Name' : 'የሚቀመጠው ወረቀት ስም' }}</span>
                            <h3 class="text-2xl font-black font-outfit" :class="isDark ? 'text-white' : 'text-slate-900'">Nile Import-Export</h3>
                        </div>

                        <!-- Cabinet grid representation -->
                        <div class="grid grid-cols-3 gap-3">
                            <div class="p-3 border rounded-xl text-center space-y-1" 
                                 :class="[
                                     selectedCabinet === 'Cabinet 1' ? 'border-blue-500 bg-blue-500/10' : 'border-slate-800',
                                     isDark ? 'bg-slate-950/20' : 'bg-slate-50'
                                 ]"
                            >
                                <span class="text-xs font-bold block">Cabinet 1</span>
                                <span class="text-[9px] uppercase font-black text-slate-500">Range: A - G</span>
                            </div>
                            <div class="p-3 border rounded-xl text-center space-y-1" 
                                 :class="[
                                     selectedCabinet === 'Cabinet 2' ? 'border-blue-500 bg-blue-500/10' : 'border-slate-850',
                                     isDark ? 'bg-slate-950/20' : 'bg-slate-50'
                                 ]"
                            >
                                <span class="text-xs font-bold block" :class="selectedCabinet === 'Cabinet 2' && 'text-blue-400'">Cabinet 2</span>
                                <span class="text-[9px] uppercase font-black text-slate-500">Range: H - N</span>
                            </div>
                            <div class="p-3 border rounded-xl text-center space-y-1" 
                                 :class="[
                                     selectedCabinet === 'Cabinet 3' ? 'border-blue-500 bg-blue-500/10' : 'border-slate-800',
                                     isDark ? 'bg-slate-950/20' : 'bg-slate-50'
                                 ]"
                            >
                                <span class="text-xs font-bold block">Cabinet 3</span>
                                <span class="text-[9px] uppercase font-black text-slate-500">Range: O - Z</span>
                            </div>
                        </div>

                        <div v-if="lab2Passed" class="p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 text-xs font-black uppercase rounded-xl animate-pulse">
                            {{ locale === 'en' ? 'Archived coordinate matching Nile!' : 'የማህደር አቀማመጥ ተመዝግቧል!' }}
                        </div>
                    </div>

                    <!-- LAB 3 SANDBOX: LEDGER REVIEW & LOCK -->
                    <div v-if="currentStep === 3" class="space-y-4 w-full">
                        <div class="flex justify-between items-center pb-2 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ content[locale].lab3.panelTitle }}</span>
                            <span class="px-2.5 py-0.5 bg-slate-500/10 text-slate-400 rounded text-[9px] font-bold">Meskeram 2026</span>
                        </div>

                        <!-- Ledger Details Table -->
                        <div class="space-y-3.5 text-xs font-medium">
                            <div class="flex justify-between border-b pb-2" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <span :class="isDark ? 'text-slate-400' : 'text-slate-650'">{{ content[locale].lab3.salesLabel }}</span>
                                <span class="font-bold text-sm" :class="isDark ? 'text-white' : 'text-slate-900'">
                                    240,000.00 ETB
                                </span>
                            </div>
                            
                            <div class="flex justify-between border-b pb-2" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                                <span :class="isDark ? 'text-slate-400' : 'text-slate-650'">{{ content[locale].lab3.zReportLabel }}</span>
                                <span class="font-bold text-sm text-indigo-400">
                                    242,000.00 ETB
                                </span>
                            </div>

                            <!-- Alert status boxes -->
                            <div v-if="ledgerState === 'mismatch'" class="p-3 bg-rose-500/10 border border-rose-500/20 text-rose-500 rounded-xl space-y-2">
                                <p class="font-bold text-[11px]">{{ content[locale].lab3.warningAlert }}</p>
                                <button @click="rejectLedger" 
                                        class="w-full py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                                >
                                    {{ content[locale].lab3.rejectBtn }}
                                </button>
                            </div>

                            <div v-if="ledgerState === 'rejected'" class="p-4 bg-slate-550/10 text-slate-400 border border-slate-800 text-center rounded-xl font-bold animate-pulse">
                                {{ content[locale].lab3.waitMsg }}
                            </div>

                            <div v-if="ledgerState === 'corrected' || ledgerState === 'locked'" class="space-y-4 pt-1 animate-fade-in">
                                <div class="p-3 bg-emerald-500/10 border border-emerald-500/25 text-emerald-450 text-xs font-semibold rounded-xl">
                                    {{ content[locale].lab3.correctedMsg }}
                                </div>

                                <!-- LEDGER LOCK TOGGLE SWITCH -->
                                <div class="p-4 border rounded-2xl space-y-3 shadow-inner"
                                     :class="isDark ? 'bg-slate-950/20 border-slate-850' : 'bg-slate-50 border-slate-200'"
                                >
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <span class="text-xs font-black uppercase" :class="isDark ? 'text-slate-350' : 'text-slate-800'">
                                                {{ content[locale].lab3.lockSwitchLabel }}
                                            </span>
                                            <p class="text-[9px] text-slate-500">{{ content[locale].lab3.switchDesc }}</p>
                                        </div>

                                        <!-- Simple HTML Toggle switch -->
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" v-model="unlockToEdit" class="sr-only peer">
                                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>

                                    <div class="flex items-center gap-1.5 justify-center py-2 rounded-xl text-[10px] font-black uppercase border"
                                         :class="[
                                             ledgerState === 'locked' 
                                                 ? 'text-emerald-500 bg-emerald-500/10 border-emerald-500/25 animate-pulse' 
                                                 : 'text-amber-500 bg-amber-500/10 border-amber-500/25'
                                         ]"
                                    >
                                        <LockClosedIcon v-if="ledgerState === 'locked'" class="h-4.5 w-4.5" />
                                        <span>{{ ledgerState === 'locked' ? content[locale].lab3.lockedBadge : content[locale].lab3.unlockedBadge }}</span>
                                    </div>
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
                    <div class="confetti-particle bg-blue-500 top-0 left-[10%]"></div>
                    <div class="confetti-particle bg-indigo-500 top-0 left-[30%]"></div>
                    <div class="confetti-particle bg-emerald-500 top-0 left-[50%]"></div>
                    <div class="confetti-particle bg-teal-500 top-0 left-[70%]"></div>
                    <div class="confetti-particle bg-purple-500 top-0 left-[90%]"></div>
                </div>

                <div class="text-center space-y-4 max-w-2xl mx-auto">
                    <span class="inline-flex items-center gap-1 bg-emerald-500/10 border border-emerald-500/25 text-emerald-450 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest animate-pulse">
                        <SparklesIcon class="h-4 w-4" />
                        {{ content[locale].graduation.title }}
                    </span>
                    <h2 class="text-4xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                        {{ locale === 'en' ? 'Manager Certified!' : 'የማናጀር ብቃት ማረጋገጫ ተሰጥቶዎታል!' }}
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
                                <span class="h-16 w-16 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-650 flex items-center justify-center text-3xl shadow-lg">🛡️</span>
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
                                   :class="isDark ? 'border-slate-800 text-white focus:border-blue-500' : 'border-slate-250 text-slate-900 focus:border-blue-500'"
                            >
                        </div>

                        <Link href="/training" 
                              class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white text-center font-black rounded-2xl text-xs uppercase tracking-wider transition-all block shadow-lg shadow-blue-600/10 hover:-translate-y-0.5"
                        >
                            {{ content[locale].graduation.homeBtn }}
                        </Link>
                    </div>

                    <!-- Right Column: Official Certificate Mockup -->
                    <div class="md:col-span-3">
                        <div class="rounded-[36px] border-8 p-8 relative overflow-hidden shadow-2xl text-center space-y-6"
                             :class="isDark 
                                 ? 'bg-[#0a0f1d] border-blue-500/30 text-slate-350 shadow-blue-500/5' 
                                 : 'bg-white border-blue-100 text-slate-700 shadow-xl'"
                        >
                            <!-- Seals & Decoration -->
                            <div class="absolute top-4 left-4 h-10 w-10 border-t-2 border-l-2 border-blue-500/20"></div>
                            <div class="absolute top-4 right-4 h-10 w-10 border-t-2 border-r-2 border-blue-500/20"></div>
                            <div class="absolute bottom-4 left-4 h-10 w-10 border-b-2 border-l-2 border-blue-500/20"></div>
                            <div class="absolute bottom-4 right-4 h-10 w-10 border-b-2 border-r-2 border-blue-500/20"></div>

                            <div class="space-y-2">
                                <span class="text-[9px] uppercase font-black text-blue-500 tracking-widest">Certificate of Leadership</span>
                                <h3 class="text-xl sm:text-2xl font-black font-outfit tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400 uppercase">
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
                                <span class="text-blue-400 font-extrabold">GGAA Audit Academy</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </main>

        <!-- Footer Operations Title -->
        <footer class="w-full py-4 border-t transition-colors text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest"
                :class="isDark ? 'border-slate-850 bg-[#060a12]' : 'border-slate-200 bg-slate-100'"
        >
            GGAA Systems Portal • leadership simulator
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
