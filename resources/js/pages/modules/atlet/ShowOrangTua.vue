<script setup lang="ts">
import { computed } from 'vue';
const props = defineProps<{ orangTua: any }>();

function formatDate(date: string | null) {
    if (!date) return '-';
    const d = new Date(date);
    if (isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'numeric', year: 'numeric' });
}

const ibuFields = computed(() => {
    const o = props.orangTua || {};
    return [
        { label: 'Nama Ibu Kandung', value: o.nama_ibu_kandung || '-' },
        { label: 'Tempat Lahir Ibu', value: o.tempat_lahir_ibu || '-' },
        { label: 'Tanggal Lahir Ibu', value: formatDate(o.tanggal_lahir_ibu) },
        { label: 'No. HP Ibu', value: o.no_hp_ibu || '-' },
        { label: 'Pekerjaan Ibu', value: o.pekerjaan_ibu || '-' },
        { label: 'Alamat Ibu', value: o.alamat_ibu || '-', className: 'sm:col-span-2' },
    ];
});

const ayahFields = computed(() => {
    const o = props.orangTua || {};
    return [
        { label: 'Nama Ayah Kandung', value: o.nama_ayah_kandung || '-' },
        { label: 'Tempat Lahir Ayah', value: o.tempat_lahir_ayah || '-' },
        { label: 'Tanggal Lahir Ayah', value: formatDate(o.tanggal_lahir_ayah) },
        { label: 'No. HP Ayah', value: o.no_hp_ayah || '-' },
        { label: 'Pekerjaan Ayah', value: o.pekerjaan_ayah || '-' },
        { label: 'Alamat Ayah', value: o.alamat_ayah || '-', className: 'sm:col-span-2' },
    ];
});

const waliFields = computed(() => {
    const o = props.orangTua || {};
    return [
        { label: 'Nama Wali', value: o.nama_wali || '-' },
        { label: 'Tempat Lahir Wali', value: o.tempat_lahir_wali || '-' },
        { label: 'Tanggal Lahir Wali', value: formatDate(o.tanggal_lahir_wali) },
        { label: 'No. HP Wali', value: o.no_hp_wali || '-' },
        { label: 'Pekerjaan Wali', value: o.pekerjaan_wali || '-' },
        { label: 'Alamat Wali', value: o.alamat_wali || '-', className: 'sm:col-span-2' },
    ];
});
</script>

<template>
    <div class="-mt-6 space-y-4">
        <div>
            <h3 class="mb-4 text-base font-semibold tracking-wide uppercase">Data Ibu Kandung</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div v-for="field in ibuFields" :key="field.label" :class="field.className">
                    <div class="text-muted-foreground text-xs">{{ field.label }}</div>
                    <div class="text-foreground text-sm font-semibold">{{ field.value }}</div>
                </div>
            </div>
        </div>
        <div>
            <h3 class="mb-4 text-base font-semibold tracking-wide uppercase">Data Ayah Kandung</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div v-for="field in ayahFields" :key="field.label" :class="field.className">
                    <div class="text-muted-foreground text-xs">{{ field.label }}</div>
                    <div class="text-foreground text-sm font-semibold">{{ field.value }}</div>
                </div>
            </div>
        </div>
        <div>
            <h3 class="mb-4 text-base font-semibold tracking-wide uppercase">Data Wali</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div v-for="field in waliFields" :key="field.label" :class="field.className">
                    <div class="text-muted-foreground text-xs">{{ field.label }}</div>
                    <div class="text-foreground text-sm font-semibold">{{ field.value }}</div>
                </div>
            </div>
        </div>
    </div>
</template>
