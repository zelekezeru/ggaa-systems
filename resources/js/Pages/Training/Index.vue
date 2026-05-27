<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    AcademicCapIcon, 
    UserIcon, 
    ShieldCheckIcon, 
    BanknotesIcon,
    ChevronRightIcon,
    SunIcon,
    MoonIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const isDark = ref(true);

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

// Simple and friendly translations
const content = {
    en: {
        badge: "Interactive Learning Hub",
        title: "Welcome to GGAA Systems Training!",
        subtitle: "We have made these simple, visual guides to show you exactly how to use the system. Pick your daily role below to begin!",
        returnHome: "🏠 Back to Homepage",
        copyright: "© 2026 GGAA Systems Portal. Made with love for simple learning.",
        begin: "Start Training",
        tracks: [
            {
                name: "Employees Track",
                title: "How to Manage Your Daily Work",
                description: "Learn how to use your personal task board, check off your daily errands, chat with your team, and keep your stress level low while getting tasks done on time.",
                badge: "Staff & Associates",
                icon: UserIcon,
                href: "/training/employees",
                color: "from-indigo-500 to-purple-600",
                glow: "shadow-indigo-500/10 hover:shadow-indigo-500/25 border-indigo-500/20"
            },
            {
                name: "Managers & Admins Track",
                title: "Managing Your Team & Clients",
                description: "Learn how to easily add new clients, assign work without overloading your staff, setup automatic task lists, and sign off on monthly logs.",
                badge: "Admins & Managers",
                icon: ShieldCheckIcon,
                href: "/training/admins",
                color: "from-blue-500 to-indigo-600",
                glow: "shadow-blue-500/10 hover:shadow-blue-500/25 border-blue-500/20"
            },
            {
                name: "Finance Track",
                title: "Client Bills & Tax Reconciliations",
                description: "Learn to handle client bills, double-check tax calculations (VAT, COGS, and corporate tax), and create beautiful, verified PDF reports.",
                badge: "Finance Admins",
                icon: BanknotesIcon,
                href: "/training/finances",
                color: "from-emerald-500 to-teal-600",
                glow: "shadow-emerald-500/10 hover:shadow-emerald-500/25 border-emerald-500/20"
            }
        ]
    },
    am: {
        badge: "በይነተገናኝ የስልጠና መድረክ",
        title: "እንኳን ወደ ጂጂኤኤ ሲስተምስ ስልጠና በደህና መጡ!",
        subtitle: "ሲስተሙን እንዴት በቀላሉ መጠቀም እንደሚችሉ በምስል የታገዙ መመሪያዎችን አዘጋጅተናል። ለመጀመር የእርስዎን የስራ ዘርፍ ከታች ይምረጡ!",
        returnHome: "🏠 ወደ ዋናው ገጽ ይመለሱ",
        copyright: "© 2026 ጂጂኤኤ ሲስተምስ። ለቀላል መማር በፍቅር የተሰራ።",
        begin: "ስልጠናውን ጀምር",
        tracks: [
            {
                name: "የሰራተኞች የስልጠና ክፍል",
                title: "የዕለት ተዕለት ስራዎን እንዴት እንደሚመሩ",
                description: "የግል ስራ ሰሌዳዎን እንዴት እንደሚጠቀሙ፣ የዕለት ተዕለት ስራዎችን እንደሚመዘግቡ፣ ከቡድንዎ ጋር እንደሚወያዩ እና ስራዎን ሳይጨናነቁ በተሳካ ሁኔታ በሰዓቱ እንደሚጨርሱ ይማሩ።",
                badge: "አጋር ሰራተኞች",
                icon: UserIcon,
                href: "/training/employees",
                color: "from-indigo-500 to-purple-600",
                glow: "shadow-indigo-500/10 hover:shadow-indigo-500/25 border-indigo-500/20"
            },
            {
                name: "የአስተዳዳሪዎችና ማናጀሮች ክፍል",
                title: "ቡድንዎን እና ደንበኞችን መምራት",
                description: "አዳዲስ ደንበኞችን እንዴት በቀላሉ እንደሚመዘግቡ፣ ሰራተኞች ሳይጨነቁ ስራዎችን እንዴት እንደሚያደላድሉ፣ አውቶማቲክ የስራ ዝርዝሮችን እንደሚፈጥሩ እና ወርሃዊ መዝገቦችን እንደሚያጸድቁ ይማሩ።",
                badge: "አስተዳዳሪዎች እና ማናጀሮች",
                icon: ShieldCheckIcon,
                href: "/training/admins",
                color: "from-blue-500 to-indigo-600",
                glow: "shadow-blue-500/10 hover:shadow-blue-500/25 border-blue-500/20"
            },
            {
                name: "የፋይናንስ የስልጠና ክፍል",
                title: "የደንበኞች ክፍያ እና የታክስ ማጠቃለያ",
                description: "የደንበኛ ሂሳቦችን ማስተዳደር፣ የተለያዩ የታክስ ስሌቶችን (VAT፣ COGS እና የንግድ ትርፍ ታክስ) ማረጋገጥ እና አስተማማኝ ፒዲኤፍ ሪፖርቶችን መፍጠር ይማሩ።",
                badge: "የፋይናንስ ባለሙያዎች",
                icon: BanknotesIcon,
                href: "/training/finances",
                color: "from-emerald-500 to-teal-600",
                glow: "shadow-emerald-500/10 hover:shadow-emerald-500/25 border-emerald-500/20"
            }
        ]
    }
};
</script>

<template>
    <Head :title="`${content[locale].title} - GGAA Systems`" />

    <div class="min-h-screen font-sans py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden flex flex-col justify-between transition-colors duration-300 selection:bg-indigo-600 selection:text-white"
         :class="isDark ? 'bg-[#070b13] text-slate-100' : 'bg-slate-50 text-slate-800'"
    >
        <!-- Background Blur Particles -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-[10%] -left-[10%] w-[45%] h-[45%] blur-[130px] rounded-full transition-opacity duration-300"
                 :class="isDark ? 'bg-indigo-600/10 opacity-100' : 'bg-indigo-500/5 opacity-50'"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[40%] h-[40%] blur-[130px] rounded-full transition-opacity duration-300"
                 :class="isDark ? 'bg-blue-600/10 opacity-100' : 'bg-blue-500/5 opacity-50'"></div>
        </div>

        <div class="max-w-6xl mx-auto w-full relative z-10 space-y-12 my-auto">
            <!-- Language and Theme Controls Bar -->
            <div class="flex justify-between items-center bg-slate-900/5 backdrop-blur-md px-6 py-3 rounded-3xl border border-slate-500/10 shadow-sm"
                 :class="isDark ? 'bg-slate-950/20 border-slate-800' : 'bg-white border-slate-200'"
            >
                <div class="flex gap-2">
                    <button @click="setLanguage('en')" 
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold font-outfit uppercase tracking-wider transition-all"
                            :class="locale === 'en' 
                                ? 'bg-indigo-600 text-white shadow-md' 
                                : (isDark ? 'text-slate-400 hover:text-white hover:bg-slate-800' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-250')"
                    >
                        🇬🇧 English
                    </button>
                    <button @click="setLanguage('am')" 
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold font-outfit transition-all"
                            :class="locale === 'am' 
                                ? 'bg-indigo-600 text-white shadow-md' 
                                : (isDark ? 'text-slate-400 hover:text-white hover:bg-slate-800' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-250')"
                    >
                        🇪🇹 አማርኛ
                    </button>
                </div>

                <button @click="toggleTheme" 
                        class="p-2 rounded-xl border transition-all"
                        :class="isDark ? 'border-slate-800 text-amber-400 hover:bg-slate-900' : 'border-slate-200 text-slate-700 hover:bg-slate-250'"
                        :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                >
                    <SunIcon v-if="isDark" class="h-5 w-5 animate-pulse" />
                    <MoonIcon v-else class="h-5 w-5" />
                </button>
            </div>

            <!-- Header Section with Larger Texts -->
            <div class="text-center space-y-6 max-w-4xl mx-auto">
                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-500/10 text-indigo-400 border border-indigo-500/25 rounded-full text-sm font-black uppercase tracking-widest font-outfit">
                    <AcademicCapIcon class="h-5 w-5" />
                    {{ content[locale].badge }}
                </span>
                
                <h1 class="text-5xl sm:text-7xl font-black font-outfit tracking-tight leading-tight"
                    :class="isDark ? 'text-white' : 'text-slate-950'"
                >
                    {{ locale === 'en' ? 'Master the' : 'የ' }} 
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-blue-500 to-emerald-400">
                        {{ locale === 'en' ? 'GGAA Engine' : 'ጂጂኤኤ ሲስተም' }}
                    </span>
                    {{ locale === 'en' ? '' : 'ይማሩ' }}
                </h1>
                
                <p class="text-xl sm:text-2xl font-medium leading-relaxed"
                   :class="isDark ? 'text-slate-450' : 'text-slate-650'"
                >
                    {{ content[locale].subtitle }}
                </p>
            </div>

            <!-- Tracks Grid (Larger and cleaner layout) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div v-for="track in content[locale].tracks" :key="track.name"
                     class="group relative backdrop-blur-xl border rounded-[36px] p-8 flex flex-col justify-between transition-all duration-300 hover:-translate-y-2.5 shadow-2xl hover:bg-opacity-95"
                     :class="[
                         track.glow,
                         isDark 
                             ? 'bg-[#0d1526]/50 border-slate-800/80 hover:bg-[#0f192e]' 
                             : 'bg-white border-slate-200 hover:bg-slate-100/50 text-slate-800'
                     ]"
                >
                    <div class="space-y-6">
                        <!-- Icon Header -->
                        <div class="flex justify-between items-center">
                            <div class="h-14 w-14 rounded-2xl bg-gradient-to-tr flex items-center justify-center text-white shadow-xl transform group-hover:scale-110 transition-transform" :class="track.color">
                                <component :is="track.icon" class="h-7 w-7" />
                            </div>
                            <span class="text-xs font-black uppercase tracking-wider px-3.5 py-1.5 rounded-full border"
                                  :class="isDark 
                                      ? 'text-slate-400 bg-slate-900/60 border-slate-800' 
                                      : 'text-slate-600 bg-slate-100 border-slate-250'"
                            >
                                {{ track.badge }}
                            </span>
                        </div>

                        <!-- Content with Larger Text -->
                        <div class="space-y-3">
                            <span class="text-xs font-black tracking-widest uppercase font-outfit"
                                  :class="isDark ? 'text-slate-500' : 'text-slate-400'"
                            >
                                {{ track.name }}
                            </span>
                            <h3 class="text-2xl font-black leading-tight font-outfit"
                                :class="isDark ? 'text-white' : 'text-slate-900'"
                            >
                                {{ track.title }}
                            </h3>
                            <p class="text-base leading-relaxed font-medium mt-3"
                               :class="isDark ? 'text-slate-400' : 'text-slate-600'"
                            >
                                {{ track.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Button Action -->
                    <div class="pt-8">
                        <Link :href="track.href"
                              class="w-full py-4 px-5 font-black rounded-2xl transition-all flex items-center justify-between text-sm uppercase tracking-wider font-outfit shadow-md group-hover:shadow-lg"
                              :class="isDark
                                  ? 'bg-slate-900 hover:bg-indigo-600 text-slate-200 hover:text-white border border-slate-800 hover:border-indigo-500'
                                  : 'bg-white hover:bg-indigo-600 text-slate-800 hover:text-white border border-slate-200 hover:border-indigo-500'"
                        >
                            <span>{{ content[locale].begin }}</span>
                            <ChevronRightIcon class="h-5 w-5 transition-transform group-hover:translate-x-1.5" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Footer Return Option -->
            <div class="text-center pt-6">
                <Link href="/" 
                      class="text-sm font-black uppercase tracking-wider transition-colors inline-flex items-center gap-2"
                      :class="isDark ? 'text-slate-500 hover:text-indigo-400' : 'text-slate-500 hover:text-indigo-600'"
                >
                    {{ content[locale].returnHome }}
                </Link>
            </div>
        </div>

        <footer class="text-center py-6 border-t mt-12 transition-colors"
                :class="isDark ? 'border-slate-900 text-slate-600' : 'border-slate-200 text-slate-400'"
        >
            <span class="text-[11px] uppercase font-black tracking-widest">
                {{ content[locale].copyright }}
            </span>
        </footer>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap');
.font-outfit {
    font-family: 'Outfit', sans-serif;
}
</style>
