<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast/useToast';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const page = usePage();
const props = page.props as any;

const programId = props.program_id as string;
const rencanaId = props.rencana_id as string;
const jenisPeserta = props.jenis_peserta as string;
const pesertaId = props.peserta_id as string;
const peserta = props.peserta as any;
const infoRencana = props.infoRencana as any;

const jenisLabel: Record<string, string> = {
    atlet: 'Atlet',
    pelatih: 'Pelatih',
    'tenaga-pendukung': 'Tenaga Pendukung',
};

const breadcrumbs = [
    { title: 'Program Latihan', href: `/program-latihan` },
    { title: 'Rencana Latihan', href: `/program-latihan/${programId}/rencana-latihan` },
    {
        title: `Peserta (${jenisLabel[jenisPeserta] || jenisPeserta})`,
        href: `/program-latihan/${programId}/rencana-latihan/${rencanaId}/index/${jenisPeserta}`,
    },
    { title: 'Set Kehadiran', href: '#' },
];

const { toast } = useToast();

const formData = ref({
    kehadiran: props.kehadiran || '',
    keterangan: props.keterangan || '',
    foto: null as File | null,
});

const fotoPreview = ref<string | null>(props.foto_kehadiran || null);
const formKey = ref(0); // Key untuk memaksa re-render FormInput

const formInputs = computed(() => {
    const inputs: any[] = [
        {
            name: 'kehadiran',
            label: 'Status Kehadiran',
            type: 'select',
            required: true,
            options: [
                { value: 'Hadir', label: 'Hadir' },
                { value: 'Tidak Hadir', label: 'Tidak Hadir' },
                { value: 'Izin', label: 'Izin' },
                { value: 'Sakit', label: 'Sakit' },
            ],
        },
        {
            name: 'keterangan',
            label: 'Keterangan',
            type: 'textarea',
            placeholder: 'Masukkan keterangan (opsional)',
        },
    ];

    // Tambahkan field foto hanya jika kehadiran adalah "Hadir"
    if (formData.value.kehadiran === 'Hadir') {
        inputs.push({
            name: 'foto',
            label: 'Foto Kehadiran',
            type: 'file',
            required: !props.foto_kehadiran, // Required hanya jika belum ada foto
            help: props.foto_kehadiran
                ? 'Upload foto baru untuk mengganti foto yang sudah ada. Format: JPG, PNG. Maksimal 5MB.'
                : 'Upload foto sebagai bukti kehadiran. Format: JPG, PNG. Maksimal 5MB.',
        });
    }

    return inputs;
});

// Watch kehadiran untuk update form key dan reset foto
watch(
    () => formData.value.kehadiran,
    (newValue) => {
        formKey.value++; // Force re-render FormInput
        if (newValue !== 'Hadir') {
            formData.value.foto = null;
            // Jangan reset fotoPreview jika ada foto_kehadiran dari props
            if (!props.foto_kehadiran) {
                fotoPreview.value = null;
            } else {
                fotoPreview.value = props.foto_kehadiran;
            }
        }
    },
);

const handleFieldUpdate = (payload: { field: string; value: any }) => {
    const fieldName = payload?.field;
    const value = payload?.value;
    if (fieldName === 'kehadiran') {
        formData.value.kehadiran = value;
    } else if (fieldName === 'keterangan') {
        formData.value.keterangan = value;
    } else if (fieldName === 'foto') {
        formData.value.foto = value;
        if (value) {
            fotoPreview.value = URL.createObjectURL(value);
        }
    }
};

const handleSave = async (data: any, setFormErrors: (errors: Record<string, string>) => void) => {
    try {
        const formDataToSend = new FormData();
        formDataToSend.append('kehadiran', data.kehadiran);
        if (data.keterangan) {
            formDataToSend.append('keterangan', data.keterangan);
        }
        if (data.foto) {
            formDataToSend.append('foto', data.foto);
        }

        const response = await axios.post(`/api/rencana-latihan/${rencanaId}/peserta/${jenisPeserta}/${pesertaId}/update-kehadiran`, formDataToSend, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        toast({ title: response.data.message || 'Kehadiran berhasil diupdate', variant: 'success' });
        router.visit(`/program-latihan/${programId}/rencana-latihan/${rencanaId}/index/${jenisPeserta}`);
    } catch (error: any) {
        if (error.response?.data?.errors) {
            setFormErrors(error.response.data.errors);
        } else {
            toast({ title: error.response?.data?.message || 'Gagal update kehadiran', variant: 'destructive' });
        }
    }
};
</script>

<template>
    <PageEdit
        :title="`Set Kehadiran - ${peserta?.nama || 'Peserta'}`"
        :breadcrumbs="breadcrumbs"
        :back-url="`/program-latihan/${programId}/rencana-latihan/${rencanaId}/index/${jenisPeserta}`"
        :use-grid="false"
    >
        <div class="space-y-6">
            <!-- Informasi Peserta -->
            <Card>
                <CardContent class="pt-6">
                    <h3 class="mb-4 text-lg font-semibold">Informasi Peserta</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <span class="text-muted-foreground text-sm font-medium">Nama:</span>
                            <p class="text-sm font-medium">{{ peserta?.nama }}</p>
                        </div>
                        <div>
                            <span class="text-muted-foreground text-sm font-medium">Jenis Peserta:</span>
                            <p class="text-sm font-medium">{{ jenisLabel[jenisPeserta] || jenisPeserta }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Informasi Rencana Latihan -->
            <Card>
                <CardContent class="pt-6">
                    <h3 class="mb-4 text-lg font-semibold">Informasi Rencana Latihan</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <span class="text-muted-foreground text-sm font-medium">Tanggal:</span>
                            <p class="text-sm font-medium">
                                {{ infoRencana.tanggal ? new Date(infoRencana.tanggal).toLocaleDateString('id-ID') : '-' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-muted-foreground text-sm font-medium">Materi:</span>
                            <p class="text-sm font-medium">{{ infoRencana.materi || '-' }}</p>
                        </div>
                        <div>
                            <span class="text-muted-foreground text-sm font-medium">Lokasi:</span>
                            <p class="text-sm font-medium">{{ infoRencana.lokasi_latihan || '-' }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Form Set Kehadiran -->
            <Card>
                <CardContent class="pt-6">
                    <h3 class="mb-4 text-lg font-semibold">Set Kehadiran</h3>
                    <FormInput
                        :key="`kehadiran-form-${formKey}`"
                        :form-inputs="formInputs"
                        :initial-data="formData"
                        @save="handleSave"
                        @field-updated="handleFieldUpdate"
                    />
                </CardContent>
            </Card>

            <!-- Preview Foto Kehadiran (jika sudah ada) -->
            <Card v-if="fotoPreview && formData.kehadiran === 'Hadir'">
                <CardContent class="pt-6">
                    <h3 class="mb-4 text-lg font-semibold">Foto Kehadiran Saat Ini</h3>
                    <div class="flex items-center gap-4">
                        <img :src="fotoPreview" alt="Foto Kehadiran" class="h-48 w-48 rounded-lg border object-cover" />
                        <div>
                            <p class="text-muted-foreground text-sm">Foto kehadiran yang sudah diupload sebelumnya.</p>
                            <p class="text-muted-foreground mt-2 text-xs">Upload foto baru untuk mengganti foto ini.</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </PageEdit>
</template>
