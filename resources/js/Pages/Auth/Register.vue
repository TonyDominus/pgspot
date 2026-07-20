<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    accept_terms: false,
    accept_privacy: false,
    notify_contributions: true,
    notify_poi_updates: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registrati" />

        <h1 class="mb-6 text-xl font-bold text-pg-text">Crea account</h1>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Nome</label>
                <input id="name" v-model="form.name" type="text" class="pg-input w-full" required autofocus autocomplete="name" />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" v-model="form.email" type="email" class="pg-input w-full" required autocomplete="username" />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                <input id="password" v-model="form.password" type="password" class="pg-input w-full" required autocomplete="new-password" />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium">Conferma password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="pg-input w-full" required autocomplete="new-password" />
                <InputError class="mt-1" :message="form.errors.password_confirmation" />
            </div>

            <div class="space-y-3 rounded-xl bg-gray-50 p-4 text-sm">
                <label class="flex items-start gap-2">
                    <input v-model="form.accept_terms" type="checkbox" class="mt-1 rounded text-pg-primary" required />
                    <span>
                        Accetto i
                        <Link :href="route('legal.show', 'termini')" class="text-pg-primary underline" target="_blank">Termini di utilizzo</Link>
                        *
                    </span>
                </label>
                <InputError :message="form.errors.accept_terms" />

                <label class="flex items-start gap-2">
                    <input v-model="form.accept_privacy" type="checkbox" class="mt-1 rounded text-pg-primary" required />
                    <span>
                        Accetto l'
                        <Link :href="route('legal.show', 'privacy')" class="text-pg-primary underline" target="_blank">Informativa privacy</Link>
                        *
                    </span>
                </label>
                <InputError :message="form.errors.accept_privacy" />

                <hr class="border-gray-200" />

                <p class="text-xs font-medium text-pg-muted">Comunicazioni email (facoltative)</p>

                <label class="flex items-start gap-2">
                    <input v-model="form.notify_contributions" type="checkbox" class="mt-1 rounded text-pg-primary" />
                    <span>Inviami email sui miei contributi (es. «Il tuo luogo è stato pubblicato»)</span>
                </label>

                <label class="flex items-start gap-2">
                    <input v-model="form.notify_poi_updates" type="checkbox" class="mt-1 rounded text-pg-primary" />
                    <span>Inviami email quando un luogo che ho segnalato viene aggiornato</span>
                </label>
            </div>

            <div class="flex items-center justify-between pt-2">
                <Link :href="route('login')" class="text-sm text-pg-primary underline">Hai già un account?</Link>
                <button type="submit" class="pg-btn-primary" :disabled="form.processing">Registrati</button>
            </div>
        </form>
    </GuestLayout>
</template>
