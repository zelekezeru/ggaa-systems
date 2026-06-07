import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, watch } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import am from './locales/am.json';

const RTL_LOCALES = ['am'];

// One-time reset: default everyone to English, clearing any previously saved
// locale (e.g. legacy 'am') exactly once. After this, a user's own language
// choice via the switcher persists normally.
const LOCALE_RESET_KEY = 'locale_default_en_v1';
if (!localStorage.getItem(LOCALE_RESET_KEY)) {
    localStorage.setItem('locale', 'en');
    localStorage.setItem(LOCALE_RESET_KEY, '1');
}

const initialLocale = localStorage.getItem('locale') || 'en';

const i18n = createI18n({
    legacy: false, // Use Composition API mode
    locale: initialLocale,
    fallbackLocale: 'en',
    messages: {
        en,
        am
    }
});

const appName = import.meta.env.VITE_APP_NAME || 'GGAA-Systems';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .use(Toast, {
                position: 'top-right',
                timeout: 3000,
            })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
