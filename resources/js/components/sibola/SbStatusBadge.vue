<script setup lang="ts">
import { bookingStatusLabel, bookingStatusTone, toneClass, type BookingStatusAudience, type BookingStatusTone } from '@/lib/bookingStatus';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        status: string;
        audience?: BookingStatusAudience;
        /** Overrides the tone derived from `status`. */
        tone?: BookingStatusTone;
        /** Overrides the label derived from `status`. */
        label?: string;
    }>(),
    { audience: 'renter', tone: undefined, label: undefined },
);

const resolvedTone = computed(() => props.tone ?? bookingStatusTone(props.status));
const resolvedLabel = computed(() => props.label ?? bookingStatusLabel(props.status, props.audience));
</script>

<template>
    <span :class="toneClass(resolvedTone)">
        <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
        {{ resolvedLabel }}
    </span>
</template>
