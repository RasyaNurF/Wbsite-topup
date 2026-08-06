<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div
        class="overflow-hidden rounded-lg border border-border/50 bg-card shadow-md"
    >
        <!-- Image skeleton -->
        <div class="relative aspect-[3/4] overflow-hidden bg-muted">
            <Skeleton class="h-full w-full" />
        </div>

        <!-- Content skeleton -->
        <div class="p-4">
            <Skeleton class="mb-2 h-4 w-[80%]" />
        </div>
    </div>
</template>
