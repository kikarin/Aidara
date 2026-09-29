<script setup lang="ts">
import { library } from '@fortawesome/fontawesome-svg-core';
import { faCheck, faLocationDot } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

library.add(faCheck, faLocationDot);

const slots = [
    { time: '07.00', state: 'taken' },
    { time: '08.00', state: 'open' },
    { time: '09.00', state: 'picked' },
    { time: '10.00', state: 'open' },
    { time: '15.00', state: 'taken' },
    { time: '16.00', state: 'open' },
] as const;

const players = [
    { cx: 92, cy: 96, team: 'a' },
    { cx: 130, cy: 176, team: 'a' },
    { cx: 168, cy: 118, team: 'a' },
    { cx: 262, cy: 82, team: 'b' },
    { cx: 248, cy: 170, team: 'b' },
    { cx: 318, cy: 132, team: 'b' },
] as const;
</script>

<template>
    <div class="sb-stage relative mx-auto aspect-[5/4] w-full max-w-xl" aria-hidden="true">
        <div class="absolute inset-x-0 top-[14%] bottom-[10%] flex items-center justify-center">
            <svg class="sb-pitch w-[92%]" viewBox="0 0 400 260" fill="none">
                <rect width="400" height="260" rx="20" class="sb-pitch-grass" />
                <rect v-for="i in 4" :key="i" :x="(i - 1) * 100" width="50" height="260" class="sb-pitch-stripe" />
                <g class="sb-pitch-line" stroke-width="2.5">
                    <rect x="16" y="16" width="368" height="228" rx="3" />
                    <line x1="200" y1="16" x2="200" y2="244" />
                    <circle cx="200" cy="130" r="34" />
                    <rect x="16" y="70" width="58" height="120" />
                    <rect x="16" y="102" width="22" height="56" />
                    <rect x="326" y="70" width="58" height="120" />
                    <rect x="362" y="102" width="22" height="56" />
                    <path d="M74 106a28 28 0 0 1 0 48" />
                    <path d="M326 106a28 28 0 0 0 0 48" />
                    <rect x="6" y="114" width="10" height="32" rx="2" />
                    <rect x="384" y="114" width="10" height="32" rx="2" />
                </g>
                <circle cx="200" cy="130" r="3.5" class="sb-pitch-dot" />
                <path d="M168 118 C 190 100, 214 108, 226 124" class="sb-pass" stroke-width="2" stroke-dasharray="5 6" />
                <circle
                    v-for="p in players"
                    :key="`${p.cx}-${p.cy}`"
                    :cx="p.cx"
                    :cy="p.cy"
                    r="7"
                    :class="p.team === 'a' ? 'sb-player-a' : 'sb-player-b'"
                />
            </svg>
        </div>

        <div class="sb-ball-wrap absolute top-[40%] left-[54%]">
            <div class="sb-ball size-11 sm:size-14">
                <svg viewBox="0 0 64 64" class="size-full">
                    <circle cx="32" cy="32" r="30" class="sb-ball-body" />
                    <path d="M32 18l10 7-4 12H26l-4-12z" class="sb-ball-patch" />
                    <path d="M32 18V4M42 25l12-6M38 37l9 11M26 37l-9 11M22 25L10 19" class="sb-ball-seam" stroke-width="2" stroke-linecap="round" />
                    <path d="M4 30l6-11M54 19l6 11M47 48l-5 11M17 48l5 11" class="sb-ball-patch" />
                </svg>
            </div>
            <div class="sb-ball-shadow mx-auto mt-3 h-2 w-9 rounded-full"></div>
        </div>

        <div class="wp-glass absolute top-[4%] left-0 flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold sm:left-[2%]">
            <FontAwesomeIcon :icon="['fas', 'location-dot']" class="size-3.5 text-(--wp-accent)" />
            Venue UPT Dispora · Kab. Bogor
        </div>

        <div class="wp-glass sb-float-a absolute top-0 right-0 w-56 rounded-2xl p-4 sm:w-60">
            <div class="flex items-baseline justify-between">
                <p class="text-sm font-semibold">Pilih slot</p>
                <p class="text-muted-foreground text-xs">Sabtu, 10 Okt</p>
            </div>
            <ul class="mt-3 grid grid-cols-3 gap-1.5 text-xs font-medium tabular-nums">
                <li
                    v-for="slot in slots"
                    :key="slot.time"
                    class="rounded-lg px-2 py-1.5 text-center"
                    :class="{
                        'text-muted-foreground/60 bg-muted line-through': slot.state === 'taken',
                        'border border-(--wp-hairline)': slot.state === 'open',
                        'bg-(--wp-accent) text-(--wp-accent-contrast)': slot.state === 'picked',
                    }"
                >
                    {{ slot.time }}
                </li>
            </ul>
            <div class="mt-3 flex items-center justify-between border-t border-(--wp-hairline) pt-3 text-xs">
                <span class="text-muted-foreground">Perkiraan biaya</span>
                <span class="font-semibold tabular-nums">Rp350.000</span>
            </div>
        </div>

        <div class="wp-glass sb-float-b absolute bottom-0 left-0 w-64 rounded-2xl p-4 sm:left-[4%]">
            <div class="flex items-center gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-(--wp-accent) text-(--wp-accent-contrast)">
                    <FontAwesomeIcon :icon="['fas', 'check']" class="size-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold">Pengajuan disetujui</p>
                    <p class="text-muted-foreground truncate text-xs">Lapangan sepak bola · Area A</p>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between border-t border-dashed border-(--wp-hairline) pt-3 text-xs">
                <span class="text-muted-foreground font-mono tabular-nums">SB-2610-0473</span>
                <span class="font-semibold">Surat izin siap</span>
            </div>
        </div>
    </div>
</template>
