<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reimposta password" />

        <h1 class="mb-6 text-xl font-bold text-pg-text">Nuova password</h1>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" v-model="form.email" type="email" class="pg-input w-full" required autofocus />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Nuova password</label>
                <input id="password" v-model="form.password" type="password" class="pg-input w-full" required />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium">Conferma password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="pg-input w-full" required />
                <InputError class="mt-1" :message="form.errors.password_confirmation" />
            </div>
            <button type="submit" class="pg-btn-primary w-full" :disabled="form.processing">Reimposta password</button>
        </form>
    </GuestLayout>
</template>
