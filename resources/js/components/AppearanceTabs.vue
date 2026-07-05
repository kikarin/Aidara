<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance } from '@/lib/theme';
import { Award, Leaf, Monitor, Moon, Palette, Sparkles, Sun } from 'lucide-vue-next';

const { appearance, updateAppearance } = useAppearance();

const tabs: { value: Appearance; Icon: typeof Sun; label: string }[] = [
    { value: 'dispora', Icon: Award, label: 'Dispora' },
    { value: 'light', Icon: Sun, label: 'Terang' },
    { value: 'slate', Icon: Sparkles, label: 'Slate' },
    { value: 'warm', Icon: Palette, label: 'Hangat' },
    { value: 'sport', Icon: Leaf, label: 'Olahraga' },
    { value: 'dark', Icon: Moon, label: 'Gelap' },
    { value: 'system', Icon: Monitor, label: 'Sistem' },
];
</script>

<template>
    <div class="grid max-w-3xl grid-cols-2 gap-1 rounded-lg bg-muted p-1 sm:grid-cols-4">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            @click="updateAppearance(value)"
            :class="[
                'flex items-center justify-center rounded-md px-2.5 py-2 transition-colors',
                appearance === value
                    ? 'bg-card text-foreground shadow-sm'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
            ]"
        >
            <component :is="Icon" class="h-4 w-4 shrink-0" />
            <span class="ml-1.5 text-sm">{{ label }}</span>
        </button>
    </div>
</template>
