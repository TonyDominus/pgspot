<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

const props = defineProps({
    poiId: { type: Number, required: true },
    poiSlug: { type: String, required: true },
    size: { type: String, default: 'md' },
    variant: { type: String, default: 'default' },
});

const page = usePage();
const favoriteIds = computed(() => page.props.favoritePoiIds ?? []);
const isFavorite = computed(() => favoriteIds.value.includes(props.poiId));
const isLoggedIn = computed(() => !!page.props.auth?.user);

const iconClass = computed(() => {
    if (props.size === 'lg') return 'h-6 w-6';
    return 'h-5 w-5';
});

function toggle(e) {
    e.preventDefault();
    e.stopPropagation();

    if (!isLoggedIn.value) {
        router.visit(route('login'));
        return;
    }

    if (isFavorite.value) {
        router.delete(route('poi.favorites.destroy', props.poiSlug), { preserveScroll: true });
    } else {
        router.post(route('poi.favorites.store', props.poiSlug), {}, { preserveScroll: true });
    }
}
</script>

<template>
    <button
        type="button"
        class="shrink-0 rounded-full p-1 transition hover:scale-110"
        :aria-label="isFavorite ? 'Rimuovi dai preferiti' : 'Aggiungi ai preferiti'"
        @click="toggle"
    >
        <PgIcon
            :name="isFavorite ? 'heart-filled' : 'heart'"
            :class="[
                iconClass,
                isFavorite
                    ? 'text-pg-error'
                    : variant === 'light'
                      ? 'text-white/80 hover:text-white'
                      : 'text-gray-300 hover:text-pg-error/60',
            ]"
        />
    </button>
</template>
