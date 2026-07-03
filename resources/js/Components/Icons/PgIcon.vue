<script setup>
defineProps({
    name: { type: String, required: true },
    class: { type: String, default: 'h-5 w-5' },
});
</script>

<template>
    <svg
        :class="class"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <!-- Mappa -->
        <template v-if="name === 'map'">
            <path d="M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3z" />
            <path d="M9 3v15M15 6v15" />
        </template>

        <!-- Lista -->
        <template v-else-if="name === 'list'">
            <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
        </template>

        <!-- Cerca -->
        <template v-else-if="name === 'search'">
            <circle cx="11" cy="11" r="7" />
            <path d="M20 20l-3.5-3.5" />
        </template>

        <!-- Preferiti -->
        <template v-else-if="name === 'heart'">
            <path d="M12 21s-7-4.5-7-10a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 5.5-7 10-7 10z" />
        </template>
        <template v-else-if="name === 'heart-filled'">
            <path d="M12 21s-7-4.5-7-10a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 5.5-7 10-7 10z" fill="currentColor" stroke="none" />
        </template>

        <!-- Filtri -->
        <template v-else-if="name === 'filter'">
            <path d="M4 6h16M7 12h10M10 18h4" />
        </template>

        <!-- Posizione -->
        <template v-else-if="name === 'location'">
            <path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z" />
            <circle cx="12" cy="11" r="2.5" />
        </template>

        <!-- Condividi -->
        <template v-else-if="name === 'share'">
            <circle cx="18" cy="5" r="2" />
            <circle cx="6" cy="12" r="2" />
            <circle cx="18" cy="19" r="2" />
            <path d="M8 11l8-5M8 13l8 5" />
        </template>

        <!-- Naviga -->
        <template v-else-if="name === 'navigate'">
            <path d="M3 11l19-9-9 19-2-8-8-2z" />
        </template>

        <!-- Segnala -->
        <template v-else-if="name === 'alert'">
            <path d="M12 9v4M12 17h.01" />
            <path d="M10.3 3.6L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.6a2 2 0 0 0-3.4 0z" />
        </template>

        <!-- Panorama -->
        <template v-else-if="name === 'panorama'">
            <path d="M3 17l5-6 4 4 3-3 6 7H3z" />
            <circle cx="8" cy="7" r="2" />
        </template>

        <!-- Bagni -->
        <template v-else-if="name === 'restroom'">
            <circle cx="8" cy="4" r="2" />
            <circle cx="16" cy="4" r="2" />
            <path d="M8 6v5M16 6v5M6 20v-4a2 2 0 0 1 2-2h0M18 20v-4a2 2 0 0 0-2-2h0M10 14h4v6" />
        </template>

        <!-- Fontanella -->
        <template v-else-if="name === 'fountain'">
            <path d="M12 3v3M8 8c0-2 1.8-3 4-3s4 1 4 3" />
            <path d="M6 14c0 3.3 2.7 6 6 6s6-2.7 6-6" />
            <path d="M12 14v4" />
        </template>

        <!-- Parcheggio -->
        <template v-else-if="name === 'parking'">
            <rect x="4" y="3" width="16" height="18" rx="2" />
            <path d="M10 17V7h4a3 3 0 0 1 0 6h-4" />
        </template>

        <!-- Instagram / camera -->
        <template v-else-if="name === 'camera'">
            <path d="M4 7h4l2-2h4l2 2h4v12H4V7z" />
            <circle cx="12" cy="13" r="3.5" />
        </template>

        <!-- Menu -->
        <template v-else-if="name === 'menu'">
            <path d="M4 7h16M4 12h16M4 17h16" />
        </template>

        <!-- Campana -->
        <template v-else-if="name === 'bell'">
            <path d="M15 17H9l-1-2H6a4 4 0 0 1 4-4h0a4 4 0 0 1 4 4h-2l-1 2z" />
            <path d="M10 20a2 2 0 0 0 4 0" />
        </template>

        <!-- Esplora -->
        <template v-else-if="name === 'compass'">
            <circle cx="12" cy="12" r="9" />
            <path d="M14.5 9.5L10 14l4.5-4.5zM9.5 14.5L14 10l-4.5 4.5z" fill="currentColor" stroke="none" />
        </template>

        <!-- Itinerari -->
        <template v-else-if="name === 'route'">
            <circle cx="6" cy="19" r="2" />
            <circle cx="18" cy="5" r="2" />
            <path d="M8 19h5a5 5 0 0 0 5-5V8" />
        </template>

        <!-- Profilo -->
        <template v-else-if="name === 'user'">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 20a8 8 0 0 1 16 0" />
        </template>

        <!-- Plus -->
        <template v-else-if="name === 'plus'">
            <path d="M12 5v14M5 12h14" />
        </template>

        <!-- Freccia indietro -->
        <template v-else-if="name === 'back'">
            <path d="M15 18l-6-6 6-6" />
        </template>

        <!-- Stella -->
        <template v-else-if="name === 'star'">
            <path d="M12 3l2.9 5.9 6.5.9-4.7 4.6 1.1 6.4L12 17.8 6.2 20.8l1.1-6.4L2.6 9.8l6.5-.9L12 3z" fill="currentColor" stroke="none" />
        </template>

        <!-- Fotocamera upload -->
        <template v-else-if="name === 'camera-add'">
            <path d="M4 7h4l2-2h4l2 2h4v12H4V7z" />
            <circle cx="12" cy="13" r="3" />
            <path d="M19 8v2M18 9h2" />
        </template>

        <!-- Accessibile -->
        <template v-else-if="name === 'accessible'">
            <circle cx="12" cy="4" r="2" />
            <path d="M7 8h10v3H7zM9 11v7M15 11v7M7 18h10" />
        </template>

        <!-- Orologio -->
        <template v-else-if="name === 'clock'">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
        </template>

        <!-- Chiudi -->
        <template v-else-if="name === 'close'">
            <path d="M6 6l12 12M18 6L6 18" />
        </template>
    </svg>
</template>
