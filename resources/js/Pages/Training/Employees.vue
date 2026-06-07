<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    UserIcon, AcademicCapIcon,
    ChevronLeftIcon, ChevronRightIcon,
    ArrowLeftIcon, SunIcon, MoonIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const isDark = ref(true);
const currentSlide = ref(1);
const totalSlides = 9;

// Interactive Stress Meter state
const hasClientA = ref(true);
const hasClientB = ref(false);
const hasErrand = ref(true);
const hasProject = ref(false);

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

// Interactive states inside slide mockup
const localDailyTasks = ref([
    { id: 1, title: 'Deliver Audit Document', location: 'Ministry of Revenues - Bole', status: 'in_progress', icon: '✉️' },
    { id: 2, title: 'Reconcile Awake Shop', location: 'Awash Bank Bole', status: 'pending', icon: '👤' }
]);

function advanceDailyStatus(task) {
    if (task.status === 'pending') task.status = 'in_progress';
    else if (task.status === 'in_progress') task.status = 'done';
}

const checklistItems = ref([
    { id: 1, text_en: 'Check your personal task board first thing in the morning.', text_am: 'ጠዋት ስራ ሲጀምሩ መጀመሪያ የግል ስራ ሰሌዳዎን ይፈትሹ።', checked: false },
    { id: 2, text_en: 'Mark any outdoor errand trips as "In Progress" when you leave.', text_am: 'ለስራ ወደ ውጭ ሲወጡ የጉዞ ሁኔታውን "በሂደት ላይ" ብለው ይመዝግቡ።', checked: false },
    { id: 3, text_en: 'Ensure your workload stress meter stays in the green zone.', text_am: 'የስራ ጭነትዎ ሁልጊዜ በአረንጓዴ (ጤናማ) ክልል ውስጥ መሆኑን ያረጋግጡ።', checked: false },
    { id: 4, text_en: 'Upload tax slips and receipts directly into the task window.', text_am: 'የታክስ ደረሰኞችን እና ሰነዶችን ቀጥታ በስራው ዝርዝር ሳጥን ውስጥ ይስቀሉ::', checked: false },
    { id: 5, text_en: 'Send completed monthly books to your manager for review.', text_am: 'ያጠናቀቁትን ወርሃዊ ሂሳብ ለማናጀርዎ ለግምገማ ይላኩ።', checked: false }
]);

function toggleCheck(item) {
    item.checked = !item.checked;
}

// Simple and Friendly bilingual slide content
const slides = {
    en: {
        slide1: {
            badge: "Part 1: Your Daily Dashboard",
            title: "Your Daily Work Made Easy",
            subtitle: "Welcome! We have built this simple guide to show you exactly how to use your new dashboard, manage your chores, and get work done easily with your team."
        },
        slide2: {
            badge: "Part 2: What You Will Do",
            title: "What You Will Do Every Day",
            subtitle: "As an accountant or assistant, your day is split into two simple parts to help keep our clients happy:",
            box1: "📊 1. Monthly Bookkeeping",
            box1Desc: "Collect sales logs and receipts from clients, make sure the numbers match, and send monthly summaries to your manager.",
            box2: "🚗 2. Errands & Team Projects",
            box2Desc: "Do cool stuff like going to the Ministry of Revenues, delivering physical folders, and working with other squad members on major projects.",
            kpiTitle: "What Managers Look For:",
            kpi1: "On-time work (completing tasks before deadlines)",
            kpi2: "Healthy stress levels (not taking on too much work)",
            kpi3: "Accurate calculations (zero typos)"
        },
        slide3: {
            badge: "Part 3: The Task Board",
            title: "Your Personal Task Board",
            subtitle: "Think of this as a simple, visual to-do list. Just drag your task cards from left to right as you work on them!",
            col1: "Pending Docs",
            col2: "To Do",
            col3: "Review",
            col4: "Done",
            tip: "💡 Note: You cannot drag cards to 'Done' yourself. A manager will sign off and move them to Done after checking your work!"
        },
        slide4: {
            badge: "Part 4: Stress Meter",
            title: "Keeping Your Workload Balanced",
            subtitle: "We care about your peace of mind! That's why we measure work in points, not by the number of clients you have.",
            boxTitle: "Try It Yourself: Interactive Stress Meter",
            boxSub: "Toggle the chores below to see how they impact your stress bar and stress levels in real-time:",
            c1: "Selam Trading PLC (Large Business) — 30 Pts",
            c2: "Coffee Exports (Medium Business) — 15 Pts",
            c3: "Ministry Errand (Outdoor trip) — 10 Pts",
            c4: "Squad Project Share (Team Audits) — 15 Pts",
            stressLabel: "Current Stress Level:",
            optimal: "😴 Too chill... Need some action!",
            happy: "🟢 Happy & Productive! The sweet spot.",
            busy: "🟡 Busy but focused. Keep going!",
            stressed: "🔴 Super Stressed! Overloaded! Call for help!"
        },
        slide5: {
            badge: "Part 5: At-Risk Flags",
            title: "Keeping an Eye on At-Risk Clients",
            subtitle: "The system has an auto-pilot flag engine. It puts warning labels on tasks that are late so you know what needs your attention first!",
            red: "🔴 Red Alert: High Risk",
            redDesc: "Tasks that are already past their due date, missing important tax files, or have immediate government deadlines.",
            yellow: "🟡 Yellow Alert: Medium Risk",
            yellowDesc: "Tasks due within two days, or when we have been waiting on a client's reply for more than 3 days."
        },
        slide6: {
            badge: "Part 6: Out-of-Office Errands",
            title: "Tracking Your Errands on Your Phone",
            subtitle: "Need to visit a tax office or drop off a physical paper? Use the Errand Log on your dashboard to track your outdoor trips easily on your mobile screen!",
            tip: "💡 Interactive Tip: Try clicking the status badges on the cards below to advance them from Pending ➔ In Progress ➔ Done!"
        },
        slide7: {
            badge: "Part 7: Team Projects",
            title: "Working Together in Squads",
            subtitle: "Large corporate clients are handled by dynamic squads. This makes work easier and more fun!",
            point1: "Leader Control: The squad leader helps guide the project and reviews everyone's drafts.",
            point2: "Shared Load: Project complexity points are split proportionally among squad members.",
            point3: "Live Team Chat: Chat with your squad and upload files directly in the project page."
        },
        slide8: {
            badge: "Part 8: Rewards & Badges",
            title: "Earn Fun Profile Badges!",
            subtitle: "We love celebrating great work! Completing your tasks on time and keeping accurate calculations unlocks cool badges for your public profile:",
            b1Title: "⚡ Speed Demon",
            b1Desc: "Completed 5 tasks before their deadlines",
            b2Title: "🛡️ Golden Auditor",
            b2Desc: "Zero verification errors from your manager",
            b3Title: "🤝 Super Partner",
            b3Desc: "Successfully helped on 3 squad audits"
        },
        slide9: {
            badge: "Part 9: Graduation",
            title: "Your Daily Checklist",
            subtitle: "Keep this simple checklist handy every day to make your work smooth, stress-free, and productive:"
        }
    },
    am: {
        slide1: {
            badge: "ክፍል 1፡ የእለታዊ ስራ ገጽ",
            title: "የእለት ተእለት ስራዎ በቀላሉ",
            subtitle: "እንኳን ደህና መጡ! የግል ስራ ሰሌዳዎን እንዴት እንደሚጠቀሙ፣ ስራዎችን እንደሚመዘግቡ፣ ከቡድንዎ ጋር አብረው እንደሚሰሩ እና ስራዎን በቀላሉ እንደሚጨርሱ የሚያሳይ ቀላል መመሪያ አዘጋጅተናል።"
        },
        slide2: {
            badge: "ክፍል 2፡ የእርስዎ ኃላፊነት",
            title: "በየቀኑ ምን እንደሚሰሩ",
            subtitle: "እንደ አካውንታንት ወይም ረዳት ሰራተኛ፣ ደንበኞቻችንን ደስተኛ ለማድረግ የእለት ስራዎ በሁለት ቀላል ክፍሎች ይከፈላል፡",
            box1: "📊 1. ወርሃዊ የሂሳብ መዛግብት",
            box1Desc: "ከደንበኞች የሽያጭ ሪፖርቶችን እና ደረሰኞችን መሰብሰብ፣ ቁጥሮቹ በትክክል መጣጣማቸውን ማረጋገጥ እና ወርሃዊ ማጠቃለያ ለሂሳብ ማናጀርዎ መላክ።",
            box2: "🚗 2. የውጭ ስራዎች እና የቡድን ፕሮጀክቶች",
            box2Desc: "ወደ ታክስ ባለስልጣን (ገቢዎች) መሄድ፣ ወረቀቶችን ማድረስ እና ከሌሎች የቡድን አባላት ጋር በትላልቅ የደንበኞች ፕሮጀክቶች ላይ አብሮ መስራት።",
            kpiTitle: "ማናጀሮች ትኩረት የሚሰጡባቸው ነጥቦች፡",
            kpi1: "ስራን በሰዓቱ ማጠናቀቅ (ከቀነ ገደቡ በፊት መጨረስ)",
            kpi2: "ከጭንቀት ነጻ መሆን (ከአቅም በላይ ስራ አለመደራረብ)",
            kpi3: "ቁጥሮችን በጥንቃቄ መሙላት (ስህተቶችን ማስወገድ)"
        },
        slide3: {
            badge: "ክፍል 3፡ የስራ ሰሌዳው",
            title: "የግል ስራ ሰሌዳዎ (Kanban)",
            subtitle: "ይህንን እንደ ቀላል የቤት ስራ ሰሌዳ ይቁጠሩት። ስራዎችን ሲሰሩ ካርዶቹን ከግራ ወደ ቀኝ መሳብ ብቻ ነው!",
            col1: "ሰነድ የሚጠበቅባቸው",
            col2: "የሚሰሩ ስራዎች",
            col3: "ለግምገማ",
            col4: "የተጠናቀቁ",
            tip: "💡 ማስታወሻ፡ ካርዶችን እራስዎ ወደ 'የተጠናቀቁ' መሳብ አይችሉም። ማናጀርዎ ስራዎን ፈትሾ ሲያጸድቅ እራሱ ያንቀሳቅሰዋል።"
        },
        slide4: {
            badge: "ክፍል 4፡ የስራ ጫና መለኪያ",
            title: "ከአቅምዎ በላይ እንዳይጫኑ መጠንቀቅ",
            subtitle: "እርስዎ እንዲጨነቁ አንፈልግም! ለዚህም ነው ስራዎችን የምንለካው በደንበኞች ብዛት ሳይሆን በስራ ውስብስብነት ነጥብ ነው።",
            boxTitle: "እራስዎ ይሞክሩት፡ የስራ ጫና መለኪያ",
            boxSub: "የስራ ጫናዎ እና የጭንቀት መጠንዎ እንዴት እንደሚቀያየር ከታች ያሉትን ስራዎች በማብራት እና በማጥፋት ይሞክሩ፡",
            c1: "ሰላም ንግድ ኃ/የተ/የግ/ማ (ትልቅ ስራ) — 30 ነጥብ",
            c2: "ቡና ላኪ ድርጅት (መካከለኛ ስራ) — 15 ነጥብ",
            c3: "የገቢዎች ውጭ ስራ (አጭር ጉዞ) — 10 ነጥብ",
            c4: "የቡድን ስራ ድርሻ (አጋር ኦዲት) — 15 ነጥብ",
            stressLabel: "የስራ ጭንቀት ሁኔታዎ፡",
            optimal: "😴 በጣም ቀዝቃዛ... ስራ ይጨመርልኝ!",
            happy: "🟢 ደስተኛ እና ንቁ! ትክክለኛው የስራ መጠን።",
            busy: "🟡 ስራ በዝቶብኛል ግን ትኩረት አድርጌያለሁ።",
            stressed: "🔴 በጣም ተጨንቄያለሁ! ከአቅም በላይ ነው! እርዳታ እፈልጋለሁ!"
        },
        slide5: {
            badge: "ክፍል 5፡ የአደጋ ቀይ ባንዲራዎች",
            title: "አደጋ ላይ ያሉ ደንበኞችን መከታተል",
            subtitle: "ሲስተሙ አውቶማቲክ የአደጋ መፈተሻ አለው። የቀነ ገደባቸው ያለፈባቸውን ስራዎች በቀለም በመለየት የትኛውን ቀድመው መስራት እንዳለብዎ ያሳይዎታል!",
            red: "🔴 ቀይ ባንዲራ፡ ከፍተኛ አደጋ",
            redDesc: "ቀነ ገደባቸው ያለፈባቸው፣ የታክስ ሰነድ የጎደላቸው ወይም አስቸኳይ የመንግስት ታክስ ሪፖርት የሚገባቸው ስራዎች።",
            yellow: "🟡 ቢጫ ባንዲራ፡ መካከለኛ አደጋ",
            yellowDesc: "በሁለት ቀናት ውስጥ መቅረብ ያለባቸው ወይም ከደንበኛው ምላሽ ለመቀበል ከ3 ቀናት በላይ የዘገዩ ስራዎች።"
        },
        slide6: {
            badge: "ክፍል 6፡ የውጭ ስራዎች መመዝገቢያ",
            title: "የውጭ ስራዎችን በስልክዎ መከታተል",
            subtitle: "ወደ መንግስት ታክስ ቢሮ መሄድ አለብዎት ወይስ ወረቀት ማድረስ? የእለት ስራዎን በቀላሉ በስልክዎ መከታተል እንዲችሉ በእለታዊ የጉዞ መዝገብ ላይ ይመዝግቡት!",
            tip: "💡 በይነተገናኝ ምክር፡ ከታች ያሉትን ካርዶች ሁኔታ ለመቀየር ባጆቹን ይጫኑ (የሚጠበቅ ➔ በሂደት ላይ ➔ ተጠናቀቀ)!"
        },
        slide7: {
            badge: "ክፍል 7፡ የቡድን ስራዎች",
            title: "በቡድን አብሮ መስራት",
            subtitle: "ለትላልቅ ደንበኞች ስራውን ቀለል እና አዝናኝ ለማድረግ ሰራተኞች በቡድን ተደራጅተው ይሰራሉ!",
            point1: "የቡድን መሪ ኃላፊነት፡ መሪው ስራዎችን ያስተባብራል፣ ሪፖርቶችን ለManager ከመላኩ በፊት ይገመግማል።",
            point2: "የተጋራ የስራ ጫና፡ የፕሮጀክቱ ጠቅላላ ነጥቦች እንደ ስራው ድርሻ ለቡድን አባላቱ ይከፋፈላሉ።",
            point3: "የቡድን ውይይት፡ በፕሮጀክቱ ገጽ ላይ ከቡድንዎ ጋር ይወያዩ፣ ፋይሎችንም በቀጥታ ይለዋወጡ።"
        },
        slide8: {
            badge: "ክፍል 8፡ ባጆችና ሽልማቶች",
            title: "የሽልማት ባጆችን ያግኙ!",
            subtitle: "ለጥሩ ስራ እውቅና እንሰጣለን! ስራዎችን በሰዓቱ ሲያጠናቅቁ እና ትክክለኛ መረጃ ሲያስገቡ በፕሮፋይልዎ ላይ የሚያማምሩ ባጆችን ያገኛሉ፡",
            b1Title: "⚡ የፍጥነት ንጉስ",
            b1Desc: "5 ስራዎችን ከቀነ ገደቡ በፊት ያጠናቀቀ",
            b2Title: "🛡️ አስተማማኝ አካውንታንት",
            b2Desc: "አንድም ስህተት የሌለበት ሪፖርት ያቀረበ",
            b3Title: "🤝 የቡድን ምሶሶ",
            b3Desc: "ለ3 የቡድን ፕሮጀክቶች መሳካት ከፍተኛ እገዛ ያደረገ"
        },
        slide9: {
            badge: "ክፍል 9፡ ማጠቃለያ",
            title: "ቀላል የእለት ስራዎች ማጠቃለያ",
            subtitle: "በየቀኑ ስራዎ የተሳካ እና ከጭንቀት ነጻ እንዲሆን ይህንን አጭር የስራ ዝርዝር ይመልከቱ፡"
        }
    }
};
</script>

<template>
    <Head :title="`${slides[locale].slide1.title} - GGAA Systems`" />

    <div class="min-h-screen font-sans flex flex-col justify-between overflow-x-hidden transition-colors duration-300 selection:bg-indigo-600 selection:text-white"
         :class="isDark ? 'bg-[#090d16] text-slate-100' : 'bg-slate-50 text-slate-800'"
    >
        
        <!-- Top Navigation Bar -->
        <header class="w-full py-4 px-6 lg:px-12 flex justify-between items-center border-b backdrop-blur-md sticky top-0 z-50 transition-colors"
                :class="isDark ? 'border-slate-800 bg-[#090d16]/80' : 'border-slate-200 bg-white/80'"
        >
            <Link href="/training" class="flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-indigo-500 transition-colors group">
                <ArrowLeftIcon class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                {{ locale === 'en' ? 'Back to Tracks' : 'ወደ ስልጠናዎች ተመለስ' }}
            </Link>
            
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-black font-outfit shadow-lg shadow-indigo-500/20 text-white">
                    G
                </div>
                <div>
                    <span class="text-base font-black tracking-wider uppercase font-outfit" :class="isDark ? 'text-white' : 'text-slate-900'">GGAA <span class="text-indigo-500">Systems</span></span>
                    <span class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest leading-none">{{ locale === 'en' ? 'Employee Workspace Guide' : 'የሰራተኛ የስራ መመሪያ' }}</span>
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

        <!-- Slides Container (Smoother, larger fonts, friendly) -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 md:px-8 py-8 flex items-center justify-center relative">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] blur-[150px] rounded-full pointer-events-none transition-opacity duration-300"
                 :class="[isDark ? 'bg-indigo-500/10 opacity-100' : 'bg-indigo-500/5 opacity-40']"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[35%] h-[35%] blur-[150px] rounded-full pointer-events-none transition-opacity duration-300"
                 :class="[isDark ? 'bg-purple-500/10 opacity-100' : 'bg-purple-500/5 opacity-40']"></div>

            <!-- SLIDE 1: WELCOME & TITLE -->
            <div v-if="currentSlide === 1" class="w-full flex flex-col items-center justify-center text-center py-12 relative z-10 space-y-6 max-w-3xl animate-fade-in">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-500/10 text-indigo-400 text-xs font-black uppercase tracking-widest border border-indigo-500/20">
                    <span class="h-2 w-2 rounded-full bg-indigo-400 animate-ping"></span>
                    {{ slides[locale].slide1.badge }}
                </span>
                
                <h1 class="text-5xl lg:text-7xl font-black font-outfit tracking-tighter leading-tight"
                    :class="isDark ? 'text-white' : 'text-slate-950'"
                >
                    {{ slides[locale].slide1.title }}
                </h1>
                
                <p class="text-xl md:text-2xl font-medium max-w-2xl mx-auto leading-relaxed"
                   :class="isDark ? 'text-slate-300' : 'text-slate-600'"
                >
                    {{ slides[locale].slide1.subtitle }}
                </p>

                <div class="pt-8 flex justify-center gap-4">
                    <button @click="nextSlide" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow-xl shadow-indigo-600/30 hover:scale-105 active:scale-95 transition-all flex items-center gap-2 text-sm uppercase tracking-wider font-outfit">
                        {{ locale === 'en' ? 'Get Started' : 'ጀምር' }}
                        <ChevronRightIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- SLIDE 2: ROLE OVERVIEW (Less professional, friendly) -->
            <div v-if="currentSlide === 2" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">{{ slides[locale].slide2.badge }}</span>
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
                            <div class="h-10 w-10 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 font-bold shrink-0">1</div>
                            <div>
                                <h4 class="font-bold text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide2.box1 }}</h4>
                                <p class="text-sm mt-1" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ slides[locale].slide2.box1Desc }}</p>
                            </div>
                        </div>
                        
                        <div class="flex gap-4 p-4 rounded-3xl border transition-colors"
                             :class="isDark ? 'bg-slate-900/50 border-slate-800' : 'bg-white border-slate-200 shadow-sm'"
                        >
                            <div class="h-10 w-10 rounded-2xl bg-purple-500/10 flex items-center justify-center text-purple-400 font-bold shrink-0">2</div>
                            <div>
                                <h4 class="font-bold text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide2.box2 }}</h4>
                                <p class="text-sm mt-1" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ slides[locale].slide2.box2Desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="shadow-2xl rounded-[32px] p-8 w-full max-w-md border space-y-4"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800 text-slate-300' : 'bg-white border-slate-250 text-slate-700 shadow-lg'"
                    >
                        <h3 class="text-lg font-black uppercase tracking-wider pb-2 border-b"
                            :class="isDark ? 'text-slate-400 border-slate-800' : 'text-slate-900 border-slate-200'"
                        >
                            {{ slides[locale].slide2.kpiTitle }}
                        </h3>
                        <ul class="space-y-3.5 text-sm">
                            <li class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                <span>{{ slides[locale].slide2.kpi1 }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                <span>{{ slides[locale].slide2.kpi2 }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                <span>{{ slides[locale].slide2.kpi3 }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- SLIDE 3: KANBAN WORKSPACE (SNAPSHOT MOCK-UP) -->
            <div v-if="currentSlide === 3" class="w-full flex flex-col py-2 relative z-10">
                <div class="text-center max-w-2xl mx-auto mb-6 space-y-1">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">{{ slides[locale].slide3.badge }}</span>
                    <h2 class="text-3xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide3.title }}
                    </h2>
                    <p class="text-base font-medium" :class="isDark ? 'text-slate-400' : 'text-slate-600'">
                        {{ slides[locale].slide3.subtitle }}
                    </p>
                </div>

                <div class="w-full rounded-3xl border overflow-hidden shadow-2xl p-6 space-y-4"
                     :class="isDark ? 'bg-[#0c1221] border-slate-800' : 'bg-white border-slate-200'"
                >
                    <div class="flex justify-between items-center pb-3 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                        <span class="text-sm font-bold" :class="isDark ? 'text-slate-350' : 'text-slate-800'">
                            {{ locale === 'en' ? 'Employee Board' : 'የሰራተኛው የስራ ሰሌዳ' }}: <strong>Zerihun Abebe</strong>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Col 1 -->
                        <div class="rounded-2xl p-3 border space-y-2.5" :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-slate-50 border-slate-200'">
                            <span class="text-xs font-black uppercase text-amber-500 block border-b pb-1" :class="isDark ? 'border-amber-500/20' : 'border-amber-500/10'">
                                {{ slides[locale].slide3.col1 }}
                            </span>
                            <div class="rounded-xl p-3 border border-l-4 border-l-amber-500 space-y-1" :class="isDark ? 'bg-[#121a2e] border-slate-800' : 'bg-white border-slate-250 shadow-sm'">
                                <h4 class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">Zenith PLC</h4>
                                <p class="text-xs" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ locale === 'en' ? 'Missing TIN Scan' : 'የግብር ቁጥር ስካን የለም' }}</p>
                            </div>
                        </div>

                        <!-- Col 2 -->
                        <div class="rounded-2xl p-3 border space-y-2.5" :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-slate-50 border-slate-200'">
                            <span class="text-xs font-black uppercase text-blue-500 block border-b pb-1" :class="isDark ? 'border-blue-500/20' : 'border-blue-500/10'">
                                {{ slides[locale].slide3.col2 }}
                            </span>
                            <div class="rounded-xl p-3 border border-l-4 border-l-emerald-500 space-y-1" :class="isDark ? 'bg-[#121a2e] border-slate-800' : 'bg-white border-slate-250 shadow-sm'">
                                <h4 class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">Alpha Traders</h4>
                                <p class="text-xs" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ locale === 'en' ? 'Collect Bank receipts' : 'የባንክ ደረሰኞች መሰብሰብ' }}</p>
                            </div>
                        </div>

                        <!-- Col 3 -->
                        <div class="rounded-2xl p-3 border space-y-2.5" :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-slate-50 border-slate-200'">
                            <span class="text-xs font-black uppercase text-purple-500 block border-b pb-1" :class="isDark ? 'border-purple-500/20' : 'border-purple-500/10'">
                                {{ slides[locale].slide3.col3 }}
                            </span>
                            <div class="rounded-xl p-3 border border-l-4 border-l-purple-500 space-y-1" :class="isDark ? 'bg-[#121a2e] border-slate-800' : 'bg-white border-slate-250 shadow-sm'">
                                <h4 class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">Nile Import-Export</h4>
                                <p class="text-xs" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ locale === 'en' ? 'Audit Finished' : 'የኦዲት ስራው አልቋል' }}</p>
                            </div>
                        </div>

                        <!-- Col 4 -->
                        <div class="rounded-2xl p-3 border space-y-2.5" :class="isDark ? 'bg-slate-900/40 border-slate-800' : 'bg-slate-50 border-slate-200'">
                            <span class="text-xs font-black uppercase text-emerald-500 block border-b pb-1" :class="isDark ? 'border-emerald-500/20' : 'border-emerald-500/10'">
                                {{ slides[locale].slide3.col4 }}
                            </span>
                            <div class="rounded-xl p-3 border border-l-4 border-l-emerald-500 opacity-60 space-y-1" :class="isDark ? 'bg-[#121a2e] border-slate-800' : 'bg-white border-slate-250 shadow-sm'">
                                <h4 class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">Haddis Shop</h4>
                                <p class="text-xs" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ locale === 'en' ? 'Archived' : 'ሂሳቡ ተጠናቋል' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl text-sm text-indigo-400 font-bold">
                        {{ slides[locale].slide3.tip }}
                    </div>
                </div>
            </div>

            <!-- SLIDE 4: WORKLOAD CAPACITY & INTERACTIVE STRESS METER (Highly graphical!) -->
            <div v-if="currentSlide === 4" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10 animate-fade-in">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">{{ slides[locale].slide4.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide4.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-600'">
                        {{ slides[locale].slide4.subtitle }}
                    </p>
                    
                    <!-- Stress meter controllers -->
                    <div class="p-6 rounded-[28px] border space-y-4"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200 shadow-sm'"
                    >
                        <h4 class="text-base font-black uppercase tracking-wider" :class="isDark ? 'text-slate-300' : 'text-slate-800'">
                            {{ slides[locale].slide4.boxSub }}
                        </h4>
                        
                        <div class="space-y-3 text-sm">
                            <label class="flex items-center gap-3 cursor-pointer p-2 hover:bg-slate-500/10 rounded-xl transition-colors">
                                <input type="checkbox" v-model="hasClientA" class="h-5 w-5 accent-indigo-600 cursor-pointer rounded">
                                <span :class="isDark ? 'text-slate-200' : 'text-slate-800'">{{ slides[locale].slide4.c1 }}</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer p-2 hover:bg-slate-500/10 rounded-xl transition-colors">
                                <input type="checkbox" v-model="hasClientB" class="h-5 w-5 accent-indigo-600 cursor-pointer rounded">
                                <span :class="isDark ? 'text-slate-200' : 'text-slate-800'">{{ slides[locale].slide4.c2 }}</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer p-2 hover:bg-slate-500/10 rounded-xl transition-colors">
                                <input type="checkbox" v-model="hasErrand" class="h-5 w-5 accent-indigo-600 cursor-pointer rounded">
                                <span :class="isDark ? 'text-slate-200' : 'text-slate-800'">{{ slides[locale].slide4.c3 }}</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer p-2 hover:bg-slate-500/10 rounded-xl transition-colors">
                                <input type="checkbox" v-model="hasProject" class="h-5 w-5 accent-indigo-600 cursor-pointer rounded">
                                <span :class="isDark ? 'text-slate-200' : 'text-slate-800'">{{ slides[locale].slide4.c4 }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <!-- Interactive Stress Visualizer (Wow element) -->
                    <div class="w-full max-w-md shadow-2xl rounded-[36px] p-8 border space-y-6 text-center transform hover:scale-[1.01] transition-all"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-250 shadow-xl'"
                    >
                        <h4 class="text-base font-black uppercase tracking-wider" :class="isDark ? 'text-slate-300' : 'text-slate-900'">
                            {{ slides[locale].slide4.boxTitle }}
                        </h4>

                        <!-- Load Computation -->
                        <div class="py-4">
                            <span class="text-slate-500 block uppercase text-xs font-bold tracking-widest">{{ locale === 'en' ? 'Combined Load Pts' : 'የተጠራቀመ የስራ ነጥብ' }}</span>
                            <span class="text-5xl font-black font-outfit tracking-tighter"
                                  :class="[
                                      (hasClientA * 30 + hasClientB * 15 + hasErrand * 10 + hasProject * 15) >= 60 ? 'text-rose-500' : 
                                      (hasClientA * 30 + hasClientB * 15 + hasErrand * 10 + hasProject * 15) >= 40 ? 'text-amber-500' : 'text-indigo-400'
                                  ]"
                            >
                                {{ hasClientA * 30 + hasClientB * 15 + hasErrand * 10 + hasProject * 15 }} Pts
                            </span>
                        </div>

                        <!-- Interactive Stress Bar -->
                        <div class="space-y-2">
                            <div class="w-full bg-slate-500/15 rounded-full h-4 overflow-hidden p-0.5 border" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                                <div class="h-full rounded-full transition-all duration-500 bg-gradient-to-r"
                                     :class="[
                                         (hasClientA * 30 + hasClientB * 15 + hasErrand * 10 + hasProject * 15) >= 60 ? 'from-orange-500 to-rose-600' : 
                                         (hasClientA * 30 + hasClientB * 15 + hasErrand * 10 + hasProject * 15) >= 40 ? 'from-yellow-400 to-amber-500' : 'from-blue-500 to-indigo-600'
                                     ]"
                                     :style="`width: ${Math.min(100, Math.round(((hasClientA * 30 + hasClientB * 15 + hasErrand * 10 + hasProject * 15) / 80) * 100))}%`"
                                ></div>
                            </div>
                            <div class="flex justify-between text-xs text-slate-500 font-bold uppercase">
                                <span>{{ locale === 'en' ? '0 Pts (Chill)' : '0 ነጥብ' }}</span>
                                <span>{{ locale === 'en' ? '80 Pts (Max)' : '80 ነጥብ' }}</span>
                            </div>
                        </div>

                        <!-- Emotion Indicator -->
                        <div class="p-5 rounded-2xl transition-all duration-300 font-black text-base"
                             :class="[
                                 (hasClientA * 30 + hasClientB * 15 + hasErrand * 10 + hasProject * 15) >= 60 ? (isDark ? 'bg-rose-500/10 border border-rose-500/20 text-rose-400' : 'bg-rose-100 border border-rose-250 text-rose-700') : 
                                 (hasClientA * 30 + hasClientB * 15 + hasErrand * 10 + hasProject * 15) >= 40 ? (isDark ? 'bg-amber-500/10 border border-amber-500/20 text-amber-400' : 'bg-amber-100 border border-amber-250 text-amber-700') : 
                                 (hasClientA * 30 + hasClientB * 15 + hasErrand * 10 + hasProject * 15) > 0 ? (isDark ? 'bg-indigo-500/10 border border-indigo-500/20 text-indigo-400' : 'bg-indigo-100 border border-indigo-250 text-indigo-700') :
                                 (isDark ? 'bg-slate-800/40 text-slate-400 border border-slate-800' : 'bg-slate-100 text-slate-500 border border-slate-200')
                             ]"
                        >
                            {{ slides[locale].slide4.stressLabel }} <br/>
                            <span class="text-lg">
                                {{ 
                                    (hasClientA * 30 + hasClientB * 15 + hasErrand * 10 + hasProject * 15) >= 60 ? slides[locale].slide4.stressed : 
                                    (hasClientA * 30 + hasClientB * 15 + hasErrand * 10 + hasProject * 15) >= 40 ? slides[locale].slide4.busy : 
                                    (hasClientA * 30 + hasClientB * 15 + hasErrand * 10 + hasProject * 15) > 0 ? slides[locale].slide4.happy : 
                                    slides[locale].slide4.optimal 
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 5: ATTENTION REQUIRED RADAR -->
            <div v-if="currentSlide === 5" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">{{ slides[locale].slide5.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide5.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide5.subtitle }}
                    </p>

                    <div class="space-y-4">
                        <div class="flex gap-4 items-start p-4 rounded-2xl" :class="isDark ? 'bg-slate-900/30' : 'bg-white border shadow-sm'">
                            <span class="text-2xl shrink-0">🚨</span>
                            <div>
                                <h5 class="font-bold text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide5.red }}</h5>
                                <p class="text-sm mt-0.5" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ slides[locale].slide5.redDesc }}</p>
                            </div>
                        </div>
                        <div class="flex gap-4 items-start p-4 rounded-2xl" :class="isDark ? 'bg-slate-900/30' : 'bg-white border shadow-sm'">
                            <span class="text-2xl shrink-0">⚠️</span>
                            <div>
                                <h5 class="font-bold text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide5.yellow }}</h5>
                                <p class="text-sm mt-0.5" :class="isDark ? 'text-slate-400' : 'text-slate-600'">{{ slides[locale].slide5.yellowDesc }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm border rounded-[32px] p-6 space-y-4 shadow-xl"
                         :class="isDark ? 'bg-slate-900/60 border-red-500/10' : 'bg-white border-red-200'"
                    >
                        <div class="flex items-center gap-2 text-xs font-black text-rose-500 uppercase tracking-widest pb-2 border-b"
                             :class="isDark ? 'border-red-500/10' : 'border-red-100'"
                        >
                            <span class="h-2 w-2 rounded-full bg-rose-500 animate-ping"></span>
                            {{ locale === 'en' ? 'Critical Alerts' : 'አስቸኳይ ጥቆማዎች' }}
                        </div>
                        
                        <div class="p-3 border rounded-2xl border-l-8 border-l-red-500 space-y-1 shadow-sm"
                             :class="isDark ? 'bg-red-950/10 border-slate-800' : 'bg-red-50/50 border-red-100'"
                        >
                            <h4 class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-950'">Abay Trading PLC</h4>
                            <p class="text-xs text-rose-500 font-bold">{{ locale === 'en' ? '⚠️ Late 3 Days' : '⚠️ 3 ቀን ዘግይቷል' }}</p>
                        </div>

                        <div class="p-3 border rounded-2xl border-l-8 border-l-yellow-500 space-y-1 shadow-sm"
                             :class="isDark ? 'bg-yellow-950/10 border-slate-800' : 'bg-yellow-50/50 border-yellow-100'"
                        >
                            <h4 class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-950'">Awash Coffee exports</h4>
                            <p class="text-xs text-amber-500 font-bold">{{ locale === 'en' ? '⚠️ Due in 24 Hours' : '⚠️ በ24 ሰዓት ውስጥ ይደርሳል' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 6: DAILY TASKS & ERRANDS (Interactive errands panel) -->
            <div v-if="currentSlide === 6" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">{{ slides[locale].slide6.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide6.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide6.subtitle }}
                    </p>
                    
                    <div class="p-5 rounded-2xl border text-sm transition-colors"
                         :class="isDark ? 'bg-indigo-950/20 border-indigo-950/40' : 'bg-indigo-50 border-indigo-100'"
                    >
                        {{ slides[locale].slide6.tip }}
                    </div>
                </div>

                <div class="w-full lg:w-1/2 space-y-4">
                    <div class="rounded-3xl p-6 border space-y-4"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200 shadow-lg'"
                    >
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-500">
                            {{ locale === 'en' ? 'ERRAND LIST MOCKUP' : 'የእለታዊ ስራዎች ማሳያ' }}
                        </h4>
                        
                        <div v-for="task in localDailyTasks" :key="task.id" 
                             @click="advanceDailyStatus(task)"
                             class="flex items-center justify-between p-4 border rounded-2xl cursor-pointer hover:scale-[1.02] active:scale-95 transition-all"
                             :class="isDark ? 'bg-slate-900/80 border-slate-800 hover:bg-slate-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 shadow-sm'"
                        >
                            <div class="flex items-center gap-3">
                                <span class="h-10 w-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-lg shrink-0">{{ task.icon }}</span>
                                <div>
                                    <h5 class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">{{ task.title }}</h5>
                                    <p class="text-[10px]" :class="isDark ? 'text-slate-400' : 'text-slate-500'">{{ task.location }}</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-xs font-black rounded-full border transition-all"
                                  :class="[
                                      task.status === 'pending' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : 
                                      task.status === 'in_progress' ? 'bg-blue-500/10 text-blue-500 border-blue-500/20' : 
                                      'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
                                  ]"
                            >
                                {{ task.status === 'pending' ? (locale === 'en' ? 'Pending' : 'የሚጠበቅ') : task.status === 'in_progress' ? (locale === 'en' ? 'In Progress' : 'በሂደት ላይ') : (locale === 'en' ? 'Done' : 'ተጠናቀቀ') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 7: SQUAD COLLABORATION & PROJECTS -->
            <div v-if="currentSlide === 7" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10 animate-fade-in">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">{{ slides[locale].slide7.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide7.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide7.subtitle }}
                    </p>
                    
                    <div class="space-y-4 font-medium text-sm">
                        <div class="flex gap-3">
                            <span class="text-xl">👑</span>
                            <p :class="isDark ? 'text-slate-300' : 'text-slate-700'">{{ slides[locale].slide7.point1 }}</p>
                        </div>
                        <div class="flex gap-3">
                            <span class="text-xl">⚖️</span>
                            <p :class="isDark ? 'text-slate-300' : 'text-slate-700'">{{ slides[locale].slide7.point2 }}</p>
                        </div>
                        <div class="flex gap-3">
                            <span class="text-xl">💬</span>
                            <p :class="isDark ? 'text-slate-300' : 'text-slate-700'">{{ slides[locale].slide7.point3 }}</p>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 space-y-4">
                    <div class="rounded-3xl p-6 border space-y-4 shadow-xl"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200'"
                    >
                        <div class="flex justify-between items-center border-b pb-3" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
                            <div>
                                <span class="text-[10px] bg-indigo-500/10 text-indigo-500 font-bold px-2 py-0.5 rounded uppercase">{{ locale === 'en' ? 'Audit Squad' : 'የኦዲት ቡድን' }}</span>
                                <h4 class="text-base font-black mt-1" :class="isDark ? 'text-white' : 'text-slate-950'">Abay Textile Corp</h4>
                            </div>
                        </div>
                        
                        <div class="space-y-2 text-xs">
                            <span class="text-[10px] font-black text-slate-400 block uppercase">{{ locale === 'en' ? 'Team Members' : 'የቡድን አባላት' }}</span>
                            <div class="flex gap-2">
                                <span class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-[10px] font-bold text-white ring-2 ring-slate-800" title="Zerihun">ZA</span>
                                <span class="h-8 w-8 rounded-full bg-purple-600 flex items-center justify-center text-[10px] font-bold text-white ring-2 ring-slate-800" title="Lydia">LA</span>
                                <span class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-[10px] font-bold text-white ring-2 ring-slate-800" title="Kidus">KK</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 8: PERFORMANCE & REWARDS -->
            <div v-if="currentSlide === 8" class="w-full flex flex-col lg:flex-row items-center justify-between gap-12 py-6 relative z-10">
                <div class="w-full lg:w-1/2 space-y-6">
                    <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">{{ slides[locale].slide8.badge }}</span>
                    <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                        {{ slides[locale].slide8.title }}
                    </h2>
                    <p class="text-lg leading-relaxed font-medium" :class="isDark ? 'text-slate-350' : 'text-slate-650'">
                        {{ slides[locale].slide8.subtitle }}
                    </p>
                </div>

                <div class="w-full lg:w-1/2 flex items-center justify-center">
                    <div class="w-full max-w-sm rounded-[36px] p-6 border space-y-4 shadow-xl"
                         :class="isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200'"
                    >
                        <h4 class="text-sm font-black uppercase text-slate-500 text-center">{{ locale === 'en' ? 'Achievements Available' : 'የሚገኙ የክብር ባጆች' }}</h4>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 border rounded-2xl text-center space-y-1 shadow-sm transition-transform hover:scale-105"
                                 :class="isDark ? 'bg-slate-900 border-slate-850' : 'bg-slate-50 border-slate-200'"
                            >
                                <span class="text-3xl block">⚡</span>
                                <h5 class="text-xs font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide8.b1Title }}</h5>
                                <p class="text-[10px] text-slate-500 font-bold mt-1 uppercase">{{ slides[locale].slide8.b1Desc }}</p>
                            </div>

                            <div class="p-4 border rounded-2xl text-center space-y-1 shadow-sm transition-transform hover:scale-105"
                                 :class="isDark ? 'bg-slate-900 border-slate-850' : 'bg-slate-50 border-slate-200'"
                            >
                                <span class="text-3xl block">🛡️</span>
                                <h5 class="text-xs font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">{{ slides[locale].slide8.b2Title }}</h5>
                                <p class="text-[10px] text-slate-500 font-bold mt-1 uppercase">{{ slides[locale].slide8.b2Desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 9: INTERACTIVE CHECKLIST & NEXT STEPS -->
            <div v-if="currentSlide === 9" class="w-full flex flex-col items-center justify-center py-6 text-center relative z-10 space-y-4 max-w-2xl animate-fade-in">
                <span class="text-xs font-black tracking-widest text-indigo-400 uppercase">{{ slides[locale].slide9.badge }}</span>
                <h2 class="text-3xl lg:text-5xl font-black font-outfit tracking-tight" :class="isDark ? 'text-white' : 'text-slate-950'">
                    {{ slides[locale].slide9.title }}
                </h2>
                <p class="text-lg font-medium" :class="isDark ? 'text-slate-400' : 'text-slate-600'">
                    {{ slides[locale].slide9.subtitle }}
                </p>
                
                <div class="p-6 rounded-[32px] border text-left w-full max-w-lg mx-auto space-y-3 text-sm"
                     :class="isDark ? 'bg-slate-900/60 border-slate-800 text-slate-350' : 'bg-white border-slate-250 text-slate-700 shadow-md'"
                >
                    <div v-for="item in checklistItems" :key="item.id" 
                         @click="toggleCheck(item)"
                         class="flex items-start gap-3 cursor-pointer select-none py-2 rounded-xl hover:bg-slate-500/10 px-3 transition-colors"
                    >
                        <input type="checkbox" :checked="item.checked" class="accent-indigo-650 h-5 w-5 shrink-0 rounded mt-0.5 cursor-pointer">
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
            <span class="text-xs font-bold" :class="isDark ? 'text-slate-500' : 'text-slate-600'">
                {{ locale === 'en' ? 'GGAA Operations Guide' : 'ጂጂኤኤ የስራ መመሪያ' }}
            </span>
            
            <div class="flex items-center gap-6">
                <button @click="prevSlide" class="p-2.5 rounded-xl border hover:scale-105 active:scale-95 transition-all text-slate-400 hover:text-indigo-500"
                        :class="isDark ? 'border-slate-800 hover:bg-slate-900' : 'border-slate-200 hover:bg-white'"
                >
                    <ChevronLeftIcon class="h-4 w-4" />
                </button>
                
                <span class="text-sm font-bold tracking-wider font-outfit" :class="isDark ? 'text-slate-400' : 'text-slate-700'">
                    {{ locale === 'en' ? 'Slide' : 'ስላይድ' }} <span class="text-indigo-500">{{ currentSlide }}</span> of <span>{{ totalSlides }}</span>
                </span>
                
                <button @click="nextSlide" class="p-2.5 rounded-xl border hover:scale-105 active:scale-95 transition-all text-slate-400 hover:text-indigo-500"
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
