<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';
import PoiThumbnail from '@/Components/Pg/PoiThumbnail.vue';
import StarRating from '@/Components/Pg/StarRating.vue';
import PoiListCard from '@/Components/Pg/PoiListCard.vue';
import FavoriteButton from '@/Components/Pg/FavoriteButton.vue';
import ReviewForm from '@/Components/Pg/ReviewForm.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

const props = defineProps({
    poi: Object,
    related: Array,
    reviews: Array,
    userReview: Object,
    mapCenter: Object,
});

const tags = computed(() => {
    const attrs = props.poi.attributes ?? {};
    const list = attrs.tags ?? [];
    if (Array.isArray(list) && list.length) return list;
    const built = [];
    if (attrs.accessible) built.push('Accessibile');
    if (attrs.free) built.push('Gratuito');
    if (attrs.sunset) built.push('Tramonto');
    if (attrs.city_view) built.push('Vista città');
    return built;
});

const navigateUrl = computed(
    () => `https://www.google.com/maps/dir/?api=1&destination=${props.poi.latitude},${props.poi.longitude}`,
);

const shareUrl = () => {
    if (navigator.share) {
        navigator.share({ title: props.poi.name, url: window.location.href });
    }
};
</script>

<template>
    <Head :title="poi.name" />

    <AppShell active-nav="explore">
        <div class="mx-auto max-w-3xl overflow-hidden lg:rounded-2xl lg:bg-pg-surface lg:shadow-card">
            <div class="relative h-56 lg:h-72">
                <PoiThumbnail :poi="poi" class="h-full w-full" />
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />

                <div class="absolute left-0 right-0 top-0 flex items-center justify-between p-4">
                    <Link :href="route('home')" class="rounded-full bg-black/30 p-2 text-white backdrop-blur-sm">
                        <PgIcon name="back" class="h-5 w-5" />
                    </Link>
                <div class="flex gap-2">
                    <FavoriteButton :poi-id="poi.id" :poi-slug="poi.slug" size="lg" variant="light" />
                    <button type="button" class="rounded-full bg-black/30 p-2 text-white backdrop-blur-sm" @click="shareUrl">
                            <PgIcon name="share" class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="px-4 pb-8 pt-4 lg:px-8">
                <h1 class="text-2xl font-bold text-pg-text">{{ poi.name }}</h1>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                    <span v-for="cat in poi.categories" :key="cat.id" class="font-medium" :style="{ color: cat.color }">
                        {{ cat.name }}
                    </span>
                    <StarRating :rating="poi.rating" size="lg" />
                    <span v-if="poi.review_count" class="text-pg-muted">({{ poi.review_count }} recensioni)</span>
                </div>

                <div v-if="tags.length" class="mt-4 flex flex-wrap gap-2">
                    <span v-for="tag in tags" :key="tag" class="rounded-full bg-pg-primary/10 px-3 py-1 text-xs font-medium text-pg-primary">
                        {{ tag }}
                    </span>
                </div>

                <p class="mt-4 text-sm leading-relaxed text-pg-muted">{{ poi.description }}</p>

                <section v-if="poi.photos?.length > 1" class="mt-6">
                    <h2 class="mb-3 font-semibold">Galleria</h2>
                    <div class="flex gap-2 overflow-x-auto pb-1">
                        <img
                            v-for="photo in poi.photos"
                            :key="photo.id"
                            :src="photo.url"
                            :alt="poi.name"
                            class="h-24 w-32 shrink-0 rounded-xl object-cover"
                        />
                    </div>
                </section>

                <section class="mt-6">
                    <h2 class="mb-3 font-semibold">Informazioni</h2>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-xl bg-pg-background p-3 text-center lg:bg-pg-background">
                            <PgIcon name="accessible" class="mx-auto h-5 w-5 text-pg-primary" />
                            <p class="mt-1 text-xs text-pg-muted">Accesso</p>
                            <p class="text-sm font-medium">{{ poi.attributes?.free !== false ? 'Libero' : 'A pagamento' }}</p>
                        </div>
                        <div class="rounded-xl bg-pg-background p-3 text-center">
                            <PgIcon name="clock" class="mx-auto h-5 w-5 text-pg-primary" />
                            <p class="mt-1 text-xs text-pg-muted">Orari</p>
                            <p class="text-sm font-medium">Sempre aperto</p>
                        </div>
                        <div class="rounded-xl bg-pg-background p-3 text-center">
                            <PgIcon name="panorama" class="mx-auto h-5 w-5 text-pg-primary" />
                            <p class="mt-1 text-xs text-pg-muted">Ideale per</p>
                            <p class="text-sm font-medium">Foto</p>
                        </div>
                    </div>
                </section>

                <div class="mt-6 flex gap-3">
                    <button type="button" class="pg-btn-outline flex-1 gap-2" @click="shareUrl">
                        <PgIcon name="share" class="h-4 w-4" /> Condividi
                    </button>
                    <a :href="navigateUrl" target="_blank" rel="noopener" class="pg-btn-primary flex-1 gap-2">
                        <PgIcon name="navigate" class="h-4 w-4" /> Vai qui
                    </a>
                </div>

                <ReviewForm :poi-slug="poi.slug" :user-review="userReview">
                    <div v-if="reviews?.length" class="space-y-3">
                        <article v-for="review in reviews" :key="review.id" class="rounded-xl bg-pg-background p-4">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-pg-text">{{ review.user?.name }}</p>
                                <StarRating :rating="review.rating" />
                            </div>
                            <p v-if="review.comment" class="mt-2 text-sm text-pg-muted">{{ review.comment }}</p>
                            <p class="mt-1 text-xs text-pg-muted">
                                {{ new Date(review.created_at).toLocaleDateString('it-IT') }}
                            </p>
                        </article>
                    </div>
                    <p v-else class="text-center text-sm text-pg-muted">Nessuna recensione ancora. Sii il primo!</p>
                </ReviewForm>

                <section v-if="related?.length" class="mt-8">
                    <h2 class="mb-3 font-semibold">Luoghi simili</h2>
                    <div class="space-y-3">
                        <PoiListCard v-for="item in related" :key="item.id" :poi="item" />
                    </div>
                </section>
            </div>
        </div>
    </AppShell>
</template>
