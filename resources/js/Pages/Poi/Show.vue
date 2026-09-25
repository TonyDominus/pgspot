<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';
import PoiThumbnail from '@/Components/Pg/PoiThumbnail.vue';
import StarRating from '@/Components/Pg/StarRating.vue';
import PoiListCard from '@/Components/Pg/PoiListCard.vue';
import FavoriteButton from '@/Components/Pg/FavoriteButton.vue';
import ReviewForm from '@/Components/Pg/ReviewForm.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';
import SeoHead from '@/Components/Pg/SeoHead.vue';

const props = defineProps({
    poi: Object,
    primaryCategory: Object,
    secondaryCategories: Array,
    placeLine: String,
    facts: Array,
    verifiedLabel: String,
    related: Array,
    itineraries: Array,
    events: Array,
    reviews: Array,
    userReview: Object,
    seo: Object,
});

const copied = ref(false);

const heroPhoto = computed(() => props.poi.photos?.find((photo) => photo.is_primary) ?? props.poi.photos?.[0] ?? null);

const gallery = computed(() => (props.poi.photos ?? []).filter((photo) => photo.id !== heroPhoto.value?.id));

const navigateUrl = computed(
    () => `https://www.google.com/maps/dir/?api=1&destination=${props.poi.latitude},${props.poi.longitude}`,
);

const mapHref = computed(() => `${route('home')}?focus=${encodeURIComponent(props.poi.slug)}`);

async function shareUrl() {
    const url = window.location.href;
    if (navigator.share) {
        try {
            await navigator.share({ title: props.poi.name, url });
            return;
        } catch (error) {
            if (error?.name === 'AbortError') return;
        }
    }
    await navigator.clipboard.writeText(url);
    copied.value = true;
}

function formatWhen(value) {
    return new Date(value).toLocaleDateString('it-IT', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <SeoHead v-if="seo" :seo="seo" />
    <Head v-else :title="poi.name" />

    <AppShell active-nav="explore">
        <article class="mx-auto max-w-5xl lg:px-4 lg:py-6">
            <div class="relative h-56 bg-pg-background sm:h-72 lg:h-80 lg:overflow-hidden lg:rounded-2xl">
                <img
                    v-if="heroPhoto"
                    :src="heroPhoto.url"
                    :alt="heroPhoto.caption || poi.name"
                    class="h-full w-full object-cover"
                />
                <PoiThumbnail v-else :poi="poi" class="h-full w-full" />
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent" />
                <div class="absolute left-0 right-0 top-0 flex items-center justify-between p-4">
                    <Link :href="route('home')" class="rounded-full bg-black/30 p-2 text-white backdrop-blur-sm" aria-label="Torna alla mappa">
                        <PgIcon name="back" class="h-5 w-5" />
                    </Link>
                    <FavoriteButton :poi-id="poi.id" :poi-slug="poi.slug" size="lg" variant="light" />
                </div>
            </div>

            <div class="flex flex-col px-4 pb-10 pt-4 lg:grid lg:grid-cols-[minmax(0,1fr)_280px] lg:items-start lg:gap-8 lg:px-0">
                <header class="lg:col-span-2">
                    <h1 class="text-2xl font-bold text-pg-text">{{ poi.name }}</h1>
                    <p v-if="poi.address" class="mt-1 text-sm text-pg-muted">{{ poi.address }}</p>
                    <p v-if="placeLine" class="mt-1 text-sm text-pg-text">{{ placeLine }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                        <span v-if="primaryCategory" class="font-medium" :style="{ color: primaryCategory.color }">
                            {{ primaryCategory.name }}
                        </span>
                        <span v-for="cat in secondaryCategories" :key="cat.id" class="text-pg-muted">
                            {{ cat.name }}
                        </span>
                        <StarRating :rating="poi.rating" size="lg" />
                        <span v-if="poi.review_count" class="text-pg-muted">{{ poi.review_count }} recensioni</span>
                    </div>
                    <div v-if="poi.tags?.length" class="mt-3 flex flex-wrap gap-2">
                        <span
                            v-for="tag in poi.tags"
                            :key="tag.id"
                            class="rounded-full bg-pg-primary/10 px-3 py-1 text-xs font-medium text-pg-primary"
                        >
                            {{ tag.name }}
                        </span>
                    </div>
                </header>

                <div class="order-2 mt-6 space-y-8 lg:order-none lg:mt-0">
                    <p v-if="poi.description" class="text-sm leading-relaxed text-pg-muted">{{ poi.description }}</p>

                    <section v-if="gallery.length">
                        <h2 class="mb-3 font-semibold">Galleria</h2>
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            <figure v-for="photo in gallery" :key="photo.id" class="w-32 shrink-0">
                                <img
                                    :src="photo.url"
                                    :alt="photo.caption || poi.name"
                                    loading="lazy"
                                    class="h-24 w-32 rounded-xl object-cover"
                                />
                                <figcaption v-if="photo.caption" class="mt-1 text-xs text-pg-muted">{{ photo.caption }}</figcaption>
                            </figure>
                        </div>
                    </section>

                    <a :href="mapHref" class="inline-flex text-sm font-medium text-pg-primary">Vedi sulla mappa</a>

                    <section v-if="itineraries?.length">
                        <h2 class="mb-3 font-semibold">Presente in questi itinerari</h2>
                        <ul class="space-y-2">
                            <li v-for="item in itineraries" :key="item.id">
                                <Link :href="route('itineraries.show', item.slug)" class="text-sm font-medium text-pg-primary">
                                    {{ item.title }}
                                </Link>
                                <span v-if="item.duration" class="text-sm text-pg-muted"> · {{ item.duration }}</span>
                            </li>
                        </ul>
                    </section>

                    <section v-if="events?.length">
                        <h2 class="mb-3 font-semibold">Eventi in questo luogo</h2>
                        <ul class="space-y-2 text-sm">
                            <li v-for="event in events" :key="event.id">
                                <span class="font-medium text-pg-text">{{ event.title }}</span>
                                <span v-if="event.starts_at" class="text-pg-muted"> · {{ formatWhen(event.starts_at) }}</span>
                            </li>
                        </ul>
                    </section>

                    <ReviewForm :poi-slug="poi.slug" :user-review="userReview">
                        <div v-if="reviews?.length" class="space-y-3">
                            <article v-for="review in reviews" :key="review.id" class="rounded-xl bg-pg-background p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-pg-text">{{ review.user?.name }}</p>
                                    <StarRating :rating="review.rating" />
                                </div>
                                <p v-if="review.comment" class="mt-2 text-sm text-pg-muted">{{ review.comment }}</p>
                                <p class="mt-1 text-xs text-pg-muted">{{ formatWhen(review.created_at) }}</p>
                            </article>
                        </div>
                        <p v-else class="text-center text-sm text-pg-muted">Nessuna recensione ancora.</p>
                    </ReviewForm>

                    <section v-if="related?.length">
                        <h2 class="mb-3 font-semibold">Luoghi vicini</h2>
                        <div class="space-y-3">
                            <PoiListCard v-for="item in related" :key="item.id" :poi="item" />
                        </div>
                    </section>
                </div>

                <aside class="order-1 mt-4 space-y-4 lg:order-none lg:mt-0">
                    <div class="grid grid-cols-2 gap-2">
                        <a :href="navigateUrl" target="_blank" rel="noopener" class="pg-btn-primary col-span-2 gap-2">
                            <PgIcon name="navigate" class="h-4 w-4" /> Vai qui
                        </a>
                        <button type="button" class="pg-btn-outline gap-2" @click="shareUrl">
                            <PgIcon name="share" class="h-4 w-4" /> Condividi
                        </button>
                        <Link :href="route('contribute.create', { poi: poi.slug, intent: 'edit' })" class="pg-btn-outline gap-2">
                            Suggerisci modifica
                        </Link>
                        <Link :href="route('contribute.create', { poi: poi.slug, intent: 'photo' })" class="pg-btn-outline gap-2">
                            Aggiungi foto
                        </Link>
                        <Link :href="route('contribute.create', { poi: poi.slug, intent: 'report' })" class="pg-btn-outline col-span-2 gap-2">
                            Segnala problema
                        </Link>
                    </div>
                    <p v-if="copied" class="text-xs text-pg-muted" role="status">Link copiato</p>

                    <section v-if="facts?.length">
                        <h2 class="mb-2 text-sm font-semibold">Informazioni</h2>
                        <ul class="space-y-2 text-sm">
                            <li v-for="fact in facts" :key="fact.id">
                                <a
                                    v-if="fact.href"
                                    :href="fact.href"
                                    class="font-medium text-pg-primary"
                                    :target="fact.id === 'website' ? '_blank' : undefined"
                                    :rel="fact.id === 'website' ? 'noopener' : undefined"
                                >
                                    {{ fact.label }}
                                </a>
                                <span v-else>
                                    <span v-if="fact.value" class="text-pg-muted">{{ fact.label }}: </span>{{ fact.value || fact.label }}
                                </span>
                            </li>
                        </ul>
                    </section>
                    <p v-if="verifiedLabel" class="text-xs text-pg-muted">{{ verifiedLabel }}</p>
                </aside>
            </div>
        </article>
    </AppShell>
</template>
