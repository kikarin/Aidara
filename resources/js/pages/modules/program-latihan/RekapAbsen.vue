<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import { router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { X, Upload, FileText, Image as ImageIcon, Trash2 } from 'lucide-vue-next';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    program_latihan: {
        id: number;
        nama_program: string;
        cabor_nama: string;
        cabor_kategori_nama: string;
        periode_mulai: string;
        periode_selesai: string;
        periode_hitung?: string;
    };
    calendar_data: Array<{
        tanggal: string;
        rekap_id?: number;
        keterangan?: string;
        foto_absen: Array<{ id: number; url: string; name: string }>;
        file_nilai: Array<{ id: number; url: string; name: string }>;
    }>;
}>();

const { toast } = useToast();

const selectedDate = ref<string | null>(null);
const selectedRekap = ref<any>(null);
const isDialogOpen = ref(false);
const keterangan = ref('');
const fotoFiles = ref<File[]>([]);
const fileNilaiFiles = ref<File[]>([]);
const isSubmitting = ref(false);
const fotoPreviewUrls = ref<string[]>([]);
// Store deleted media IDs to be sent on save
const deletedMediaIds = ref<number[]>([]);

// Create reactive copy of calendar_data
const calendarData = ref([...props.calendar_data]);

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Rekap Absen', href: '#' },
];

// Group calendar data by month
const groupedByMonth = computed(() => {
    const groups: Record<string, typeof calendarData.value> = {};
    calendarData.value.forEach((item) => {
        const date = new Date(item.tanggal);
        const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        if (!groups[monthKey]) {
            groups[monthKey] = [];
        }
        groups[monthKey].push(item);
    });
    return groups;
});

// Get month name in Indonesian
const getMonthName = (dateStr: string) => {
    const date = new Date(dateStr);
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return months[date.getMonth()];
};

// Format date to Indonesian
const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    const day = date.getDate();
    const month = getMonthName(dateStr);
    const year = date.getFullYear();
    return `${day} ${month} ${year}`;
};

// Get day name in Indonesian
const getDayName = (dateStr: string) => {
    const date = new Date(dateStr);
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return days[date.getDay()];
};

// Open dialog for editing a date
const openEditDialog = (item: typeof calendarData.value[0]) => {
    selectedDate.value = item.tanggal;
    // Create a deep copy to avoid mutating original
    selectedRekap.value = JSON.parse(JSON.stringify(item));
    keterangan.value = item.keterangan || '';
    // Revoke old preview URLs
    fotoPreviewUrls.value.forEach(url => {
        if (url && typeof URL !== 'undefined' && URL.revokeObjectURL) {
            URL.revokeObjectURL(url);
        }
    });
    fotoFiles.value = [];
    fileNilaiFiles.value = [];
    fotoPreviewUrls.value = [];
    deletedMediaIds.value = []; // Reset deleted media IDs
    isDialogOpen.value = true;
};

// Handle file selection
const handleFotoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files) {
        const files = Array.from(target.files);
        fotoFiles.value = files;
        // Create preview URLs
        fotoPreviewUrls.value = files.map(file => {
            if (typeof URL !== 'undefined' && URL.createObjectURL) {
                return URL.createObjectURL(file);
            }
            return '';
        });
    }
};

const handleFileNilaiChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files) {
        fileNilaiFiles.value = Array.from(target.files);
    }
};

// Remove file from preview
const removeFotoFile = (index: number) => {
    // Revoke object URL to free memory
    if (fotoPreviewUrls.value[index] && typeof URL !== 'undefined' && URL.revokeObjectURL) {
        URL.revokeObjectURL(fotoPreviewUrls.value[index]);
    }
    fotoFiles.value.splice(index, 1);
    fotoPreviewUrls.value.splice(index, 1);
};

const removeFileNilai = (index: number) => {
    fileNilaiFiles.value.splice(index, 1);
};

// Submit form
const submitForm = async () => {
    if (!selectedDate.value) return;

    isSubmitting.value = true;
    try {
        const formData = new FormData();
        formData.append('tanggal', selectedDate.value);
        formData.append('keterangan', keterangan.value);

        // Add foto files
        fotoFiles.value.forEach((file) => {
            formData.append('foto_absen[]', file);
        });

        // Add file nilai files
        fileNilaiFiles.value.forEach((file) => {
            formData.append('file_nilai[]', file);
        });

        // Add deleted media IDs (seperti is_delete_foto di atlet)
        deletedMediaIds.value.forEach((mediaId) => {
            formData.append('deleted_media_ids[]', mediaId.toString());
        });

        let url = `/program-latihan/${props.program_latihan.id}/rekap-absen`;

        // If updating, use PUT method with method spoofing
        if (selectedRekap.value?.rekap_id) {
            url = `/program-latihan/${props.program_latihan.id}/rekap-absen/${selectedRekap.value.rekap_id}`;
            formData.append('_method', 'PUT');
        }

        await axios.post(url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        // Cleanup preview URLs
        fotoPreviewUrls.value.forEach(url => {
            if (url && typeof URL !== 'undefined' && URL.revokeObjectURL) {
                URL.revokeObjectURL(url);
            }
        });
        fotoPreviewUrls.value = [];
        deletedMediaIds.value = []; // Reset deleted media IDs
        
        // Reload data dari server untuk sync
        toast({ title: 'Rekap absen berhasil disimpan', variant: 'success' });
        isDialogOpen.value = false;
        
        // Reload hanya data, tidak reload seluruh halaman
        router.reload({ only: ['calendar_data'] });
    } catch (error: any) {
        toast({
            title: error.response?.data?.message || 'Gagal menyimpan rekap absen',
            variant: 'destructive',
        });
    } finally {
        isSubmitting.value = false;
    }
};

// Delete media (hanya update UI, hapus saat save)
const deleteMedia = (mediaId: number) => {
    // Tambahkan ke list deleted media IDs
    if (!deletedMediaIds.value.includes(mediaId)) {
        deletedMediaIds.value.push(mediaId);
    }

    // Update state local langsung (optimistic update)
    if (selectedRekap.value.foto_absen) {
        const fotoIndex = selectedRekap.value.foto_absen.findIndex((f: any) => f.id === mediaId);
        if (fotoIndex !== -1) {
            selectedRekap.value.foto_absen.splice(fotoIndex, 1);
        }
    }
    
    if (selectedRekap.value.file_nilai) {
        const fileIndex = selectedRekap.value.file_nilai.findIndex((f: any) => f.id === mediaId);
        if (fileIndex !== -1) {
            selectedRekap.value.file_nilai.splice(fileIndex, 1);
        }
    }
    
    // Update calendar_data juga
    const calendarItem = calendarData.value.find(item => item.tanggal === selectedDate.value);
    if (calendarItem) {
        if (calendarItem.foto_absen) {
            const fotoIndex = calendarItem.foto_absen.findIndex((f: any) => f.id === mediaId);
            if (fotoIndex !== -1) {
                calendarItem.foto_absen.splice(fotoIndex, 1);
            }
        }
        
        if (calendarItem.file_nilai) {
            const fileIndex = calendarItem.file_nilai.findIndex((f: any) => f.id === mediaId);
            if (fileIndex !== -1) {
                calendarItem.file_nilai.splice(fileIndex, 1);
            }
        }
    }
};

// Check if date has data
const hasData = (item: typeof calendarData.value[0]) => {
    return item.rekap_id && (item.keterangan || item.foto_absen.length > 0 || item.file_nilai.length > 0);
};

// Watch for props changes to update local state
watch(() => props.calendar_data, (newData) => {
    calendarData.value = [...newData];
}, { deep: true, immediate: true });

// Cleanup preview URLs on unmount
onBeforeUnmount(() => {
    fotoPreviewUrls.value.forEach(url => {
        if (url && typeof URL !== 'undefined' && URL.revokeObjectURL) {
            URL.revokeObjectURL(url);
        }
    });
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 bg-background text-foreground">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Rekap Absen Program Latihan</h1>
                    <p class="text-muted-foreground mt-1">
                        {{ program_latihan.nama_program }} - {{ program_latihan.cabor_nama }}
                    </p>
                </div>
                <Button variant="outline" @click="router.visit('/program-latihan')">
                    Kembali
                </Button>
            </div>
            <div class="bg-muted p-4 rounded-lg border border-border">
                <p class="text-sm text-foreground">
                    <strong>Durasi:</strong> {{ program_latihan.periode_hitung || '-' }}
                </p>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="space-y-8">
            <div v-for="(items, monthKey) in groupedByMonth" :key="monthKey" class="border border-border rounded-lg p-4 bg-card">
                <h2 class="text-xl font-semibold mb-4 text-foreground">
                    {{ getMonthName(items[0].tanggal) }} {{ new Date(items[0].tanggal).getFullYear() }}
                </h2>
                <div class="grid grid-cols-7 gap-2">
                    <!-- Day headers -->
                    <div
                        v-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']"
                        :key="day"
                        class="text-center font-semibold text-sm text-muted-foreground py-2"
                    >
                        {{ day }}
                    </div>
                    <!-- Calendar cells -->
                    <div
                        v-for="item in items"
                        :key="item.tanggal"
                        class="border border-border rounded-lg p-2 min-h-[100px] cursor-pointer transition-colors bg-card text-card-foreground"
                        :class="{
                            'bg-accent/50 border-accent hover:bg-accent': hasData(item),
                            'hover:bg-muted': !hasData(item),
                        }"
                        @click="openEditDialog(item)"
                    >
                        <div class="text-sm font-medium mb-1 text-foreground">
                            {{ new Date(item.tanggal).getDate() }}
                        </div>
                        <div class="text-xs text-muted-foreground mb-2">
                            {{ getDayName(item.tanggal) }}
                        </div>
                        <div v-if="hasData(item)" class="space-y-1">
                            <div v-if="item.foto_absen.length > 0" class="flex items-center gap-1 text-xs text-foreground">
                                <ImageIcon class="h-3 w-3" />
                                <span>{{ item.foto_absen.length }} foto</span>
                            </div>
                            <div v-if="item.file_nilai.length > 0" class="flex items-center gap-1 text-xs text-foreground">
                                <FileText class="h-3 w-3" />
                                <span>{{ item.file_nilai.length }} file</span>
                            </div>
                            <div v-if="item.keterangan" class="text-xs text-muted-foreground truncate">
                                {{ item.keterangan }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Dialog -->
        <Dialog v-model:open="isDialogOpen">
            <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        Rekap Absen - {{ selectedDate ? formatDate(selectedDate) : '' }}
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-4 mt-4">
                    <!-- Keterangan -->
                    <div>
                        <label class="text-sm font-medium mb-2 block">Keterangan (Opsional)</label>
                        <textarea
                            v-model="keterangan"
                            placeholder="Masukkan keterangan..."
                            rows="3"
                            class="border-input bg-background text-foreground placeholder:text-muted-foreground focus-visible:ring-ring min-h-[100px] w-full rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                    </div>

                    <!-- Foto Absen -->
                    <div>
                        <label class="text-sm font-medium mb-2 block">Foto Absen</label>
                        <div class="space-y-2">
                            <!-- Existing photos -->
                            <div v-if="selectedRekap?.foto_absen?.length > 0" class="grid grid-cols-4 gap-2">
                                <div
                                    v-for="foto in selectedRekap.foto_absen"
                                    :key="foto.id"
                                    class="relative group"
                                >
                                    <a
                                        :href="foto.url"
                                        target="_blank"
                                        class="block w-full h-24 rounded border overflow-hidden hover:opacity-80 transition-opacity"
                                    >
                                        <img
                                            :src="foto.url"
                                            :alt="foto.name"
                                            class="w-full h-full object-cover"
                                        />
                                    </a>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                        @click.stop="deleteMedia(foto.id)"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                    </Button>
                                </div>
                            </div>
                            <!-- New photo files -->
                            <div v-if="fotoFiles.length > 0" class="grid grid-cols-4 gap-2">
                                <div
                                    v-for="(file, index) in fotoFiles"
                                    :key="index"
                                    class="relative"
                                >
                                    <img
                                        :src="fotoPreviewUrls[index] || ''"
                                        :alt="file.name"
                                        class="w-full h-24 object-cover rounded border"
                                    />
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        class="absolute top-1 right-1"
                                        @click="removeFotoFile(index)"
                                    >
                                        <X class="h-3 w-3" />
                                    </Button>
                                </div>
                            </div>
                            <!-- Upload button -->
                            <div>
                                <Input
                                    type="file"
                                    accept="image/*"
                                    multiple
                                    @change="handleFotoChange"
                                    class="cursor-pointer"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Format: JPEG, PNG, GIF (maks 5MB per file)
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- File Nilai -->
                    <div>
                        <label class="text-sm font-medium mb-2 block">File Nilai Atlet (PDF, Excel)</label>
                        <div class="space-y-2">
                            <!-- Existing files -->
                            <div v-if="selectedRekap?.file_nilai?.length > 0" class="space-y-2">
                                <div
                                    v-for="file in selectedRekap.file_nilai"
                                    :key="file.id"
                                    class="flex items-center justify-between p-2 border rounded"
                                >
                                    <div class="flex items-center gap-2">
                                        <FileText class="h-4 w-4" />
                                        <a
                                            :href="file.url"
                                            target="_blank"
                                            class="text-sm hover:underline"
                                        >
                                            {{ file.name }}
                                        </a>
                                    </div>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="deleteMedia(file.id)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <!-- New files -->
                            <div v-if="fileNilaiFiles.length > 0" class="space-y-2">
                                <div
                                    v-for="(file, index) in fileNilaiFiles"
                                    :key="index"
                                    class="flex items-center justify-between p-2 border rounded"
                                >
                                    <span class="text-sm">{{ file.name }}</span>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="removeFileNilai(index)"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <!-- Upload button -->
                            <div>
                                <Input
                                    type="file"
                                    accept=".pdf,.xls,.xlsx"
                                    multiple
                                    @change="handleFileNilaiChange"
                                    class="cursor-pointer"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Format: PDF, XLS, XLSX (maks 10MB per file)
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-2 pt-4">
                        <Button variant="outline" @click="isDialogOpen = false" :disabled="isSubmitting">
                            Batal
                        </Button>
                        <Button @click="submitForm" :disabled="isSubmitting">
                            <Upload class="h-4 w-4 mr-2" />
                            {{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
    </AppLayout>
</template>

