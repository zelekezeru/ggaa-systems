<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { 
    AcademicCapIcon, 
    UserIcon, 
    ShieldCheckIcon, 
    BanknotesIcon,
    ChevronRightIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const tracks = [
    {
        name: 'Employees Track',
        title: 'Precision Workspace & Field Operations',
        description: 'Master your personal Kanban Workspace, log daily errand states, collaborate in audit squads, and optimize your monthly productivity outputs.',
        icon: UserIcon,
        href: '/training/employees',
        color: 'from-indigo-500 to-purple-600',
        glow: 'shadow-indigo-500/20 hover:shadow-indigo-500/35 border-indigo-500/20',
        badge: 'Staff & Associates'
    },
    {
        name: 'Managers & Admins Track',
        title: 'Branch Oversight & Resource Allocation',
        description: 'Learn to onboard client portfolios, strategically allocate workload capacities, configure task templates, archive records, and audit verifications.',
        icon: ShieldCheckIcon,
        href: '/training/admins',
        color: 'from-blue-500 to-indigo-600',
        glow: 'shadow-blue-500/20 hover:shadow-blue-500/35 border-blue-500/20',
        badge: 'Admins & Branch Managers'
    },
    {
        name: 'Finance Track',
        title: 'Financial Ledger & Invoicing Excellence',
        description: 'Master client invoices, monthly ledger audits (reconciling cash books, inventory COGS, VAT), Ethiopian profit tax formulas, and verifiable PDF exports.',
        icon: BanknotesIcon,
        href: '/training/finances',
        color: 'from-emerald-500 to-teal-600',
        glow: 'shadow-emerald-500/20 hover:shadow-emerald-500/35 border-emerald-500/20',
        badge: 'Finance Admins'
    }
];
</script>

<template>
    <Head :title="`${$t('training')} - GGAA Systems`" />

    <div class="min-h-screen bg-[#070b13] text-slate-100 font-sans py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden flex flex-col justify-between selection:bg-indigo-600 selection:text-white">
        <!-- Blur Background Blobs -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-[10%] -left-[10%] w-[45%] h-[45%] bg-indigo-600/10 blur-[130px] rounded-full animate-pulse"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[40%] h-[40%] bg-blue-600/10 blur-[130px] rounded-full animate-pulse" style="animation-delay: 3s"></div>
        </div>

        <div class="max-w-6xl mx-auto w-full relative z-10 space-y-12 my-auto">
            <!-- Header section -->
            <div class="text-center space-y-4 max-w-3xl mx-auto">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-500/10 text-indigo-400 border border-indigo-500/25 rounded-full text-xs font-black uppercase tracking-widest font-outfit">
                    <AcademicCapIcon class="h-4 w-4" />
                    Interactive Training Portal
                </span>
                
                <h1 class="text-4xl sm:text-6xl font-black font-outfit text-white tracking-tight leading-none">
                    Master the <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-blue-400 to-emerald-400">GGAA Engine</span>
                </h1>
                
                <p class="text-base sm:text-lg text-slate-400 font-medium leading-relaxed">
                    Select your operational division below to access tailored, slide-based guides complete with responsive CSS mockups of the system interfaces.
                </p>
            </div>

            <!-- Tracks Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div v-for="track in tracks" :key="track.name"
                     class="group relative bg-[#0d1526]/60 backdrop-blur-xl border rounded-3xl p-6 flex flex-col justify-between transition-all duration-300 hover:-translate-y-2 shadow-2xl hover:bg-[#0f192e]"
                     :class="track.glow"
                >
                    <div class="space-y-6">
                        <!-- Icon Header -->
                        <div class="flex justify-between items-center">
                            <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr flex items-center justify-center text-white shadow-lg" :class="track.color">
                                <component :is="track.icon" class="h-6 w-6" />
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 bg-slate-900/60 px-2.5 py-1 rounded-full border border-slate-800">
                                {{ track.badge }}
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="space-y-2">
                            <span class="text-xs font-black tracking-widest text-slate-400 uppercase font-outfit">{{ track.name }}</span>
                            <h3 class="text-xl font-bold text-white group-hover:text-white leading-tight font-outfit">{{ track.title }}</h3>
                            <p class="text-xs text-slate-400 leading-relaxed font-medium mt-2">{{ track.description }}</p>
                        </div>
                    </div>

                    <!-- Button Action -->
                    <div class="pt-8">
                        <Link :href="track.href"
                              class="w-full py-3 px-4 bg-slate-900 hover:bg-indigo-600 text-slate-300 hover:text-white border border-slate-800 hover:border-indigo-500 font-bold rounded-2xl transition-all flex items-center justify-between text-xs uppercase tracking-wider font-outfit"
                        >
                            <span>Begin Training Course</span>
                            <ChevronRightIcon class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Footer Return Option -->
            <div class="text-center pt-4">
                <Link href="/" class="text-slate-500 hover:text-indigo-400 text-xs font-semibold uppercase tracking-wider transition-colors inline-flex items-center gap-2">
                    🏠 Return to Homepage
                </Link>
            </div>
        </div>

        <footer class="text-center py-6 border-t border-slate-900 mt-12">
            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest">&copy; 2026 GGAA Systems Portal. Built with Precision.</span>
        </footer>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap');
:root {
    font-family: 'Outfit', sans-serif;
}
</style>
