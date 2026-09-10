<script setup lang="ts">
import { show } from '@/actions/App/Http/Controllers/Main/BrandController';
import { PPOBBrandDataItem } from '@/types/cms/ppob';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Gamepad2 } from 'lucide-vue-next';

defineProps<{
    brand: PPOBBrandDataItem;
}>();
</script>

<template>
    <div
        class="storefront-product group relative overflow-hidden rounded-2xl border border-border bg-card hover:border-primary/40"
    >
        <Link
            class="block h-full rounded-2xl focus-visible:outline-offset-[-3px]"
            :href="
                show({
                    brand: brand.slug,
                }).url
            "
        >
            <!-- Brand Image -->
            <div
                class="relative m-2 aspect-[4/5] overflow-hidden rounded-xl bg-muted"
            >
                <img
                    v-if="brand.image"
                    :src="brand.image"
                    :alt="brand.name"
                    class="h-full w-full object-cover"
                    loading="lazy"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center text-muted-foreground"
                >
                    <Gamepad2
                        class="size-16 text-primary/40"
                        :stroke-width="1"
                    />
                </div>

                <!-- Overlay on hover -->
                <div
                    class="pointer-events-none absolute inset-0 bg-linear-to-t from-slate-950/20 to-transparent"
                ></div>
            </div>

            <!-- Brand Info -->
            <div class="flex items-center justify-between gap-2 px-4 pt-2 pb-5">
                <!-- Name -->
                <h3
                    class="line-clamp-2 text-sm leading-6 font-semibold text-foreground"
                >
                    {{ brand.name }}
                </h3>
                <ArrowUpRight
                    class="size-4 shrink-0 text-muted-foreground transition-colors group-hover:text-primary"
                />
            </div>
        </Link>
    </div>
</template>
