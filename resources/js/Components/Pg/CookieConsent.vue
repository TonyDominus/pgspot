<script setup>
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { acceptAnalytics, hasAnalyticsConsent, rejectAnalytics } from '@/utils/cookieConsent';
import { loadGoogleAnalytics } from '@/utils/googleAnalytics';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(false);

onMounted(() => {
    if (hasAnalyticsConsent()) {
        loadGoogleAnalytics(page.props.analytics?.ga_id);
        return;
    }
    if (localStorage.getItem('pgspot_cookie_consent') === 'necessary') {
        return;
    }
    visible.value = true;
});

function acceptAll() {
    acceptAnalytics();
    loadGoogleAnalytics(page.props.analytics?.ga_id);
    visible.value = false;
}

function acceptNecessary() {
    rejectAnalytics();
    visible.value = false;
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="fixed inset-x-0 bottom-0 z-[800] border-t border-gray-200 bg-pg-surface p-4 shadow-2xl lg:bottom-4 lg:left-4 lg:right-auto lg:max-w-md lg:rounded-2xl lg:border"
        >
            <p class="text-sm font-semibold text-pg-text">Cookie e privacy</p>
            <p class="mt-2 text-xs leading-relaxed text-pg-muted">
                Usiamo cookie tecnici necessari al funzionamento. Con il tuo consenso attiviamo anche Google Analytics
                per statistiche anonime aggregate.
                <Link :href="route('legal.show', 'cookie')" class="text-pg-primary underline">Cookie Policy</Link>
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" class="pg-btn-primary text-sm" @click="acceptAll">
                    Accetta tutti
                </button>
                <button
                    type="button"
                    class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-pg-text hover:bg-gray-50"
                    @click="acceptNecessary"
                >
                    Solo necessari
                </button>
            </div>
        </div>
    </Teleport>
</template>
