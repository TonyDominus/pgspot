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
    <div class="relative flex w-64 shrink-0 gap-3 rounded-2xl bg-pg-surface p-3 shadow-card transition hover:shadow-md">
        <Link :href="route('poi.show', poi.slug)" class="flex min-w-0 flex-1 gap-3">
            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl">
                <PoiThumbnail :poi="poi" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-pg-text">{{ poi.name }}</p>
                <p class="truncate text-xs text-pg-muted">
                    {{ poi.categories?.[0]?.name }}
                    <span v-if="poi.distance_label"> · {{ poi.distance_label }}</span>
                </p>
                <StarRating :rating="poi.rating" class="mt-1" />
            </div>
        </Link>
        <FavoriteButton :poi-id="poi.id" :poi-slug="poi.slug" class="absolute right-2 top-2" size="sm" />
    </div>
</template>
