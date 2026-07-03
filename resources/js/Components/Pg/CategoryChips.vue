<script setup>
import PgIcon from '@/Components/Icons/PgIcon.vue';
import { categoryMeta } from '@/theme/colors';

const props = defineProps({
    categories: Array,
    activeCategory: String,
});

const emit = defineEmits(['select']);

function iconFor(slug) {
    return categoryMeta[slug]?.icon ?? 'map';
}

function chipStyle(category, active) {
    const color = category.color ?? categoryMeta[category.slug]?.color ?? '#2E7D32';
    const pastel = categoryMeta[category.slug]?.pastel ?? `${color}22`;

    if (active) {
        return {
            backgroundColor: color,
            color: '#ffffff',
            boxShadow: `0 4px 14px ${color}55`,
            border: `1.5px solid ${color}`,
        };
    }

    return {
        backgroundColor: pastel,
        color: color,
        border: `1.5px solid ${color}33`,
    };
}

function toggle(slug) {
    emit('select', props.activeCategory === slug ? null : slug);
}
</script>

<template>
    <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 scrollbar-hide">
        <button
            v-for="category in categories"
            :key="category.id"
            type="button"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-semibold backdrop-blur-sm transition-all duration-200 hover:scale-[1.02]"
            :style="chipStyle(category, activeCategory === category.slug)"
            @click="toggle(category.slug)"
        >
            <PgIcon :name="iconFor(category.slug)" class="h-4 w-4" />
            {{ category.name }}
        </button>
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
