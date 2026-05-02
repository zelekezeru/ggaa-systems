<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
});

// Color scale based on count
const cellColor = (count) => {
    if (count === 0) return 'bg-gray-100 dark:bg-gray-800';
    if (count === 1) return 'bg-blue-200 dark:bg-blue-900';
    if (count <= 3) return 'bg-blue-400 dark:bg-blue-700';
    return 'bg-blue-600 dark:bg-blue-500';
};

// Build a 13×7 grid (91 cells, first cell may be padding)
// We need to figure out what day of week the first date falls on
const grid = computed(() => {
    if (!props.data.length) return [];

    const firstDate = new Date(props.data[0].date + 'T00:00:00');
    // day of week: 0=Sun..6=Sat
    const startPadding = firstDate.getDay(); // cells to leave blank at start

    const cells = [];
    // Add blank padding cells
    for (let i = 0; i < startPadding; i++) {
        cells.push(null);
    }
    // Add actual data cells
    for (const item of props.data) {
        cells.push(item);
    }
    // Pad end to fill last row
    while (cells.length % 7 !== 0) cells.push(null);

    // Split into weeks (columns)
    const weeks = [];
    for (let i = 0; i < cells.length; i += 7) {
        weeks.push(cells.slice(i, i + 7));
    }
    return weeks;
});

// Month labels positioned above columns
const monthLabels = computed(() => {
    const labels = [];
    let lastMonth = null;
    props.data.forEach((item, idx) => {
        const d = new Date(item.date + 'T00:00:00');
        const month = d.toLocaleString('en-US', { month: 'short' });
        if (month !== lastMonth) {
            labels.push({ month, weekIndex: Math.floor(idx / 7) });
            lastMonth = month;
        }
    });
    return labels;
});

const dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const totalCompleted = computed(() => props.data.reduce((s, d) => s + d.count, 0));

const formatTooltip = (cell) => {
    if (!cell) return '';
    const d = new Date(cell.date + 'T00:00:00');
    const label = d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    return cell.count === 0 ? `No tasks — ${label}` : `${cell.count} task${cell.count > 1 ? 's' : ''} — ${label}`;
};
</script>

<template>
    <div class="w-full">
        <!-- Header -->
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Activity Heatmap</h3>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ totalCompleted }} daily tasks completed in the last 90 days
            </span>
        </div>

        <div class="overflow-x-auto">
            <div class="inline-flex flex-col gap-1 min-w-max">

                <!-- Month labels row -->
                <div class="flex gap-1 pl-8">
                    <template v-for="(week, wIdx) in grid" :key="wIdx">
                        <div class="w-3 text-[9px] text-gray-400 dark:text-gray-500 leading-none">
                            <span v-if="monthLabels.some(m => m.weekIndex === wIdx)">
                                {{ monthLabels.find(m => m.weekIndex === wIdx)?.month }}
                            </span>
                        </div>
                    </template>
                </div>

                <!-- Grid rows (days of week) -->
                <div class="flex gap-1">
                    <!-- Day labels -->
                    <div class="flex flex-col gap-1 mr-1">
                        <div v-for="day in dayLabels" :key="day" class="w-6 h-3 text-[9px] text-gray-400 dark:text-gray-500 leading-none flex items-center">
                            <span v-if="['Mon', 'Wed', 'Fri'].includes(day)">{{ day }}</span>
                        </div>
                    </div>

                    <!-- Weeks (columns) -->
                    <div class="flex gap-1">
                        <div v-for="(week, wIdx) in grid" :key="wIdx" class="flex flex-col gap-1">
                            <div
                                v-for="(cell, dIdx) in week"
                                :key="dIdx"
                                class="w-3 h-3 rounded-[2px] transition-colors duration-150"
                                :class="cell ? cellColor(cell.count) : 'bg-transparent'"
                                :title="formatTooltip(cell)"
                            />
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="flex items-center gap-1.5 mt-1 pl-8">
                    <span class="text-[10px] text-gray-400 dark:text-gray-500">Less</span>
                    <div class="w-3 h-3 rounded-[2px] bg-gray-100 dark:bg-gray-800" />
                    <div class="w-3 h-3 rounded-[2px] bg-blue-200 dark:bg-blue-900" />
                    <div class="w-3 h-3 rounded-[2px] bg-blue-400 dark:bg-blue-700" />
                    <div class="w-3 h-3 rounded-[2px] bg-blue-600 dark:bg-blue-500" />
                    <span class="text-[10px] text-gray-400 dark:text-gray-500">More</span>
                </div>
            </div>
        </div>
    </div>
</template>
