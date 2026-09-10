<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useCart } from '@/composables/useCart';
import { formatCurrency } from '@/lib/utils';
import { CartItemData } from '@/types/cart';
import { Loader2, Minus, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    item: CartItemData;
}>();

const emit = defineEmits<{
    changed: [];
}>();

const { updateQuantity, removeItem, isLoading } = useCart();

const decrease = () => {
    if (props.item.quantity <= 1) {
        removeItem(props.item.id, { onSuccess: () => emit('changed') });
        return;
    }

    updateQuantity(props.item.id, props.item.quantity - 1, {
        onSuccess: () => emit('changed'),
    });
};

const increase = () => {
    updateQuantity(props.item.id, props.item.quantity + 1, {
        onSuccess: () => emit('changed'),
    });
};

const remove = () => {
    removeItem(props.item.id, { onSuccess: () => emit('changed') });
};
</script>

<template>
    <div class="flex gap-3 border-b border-border/50 py-4 last:border-b-0">
        <div
            class="flex h-16 w-16 flex-shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted"
        >
            <img
                v-if="item.product?.image"
                :src="item.product.image"
                :alt="item.product?.name"
                class="h-full w-full object-cover"
            />
        </div>

        <div class="flex flex-1 flex-col gap-1">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p
                        v-if="item.product?.brand"
                        class="text-xs text-muted-foreground"
                    >
                        {{ item.product.brand.name }}
                    </p>
                    <p class="text-sm font-semibold text-foreground">
                        {{ item.product?.name }}
                    </p>
                </div>
                <button
                    type="button"
                    class="text-muted-foreground hover:text-destructive"
                    :disabled="isLoading"
                    @click="remove"
                >
                    <Trash2 class="h-4 w-4" />
                </button>
            </div>

            <div class="mt-1 flex items-center justify-between">
                <p class="text-sm font-bold text-primary">
                    {{ formatCurrency(item.price) }}
                </p>

                <div
                    class="flex items-center gap-1 rounded-md border border-border/50"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        :disabled="isLoading"
                        @click="decrease"
                    >
                        <Minus class="h-3 w-3" />
                    </Button>
                    <span class="w-6 text-center text-sm font-medium">
                        {{ item.quantity }}
                    </span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        :disabled="isLoading"
                        @click="increase"
                    >
                        <Plus v-if="!isLoading" class="h-3 w-3" />
                        <Loader2 v-else class="h-3 w-3 animate-spin" />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
