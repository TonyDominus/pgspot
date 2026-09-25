<script setup>
import InputError from '@/Components/InputError.vue';
import { onMounted, ref } from 'vue';

const regionId = defineModel('regionId', { default: null });
const provinceId = defineModel('provinceId', { default: null });
const municipalityId = defineModel('municipalityId', { default: null });

defineProps({
    regions: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

const provinces = ref([]);
const municipalities = ref([]);
const loadingProvinces = ref(false);
const loadingMunicipalities = ref(false);

function num(value) {
    if (value === null || value === undefined || value === '') return null;
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
}

async function loadProvinces(id, keepSelection) {
    if (!keepSelection) {
        provinceId.value = null;
        municipalityId.value = null;
        municipalities.value = [];
    }
    provinces.value = [];
    if (!id) return;

    loadingProvinces.value = true;
    try {
        const response = await fetch(`${route('territories.provinces')}?region_id=${id}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        provinces.value = response.ok ? await response.json() : [];
    } finally {
        loadingProvinces.value = false;
    }
}

async function loadMunicipalities(id, keepSelection) {
    if (!keepSelection) municipalityId.value = null;
    municipalities.value = [];
    if (!id) return;

    loadingMunicipalities.value = true;
    try {
        const response = await fetch(`${route('territories.municipalities')}?province_id=${id}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        municipalities.value = response.ok ? await response.json() : [];
    } finally {
        loadingMunicipalities.value = false;
    }
}

function onRegion(event) {
    regionId.value = num(event.target.value);
    loadProvinces(regionId.value, false);
}

function onProvince(event) {
    provinceId.value = num(event.target.value);
    loadMunicipalities(provinceId.value, false);
}

function onMunicipality(event) {
    municipalityId.value = num(event.target.value);
}

onMounted(async () => {
    regionId.value = num(regionId.value);
    provinceId.value = num(provinceId.value);
    municipalityId.value = num(municipalityId.value);
    if (regionId.value) await loadProvinces(regionId.value, true);
    if (provinceId.value) await loadMunicipalities(provinceId.value, true);
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label class="mb-1 block text-sm font-medium">Regione *</label>
            <select class="pg-input" :value="regionId ?? ''" required @change="onRegion">
                <option value="">Seleziona</option>
                <option v-for="region in regions" :key="region.id" :value="region.id">{{ region.name }}</option>
            </select>
            <InputError :message="errors.region_id" class="mt-1" />
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Provincia *</label>
            <select class="pg-input" :value="provinceId ?? ''" :disabled="!regionId || loadingProvinces" required @change="onProvince">
                <option value="">{{ loadingProvinces ? 'Caricamento...' : 'Seleziona' }}</option>
                <option v-for="province in provinces" :key="province.id" :value="province.id">
                    {{ province.name }}<template v-if="province.code"> ({{ province.code }})</template>
                </option>
            </select>
            <InputError :message="errors.province_id" class="mt-1" />
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Comune *</label>
            <select class="pg-input" :value="municipalityId ?? ''" :disabled="!provinceId || loadingMunicipalities" required @change="onMunicipality">
                <option value="">{{ loadingMunicipalities ? 'Caricamento...' : 'Seleziona' }}</option>
                <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                    {{ municipality.name }}
                </option>
            </select>
            <InputError :message="errors.municipality_id" class="mt-1" />
        </div>
    </div>
</template>
