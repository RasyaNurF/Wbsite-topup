import {
    destroy,
    store,
    update,
} from '@/actions/App/Http/Controllers/Main/CartController';
import { useSwal } from '@/composables/useSwal';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Handles talking to the cart endpoints. Every mutation does a partial
 * Inertia reload of just the `cart` shared prop (badge/summary) by default,
 * so it stays cheap on pages that don't render the full cart list. Pass
 * `only` to also refresh page-specific props (e.g. the cart page itself).
 */
export function useCart() {
    const { toast } = useSwal();
    const isLoading = ref(false);

    const addToCart = (
        productId: number,
        quantity: number = 1,
        options: {
            only?: string[];
            silent?: boolean;
            onSuccess?: () => void;
        } = {},
    ) => {
        isLoading.value = true;

        router.post(
            store().url,
            { product_id: productId, quantity },
            {
                preserveScroll: true,
                preserveState: true,
                only: options.only ?? ['cart'],
                onSuccess: () => {
                    if (!options.silent) {
                        toast.fire({
                            icon: 'success',
                            title: 'Produk ditambahkan ke keranjang.',
                        });
                    }
                    options.onSuccess?.();
                },
                onError: () => {
                    toast.fire({
                        icon: 'error',
                        title: 'Gagal menambahkan produk ke keranjang.',
                    });
                },
                onFinish: () => {
                    isLoading.value = false;
                },
            },
        );
    };

    const updateQuantity = (
        cartItemId: number,
        quantity: number,
        options: { only?: string[]; onSuccess?: () => void } = {},
    ) => {
        isLoading.value = true;

        router.patch(
            update({ cartItem: cartItemId }).url,
            { quantity },
            {
                preserveScroll: true,
                preserveState: true,
                only: options.only ?? ['cart', 'items', 'total'],
                onSuccess: () => options.onSuccess?.(),
                onFinish: () => {
                    isLoading.value = false;
                },
            },
        );
    };

    const removeItem = (
        cartItemId: number,
        options: { only?: string[]; onSuccess?: () => void } = {},
    ) => {
        isLoading.value = true;

        router.delete(destroy({ cartItem: cartItemId }).url, {
            preserveScroll: true,
            preserveState: true,
            only: options.only ?? ['cart', 'items', 'total'],
            onSuccess: () => {
                toast.fire({
                    icon: 'success',
                    title: 'Produk dihapus dari keranjang.',
                });
                options.onSuccess?.();
            },
            onFinish: () => {
                isLoading.value = false;
            },
        });
    };

    return {
        isLoading,
        addToCart,
        updateQuantity,
        removeItem,
    };
}
