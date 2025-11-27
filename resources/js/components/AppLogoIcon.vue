<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}
defineProps<Props>();
</script>

<template>
    <div class="flex items-center" :class="className" v-bind="$attrs">
        <div class="rounded-xl">
            <img src="/Logo.png" alt="Logo" class="size-15 object-contain" />
        </div>
    </div>
</template>
