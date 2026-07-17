<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    contributions: Object,
    filters: Object,
    pendingCount: Number,
});

const rejectForm = useForm({ rejection_reason: '' });
const rejectingId = ref(null);

function approve(id) {
    router.post(route('admin.contributions.approve', id), {}, { preserveScroll: true });
}

function startReject(id) {
    rejectingId.value = id;
    rejectForm.rejection_reason = '';
}

function submitReject(id) {
    rejectForm.post(route('admin.contributions.reject', id), {
        preserveScroll: true,
        onSuccess: () => { rejectingId.value = null; },
    });
}

function typeLabel(type) {
    const map = { new_poi: 'Nuovo POI', edit: 'Modifica', photo: 'Foto', report: 'Segnalazione' };
    return map[type] ?? type;
}
</script>

<template>
    <Head title="Moderazione contributi" />

    <AdminShell>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-pg-text">Moderazione</h1>
            <p class="text-sm text-pg-muted">
                {{ pendingCount }} contributi in attesa di revisione
            </p>
        </div>

        <div class="mb-4 flex gap-2">
            <button
                type="button"
                class="rounded-full px-4 py-2 text-sm font-medium"
                :class="!filters.status ? 'bg-pg-primary text-white' : 'bg-gray-100 text-pg-muted'"
                @click="router.get(route('admin.contributions.index'))"
            >
                Tutti
            </button>
            <button
                type="button"
                class="rounded-full px-4 py-2 text-sm font-medium"
                :class="filters.status === 'pending' ? 'bg-pg-warning text-white' : 'bg-gray-100 text-pg-muted'"
                @click="router.get(route('admin.contributions.index'), { status: 'pending' })"
            >
                In attesa
            </button>
        </div>

        <div class="space-y-4">
            <article v-for="c in contributions.data" :key="c.id" class="pg-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium capitalize">{{ c.status }}</span>
                        <span class="ml-2 text-xs text-pg-muted">{{ typeLabel(c.type) }}</span>
                        <p class="mt-2 font-semibold text-pg-text">
                            {{ c.payload?.name ?? c.poi?.name ?? 'Contributo' }}
                        </p>
                        <p class="text-sm text-pg-muted">
                            da {{ c.user?.name }} ({{ c.user?.email }}) · {{ new Date(c.created_at).toLocaleString('it-IT') }}
                        </p>
                    </div>
                    <div v-if="c.status === 'pending'" class="flex gap-2">
                        <button type="button" class="pg-btn-primary text-sm" @click="approve(c.id)">Approva</button>
                        <button type="button" class="pg-btn-outline text-sm text-pg-error" @click="startReject(c.id)">Rifiuta</button>
                    </div>
                </div>

                <div v-if="c.payload" class="mt-3 rounded-xl bg-gray-50 p-3 text-sm">
                    <img
                        v-if="c.photo_preview_url"
                        :src="c.photo_preview_url"
                        :alt="c.payload.name"
                        class="mb-3 max-h-48 w-full rounded-lg object-cover"
                    />
                    <p v-if="c.payload.description">{{ c.payload.description }}</p>
                    <p v-if="c.payload.latitude" class="text-xs text-pg-muted">
                        {{ c.payload.latitude }}, {{ c.payload.longitude }}
                    </p>
                </div>

                <div v-if="rejectingId === c.id" class="mt-3 border-t border-gray-100 pt-3">
                    <textarea v-model="rejectForm.rejection_reason" rows="2" class="pg-input text-sm" placeholder="Motivo del rifiuto (opzionale)" />
                    <div class="mt-2 flex gap-2">
                        <button type="button" class="pg-btn-primary text-sm" @click="submitReject(c.id)">Conferma rifiuto</button>
                        <button type="button" class="text-sm text-pg-muted" @click="rejectingId = null">Annulla</button>
                    </div>
                </div>

                <p v-if="c.rejection_reason" class="mt-2 text-sm text-pg-error">Motivo: {{ c.rejection_reason }}</p>
            </article>

            <p v-if="!contributions.data?.length" class="py-12 text-center text-sm text-pg-muted">Nessun contributo.</p>
        </div>
    </AdminShell>
</template>
