<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PoiThumbnail from '@/Components/Pg/PoiThumbnail.vue';
import StarRating from '@/Components/Pg/StarRating.vue';
import FavoriteButton from '@/Components/Pg/FavoriteButton.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

const props = defineProps({
    poi: Object,
    visible: Boolean,
});

const emit = defineEmits(['close']);

const category = computed(() => props.poi?.categories?.[0]);
const navigateUrl = computed(() =>
  props.poi
    ? `https://www.google.com/maps/dir/?api=1&destination=${props.poi.latitude},${props.poi.longitude}`
    : '#',
);
</script>

<template>
    <Transition name="slide-up">
        <div
            v-if="visible && poi"
            class="absolute inset-x-0 bottom-0 z-[500] rounded-t-3xl bg-pg-surface shadow-[0_-8px_30px_rgba(0,0,0,0.12)] lg:bottom-4 lg:left-4 lg:right-auto lg:w-96 lg:rounded-2xl"
        >
            <div class="flex justify-center pt-2 lg:hidden">
                <div class="h-1 w-10 rounded-full bg-gray-200" />
            </div>

            <button
                type="button"
                class="absolute right-3 top-3 rounded-full p-2 text-pg-muted hover:bg-gray-100"
                aria-label="Chiudi"
                @click="emit('close')"
            >
                <PgIcon name="close" class="h-5 w-5" />
            </button>

            <div class="flex gap-3 p-4 pt-3">
                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl">
                    <PoiThumbnail :poi="poi" class="h-full w-full" />
                </div>
                <div class="min-w-0 flex-1 pr-8">
                    <span
                        v-if="category"
                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold text-white"
                        :style="{ backgroundColor: category.color }"
                    >
                        {{ category.name }}
                    </span>
                    <h3 class="mt-1 truncate font-semibold text-pg-text">{{ poi.name }}</h3>
                    <StarRating v-if="poi.rating" :rating="poi.rating" class="mt-1" />
                    <p v-if="poi.address" class="mt-1 truncate text-xs text-pg-muted">{{ poi.address }}</p>
                </div>
            </div>

            <p v-if="poi.description" class="line-clamp-2 px-4 text-sm text-pg-muted">
                {{ poi.description }}
            </p>

            <div class="flex items-center gap-2 p-4">
                <Link
                    :href="route('poi.show', poi.slug)"
                    class="pg-btn-primary flex-1 text-center text-sm"
                >
                    Dettagli
                </Link>
                <FavoriteButton :poi-id="poi.id" :poi-slug="poi.slug" />
                <a
                    :href="navigateUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-pg-primary"
                    aria-label="Naviga"
                >
                    <PgIcon name="location" class="h-5 w-5" />
                </a>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.25s ease, opacity 0.25s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}
</style>
