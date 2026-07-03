<script setup>
import { Link } from '@inertiajs/vue3';
import PoiThumbnail from '@/Components/Pg/PoiThumbnail.vue';
import StarRating from '@/Components/Pg/StarRating.vue';

const props = defineProps({
    sponsorship: Object,
});

const href = props.sponsorship.poi
    ? route('poi.show', props.sponsorship.poi.slug)
    : props.sponsorship.external_url || '#';

const isExternal = !props.sponsorship.poi && props.sponsorship.external_url;
</script>

<template>
    <component
        :is="isExternal ? 'a' : Link"
        :href="href"
        :target="isExternal ? '_blank' : undefined"
        :rel="isExternal ? 'noopener noreferrer' : undefined"
        class="relative flex w-64 shrink-0 gap-3 rounded-2xl border-2 border-pg-accent/40 bg-gradient-to-br from-amber-50 to-white p-3 shadow-card transition hover:shadow-md"
    >
        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl">
            <PoiThumbnail v-if="sponsorship.poi" :poi="sponsorship.poi" />
            <div v-else class="flex h-full w-full items-center justify-center bg-pg-accent/20 text-2xl">★</div>
        </div>
        <div class="min-w-0 flex-1">
            <span class="rounded-full bg-pg-accent/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800">
                Sponsorizzato
            </span>
            <p class="mt-1 truncate text-sm font-semibold text-pg-text">
                {{ sponsorship.poi?.name ?? sponsorship.title }}
            </p>
            <p class="truncate text-xs text-pg-muted">{{ sponsorship.partner_name }}</p>
            <StarRating v-if="sponsorship.poi?.rating" :rating="sponsorship.poi.rating" class="mt-1" />
        </div>
    </component>
</template>
