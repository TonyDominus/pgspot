<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Verifica email" />

        <h1 class="mb-4 text-xl font-bold text-pg-text">Verifica la tua email</h1>

        <p class="mb-4 text-sm text-pg-muted">
            Grazie per esserti registrato! Prima di iniziare, clicca sul link che ti abbiamo inviato via email.
            Se non l'hai ricevuta, possiamo inviartene un'altra.
        </p>

        <p v-if="verificationLinkSent" class="mb-4 text-sm font-medium text-green-600">
            Un nuovo link di verifica è stato inviato al tuo indirizzo email.
        </p>

        <form @submit.prevent="submit">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <button type="submit" class="pg-btn-primary" :disabled="form.processing">
                    Reinvia email di verifica
                </button>
                <Link :href="route('logout')" method="post" as="button" class="text-sm text-pg-muted underline">
                    Esci
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
