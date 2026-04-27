import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import am from './locales/am.json';
import ar from './locales/ar.json';

const i18n = createI18n({
    legacy: false, // Use Composition API mode
    locale: localStorage.getItem('locale') || 'en',
    fallbackLocale: 'en',
    messages: {
        en,
        am,
        ar
    }
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

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
