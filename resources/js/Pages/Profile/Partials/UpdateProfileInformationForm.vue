<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-semibold text-pg-text">Informazioni profilo</h2>
            <p class="mt-1 text-sm text-pg-muted">Aggiorna nome e email del tuo account.</p>
        </header>

        <form class="mt-6 space-y-5" @submit.prevent="form.patch(route('profile.update'))">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-pg-text">Nome</label>
                <input id="name" v-model="form.name" type="text" class="pg-input" required autofocus autocomplete="name" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-pg-text">Email</label>
                <input id="email" v-model="form.email" type="email" class="pg-input" required autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm text-pg-muted">
                    Email non verificata.
                    <Link :href="route('verification.send')" method="post" as="button" class="font-medium text-pg-primary underline">
                        Invia di nuovo il link
                    </Link>
                </p>
                <p v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-pg-success">
                    Link inviato!
                </p>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Salva</PrimaryButton>
                <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-pg-success">Salvato.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
