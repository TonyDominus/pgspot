<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';

const props = defineProps({
    pois: { type: Array, default: () => [] },
    center: { type: Object, default: () => ({ lat: 43.1107, lng: 12.3908 }) },
    zoom: { type: Number, default: 14 },
    activeSlug: { type: String, default: null },
    sponsoredPoiIds: { type: Array, default: () => [] },
    userLocation: { type: Object, default: null },
    numbered: { type: Boolean, default: false },
    showLine: { type: Boolean, default: false },
    fitStops: { type: Boolean, default: false },
    cluster: { type: Boolean, default: false },
    class: { type: String, default: '' },
});

const emit = defineEmits(['select', 'moveend']);

const mapEl = ref(null);
const mapStyle = ref('standard');
let map = null;
let markers = [];
let markerBySlug = new Map();
let tileLayer = null;
let userMarker = null;
let line = null;
let clusterGroup = null;
let moveTimer = null;

const tileLayers = {
    standard: {
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '&copy; OpenStreetMap',
        label: 'Mappa',
    },
    terrain: {
        url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
        attribution: '&copy; OpenTopoMap',
        label: 'Rilievo',
    },
};

function usesCluster() {
    return props.cluster && !props.numbered;
}

onMounted(() => {
    if (!mapEl.value) return;

    map = L.map(mapEl.value, {
        zoomControl: false,
        attributionControl: true,
    }).setView([props.center.lat, props.center.lng], props.zoom);

    setTileLayer(mapStyle.value);

    L.control.zoom({ position: 'bottomright' }).addTo(map);
    map.on('moveend', scheduleBounds);

    renderMarkers();
    renderUser();
    scheduleBounds();
});

onUnmounted(() => {
    clearTimeout(moveTimer);
    clearMarkerLayer();
    if (clusterGroup && map?.hasLayer(clusterGroup)) {
        map.removeLayer(clusterGroup);
    }
    clusterGroup?.off();
    clusterGroup = null;
    map?.remove();
    map = null;
});

watch(() => props.pois, renderMarkers, { deep: true });
watch(() => props.activeSlug, highlightActive);
watch(() => props.userLocation, renderUser, { deep: true });
watch(() => [props.cluster, props.numbered], renderMarkers);

function scheduleBounds() {
    clearTimeout(moveTimer);
    moveTimer = setTimeout(() => {
        if (!map) return;
        const bounds = map.getBounds();
        emit('moveend', {
            south: bounds.getSouth(),
            north: bounds.getNorth(),
            west: bounds.getWest(),
            east: bounds.getEast(),
        });
    }, 350);
}

function setTileLayer(style) {
    if (!map) return;
    if (tileLayer) map.removeLayer(tileLayer);
    const cfg = tileLayers[style];
    tileLayer = L.tileLayer(cfg.url, {
        attribution: cfg.attribution,
        maxZoom: style === 'terrain' ? 17 : 19,
    }).addTo(map);
}

function switchStyle(style) {
    mapStyle.value = style;
    setTileLayer(style);
}

function markerColor(poi) {
    return poi.primary_category?.color ?? '#64748b';
}

function renderUser() {
    if (!map) return;
    if (userMarker) {
        map.removeLayer(userMarker);
        userMarker = null;
    }
    if (!props.userLocation) return;
    userMarker = L.circleMarker([props.userLocation.lat, props.userLocation.lng], {
        radius: 7,
        color: '#1d4ed8',
        weight: 2,
        fillColor: '#3b82f6',
        fillOpacity: 0.9,
    }).addTo(map);
    userMarker.bindTooltip('La tua posizione', { direction: 'top' });
}

function clearMarkerLayer() {
    markers.forEach((marker) => {
        marker.off();
        if (clusterGroup?.hasLayer(marker)) {
            clusterGroup.removeLayer(marker);
        } else if (map?.hasLayer(marker)) {
            map.removeLayer(marker);
        }
    });
    markers = [];
    markerBySlug = new Map();

    clusterGroup?.clearLayers();

    if (line && map) {
        map.removeLayer(line);
        line = null;
    }
}

function createClusterGroup() {
    const group = L.markerClusterGroup({
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        spiderfyOnMaxZoom: true,
        disableClusteringAtZoom: 17,
        maxClusterRadius: 56,
        chunkedLoading: true,
        iconCreateFunction(cluster) {
            const count = cluster.getChildCount();
            return L.divIcon({
                html: `<div class="pg-cluster-count" title="${count} luoghi" aria-label="${count} luoghi">${count}</div>`,
                className: 'pg-cluster',
                iconSize: [36, 36],
                iconAnchor: [18, 18],
            });
        },
    });
    return group;
}

function discoveryIcon(poi, color, isSponsored) {
    const active = props.activeSlug === poi.slug;
    const size = active ? 22 : isSponsored ? 20 : 16;
    return L.divIcon({
        className: '',
        html: `<div style="width:${size}px;height:${size}px;border-radius:999px;background:${color};border:${isSponsored ? 3 : 2}px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.25)"></div>`,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
    });
}

function renderMarkers() {
    if (!map) return;

    clearMarkerLayer();

    const points = [];
    const clustered = usesCluster();
    if (clustered) {
        if (!clusterGroup) {
            clusterGroup = createClusterGroup();
        }
        if (!map.hasLayer(clusterGroup)) {
            map.addLayer(clusterGroup);
        }
    } else if (clusterGroup) {
        if (map.hasLayer(clusterGroup)) {
            map.removeLayer(clusterGroup);
        }
        clusterGroup.off();
        clusterGroup = null;
    }

    props.pois.forEach((poi, index) => {
        const latLng = [Number(poi.latitude), Number(poi.longitude)];
        if (!Number.isFinite(latLng[0]) || !Number.isFinite(latLng[1])) {
            return;
        }
        points.push(latLng);
        const isSponsored = props.sponsoredPoiIds.includes(poi.id);
        const color = isSponsored ? '#FFB300' : markerColor(poi);
        const number = poi.stop_number || poi.position || index + 1;
        const marker = props.numbered
            ? L.marker(latLng, {
                icon: L.divIcon({
                    className: '',
                    html: `<div style="width:24px;height:24px;border-radius:999px;background:${color};color:#fff;border:2px solid #fff;display:flex;align-items:center;justify-content:center;font:700 11px/1 Inter,sans-serif">${number}</div>`,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12],
                }),
                keyboard: true,
                title: poi.name,
            })
            : L.marker(latLng, {
                icon: discoveryIcon(poi, color, isSponsored),
                keyboard: true,
                title: poi.name,
            });
        marker.on('click', () => emit('select', poi));
        marker.bindTooltip(isSponsored ? `★ ${poi.name}` : poi.name, { direction: 'top', offset: [0, -8] });
        marker._pgSlug = poi.slug;
        if (clustered) {
            clusterGroup.addLayer(marker);
        } else {
            marker.addTo(map);
        }
        markers.push(marker);
        markerBySlug.set(poi.slug, marker);
    });

    if (props.showLine && points.length > 1) {
        line = L.polyline(points, {
            color: '#2E7D32',
            weight: 3,
            opacity: 0.7,
            dashArray: '8 6',
        }).addTo(map);
    }

    if (props.fitStops && points.length) {
        map.fitBounds(points, { padding: [28, 28], maxZoom: 16 });
    }
}

function highlightActive() {
    if (usesCluster()) {
        markers.forEach((marker) => {
            const poi = props.pois.find((item) => item.slug === marker._pgSlug);
            if (!poi) return;
            const isSponsored = props.sponsoredPoiIds.includes(poi.id);
            marker.setIcon(discoveryIcon(poi, isSponsored ? '#FFB300' : markerColor(poi), isSponsored));
        });
        return;
    }
    renderMarkers();
}

function flyTo(lat, lng) {
    map?.flyTo([lat, lng], 16, { duration: 0.8 });
}

function fitToStops() {
    const points = props.pois
        .map((poi) => [Number(poi.latitude), Number(poi.longitude)])
        .filter((point) => Number.isFinite(point[0]) && Number.isFinite(point[1]));
    if (map && points.length) {
        map.fitBounds(points, { padding: [28, 28], maxZoom: 16 });
    }
}

function revealSlug(slug, lat, lng) {
    const marker = markerBySlug.get(slug);
    if (clusterGroup && marker) {
        clusterGroup.zoomToShowLayer(marker, () => {
            if (typeof window !== 'undefined' && window.innerWidth < 1024) {
                map?.panBy([0, -90], { animate: true });
            }
        });
        return;
    }
    if (Number.isFinite(lat) && Number.isFinite(lng)) {
        flyTo(lat, lng);
        if (typeof window !== 'undefined' && window.innerWidth < 1024) {
            map?.once('moveend', () => map.panBy([0, -90], { animate: true }));
        }
    }
}

defineExpose({ flyTo, fitToStops, revealSlug, usesCluster });
</script>

<template>
    <div class="relative h-full w-full" :class="class">
        <div ref="mapEl" class="h-full w-full" />
        <div class="absolute bottom-20 left-4 z-[400] flex gap-1 rounded-full bg-pg-surface/95 p-1 shadow-card backdrop-blur-sm lg:bottom-4">
            <button
                v-for="(cfg, key) in tileLayers"
                :key="key"
                type="button"
                class="rounded-full px-3 py-1.5 text-xs font-medium transition"
                :class="mapStyle === key ? 'bg-pg-primary text-white' : 'text-pg-muted hover:bg-gray-100'"
                @click="switchStyle(key)"
            >
                {{ cfg.label }}
            </button>
        </div>
    </div>
</template>

<style>
.pg-cluster {
    background: transparent;
    border: 0;
}
.pg-cluster-count {
    width: 36px;
    height: 36px;
    border-radius: 999px;
    background: #2e7d32;
    color: #fff;
    font: 700 13px/36px Inter, sans-serif;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.22);
    border: 2px solid #fff;
}
</style>
