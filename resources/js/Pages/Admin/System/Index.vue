<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    health: Object,
});

function sendTestMail() {
    router.post(route('admin.system.test-mail'));
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('it-IT', { timeZone: 'Europe/Rome' });
}
</script>

<template>
    <Head title="Monitoraggio" />

    <AdminShell>
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-pg-text">Monitoraggio sistema</h1>
                <p class="text-sm text-pg-muted">Stato applicazione, email, database e backup</p>
            </div>
            <button type="button" class="pg-btn-primary" @click="sendTestMail">
                Invia email di test
            </button>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="pg-card space-y-3 p-6">
                <h2 class="font-semibold text-pg-text">Applicazione</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Ambiente</dt>
                        <dd class="font-medium">{{ health.application.env }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">URL</dt>
                        <dd class="truncate font-medium">{{ health.application.url }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Debug</dt>
                        <dd :class="health.application.debug ? 'text-pg-warning' : 'text-green-700'">
                            {{ health.application.debug ? 'Attivo (disattiva in produzione)' : 'Off' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Health check</dt>
                        <dd>
                            <a :href="health.health_url" target="_blank" class="text-pg-primary underline">/up</a>
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="pg-card space-y-3 p-6">
                <h2 class="font-semibold text-pg-text">Runtime</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">PHP</dt>
                        <dd class="font-medium">{{ health.runtime.php_version }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Laravel</dt>
                        <dd class="font-medium">{{ health.runtime.laravel_version }}</dd>
                    </div>
                </dl>
            </section>

            <section class="pg-card space-y-3 p-6">
                <h2 class="font-semibold text-pg-text">Email (Resend)</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Mailer</dt>
                        <dd class="font-medium">{{ health.mail.mailer }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">From</dt>
                        <dd class="font-medium">{{ health.mail.from_name }} &lt;{{ health.mail.from }}&gt;</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">API key Resend</dt>
                        <dd :class="health.mail.resend_configured ? 'text-green-700' : 'text-pg-error'">
                            {{ health.mail.resend_configured ? 'Configurata' : 'Mancante' }}
                        </dd>
                    </div>
                </dl>
                <p class="text-xs text-pg-muted">
                    Puoi riusare la stessa API key Resend dell'altro sito sulla VPS. Verifica il dominio pgspot.it su Resend e imposta MAIL_FROM con un indirizzo di quel dominio.
                </p>
            </section>

            <section class="pg-card space-y-3 p-6">
                <h2 class="font-semibold text-pg-text">Database</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Connessione</dt>
                        <dd :class="health.database.connected ? 'text-green-700' : 'text-pg-error'">
                            {{ health.database.connected ? 'OK' : 'Errore' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Driver</dt>
                        <dd class="font-medium">{{ health.database.driver }}</dd>
                    </div>
                </dl>
            </section>

            <section class="pg-card space-y-3 p-6">
                <h2 class="font-semibold text-pg-text">Filesystem</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">storage/ scrivibile</dt>
                        <dd :class="health.filesystem.storage_writable ? 'text-green-700' : 'text-pg-error'">
                            {{ health.filesystem.storage_writable ? 'Sì' : 'No' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">bootstrap/cache scrivibile</dt>
                        <dd :class="health.filesystem.bootstrap_cache_writable ? 'text-green-700' : 'text-pg-error'">
                            {{ health.filesystem.bootstrap_cache_writable ? 'Sì' : 'No' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Link public/storage</dt>
                        <dd :class="health.filesystem.public_storage_linked ? 'text-green-700' : 'text-pg-warning'">
                            {{ health.filesystem.public_storage_linked ? 'OK' : 'Mancante — esegui php artisan storage:link' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Spazio libero (storage)</dt>
                        <dd class="font-medium">{{ health.filesystem.storage_free_mb ?? '—' }} MB</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Spazio libero (app)</dt>
                        <dd class="font-medium">{{ health.filesystem.app_free_mb ?? '—' }} MB</dd>
                    </div>
                </dl>
            </section>

            <section class="pg-card space-y-3 p-6">
                <h2 class="font-semibold text-pg-text">Code & log</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Queue</dt>
                        <dd class="font-medium">{{ health.queue.connection }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Job falliti</dt>
                        <dd :class="health.queue.failed_jobs > 0 ? 'text-pg-warning' : 'text-green-700'">
                            {{ health.queue.failed_jobs }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">laravel.log</dt>
                        <dd class="font-medium">{{ health.logs.laravel_log_mb ?? 0 }} MB</dd>
                    </div>
                </dl>
            </section>

            <section class="pg-card space-y-3 p-6">
                <h2 class="font-semibold text-pg-text">Statistiche piattaforma</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Utenti</dt>
                        <dd class="font-medium">{{ health.stats.users }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Email non verificate</dt>
                        <dd class="font-medium">{{ health.stats.users_unverified }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">POI pubblicati</dt>
                        <dd class="font-medium">{{ health.stats.pois_published }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Contributi in attesa</dt>
                        <dd class="font-medium">{{ health.stats.contributions_pending }}</dd>
                    </div>
                </dl>
            </section>

            <section class="pg-card space-y-3 p-6">
                <h2 class="font-semibold text-pg-text">Ultimo backup</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Data</dt>
                        <dd class="font-medium">{{ formatDate(health.backup?.at) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-pg-muted">Tipo</dt>
                        <dd class="font-medium">{{ health.backup?.type ?? '—' }}</dd>
                    </div>
                    <div v-if="health.backup?.database" class="text-xs text-pg-muted break-all">
                        DB: {{ health.backup.database }}
                    </div>
                    <div v-if="health.backup?.storage" class="text-xs text-pg-muted break-all">
                        Foto: {{ health.backup.storage }}
                    </div>
                </dl>
                <p class="text-xs text-pg-muted">
                    Esegui <code class="rounded bg-pg-background px-1">deploy/backup.sh</code> sulla VPS e configura un cron giornaliero. Conserva anche una copia del file <code class="rounded bg-pg-background px-1">.env</code> fuori dal server.
                </p>
            </section>
        </div>
    </AdminShell>
</template>
