<script setup>
import { computed } from 'vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

const props = defineProps({
    rating: { type: [Number, String], default: 0 },
    size: { type: String, default: 'sm' },
    interactive: { type: Boolean, default: false },
    modelValue: { type: Number, default: 0 },
});

const emit = defineEmits(['update:modelValue']);

const iconClass = computed(() => (props.size === 'lg' ? 'h-6 w-6' : 'h-4 w-4'));

const displayValue = computed(() =>
    props.interactive ? props.modelValue : Math.round(Number(props.rating) || 0),
);

function setRating(n) {
    if (props.interactive) {
        emit('update:modelValue', n);
    }
}
</script>

<template>
    <div class="inline-flex items-center gap-0.5">
        <button
            v-for="n in 5"
            :key="n"
            type="button"
            class="transition-transform"
            :class="interactive ? 'cursor-pointer hover:scale-110' : 'cursor-default pointer-events-none'"
            :disabled="!interactive"
            @click="setRating(n)"
        >
            <PgIcon
                name="star"
                :class="[n <= displayValue ? 'text-pg-accent' : 'text-gray-200', iconClass]"
            />
        </button>
        <span v-if="!interactive && Number(rating) > 0" class="ml-1 text-xs font-medium text-pg-muted">
            {{ Number(rating).toFixed(1) }}
        </span>
    </div>
</template>
