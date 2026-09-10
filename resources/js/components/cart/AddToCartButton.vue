<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useCart } from '@/composables/useCart';
import { ShoppingCart } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        productId: number;
        quantity?: number;
        variant?: 'default' | 'outline' | 'secondary' | 'ghost';
        size?: 'default' | 'sm' | 'lg' | 'icon';
        label?: string;
    }>(),
    {
        quantity: 1,
        variant: 'secondary',
        size: 'sm',
        label: 'Tambah ke Keranjang',
    },
);

const { addToCart, isLoading } = useCart();

const handleClick = (event: Event) => {
    // Prevent this from also triggering a parent click handler, e.g. a
    // product card that selects the product when clicked.
    event.stopPropagation();
    event.preventDefault();

    addToCart(props.productId, props.quantity);
};
</script>

<template>
    <Button
        type="button"
        :variant="variant"
        :size="size"
        :disabled="isLoading"
        @click="handleClick"
    >
        <ShoppingCart class="h-4 w-4" />
        <span v-if="size !== 'icon'">{{ label }}</span>
    </Button>
</template>
