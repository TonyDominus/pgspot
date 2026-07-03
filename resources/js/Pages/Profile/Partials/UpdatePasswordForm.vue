<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-semibold text-pg-text">Cambia password</h2>
            <p class="mt-1 text-sm text-pg-muted">Usa una password lunga e sicura.</p>
        </header>

        <form class="mt-6 space-y-5" @submit.prevent="updatePassword">
            <div>
                <label for="current_password" class="mb-1 block text-sm font-medium">Password attuale</label>
                <input id="current_password" ref="currentPasswordInput" v-model="form.current_password" type="password" class="pg-input" autocomplete="current-password" />
                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Nuova password</label>
                <input id="password" ref="passwordInput" v-model="form.password" type="password" class="pg-input" autocomplete="new-password" />
                <InputError :message="form.errors.password" class="mt-2" />
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium">Conferma password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="pg-input" autocomplete="new-password" />
                <InputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>
            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Aggiorna</PrimaryButton>
                <p v-if="form.recentlySuccessful" class="text-sm text-pg-success">Aggiornata.</p>
            </div>
        </form>
    </section>
</template>
