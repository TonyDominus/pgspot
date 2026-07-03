<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    pois: { type: Array, default: () => [] },
    center: { type: Object, default: () => ({ lat: 43.1107, lng: 12.3908 }) },
    zoom: { type: Number, default: 14 },
    activeSlug: { type: String, default: null },
    sponsoredPoiIds: { type: Array, default: () => [] },
    class: { type: String, default: '' },
});

const emit = defineEmits(['select']);

const mapEl = ref(null);
let map = null;
let markers = [];

onMounted(() => {
    if (!mapEl.value) return;

    map = L.map(mapEl.value, {
        zoomControl: false,
        attributionControl: true,
    }).setView([props.center.lat, props.center.lng], props.zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19,
    }).addTo(map);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    renderMarkers();
});

onUnmounted(() => {
    map?.remove();
});

watch(() => props.pois, renderMarkers, { deep: true });
watch(() => props.activeSlug, highlightActive);

function markerColor(poi) {
    return poi.categories?.[0]?.color ?? '#2E7D32';
}

function renderMarkers() {
    if (!map) return;

    markers.forEach((m) => map.removeLayer(m));
    markers = [];

    props.pois.forEach((poi) => {
        const isSponsored = props.sponsoredPoiIds.includes(poi.id);
        const color = isSponsored ? '#FFB300' : markerColor(poi);
        const marker = L.circleMarker([Number(poi.latitude), Number(poi.longitude)], {
            radius: props.activeSlug === poi.slug ? 10 : isSponsored ? 9 : 7,
            color: '#fff',
            weight: isSponsored ? 3 : 2,
            fillColor: color,
            fillOpacity: 0.95,
        }).addTo(map);

        const label = isSponsored ? `★ ${poi.name}` : poi.name;
        marker.on('click', () => emit('select', poi));
        marker.bindTooltip(label, { direction: 'top', offset: [0, -8] });
        markers.push(marker);
    });
}

function highlightActive() {
    renderMarkers();
}

function flyTo(lat, lng) {
    map?.flyTo([lat, lng], 16, { duration: 0.8 });
}

defineExpose({ flyTo });
</script>

<template>
    <div ref="mapEl" class="h-full w-full" :class="class" />
</template>
