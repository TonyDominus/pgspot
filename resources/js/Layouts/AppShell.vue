<script setup>
import { ref } from 'vue';
import BottomNav from '@/Components/Pg/BottomNav.vue';
import DesktopSidebar from '@/Components/Pg/DesktopSidebar.vue';
import PageTransition from '@/Components/Pg/PageTransition.vue';
import SideMenu from '@/Components/Pg/SideMenu.vue';

defineProps({
    activeNav: { type: String, default: 'explore' },
    noPadding: { type: Boolean, default: false },
    fullWidth: { type: Boolean, default: false },
});

const menuOpen = ref(false);
const openMenu = () => {
    menuOpen.value = true;
};
</script>

<template>
    <div class="flex min-h-screen bg-pg-background">
        <DesktopSidebar :active="activeNav" />

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <PageTransition>
                <div
                    class="flex-1"
                    :class="[
                        noPadding ? '' : 'pb-24 lg:pb-0',
                        fullWidth ? 'w-full' : 'mx-auto w-full max-w-4xl px-0 lg:px-6',
                    ]"
                >
                    <slot :open-menu="openMenu" />
                </div>
            </PageTransition>

            <BottomNav :active="activeNav" />
        </div>

        <SideMenu :open="menuOpen" @close="menuOpen = false" />
    </div>
</template>
