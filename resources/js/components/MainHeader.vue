<script setup lang="ts">
import CartSheet from '@/components/cart/CartSheet.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { useAppearance } from '@/composables/useAppearance';
import { useSwal } from '@/composables/useSwal';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    LogIn,
    Menu,
    Monitor,
    Moon,
    Sun,
    UserPlus,
    X,
} from 'lucide-vue-next';
import { ref } from 'vue';
import Button from './ui/button/Button.vue';

interface Props {
    showSearch?: boolean;
    showBackButton?: boolean;
}

const page = usePage();
const mobileMenuOpen = ref(false);
const { confirm } = useSwal();
const { appearance, updateAppearance } = useAppearance();

// Use setting title or default
const appName = page.props.setting?.title || 'GameStore';
const appLogo = page.props.setting?.logo;

withDefaults(defineProps<Props>(), {
    showSearch: false,
    showBackButton: false,
});

const goBack = () => {
    router.visit('/');
};

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};

const logout = () => {
    confirm({
        title: 'Apakah Anda yakin ingin keluar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, keluar',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post('/logout');
        }
    });
};
</script>

<template>
    <header
        class="sticky top-0 z-40 border-b border-border/70 bg-background/90 backdrop-blur-xl"
    >
        <div class="mx-auto max-w-7xl px-5 py-4 sm:px-8">
            <div class="flex items-center justify-between gap-4">
                <!-- Logo -->
                <Link
                    href="/"
                    class="flex min-w-0 shrink-0 items-center gap-3"
                    :aria-label="appName + ' — Beranda'"
                >
                    <img
                        v-if="appLogo"
                        :src="appLogo"
                        alt="Logo"
                        class="h-10 w-10 rounded-xl object-cover"
                    />
                    <div
                        v-else
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-primary-foreground"
                    >
                        <span class="text-xl font-bold">{{
                            appName.charAt(0)
                        }}</span>
                    </div>
                    <span
                        class="hidden max-w-40 truncate text-lg font-semibold tracking-tight text-foreground sm:inline"
                    >
                        {{ appName }}
                    </span>
                </Link>

                <!-- Search Bar (only on home) -->
                <div v-if="showSearch" class="max-w-xl flex-1">
                    <Input
                        type="search"
                        placeholder="Cari nama game"
                        class="w-full border-border/50 bg-background/50 text-foreground placeholder:text-muted-foreground"
                    />
                </div>

                <!-- Spacer when search is hidden -->
                <div v-else class="flex-1"></div>

                <!-- Desktop Navigation -->
                <nav
                    class="hidden items-center gap-5 lg:flex"
                    aria-label="Navigasi utama"
                >
                    <Link
                        href="/"
                        class="storefront-nav-link"
                        :aria-current="
                            page.url === '/' || page.url.startsWith('/?')
                                ? 'page'
                                : undefined
                        "
                    >
                        Beranda
                    </Link>
                    <Link
                        href="/transaction"
                        class="storefront-nav-link"
                        :aria-current="
                            page.url.startsWith('/transaction')
                                ? 'page'
                                : undefined
                        "
                    >
                        Cek Transaksi
                    </Link>
                    <Link
                        href="/cart"
                        class="storefront-nav-link"
                        :aria-current="
                            page.url.startsWith('/cart') ? 'page' : undefined
                        "
                    >
                        Keranjang
                    </Link>
                    <Link
                        href="/profile"
                        class="storefront-nav-link"
                        :aria-current="
                            page.url.startsWith('/profile') ? 'page' : undefined
                        "
                    >
                        Profil
                    </Link>
                </nav>

                <!-- Right side container for mobile -->
                <div class="flex items-center gap-2">
                    <!-- Cart -->
                    <CartSheet />

                    <!-- Theme Switcher -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-10 w-10 rounded-full border border-border/70"
                            >
                                <Sun
                                    v-if="appearance === 'light'"
                                    class="h-[1.2rem] w-[1.2rem] scale-100 rotate-0 transition-all dark:scale-0 dark:-rotate-90"
                                />
                                <Moon
                                    v-else-if="appearance === 'dark'"
                                    class="absolute h-[1.2rem] w-[1.2rem] scale-0 rotate-90 transition-all dark:scale-100 dark:rotate-0"
                                />
                                <Monitor v-else class="h-[1.2rem] w-[1.2rem]" />
                                <span class="sr-only">Toggle theme</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem
                                @click="updateAppearance('light')"
                            >
                                <Sun class="mr-2 h-4 w-4" />
                                <span>Light</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="updateAppearance('dark')">
                                <Moon class="mr-2 h-4 w-4" />
                                <span>Dark</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                @click="updateAppearance('system')"
                            >
                                <Monitor class="mr-2 h-4 w-4" />
                                <span>System</span>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <!-- Mobile Menu Button -->
                    <button
                        v-if="!showBackButton"
                        class="flex size-10 items-center justify-center rounded-full border border-border text-foreground transition-colors hover:bg-muted lg:hidden"
                        :aria-expanded="mobileMenuOpen"
                        aria-controls="storefront-mobile-menu"
                        :aria-label="
                            mobileMenuOpen ? 'Tutup menu' : 'Buka menu'
                        "
                        @click="toggleMobileMenu"
                    >
                        <Menu v-if="!mobileMenuOpen" class="h-6 w-6" />
                        <X v-else class="h-6 w-6" />
                    </button>

                    <!-- Back Button -->
                    <button
                        v-if="showBackButton"
                        class="flex items-center gap-2 text-sm font-medium text-foreground hover:text-primary"
                        @click="goBack"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span class="hidden md:inline">Kembali</span>
                    </button>

                    <!-- Auth Buttons -->
                    <div
                        v-if="!page.props.auth.user"
                        class="hidden items-center gap-2 lg:flex"
                    >
                        <Link href="/login">
                            <Button variant="ghost" class="rounded-full px-4">
                                <!-- LogIn Icon -->
                                <LogIn class="h-4 w-4" />
                                <span>Masuk</span>
                            </Button>
                        </Link>
                        <Link href="/register">
                            <Button class="storefront-button rounded-full px-5">
                                <!-- UserPlus Icon -->
                                <UserPlus class="h-4 w-4" />
                                <span>Daftar</span>
                            </Button>
                        </Link>
                    </div>
                    <Button
                        v-else
                        variant="outline"
                        class="hidden rounded-full lg:flex"
                        @click="logout"
                    >
                        <!-- LogOut Icon -->
                        <LogIn class="h-4 w-4 rotate-180" />
                        <span>Keluar</span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <Transition name="storefront-menu">
            <div
                v-if="mobileMenuOpen"
                id="storefront-mobile-menu"
                class="border-t border-border/50 bg-card lg:hidden"
            >
                <nav class="mx-auto max-w-7xl space-y-1 px-4 py-4">
                    <Link
                        href="/"
                        class="block rounded-md px-3 py-2 text-base font-medium text-foreground hover:bg-muted hover:text-primary"
                        @click="closeMobileMenu"
                    >
                        Beranda
                    </Link>
                    <Link
                        href="/transaction"
                        class="block rounded-md px-3 py-2 text-base font-medium text-foreground hover:bg-muted hover:text-primary"
                        @click="closeMobileMenu"
                    >
                        Cek Transaksi
                    </Link>
                    <Link
                        href="/cart"
                        class="block rounded-md px-3 py-2 text-base font-medium text-foreground hover:bg-muted hover:text-primary"
                        @click="closeMobileMenu"
                    >
                        Keranjang
                    </Link>
                    <Link
                        href="/profile"
                        class="block rounded-md px-3 py-2 text-base font-medium text-foreground hover:bg-muted hover:text-primary"
                        @click="closeMobileMenu"
                    >
                        Profile
                    </Link>

                    <!-- Mobile Auth Links -->
                    <template v-if="!page.props.auth.user">
                        <Link
                            href="/login"
                            class="block rounded-md px-3 py-2 text-base font-medium text-foreground hover:bg-muted hover:text-primary"
                            @click="closeMobileMenu"
                        >
                            Log In
                        </Link>
                        <Link
                            href="/register"
                            class="block rounded-md px-3 py-2 text-base font-medium text-foreground hover:bg-muted hover:text-primary"
                            @click="closeMobileMenu"
                        >
                            Sign Up
                        </Link>
                    </template>
                    <button
                        v-else
                        class="block w-full rounded-md px-3 py-2 text-left text-base font-medium text-foreground hover:bg-muted hover:text-primary"
                        @click="logout"
                    >
                        Log Out
                    </button>
                </nav>
            </div>
        </Transition>
    </header>
</template>
