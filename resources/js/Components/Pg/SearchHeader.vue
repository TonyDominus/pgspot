<script setup>
import { Link } from '@inertiajs/vue3';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    search: { type: String, default: '' },
    showBack: { type: Boolean, default: false },
    backHref: { type: String, default: '/' },
    title: { type: String, default: '' },
    floating: { type: Boolean, default: false },
});

const emit = defineEmits(['update:search', 'open-menu']);
</script>

<template>
    <header
        class="z-30 px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top))]"
        :class="floating ? 'pointer-events-none absolute left-0 right-0 top-0 bg-gradient-to-b from-white/90 via-white/70 to-transparent' : 'bg-pg-surface shadow-sm'"
    >
        <div v-if="showBack" class="pointer-events-auto mb-3 flex items-center gap-3">
            <Link :href="backHref" class="rounded-full p-1 text-pg-text hover:bg-white/80">
                <PgIcon name="back" class="h-6 w-6" />
            </Link>
            <h1 class="flex-1 text-lg font-semibold">{{ title }}</h1>
            <slot name="header-right" />
        </div>

        <div v-else class="pointer-events-auto mb-3 flex items-center gap-3">
            <button
                type="button"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-pg-surface/90 text-pg-text shadow-card backdrop-blur-sm transition hover:bg-white"
                aria-label="Menu"
                @click="emit('open-menu')"
            >
                <PgIcon name="menu" class="h-5 w-5" />
            </button>
            <div class="relative min-w-0 flex-1">
                <PgIcon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pg-muted" />
                <input
                    :value="search"
                    type="search"
                    placeholder="Cerca un luogo..."
                    class="w-full rounded-full border-0 bg-pg-surface/95 py-2.5 pl-10 pr-4 text-sm shadow-card backdrop-blur-sm focus:ring-2 focus:ring-pg-primary"
                    @input="emit('update:search', $event.target.value)"
                />
            </div>
            <button
                type="button"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-pg-surface/90 text-pg-muted shadow-card backdrop-blur-sm hover:bg-white"
                aria-label="Notifiche"
            >
                <PgIcon name="bell" class="h-5 w-5" />
            </button>
        </div>

        <div class="pointer-events-auto">
            <slot />
        </div>
    </header>
</template>
