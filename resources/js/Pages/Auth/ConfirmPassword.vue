<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

const submit = () => {
    form.post(route('password.confirm'), { onFinish: () => form.reset() });
};
</script>

<template>
    <GuestLayout>
        <Head title="Conferma password" />

        <p class="mb-4 text-sm text-pg-muted">
            Area protetta. Conferma la password per continuare.
        </p>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                <input id="password" v-model="form.password" type="password" class="pg-input w-full" required autofocus />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>
            <button type="submit" class="pg-btn-primary" :disabled="form.processing">Conferma</button>
        </form>
    </GuestLayout>
</template>
