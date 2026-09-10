<script setup lang="ts">
import { home, privacyPolicy, terms } from '@/routes';
import { check } from '@/routes/transaction';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Gamepad2, Mail, Phone } from 'lucide-vue-next';
const page = usePage();
</script>

<template>
    <footer class="border-t border-border bg-card">
        <div class="mx-auto max-w-7xl px-5 pt-12 pb-6 sm:px-8 sm:pt-16">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Company Info -->
                <div class="lg:col-span-2">
                    <Link
                        :href="home().url"
                        class="mb-5 inline-flex items-center gap-3 text-lg font-semibold tracking-tight"
                        ><Gamepad2
                            class="size-6 text-primary"
                            :stroke-width="1.5"
                        />{{
                            page.props.setting?.title || page.props.name
                        }}</Link
                    >
                    <p class="max-w-sm text-sm leading-7 text-muted-foreground">
                        {{
                            page.props.setting.footer_description ||
                            'Tempat untuk menemukan kebutuhan game favoritmu. Pilih produk dan nikmati pengalaman top up yang lebih mudah.'
                        }}
                    </p>
                    <Link
                        :href="check().url"
                        class="mt-5 inline-flex items-center gap-2 text-sm font-medium text-primary"
                        >Lacak pesananmu <ArrowUpRight class="size-4"
                    /></Link>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4
                        class="mb-5 text-xs font-semibold tracking-widest text-foreground uppercase"
                    >
                        Jelajahi
                    </h4>
                    <ul class="space-y-3.5 text-sm">
                        <li>
                            <Link
                                :href="home().url"
                                class="text-muted-foreground transition-colors hover:text-primary"
                            >
                                Beranda
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="check().url"
                                class="text-muted-foreground transition-colors hover:text-primary"
                            >
                                Cek Transaksi
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="privacyPolicy().url"
                                class="text-muted-foreground transition-colors hover:text-primary"
                            >
                                Kebijakan Privasi
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="terms().url"
                                class="text-muted-foreground transition-colors hover:text-primary"
                            >
                                Syarat & Ketentuan
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4
                        class="mb-5 text-xs font-semibold tracking-widest text-foreground uppercase"
                    >
                        Hubungi kami
                    </h4>
                    <ul class="space-y-3 text-sm">
                        <li
                            v-if="page.props.setting.email"
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Mail class="h-4 w-4" />
                            <a
                                :href="'mailto:' + page.props.setting.email"
                                class="break-all transition-colors hover:text-primary"
                                >{{ page.props.setting.email }}</a
                            >
                        </li>
                        <li
                            v-if="page.props.setting.phone"
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Phone class="h-4 w-4" />
                            <a
                                :href="'tel:' + page.props.setting.phone"
                                class="transition-colors hover:text-primary"
                                >{{ page.props.setting.phone }}</a
                            >
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Copyright -->
            <div
                class="mt-12 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-6 text-xs leading-6 text-muted-foreground"
            >
                <p>
                    &copy; {{ new Date().getFullYear() }}
                    {{ page.props.setting?.title || page.props.name }}. All
                    rights reserved.
                </p>
                <span>Untuk setiap permainan, setiap hari.</span>
            </div>
        </div>
    </footer>
</template>
