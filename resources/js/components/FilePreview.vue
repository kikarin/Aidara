<script setup lang="ts">
const props = defineProps<{ fileUrl: string }>();

function isImage(url: string) {
    return /\.(jpg|jpeg|png|gif|webp)$/i.test(url);
}
function isPdf(url: string) {
    return /\.pdf$/i.test(url);
}
</script>

<template>
    <div>
        <template v-if="props.fileUrl">
            <template v-if="isImage(props.fileUrl)">
                <a :href="props.fileUrl" target="_blank">
                    <img :src="props.fileUrl" alt="File" class="mb-2 h-32 w-32 rounded border object-cover" />
                </a>
                <div>
                    <a :href="props.fileUrl" target="_blank" class="text-blue-600 underline">Lihat File</a>
                </div>
            </template>
            <template v-else-if="isPdf(props.fileUrl)">
                <a :href="props.fileUrl" target="_blank" class="text-blue-600 underline">Lihat File PDF</a>
            </template>
            <template v-else>
                <a :href="props.fileUrl" target="_blank" class="text-blue-600 underline">Download File</a>
            </template>
        </template>
        <template v-else>
            <span class="text-muted-foreground">-</span>
        </template>
    </div>
</template>
