<script setup>
import { computed } from 'vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';
import { categoryMeta } from '@/theme/colors';

const props = defineProps({
    poi: Object,
});

const gradient = computed(() => {
    const cat = props.poi?.categories?.[0];
    const color = cat?.color ?? categoryMeta.panorami?.color ?? '#2E7D32';
    return `linear-gradient(135deg, ${color}99, ${color})`;
});

const icon = computed(() => {
    const slug = props.poi?.categories?.[0]?.slug;
    return categoryMeta[slug]?.icon ?? 'panorama';
});
</script>

<template>
    <div
        class="flex h-full w-full items-center justify-center"
        :style="{ background: gradient }"
    >
        <PgIcon :name="icon" class="h-8 w-8 text-white/90" />
    </div>
</template>
