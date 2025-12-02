<script setup lang="ts">
import { ref } from 'vue';
import { ImageModal, ImagePreview } from './index';

// Contoh data
const sampleImage = 'https://via.placeholder.com/400x300/4F46E5/FFFFFF?text=Sample+Image';

// State untuk modal manual
const showManualModal = ref(false);

const openManualModal = () => {
    showManualModal.value = true;
};

const closeManualModal = () => {
    showManualModal.value = false;
};
</script>

<template>
    <div class="space-y-8 p-6">
        <h1 class="text-2xl font-bold">Image Components Examples</h1>

        <!-- ImagePreview Examples -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold">ImagePreview Examples</h2>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <!-- Small size -->
                <div class="text-center">
                    <h3 class="mb-2 text-sm font-medium">Small (64x64)</h3>
                    <ImagePreview :image-url="sampleImage" alt="Sample Image Small" size="sm" label-text="Klik untuk zoom" />
                </div>

                <!-- Medium size -->
                <div class="text-center">
                    <h3 class="mb-2 text-sm font-medium">Medium (128x128)</h3>
                    <ImagePreview :image-url="sampleImage" alt="Sample Image Medium" size="md" label-text="Klik untuk zoom" />
                </div>

                <!-- Large size -->
                <div class="text-center">
                    <h3 class="mb-2 text-sm font-medium">Large (192x192)</h3>
                    <ImagePreview :image-url="sampleImage" alt="Sample Image Large" size="lg" label-text="Klik untuk zoom" />
                </div>

                <!-- Extra Large size -->
                <div class="text-center">
                    <h3 class="mb-2 text-sm font-medium">Extra Large (256x256)</h3>
                    <ImagePreview :image-url="sampleImage" alt="Sample Image XL" size="xl" label-text="Klik untuk zoom" />
                </div>
            </div>

            <!-- Without label (for tables) -->
            <div class="text-center">
                <h3 class="mb-2 text-sm font-medium">Without Label (for tables)</h3>
                <ImagePreview :image-url="sampleImage" alt="Sample Image No Label" size="sm" :show-label="false" />
            </div>
        </section>

        <!-- Manual Modal Example -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold">Manual Modal Example</h2>

            <div class="space-y-2">
                <p class="text-sm text-gray-600">Anda juga bisa menggunakan ImageModal secara manual untuk kontrol yang lebih fleksibel.</p>

                <button @click="openManualModal" class="rounded bg-blue-500 px-4 py-2 text-white transition-colors hover:bg-blue-600">
                    Buka Modal Manual
                </button>

                <ImageModal :is-open="showManualModal" :image-url="sampleImage" alt="Manual Modal Example" @close="closeManualModal" />
            </div>
        </section>

        <!-- Usage Instructions -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold">Cara Penggunaan</h2>

            <div class="rounded-lg bg-gray-50 p-4">
                <h3 class="mb-2 font-medium">Import Komponen:</h3>
                <pre class="rounded border bg-white p-2 text-sm"><code>import { ImagePreview, ImageModal } from '@/components';
// atau
import ImagePreview from '@/components/ImagePreview.vue';
import ImageModal from '@/components/ImageModal.vue';</code></pre>

                <h3 class="mt-4 mb-2 font-medium">Penggunaan ImagePreview:</h3>
                <pre class="rounded border bg-white p-2 text-sm"><code>&lt;ImagePreview
  :image-url="item.foto"
  :alt="`Foto ${item.nama}`"
  size="lg"
  label-text="Klik untuk melihat foto"
/&gt;</code></pre>

                <h3 class="mt-4 mb-2 font-medium">Penggunaan ImageModal:</h3>
                <pre class="rounded border bg-white p-2 text-sm"><code>&lt;ImageModal
  :is-open="showModal"
  :image-url="imageUrl"
  alt="Contoh Gambar"
  @close="closeModal"
/&gt;</code></pre>
            </div>
        </section>
    </div>
</template>
