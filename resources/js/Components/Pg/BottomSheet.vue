<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import PoiMapCard from '@/Components/Pg/PoiMapCard.vue';
import PoiListCard from '@/Components/Pg/PoiListCard.vue';
import SponsoredCard from '@/Components/Pg/SponsoredCard.vue';

const props = defineProps({
    pois: { type: Array, default: () => [] },
    sponsorships: { type: Array, default: () => [] },
    title: { type: String, default: 'Vicino a te' },
});

const SNAP = { peek: 200, half: 0.5, full: 0.82 };

const snap = ref('peek');
const dragging = ref(false);
const dragStartY = ref(0);
const dragStartHeight = ref(0);
const currentHeight = ref(SNAP.peek);
const viewportH = ref(typeof window !== 'undefined' ? window.innerHeight : 800);

const maxHeight = computed(() => viewportH.value * SNAP.full);
const halfHeight = computed(() => viewportH.value * SNAP.half);

const sheetHeightPx = computed(() => {
    if (dragging.value) return currentHeight.value;
    if (snap.value === 'peek') return SNAP.peek;
    if (snap.value === 'half') return halfHeight.value;
    return maxHeight.value;
});

const isExpanded = computed(() => snap.value !== 'peek' || dragging.value);

function onResize() {
    viewportH.value = window.innerHeight;
}

function snapTo(name) {
    snap.value = name;
    dragging.value = false;
}

function onHandleDown(e) {
    dragging.value = true;
    dragStartY.value = e.clientY;
    dragStartHeight.value = sheetHeightPx.value;
    e.currentTarget.setPointerCapture(e.pointerId);
}

function onHandleMove(e) {
    if (!dragging.value) return;
    const delta = dragStartY.value - e.clientY;
    currentHeight.value = Math.min(maxHeight.value, Math.max(120, dragStartHeight.value + delta));
}

function onHandleUp(e) {
    if (!dragging.value) return;
    dragging.value = false;
    e.currentTarget.releasePointerCapture(e.pointerId);

    const h = currentHeight.value;
    if (h < SNAP.peek + 60) snapTo('peek');
    else if (h < halfHeight.value + 80) snapTo('half');
    else snapTo('full');
}

onMounted(() => window.addEventListener('resize', onResize));
onUnmounted(() => window.removeEventListener('resize', onResize));
</script>

<template>
    <div
        class="absolute bottom-0 left-0 right-0 z-[500] flex flex-col rounded-t-3xl bg-pg-surface shadow-sheet transition-[height] duration-300 ease-out"
        :class="dragging ? '!duration-0' : ''"
        :style="{ height: `${sheetHeightPx}px` }"
    >
        <div
            class="shrink-0 cursor-grab touch-none px-4 pb-2 pt-3 active:cursor-grabbing"
            @pointerdown="onHandleDown"
            @pointermove="onHandleMove"
            @pointerup="onHandleUp"
            @pointercancel="onHandleUp"
        >
            <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-gray-300" />
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-pg-text">{{ title }}</h2>
                <span class="text-xs text-pg-muted">{{ pois.length }} luoghi · trascina su</span>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-6">
            <div v-if="pois.length === 0" class="py-8 text-center text-sm text-pg-muted">
                Nessun luogo trovato.
            </div>

            <div v-else-if="!isExpanded" class="-mx-1 flex gap-3 overflow-x-auto pb-1 scrollbar-hide">
                <SponsoredCard v-for="s in sponsorships" :key="'s-' + s.id" :sponsorship="s" />
                <PoiMapCard v-for="poi in pois" :key="poi.id" :poi="poi" />
            </div>

            <div v-else class="space-y-3">
                <SponsoredCard v-for="s in sponsorships" :key="'s-' + s.id" :sponsorship="s" class="!w-full" />
                <PoiListCard v-for="poi in pois" :key="poi.id" :poi="poi" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
