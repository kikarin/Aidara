<script setup lang="ts">
import { computed } from 'vue';
const props = defineProps<{ kesehatan: any }>();

const fields = computed(() => {
    const o = props.kesehatan || {};
    return [
        { label: 'Golongan Darah', value: o.golongan_darah || '-' },
        { label: 'Tinggi Badan (cm)', value: o.tinggi_badan ? `${o.tinggi_badan} cm` : '-' },
        { label: 'Berat Badan (kg)', value: o.berat_badan ? `${o.berat_badan} kg` : '-' },
        { label: 'Penglihatan', value: o.penglihatan || '-' },
        { label: 'Pendengaran', value: o.pendengaran || '-' },
        { label: 'Riwayat Penyakit', value: o.riwayat_penyakit || '-', className: 'sm:col-span-2' },
        { label: 'Alergi', value: o.alergi || '-', className: 'sm:col-span-2' },
    ];
});
</script>

<template>
    <div class="-mt-6 space-y-4">
        <div>
            <h3 class="text-muted-foreground mb-4 text-base font-semibold tracking-wide uppercase">Data Kesehatan Atlet</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div v-for="field in fields" :key="field.label" :class="field.className">
                    <div class="text-muted-foreground text-xs">{{ field.label }}</div>
                    <div class="text-foreground text-sm font-semibold">{{ field.value }}</div>
                </div>
            </div>
        </div>
    </div>
</template>
