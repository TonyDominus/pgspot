<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ status: String });

const form = useForm({ email: '' });
const submit = () => form.post(route('password.email'));
</script>

<template>
    <GuestLayout>
        <Head title="Password dimenticata" />

        <h1 class="mb-4 text-xl font-bold text-pg-text">Password dimenticata</h1>
        <p class="mb-4 text-sm text-pg-muted">
            Inserisci la tua email e ti invieremo un link per reimpostare la password.
        </p>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">{{ status }}</div>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" v-model="form.email" type="email" class="pg-input w-full" required autofocus />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>
            <div class="flex justify-between gap-3">
                <Link :href="route('login')" class="text-sm text-pg-primary underline">Torna al login</Link>
                <button type="submit" class="pg-btn-primary" :disabled="form.processing">Invia link</button>
            </div>
        </form>
    </GuestLayout>
</template>
