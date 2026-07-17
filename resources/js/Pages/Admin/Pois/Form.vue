<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import PoiThumbnail from '@/Components/Pg/PoiThumbnail.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    poi: Object,
    categories: Array,
    statuses: Array,
});

const form = useForm({
    name: props.poi.name,
    description: props.poi.description ?? '',
    latitude: props.poi.latitude,
    longitude: props.poi.longitude,
    address: props.poi.address ?? '',
    status: props.poi.status,
    category_ids: props.poi.categories?.map((c) => c.id) ?? [],
});

const photoForm = useForm({ photo: null, is_primary: false });
const photoInput = ref(null);

function submit() {
    form.put(route('admin.pois.update', props.poi.id));
}

function pickPhoto() {
    photoInput.value?.click();
}

function uploadPhoto(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    photoForm.photo = file;
    photoForm.post(route('admin.pois.photos.store', props.poi.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            photoForm.reset();
            if (photoInput.value) photoInput.value.value = '';
        },
    });
}

function setPrimary(photoId) {
    router.post(route('admin.pois.photos.primary', [props.poi.id, photoId]), {}, { preserveScroll: true });
}

function deletePhoto(photoId) {
    if (!confirm('Eliminare questa foto?')) return;
    router.delete(route('admin.pois.photos.destroy', [props.poi.id, photoId]), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Modifica ${poi.name}`" />

    <AdminShell>
        <div class="mb-6">
            <Link :href="route('admin.pois.index')" class="text-sm text-pg-primary">← Torna alla lista</Link>
            <h1 class="mt-2 text-2xl font-bold text-pg-text">Modifica POI</h1>
        </div>

        <div class="mx-auto max-w-2xl space-y-5">
            <section class="pg-card p-6">
                <h2 class="mb-4 font-semibold text-pg-text">Foto del luogo</h2>

                <div class="mb-4 overflow-hidden rounded-2xl">
                    <div class="aspect-[16/9] w-full">
                        <PoiThumbnail :poi="poi" class="h-full w-full" />
                    </div>
                    <p v-if="!poi.photos?.length" class="mt-2 text-xs text-pg-muted">
                        Nessuna foto — viene mostrato il placeholder colorato per categoria.
                    </p>
                </div>

                <div v-if="poi.photos?.length" class="mb-4 grid grid-cols-3 gap-2 sm:grid-cols-4">
                    <div
                        v-for="photo in poi.photos"
                        :key="photo.id"
                        class="group relative overflow-hidden rounded-xl border-2"
                        :class="photo.is_primary ? 'border-pg-primary' : 'border-transparent'"
                    >
                        <img :src="photo.url" :alt="poi.name" class="aspect-square w-full object-cover" />
                        <span
                            v-if="photo.is_primary"
                            class="absolute left-1 top-1 rounded bg-pg-primary px-1.5 py-0.5 text-[10px] font-bold text-white"
                        >
                            Principale
                        </span>
                        <div class="absolute inset-x-0 bottom-0 flex gap-1 bg-black/60 p-1 opacity-0 transition group-hover:opacity-100">
                            <button
                                v-if="!photo.is_primary"
                                type="button"
                                class="flex-1 rounded bg-white/90 py-0.5 text-[10px] font-medium"
                                @click="setPrimary(photo.id)"
                            >
                                Principale
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded bg-red-500 py-0.5 text-[10px] font-medium text-white"
                                @click="deletePhoto(photo.id)"
                            >
                                Elimina
                            </button>
                        </div>
                    </div>
                </div>

                <input ref="photoInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="uploadPhoto" />
                <button type="button" class="pg-btn-outline w-full text-sm" :disabled="photoForm.processing" @click="pickPhoto">
                    {{ photoForm.processing ? 'Caricamento...' : '+ Aggiungi foto' }}
                </button>
            </section>

            <form class="pg-card space-y-5 p-6" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium">Nome *</label>
                    <input v-model="form.name" type="text" class="pg-input" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Descrizione</label>
                    <textarea v-model="form.description" rows="4" class="pg-input" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Latitudine *</label>
                        <input v-model="form.latitude" type="number" step="any" class="pg-input" required />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Longitudine *</label>
                        <input v-model="form.longitude" type="number" step="any" class="pg-input" required />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Indirizzo</label>
                    <input v-model="form.address" type="text" class="pg-input" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Stato *</label>
                    <select v-model="form.status" class="pg-input" required>
                        <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Categorie</label>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="cat in categories"
                            :key="cat.id"
                            class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-sm"
                            :class="form.category_ids.includes(cat.id) ? 'border-pg-primary bg-pg-primary/5' : 'border-gray-200'"
                        >
                            <input v-model="form.category_ids" type="checkbox" :value="cat.id" class="rounded text-pg-primary" />
                            {{ cat.name }}
                        </label>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="pg-btn-primary flex-1" :disabled="form.processing">Salva</button>
                    <Link :href="route('admin.pois.index')" class="pg-btn-outline">Annulla</Link>
                </div>
            </form>
        </div>
    </AdminShell>
</template>
