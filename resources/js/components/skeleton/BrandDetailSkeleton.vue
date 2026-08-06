<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <!-- Banner skeleton -->
        <div class="relative h-64 overflow-hidden bg-muted">
            <Skeleton class="h-full w-full" />
        </div>

        <!-- Main Content -->
        <div class="mx-auto max-w-7xl px-4 py-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left Column - Forms skeleton -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Step 1 skeleton -->
                    <div
                        class="rounded-lg border border-border/50 bg-card p-6 shadow-sm"
                    >
                        <div class="mb-4 flex items-center gap-2">
                            <Skeleton class="h-7 w-7 rounded-full" />
                            <Skeleton class="h-5 w-[150px]" />
                        </div>
                        <div class="space-y-4">
                            <div>
                                <Skeleton class="mb-2 h-4 w-[100px]" />
                                <Skeleton class="h-10 w-full" />
                            </div>
                            <div>
                                <Skeleton class="mb-2 h-4 w-[100px]" />
                                <Skeleton class="h-10 w-full" />
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 skeleton -->
                    <div
                        class="rounded-lg border border-border/50 bg-card p-6 shadow-sm"
                    >
                        <div class="mb-4 flex items-center gap-2">
                            <Skeleton class="h-7 w-7 rounded-full" />
                            <Skeleton class="h-5 w-[120px]" />
                        </div>
                        <div
                            class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
                        >
                            <div
                                v-for="i in 8"
                                :key="i"
                                class="flex flex-col overflow-hidden rounded-xl border-2 border-border/50"
                            >
                                <Skeleton class="h-4 w-[80%] p-4" />
                                <Skeleton class="mt-2 h-8 w-[60%] pl-4" />
                                <Skeleton class="mt-5 h-16 w-full" />
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 skeleton -->
                    <div
                        class="rounded-lg border border-border/50 bg-card p-6 shadow-sm"
                    >
                        <div class="mb-4 flex items-center gap-2">
                            <Skeleton class="h-7 w-7 rounded-full" />
                            <Skeleton class="h-5 w-[150px]" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Skeleton class="mb-2 h-4 w-[80px]" />
                                <Skeleton class="h-10 w-full" />
                            </div>
                            <div>
                                <Skeleton class="mb-2 h-4 w-[100px]" />
                                <Skeleton class="h-10 w-full" />
                            </div>
                            <div>
                                <Skeleton class="mb-2 h-4 w-[60px]" />
                                <Skeleton class="h-10 w-full" />
                            </div>
                        </div>
                    </div>

                    <!-- Step 4 skeleton -->
                    <div
                        class="rounded-lg border border-border/50 bg-card p-6 shadow-sm"
                    >
                        <div class="mb-4 flex items-center gap-2">
                            <Skeleton class="h-7 w-7 rounded-full" />
                            <Skeleton class="h-5 w-[200px]" />
                        </div>
                        <div class="mb-4 flex gap-2">
                            <Skeleton class="h-10 flex-1" />
                            <Skeleton class="h-10 flex-1" />
                        </div>
                        <div class="space-y-2">
                            <Skeleton class="h-16 w-full" />
                            <Skeleton class="h-16 w-full" />
                            <Skeleton class="h-16 w-full" />
                        </div>
                    </div>
                </div>

                <!-- Right Column - OrderSummary skeleton -->
                <div class="lg:col-span-1">
                    <div
                        class="sticky top-4 rounded-lg border border-border/50 bg-card p-6 shadow-sm"
                    >
                        <Skeleton class="mb-4 h-5 w-[150px]" />
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <Skeleton class="h-4 w-[80px]" />
                                <Skeleton class="h-4 w-[100px]" />
                            </div>
                            <div class="flex justify-between">
                                <Skeleton class="h-4 w-[100px]" />
                                <Skeleton class="h-4 w-[80px]" />
                            </div>
                            <div class="flex justify-between">
                                <Skeleton class="h-4 w-[60px]" />
                                <Skeleton class="h-4 w-[120px]" />
                            </div>
                            <div class="border-t pt-4">
                                <div class="flex justify-between">
                                    <Skeleton class="h-5 w-[80px]" />
                                    <Skeleton class="h-5 w-[150px]" />
                                </div>
                            </div>
                        </div>
                        <Skeleton class="mt-6 h-12 w-full" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
