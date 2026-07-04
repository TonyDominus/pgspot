<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const site = computed(() => page.props.site ?? {});
</script>

<template>
    <footer class="border-t border-gray-100 bg-pg-surface px-4 py-8 text-sm text-pg-muted">
        <div class="mx-auto flex max-w-4xl flex-col gap-6 sm:flex-row sm:justify-between">
            <div>
                <p class="font-bold text-pg-primary">PG Spot</p>
                <p class="mt-1 text-xs">La mappa collaborativa di Perugia</p>
                <div v-if="site.paypalUrl" class="mt-3">
                    <a
                        :href="site.paypalUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-4 py-2 text-xs font-semibold text-amber-900 transition hover:bg-amber-200"
                    >
                        ☕ Offrimi un caffè
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 sm:gap-12">
                <div>
                    <p class="mb-2 font-semibold text-pg-text">Legale</p>
                    <ul class="space-y-1.5 text-xs">
                        <li><Link :href="route('legal.show', 'privacy')" class="hover:text-pg-primary">Privacy</Link></li>
                        <li><Link :href="route('legal.show', 'termini')" class="hover:text-pg-primary">Termini</Link></li>
                        <li><Link :href="route('legal.show', 'cookie')" class="hover:text-pg-primary">Cookie</Link></li>
                        <li><Link :href="route('legal.show', 'contatti')" class="hover:text-pg-primary">Contatti</Link></li>
                    </ul>
                </div>
                <div>
                    <p class="mb-2 font-semibold text-pg-text">Social</p>
                    <ul class="space-y-1.5 text-xs">
                        <li v-if="site.instagram">
                            <a :href="site.instagram" target="_blank" rel="noopener noreferrer" class="hover:text-pg-primary">Instagram</a>
                        </li>
                        <li v-if="site.facebook">
                            <a :href="site.facebook" target="_blank" rel="noopener noreferrer" class="hover:text-pg-primary">Facebook</a>
                        </li>
                        <li v-if="site.email">
                            <a :href="`mailto:${site.email}`" class="hover:text-pg-primary">{{ site.email }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <p class="mx-auto mt-6 max-w-4xl text-center text-[11px]">
            © {{ new Date().getFullYear() }} PG Spot — Dati mappa © OpenStreetMap contributors
        </p>
    </footer>
</template>
