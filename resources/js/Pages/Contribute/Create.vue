<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import AppShell from '@/Layouts/AppShell.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';
import InputError from '@/Components/InputError.vue';
import TerritoryCascade from '@/Components/Pg/TerritoryCascade.vue';

const props = defineProps({
    categories: Array,
    tags: Array,
    regions: Array,
    territory: Object,
    mapCenter: Object,
    reportPoi: Object,
    targetPoi: Object,
    intent: String,
});

const spot = computed(() => props.targetPoi ?? props.reportPoi);
const mode = ref(props.intent === 'report' || props.intent === 'edit' || props.intent === 'photo' ? props.intent : 'new_poi');
const tab = ref(mode.value === 'report' ? 'report' : 'add');
const photoPreview = ref(null);
const extraPreviews = ref([]);
const photoInput = ref(null);
const extraInput = ref(null);
const duplicates = ref([]);
const acknowledged = ref(false);
const selectedTags = ref([]);
const editFields = ref([]);
const editChoices = [
    { id: 'name', label: 'Nome' },
    { id: 'description', label: 'Descrizione' },
    { id: 'address', label: 'Indirizzo' },
    { id: 'category_id', label: 'Categoria' },
    { id: 'tag_ids', label: 'Tag' },
    { id: 'is_free', label: 'Gratuito' },
    { id: 'accessibility', label: 'Accessibilità' },
    { id: 'parking', label: 'Parcheggio' },
    { id: 'latitude', label: 'Posizione' },
];

const form = useForm({
    name: spot.value?.name ?? '',
    category_id: spot.value?.primary_category_id ?? '',
    description: spot.value?.description ?? '',
    address: spot.value?.address ?? '',
    latitude: spot.value?.latitude ?? props.mapCenter?.lat ?? 43.1107,
    longitude: spot.value?.longitude ?? props.mapCenter?.lng ?? 12.3908,
    notes: '',
    reason: '',
    duplicate_poi_id: '',
    caption: '',
    type: mode.value,
    photo: null,
    extra_photos: [],
    tag_ids: [],
    is_free: 'unknown',
    accessibility: 'unknown',
    parking: 'unknown',
    changes: {},
    region_id: spot.value?.region_id ?? props.territory?.region_id ?? null,
    province_id: spot.value?.province_id ?? props.territory?.province_id ?? null,
    municipality_id: spot.value?.municipality_id ?? props.territory?.municipality_id ?? null,
    poi_id: spot.value?.id ?? null,
});

const photoRequired = computed(() => mode.value === 'photo');

const pickerEl = ref(null);
let pickerMap = null;
let pickerMarker = null;

function movePicker(lat, lng, zoom) {
    form.latitude = lat;
    form.longitude = lng;
    pickerMarker?.setLatLng([lat, lng]);
    if (zoom) pickerMap?.setView([lat, lng], zoom);
}

function useMyLocation() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition((pos) => {
        movePicker(pos.coords.latitude, pos.coords.longitude, 16);
    });
}

onMounted(() => {
    if (!pickerEl.value) return;
    const lat = Number(form.latitude);
    const lng = Number(form.longitude);
    pickerMap = L.map(pickerEl.value, { zoomControl: false }).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19,
    }).addTo(pickerMap);
    pickerMarker = L.marker([lat, lng], {
        draggable: true,
        icon: L.divIcon({
            className: '',
            html: '<div style="width:18px;height:18px;border-radius:999px;background:#1d4ed8;border:2px solid #fff"></div>',
            iconSize: [18, 18],
            iconAnchor: [9, 9],
        }),
    }).addTo(pickerMap);
    pickerMarker.on('dragend', () => {
        const point = pickerMarker.getLatLng();
        form.latitude = point.lat;
        form.longitude = point.lng;
    });
    pickerMap.on('click', (event) => {
        movePicker(event.latlng.lat, event.latlng.lng);
    });
    setTimeout(() => pickerMap?.invalidateSize(), 200);
});

onUnmounted(() => {
    pickerMap?.remove();
});

function onPhotoChange(e) {
    const file = e.target.files?.[0];
    form.photo = file ?? null;
    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
    photoPreview.value = file ? URL.createObjectURL(file) : null;
}

function onExtraPhotosChange(e) {
    const files = Array.from(e.target.files ?? []);
    form.extra_photos = files;
    extraPreviews.value.forEach((url) => URL.revokeObjectURL(url));
    extraPreviews.value = files.map((f) => URL.createObjectURL(f));
}

function toggleTag(id) {
    if (selectedTags.value.includes(id)) {
        selectedTags.value = selectedTags.value.filter((item) => item !== id);
    } else if (selectedTags.value.length < 5) {
        selectedTags.value = [...selectedTags.value, id];
    }
    form.tag_ids = selectedTags.value;
}

function removePhoto() {
    form.photo = null;
    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
    photoPreview.value = null;
    if (photoInput.value) photoInput.value.value = '';
}

async function submit() {
    form.type = mode.value === 'new_poi' && tab.value === 'report' ? 'report' : mode.value;
    if (form.type === 'new_poi' && !acknowledged.value && form.name) {
        const params = new URLSearchParams({
            name: form.name,
            latitude: String(form.latitude),
            longitude: String(form.longitude),
            municipality_id: String(form.municipality_id ?? ''),
        });
        const response = await fetch(`${route('contribute.duplicates')}?${params}`, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (data.pois?.length) {
            duplicates.value = data.pois;
            return;
        }
    }
    if (form.type === 'edit' && spot.value) {
        const next = {};
        const include = (field, from, to) => {
            if (editFields.value.includes(field)) next[field] = { from, to };
        };
        include('name', spot.value.name, form.name);
        include('description', spot.value.description, form.description);
        include('address', spot.value.address, form.address);
        include('category_id', spot.value.primary_category_id, form.category_id);
        if (editFields.value.includes('latitude')) {
            next.latitude = { from: spot.value.latitude, to: form.latitude };
            next.longitude = { from: spot.value.longitude, to: form.longitude };
        }
        include('is_free', spot.value.is_free, form.is_free === 'yes' ? true : form.is_free === 'no' ? false : null);
        include('accessibility', spot.value.accessibility, form.accessibility);
        include('parking', spot.value.parking, form.parking);
        include('tag_ids', spot.value.tag_ids ?? [], form.tag_ids);
        if (!Object.keys(next).length) {
            form.setError('name', 'Scegli almeno un campo da correggere.');
            return;
        }
        form.changes = next;
    }
    form.post(route('contribute.store'), { forceFormData: true });
}

watch(tab, (val) => {
    if (val === 'report') {
        form.photo = null;
        form.extra_photos = [];
        photoPreview.value = null;
        extraPreviews.value = [];
    }
});
</script>

<template>
    <Head title="Aggiungi luogo" />

    <AppShell active-nav="explore">
        <header class="flex items-center gap-3 bg-pg-surface px-4 py-4 shadow-sm">
            <Link :href="route('home')" class="rounded-full p-1">
                <PgIcon name="back" class="h-6 w-6" />
            </Link>
            <h1 class="text-lg font-semibold">
                {{ mode === 'edit' ? 'Suggerisci modifica' : mode === 'photo' ? 'Aggiungi foto' : mode === 'report' ? 'Segnala problema' : 'Aggiungi spot' }}
            </h1>
        </header>

        <div v-if="mode === 'new_poi'" class="mx-4 mt-4 flex rounded-xl bg-gray-100 p-1">
            <button
                type="button"
                class="flex-1 rounded-lg py-2 text-sm font-medium transition"
                :class="tab === 'add' ? 'bg-pg-surface text-pg-primary shadow-sm' : 'text-pg-muted'"
                @click="tab = 'add'"
            >
                Aggiungi
            </button>
            <button
                type="button"
                class="flex-1 rounded-lg py-2 text-sm font-medium transition"
                :class="tab === 'report' ? 'bg-pg-surface text-pg-primary shadow-sm' : 'text-pg-muted'"
                @click="tab = 'report'"
            >
                Segnala problema
            </button>
        </div>

        <form class="space-y-4 px-4 py-6" @submit.prevent="submit">
            <div v-if="(mode === 'new_poi' && tab === 'add') || mode === 'photo'">
                <label class="mb-2 block text-sm font-medium">{{ mode === 'photo' ? 'Foto' : 'Foto (consigliata)' }}</label>
                <button
                    type="button"
                    class="flex h-40 w-full flex-col items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-gray-200 bg-pg-surface"
                    @click="photoInput?.click()"
                >
                    <img v-if="photoPreview" :src="photoPreview" alt="Anteprima" class="h-full w-full object-cover" />
                    <template v-else>
                        <PgIcon name="camera-add" class="mb-2 h-8 w-8 text-pg-muted" />
                        <span class="text-sm text-pg-muted">Tocca per aggiungere la foto</span>
                        <span class="text-xs text-pg-muted">JPG, PNG o WebP · max 5 MB</span>
                    </template>
                </button>
                <input ref="photoInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onPhotoChange" />
                <button v-if="photoPreview" type="button" class="mt-2 text-sm text-pg-error" @click="removePhoto">Rimuovi foto</button>
                <input v-if="mode === 'photo'" v-model="form.caption" class="pg-input mt-2" placeholder="Didascalia (facoltativa)" />
                <InputError :message="form.errors.photo" class="mt-1" />

                <div v-if="mode === 'new_poi'" class="mt-3">
                    <label class="mb-1 block text-xs font-medium text-pg-muted">Altre foto (facoltative, max 5)</label>
                    <button type="button" class="pg-btn-outline w-full text-sm" @click="extraInput?.click()">
                        + Aggiungi altre foto
                    </button>
                    <input ref="extraInput" type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="onExtraPhotosChange" />
                    <div v-if="extraPreviews.length" class="mt-2 flex gap-2 overflow-x-auto">
                        <img v-for="(src, i) in extraPreviews" :key="i" :src="src" class="h-16 w-16 shrink-0 rounded-lg object-cover" alt="" />
                    </div>
                </div>
            </div>

            <div v-if="mode === 'report'">
                <label class="mb-1 block text-sm font-medium">Motivo</label>
                <select v-model="form.reason" class="pg-input" required>
                    <option value="" disabled>Seleziona...</option>
                    <option value="wrong_info">Informazioni errate</option>
                    <option value="closed">Luogo chiuso o non più esistente</option>
                    <option value="wrong_position">Posizione errata</option>
                    <option value="duplicate">Duplicato</option>
                    <option value="inappropriate">Contenuto inappropriato</option>
                    <option value="other">Altro</option>
                </select>
                <input v-if="form.reason === 'duplicate'" v-model="form.duplicate_poi_id" class="pg-input mt-2" placeholder="ID del luogo duplicato (facoltativo)" />
            </div>

            <div v-if="mode === 'edit'" class="space-y-2">
                <p class="text-sm font-medium">Cosa vuoi correggere?</p>
                <label v-for="field in editChoices" :key="field.id" class="flex items-center gap-2 text-sm">
                    <input v-model="editFields" type="checkbox" :value="field.id" />
                    {{ field.label }}
                </label>
            </div>

            <div v-if="mode !== 'photo'">
                <label class="mb-1 block text-sm font-medium">Nome luogo</label>
                <input v-model="form.name" type="text" class="pg-input" required />
                <InputError :message="form.errors.name" />
            </div>

            <div v-if="mode === 'new_poi' || mode === 'edit'">
                <label class="mb-1 block text-sm font-medium">Categoria</label>
                <select v-model="form.category_id" class="pg-input" :required="mode === 'new_poi' && tab === 'add'">
                    <option value="" disabled>Seleziona...</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </select>
            </div>

            <div v-if="mode === 'new_poi' || mode === 'edit'">
                <label class="mb-1 block text-sm font-medium">Descrizione</label>
                <textarea v-model="form.description" rows="3" class="pg-input" placeholder="Facoltativa" />
            </div>

            <div v-if="mode === 'edit'">
                <label class="mb-1 block text-sm font-medium">Indirizzo</label>
                <input v-model="form.address" type="text" class="pg-input" />
            </div>

            <div v-if="mode === 'new_poi' && tab === 'add'">
                <p class="mb-2 text-sm font-medium">Cosa rende interessante questo spot?</p>
                <div class="mb-4 flex flex-wrap gap-2">
                    <button
                        v-for="tag in tags"
                        :key="tag.id"
                        type="button"
                        class="rounded-full px-3 py-1 text-sm"
                        :class="selectedTags.includes(tag.id) ? 'bg-pg-primary text-white' : 'bg-gray-100'"
                        @click="toggleTag(tag.id)"
                    >
                        {{ tag.name }}
                    </button>
                </div>
                <label class="mb-1 block text-sm font-medium">Gratuito?</label>
                <select v-model="form.is_free" class="pg-input mb-3">
                    <option value="unknown">Non so</option>
                    <option value="yes">Sì</option>
                    <option value="no">No</option>
                </select>
                <label class="mb-1 block text-sm font-medium">Accessibilità</label>
                <select v-model="form.accessibility" class="pg-input mb-3">
                    <option value="unknown">Non so</option>
                    <option value="yes">Sì</option>
                    <option value="partial">Parziale</option>
                    <option value="no">No</option>
                </select>
                <label class="mb-1 block text-sm font-medium">Parcheggio</label>
                <select v-model="form.parking" class="pg-input mb-3">
                    <option value="unknown">Non so</option>
                    <option value="on_site">Sul posto</option>
                    <option value="nearby">Vicino</option>
                    <option value="paid">A pagamento</option>
                    <option value="none">Nessuno</option>
                </select>
            </div>

            <div v-if="duplicates.length" class="rounded-xl bg-amber-50 p-3 text-sm">
                <p class="font-medium">Potrebbe già essere presente</p>
                <Link v-for="item in duplicates" :key="item.id" :href="route('poi.show', item.slug)" class="mt-1 block text-pg-primary">{{ item.name }}</Link>
                <button type="button" class="mt-2 text-sm font-medium" @click="acknowledged = true; submit()">Continua comunque</button>
            </div>

            <div v-if="tab === 'add' && mode === 'new_poi'">
                <p class="mb-2 text-sm font-medium">Comune</p>
                <TerritoryCascade
                    :regions="regions"
                    v-model:region-id="form.region_id"
                    v-model:province-id="form.province_id"
                    v-model:municipality-id="form.municipality_id"
                    :errors="form.errors"
                />
            </div>

            <div v-if="mode !== 'photo'">
                <div class="mb-1 flex items-center justify-between">
                    <label class="text-sm font-medium">Posizione</label>
                    <button type="button" class="text-xs font-medium text-pg-primary" @click="useMyLocation">
                        La mia posizione
                    </button>
                </div>
                <div ref="pickerEl" class="mb-2 h-56 w-full overflow-hidden rounded-2xl" />
                <div class="grid grid-cols-2 gap-2">
                    <input v-model="form.latitude" type="number" step="any" class="pg-input" placeholder="Latitudine" required />
                    <input v-model="form.longitude" type="number" step="any" class="pg-input" placeholder="Longitudine" required />
                </div>
            </div>

            <div v-if="mode === 'report' || (mode === 'new_poi' && tab === 'report')">
                <label class="mb-1 block text-sm font-medium">Note</label>
                <textarea v-model="form.notes" rows="2" class="pg-input" :required="form.reason === 'other'" placeholder="Dettagli della segnalazione" />
            </div>

            <button type="submit" class="pg-btn-primary w-full py-4" :disabled="form.processing || (photoRequired && !form.photo)">
                Invia
            </button>
        </form>
    </AppShell>
</template>
