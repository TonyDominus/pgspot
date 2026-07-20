<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import NotificationPreferencesForm from './Partials/NotificationPreferencesForm.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const roleLabel = computed(() => ({
    user: 'Utente',
    admin: 'Admin',
    superadmin: 'Super Admin',
}[user.value?.role] ?? 'Utente'));
</script>

<template>
    <Head title="Profilo" />

    <AppShell active-nav="profile" v-slot="{ openMenu }">
        <header class="flex items-center gap-3 bg-pg-surface px-4 py-5 shadow-sm lg:rounded-2xl lg:shadow-card">
            <button type="button" class="rounded-full p-2 hover:bg-gray-100 lg:hidden" @click="openMenu">
                <PgIcon name="menu" class="h-5 w-5" />
            </button>
            <div class="flex-1">
                <h1 class="text-xl font-bold text-pg-text">Profilo</h1>
                <p class="text-sm text-pg-muted">Gestisci il tuo account</p>
            </div>
        </header>

        <div class="mx-auto mt-4 max-w-2xl space-y-4 px-4 pb-8 lg:px-0">
            <div class="pg-card flex items-center gap-4 p-5">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-pg-primary/10 text-xl font-bold text-pg-primary">
                    {{ user?.name?.charAt(0)?.toUpperCase() }}
                </div>
                <div>
                    <p class="font-semibold text-pg-text">{{ user?.name }}</p>
                    <p class="text-sm text-pg-muted">{{ user?.email }}</p>
                    <span class="mt-1 inline-block rounded-full bg-pg-primary/10 px-2 py-0.5 text-xs font-medium text-pg-primary">
                        {{ roleLabel }}
                    </span>
                </div>
            </div>

            <div class="pg-card p-5 sm:p-6">
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </div>

            <div class="pg-card p-5 sm:p-6">
                <NotificationPreferencesForm />
            </div>

            <div class="pg-card p-5 sm:p-6">
                <UpdatePasswordForm />
            </div>

            <div class="pg-card p-5 sm:p-6">
                <DeleteUserForm />
            </div>
        </div>
    </AppShell>
</template>
