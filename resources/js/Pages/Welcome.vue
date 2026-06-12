<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { 
    ShieldCheckIcon, 
    ChartBarIcon, 
    UserGroupIcon, 
    ArrowRightIcon, 
    CloudArrowUpIcon, 
    BoltIcon,
    Bars3Icon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { ref, onMounted } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const isMenuOpen = ref(false);
const scrolled = ref(false);

onMounted(() => {
    window.addEventListener('scroll', () => {
        scrolled.value = window.scrollY > 20;
    });
});

const features = [
    { key: 'service_tracking',  icon: ChartBarIcon,    color: 'text-blue-600',    bg: 'bg-blue-100 dark:bg-blue-900/30' },
    { key: 'capacity_balancing', icon: BoltIcon,        color: 'text-amber-600',   bg: 'bg-amber-100 dark:bg-amber-900/30' },
    { key: 'multi_role_portal',  icon: ShieldCheckIcon, color: 'text-emerald-600', bg: 'bg-emerald-100 dark:bg-emerald-900/30' },
    { key: 'realtime_comms',     icon: UserGroupIcon,   color: 'text-purple-600',  bg: 'bg-purple-100 dark:bg-purple-900/30' },
];
</script>

<template>
    <Head :title="`${$t('welcome')} - GGAA Systems`" />
    
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans selection:bg-blue-600 selection:text-white transition-colors duration-500 overflow-x-hidden">
        
        <!-- Abstract Background Blobs -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-400/10 dark:bg-blue-600/5 blur-[120px] rounded-full animate-pulse"></div>
            <div class="absolute top-[20%] -right-[5%] w-[30%] h-[30%] bg-purple-400/10 dark:bg-purple-600/5 blur-[120px] rounded-full animate-pulse" style="animation-delay: 2s"></div>
            <div class="absolute bottom-[10%] left-[20%] w-[25%] h-[25%] bg-emerald-400/10 dark:bg-emerald-600/5 blur-[100px] rounded-full animate-pulse" style="animation-delay: 4s"></div>
        </div>

        <!-- Floating Logo Circle (Top Right Overlay) -->
        <div class="absolute top-8 right-8 z-20 pointer-events-none opacity-20 dark:opacity-40 lg:opacity-100">
            <div class="h-24 w-24 lg:h-32 lg:w-32 rounded-full border-2 border-dashed border-blue-500/30 p-2 animate-[spin_20s_linear_infinite]">
                 <img src="/logo.png" class="h-full w-full rounded-full object-cover shadow-2xl" alt="GGAA Logo" />
            </div>
        </div>

        <!-- Sticky Header -->
        <header 
            class="fixed top-0 w-full z-50 transition-all duration-300 transform"
            :class="scrolled ? 'bg-white/80 dark:bg-slate-900/80 backdrop-blur-md shadow-lg py-3' : 'bg-transparent py-6'"
        >
            <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
                <!-- Logo -->
                <div class="flex items-center gap-3 group cursor-pointer">
                    <div class="h-10 w-10 overflow-hidden rounded-full shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform">
                        <img src="/logo.png" class="h-full w-full object-cover" />
                    </div>
                    <div class="flex flex-col -space-y-1">
                        <span class="text-xl font-black tracking-tighter text-slate-900 dark:text-white uppercase leading-none">GGAA <span class="text-blue-600">Systems</span></span>
                        <span class="text-[8px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Gedewon Girma Authorized Accountant</span>
                    </div>
                </div>

                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center gap-8">
                    <nav v-if="canLogin" class="flex items-center gap-4">
                        <Link
                            :href="route('training.index')"
                            class="text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 transition-all mr-2"
                        >
                            {{ $t('training') }}
                        </Link>

                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-sm font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 border-2 border-blue-600/20 px-5 py-2 rounded-xl transition-all"
                        >
                            {{ $t('return_to_workspace') }}
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 transition-all"
                            >
                                {{ $t('login') }}
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-6 py-2.5 rounded-xl shadow-lg shadow-blue-600/30 hover:scale-105 transition-all active:scale-95"
                            >
                                {{ $t('start_trial') }}
                            </Link>
                        </template>
                    </nav>
                </div>

                <!-- Mobile Menu Toggle -->
                <button @click="isMenuOpen = !isMenuOpen" class="md:hidden text-slate-600 dark:text-slate-400">
                    <Bars3Icon v-if="!isMenuOpen" class="h-6 w-6" />
                    <XMarkIcon v-else class="h-6 w-6" />
                </button>
            </div>

            <!-- Mobile Menu -->
            <div v-if="isMenuOpen" class="md:hidden absolute top-full w-full bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 p-6 flex flex-col gap-4 animate-in slide-in-from-top duration-300">
                <Link
                    :href="route('training.index')"
                    class="w-full text-center py-3 rounded-xl bg-indigo-600/10 text-indigo-600 dark:text-indigo-400 border border-indigo-600/20 font-bold"
                >
                    {{ $t('training') }}
                </Link>
                
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="w-full text-center py-3 rounded-xl bg-blue-600 text-white font-bold"
                >
                    {{ $t('dashboard') }}
                </Link>
                <template v-else>
                    <Link :href="route('login')" class="w-full text-center py-3 text-slate-600 dark:text-slate-400 font-bold">{{ $t('login') }}</Link>
                    <Link v-if="canRegister" :href="route('register')" class="w-full text-center py-3 bg-blue-600 text-white rounded-xl font-bold">{{ $t('get_started') }}</Link>
                </template>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 px-6">
            <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-16 items-center">
                <div class="space-y-8 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-black uppercase tracking-widest animate-bounce">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
                        </span>
                        {{ $t('enterprise_ready') }}
                    </div>

                    <h1 class="text-5xl lg:text-7xl font-black text-slate-900 dark:text-white leading-[1.1] tracking-tighter">
                        {{ $t('welcome_title_p1') }} <br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-500">{{ $t('welcome_title_p2') }}</span>
                    </h1>

                    <p class="text-lg lg:text-xl text-slate-600 dark:text-slate-400 max-w-xl mx-auto lg:mx-0 leading-relaxed font-medium">
                        {{ $t('welcome_desc') }}
                    </p>

                    <div class="flex flex-wrap justify-center lg:justify-start gap-4">
                        <Link 
                            :href="canRegister ? route('register') : route('login')"
                            class="group relative inline-flex items-center px-8 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-950 font-black rounded-2xl overflow-hidden hover:scale-105 transition-all shadow-xl dark:shadow-white/5 active:scale-95"
                        >
                            <span>{{ $t('deploy_system_now') }}</span>
                            <ArrowRightIcon class="h-5 w-5 ml-3 transition-transform group-hover:translate-x-1" />
                            <div class="absolute inset-0 bg-blue-600 opacity-0 group-hover:opacity-10 transition-opacity"></div>
                        </Link>
                        
                        <button class="px-8 py-4 bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                            {{ $t('watch_demo') }}
                        </button>
                    </div>

                    <div class="pt-8 flex items-center justify-center lg:justify-start gap-8 opacity-50 dark:opacity-30">
                        <div class="text-xs font-bold uppercase tracking-tight text-slate-500 dark:text-slate-400">{{ $t('trusted_by') }}</div>
                        <div class="h-6 w-32 bg-slate-400 dark:bg-slate-600 rounded-lg blur-[2px]"></div>
                        <div class="h-6 w-24 bg-slate-400 dark:bg-slate-600 rounded-lg blur-[2px]"></div>
                    </div>
                </div>

                <!-- Dashboard Preview -->
                <div class="relative group">
                    <div class="absolute -inset-4 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-[2.5rem] blur-2xl opacity-20 group-hover:opacity-30 transition-opacity"></div>
                    <div class="relative bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden aspect-[4/3] group-hover:scale-[1.02] transition-transform duration-500">
                        <!-- Mock UI Shell -->
                        <div class="h-8 bg-slate-50 dark:bg-slate-800/50 flex items-center px-4 gap-1.5 border-b border-slate-100 dark:border-white/5">
                            <div class="h-2 w-2 rounded-full bg-slate-300 dark:bg-slate-600"></div>
                            <div class="h-2 w-2 rounded-full bg-slate-300 dark:bg-slate-600"></div>
                            <div class="h-2 w-2 rounded-full bg-slate-300 dark:bg-slate-600"></div>
                        </div>
                        <div class="p-6 grid grid-cols-4 gap-4 h-full">
                            <div class="col-span-1 bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3">
                                <div class="h-4 w-full bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                                <div class="h-4 w-3/4 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                                <div class="h-4 w-1/2 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                            </div>
                            <div class="col-span-3 space-y-6">
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="h-24 bg-blue-50 dark:bg-blue-600/10 rounded-2xl flex items-center justify-center border border-blue-600/20 animate-pulse">
                                        <ChartBarIcon class="h-8 w-8 text-blue-600 opacity-50" />
                                    </div>
                                    <div class="h-24 bg-slate-50 dark:bg-slate-800 rounded-2xl"></div>
                                    <div class="h-24 bg-slate-50 dark:bg-slate-800 rounded-2xl"></div>
                                </div>
                                <div class="space-y-4">
                                    <div class="h-4 w-1/3 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                                    <div class="h-32 bg-slate-50 dark:bg-slate-800 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Floating Badge -->
                    <div class="absolute -bottom-6 -left-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-xl animate-bounce">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                                <bolt-icon class="h-6 w-6 text-emerald-600" />
                            </div>
                            <div>
                                <div class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tighter line-clamp-1">{{ $t('auto_scaling_capacity') }}</div>
                                <div class="text-[10px] font-bold text-emerald-600 uppercase">{{ $t('live_optimizer_active') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Grid -->
        <section class="py-24 bg-white/50 dark:bg-slate-900/30 backdrop-blur-sm relative border-y border-slate-100 dark:border-slate-800">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center max-w-2xl mx-auto mb-20 space-y-4">
                    <h2 class="text-xs font-black text-blue-600 uppercase tracking-[0.2em]">{{ $t('features_label') }}</h2>
                    <p class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">{{ $t('features_heading') }}</p>
                    <p class="text-slate-600 dark:text-slate-400 font-medium">{{ $t('features_subheading') }}</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="feature in features" :key="feature.name" class="group relative bg-white dark:bg-slate-900 p-8 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-300">
                        <div :class="['h-14 w-14 rounded-2xl mb-6 flex items-center justify-center transition-transform group-hover:scale-110 duration-500 shadow-inner', feature.bg]">
                            <component :is="feature.icon" :class="['h-7 w-7', feature.color]" />
                        </div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white mb-3 tracking-tight">{{ $t(`feature_${feature.key}_name`) }}</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
                            {{ $t(`feature_${feature.key}_desc`) }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tech Stack CTA -->
        <section class="py-32 px-6">
            <div class="max-w-5xl mx-auto rounded-[3rem] bg-slate-900 dark:bg-blue-600 relative overflow-hidden p-12 lg:p-20 text-center shadow-2xl">
                <!-- Abstract patterns -->
                <div class="absolute inset-0 opacity-20 pointer-events-none">
                    <div class="absolute top-0 right-0 w-96 h-96 bg-white/20 rounded-full blur-[100px] -mr-48 -mt-48"></div>
                    <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-400/20 rounded-full blur-[100px] -ml-48 -mb-48"></div>
                </div>

                <div class="relative z-10 space-y-10">
                    <h2 class="text-4xl lg:text-6xl font-black text-white tracking-tight leading-[1.1]">{{ $t('ready_to_scale') }}</h2>
                    
                    <div class="flex flex-wrap justify-center gap-6">
                        <Link 
                            :href="canRegister ? route('register') : route('login')"
                            class="px-10 py-5 bg-white text-slate-900 hover:bg-slate-50 font-black rounded-2xl shadow-xl transition-all hover:scale-105 active:scale-95"
                        >
                            {{ $t('request_access') }}
                        </Link>
                        <button class="px-10 py-5 bg-white/10 text-white backdrop-blur-md border border-white/20 hover:bg-white/20 font-black rounded-2xl transition-all">
                            {{ $t('talk_to_team') }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Simple Footer -->
        <footer class="py-12 border-t border-slate-100 dark:border-slate-900">
            <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="flex items-center gap-2 grayscale group-hover:grayscale-0 transition-all opacity-50">
                    <div class="h-6 w-6 overflow-hidden rounded-full flex items-center justify-center">
                        <img src="/logo.png" class="h-full w-full object-cover" />
                    </div>
                    <span class="text-xs font-black tracking-tighter text-slate-900 dark:text-white uppercase">GGAA Systems.</span>
                </div>
                
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    &copy; 2026 GGAA (Gedewon Girma Authorized Accountant) . Built with Precision.
                </div>

                <div class="flex gap-6">
                    <a href="#" class="text-slate-400 hover:text-blue-600 transition-colors"><bolt-icon class="h-5 w-5" /></a>
                    <a href="#" class="text-slate-400 hover:text-blue-600 transition-colors"><cloud-arrow-up-icon class="h-5 w-5" /></a>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100;400;700;900&display=swap');

:root {
    font-family: 'Outfit', sans-serif;
}

.animate-in {
    animation-duration: 0.8s;
    animation-fill-mode: both;
}

@keyframes slide-up {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.slide-up {
    animation-name: slide-up;
}
</style>
