const CONSENT_KEY = 'pgspot_cookie_consent';

export function hasAnalyticsConsent() {
    return localStorage.getItem(CONSENT_KEY) === 'all';
}

export function acceptAnalytics() {
    localStorage.setItem(CONSENT_KEY, 'all');
}

export function rejectAnalytics() {
    localStorage.setItem(CONSENT_KEY, 'necessary');
}
