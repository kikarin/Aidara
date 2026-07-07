<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const showWorldCup = computed(() => Boolean(page.props.worldcup?.enabled));

const homeSection = (id: string) => `${route('home')}#${id}`;
</script>

<template>
    <footer class="border-border bg-card mt-auto border-t" role="contentinfo" aria-label="Footer situs">
        <div class="mx-auto max-w-6xl px-6 py-10">
            <nav aria-label="Tautan internal situs">
                <ol class="text-muted-foreground flex flex-wrap items-center gap-x-2 gap-y-2 text-sm">
                    <li>
                        <Link :href="route('home')" class="hover:text-foreground transition-colors">Beranda</Link>
                    </li>
                    <li aria-hidden="true" class="select-none">→</li>
                    <li>
                        <Link :href="route('event.public.index')" class="hover:text-foreground transition-colors">Event</Link>
                    </li>
                    <template v-if="showWorldCup">
                        <li aria-hidden="true" class="select-none">→</li>
                        <li>
                            <Link :href="homeSection('piala-dunia')" class="hover:text-foreground transition-colors">Piala Dunia</Link>
                        </li>
                    </template>
                    <li aria-hidden="true" class="select-none">→</li>
                    <li>
                        <Link :href="homeSection('fitur')" class="hover:text-foreground transition-colors">Fitur</Link>
                    </li>
                    <li aria-hidden="true" class="select-none">→</li>
                    <li>
                        <Link :href="homeSection('faq')" class="hover:text-foreground transition-colors">FAQ</Link>
                    </li>
                    <li aria-hidden="true" class="select-none">→</li>
                    <li>
                        <Link :href="route('legal.show', { slug: 'privacy' })" class="hover:text-foreground transition-colors">
                            Privasi
                        </Link>
                    </li>
                    <li aria-hidden="true" class="select-none">→</li>
                    <li>
                        <Link :href="route('legal.show', { slug: 'terms' })" class="hover:text-foreground transition-colors">
                            Syarat & Ketentuan
                        </Link>
                    </li>
                </ol>
            </nav>

            <p class="text-muted-foreground mt-6 text-center text-xs">
                © {{ new Date().getFullYear() }} AIDARA — Dinas Pemuda dan Olahraga Kabupaten Bogor
            </p>
        </div>
    </footer>
</template>
