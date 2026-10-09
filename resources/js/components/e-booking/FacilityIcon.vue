<script setup lang="ts">
import type { IconDefinition } from '@fortawesome/fontawesome-svg-core';
import {
    faBaby,
    faBolt,
    faBullhorn,
    faBullseye,
    faCar,
    faCircleDot,
    faCouch,
    faDisplay,
    faFlag,
    faLandmark,
    faLightbulb,
    faRuler,
    faShieldHalved,
    faShower,
    faStopwatch,
    faTableCells,
    faTableCellsLarge,
    faTrophy,
    faUtensils,
    faWheelchair,
    faWifi,
    faWrench,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { computed } from 'vue';

const props = defineProps<{
    icon?: string | null;
    class?: string;
}>();

// Keys are persisted on facilities (legacy lucide names) — keep them stable.
const icons: Record<string, IconDefinition> = {
    car: faCar,
    utensils: faUtensils,
    'shower-head': faShower,
    wifi: faWifi,
    landmark: faLandmark,
    shield: faShieldHalved,
    accessibility: faWheelchair,
    baby: faBaby,
    armchair: faCouch,
    zap: faBolt,
    monitor: faDisplay,
    'grid-3x3': faTableCells,
    target: faBullseye,
    circle: faCircleDot,
    lightbulb: faLightbulb,
    flag: faFlag,
    megaphone: faBullhorn,
    timer: faStopwatch,
    trophy: faTrophy,
    ruler: faRuler,
    wrench: faWrench,
};

const resolved = computed(() => (props.icon && icons[props.icon]) || faTableCellsLarge);
</script>

<template>
    <FontAwesomeIcon :icon="resolved" :class="props.class" aria-hidden="true" />
</template>
