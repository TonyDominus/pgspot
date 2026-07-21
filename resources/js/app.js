import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { hasAnalyticsConsent } from './utils/cookieConsent';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function trackAnalyticsPageView() {
    if (typeof window.gtag !== 'function' || !hasAnalyticsConsent() || !window.__pgspotGaId) {
        return;
    }

    window.gtag('config', window.__pgspotGaId, {
        page_path: window.location.pathname + window.location.search,
    });
}

router.on('navigate', () => {
    setTimeout(trackAnalyticsPageView, 0);
});

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
            .mount(el);
    },
    progress: {
        color: '#2E7D32',
    },
});
