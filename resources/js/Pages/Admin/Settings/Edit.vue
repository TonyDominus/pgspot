<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    tagline: props.settings.tagline,
    map_lat: props.settings.map_center?.lat ?? 43.1107,
    map_lng: props.settings.map_center?.lng ?? 12.3908,
    map_zoom: props.settings.map_center?.zoom ?? 14,
    paypal_url: props.settings.paypal_url,
    instagram: props.settings.instagram,
    facebook: props.settings.facebook,
    contact_email: props.settings.contact_email,
    legal_privacy: props.settings.legal_privacy,
    legal_terms: props.settings.legal_terms,
    legal_cookies: props.settings.legal_cookies,
    legal_contact: props.settings.legal_contact,
    events_public: props.settings.events_public ?? true,
});

function submit() {
    form.put(route('admin.settings.update'));
}
</script>

<template>
    <Head title="Impostazioni" />

    <AdminShell>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-pg-text">Impostazioni globali</h1>
            <p class="text-sm text-pg-muted">Solo Super Admin — testi legali, social e mappa</p>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <section class="pg-card space-y-4 p-6">
                <h2 class="font-semibold text-pg-text">Sito</h2>
                <div>
                    <label class="mb-1 block text-sm font-medium">Tagline</label>
                    <input v-model="form.tagline" type="text" class="pg-input" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Email contatti</label>
                    <input v-model="form.contact_email" type="email" class="pg-input" required />
                </div>
            </section>

            <section class="pg-card space-y-4 p-6">
                <h2 class="font-semibold text-pg-text">Centro mappa predefinito</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Latitudine</label>
                        <input v-model="form.map_lat" type="number" step="any" class="pg-input" required />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Longitudine</label>
                        <input v-model="form.map_lng" type="number" step="any" class="pg-input" required />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Zoom</label>
                        <input v-model="form.map_zoom" type="number" min="1" max="19" class="pg-input" required />
                    </div>
                </div>
            </section>

            <section class="pg-card space-y-4 p-6">
                <h2 class="font-semibold text-pg-text">Social e PayPal</h2>
                <div>
                    <label class="mb-1 block text-sm font-medium">PayPal (Offrimi un caffè)</label>
                    <input v-model="form.paypal_url" type="url" class="pg-input" placeholder="https://paypal.me/..." />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Instagram</label>
                        <input v-model="form.instagram" type="url" class="pg-input" placeholder="https://instagram.com/..." />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Facebook</label>
                        <input v-model="form.facebook" type="url" class="pg-input" placeholder="https://facebook.com/..." />
                    </div>
                </div>
            </section>

            <section class="pg-card space-y-4 p-6">
                <h2 class="font-semibold text-pg-text">Funzionalità</h2>
                <label class="flex items-center gap-3 text-sm">
                    <input v-model="form.events_public" type="checkbox" class="rounded border-gray-300 text-pg-primary" />
                    <span>Mostra sezione eventi pubblica su <code>/eventi</code> e banner in home</span>
                </label>
            </section>

            <section class="pg-card space-y-4 p-6">
                <h2 class="font-semibold text-pg-text">Testi legali</h2>
                <p class="text-xs text-pg-muted">Visibili su /legal/privacy, /legal/termini, ecc.</p>
                <div>
                    <label class="mb-1 block text-sm font-medium">Privacy Policy</label>
                    <textarea v-model="form.legal_privacy" rows="5" class="pg-input font-mono text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Termini di utilizzo</label>
                    <textarea v-model="form.legal_terms" rows="5" class="pg-input font-mono text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Cookie Policy</label>
                    <textarea v-model="form.legal_cookies" rows="5" class="pg-input font-mono text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Contatti (testo pagina)</label>
                    <textarea v-model="form.legal_contact" rows="4" class="pg-input font-mono text-sm" />
                </div>
            </section>

            <button type="submit" class="pg-btn-primary w-full sm:w-auto" :disabled="form.processing">
                Salva impostazioni
            </button>
        </form>
    </AdminShell>
</template>
