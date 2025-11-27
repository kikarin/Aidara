<script setup lang="ts">
import { ref } from 'vue';
import ImageModal from './ImageModal.vue';

interface Props {
    imageUrl: string;
    alt?: string;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    showLabel?: boolean;
    labelText?: string;
}

withDefaults(defineProps<Props>(), {
    alt: 'Image',
    size: 'md',
    showLabel: true,
    labelText: 'Klik untuk melihat lebih besar',
});

const showModal = ref(false);

const sizeClasses = {
    sm: 'w-16 h-16',
    md: 'w-32 h-32',
    lg: 'w-48 h-48',
    xl: 'w-64 h-64',
};

const openModal = () => {
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};
</script>

<template>
    <div class="inline-block">
        <!-- Image thumbnail -->
        <div class="cursor-pointer" @click="openModal">
            <img
                :src="imageUrl"
                :alt="alt"
                :class="[sizeClasses[size], 'rounded-lg border object-cover shadow-sm transition-shadow hover:shadow-md']"
            />
            <p v-if="showLabel" class="text-muted-foreground mt-1 text-xs">
                {{ labelText }}
            </p>
        </div>

        <!-- Image Modal -->
        <ImageModal :is-open="showModal" :image-url="imageUrl" :alt="alt" @close="closeModal" />
    </div>
</template>
