<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';

interface Props {
    isOpen: boolean;
    imageUrl: string;
    alt?: string;
}

const props = withDefaults(defineProps<Props>(), {
    alt: 'Image',
});

const emit = defineEmits<{
    close: [];
}>();

// Image zoom and pan state
const imageScale = ref(1);
const imageX = ref(0);
const imageY = ref(0);
const isDragging = ref(false);
const lastX = ref(0);
const lastY = ref(0);

// Zoom functions
const zoomIn = () => {
    imageScale.value = Math.min(imageScale.value * 1.2, 5);
};

const zoomOut = () => {
    imageScale.value = Math.max(imageScale.value / 1.2, 0.5);
};

const resetZoom = () => {
    imageScale.value = 1;
    imageX.value = 0;
    imageY.value = 0;
};

// Mouse wheel zoom
const handleWheel = (e: WheelEvent) => {
    e.preventDefault();
    if (e.deltaY < 0) {
        zoomIn();
    } else {
        zoomOut();
    }
};

// Mouse drag functions
const startDrag = (e: MouseEvent) => {
    if (imageScale.value <= 1) return;
    isDragging.value = true;
    lastX.value = e.clientX;
    lastY.value = e.clientY;
};

const drag = (e: MouseEvent) => {
    if (!isDragging.value) return;
    const deltaX = e.clientX - lastX.value;
    const deltaY = e.clientY - lastY.value;
    imageX.value += deltaX;
    imageY.value += deltaY;
    lastX.value = e.clientX;
    lastY.value = e.clientY;
};

const stopDrag = () => {
    isDragging.value = false;
};

// Keyboard shortcuts
const handleKeydown = (e: KeyboardEvent) => {
    if (!props.isOpen) return;

    switch (e.key) {
        case 'Escape':
            emit('close');
            break;
        case '+':
        case '=':
            e.preventDefault();
            zoomIn();
            break;
        case '-':
            e.preventDefault();
            zoomOut();
            break;
        case '0':
            e.preventDefault();
            resetZoom();
            break;
    }
};

// Reset transform when modal opens
const handleOpen = () => {
    if (props.isOpen) {
        imageScale.value = 1;
        imageX.value = 0;
        imageY.value = 0;
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', stopDrag);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    document.removeEventListener('mousemove', drag);
    document.removeEventListener('mouseup', stopDrag);
});

// Watch for modal open state
watch(() => props.isOpen, handleOpen);
</script>

<template>
    <!-- Enhanced Image Modal -->
    <div v-if="isOpen" class="bg-opacity-90 fixed inset-0 z-50 flex items-center justify-center bg-black" @click="emit('close')">
        <div class="relative flex h-full w-full items-center justify-center p-4" @click.stop>
            <!-- Close button -->
            <button
                @click="emit('close')"
                class="bg-opacity-50 hover:bg-opacity-75 absolute top-4 right-4 z-10 rounded-full bg-black p-2 text-white transition-all"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <!-- Control buttons -->
            <div class="absolute top-4 left-4 z-10 flex gap-2">
                <button @click="zoomIn" class="bg-opacity-50 hover:bg-opacity-75 rounded-full bg-black p-2 text-white transition-all">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </button>
                <button @click="zoomOut" class="bg-opacity-50 hover:bg-opacity-75 rounded-full bg-black p-2 text-white transition-all">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"></path>
                    </svg>
                </button>
                <button @click="resetZoom" class="bg-opacity-50 hover:bg-opacity-75 rounded bg-black px-3 py-2 text-sm text-white transition-all">
                    Reset
                </button>
            </div>

            <!-- Zoom indicator -->
            <div class="bg-opacity-50 absolute bottom-4 left-4 z-10 rounded bg-black px-3 py-1 text-sm text-white">
                {{ Math.round(imageScale * 100) }}%
            </div>

            <!-- Image container -->
            <div class="relative flex h-full w-full items-center justify-center overflow-hidden">
                <img
                    :src="imageUrl"
                    :alt="alt"
                    class="max-h-[80vh] max-w-full object-contain transition-transform duration-200 select-none"
                    :class="[imageScale > 1 ? 'cursor-grab' : 'cursor-default', isDragging ? 'cursor-grabbing' : '']"
                    :style="{
                        transform: `scale(${imageScale}) translate(${imageX / imageScale}px, ${imageY / imageScale}px)`,
                        transformOrigin: 'center center',
                    }"
                    @wheel="handleWheel"
                    @mousedown="startDrag"
                    @dragstart.prevent
                />
            </div>
        </div>
    </div>
</template>
