<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    seo: {
        type: Object,
        required: true,
    },
});

const jsonLd = computed(() => {
    if (!props.seo.json_ld) return null;
    const data = { ...props.seo.json_ld };
    Object.keys(data).forEach((key) => {
        if (data[key] === null || data[key] === undefined) delete data[key];
    });
    return JSON.stringify(data);
});
</script>

<template>
    <Head :title="seo.title">
        <meta head-key="description" name="description" :content="seo.description" />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="og:title" property="og:title" :content="seo.title" />
        <meta head-key="og:description" property="og:description" :content="seo.description" />
        <meta head-key="og:url" property="og:url" :content="seo.url" />
        <meta head-key="og:image" property="og:image" :content="seo.image" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" :content="seo.title" />
        <meta head-key="twitter:description" name="twitter:description" :content="seo.description" />
        <script v-if="jsonLd" type="application/ld+json" head-key="jsonld">{{ jsonLd }}</script>
    </Head>
</template>
