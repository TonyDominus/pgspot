<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <Transition name="page" mode="out-in">
        <div :key="page.url" class="page-content">
            <slot />
        </div>
    </Transition>
</template>

<style scoped>
.page-enter-active,
.page-leave-active {
    transition: opacity 0.28s ease, transform 0.28s ease;
}

.page-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.page-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

@media (prefers-reduced-motion: reduce) {
    .page-enter-active,
    .page-leave-active {
        transition: none;
    }
}
</style>
