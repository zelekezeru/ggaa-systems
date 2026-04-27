<template>
    <div class="bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 sm:rounded-xl overflow-hidden transition-colors">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <slot />
            </table>
        </div>
    </div>
</template>
