<script setup lang="ts">
import FilePreview from '@/components/FilePreview.vue';
import ImagePreview from '@/components/ImagePreview.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Tabs from '@/components/ui/tabs/Tabs.vue';
import TabsContent from '@/components/ui/tabs/TabsContent.vue';
import TabsList from '@/components/ui/tabs/TabsList.vue';
import TabsTrigger from '@/components/ui/tabs/TabsTrigger.vue';
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { CheckCircle2, XCircle } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps<{
    registration: {
        id: number;
        user_id: number;
        peserta_type: string;
        status: string;
        step_current: number;
        data_json: Record<string, any>;
        rejected_reason?: string;
        created_at: string;
        user: {
            name: string;
            email: string;
        };
    };
    registrationData: Record<string, any>;
    additionalData?: Record<string, any>;
}>();

const { toast } = useToast();

const breadcrumbs = [
    { title: 'Persetujuan Registrasi', href: '/registration-approval' },
    { title: 'Detail Pengajuan', href: '#' },
];

const pesertaTypeLabel = computed(() => {
    const labels: Record<string, string> = {
        atlet: 'Atlet',
        pelatih: 'Pelatih',
        tenaga_pendukung: 'Tenaga Pendukung',
    };
    return labels[props.registration.peserta_type] || props.registration.peserta_type;
});

const statusLabel = computed(() => {
    const labels: Record<string, string> = {
        draft: 'Draft',
        submitted: 'Menunggu Persetujuan',
        approved: 'Disetujui',
        rejected: 'Ditolak',
    };
    return labels[props.registration.status] || props.registration.status;
});

const statusColor = computed(() => {
    const colors: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800',
        submitted: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    };
    return colors[props.registration.status] || 'bg-gray-100 text-gray-800';
});

const activeTab = ref('data-diri');

const summaryFields = computed(() => {
    const fields = [
        {
            label: 'Status Pengajuan',
            value: statusLabel.value,
            className: `inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full ${statusColor.value}`,
        },
        { label: 'Email', value: props.registration.user.email || '-' },
        { label: 'Jenis Peserta', value: pesertaTypeLabel.value },
    ];

    if (props.registration.status === 'rejected' && props.registration.rejected_reason) {
        fields.push({
            label: 'Catatan Penolakan',
            value: props.registration.rejected_reason,
            className: 'text-red-500',
        });
    }

    return fields;
});

const actionFields = computed(() => [
    { label: 'Tanggal Pengajuan', value: new Date(props.registration.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
]);

const registrationData = computed(() => props.registrationData || {});
const step2Data = computed(() => registrationData.value.step_2 || {});
const step3Data = computed(() => registrationData.value.step_3 || {});
const step4Data = computed(() => registrationData.value.step_4 || {});
const step5Data = computed(() => registrationData.value.step_5 || {});

const genderOptions: Record<string, string> = {
    L: 'Laki-laki',
    P: 'Perempuan',
};

const fieldConfigs: Record<string, Array<{ key: string; label: string }>> = {
    atlet: [
        { key: 'nik', label: 'NIK' },
        { key: 'nisn', label: 'NISN' },
        { key: 'nama', label: 'Nama' },
        { key: 'jenis_kelamin', label: 'Jenis Kelamin' },
        { key: 'tempat_lahir', label: 'Tempat Lahir' },
        { key: 'tanggal_lahir', label: 'Tanggal Lahir' },
        { key: 'alamat', label: 'Alamat' },
        { key: 'kecamatan_id', label: 'Kecamatan' },
        { key: 'kelurahan_id', label: 'Kelurahan' },
        { key: 'no_hp', label: 'No HP' },
        { key: 'email', label: 'Email' },
        { key: 'tanggal_bergabung', label: 'Tanggal Bergabung' },
        { key: 'agama', label: 'Agama' },
        { key: 'sekolah', label: 'Sekolah' },
        { key: 'kelas_sekolah', label: 'Kelas Sekolah' },
        { key: 'ukuran_baju', label: 'Ukuran Baju' },
        { key: 'ukuran_celana', label: 'Ukuran Celana' },
        { key: 'ukuran_sepatu', label: 'Ukuran Sepatu' },
    ],
    pelatih: [
        { key: 'nik', label: 'NIK' },
        { key: 'nama', label: 'Nama' },
        { key: 'jenis_kelamin', label: 'Jenis Kelamin' },
        { key: 'tempat_lahir', label: 'Tempat Lahir' },
        { key: 'tanggal_lahir', label: 'Tanggal Lahir' },
        { key: 'alamat', label: 'Alamat' },
        { key: 'kecamatan_id', label: 'Kecamatan' },
        { key: 'kelurahan_id', label: 'Kelurahan' },
        { key: 'no_hp', label: 'No HP' },
        { key: 'email', label: 'Email' },
        { key: 'pekerjaan_selain_melatih', label: 'Pekerjaan Selain Melatih' },
        { key: 'tanggal_bergabung', label: 'Tanggal Bergabung' },
    ],
    tenaga_pendukung: [
        { key: 'nik', label: 'NIK' },
        { key: 'nama', label: 'Nama' },
        { key: 'jenis_kelamin', label: 'Jenis Kelamin' },
        { key: 'tempat_lahir', label: 'Tempat Lahir' },
        { key: 'tanggal_lahir', label: 'Tanggal Lahir' },
        { key: 'alamat', label: 'Alamat' },
        { key: 'kecamatan_id', label: 'Kecamatan' },
        { key: 'kelurahan_id', label: 'Kelurahan' },
        { key: 'no_hp', label: 'No HP' },
        { key: 'email', label: 'Email' },
        { key: 'tanggal_bergabung', label: 'Tanggal Bergabung' },
    ],
};

const currentFieldConfig = computed(() => fieldConfigs[props.registration.peserta_type] || fieldConfigs.atlet);

const formatFieldValue = (key: string, value: any) => {
    if (value === null || value === undefined || value === '') return '-';

    if (key === 'jenis_kelamin') {
        return genderOptions[value] || value;
    }

    if (key === 'kecamatan_id') {
        return props.additionalData?.kecamatan?.nama || step2Data.value.kecamatan_nama || value;
    }

    if (key === 'kelurahan_id') {
        return props.additionalData?.kelurahan?.nama || step2Data.value.kelurahan_nama || value;
    }

    return value;
};

const kategoriPesertaLabels = computed(() => {
    const list = props.additionalData?.kategori_pesertas;
    if (Array.isArray(list) && list.length) {
        return list.map((item: any) => item.nama || item.label || `ID ${item.id}`);
    }
    const ids = step2Data.value.kategori_pesertas;
    if (Array.isArray(ids) && ids.length) {
        return ids.map((id: number | string) => `ID ${id}`);
    }
    return [];
});

const tempObjectUrls: string[] = [];
const fileUrlCache = new WeakMap<File, string>();

const isImageSource = (src: string) => {
    const cleanSrc = src?.split('?')[0] || '';
    return /\.(jpg|jpeg|png|gif|bmp|webp)$/i.test(cleanSrc);
};

const extractFileName = (src: string) => {
    if (!src) return 'Lampiran';
    const cleanSrc = src.split('?')[0] || src;
    return cleanSrc.substring(cleanSrc.lastIndexOf('/') + 1) || cleanSrc;
};

const buildPreviewFromSrc = (src: string, name?: string) => {
    const cleanSrc = src?.split('?')[0] || '';
    const extension = cleanSrc.split('.').pop()?.toLowerCase();
    return {
        isImage: isImageSource(src),
        isPdf: extension === 'pdf',
        src,
        name: name || extractFileName(src),
    };
};

const getFilePreview = (file: any) => {
    if (!file) return null;

    if (typeof file === 'string') {
        return buildPreviewFromSrc(file);
    }

    if (file.url || file.file_url) {
        const src = file.url || file.file_url;
        return buildPreviewFromSrc(src, file.name || file.original_name);
    }

    if (typeof window !== 'undefined' && typeof File !== 'undefined' && file instanceof File) {
        let url = fileUrlCache.get(file);
        if (!url) {
            url = URL.createObjectURL(file);
            fileUrlCache.set(file, url);
            tempObjectUrls.push(url);
        }
        return {
            isImage: file.type?.startsWith?.('image/'),
            src: url,
            name: file.name,
        };
    }

    if (file.path) {
        return buildPreviewFromSrc(file.path, file.name);
    }

    return {
        isImage: false,
        src: file.src || null,
        name: file.name || file.original_name || 'Lampiran',
    };
};

onBeforeUnmount(() => {
    tempObjectUrls.forEach((url) => URL.revokeObjectURL(url));
});

const profilePhotoPreview = computed(() => getFilePreview(step2Data.value.file || step2Data.value.foto || step2Data.value.file_url));

const sertifikatList = computed(() => {
    // Step 3 bisa memiliki struktur: { sertifikat: [...] } atau langsung array
    let sertifikat = [];
    if (Array.isArray(step3Data.value)) {
        sertifikat = step3Data.value;
    } else if (step3Data.value.sertifikat && Array.isArray(step3Data.value.sertifikat)) {
        sertifikat = step3Data.value.sertifikat;
    } else if (step3Data.value.sertifikat_files && Array.isArray(step3Data.value.sertifikat_files)) {
        sertifikat = step3Data.value.sertifikat_files;
    }

    return sertifikat.map((item: any, index: number) => ({
        ...item,
        idx: index + 1,
        filePreview: getFilePreview(item.file || item.file_url || item.attachment || item.src),
    }));
});

const prestasiList = computed(() => {
    // Step 4 bisa berupa array langsung atau object dengan property prestasi
    if (Array.isArray(step4Data.value)) {
        return step4Data.value;
    }
    return step4Data.value.prestasi || [];
});

const dokumenList = computed(() => {
    // Step 5 bisa memiliki struktur: { dokumen: [...] } atau langsung array
    let dokumen = [];
    if (Array.isArray(step5Data.value)) {
        dokumen = step5Data.value;
    } else if (step5Data.value.dokumen && Array.isArray(step5Data.value.dokumen)) {
        dokumen = step5Data.value.dokumen;
    } else if (step5Data.value.dokumen_files && Array.isArray(step5Data.value.dokumen_files)) {
        dokumen = step5Data.value.dokumen_files;
    }

    return dokumen.map((item: any, index: number) => ({
        ...item,
        idx: index + 1,
        filePreview: getFilePreview(item.file || item.file_url || item.attachment || item.src),
    }));
});

const resolveJenisDokumen = (dokumen: any) => {
    // Cek dari additionalData dulu (data dikirim sebagai object dengan key ID)
    if (dokumen?.jenis_dokumen_id && props.additionalData?.jenis_dokumen) {
        const jenisDokumen = props.additionalData.jenis_dokumen[dokumen.jenis_dokumen_id];
        if (jenisDokumen && (jenisDokumen.nama || jenisDokumen.label)) {
            return jenisDokumen.nama || jenisDokumen.label;
        }
    }
    
    // Fallback ke data yang ada di dokumen object
    return (
        dokumen?.jenis_dokumen_label ||
        dokumen?.jenis_dokumen?.nama ||
        dokumen?.jenis_dokumen_nama ||
        (dokumen?.jenis_dokumen_id ? `ID ${dokumen.jenis_dokumen_id}` : '-')
    );
};


// Approve/Reject dialogs
const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const rejectReason = ref('');

const handleApprove = () => {
    showApproveDialog.value = true;
};

const handleReject = () => {
    rejectReason.value = '';
    showRejectDialog.value = true;
};

const confirmApprove = async () => {
    try {
        const response = await axios.post(`/registration-approval/${props.registration.id}/approve`, {
            ids: [props.registration.id],
        });
        toast({ title: response.data?.message || 'Pengajuan berhasil disetujui', variant: 'success' });
        showApproveDialog.value = false;
        router.visit('/registration-approval');
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal menyetujui pengajuan', variant: 'destructive' });
    }
};

const confirmReject = async () => {
    if (!rejectReason.value.trim()) {
        toast({ title: 'Alasan penolakan wajib diisi', variant: 'destructive' });
        return;
    }

    try {
        const response = await axios.post(`/registration-approval/${props.registration.id}/reject`, {
            ids: [props.registration.id],
            rejected_reason: rejectReason.value,
        });
        toast({ title: response.data?.message || 'Pengajuan berhasil ditolak', variant: 'success' });
        showRejectDialog.value = false;
        router.visit('/registration-approval');
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal menolak pengajuan', variant: 'destructive' });
    }
};
</script>

<template>
    <PageShow
        title="Pengajuan Registrasi"
        :breadcrumbs="breadcrumbs"
        :fields="summaryFields"
        :action-fields="actionFields"
        back-url="/registration-approval"
    >
        <template #custom-action>
            <Button v-if="registration.status === 'submitted'" variant="default" class="mr-2" @click="handleApprove">
                <CheckCircle2 class="mr-2 h-4 w-4" />
                Setujui
            </Button>
            <Button v-if="registration.status === 'submitted'" variant="destructive" @click="handleReject">
                <XCircle class="mr-2 h-4 w-4" />
                Tolak
            </Button>
        </template>

        <template #custom>
            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="bg-muted/30 flex flex-wrap gap-2 rounded-full p-1">
                    <TabsTrigger
                        value="data-diri"
                        class="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground hover:bg-primary/10 rounded-full px-4 py-2 text-sm font-medium transition-colors"
                    >
                        Data Diri
                    </TabsTrigger>
                    <TabsTrigger
                        value="sertifikat"
                        class="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground hover:bg-primary/10 rounded-full px-4 py-2 text-sm font-medium transition-colors"
                    >
                        Sertifikat
                    </TabsTrigger>
                    <TabsTrigger
                        value="prestasi"
                        class="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground hover:bg-primary/10 rounded-full px-4 py-2 text-sm font-medium transition-colors"
                    >
                        Prestasi
                    </TabsTrigger>
                    <TabsTrigger
                        value="dokumen"
                        class="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground hover:bg-primary/10 rounded-full px-4 py-2 text-sm font-medium transition-colors"
                    >
                        Dokumen
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="data-diri" class="space-y-6 pt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Data Diri</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div v-for="field in currentFieldConfig" :key="field.key">
                                    <p class="text-muted-foreground text-sm">{{ field.label }}</p>
                                    <p class="font-medium">{{ formatFieldValue(field.key, step2Data[field.key]) }}</p>
                                </div>
                            </div>

                            <div v-if="kategoriPesertaLabels.length" class="space-y-2">
                                <p class="text-muted-foreground text-sm">Kategori Peserta</p>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(label, index) in kategoriPesertaLabels"
                                        :key="`${label}-${index}`"
                                        class="bg-secondary rounded-full px-3 py-1 text-sm"
                                    >
                                        {{ label }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="profilePhotoPreview" class="space-y-2">
                                <p class="text-muted-foreground text-sm">Foto</p>
                                <div class="inline-flex">
                                    <ImagePreview
                                        v-if="profilePhotoPreview.isImage && profilePhotoPreview.src"
                                        :image-url="profilePhotoPreview.src"
                                        :alt="profilePhotoPreview.name || 'Foto peserta'"
                                        size="lg"
                                    />
                                    <FilePreview
                                        v-else-if="profilePhotoPreview.src"
                                        :file-url="profilePhotoPreview.src"
                                        :file-name="profilePhotoPreview.name"
                                    />
                                    <div v-else class="text-muted-foreground text-sm">Tidak ada lampiran</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="sertifikat" class="pt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Sertifikat</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="sertifikatList.length" class="space-y-4">
                                <div
                                    v-for="sertifikat in sertifikatList"
                                    :key="sertifikat.idx || sertifikat.tempId"
                                    class="space-y-3 rounded-md border p-4"
                                >
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <p class="text-muted-foreground text-sm">Nama Sertifikat</p>
                                            <p class="font-medium">{{ sertifikat.nama_sertifikat || `Sertifikat ${sertifikat.idx}` }}</p>
                                        </div>
                                        <div>
                                            <p class="text-muted-foreground text-sm">Penyelenggara</p>
                                            <p class="font-medium">{{ sertifikat.penyelenggara || '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-muted-foreground text-sm">Tanggal Terbit</p>
                                            <p class="font-medium">{{ sertifikat.tanggal_terbit || '-' }}</p>
                                        </div>
                                    </div>
                                    <div v-if="sertifikat.filePreview" class="space-y-2">
                                        <p class="text-muted-foreground text-sm">Lampiran</p>
                                        <div class="inline-flex">
                                            <ImagePreview
                                                v-if="sertifikat.filePreview.isImage && sertifikat.filePreview.src"
                                                :image-url="sertifikat.filePreview.src"
                                                :alt="sertifikat.filePreview.name || 'Lampiran sertifikat'"
                                                size="lg"
                                            />
                                            <FilePreview
                                                v-else-if="sertifikat.filePreview.src"
                                                :file-url="sertifikat.filePreview.src"
                                                :file-name="sertifikat.filePreview.name"
                                            />
                                            <div v-else class="text-muted-foreground text-sm">Tidak ada lampiran</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-muted-foreground text-sm">Belum ada sertifikat yang diunggah.</p>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="prestasi" class="pt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Prestasi</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="prestasiList.length" class="space-y-4">
                                <div v-for="(prestasi, index) in prestasiList" :key="index" class="space-y-2 rounded-md border p-4">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <p class="text-muted-foreground text-sm">Nama Event</p>
                                            <p class="font-medium">{{ prestasi.nama_event || `Prestasi ${index + 1}` }}</p>
                                        </div>
                                        <div>
                                            <p class="text-muted-foreground text-sm">Tingkat</p>
                                            <p class="font-medium">
                                                {{
                                                    (prestasi.tingkat_id && props.additionalData?.tingkat?.[prestasi.tingkat_id] && 
                                                     (props.additionalData.tingkat[prestasi.tingkat_id].nama || props.additionalData.tingkat[prestasi.tingkat_id].label)) ||
                                                    prestasi.tingkat_label ||
                                                    prestasi.tingkat_nama ||
                                                    (prestasi.tingkat_id ? `ID ${prestasi.tingkat_id}` : '-')
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-muted-foreground text-sm">Tanggal</p>
                                            <p class="font-medium">{{ prestasi.tanggal || '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-muted-foreground text-sm">Peringkat</p>
                                            <p class="font-medium">{{ prestasi.peringkat || '-' }}</p>
                                        </div>
                                    </div>
                                    <div v-if="prestasi.keterangan">
                                        <p class="text-muted-foreground text-sm">Keterangan</p>
                                        <p class="font-medium">{{ prestasi.keterangan }}</p>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-muted-foreground text-sm">Belum ada prestasi yang diinput.</p>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="dokumen" class="pt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Dokumen</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="dokumenList.length" class="space-y-4">
                                <div v-for="dokumen in dokumenList" :key="dokumen.idx || dokumen.tempId" class="space-y-3 rounded-md border p-4">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <p class="text-muted-foreground text-sm">Jenis Dokumen</p>
                                            <p class="font-medium">{{ resolveJenisDokumen(dokumen) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-muted-foreground text-sm">Nomor</p>
                                            <p class="font-medium">{{ dokumen.nomor || '-' }}</p>
                                        </div>
                                    </div>

                                    <div v-if="dokumen.filePreview" class="space-y-2">
                                        <p class="text-muted-foreground text-sm">Lampiran</p>
                                        <div class="inline-flex">
                                            <ImagePreview
                                                v-if="dokumen.filePreview.isImage && dokumen.filePreview.src"
                                                :image-url="dokumen.filePreview.src"
                                                :alt="dokumen.filePreview.name || 'Lampiran dokumen'"
                                                size="lg"
                                            />
                                            <FilePreview
                                                v-else-if="dokumen.filePreview.src"
                                                :file-url="dokumen.filePreview.src"
                                                :file-name="dokumen.filePreview.name"
                                            />
                                            <div v-else class="text-muted-foreground text-sm">Tidak ada lampiran</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-muted-foreground text-sm">Belum ada dokumen yang diunggah.</p>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </template>
    </PageShow>

    <!-- Approve Dialog -->
    <Dialog :open="showApproveDialog" @update:open="showApproveDialog = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Setujui Pengajuan Registrasi</DialogTitle>
                <DialogDescription> Apakah Anda yakin ingin menyetujui pengajuan ini? </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showApproveDialog = false">Batal</Button>
                <Button @click="confirmApprove">Setujui</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Reject Dialog -->
    <Dialog :open="showRejectDialog" @update:open="showRejectDialog = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tolak Pengajuan Registrasi</DialogTitle>
                <DialogDescription> Masukkan alasan penolakan untuk pengajuan ini. </DialogDescription>
            </DialogHeader>
            <div class="space-y-4 py-4">
                <div>
                    <Label for="reject_reason">Alasan Penolakan *</Label>
                    <Input id="reject_reason" v-model="rejectReason" placeholder="Masukkan alasan penolakan" class="mt-2" />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showRejectDialog = false">Batal</Button>
                <Button variant="destructive" @click="confirmReject">Tolak</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
