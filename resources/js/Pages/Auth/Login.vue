<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Accedi" />

        <h1 class="mb-6 text-xl font-bold text-pg-text">Accedi</h1>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">{{ status }}</div>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" v-model="form.email" type="email" class="pg-input w-full" required autofocus autocomplete="username" />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                <input id="password" v-model="form.password" type="password" class="pg-input w-full" required autocomplete="current-password" />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <label class="flex items-center gap-2 text-sm text-pg-muted">
                <Checkbox name="remember" v-model:checked="form.remember" />
                Ricordami
            </label>

            <div class="flex items-center justify-between pt-2">
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-pg-primary underline">
                    Password dimenticata?
                </Link>
                <button type="submit" class="pg-btn-primary ms-auto" :disabled="form.processing">Accedi</button>
            </div>

            <p class="text-center text-sm text-pg-muted">
                Non hai un account?
                <Link :href="route('register')" class="text-pg-primary underline">Registrati</Link>
            </p>
        </form>
    </GuestLayout>
</template>
