<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({ password: '' });

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="text-lg font-semibold text-pg-error">Elimina account</h2>
            <p class="mt-1 text-sm text-pg-muted">
                L'account e tutti i dati associati verranno eliminati in modo permanente.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">Elimina account</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-pg-text">Confermi l'eliminazione?</h2>
                <p class="mt-2 text-sm text-pg-muted">Inserisci la password per confermare.</p>
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="pg-input mt-4"
                    placeholder="Password"
                    @keyup.enter="deleteUser"
                />
                <InputError :message="form.errors.password" class="mt-2" />
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">Annulla</SecondaryButton>
                    <DangerButton :disabled="form.processing" @click="deleteUser">Elimina</DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
