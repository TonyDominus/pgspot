<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    tab: String,
    regions: Array,
    provinces: Array,
    municipalities: Object,
    filters: Object,
});

const regionFilter = ref(props.filters.region_id ? String(props.filters.region_id) : '');
const provinceFilter = ref(props.filters.province_id ? String(props.filters.province_id) : '');

const provinceOptions = computed(() => {
    if (!regionFilter.value) return props.provinces;
    return props.provinces.filter((province) => String(province.region_id) === regionFilter.value);
});

const typeLabel = {
    province: 'Provincia',
    autonomous_province: 'Provincia autonoma',
    metropolitan_city: 'Città metropolitana',
    free_municipal_consortium: 'Libero consorzio',
    non_administrative_unit: 'Unità non amministrativa',
};

function municipalityQuery(extra = {}) {
    return {
        tab: 'municipalities',
        q: extra.q ?? props.filters.q ?? undefined,
        region_id: regionFilter.value || undefined,
        province_id: provinceFilter.value || undefined,
        include_inactive: props.filters.include_inactive ? 1 : undefined,
        ...extra,
    };
}
function openTab(tab) {
    router.get(route('admin.territories.index'), { tab }, { preserveState: false });
}

function applyMunicipalityFilters(event) {
    const query = event?.target?.q?.value ?? props.filters.q;
    router.get(route('admin.territories.index'), municipalityQuery({ q: query || undefined }), { preserveState: true });
}

function toggleInactive(event) {
    router.get(route('admin.territories.index'), municipalityQuery({
        include_inactive: event.target.checked ? 1 : undefined,
    }), { preserveState: true });
}

function onRegionFilter(event) {
    regionFilter.value = event.target.value;
    if (provinceFilter.value && !provinceOptions.value.some((province) => String(province.id) === provinceFilter.value)) {
        provinceFilter.value = '';
    }
    applyMunicipalityFilters();
}
</script>

<template>
    <Head title="Territori" />

    <AdminShell>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-pg-text">Territori</h1>
                <p class="text-sm text-pg-muted">Anagrafica geografica. Un comune può esistere senza pagine pubbliche.</p>
            </div>
        </div>

        <p class="mb-4 text-xs text-pg-muted">
            L'eliminazione non è disponibile: regioni, province e comuni già usati restano in anagrafica.
        </p>

        <div class="mb-4 flex gap-2">
            <button type="button" class="rounded-full px-4 py-2 text-sm font-medium" :class="tab === 'regions' ? 'bg-pg-primary text-white' : 'bg-gray-100 text-pg-muted'" @click="openTab('regions')">Regioni</button>
            <button type="button" class="rounded-full px-4 py-2 text-sm font-medium" :class="tab === 'provinces' ? 'bg-pg-primary text-white' : 'bg-gray-100 text-pg-muted'" @click="openTab('provinces')">Province</button>
            <button type="button" class="rounded-full px-4 py-2 text-sm font-medium" :class="tab === 'municipalities' ? 'bg-pg-primary text-white' : 'bg-gray-100 text-pg-muted'" @click="openTab('municipalities')">Comuni</button>
        </div>

        <section v-if="tab === 'regions'" class="space-y-4">
            <div class="flex justify-end">
                <Link :href="route('admin.territories.regions.create')" class="pg-btn-primary text-sm">Nuova regione</Link>
            </div>
            <div class="pg-card overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 text-xs uppercase text-pg-muted">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3">Province</th>
                            <th class="px-4 py-3 text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="region in regions" :key="region.id" class="border-b border-gray-50">
                            <td class="px-4 py-3 font-medium">{{ region.name }}</td>
                            <td class="px-4 py-3 text-pg-muted">{{ region.slug }}</td>
                            <td class="px-4 py-3">{{ region.provinces_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('admin.territories.regions.edit', region.id)" class="text-pg-primary hover:underline">Modifica</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-else-if="tab === 'provinces'" class="space-y-4">
            <div class="flex justify-end">
                <Link :href="route('admin.territories.provinces.create')" class="pg-btn-primary text-sm">Nuova provincia</Link>
            </div>
            <div class="pg-card overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-gray-100 text-xs uppercase text-pg-muted">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Regione</th>
                            <th class="px-4 py-3">Sigla</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Comuni</th>
                            <th class="px-4 py-3 text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="province in provinces" :key="province.id" class="border-b border-gray-50">
                            <td class="px-4 py-3 font-medium">{{ province.name }}</td>
                            <td class="px-4 py-3">{{ province.region?.name }}</td>
                            <td class="px-4 py-3">{{ province.code || '—' }}</td>
                            <td class="px-4 py-3">{{ typeLabel[province.type] || 'Provincia' }}</td>
                            <td class="px-4 py-3">{{ province.municipalities_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('admin.territories.provinces.edit', province.id)" class="text-pg-primary hover:underline">Modifica</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-else class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <form class="flex flex-wrap gap-2" @submit.prevent="applyMunicipalityFilters">
                    <input name="q" :value="filters.q" type="search" placeholder="Cerca comune..." class="pg-input text-sm" />
                    <select :value="regionFilter" class="pg-input text-sm" @change="onRegionFilter">
                        <option value="">Tutte le regioni</option>
                        <option v-for="region in regions" :key="region.id" :value="String(region.id)">{{ region.name }}</option>
                    </select>
                    <select v-model="provinceFilter" class="pg-input text-sm" @change="applyMunicipalityFilters()">
                        <option value="">Tutte le province</option>
                        <option v-for="province in provinceOptions" :key="province.id" :value="String(province.id)">{{ province.name }}</option>
                    </select>
                    <label class="flex items-center gap-2 text-sm text-pg-muted">
                        <input type="checkbox" :checked="filters.include_inactive" @change="toggleInactive" />
                        Includi non attivi
                    </label>
                    <button type="submit" class="pg-btn-outline text-sm">Cerca</button>
                </form>
                <Link :href="route('admin.territories.municipalities.create')" class="pg-btn-primary text-sm">Nuovo comune</Link>
            </div>

            <div class="pg-card overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="border-b border-gray-100 text-xs uppercase text-pg-muted">
                        <tr>
                            <th class="px-4 py-3">Comune</th>
                            <th class="px-4 py-3">Provincia</th>
                            <th class="px-4 py-3">Regione</th>
                            <th class="px-4 py-3">POI</th>
                            <th class="px-4 py-3">Indicizzabile</th>
                            <th class="px-4 py-3 text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="municipality in municipalities.data" :key="municipality.id" class="border-b border-gray-50">
                            <td class="px-4 py-3 font-medium">
                                {{ municipality.name }}
                                <span v-if="municipality.is_active === false" class="ml-2 text-xs text-pg-muted">Non attivo</span>
                            </td>
                            <td class="px-4 py-3">{{ municipality.province?.name }}</td>
                            <td class="px-4 py-3">{{ municipality.province?.region?.name }}</td>
                            <td class="px-4 py-3">{{ municipality.pois_count }}</td>
                            <td class="px-4 py-3">{{ municipality.is_indexable ? 'Sì' : 'No' }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('admin.territories.municipalities.edit', municipality.id)" class="text-pg-primary hover:underline">Modifica</Link>
                            </td>
                        </tr>
                        <tr v-if="!municipalities.data?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-pg-muted">Nessun comune.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="municipalities.links?.length > 3" class="flex flex-wrap justify-center gap-2">
                <Link
                    v-for="link in municipalities.links"
                    :key="link.label"
                    :href="link.url || undefined"
                    class="rounded-lg px-3 py-1 text-sm"
                    :class="link.active ? 'bg-pg-primary text-white' : 'bg-gray-100 text-pg-muted'"
                    v-html="link.label"
                />
            </div>
        </section>
    </AdminShell>
</template>
