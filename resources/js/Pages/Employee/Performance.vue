<script setup>
import { computed } from 'vue';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import HeatmapCalendar from '@/Components/HeatmapCalendar.vue';
import { useI18n } from 'vue-i18n';
import { 
    TrophyIcon, 
    RocketLaunchIcon, 
    HeartIcon, 
    BoltIcon,
    ChartBarIcon,
    CalendarIcon,
    CheckBadgeIcon,
    FireIcon
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    stats:                  { type: Object, required: true },
    heatmap:                { type: Array,  default: () => [] },
    employee:               { type: Object, required: true },
    earnedAchievements:     { type: Array,  default: () => [] },
    lockedAchievements:     { type: Array,  default: () => [] },
    totalAchievementPoints: { type: Number, default: 0 },
    leaderboard:            { type: Array,  default: () => [] },
});

const tierStyle = (tier) => ({
    bronze:   { ring: 'ring-amber-700/40', bg: 'bg-amber-50 dark:bg-amber-900/20', text: 'text-amber-700 dark:text-amber-300' },
    silver:   { ring: 'ring-slate-400/40', bg: 'bg-slate-50 dark:bg-slate-800/40', text: 'text-slate-600 dark:text-slate-300' },
    gold:     { ring: 'ring-yellow-500/40', bg: 'bg-yellow-50 dark:bg-yellow-900/20', text: 'text-yellow-700 dark:text-yellow-300' },
    platinum: { ring: 'ring-violet-500/40', bg: 'bg-violet-50 dark:bg-violet-900/20', text: 'text-violet-700 dark:text-violet-300' },
}[tier] || { ring: 'ring-slate-300', bg: 'bg-slate-50', text: 'text-slate-600' });

const scoreCategory = computed(() => {
    if (props.stats.score >= 90) return { label: 'Elite', color: 'text-emerald-500', bg: 'bg-emerald-500/10' };
    if (props.stats.score >= 75) return { label: 'Strong', color: 'text-indigo-500', bg: 'bg-indigo-500/10' };
    return { label: 'Building', color: 'text-amber-500', bg: 'bg-amber-500/10' };
});

const pulseMax = computed(() => Math.max(...props.stats.pulse.map(p => p.count), 1));
</script>

<template>
    <EmployeeLayout>
        <div class="w-full max-w-none space-y-8 pb-12">
            <!-- Header section -->
            <div class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-200/60 dark:border-slate-800 shadow-sm p-8 sm:p-12">
                <div class="absolute -top-24 -right-24 w-[30rem] h-[30rem] bg-indigo-500/10 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-[25rem] h-[25rem] bg-purple-500/5 rounded-full blur-[80px] pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row gap-12 items-start lg:items-center justify-between">
                    <div class="space-y-4">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-xs font-black uppercase tracking-[0.2em]">
                            <TrophyIcon class="w-4 h-4" />
                            {{ t('performance_overview') || 'Performance Mindset' }}
                        </div>
                        <h1 class="text-5xl font-black tracking-tight text-slate-900 dark:text-white">
                            {{ t('your_impact') || 'Your Momentum' }}, <span class="text-indigo-600 dark:text-indigo-400">{{ employee.name.split(' ')[0] }}</span>
                        </h1>
                        <p class="text-lg text-slate-500 dark:text-slate-400 font-medium max-w-2xl">
                            {{ t('performance_desc') || 'Numbers tell a story, but your dedication shapes the firm. Here is how your contributions have scaled this month.' }}
                        </p>
                    </div>

                    <!-- Main Score Card -->
                    <div class="relative flex-shrink-0">
                        <div class="w-48 h-48 sm:w-56 sm:h-56 rounded-full border-[12px] border-slate-100 dark:border-slate-800 flex items-center justify-center relative overflow-hidden group">
                           <div class="absolute inset-0 bg-indigo-600 transition-all duration-1000 ease-out origin-bottom" :style="`height: ${stats.score}%`" />
                           <div class="relative z-10 flex flex-col items-center">
                                <span class="text-5xl sm:text-6xl font-black text-slate-900 dark:text-white group-hover:scale-110 transition-transform duration-500" :class="stats.score > 50 ? 'mix-blend-difference' : ''">
                                    {{ Math.round(stats.score) }}%
                                </span>
                                <span class="text-xs font-black uppercase tracking-widest mt-1 opacity-60" :class="stats.score > 50 ? 'mix-blend-difference' : ''">
                                    {{ t('weighted_score') || 'Efficiency' }}
                                </span>
                           </div>
                        </div>
                        <div class="absolute -bottom-4 -right-4 bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 flex flex-col items-center">
                            <span class="text-xs font-bold text-slate-400 uppercase mb-1">{{ t('status') || 'Vibe' }}</span>
                            <span class="text-sm font-black italic" :class="scoreCategory.color">{{ scoreCategory.label }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- On Time Rate -->
                <div class="bg-white dark:bg-slate-900 p-8 rounded-[2rem] border border-slate-200/60 dark:border-slate-800 shadow-sm group hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <RocketLaunchIcon class="w-6 h-6 text-emerald-500" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">{{ t('on_time_rate') || 'Reliability' }}</h3>
                        <p class="text-3xl font-black text-slate-900 dark:text-white">{{ stats.onTimeRate }}%</p>
                    </div>
                    <div class="mt-4 w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500" :style="`width: ${stats.onTimeRate}%`" />
                    </div>
                </div>

                <!-- Daily Efficiency -->
                <div class="bg-white dark:bg-slate-900 p-8 rounded-[2rem] border border-slate-200/60 dark:border-slate-800 shadow-sm group hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <BoltIcon class="w-6 h-6 text-indigo-500" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">{{ t('today_efficiency') || 'Agility' }}</h3>
                        <p class="text-3xl font-black text-slate-900 dark:text-white">{{ stats.efficiency }}%</p>
                    </div>
                    <div class="mt-4 w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500" :style="`width: ${stats.efficiency}%`" />
                    </div>
                </div>

                <!-- Workload Health -->
                <div class="bg-white dark:bg-slate-900 p-8 rounded-[2rem] border border-slate-200/60 dark:border-slate-800 shadow-sm group hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <HeartIcon class="w-6 h-6 text-rose-500" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">{{ t('workload_health') || 'Burnout Risk' }}</h3>
                        <p class="text-3xl font-black" :class="stats.healthStatus === 'Overloaded' ? 'text-rose-500' : 'text-slate-900 dark:text-white'">
                            {{ stats.healthStatus }}
                        </p>
                    </div>
                    <p class="text-xs font-bold text-slate-500 mt-4">{{ stats.load }} / 30 {{ t('capacity_units') || 'Capacity Units' }}</p>
                </div>

                <!-- Life-time Impact -->
                <div class="bg-white dark:bg-slate-900 p-8 rounded-[2rem] border border-slate-200/60 dark:border-slate-800 shadow-sm group hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <CheckBadgeIcon class="w-6 h-6 text-amber-500" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">{{ t('total_impact') || 'Legacy' }}</h3>
                        <p class="text-3xl font-black text-slate-900 dark:text-white">{{ stats.totalDone }}</p>
                    </div>
                    <p class="text-xs font-bold text-slate-500 mt-4">{{ t('tasks_completed') || 'Total Tasks Closed' }}</p>
                </div>
            </div>

            <!-- Bottom charts / heat area -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Activity Pulse -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-900 p-8 sm:p-10 rounded-[2.5rem] border border-slate-200/60 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between mb-12">
                        <div class="flex items-center gap-3">
                            <ChartBarIcon class="w-6 h-6 text-indigo-500" />
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">{{ t('activity_pulse') || 'Activity Pulse' }}</h2>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-500">
                           <CalendarIcon class="w-3 h-3" />
                           {{ t('last_seven_days') || 'Last 7 Days' }}
                        </div>
                    </div>

                    <div class="flex items-end justify-between h-48 gap-2 sm:gap-4">
                        <div v-for="day in stats.pulse" :key="day.day" class="flex-1 flex flex-col items-center gap-3 group">
                            <div class="relative w-full flex flex-col justify-end items-center h-full">
                                <div class="w-full sm:w-16 bg-slate-100 dark:bg-slate-800 rounded-2xl relative overflow-hidden transition-all duration-700 group-hover:bg-indigo-500/20" 
                                     :style="`height: ${Math.max((day.count / pulseMax) * 100, 5)}%`"
                                >
                                    <div class="absolute inset-0 bg-indigo-600 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity" />
                                </div>
                                <span class="absolute -top-8 text-xs font-black text-indigo-600 dark:text-indigo-400 opacity-0 group-hover:opacity-100 transition-all transform translate-y-2 group-hover:translate-y-0">
                                    {{ day.count }}
                                </span>
                            </div>
                            <span class="text-xs font-bold text-slate-400">{{ day.day }}</span>
                        </div>
                    </div>
                </div>

                <!-- Daily Mantra / Motivation -->
                <div class="bg-indigo-600 rounded-[2.5rem] p-10 text-white relative overflow-hidden shadow-xl shadow-indigo-500/20 flex flex-col justify-between group">
                    <div class="absolute top-0 right-0 p-8 opacity-20 transform group-hover:rotate-12 transition-transform duration-700">
                        <FireIcon class="w-40 h-40" />
                    </div>
                    
                    <div class="relative z-10">
                        <p class="text-xs font-black tracking-widest uppercase opacity-60 mb-6">{{ t('daily_mantra') || 'Daily Catalyst' }}</p>
                        <h3 class="text-3xl font-black italic leading-tight">
                            "{{ stats.load < 10 ? (t('mantra_low') || 'The master of precision waits for the right moment.') : (stats.load > 25 ? (t('mantra_high') || 'Pressure is just the weight of your brilliance.') : (t('mantra_med') || 'Flow in focus, rest in motion.')) }}"
                        </h3>
                    </div>

                    <div class="relative z-10 pt-12">
                        <div class="flex items-center gap-4 p-4 rounded-3xl bg-white/10 backdrop-blur-md border border-white/10">
                            <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center font-black text-xl">
                                {{ Math.round(stats.score / 10) }}
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest opacity-60">{{ t('daily_streak') || 'Focus Multiplier' }}</p>
                                <p class="text-sm font-bold text-white">X{{ (stats.onTimeRate / 50).toFixed(1) }} Performance Power</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Heatmap Calendar -->
            <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-200/60 dark:border-slate-800 shadow-sm">
                <HeatmapCalendar :data="heatmap" />
            </div>

            <!-- Achievements -->
            <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-200/60 dark:border-slate-800 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ t('achievements') || 'Achievements' }}</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            {{ earnedAchievements.length }} {{ t('earned_lc') || 'earned' }} · {{ totalAchievementPoints }} {{ t('pts') || 'pts' }} ·
                            {{ lockedAchievements.length }} {{ t('to_go') || 'to go' }}
                        </p>
                    </div>
                    <div v-if="earnedAchievements.length" class="text-4xl">🏆</div>
                </div>

                <div v-if="earnedAchievements.length === 0 && lockedAchievements.length === 0" class="text-center text-slate-400 py-8">
                    {{ t('no_achievements') || 'No achievements configured yet.' }}
                </div>

                <!-- Earned -->
                <div v-if="earnedAchievements.length" class="mb-6">
                    <h3 class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-3">{{ t('earned') || 'Earned' }}</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        <div
                            v-for="a in earnedAchievements"
                            :key="a.id"
                            class="rounded-xl p-4 ring-2 transition-transform hover:scale-[1.02]"
                            :class="[tierStyle(a.tier).bg, tierStyle(a.tier).ring]"
                        >
                            <div class="text-3xl mb-2">{{ a.icon }}</div>
                            <p class="font-bold text-sm" :class="tierStyle(a.tier).text">{{ a.name }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">{{ a.description }}</p>
                            <p class="text-[10px] uppercase tracking-wider mt-2 font-bold" :class="tierStyle(a.tier).text">
                                {{ t(a.tier) || a.tier }} · +{{ a.points }} {{ t('pts') || 'pts' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Locked -->
                <div v-if="lockedAchievements.length">
                    <h3 class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-3">{{ t('locked') || 'Locked' }}</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        <div
                            v-for="a in lockedAchievements"
                            :key="a.id"
                            class="rounded-xl p-4 ring-1 ring-slate-200 dark:ring-slate-700 bg-slate-50/50 dark:bg-slate-800/30 opacity-60"
                            :title="a.description"
                        >
                            <div class="text-3xl mb-2 grayscale">{{ a.icon }}</div>
                            <p class="font-bold text-sm text-slate-600 dark:text-slate-300">{{ a.name }}</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 line-clamp-2">{{ a.description }}</p>
                            <p class="text-[10px] uppercase tracking-wider mt-2 font-bold text-slate-400">
                                {{ t(a.tier) || a.tier }} · {{ a.points }} {{ t('pts') || 'pts' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leaderboard -->
            <div v-if="leaderboard.length" class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-200/60 dark:border-slate-800 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ t('branch_leaderboard') || 'Branch Leaderboard' }}</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ t('friendly_competition') || 'Friendly competition · ranked by achievement points and on-time score' }}</p>
                    </div>
                    <FireIcon class="h-8 w-8 text-orange-500" />
                </div>

                <ul class="space-y-2">
                    <li
                        v-for="(u, i) in leaderboard"
                        :key="u.id"
                        class="flex items-center gap-4 p-3 rounded-xl ring-1 transition"
                        :class="u.is_self
                            ? 'bg-indigo-50 dark:bg-indigo-900/20 ring-indigo-300 dark:ring-indigo-700'
                            : 'bg-slate-50/50 dark:bg-slate-800/30 ring-slate-100 dark:ring-slate-700'"
                    >
                        <div
                            class="w-10 h-10 rounded-full flex items-center justify-center font-black text-lg flex-shrink-0"
                            :class="i === 0 ? 'bg-yellow-100 text-yellow-700' : i === 1 ? 'bg-slate-100 text-slate-600' : i === 2 ? 'bg-amber-100 text-amber-700' : 'bg-slate-50 text-slate-400 dark:bg-slate-700 dark:text-slate-300'"
                        >
                            <span v-if="i === 0">🥇</span>
                            <span v-else-if="i === 1">🥈</span>
                            <span v-else-if="i === 2">🥉</span>
                            <span v-else>{{ i + 1 }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-900 dark:text-white truncate">
                                {{ u.name }}
                                <span v-if="u.is_self" class="ml-2 text-xs uppercase tracking-wider font-semibold text-indigo-600 dark:text-indigo-400">{{ t('you') || 'You' }}</span>
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ u.achievement_count }} {{ t('achievements_lc') || 'achievements' }} · {{ t('score') || 'Score' }} {{ u.monthly_score }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-black text-slate-900 dark:text-white">{{ u.achievement_points }}</p>
                            <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">{{ t('points_lc') || 'points' }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </EmployeeLayout>
</template>

<style scoped>
/* Keyframe for floating score card */
@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}
</style>
