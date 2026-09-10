<script setup lang="ts">
import CartItemRow from '@/components/cart/CartItemRow.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { formatCurrency } from '@/lib/utils';
import { CartItemData } from '@/types/cart';
import { Link, usePage } from '@inertiajs/vue3';
import { Loader2, ShoppingCart } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const page = usePage();
const cartCount = computed(() => page.props.cart?.count ?? 0);

const open = ref(false);
const isLoading = ref(false);
const items = ref<CartItemData[]>([]);
const total = ref(0);

const fetchItems = async () => {
    isLoading.value = true;

    try {
        const response = await fetch('/cart/items', {
            headers: { Accept: 'application/json' },
        });
        const json = await response.json();

        items.value = json.data?.items ?? [];
        total.value = json.data?.total ?? 0;
    } finally {
        isLoading.value = false;
    }
};

watch(open, (isOpen) => {
    if (isOpen) {
        fetchItems();
    }
});

// Keep the drawer's local list in sync right after a quantity/remove change.
const handleChanged = () => fetchItems();
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative h-9 w-9 rounded-full border-0"
            >
                <ShoppingCart class="h-[1.2rem] w-[1.2rem]" />
                <span
                    v-if="cartCount > 0"
                    class="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-primary-foreground"
                >
                    {{ cartCount > 99 ? '99+' : cartCount }}
                </span>
                <span class="sr-only">Keranjang</span>
            </Button>
        </SheetTrigger>

        <SheetContent class="flex w-full flex-col sm:max-w-md">
            <SheetHeader>
                <SheetTitle>Keranjang Saya</SheetTitle>
            </SheetHeader>

            <div class="flex-1 overflow-y-auto px-4">
                <div v-if="isLoading" class="flex justify-center py-10">
                    <Loader2
                        class="h-6 w-6 animate-spin text-muted-foreground"
                    />
                </div>

                <div
                    v-else-if="items.length === 0"
                    class="flex flex-col items-center justify-center gap-2 py-16 text-center"
                >
                    <ShoppingCart class="h-10 w-10 text-muted-foreground" />
                    <p class="text-sm text-muted-foreground">
                        Keranjang kamu masih kosong.
                    </p>
                </div>

                <template v-else>
                    <CartItemRow
                        v-for="item in items"
                        :key="item.id"
                        :item="item"
                        @changed="handleChanged"
                    />
                </template>
            </div>

            <div v-if="items.length > 0" class="border-t border-border/50 p-4">
                <div class="mb-4 flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground"
                        >Total</span
                    >
                    <span class="text-lg font-bold text-primary">{{
                        formatCurrency(total)
                    }}</span>
                </div>
                <Link href="/cart" class="block" @click="open = false">
                    <Button class="w-full">Lihat Keranjang</Button>
                </Link>
            </div>
        </SheetContent>
    </Sheet>
</template>
