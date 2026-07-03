<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';
import { categoryMeta } from '@/theme/colors';

const props = defineProps({
    categories: Array,
    activeCategory: String,
    characteristicTags: Array,
    filters: Object,
});

const selectedTags = ref([...(props.filters?.tags ?? [])]);
const maxDistance = ref(props.filters?.max_distance ?? 5000);
const minRating = ref(props.filters?.min_rating ?? 0);

function iconFor(slug) {
    return categoryMeta[slug]?.icon ?? 'map';
}

function reset() {
    selectedTags.value = [];
    maxDistance.value = 5000;
    minRating.value = 0;
}

function apply() {
    router.get(route('home'), {
        cat: props.activeCategory || undefined,
        tags: selectedTags.value.length ? selectedTags.value : undefined,
        max_distance: maxDistance.value !== 5000 ? maxDistance.value : undefined,
        min_rating: minRating.value || undefined,
    });
}

function toggleTag(tag) {
    const i = selectedTags.value.indexOf(tag);
    if (i >= 0) selectedTags.value.splice(i, 1);
    else selectedTags.value.push(tag);
}
</script>

<template>
    <Head title="Filtri" />

    <AppShell active-nav="explore" v-slot="{ openMenu }">
        <header class="sticky top-0 z-20 flex items-center gap-3 bg-pg-surface px-4 py-4 shadow-sm">
            <button type="button" class="rounded-full p-1 lg:hidden" @click="openMenu">
                <PgIcon name="menu" class="h-5 w-5" />
            </button>
            <Link :href="route('home', { cat: activeCategory })" class="rounded-full p-1">
                <PgIcon name="back" class="h-6 w-6" />
            </Link>
            <h1 class="flex-1 text-lg font-semibold">Filtri</h1>
            <button type="button" class="text-sm font-medium text-pg-primary" @click="reset">
                Ripristina
            </button>
        </header>

        <main class="space-y-8 px-4 py-6">
            <section>
                <h2 class="mb-3 text-sm font-semibold text-pg-text">Categorie</h2>
                <div class="flex flex-wrap justify-center gap-4">
                    <Link
                        v-for="cat in categories"
                        :key="cat.id"
                        :href="route('filters', { cat: cat.slug })"
                        class="flex flex-col items-center gap-2"
                    >
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-full"
                            :class="activeCategory === cat.slug ? 'text-white' : 'bg-gray-100 text-pg-muted'"
                            :style="activeCategory === cat.slug ? { backgroundColor: cat.color } : {}"
                        >
                            <PgIcon :name="iconFor(cat.slug)" class="h-6 w-6" />
                        </div>
                        <span class="max-w-[4.5rem] text-center text-xs font-medium">{{ cat.name }}</span>
                    </Link>
                </div>
            </section>

            <section>
                <h2 class="mb-3 text-sm font-semibold">Caratteristiche</h2>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="tag in characteristicTags"
                        :key="tag"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-sm font-medium transition"
                        :class="selectedTags.includes(tag) ? 'bg-pg-primary text-white' : 'bg-pg-surface ring-1 ring-gray-200 text-pg-muted'"
                        @click="toggleTag(tag)"
                    >
                        {{ tag }}
                    </button>
                </div>
            </section>

            <section>
                <div class="mb-2 flex justify-between text-sm">
                    <span class="font-semibold">Distanza</span>
                    <span class="text-pg-muted">{{ maxDistance >= 1000 ? `${maxDistance / 1000} km` : `${maxDistance} m` }}</span>
                </div>
                <input
                    v-model.number="maxDistance"
                    type="range"
                    min="500"
                    max="5000"
                    step="250"
                    class="w-full accent-pg-primary"
                />
            </section>

            <section>
                <h2 class="mb-3 text-sm font-semibold">Valutazione minima</h2>
                <div class="flex gap-2">
                    <button
                        v-for="n in 5"
                        :key="n"
                        type="button"
                        class="rounded-lg p-2"
                        @click="minRating = n"
                    >
                        <PgIcon
                            name="star"
                            class="h-7 w-7"
                            :class="n <= minRating ? 'text-pg-accent' : 'text-gray-200'"
                        />
                    </button>
                </div>
            </section>
        </main>

        <div class="fixed bottom-20 left-0 right-0 z-30 px-4">
            <div class="mx-auto max-w-lg">
                <button type="button" class="pg-btn-primary w-full py-4 text-base" @click="apply">
                    Mostra risultati
                </button>
            </div>
        </div>
    </AppShell>
</template>
