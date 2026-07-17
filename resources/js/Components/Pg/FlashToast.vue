<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();

const visible = ref(false);
const message = ref('');
const type = ref('success');
let hideTimer;

function show(msg, msgType = 'success') {
    if (!msg) return;
    message.value = msg;
    type.value = msgType;
    visible.value = true;
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => {
        visible.value = false;
    }, 4500);
}

function dismiss() {
    visible.value = false;
    clearTimeout(hideTimer);
}

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) show(flash.success, 'success');
        if (flash?.error) show(flash.error, 'error');
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <Teleport to="body">
        <Transition name="toast">
            <div
                v-if="visible"
                class="fixed left-1/2 top-4 z-[900] flex max-w-sm -translate-x-1/2 items-start gap-3 rounded-2xl px-4 py-3 shadow-lg backdrop-blur-sm sm:left-auto sm:right-4 sm:translate-x-0"
                :class="type === 'error'
                    ? 'border border-red-200 bg-red-50 text-red-900'
                    : 'border border-green-200 bg-green-50 text-green-900'"
                role="alert"
            >
                <span class="text-lg leading-none">{{ type === 'error' ? '✕' : '✓' }}</span>
                <p class="flex-1 text-sm font-medium">{{ message }}</p>
                <button
                    type="button"
                    class="text-lg leading-none opacity-60 hover:opacity-100"
                    aria-label="Chiudi"
                    @click="dismiss"
                >
                    ×
                </button>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-12px);
}
</style>
