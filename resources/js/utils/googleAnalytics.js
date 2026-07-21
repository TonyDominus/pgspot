export function loadGoogleAnalytics(measurementId) {
    if (!measurementId || typeof window === 'undefined' || window.__pgspotGaLoaded) {
        return;
    }

    window.__pgspotGaLoaded = true;
    window.__pgspotGaId = measurementId;
    window.dataLayer = window.dataLayer || [];

    window.gtag = function gtag() {
        window.dataLayer.push(arguments);
    };

    window.gtag('js', new Date());
    window.gtag('config', measurementId, { anonymize_ip: true });

    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${measurementId}`;
    document.head.appendChild(script);
}
