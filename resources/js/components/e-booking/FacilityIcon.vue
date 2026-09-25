<script setup lang="ts">
import {
    Accessibility,
    Armchair,
    Baby,
    Car,
    Circle,
    Flag,
    Grid3x3,
    Landmark,
    LayoutGrid,
    Lightbulb,
    Megaphone,
    Monitor,
    Ruler,
    Shield,
    ShowerHead,
    Target,
    Timer,
    Trophy,
    Utensils,
    Wifi,
    Wrench,
    Zap,
} from 'lucide-vue-next';
import { computed, type Component } from 'vue';

const props = defineProps<{
    icon?: string | null;
    class?: string;
}>();

const icons: Record<string, Component> = {
    car: Car,
    utensils: Utensils,
    'shower-head': ShowerHead,
    wifi: Wifi,
    landmark: Landmark,
    shield: Shield,
    accessibility: Accessibility,
    baby: Baby,
    armchair: Armchair,
    zap: Zap,
    monitor: Monitor,
    'grid-3x3': Grid3x3,
    target: Target,
    circle: Circle,
    lightbulb: Lightbulb,
    flag: Flag,
    megaphone: Megaphone,
    timer: Timer,
    trophy: Trophy,
    ruler: Ruler,
    wrench: Wrench,
};

const resolved = computed<Component>(() => (props.icon && icons[props.icon]) || LayoutGrid);
</script>

<template>
    <component :is="resolved" />
</template>
