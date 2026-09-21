<script setup lang="ts">
import { fifaCodeToEmoji } from '@/lib/worldcupFlags';
import { computed, ref } from 'vue';

const props = defineProps<{
    flag?: string;
    fifaCode?: string;
    name: string;
    size?: 'sm' | 'md' | 'lg';
}>();

const imageFailed = ref(false);

const sizeClass = computed(() => {
    switch (props.size ?? 'md') {
        case 'sm':
            return 'h-5 w-7 text-base';
        case 'lg':
            return 'h-8 w-11 text-2xl';
        default:
            return 'h-6 w-9 text-lg';
    }
});

const emojiFlag = computed(() => fifaCodeToEmoji(props.fifaCode));

const initials = computed(() =>
    props.name
        .split(' ')
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase(),
);

const showImage = computed(() => Boolean(props.flag) && !imageFailed.value);
const showEmoji = computed(() => !showImage.value && Boolean(emojiFlag.value));

const onImageError = () => {
    imageFailed.value = true;
};
</script>

<template>
    <img
        v-if="showImage"
        :src="flag"
        :alt="`Bendera ${name}`"
        :class="[sizeClass, 'rounded-sm object-cover shadow-sm']"
        loading="lazy"
        decoding="async"
        fetchpriority="low"
        @error="onImageError"
    />
    <span
        v-else-if="showEmoji"
        :class="[sizeClass, 'inline-flex items-center justify-center leading-none']"
        :title="name"
        aria-hidden="true"
    >
        {{ emojiFlag }}
    </span>
    <span
        v-else
        :class="[
            sizeClass,
            'bg-muted text-muted-foreground inline-flex items-center justify-center rounded-sm text-[10px] font-bold',
        ]"
        :title="name"
    >
        {{ initials }}
    </span>
</template>
