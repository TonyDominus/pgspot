<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    reviews: Object,
    filters: Object,
});

const search = ref(props.filters?.q ?? '');

function applySearch() {
    router.get(route('admin.reviews.index'), { q: search.value || undefined }, { preserveState: true });
}

function destroyReview(id) {
    if (! confirm('Eliminare questa recensione?')) return;
    router.delete(route('admin.reviews.destroy', id));
}
</script>

<template>
    <Head title="Recensioni" />

    <AdminShell>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-pg-text">Moderazione recensioni</h1>
            <p class="text-sm text-pg-muted">Visualizza ed elimina recensioni inappropriate</p>
        </div>

        <form class="mb-4 flex gap-2" @submit.prevent="applySearch">
            <input v-model="search" type="search" class="pg-input max-w-md" placeholder="Cerca per luogo, utente o testo..." />
            <button type="submit" class="pg-btn-primary">Cerca</button>
        </form>

        <div class="pg-card overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-gray-100 text-pg-muted">
                    <tr>
                        <th class="px-4 py-3">Luogo</th>
                        <th class="px-4 py-3">Utente</th>
                        <th class="px-4 py-3">Voto</th>
                        <th class="px-4 py-3">Commento</th>
                        <th class="px-4 py-3">Data</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="review in reviews.data" :key="review.id" class="border-b border-gray-50 align-top">
                        <td class="px-4 py-3">
                            <Link
                                v-if="review.poi"
                                :href="route('poi.show', review.poi.slug)"
                                class="font-medium text-pg-primary hover:underline"
                            >
                                {{ review.poi.name }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ review.user?.name }}</p>
                            <p class="text-xs text-pg-muted">{{ review.user?.email }}</p>
                        </td>
                        <td class="px-4 py-3">{{ review.rating }}/5</td>
                        <td class="max-w-xs px-4 py-3 text-pg-muted">{{ review.comment || '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-pg-muted">
                            {{ new Date(review.created_at).toLocaleDateString('it-IT') }}
                        </td>
                        <td class="px-4 py-3">
                            <button type="button" class="text-pg-error hover:underline" @click="destroyReview(review.id)">
                                Elimina
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-if="!reviews.data?.length" class="py-8 text-center text-sm text-pg-muted">
            Nessuna recensione trovata.
        </p>
    </AdminShell>
</template>
