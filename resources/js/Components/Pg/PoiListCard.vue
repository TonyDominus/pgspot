<script setup>
import { Link } from '@inertiajs/vue3';
import PoiThumbnail from '@/Components/Pg/PoiThumbnail.vue';
import StarRating from '@/Components/Pg/StarRating.vue';
import FavoriteButton from '@/Components/Pg/FavoriteButton.vue';

defineProps({
    poi: Object,
});
</script>

<template>
    <div class="relative flex gap-4 rounded-2xl bg-pg-surface p-3 shadow-card transition hover:shadow-md">
        <Link :href="route('poi.show', poi.slug)" class="flex min-w-0 flex-1 gap-4">
            <div class="h-24 w-28 shrink-0 overflow-hidden rounded-xl">
                <PoiThumbnail :poi="poi" />
            </div>
            <div class="min-w-0 flex-1 py-0.5">
                <h3 class="font-semibold text-pg-text">{{ poi.name }}</h3>
                <p class="mt-1 line-clamp-2 text-sm text-pg-muted">{{ poi.description }}</p>
                <div class="mt-2 flex items-center justify-between">
                    <span v-if="poi.distance_label" class="text-xs text-pg-muted">{{ poi.distance_label }}</span>
                    <StarRating :rating="poi.rating" />
                </div>
            </div>
        </Link>
        <FavoriteButton :poi-id="poi.id" :poi-slug="poi.slug" class="absolute right-3 top-3" />
    </div>
</template>
