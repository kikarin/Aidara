import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import PersistentAppShell from './layouts/PersistentAppShell.vue';
import { shouldUsePersistentShell } from './lib/inertiaLayout';
import axios from 'axios';

// Configure axios to send credentials (cookies) with requests for session-based auth
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

axios.interceptors.request.use((config) => {
    const xsrfCookie = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
    if (xsrfCookie) {
        config.headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfCookie.split('=')[1] ?? '');
    }

    return config;
});

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        const page = await resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue'));

        if (shouldUsePersistentShell(name) && !(page.default as DefineComponent & { layout?: unknown }).layout) {
            (page.default as DefineComponent & { layout?: unknown }).layout = PersistentAppShell;
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        delay: 120,
        color: '#2e7d32',
        includeCSS: true,
        showSpinner: false,
    },
});

router.on('finish', () => {
    const main = document.querySelector('[data-main-scroll]');
    if (main instanceof HTMLElement) {
        main.scrollTop = 0;
    }
});

// This will set light / dark mode on page load...
initializeTheme();
