<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
    roles: Array,
});

const editingId = ref(null);
const editForm = useForm({
    name: '',
    email: '',
    role: '',
    is_trusted_contributor: false,
});

function startEdit(user) {
    editingId.value = user.id;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role = user.role;
    editForm.is_trusted_contributor = user.is_trusted_contributor;
}

function saveUser(id) {
    editForm.put(route('admin.users.update', id), {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
}

function destroyUser(id) {
    if (!confirm('Eliminare questo utente?')) return;
    router.delete(route('admin.users.destroy', id), { preserveScroll: true });
}

function roleLabel(value) {
    return props.roles.find((r) => r.value === value)?.label ?? value;
}
</script>

<template>
    <Head title="Gestione utenti" />

    <AdminShell>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-pg-text">Utenti</h1>
            <p class="text-sm text-pg-muted">Monitora e gestisci tutti gli account (solo Super Admin)</p>
        </div>

        <form
            class="mb-4 flex flex-wrap gap-2"
            @submit.prevent="router.get(route('admin.users.index'), { q: $event.target.q.value, role: filters.role })"
        >
            <input name="q" :value="filters.q" type="search" placeholder="Cerca nome o email..." class="pg-input text-sm" />
            <select
                name="role"
                :value="filters.role"
                class="pg-input text-sm"
                @change="router.get(route('admin.users.index'), { ...filters, role: $event.target.value || undefined })"
            >
                <option value="">Tutti i ruoli</option>
                <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
            </select>
        </form>

        <div class="pg-card overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-gray-100 text-xs uppercase text-pg-muted">
                    <tr>
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Ruolo</th>
                        <th class="px-4 py-3">Registrato</th>
                        <th class="px-4 py-3 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users.data" :key="user.id" class="border-b border-gray-50">
                        <template v-if="editingId === user.id">
                            <td class="px-4 py-3" colspan="5">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <input v-model="editForm.name" class="pg-input text-sm" />
                                    <input v-model="editForm.email" type="email" class="pg-input text-sm" />
                                    <select v-model="editForm.role" class="pg-input text-sm">
                                        <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                                    </select>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input v-model="editForm.is_trusted_contributor" type="checkbox" class="rounded text-pg-primary" />
                                        Contributore fidato
                                    </label>
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <button type="button" class="pg-btn-primary text-sm" @click="saveUser(user.id)">Salva</button>
                                    <button type="button" class="text-sm text-pg-muted" @click="editingId = null">Annulla</button>
                                </div>
                            </td>
                        </template>
                        <template v-else>
                            <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                            <td class="px-4 py-3">{{ user.email }}</td>
                            <td class="px-4 py-3">{{ roleLabel(user.role) }}</td>
                            <td class="px-4 py-3 text-xs text-pg-muted">{{ new Date(user.created_at).toLocaleDateString('it-IT') }}</td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" class="mr-3 text-pg-primary hover:underline" @click="startEdit(user)">Modifica</button>
                                <button type="button" class="text-pg-error hover:underline" @click="destroyUser(user.id)">Elimina</button>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminShell>
</template>
