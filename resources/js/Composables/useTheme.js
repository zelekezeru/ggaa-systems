import { useDark, useToggle } from '@vueuse/core';
import { watchEffect } from 'vue';

export function useTheme() {
    // isDark will automatically read/write to localStorage and check system preferences
    const isDark = useDark({
        selector: 'html',
        attribute: 'class',
        valueDark: 'dark',
        valueLight: '',
    });

    const toggleDark = useToggle(isDark);

    // Provide a helper to explicitly set theme if needed
    const setTheme = (theme) => {
        isDark.value = theme === 'dark';
    };

    return {
        isDark,
        toggleDark,
        setTheme
    };
}
