<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    ShieldCheckIcon, AcademicCapIcon,
    ChevronLeftIcon, ChevronRightIcon,
    ArrowLeftIcon, SunIcon, MoonIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const isDark = ref(true);
const currentSlide = ref(1);
const totalSlides = 9;

// Interactive workload assigner state
const assignTarget = ref(''); // 'abebe' or 'lydia'

// Interactive locking state
const unlockToEdit = ref(false);

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
    { id: 1, text_en: 'Check the Branch scoreboard weekly to see how many tasks are completed on time.', text_am: 'የቅርንጫፉን ጠቅላላ የስራ አፈጻጸም በየሳምንቱ በመገምገም ስራዎች በሰዓቱ መጠናቀቃቸውን ያረጋግጡ።', checked: false },
    { id: 2, text_en: 'Move clients away from busy staff members (stress level over 90%) to balance work.', text_am: 'የስራ ጫና የበዛባቸውን ሰራተኞች (ከ90% በላይ የሆኑ) ስራዎችን ወደ ሌሎች በማስተላለፍ ስራ ያደላድሉ::', checked: false },
    { id: 3, text_en: 'Log physical coordinates (Shelf and Section) for every client folder we store.', text_am: 'ለእያንዳንዱ የምናስቀምጠው የደንበኛ ወረቀት መዝገብ ላይ ትክክለኛ የካቢኔ (Shelf) እና የሴክሽን ቦታ መዝግቡ::', checked: false },
    { id: 4, text_en: 'Double-check submitted monthly books and print the officially signed PDF reports.', text_am: 'የቀረቡትን ወርሃዊ የሂሳብ መዛግብቶች በጥንቃቄ መርምረው ካረጋገጡ በኋላ የፒዲኤፍ ሪፖርት ያትሙ::', checked: false }
]);

function toggleCheck(item) {
    item.checked = !item.checked;
}

// Simple and Friendly bilingual slide content
const slides = {
    en: {
        slide1: {
            badge: "Part 2: Admins & Managers Course",
            title: "Manager Oversight & Team Control",
            subtitle: "Welcome! This friendly guide is made for senior staff and managers. Learn how to register new clients, assign tasks safely, organize physical archives, and verify monthly financial records."
        },
        slide2: {
            badge: "Part 2: Branch Scoreboard",
            title: "Your Branch Dashboard at a Glance",
            subtitle: "The dashboard collects live numbers across your branch so you can see exactly how well your team is performing and where to help.",
            metric1: "Overall Compliance",
            metric1Desc: "Percentage of monthly books completed without mistakes.",
            metric2: "On-Time Completion",
            metric2Desc: "Proportion of tasks completed before their due dates.",
            panelTitle: "Branch Stats Overview",
            item1: "Onboarded Businesses",
            item2: "Staff Capacity Utilized",
            item3: "Pending Manager Audits",
            activeText: "142 Active Clients",
            capacityText: "62% Load (Healthy)",
            auditsText: "8 Files waiting"
        },
        slide3: {
            badge: "Part 3: Onboarding Clients",
            title: "Registering New Clients Easily",
            subtitle: "When you add a new business to the system, you save important settings that dictate how tax calculations and task lists are created:",
            p1: "📌 Tax IDs (TIN Mappings): Storing unique 10-digit Tax IDs to satisfy government e-filing systems.",
            p2: "🏢 Legal Classification: Registering the company as PLC, Sole Proprietorship, etc., which automatically sets up their tax forms.",
            p3: "💳 Linked Bank Accounts: Associating corporate bank accounts for easy bookkeeping reconciliation."
        },
        slide4: {
            badge: "Part 4: Workload Assigner",
            title: "Safe Work Allocation Console",
            subtitle: "GGAA uses an intelligent workload allocation system. You can see how much work your staff has in real-time, preventing employee burnout.",
            boxTitle: "Try It Yourself: Interactive Client Assigner",
            boxSub: "A new client Sheger PLC (Complexity: 25 Pts) needs an accountant. Choose who to assign it to:",
            assignA: "Assign Sheger to Abebe",
            assignL: "Assign Sheger to Lydia",
            aInitial: "Abebe (Senior) — Current Load: 50 / 80 Pts",
            lInitial: "Lydia (Junior) — Current Load: 65 / 80 Pts",
            aFinal: "Abebe — New Load: 75 / 80 Pts (93% - Busy but safe)",
            lFinal: "Lydia — New Load: 90 / 80 Pts (112% - OVERLOADED!)",
            successMsg: "✅ Abebe is busy, but the system successfully assigned Sheger to him!",
            errorMsg: "❌ Allocation Blocked! Assigning Sheger to Lydia would exceed her max workload limit (80 Pts)!",
            resetBtn: "🔄 Reset Assigner"
        },
        slide5: {
            badge: "Part 5: Digital & Physical Vaults",
            title: "Physical Folder & Digital Archiving",
            subtitle: "We track both digital scan uploads and physical paper folders to make sure no client documents are ever lost.",
            p1: "📁 Digital Scans: TIN certificates, business licenses, and tax booklets are saved directly in our secure cloud.",
            p2: "🗄️ Physical Archiving: We record the exact shelf and section for paper folders so you can find any physical file in less than 60 seconds."
        },
        slide6: {
            badge: "Part 6: Task Templates",
            title: "Creating Reusable Task Lists",
            subtitle: "Instead of creating a to-do list manually every month, Admins create easy Task Templates that automatically generate tasks for active clients.",
            p1: "📅 Due Date Offsets: Automatically calculates the due date relative to the month-end (e.g. 10 days offset means due on the 10th).",
            p2: "🔧 Auto-generation: Work tasks are created on autopilot whenever a client subscribes to accounting or tax services."
        },
        slide7: {
            badge: "Part 7: Safe Scopes",
            title: "Built-in Security: View Only What You Own",
            subtitle: "The platform has built-in security that automatically keeps client data safe without you needing to do any manual checks.",
            p1: "🔒 Branch Managers: Automatically see client data and files only within their specific branch.",
            p2: "🔒 Standard Employees: Restricted to view only their assigned client portfolios.",
            p3: "🔒 Clients: Only see their own verified financial ledgers and billing invoices."
        },
        slide8: {
            badge: "Part 8: Ledger Lock",
            title: "Reconciliation Auditing & Sign-Offs",
            subtitle: "When employees finish monthly client books, they submit them for your review. Senior managers can verify and sign off on these records.",
            p1: "👁️ Locking Mechanism: Once verified, records are locked automatically. The client can now see these verified books in their portal.",
            p2: "🔑 Unlock-to-Edit switch: If the client requests corrections, Admins can toggle this switch to unlock the files and make adjustments.",
            boxTitle: "Try It Yourself: Live Locking Switch",
            indicatorL: "UNLOCKED (Auditing Mode)",
            indicatorV: "VERIFIED (Locked Securely)",
            toggleLabel: "Unlock-to-Edit Mode",
            toggleDesc: "Unlocks the verified ledger for corrections",
            widgetTip: "💡 Toggle the switch above to experience the lock/unlock security engine!"
        },
        slide9: {
            badge: "Part 9: Graduation",
            title: "Your Daily Manager Checklist",
            subtitle: "Keep this simple checklist handy every day to maintain a highly productive and compliant branch:"
        }
    },
    am: {
        slide1: {
            badge: "ክፍል 2፡ የአስተዳዳሪዎችና ማናጀሮች ኮርስ",
            title: "የማናጀር ክትትል እና የቡድን ቁጥጥር",
            subtitle: "እንኳን ደህና መጡ! ይህ መመሪያ ለአስተዳዳሪዎች እና ለማናጀሮች የተዘጋጀ ነው። አዳዲስ ደንበኞችን እንዴት እንደሚመዘግቡ፣ ስራዎችን እንዴት እንደሚያደላድሉ፣ ወረቀቶችን እንደሚያስቀምጡ እና ወርሃዊ መዛግብትን እንደሚያጸድቁ ይማሩ።"
        },
        slide2: {
            badge: "ክፍል 2፡ የቅርንጫፍ የስራ ሰሌዳ",
            title: "የቅርንጫፍዎ አፈጻጸም በአንድ እይታ",
            subtitle: "ይህ ሰሌዳ ቡድንዎ ምን ያህል በጥሩ ሁኔታ እየሰራ እንደሆነ እና የትኛው ላይ ማገዝ እንዳለብዎ በቅጽበት ጠቅላላ መረጃ ይሰጥዎታል፡",
            metric1: "ጠቅላላ ተገዢነት",
            metric1Desc: "ያለ ምንም ስህተት ተረጋግጠው ያለፉ ወርሃዊ መዛግብት መቶኛ።",
            metric2: "ስራዎችን በሰዓቱ ማጠናቀቅ",
            metric2Desc: "ከቀነ ገደቡ በፊት ተጠናቀው የጸደቁ ስራዎች ጥምርታ።",
            panelTitle: "የቅርንጫፉ ጠቅላላ መረጃ",
            item1: "የተመዘገቡ ደንበኞች",
            item2: "የሰራተኞች ጠቅላላ ጫና",
            item3: "ለማናጀር የቀረቡ ኦዲቶች",
            activeText: "142 ንቁ ደንበኞች",
            capacityText: "62% ጫና (ጤናማ)",
            auditsText: "8 ፋይሎች ይጠበቃሉ"
        },
        slide3: {
            badge: "ክፍል 3፡ ደንበኞችን መመዝገብ",
            title: "ደንበኞችን በቀላሉ መመዝገብ",
            subtitle: "አዲስ ደንበኛ በሲስተሙ ላይ ሲመዘግቡ፣ የታክስ ስሌቶች እና የስራ ዝርዝሮች እንዴት መፈጠር እንዳለባቸው የሚወስኑ ቅንብሮችን ያስቀምጣሉ፡",
            p1: "📌 የግብር ከፋይ ቁጥር (TIN): ለመንግስት ታክስ ሪፖርት የሚጠቅም ባለ 10 አሃዝ የግብር ከፋይ መለያ መመዝገብ።",
            p2: "🏢 የህግ መዋቅር፡ ደንበኛውን እንደ PLC፣ የግል ድርጅት፣ ወዘተ. መመዝገብ ይህም የታክስ ፎርማቶችን በራሱ ይወስናል።",
            p3: "💳 የባንክ አካውንት ማያያዝ፡ ወርሃዊ ሂሳቦችን በቀላሉ ለማስታረቅ የድርጅቱን የባንክ ሂሳቦች ማገናኘት።"
        },
        slide4: {
            badge: "ክፍል 4፡ ስራ ማደላደያ ሰሌዳ",
            title: "ስራዎችን በፍትሃዊነት ማደላደል",
            subtitle: "ሰራተኞቻችን በስራ ብዛት እንዳይጨናነቁ እና ስራዎች እንዳይበላሹ ጂጂኤኤ የስራ ጫና መፈተሻ ዘዴ ይጠቀማል።",
            boxTitle: "እራስዎ ይሞክሩት፡ በይነተገናኝ ስራ ማደላደያ",
            boxSub: "አዲስ ደንበኛ ሸገር ኃ/የተ/የግ/ማ (የስራ ክብደት፡ 25 ነጥብ) መጥቷል። ይህንን ስራ ለማን ማደላደል ይፈልጋሉ፡",
            assignA: "ስራውን ለአበበ ስጥ",
            assignL: "ስራውን ለሊዲያ ስጥ",
            aInitial: "አበበ (ሲኒየር) — አሁን ያለበት ጫና፡ 50 / 80 ነጥብ",
            lInitial: "ሊዲያ (ጁኒየር) — አሁን ያለባት ጫና፡ 65 / 80 ነጥብ",
            aFinal: "አበበ — አዲስ የስራ ጫና፡ 75 / 80 ነጥብ (93% - ስራ በዝቷል ግን ጤናማ ነው)",
            lFinal: "ሊዲያ — አዲስ የስራ ጫና፡ 90 / 80 ነጥብ (112% - ከአቅም በላይ ተጭናለች!)",
            successMsg: "✅ ስራው በተሳካ ሁኔታ ለአበበ ተሰጥቷል! አበበ በስራ ቢጠመድም ሊያጠናቅቀው ይችላል።",
            errorMsg: "❌ ስራ ማደላደል አልተቻለም! ይህንን ስራ ለሊዲያ መስጠት ከእሷ ከፍተኛ የጫና ገደብ (80 ነጥብ) በላይ ያደርጋታል።",
            resetBtn: "🔄 ድጋሚ አስጀምር"
        },
        slide5: {
            badge: "ክፍል 5፡ ወረቀትና ዲጂታል ማህደር",
            title: "የዲጂታል እና አካላዊ ሰነዶች ማህደር",
            subtitle: "የደንበኞች ሰነዶች በፍጹም እንዳይጠፉ ዲጂታል ፋይሎችን እና አካላዊ ወረቀቶችን አጣምረን እንይዛለን።",
            p1: "📁 ዲጂታል ፋይሎች፡ የTIN ሰነዶች፣ የንግድ ፈቃዶች እና የታክስ መግለጫዎች ደህንነቱ በተጠበቀ የደመና ማከማቻ ውስጥ ይቀመጣሉ።",
            p2: "🗄️ አካላዊ ሰነዶች፡ ወረቀቶቹ የተቀመጡበትን ትክክለኛ የካቢኔ መደርደሪያ (Shelf) እና የሴክሽን ቦታ በመመዝገብ በ60 ሰከንድ ውስጥ ማግኘት እንዲቻል እናደርጋለን።"
        },
        slide6: {
            badge: "ክፍል 6፡ የስራ ናሙናዎች",
            title: "አውቶማቲክ የስራ ዝርዝሮችን መፍጠር",
            subtitle: "በየወሩ አዲስ የስራ ዝርዝር እራስዎ መፍጠር አይጠበቅብዎትም። አስተዳዳሪዎች አንድ ጊዜ የስራ ዝርዝር ናሙና ከፈጠሩ በኋላ ሲስተሙ እራሱ ስራዎችን ያመነጫል።",
            p1: "📅 የቀነ-ገደብ ስሌት፡ ከወሩ መጨረሻ ጀምሮ የቀናት ልዩነትን በመውሰድ የቀነ-ገደብ ቀንን በራሱ ያሰላል (ለምሳሌ ከወር መጨረሻ 10 ቀን ልዩነት)።",
            p2: "🔧 አውቶማቲክ ስራዎች፡ ደንበኞች ለታክስ ወይም ለሂሳብ አያያዝ አገልግሎት ሲመዘገቡ ስራዎች በራሳቸው ይፈጠራሉ።"
        },
        slide7: {
            badge: "ክፍል 7፡ ደህንነት እና ገደቦች",
            title: "የተገደበ መረጃ ደህንነት",
            subtitle: "ሲስተሙ የደንበኞችን መረጃ ደህንነት ለመጠበቅ እርስዎ እራስዎ መቆጣጠር ሳይጠበቅብዎት መረጃዎችን በራስ-ሰር ይገድባል።",
            p1: "🔒 የቅርንጫፍ ማናጀሮች፡ ማየት የሚችሉት በራሳቸው ቅርንጫፍ ስር ያሉ ደንበኞችን እና ፋይሎችን ብቻ ነው።",
            p2: "🔒 አካውንታንቶች፡ ማየት የሚችሉት ለእነሱ በግል የተሰጡ ደንበኞችን ብቻ ነው።",
            p3: "🔒 ደንበኞች፡ ማየት የሚችሉት የራሳቸውን የጸደቁ ሂሳቦች እና የክፍያ ሰነዶች ብቻ ነው።"
        },
        slide8: {
            badge: "ክፍል 8፡ የሂሳብ መቆለፊያ",
            title: "ወርሃዊ ሂሳቦችን መገምገም እና ማጽደቅ",
            subtitle: "አካውንታንቶች ወርሃዊ ሂሳቦችን ሲያጠናቅቁ ለግምገማ ይልኩልዎታል። ማናጀሮች ሂሳቦቹን ፈትሸው ያጸድቃሉ::",
            p1: "👁️ የማጽደቅ መቆለፊያ፡ ሂሳቡ ከጸደቀ በኋላ በራሱ ይቆለፋል። ደንበኞች ማየት የሚችሉት የጸደቁትን መዛግብቶች ብቻ ነው።",
            p2: "🔑 መቆለፊያ መክፈቻ፡ ደንበኛው ማስተካከያ ከጠየቀ፣ ማናጀሩ ይህንን ማብሪያ/ማጥፊያ በመጫን ሂሳቡን ለአካውንታንቱ መልሶ መክፈት ይችላል።",
            boxTitle: "እራስዎ ይሞክሩት፡ መቆለፊያ ማብሪያ/ማጥፊያ",
            indicatorL: "መቆለፊያ ተከፍቷል (ለማስተካከያ ዝግጁ)",
            indicatorV: "ጸድቋል (በደህንነት ተቆልፏል)",
            toggleLabel: "የመቆለፊያ መክፈቻ ሁነታ",
            toggleDesc: "የጸደቀውን ሂሳብ ለማስተካከያ መልሶ ይከፍታል",
            widgetTip: "💡 የመቆለፊያውን አሰራር ለመረዳት ከላይ ያለውን ማብሪያ/ማጥፊያ ይጫኑ!"
        },
        slide9: {
            badge: "ክፍል 9፡ ማጠቃለያ",
            title: "የማናጀሮች እለታዊ የስራ ዝርዝር",
            subtitle: "ቅርንጫፍዎ በታላቅ አፈጻጸም እንዲመራ በየቀኑ ይህንን ቀላል የስራ ዝርዝር ይመልከቱ፡"
        }
    }
};
</script>

<template>
    <Head :title="`${slides[locale].slide1.title} - GGAA Systems`" />

    <div class="min-h-screen font-sans flex flex-col justify-between overflow-x-hidden transition-colors duration-300 selection:bg-blue-600 selection:text-white"
         :class="isDark ? 'bg-[#060a12] text-slate-100' : 'bg-slate-50 text-slate-800'"
    >
        
        <!-- Top Navigation Bar -->
        <header class="w-full py-4 px-6 lg:px-12 flex justify-between items-center border-b backdrop-blur-md sticky top-0 z-50 transition-colors"
                :class="isDark ? 'border-slate-800 bg-[#060a12]/80' : 'border-slate-200 bg-white/80'"
        >
            <Link href="/training" class="flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-blue-500 transition-colors group">
                <ArrowLeftIcon class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                {{ locale === 'en' ? 'Back to Tracks' : 'ወደ ስልጠናዎች ተመለስ' }}
            </Link>
            
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-650 flex items-center justify-center font-black font-outfit shadow-lg shadow-blue-500/20 text-white">
                    M
                </div>
                <div>
                    <span class="text-base font-black tracking-wider uppercase font-outfit" :class="isDark ? 'text-white' : 'text-slate-900'">GGAA <span class="text-blue-500">Systems</span></span>
                    <span class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest leading-none">{{ locale === 'en' ? 'Admin & Manager Control Course' : 'የአስተዳዳሪዎች ማሰልጠኛ' }}</span>
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

        <!-- Slides Container (Smoother, larger fonts, conversational) -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 md:px-8 py-8 flex items-center justify-center relative">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] blur-[150px] rounded-full pointer-events-none transition-opacity duration-300"
                 :class="[isDark ? 'bg-blue-500/10 opacity-100' : 'bg-blue-500/5 opacity-40']"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[35%] h-[35%] blur-[150px] rounded-full pointer-events-none transition-opacity duration-300"
                 :class="[isDark ? 'bg-indigo-500/10 opacity-100' : 'bg-indigo-500/5 opacity-40']"></div>

            <!-- SLIDE 1: WELCOME & TITLE -->
            <div v-if="currentSlide === 1" class="w-full flex flex-col items-center justify-center text-center py-12 relative z-10 space-y-6 max-w-3xl animate-fade-in">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/10 text-blue-400 text-xs font-black uppercase tracking-widest border border-blue-500/20">
                    <span class="h-2 w-2 rounded-full bg-blue-400 animate-ping"></span>
                    {{ slides[locale].slide1.badge }}
                </span>
                
                <h1 class="text-5xl lg:text-7xl font-black font-outfit tracking-tighter leading-tight"
                    :class="isDark ? 'text-white' : 'text-slate-950'"
                >
                    {{ slides[locale].slide1.title }}
                </h1>
                
                <p class="text-xl md:text-2xl font-medium max-w-2xl mx-auto leading-relaxed"
                   :class="isDark ? 'text-slate-300' : 'text-slate-650'"
                >
                    {{ slides[locale].slide1.subtitle }}
                </p>

                <div class="pt-8 flex justify-center gap-4">
                    <button @click="nextSlide" class="px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow-xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all flex items-center gap-2 text-sm uppercase tracking-wider font-outfit">
                        {{ locale === 'en' ? 'Get Started' : 'ጀምር' }}
                        <ChevronRightIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- SLIDE 2: THE ADMIN DASHBOARD OVERVIEW -->
            <div v-if="currentSlide === 2" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10 animate-fade-in">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">{{ slides[locale].slide2.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-955'">
                        {{ slides[locale].slide2.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-600'">
                        {{ slides[locale].slide2.subtitle }}
                    </p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 rounded-2xl border transition-all"
                             :class="isDark ? 'bg-slate-900/50 border-slate-800' : 'bg-white border-slate-200 shadow-sm'"
                        >
                            <span class="text-xs text-blue-500 font-bold uppercase tracking-wider">{{ slides[locale].slide2.metric1 }}</span>
                            <h4 class="text-2xl font-black font-outfit mt-1" :class="isDark ? 'text-white' : 'text-slate-900'">94.8%</h4>
                            <p class="text-[11px] mt-1" :class="isDark ? 'text-slate-400' : 'text-slate-500'">{{ slides[locale].slide2.metric1Desc }}</p>
                        </div>

                        <div class="p-4 rounded-2xl border transition-all"
                             :class="isDark ? 'bg-slate-900/50 border-slate-800' : 'bg-white border-slate-200 shadow-sm'"
                        >
                            <span class="text-xs text-blue-500 font-bold uppercase tracking-wider">{{ slides[locale].slide2.metric2 }}</span>
                            <h4 class="text-2xl font-black font-outfit mt-1" :class="isDark ? 'text-white' : 'text-slate-900'">89.2%</h4>
                            <p class="text-[11px] mt-1" :class="isDark ? 'text-slate-400' : 'text-slate-500'">{{ slides[locale].slide2.metric2Desc }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm rounded-[32px] p-6 border space-y-4 shadow-xl"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800 text-slate-300' : 'bg-white border-slate-250 text-slate-700 shadow-lg'"
                    >
                        <h4 class="text-xs font-black uppercase tracking-widest block pb-2 border-b"
                            :class="isDark ? 'text-slate-400 border-slate-800' : 'text-slate-950 border-slate-200'"
                        >
                            {{ slides[locale].slide2.panelTitle }}
                        </h4>
                        
                        <div class="space-y-3 text-xs">
                            <div class="flex justify-between items-center p-3 rounded-xl border" :class="isDark ? 'bg-slate-900/40 border-slate-850' : 'bg-slate-50 border-slate-200'">
                                <span>{{ slides[locale].slide2.item1 }}</span>
                                <span class="font-bold text-sm" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide2.activeText }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center p-3 rounded-xl border" :class="isDark ? 'bg-slate-900/40 border-slate-850' : 'bg-slate-50 border-slate-200'">
                                <span>{{ slides[locale].slide2.item2 }}</span>
                                <span class="font-bold text-sm text-blue-500">{{ slides[locale].slide2.capacityText }}</span>
                            </div>

                            <div class="flex justify-between items-center p-3 rounded-xl border" :class="isDark ? 'bg-slate-900/40 border-slate-850' : 'bg-slate-50 border-slate-200'">
                                <span>{{ slides[locale].slide2.item3 }}</span>
                                <span class="font-bold text-sm text-amber-500">{{ slides[locale].slide2.auditsText }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 3: CLIENT ONBOARDING & PROFILES -->
            <div v-if="currentSlide === 3" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">{{ slides[locale].slide3.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide3.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide3.subtitle }}
                    </p>

                    <div class="space-y-4 text-sm" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                        <p class="font-medium">{{ slides[locale].slide3.p1 }}</p>
                        <p class="font-medium">{{ slides[locale].slide3.p2 }}</p>
                        <p class="font-medium">{{ slides[locale].slide3.p3 }}</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm rounded-[32px] p-6 border space-y-4 shadow-xl"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-250'"
                    >
                        <div class="flex justify-between items-center border-b pb-2.5" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <span class="text-[10px] bg-blue-500/10 text-blue-400 font-bold px-2.5 py-0.5 rounded uppercase">{{ slides[locale].slide3.badge }}</span>
                            <span class="text-xs" :class="isDark ? 'text-slate-450' : 'text-slate-500'">ID: #C-9024</span>
                        </div>

                        <div class="space-y-3.5 text-xs">
                            <div>
                                <span class="text-[10px] text-slate-500 block uppercase">{{ locale === 'en' ? 'Company Name' : 'የድርጅት ስም' }}</span>
                                <span class="font-black text-lg" :class="isDark ? 'text-white' : 'text-slate-900'">Sheger Foods PLC</span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-[10px] text-slate-500 block uppercase">TIN Number</span>
                                    <span class="font-bold text-sm" :class="isDark ? 'text-white' : 'text-slate-800'">0048291039</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-500 block uppercase">Legal Structure</span>
                                    <span class="font-bold text-sm" :class="isDark ? 'text-white' : 'text-slate-800'">PLC</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 4: WORKLOAD CAPACITY BALANCING (Highly Interactive drag-and-drop/click simulation) -->
            <div v-if="currentSlide === 4" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10 animate-fade-in">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">{{ slides[locale].slide4.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide4.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide4.subtitle }}
                    </p>

                    <!-- Workload Allocator Control -->
                    <div class="p-6 rounded-[28px] border space-y-4"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200 shadow-md'"
                    >
                        <h4 class="text-sm font-black uppercase tracking-wider text-slate-500">
                            {{ slides[locale].slide4.boxSub }}
                        </h4>
                        
                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <button @click="assignTarget = 'abebe'" 
                                    class="flex-1 py-3 px-4 font-bold rounded-xl text-xs uppercase tracking-wider transition-all"
                                    :class="assignTarget === 'abebe' 
                                        ? 'bg-blue-600 text-white shadow-md' 
                                        : 'bg-slate-500/10 text-slate-400 hover:bg-slate-500/20'"
                            >
                                {{ slides[locale].slide4.assignA }}
                            </button>
                            <button @click="assignTarget = 'lydia'" 
                                    class="flex-1 py-3 px-4 font-bold rounded-xl text-xs uppercase tracking-wider transition-all"
                                    :class="assignTarget === 'lydia' 
                                        ? 'bg-rose-600 text-white shadow-md' 
                                        : 'bg-slate-500/10 text-slate-400 hover:bg-slate-500/20'"
                            >
                                {{ slides[locale].slide4.assignL }}
                            </button>
                        </div>

                        <button v-if="assignTarget" @click="assignTarget = ''" 
                                class="w-full text-center text-xs font-bold text-slate-400 hover:text-white underline pt-1 block"
                        >
                            {{ slides[locale].slide4.resetBtn }}
                        </button>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <!-- Interactive allocation console mock-up -->
                    <div class="w-full max-w-sm rounded-[36px] p-6 border space-y-5 shadow-2xl transition-all"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-250'"
                    >
                        <h4 class="text-sm font-black uppercase text-center" :class="isDark ? 'text-slate-300' : 'text-slate-950'">
                            {{ slides[locale].slide4.boxTitle }}
                        </h4>

                        <!-- Staff 1 Abebe -->
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between items-end font-bold">
                                <span :class="isDark ? 'text-slate-200' : 'text-slate-800'">Abebe (Senior)</span>
                                <span :class="assignTarget === 'abebe' ? 'text-amber-500 font-black' : 'text-blue-500'">
                                    {{ assignTarget === 'abebe' ? slides[locale].slide4.aFinal : slides[locale].slide4.aInitial }}
                                </span>
                            </div>
                            <div class="w-full bg-slate-500/15 rounded-full h-3 overflow-hidden p-0.5 border" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                                <div class="h-full rounded-full transition-all duration-500 bg-blue-500" 
                                     :style="`width: ${assignTarget === 'abebe' ? 93 : 62}%`"
                                ></div>
                            </div>
                        </div>

                        <!-- Staff 2 Lydia -->
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between items-end font-bold">
                                <span :class="isDark ? 'text-slate-200' : 'text-slate-800'">Lydia (Junior)</span>
                                <span :class="assignTarget === 'lydia' ? 'text-rose-500 font-black' : 'text-emerald-500'">
                                    {{ assignTarget === 'lydia' ? slides[locale].slide4.lFinal : slides[locale].slide4.lInitial }}
                                </span>
                            </div>
                            <div class="w-full bg-slate-500/15 rounded-full h-3 overflow-hidden p-0.5 border" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                                <div class="h-full rounded-full transition-all duration-500 bg-emerald-500"
                                     :class="assignTarget === 'lydia' && 'bg-rose-500'"
                                     :style="`width: ${assignTarget === 'lydia' ? 100 : 81}%`"
                                ></div>
                            </div>
                        </div>

                        <!-- Allocation Feedback box -->
                        <div v-if="assignTarget" class="p-4 rounded-2xl text-xs font-bold transition-all duration-300"
                             :class="[
                                 assignTarget === 'abebe' 
                                     ? (isDark ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-amber-100 text-amber-800 border-amber-250') 
                                     : (isDark ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20 animate-bounce' : 'bg-rose-100 text-rose-800 border border-rose-250 animate-bounce')
                             ]"
                        >
                            {{ assignTarget === 'abebe' ? slides[locale].slide4.successMsg : slides[locale].slide4.errorMsg }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 5: DIGITAL & PHYSICAL VAULTS -->
            <div v-if="currentSlide === 5" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10 animate-fade-in">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">{{ slides[locale].slide5.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-955'">
                        {{ slides[locale].slide5.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-600'">
                        {{ slides[locale].slide5.subtitle }}
                    </p>

                    <div class="space-y-4">
                        <div class="flex gap-4 p-4 rounded-2xl" :class="isDark ? 'bg-slate-900/40 border border-slate-800' : 'bg-white border shadow-sm'">
                            <span class="text-2xl shrink-0">📁</span>
                            <div>
                                <h5 class="font-bold text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide5.p1 }}</h5>
                            </div>
                        </div>
                        
                        <div class="flex gap-4 p-4 rounded-2xl" :class="isDark ? 'bg-slate-900/40 border border-slate-800' : 'bg-white border shadow-sm'">
                            <span class="text-2xl shrink-0">🗄️</span>
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
                        <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ locale === 'en' ? 'Storage Coordinates' : 'የማህደር መረጃ' }}</h4>
                        
                        <div class="p-3.5 rounded-2xl border" :class="isDark ? 'bg-slate-900 border-slate-850' : 'bg-slate-50 border-slate-200'">
                            <span class="text-[9px] text-slate-500 block uppercase">{{ locale === 'en' ? 'Physical Folder Place' : 'የአካላዊ ሰነድ ማስቀመጫ' }}</span>
                            <div class="grid grid-cols-2 gap-4 text-center mt-2 font-bold">
                                <div class="p-2 rounded-xl bg-blue-500/10 text-blue-400">
                                    <span class="text-[8px] block uppercase">Cabinet</span>
                                    <span class="text-sm">Shelf A</span>
                                </div>
                                <div class="p-2 rounded-xl bg-blue-500/10 text-blue-400">
                                    <span class="text-[8px] block uppercase">Section</span>
                                    <span class="text-sm">Section 4</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 6: REUSABLE WORKFLOW TEMPLATES -->
            <div v-if="currentSlide === 6" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">{{ slides[locale].slide6.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide6.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide6.subtitle }}
                    </p>

                    <div class="space-y-4 text-sm" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                        <p class="font-medium">{{ slides[locale].slide6.p1 }}</p>
                        <p class="font-medium">{{ slides[locale].slide6.p2 }}</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm rounded-[32px] p-6 border space-y-4 shadow-xl"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200'"
                    >
                        <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ locale === 'en' ? 'Template Sample' : 'የናሙና የስራ ዝርዝር' }}</h4>
                        
                        <div class="p-4 rounded-2xl border space-y-2 text-xs" :class="isDark ? 'bg-slate-900 border-slate-850 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-700'">
                            <span class="font-black text-sm block" :class="isDark ? 'text-white' : 'text-slate-900'">Monthly VAT Filing</span>
                            <div class="flex justify-between border-t pt-2" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                                <span>Due Offset:</span>
                                <span class="font-bold">10 Days</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Task Load:</span>
                                <span class="font-bold text-blue-500">15 Pts</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 7: INVISIBLE SECURITY -->
            <div v-if="currentSlide === 7" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">{{ slides[locale].slide7.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide7.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide7.subtitle }}
                    </p>

                    <div class="space-y-4 text-sm" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                        <p class="font-medium">{{ slides[locale].slide7.p1 }}</p>
                        <p class="font-medium">{{ slides[locale].slide7.p2 }}</p>
                        <p class="font-medium">{{ slides[locale].slide7.p3 }}</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm rounded-[32px] p-6 border space-y-3.5 shadow-xl text-center text-xs"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200'"
                    >
                        <h4 class="font-black pb-2 border-b" :class="isDark ? 'text-slate-300 border-slate-800' : 'text-slate-900 border-slate-200'">{{ locale === 'en' ? 'Who Sees What' : 'የመረጃ እይታ ገደብ' }}</h4>
                        
                        <div class="p-3 bg-blue-500/10 border border-blue-500/25 text-blue-500 font-bold rounded-2xl">
                            {{ locale === 'en' ? 'Managers: Only their own branch' : 'ማናጀሮች፡ የራሳቸውን ቅርንጫፍ ብቻ' }}
                        </div>
                        <div class="p-3 bg-indigo-500/10 border border-indigo-500/25 text-indigo-500 font-bold rounded-2xl">
                            {{ locale === 'en' ? 'Accountants: Only assigned clients' : 'አካውንታንቶች፡ ለእነሱ የተሰጡ ደንበኞችን ብቻ' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 8: LEDGER VERIFICATIONS & AUDITING (Bilingual, clear, visual) -->
            <div v-if="currentSlide === 8" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10 animate-fade-in">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-blue-400 uppercase">{{ slides[locale].slide8.badge }}</span>
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
                    <div class="w-full max-w-sm rounded-[36px] p-6 border space-y-4 shadow-xl transition-all"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-250'"
                    >
                        <div class="flex justify-between items-center pb-2.5 border-b" :class="isDark ? 'border-slate-850' : 'border-slate-200'">
                            <span class="text-xs font-black" :class="isDark ? 'text-slate-300' : 'text-slate-800'">{{ slides[locale].slide8.boxTitle }}</span>
                            <span class="px-3 py-1.5 rounded-xl text-xs font-black border transition-all"
                                  :class="unlockToEdit 
                                      ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' 
                                      : 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'"
                            >
                                {{ unlockToEdit ? slides[locale].slide8.indicatorL : slides[locale].slide8.indicatorV }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center p-4 border rounded-2xl" :class="isDark ? 'bg-slate-900 border-slate-850' : 'bg-slate-50 border-slate-200 shadow-sm'">
                            <div>
                                <h5 class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide8.toggleLabel }}</h5>
                                <p class="text-[10px] mt-0.5" :class="isDark ? 'text-slate-500' : 'text-slate-400'">{{ slides[locale].slide8.toggleDesc }}</p>
                            </div>
                            
                            <div @click="unlockToEdit = !unlockToEdit" 
                                 class="w-12 h-7 rounded-full p-1 flex items-center cursor-pointer transition-colors duration-200"
                                 :class="unlockToEdit ? 'bg-blue-600 justify-end' : 'bg-slate-700 justify-start'"
                            >
                                <div class="h-5 w-5 bg-white rounded-full flex items-center justify-center text-[10px] text-blue-600 font-bold shadow-md">
                                    {{ unlockToEdit ? '🔓' : '🔒' }}
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-center text-blue-500 font-bold">{{ slides[locale].slide8.widgetTip }}</p>
                    </div>
                </div>
            </div>

            <!-- SLIDE 9: ADMIN CHECKLIST & NEXT STEPS -->
            <div v-if="currentSlide === 9" class="w-full flex flex-col items-center justify-center py-6 text-center relative z-10 space-y-4 max-w-2xl animate-fade-in">
                <span class="text-xs font-black tracking-widest text-blue-400 uppercase">{{ slides[locale].slide9.badge }}</span>
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
                        <input type="checkbox" :checked="item.checked" class="accent-blue-600 h-5 w-5 shrink-0 rounded mt-0.5 cursor-pointer">
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
                :class="isDark ? 'border-slate-800 bg-[#04060d]' : 'border-slate-200 bg-slate-100'"
        >
            <span class="text-xs font-bold" :class="isDark ? 'text-slate-500' : 'text-slate-650'">
                {{ locale === 'en' ? 'GGAA Admins Course' : 'ጂጂኤኤ የአስተዳዳሪዎች ስልጠና' }}
            </span>
            
            <div class="flex items-center gap-6">
                <button @click="prevSlide" class="p-2.5 rounded-xl border hover:scale-105 active:scale-95 transition-all text-slate-400 hover:text-blue-500"
                        :class="isDark ? 'border-slate-800 hover:bg-slate-900' : 'border-slate-200 hover:bg-white'"
                >
                    <ChevronLeftIcon class="h-4 w-4" />
                </button>
                
                <span class="text-sm font-bold tracking-wider font-outfit" :class="isDark ? 'text-slate-400' : 'text-slate-700'">
                    {{ locale === 'en' ? 'Slide' : 'ስላይድ' }} <span class="text-blue-500">{{ currentSlide }}</span> of <span>{{ totalSlides }}</span>
                </span>
                
                <button @click="nextSlide" class="p-2.5 rounded-xl border hover:scale-105 active:scale-95 transition-all text-slate-400 hover:text-blue-500"
                        :class="isDark ? 'border-slate-800 hover:bg-slate-900' : 'border-slate-200 hover:bg-white'"
                >
                    <ChevronRightIcon class="h-4 w-4" />
                </button>
            </div>
            
            <div class="text-[11px] uppercase font-black hidden md:block" :class="isDark ? 'text-slate-600' : 'text-slate-400'">
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
