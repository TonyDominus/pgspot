<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    categories: Array,
    pois: Array,
    featuredEvents: Array,
    activeCategory: String,
    canContribute: Boolean,
});

const user = computed(() => usePage().props.auth?.user);
const isAdmin = computed(() => ['admin', 'superadmin'].includes(user.value?.role));

function filterCategory(slug) {
    router.get(route('home'), slug ? { cat: slug } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Home" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <Link :href="route('home')" class="text-xl font-bold text-blue-700">
                        PG Spot
                    </Link>
                    <p class="text-sm text-slate-500">La mappa collaborativa di Perugia</p>
                </div>

                <nav class="flex items-center gap-3 text-sm">
                    <template v-if="user">
                        <Link
                            v-if="isAdmin"
                            :href="route('admin.dashboard')"
                            class="rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100"
                        >
                            Admin
                        </Link>
                        <Link
                            :href="route('profile.edit')"
                            class="rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100"
                        >
                            Profilo
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100"
                        >
                            Accedi
                        </Link>
                        <Link
                            :href="route('register')"
                            class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700"
                        >
                            Registrati
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <section class="mb-8 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 p-8 text-white shadow-lg">
                <h1 class="text-3xl font-bold sm:text-4xl">Scopri Perugia</h1>
                <p class="mt-2 max-w-2xl text-blue-100">
                    Panorami, servizi igienici, eventi e molto altro. Consulta la mappa liberamente
                    o registrati per contribuire con foto e nuovi punti.
                </p>
                <p v-if="!canContribute" class="mt-4 text-sm text-blue-100">
                    <Link :href="route('register')" class="underline">Crea un account</Link>
                    per proporre nuovi luoghi alla community.
                </p>
            </section>

            <section v-if="featuredEvents.length" class="mb-8">
                <h2 class="mb-4 text-lg font-semibold">In evidenza</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="event in featuredEvents"
                        :key="event.id"
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-blue-600">Evento</p>
                        <h3 class="mt-1 font-semibold">{{ event.title }}</h3>
                        <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ event.description }}</p>
                        <p class="mt-3 text-xs text-slate-500">
                            {{ new Date(event.starts_at).toLocaleDateString('it-IT') }}
                        </p>
                    </article>
                </div>
            </section>

            <section class="mb-6">
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="!activeCategory ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                        @click="filterCategory(null)"
                    >
                        Tutti
                    </button>
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        type="button"
                        class="rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="activeCategory === category.slug ? 'text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                        :style="activeCategory === category.slug ? { backgroundColor: category.color } : {}"
                        @click="filterCategory(category.slug)"
                    >
                        {{ category.name }}
                    </button>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <h2 class="mb-4 text-lg font-semibold">
                        Luoghi
                        <span class="text-sm font-normal text-slate-500">({{ pois.length }})</span>
                    </h2>

                    <div v-if="pois.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                        Nessun luogo in questa categoria.
                    </div>

                    <div v-else class="grid gap-4 sm:grid-cols-2">
                        <article
                            v-for="poi in pois"
                            :key="poi.id"
                            class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="cat in poi.categories"
                                    :key="cat.id"
                                    class="rounded-full px-2 py-0.5 text-xs font-medium text-white"
                                    :style="{ backgroundColor: cat.color }"
                                >
                                    {{ cat.name }}
                                </span>
                            </div>
                            <h3 class="mt-2 font-semibold">{{ poi.name }}</h3>
                            <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ poi.description }}</p>
                            <p v-if="poi.address" class="mt-2 text-xs text-slate-500">{{ poi.address }}</p>
                            <a
                                class="mt-3 inline-flex text-sm font-medium text-blue-600 hover:underline"
                                :href="`https://www.google.com/maps/dir/?api=1&destination=${poi.latitude},${poi.longitude}`"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Naviga →
                            </a>
                        </article>
                    </div>
                </div>

                <aside class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-semibold">Mappa</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        La mappa interattiva Leaflet sarà integrata nel prossimo step.
                        Per ora usa i link di navigazione su ogni scheda.
                    </p>
                    <div class="mt-4 flex h-64 items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-500">
                        Mappa in arrivo
                    </div>
                </aside>
            </section>
        </main>
    </div>
</template>
