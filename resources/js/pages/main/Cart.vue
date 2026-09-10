<script setup lang="ts">
import CartItemRow from '@/components/cart/CartItemRow.vue';
import MainFooter from '@/components/MainFooter.vue';
import MainHeader from '@/components/MainHeader.vue';
import { Button } from '@/components/ui/button';
import { useSwal } from '@/composables/useSwal';
import { formatCurrency } from '@/lib/utils';
import { CartItemData } from '@/types/cart';
import { Head, Link, router } from '@inertiajs/vue3';
import { ShoppingCart, Trash2 } from 'lucide-vue-next';

defineProps<{
    items: CartItemData[];
    total: number;
}>();

const { confirm } = useSwal();

const handleClear = () => {
    confirm({
        title: 'Kosongkan keranjang?',
        text: 'Semua produk di keranjang akan dihapus.',
        confirmButtonText: 'Ya, kosongkan',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete('/cart', {
                preserveScroll: true,
                only: ['cart', 'items', 'total'],
            });
        }
    });
};
</script>

<template>
    <Head title="Keranjang Saya" />

    <div class="flex min-h-screen flex-col bg-background">
        <MainHeader />

        <main class="flex-1">
            <div class="mx-auto max-w-5xl px-4 py-8">
                <div class="mb-6 flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-foreground">
                        Keranjang Saya
                    </h1>
                    <Button
                        v-if="items.length > 0"
                        variant="outline"
                        size="sm"
                        @click="handleClear"
                    >
                        <Trash2 class="h-4 w-4" />
                        Kosongkan
                    </Button>
                </div>

                <div
                    v-if="items.length === 0"
                    class="flex flex-col items-center justify-center gap-3 rounded-lg border border-border/50 bg-card py-24 text-center shadow-sm"
                >
                    <ShoppingCart class="h-12 w-12 text-muted-foreground" />
                    <p class="text-muted-foreground">
                        Keranjang kamu masih kosong.
                    </p>
                    <Link href="/">
                        <Button class="mt-2">Mulai Belanja</Button>
                    </Link>
                </div>

                <div v-else class="grid gap-6 lg:grid-cols-3">
                    <div
                        class="rounded-lg border border-border/50 bg-card px-4 shadow-sm lg:col-span-2"
                    >
                        <CartItemRow
                            v-for="item in items"
                            :key="item.id"
                            :item="item"
                        />
                    </div>

                    <div class="lg:col-span-1">
                        <div
                            class="sticky top-6 rounded-lg border border-border/50 bg-card p-6 shadow-sm"
                        >
                            <h2 class="mb-4 text-lg font-bold text-foreground">
                                Ringkasan Belanja
                            </h2>
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-muted-foreground"
                                    >Total ({{ items.length }} produk)</span
                                >
                                <span class="font-bold text-primary">{{
                                    formatCurrency(total)
                                }}</span>
                            </div>
                            <Button class="mt-6 w-full" size="lg" disabled>
                                Checkout
                            </Button>
                            <p
                                class="mt-2 text-center text-xs text-muted-foreground"
                            >
                                Checkout multi-produk segera hadir.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <MainFooter />
    </div>
</template>
