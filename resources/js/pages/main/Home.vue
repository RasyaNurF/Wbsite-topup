<script setup lang="ts">
import { index } from '@/actions/App/Http/Controllers/Main/HomeController';
import BrandCard from '@/components/BrandCard.vue';
import HeroBanner from '@/components/HeroBanner.vue';
import MainFooter from '@/components/MainFooter.vue';
import MainHeader from '@/components/MainHeader.vue';
import Maintenance from '@/pages/main/Maintenance.vue';
import { PaginationItem } from '@/types';
import { PPOBBrandDataItem, PPOBCategoryDataItem } from '@/types/cms/ppob';
import { SliderDataItem } from '@/types/cms/web';
import { Head, InfiniteScroll, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    CreditCard,
    Gamepad2,
    Headphones,
    Layers3,
    Sparkles,
} from 'lucide-vue-next';
import { computed } from 'vue';

defineProps<{
    sliders: SliderDataItem[];
    brands: PaginationItem<PPOBBrandDataItem>;
    featured_brands: PPOBBrandDataItem[];
    categories: PPOBCategoryDataItem[];
}>();

const page = usePage();
const setting = page.props.setting;
const selectedCategory = computed(
    () =>
        new URLSearchParams(page.url.split('?')[1] ?? '').get('category') ?? '',
);
</script>

<template>
    <div class="storefront flex min-h-screen flex-col bg-background">
        <Head>
            <title>{{ setting?.title }}</title>
            <component :is="'script'" type="application/ld+json">
                {{
                    JSON.stringify({
                        '@context': 'https://schema.org',
                        '@type': 'WebSite',
                        name: setting?.title,
                        url: index().url,
                        potentialAction: {
                            '@type': 'SearchAction',
                            target: {
                                '@type': 'EntryPoint',
                                urlTemplate: `${index().url}?search={search_term_string}`,
                            },
                            'query-input': 'required name=search_term_string',
                        },
                    })
                }}
            </component>
        </Head>

        <!-- Header -->
        <MainHeader />

        <template v-if="setting?.maintenance_status === 'active'">
            <Maintenance />
        </template>
        <!-- Main Content -->
        <main
            class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-12 px-5 py-6 sm:px-8 sm:py-10 lg:gap-16"
            v-else
        >
            <section
                class="storefront-reveal relative isolate overflow-hidden rounded-[2rem] bg-[#101c36] text-white"
            >
                <div
                    class="pointer-events-none absolute -top-32 right-0 size-96 rounded-full bg-blue-500/15 blur-3xl"
                    aria-hidden="true"
                ></div>
                <div
                    class="relative grid items-center gap-8 p-7 sm:p-10 lg:grid-cols-2 lg:gap-12 lg:p-14"
                >
                    <div class="flex flex-col items-start gap-6">
                        <p
                            class="flex items-center gap-2 text-xs font-semibold tracking-[0.18em] text-blue-200 uppercase"
                        >
                            <span
                                class="size-1.5 rounded-full bg-blue-300"
                            ></span>
                            Your next level starts here
                        </p>
                        <h1
                            class="max-w-lg text-4xl leading-[1.1] font-semibold tracking-[-0.045em] sm:text-5xl lg:text-6xl"
                        >
                            Lebih banyak main.<br /><span class="text-blue-300"
                                >Lebih sedikit ribet.</span
                            >
                        </h1>
                        <p
                            class="max-w-sm text-sm leading-7 text-slate-300 sm:text-base"
                        >
                            Temukan game favoritmu, pilih nominal, dan nikmati
                            cara top up yang lebih praktis.
                        </p>
                        <a
                            href="#catalog"
                            class="storefront-button inline-flex items-center gap-3 rounded-full bg-white px-6 py-3.5 text-sm font-semibold text-slate-900 hover:bg-blue-100"
                            >Jelajahi produk <ArrowRight class="size-4"
                        /></a>
                        <div
                            class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-400"
                        >
                            <span>Pilih game</span
                            ><span aria-hidden="true">/</span
                            ><span>Pilih nominal</span
                            ><span aria-hidden="true">/</span
                            ><span>Siap bermain</span>
                        </div>
                    </div>
                    <HeroBanner
                        v-if="sliders.length"
                        class="w-full shadow-2xl shadow-black/20"
                        :slides="sliders"
                        :autoplay="true"
                        :interval="7000"
                    />
                    <div
                        v-else
                        class="relative flex min-h-60 items-center justify-center overflow-hidden rounded-3xl border border-white/10 bg-white/[0.03] sm:min-h-72 lg:min-h-80"
                        aria-hidden="true"
                    >
                        <div
                            class="absolute size-64 rounded-full border border-blue-200/10"
                        ></div>
                        <div
                            class="absolute size-48 rounded-full border border-blue-200/15"
                        ></div>
                        <div
                            class="absolute inset-x-0 top-6 text-center text-[10px] tracking-[0.35em] text-blue-200/60"
                        >
                            MAKE TIME TO PLAY
                        </div>
                        <Gamepad2
                            class="size-32 -rotate-12 text-blue-200 drop-shadow-[0_12px_32px_rgba(96,165,250,0.3)] sm:size-40"
                            :stroke-width="1"
                        />
                        <span
                            class="absolute right-6 bottom-6 text-xs font-medium tracking-widest text-blue-100/60"
                            >PLAY. TOP UP. REPEAT.</span
                        >
                        <Sparkles
                            class="absolute top-14 right-12 size-6 text-blue-300"
                            :stroke-width="1.5"
                        />
                    </div>
                </div>
            </section>

            <div
                class="grid gap-5 border-b border-border pb-8 sm:grid-cols-3 sm:gap-8"
            >
                <div
                    v-for="benefit in [
                        {
                            icon: Gamepad2,
                            title: 'Game favorit, satu tempat',
                            text: 'Jelajahi pilihan produk untukmu.',
                        },
                        {
                            icon: CreditCard,
                            title: 'Pembayaran lebih mudah',
                            text: 'Pilih metode yang paling nyaman.',
                        },
                        {
                            icon: Headphones,
                            title: 'Selalu terhubung',
                            text: 'Pantau status pesanan kapan saja.',
                        },
                    ]"
                    :key="benefit.title"
                    class="flex items-center gap-4"
                >
                    <component
                        :is="benefit.icon"
                        class="size-6 shrink-0 text-primary"
                        :stroke-width="1.5"
                    />
                    <div>
                        <p class="text-sm font-semibold tracking-tight">
                            {{ benefit.title }}
                        </p>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">
                            {{ benefit.text }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Categories Section -->
            <section aria-labelledby="category-heading">
                <div class="mb-5 flex items-end justify-between gap-4">
                    <div>
                        <p class="storefront-eyebrow">Temukan pilihanmu</p>
                        <h2
                            id="category-heading"
                            class="mt-2 text-2xl font-semibold tracking-tight text-foreground"
                        >
                            Mulai dari kategori
                        </h2>
                    </div>
                </div>
                <div class="flex gap-3 overflow-x-auto pb-3">
                    <Link
                        :href="index().url"
                        preserve-scroll
                        :aria-current="!selectedCategory ? 'page' : undefined"
                        class="storefront-category"
                        :class="{ 'is-active': !selectedCategory }"
                        ><Layers3 class="size-5" /><span
                            >Semua produk</span
                        ></Link
                    >
                    <Link
                        v-for="category in categories"
                        :href="
                            index({ query: { category: category.slug } }).url
                        "
                        :key="category.id"
                        preserve-scroll
                        class="storefront-category"
                        :class="{
                            'is-active': selectedCategory === category.slug,
                        }"
                        :aria-current="
                            selectedCategory === category.slug
                                ? 'page'
                                : undefined
                        "
                    >
                        <img
                            v-if="category.image"
                            :src="category.image"
                            :alt="category.name"
                            class="size-6 rounded-md object-contain"
                            loading="lazy"
                        />
                        <Gamepad2 v-else class="size-5" />
                        <span class="text-sm font-semibold text-foreground">
                            {{ category.name }}
                        </span>
                        <span class="text-xs opacity-60">{{
                            category.active_brands_count
                        }}</span>
                    </Link>
                </div>
            </section>

            <!-- Featured Brands Section -->
            <section
                v-if="featured_brands.length > 0"
                aria-labelledby="featured-heading"
            >
                <div class="mb-6">
                    <p class="storefront-eyebrow">Pilihan unggulan</p>
                    <h2
                        id="featured-heading"
                        class="mt-2 text-2xl font-semibold tracking-tight text-foreground"
                    >
                        Layak jadi favoritmu
                    </h2>
                </div>
                <div
                    class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6 lg:grid-cols-5"
                >
                    <BrandCard
                        v-for="brand in featured_brands"
                        :key="brand.id"
                        :brand="brand"
                    />
                </div>
            </section>

            <!-- Brands Section -->
            <section
                id="catalog"
                class="scroll-mt-28 pb-6"
                aria-labelledby="catalog-heading"
            >
                <div
                    class="mb-6 flex flex-wrap items-end justify-between gap-3"
                >
                    <div>
                        <p class="storefront-eyebrow">Jelajahi katalog</p>
                        <h2
                            id="catalog-heading"
                            class="mt-2 text-2xl font-semibold tracking-tight text-foreground"
                        >
                            Pilihan untuk waktu mainmu
                        </h2>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Pilih produk untuk mulai top up
                    </p>
                </div>

                <InfiniteScroll data="brands">
                    <div
                        class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6 lg:grid-cols-5"
                    >
                        <BrandCard
                            v-for="brand in brands?.data"
                            :key="brand.id"
                            :brand="brand"
                        />
                    </div>
                </InfiniteScroll>
                <div
                    v-if="!brands?.data?.length"
                    class="flex flex-col items-center gap-3 rounded-3xl border border-dashed border-border px-6 py-14 text-center"
                >
                    <Gamepad2
                        class="size-9 text-muted-foreground"
                        :stroke-width="1.5"
                    />
                    <h3 class="font-semibold">
                        Belum ada produk di kategori ini
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Coba jelajahi kategori lain untuk menemukan pilihanmu.
                    </p>
                    <Link
                        :href="index().url"
                        preserve-scroll
                        class="mt-2 text-sm font-semibold text-primary"
                        >Lihat semua produk
                        <span aria-hidden="true">&rarr;</span></Link
                    >
                </div>
            </section>
        </main>

        <!-- Footer -->
        <MainFooter />
    </div>
</template>
