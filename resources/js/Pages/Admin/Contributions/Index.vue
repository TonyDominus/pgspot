<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    contributions: Object,
    filters: Object,
    pendingCount: Number,
});

const localFilters = reactive({
    status: props.filters?.status ?? '',
    type: props.filters?.type ?? '',
    user: props.filters?.user ?? '',
    municipality: props.filters?.municipality ?? '',
    from: props.filters?.from ?? '',
    to: props.filters?.to ?? '',
    duplicates: props.filters?.duplicates ?? '',
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

        <form class="mb-4 grid gap-2 sm:grid-cols-4" @submit.prevent="router.get(route('admin.contributions.index'), localFilters)">
            <select v-model="localFilters.type" class="pg-input text-sm">
                <option value="">Tutti i tipi</option>
                <option value="new_poi">Nuovo spot</option>
                <option value="edit">Modifica</option>
                <option value="photo">Foto</option>
                <option value="report">Segnalazione</option>
            </select>
            <input v-model="localFilters.user" class="pg-input text-sm" placeholder="Utente" />
            <input v-model="localFilters.municipality" class="pg-input text-sm" placeholder="Comune" />
            <input v-model="localFilters.from" type="date" class="pg-input text-sm" />
            <input v-model="localFilters.to" type="date" class="pg-input text-sm" />
            <label class="flex items-center gap-2 text-sm">
                <input v-model="localFilters.duplicates" type="checkbox" true-value="1" false-value="" />
                Possibili duplicati
            </label>
            <button type="submit" class="pg-btn-outline text-sm">Filtra</button>
        </form>

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
                            da {{ c.user?.name }} ({{ c.user?.email }})
                            <span v-if="c.user?.is_trusted_contributor"> · affidabile</span>
                            · {{ new Date(c.created_at).toLocaleString('it-IT') }}
                        </p>
                        <p v-if="c.poi" class="text-xs text-pg-muted">
                            Luogo:
                            <Link :href="route('admin.pois.edit', c.poi.id)" class="text-pg-primary">{{ c.poi.name }}</Link>
                        </p>
                        <p v-if="c.has_duplicates" class="text-xs text-amber-700">Possibile duplicato</p>
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
                    <p v-if="c.municipality_name" class="text-xs text-pg-muted">Comune: {{ c.municipality_name }}</p>
                    <ul v-if="c.payload.changes" class="mt-2 space-y-1 text-xs">
                        <li v-for="(change, field) in c.payload.changes" :key="field">
                            {{ field }}: {{ change.from ?? '—' }} → {{ change.to ?? '—' }}
                        </li>
                    </ul>
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
