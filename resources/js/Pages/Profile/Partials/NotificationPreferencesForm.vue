<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const user = usePage().props.auth.user;

const form = useForm({
    notify_contributions: user.notify_contributions ?? false,
    notify_poi_updates: user.notify_poi_updates ?? false,
});

const submit = () => {
    form.patch(route('profile.notifications.update'));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-semibold text-pg-text">Notifiche email</h2>
            <p class="mt-1 text-sm text-pg-muted">
                Scegli quali aggiornamenti ricevere via email. Richiedono email verificata.
            </p>
        </header>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <label class="flex items-start gap-3 rounded-xl border border-gray-100 p-4">
                <input v-model="form.notify_contributions" type="checkbox" class="mt-1 rounded text-pg-primary" />
                <span class="text-sm">
                    <span class="font-medium text-pg-text">Contributi e pubblicazioni</span>
                    <span class="mt-0.5 block text-pg-muted">Es. «Il tuo luogo è stato pubblicato» o aggiornamenti sulla moderazione</span>
                </span>
            </label>

            <label class="flex items-start gap-3 rounded-xl border border-gray-100 p-4">
                <input v-model="form.notify_poi_updates" type="checkbox" class="mt-1 rounded text-pg-primary" />
                <span class="text-sm">
                    <span class="font-medium text-pg-text">Aggiornamenti luoghi</span>
                    <span class="mt-0.5 block text-pg-muted">Quando un luogo che hai segnalato viene modificato dal team</span>
                </span>
            </label>

            <div class="flex items-center gap-4">
                <button type="submit" class="pg-btn-primary text-sm" :disabled="form.processing">Salva preferenze</button>
                <p v-if="form.recentlySuccessful" class="text-sm text-pg-success">Salvato.</p>
            </div>
            <InputError :message="form.errors.notify_contributions" />
        </form>
    </section>
</template>
