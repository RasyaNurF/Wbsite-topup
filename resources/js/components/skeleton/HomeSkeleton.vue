<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
import BrandCardSkeleton from './BrandCardSkeleton.vue';
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-8">
        <!-- HeroBanner skeleton -->
        <div class="mb-8 aspect-[3/1] overflow-hidden rounded-xl bg-muted">
            <Skeleton class="h-full w-full" />
        </div>

        <!-- Categories section skeleton -->
        <section class="mb-8">
            <div class="mb-4">
                <Skeleton class="h-6 w-[100px]" />
            </div>
            <div class="flex gap-3 overflow-x-auto pb-2">
                <div
                    v-for="i in 6"
                    :key="i"
                    class="flex min-w-[140px] flex-col items-center gap-2 rounded-lg border border-border/50 bg-card p-4"
                >
                    <Skeleton class="h-12 w-12 rounded-lg" />
                    <Skeleton class="h-4 w-[80px]" />
                    <Skeleton class="h-3 w-[60px]" />
                </div>
            </div>
        </section>

        <!-- Featured brands section skeleton -->
        <section class="mb-8">
            <div class="mb-4">
                <Skeleton class="h-6 w-[150px]" />
            </div>
            <div
                class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
            >
                <BrandCardSkeleton v-for="i in 6" :key="i" />
            </div>
        </section>

        <!-- Popular brands section skeleton -->
        <section>
            <div class="mb-6">
                <Skeleton class="h-6 w-[150px]" />
            </div>
            <div
                class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
            >
                <BrandCardSkeleton v-for="i in 12" :key="i" />
            </div>
        </section>
    </div>
</template>
