<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import StarRating from '@/Components/Pg/StarRating.vue';

const props = defineProps({
    poiSlug: String,
    userReview: Object,
});

const page = usePage();
const isLoggedIn = !!page.props.auth?.user;

const rating = ref(props.userReview?.rating ?? 0);
const comment = ref(props.userReview?.comment ?? '');

const form = useForm({
    rating: rating.value,
    comment: comment.value,
});

watch(rating, (val) => {
    form.rating = val;
});

function submit() {
    if (!rating.value) return;
    form.rating = rating.value;
    form.comment = comment.value;
    form.post(route('poi.reviews.store', props.poiSlug), {
        preserveScroll: true,
    });
}

function remove() {
    form.delete(route('poi.reviews.destroy', props.poiSlug), {
        preserveScroll: true,
        onSuccess: () => {
            rating.value = 0;
            comment.value = '';
        },
    });
}
</script>

<template>
    <section class="mt-8">
        <h2 class="mb-3 font-semibold text-pg-text">Recensioni</h2>

        <div v-if="isLoggedIn" class="pg-card mb-4 p-4">
            <p class="mb-2 text-sm font-medium text-pg-text">
                {{ userReview ? 'Modifica la tua recensione' : 'Lascia una recensione' }}
            </p>
            <StarRating v-model="rating" interactive size="lg" />
            <textarea
                v-model="comment"
                rows="2"
                class="pg-input mt-3"
                placeholder="Commento opzionale..."
            />
            <div class="mt-3 flex gap-2">
                <button type="button" class="pg-btn-primary flex-1" :disabled="!rating || form.processing" @click="submit">
                    {{ userReview ? 'Aggiorna' : 'Invia' }}
                </button>
                <button
                    v-if="userReview"
                    type="button"
                    class="pg-btn-outline"
                    :disabled="form.processing"
                    @click="remove"
                >
                    Rimuovi
                </button>
            </div>
        </div>

        <div v-else class="pg-card mb-4 p-4 text-center text-sm text-pg-muted">
            <Link :href="route('login')" class="font-medium text-pg-primary">Accedi</Link>
            per lasciare una recensione.
        </div>

        <slot />
    </section>
</template>
