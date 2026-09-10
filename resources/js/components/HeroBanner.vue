<script setup lang="ts">
import { SliderDataItem } from '@/types/cms/web';
import { Pause, Play } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

interface Props {
    slides: SliderDataItem[];
    autoplay?: boolean;
    interval?: number;
}

const props = defineProps<Props>();

const currentSlide = ref(0);
const autoplayPaused = ref(false);
let autoplayTimer: ReturnType<typeof setInterval> | null = null;

const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % props.slides.length;
};

const prevSlide = () => {
    currentSlide.value =
        currentSlide.value === 0
            ? props.slides.length - 1
            : currentSlide.value - 1;
};

const goToSlide = (index: number) => {
    currentSlide.value = index;
};

const startAutoplay = () => {
    stopAutoplay();
    if (
        props.autoplay &&
        props.slides.length > 1 &&
        !autoplayPaused.value &&
        !window.matchMedia('(prefers-reduced-motion: reduce)').matches
    ) {
        autoplayTimer = setInterval(nextSlide, props.interval ?? 7000);
    }
};

const toggleAutoplay = () => {
    autoplayPaused.value = !autoplayPaused.value;
    if (autoplayPaused.value) {
        stopAutoplay();
    } else {
        startAutoplay();
    }
};

const resumeAfterFocus = (event: FocusEvent) => {
    if (
        !(event.currentTarget as HTMLElement).contains(
            event.relatedTarget as Node | null,
        )
    ) {
        startAutoplay();
    }
};

const stopAutoplay = () => {
    if (autoplayTimer) {
        clearInterval(autoplayTimer);
        autoplayTimer = null;
    }
};

onMounted(() => {
    startAutoplay();
});

onUnmounted(() => {
    stopAutoplay();
});
</script>

<template>
    <div
        class="relative overflow-hidden rounded-2xl bg-slate-800"
        role="region"
        aria-roledescription="carousel"
        aria-label="Promo pilihan"
        @mouseenter="stopAutoplay"
        @mouseleave="startAutoplay"
        @focusin="stopAutoplay"
        @focusout="resumeAfterFocus"
    >
        <!-- Slides -->
        <div class="relative aspect-[3/2] w-full">
            <div
                v-for="(slide, index) in slides"
                :key="slide.id"
                class="absolute inset-0 transition-opacity duration-700 motion-reduce:transition-none"
                :aria-hidden="index !== currentSlide"
                :class="index === currentSlide ? 'opacity-100' : 'opacity-0'"
            >
                <img
                    :src="slide.image"
                    :alt="slide.title"
                    class="h-full w-full object-cover"
                    :loading="index === 0 ? 'eager' : 'lazy'"
                    :fetchpriority="index === 0 ? 'high' : 'auto'"
                />
            </div>
        </div>

        <!-- Navigation Arrows -->
        <button
            v-if="slides.length > 1"
            class="absolute top-1/2 left-4 -translate-y-1/2 rounded-full bg-white/80 p-2 shadow-md transition-all hover:bg-white"
            @click="prevSlide"
            aria-label="Promo sebelumnya"
        >
            <svg
                class="h-6 w-6 text-gray-800"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 19l-7-7 7-7"
                />
            </svg>
        </button>

        <button
            v-if="slides.length > 1"
            class="absolute top-1/2 right-4 -translate-y-1/2 rounded-full bg-white/80 p-2 shadow-md transition-all hover:bg-white"
            @click="nextSlide"
            aria-label="Promo berikutnya"
        >
            <svg
                class="h-6 w-6 text-gray-800"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5l7 7-7 7"
                />
            </svg>
        </button>

        <!-- Indicators -->
        <div
            v-if="slides.length > 1"
            class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2"
        >
            <button
                v-for="(slide, index) in slides"
                :key="`indicator-${slide.id}`"
                class="h-2 rounded-full transition-all"
                :class="
                    index === currentSlide
                        ? 'w-8 bg-white'
                        : 'w-2 bg-white/50 hover:bg-white/75'
                "
                @click="goToSlide(index)"
                :aria-label="'Lihat promo ' + (index + 1)"
                :aria-current="index === currentSlide ? 'true' : undefined"
            />
        </div>
        <button
            v-if="autoplay && slides.length > 1"
            class="absolute right-4 bottom-3 flex size-9 items-center justify-center rounded-full bg-slate-950/70 text-white"
            :aria-label="
                autoplayPaused ? 'Putar promo otomatis' : 'Jeda promo otomatis'
            "
            @click="toggleAutoplay"
        >
            <Play v-if="autoplayPaused" class="size-4" /><Pause
                v-else
                class="size-4"
            />
        </button>
    </div>
</template>
