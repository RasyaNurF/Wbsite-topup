<script setup lang="ts">
import { checkout } from '@/actions/App/Http/Controllers/Main/CartController';
import { check as checkGameAccountRoute } from '@/actions/App/Http/Controllers/Main/CheckGameAccountController';
import { checkVoucher as checkVoucherRoute } from '@/actions/App/Http/Controllers/Main/TransactionController';
import AccountDataForm from '@/components/brand-detail/AccountDataForm.vue';
import ContactDetailsForm from '@/components/brand-detail/ContactDetailsForm.vue';
import PaymentMethodSelection from '@/components/brand-detail/PaymentMethodSelection.vue';
import CartItemRow from '@/components/cart/CartItemRow.vue';
import MainFooter from '@/components/MainFooter.vue';
import MainHeader from '@/components/MainHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSwal } from '@/composables/useSwal';
import { formatCurrency } from '@/lib/utils';
import { CartItemData } from '@/types/cart';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import axios from 'axios';
import {
    BadgeCheck,
    Loader2,
    ShoppingCart,
    TicketPercent,
    Trash2,
} from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps<{
    items: CartItemData[];
    total: number;
}>();

const page = usePage();
const user = page.props.auth.user;
const setting = page.props.setting;

const { confirm, toast } = useSwal();

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

// Checkout form
const form = useForm({
    name: user?.name || '',
    email: user?.email || '',
    phone: user?.phone || '',
    payment_type: 'manual' as 'manual' | 'automatic',
    payment_method: null as string | null,
    voucher_code: null as string | null,
});

// Every distinct brand in the cart needs its own player ID (e.g. buying
// Mobile Legends diamonds AND Honor of Kings diamonds in one checkout means
// two separate ID inputs, one per brand).
const accountGroups = computed(() => {
    const seen = new Map<number, (typeof props.items)[number]['product']>();

    for (const item of props.items) {
        const brand = item.product?.brand;
        if (brand && !seen.has(brand.id)) {
            seen.set(brand.id, item.product);
        }
    }

    return Array.from(seen.values()).map((product) => {
        const brand = product!.brand!;
        const settings = brand.settings ?? {};

        return {
            brandId: brand.id,
            brandName: brand.name,
            brandSlug: brand.slug,
            inputType: settings.type ?? 'id',
            labelId: settings.label_id ?? 'ID',
            labelServer: settings.label_server ?? 'Server',
            serverOptions: settings.servers ?? [],
        };
    });
});

// account_id / server_id per brand, keyed by brand id
const accountData = reactive<
    Record<number, { accountId: string; serverId: string }>
>({});

const ensureAccountEntry = (brandId: number) => {
    if (!accountData[brandId]) {
        accountData[brandId] = { accountId: '', serverId: '' };
    }
    return accountData[brandId];
};

watch(
    accountGroups,
    (groups) => groups.forEach((g) => ensureAccountEntry(g.brandId)),
    { immediate: true },
);

// Per-brand ML-style ID verification state
const checkState = reactive<
    Record<
        number,
        {
            resolvedUsername: string | null;
            checkError: string | null;
            isLoadingCheck: boolean;
        }
    >
>({});

const ensureCheckState = (brandId: number) => {
    if (!checkState[brandId]) {
        checkState[brandId] = {
            resolvedUsername: null,
            checkError: null,
            isLoadingCheck: false,
        };
    }
    return checkState[brandId];
};

const checkGameAccount = useDebounceFn(
    async (brandId: number, brandSlug: string, inputType: string) => {
        const account = accountData[brandId];
        const state = ensureCheckState(brandId);

        if (!account?.accountId) return;
        if (inputType === 'id+server' && !account.serverId) return;

        state.isLoadingCheck = true;
        state.resolvedUsername = null;
        state.checkError = null;

        try {
            const response = await axios.post(checkGameAccountRoute().url, {
                account_id: account.accountId,
                server_id: account.serverId,
                slug: brandSlug,
            });

            if (response.data.status || response.data.code === 200) {
                state.resolvedUsername =
                    response.data.data?.username ||
                    response.data.data?.data?.username;
            } else {
                state.checkError =
                    response.data.message || 'Game ID tidak ditemukan';
            }
        } catch (error: any) {
            state.resolvedUsername = null;
            state.checkError =
                error.response?.data?.message || 'Gagal mengecek Game ID';
        } finally {
            state.isLoadingCheck = false;
        }
    },
    800,
);

const manualBank = {
    id: setting?.manual_transfer_bank,
    name: setting?.manual_transfer_bank,
    account_number: setting?.manual_transfer_account_number,
    account_name: setting?.manual_transfer_account_name,
    img: setting?.manual_transfer_bank_logo,
};

const paymentMethods = [
    {
        id: 'qris',
        name: 'QRIS',
        fee: 0.007,
        action: 'multiply' as const,
        img: '/images/QRIS.svg',
    },
    {
        id: 'bca',
        name: 'Virtual Account BCA',
        fee: 4000,
        action: 'add' as const,
        img: '/images/BCA.svg',
    },
    {
        id: 'mandiri',
        name: 'Virtual Account Mandiri',
        fee: 4000,
        action: 'add' as const,
        img: '/images/MANDIRI.svg',
    },
    {
        id: 'bni',
        name: 'Virtual Account BNI',
        fee: 4000,
        action: 'add' as const,
        img: '/images/BNI.svg',
    },
    {
        id: 'bri',
        name: 'Virtual Account BRI',
        fee: 4000,
        action: 'add' as const,
        img: '/images/BRI.svg',
    },
    {
        id: 'permata',
        name: 'Virtual Account Permata',
        fee: 4000,
        action: 'add' as const,
        img: '/images/PERMATA.svg',
    },
];

// Voucher
const voucherCode = ref('');
const voucherError = ref<string | null>(null);
const discountAmount = ref(0);
const isCheckingVoucher = ref(false);
const appliedVoucherCode = ref<string | null>(null);

const checkVoucher = async () => {
    if (!voucherCode.value) return;

    isCheckingVoucher.value = true;
    voucherError.value = null;

    try {
        const response = await axios.post(checkVoucherRoute().url, {
            voucher_code: voucherCode.value,
            amount: props.total,
        });

        if (response.data.data.valid) {
            discountAmount.value = response.data.data.discount_amount;
            appliedVoucherCode.value = response.data.data.voucher_code;
            form.voucher_code = response.data.data.voucher_code;
        }
    } catch (error: any) {
        voucherError.value =
            error.response?.data?.message || 'Voucher tidak valid';
        discountAmount.value = 0;
        appliedVoucherCode.value = null;
        form.voucher_code = null;
    } finally {
        isCheckingVoucher.value = false;
    }
};

const removeVoucher = () => {
    voucherCode.value = '';
    discountAmount.value = 0;
    appliedVoucherCode.value = null;
    voucherError.value = null;
    form.voucher_code = null;
};

// Total with fee, same formula as the single-product checkout
const totalAmount = computed(() => {
    const priceAfterDiscount = Math.max(0, props.total - discountAmount.value);

    if (form.payment_type === 'manual') {
        return priceAfterDiscount;
    }

    if (!form.payment_method) return priceAfterDiscount;

    const method = paymentMethods.find((m) => m.id === form.payment_method);
    if (!method) return priceAfterDiscount;

    if (method.action === 'multiply') {
        return priceAfterDiscount + priceAfterDiscount * method.fee;
    }

    return priceAfterDiscount + method.fee;
});

const feeAmount = computed(() =>
    Math.max(0, totalAmount.value - (props.total - discountAmount.value)),
);

watch(
    () => props.total,
    () => {
        if (appliedVoucherCode.value) {
            removeVoucher();
        }
    },
);

const handleCheckout = () => {
    for (const group of accountGroups.value) {
        const account = accountData[group.brandId];

        if (!account?.accountId) {
            toast.fire({
                icon: 'error',
                title: `Mohon isi ${group.labelId} untuk ${group.brandName}`,
            });
            return;
        }

        if (group.inputType === 'id+server' && !account.serverId) {
            toast.fire({
                icon: 'error',
                title: `Mohon isi ${group.labelServer} untuk ${group.brandName}`,
            });
            return;
        }
    }

    if (!form.name || !form.phone) {
        toast.fire({ icon: 'error', title: 'Mohon lengkapi data kontak' });
        return;
    }

    if (!form.payment_method) {
        toast.fire({ icon: 'error', title: 'Mohon pilih metode pembayaran' });
        return;
    }

    form.transform((data) => ({
        ...data,
        account_id: Object.fromEntries(
            Object.entries(accountData).map(([brandId, v]) => [
                brandId,
                v.accountId,
            ]),
        ),
        server_id: Object.fromEntries(
            Object.entries(accountData).map(([brandId, v]) => [
                brandId,
                v.serverId,
            ]),
        ),
    })).post(checkout().url, {
        preserveScroll: true,
        onError: (errors) => {
            toast.fire({
                icon: 'error',
                title: Object.values(errors)[0] ?? 'Terjadi kesalahan',
            });
        },
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
                    <div class="space-y-6 lg:col-span-2">
                        <div
                            class="rounded-lg border border-border/50 bg-card px-4 shadow-sm"
                        >
                            <CartItemRow
                                v-for="item in items"
                                :key="item.id"
                                :item="item"
                            />
                        </div>

                        <ContactDetailsForm
                            v-model:name="form.name"
                            v-model:email="form.email"
                            v-model:phone="form.phone"
                            :form-errors="form.errors"
                        />

                        <AccountDataForm
                            v-for="group in accountGroups"
                            :key="group.brandId"
                            :input-type="group.inputType"
                            :label-id="`${group.labelId} (${group.brandName})`"
                            :label-server="group.labelServer"
                            :server-options="group.serverOptions"
                            :account-id="
                                accountData[group.brandId]?.accountId ?? ''
                            "
                            :server-id="
                                accountData[group.brandId]?.serverId ?? ''
                            "
                            :form-errors="{
                                account_id:
                                    form.errors[`account_id.${group.brandId}`],
                                server_id:
                                    form.errors[`server_id.${group.brandId}`],
                            }"
                            :resolved-username="
                                checkState[group.brandId]?.resolvedUsername
                            "
                            :check-error="checkState[group.brandId]?.checkError"
                            :is-loading-check="
                                checkState[group.brandId]?.isLoadingCheck
                            "
                            @update:account-id="
                                (value) => {
                                    ensureAccountEntry(
                                        group.brandId,
                                    ).accountId = value;
                                    checkGameAccount(
                                        group.brandId,
                                        group.brandSlug,
                                        group.inputType,
                                    );
                                }
                            "
                            @update:server-id="
                                (value) => {
                                    ensureAccountEntry(group.brandId).serverId =
                                        value;
                                    checkGameAccount(
                                        group.brandId,
                                        group.brandSlug,
                                        group.inputType,
                                    );
                                }
                            "
                        />

                        <PaymentMethodSelection
                            v-model:payment-type="form.payment_type"
                            v-model:selected-payment="form.payment_method"
                            :manual-bank="manualBank"
                            :payment-methods="paymentMethods"
                        />
                    </div>

                    <div class="lg:col-span-1">
                        <div class="sticky top-6 space-y-5">
                            <!-- Voucher -->
                            <div
                                class="rounded-lg border border-border/50 bg-card p-6 shadow-sm"
                            >
                                <h3
                                    class="mb-4 text-lg font-bold text-foreground"
                                >
                                    Kode Voucher
                                </h3>
                                <div class="flex gap-2">
                                    <div class="relative w-full">
                                        <div
                                            class="absolute top-2.5 left-2.5 text-muted-foreground"
                                        >
                                            <TicketPercent class="h-4 w-4" />
                                        </div>
                                        <Input
                                            v-model="voucherCode"
                                            placeholder="Masukkan kode voucher"
                                            class="pl-9"
                                            :disabled="!!appliedVoucherCode"
                                            @keyup.enter="checkVoucher"
                                        />
                                    </div>
                                    <Button
                                        v-if="!appliedVoucherCode"
                                        variant="secondary"
                                        :disabled="
                                            isCheckingVoucher || !voucherCode
                                        "
                                        @click="checkVoucher"
                                    >
                                        <Loader2
                                            v-if="isCheckingVoucher"
                                            class="h-4 w-4 animate-spin"
                                        />
                                        <span v-else>Gunakan</span>
                                    </Button>
                                    <Button
                                        v-else
                                        variant="destructive"
                                        @click="removeVoucher"
                                    >
                                        Hapus
                                    </Button>
                                </div>
                                <p
                                    v-if="voucherError"
                                    class="mt-2 text-xs text-red-500"
                                >
                                    {{ voucherError }}
                                </p>
                                <p
                                    v-if="appliedVoucherCode"
                                    class="mt-2 text-xs text-green-500"
                                >
                                    Voucher berhasil digunakan! Hemat
                                    {{ formatCurrency(discountAmount) }}
                                </p>
                            </div>

                            <!-- Summary -->
                            <div
                                class="rounded-lg border border-border/50 bg-card p-6 shadow-sm"
                            >
                                <h2
                                    class="mb-4 text-lg font-bold text-foreground"
                                >
                                    Ringkasan Belanja
                                </h2>

                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground"
                                            >Subtotal ({{
                                                items.length
                                            }}
                                            produk)</span
                                        >
                                        <span
                                            class="font-medium text-foreground"
                                            >{{ formatCurrency(total) }}</span
                                        >
                                    </div>

                                    <div
                                        v-if="discountAmount > 0"
                                        class="flex justify-between text-green-500"
                                    >
                                        <span class="font-medium">Diskon</span>
                                        <span class="font-medium"
                                            >-{{
                                                formatCurrency(discountAmount)
                                            }}</span
                                        >
                                    </div>

                                    <div
                                        v-if="
                                            form.payment_type === 'automatic' &&
                                            form.payment_method
                                        "
                                        class="flex justify-between"
                                    >
                                        <span class="text-muted-foreground"
                                            >Fee</span
                                        >
                                        <span
                                            class="font-medium text-foreground"
                                            >{{
                                                formatCurrency(feeAmount)
                                            }}</span
                                        >
                                    </div>

                                    <div
                                        class="flex justify-between border-t border-border/30 pt-2 text-base font-bold"
                                    >
                                        <span class="text-foreground"
                                            >Total</span
                                        >
                                        <span class="text-primary">{{
                                            formatCurrency(totalAmount)
                                        }}</span>
                                    </div>
                                </div>

                                <Button
                                    class="mt-6 w-full"
                                    size="lg"
                                    :disabled="form.processing"
                                    @click="handleCheckout"
                                >
                                    <Loader2
                                        v-if="form.processing"
                                        class="mr-2 h-4 w-4 animate-spin"
                                    />
                                    <BadgeCheck v-else class="mr-2 h-4 w-4" />
                                    {{
                                        form.processing
                                            ? 'Memproses...'
                                            : 'Checkout'
                                    }}
                                </Button>
                                <p
                                    v-if="form.errors.error"
                                    class="mt-2 text-center text-xs text-red-500"
                                >
                                    {{ form.errors.error }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <MainFooter />
    </div>
</template>
