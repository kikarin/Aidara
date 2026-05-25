<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import { router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { X, Upload, FileText, Image as ImageIcon, Trash2, Download, ChevronDown, ChevronUp, Eye, Camera, MapPin, Clock, Calendar as CalendarIcon } from 'lucide-vue-next';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

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
        jenis_latihan?: string;
        keterangan?: string;
        foto_absen: Array<{ id: number; url: string; name: string; lokasi?: string | null; latitude?: number | null; longitude?: number | null; waktu_foto?: string | null }>;
        file_nilai: Array<{ id: number; url: string; name: string }>;
    }>;
    pelatih_data?: {
        nama: string;
        kategori_peserta: string;
        cabor: string;
        jenis_pelatih: string;
    } | null;
}>();

const { toast } = useToast();

const selectedDate = ref<string | null>(null);
const selectedRekap = ref<any>(null);
const isDialogOpen = ref(false);
const jenisLatihan = ref<string>('');
const keterangan = ref('');
const fotoFiles = ref<File[]>([]);
const fileNilaiFiles = ref<File[]>([]);
const isSubmitting = ref(false);
const fotoPreviewUrls = ref<string[]>([]);
const fotoLocations = ref<Array<{ latitude?: number; longitude?: number; lokasi?: string; waktu_foto: string }>>([]);
// Store deleted media IDs to be sent on save
const deletedMediaIds = ref<number[]>([]);
// Track expanded dates for show detail
const expandedDates = ref<Set<string>>(new Set());

// Camera and location states
const isCameraOpen = ref(false);
const videoStream = ref<MediaStream | null>(null);
const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const currentLocation = ref<GeolocationCoordinates | null>(null);
const locationError = ref<string | null>(null);
const isGettingLocation = ref(false);
const locationAddress = ref<string>('');

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

// Get current time in HH:MM format (Asia/Jakarta timezone)
const getCurrentTime = () => {
    const now = new Date();
    return now.toLocaleTimeString('id-ID', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: false,
        timeZone: 'Asia/Jakarta'
    });
};

// Get formatted date for display (hari, tanggal bulan tahun)
const getFormattedDateTime = () => {
    const dateToFormat = selectedDate.value || getTodayDate();
    const dayName = getDayName(dateToFormat);
    const formattedDate = formatDate(dateToFormat);
    return `${dayName}, ${formattedDate}`;
};

// Get today's date in YYYY-MM-DD format (Asia/Jakarta timezone)
// Menggunakan tanggal kalender biasa, setelah jam 00:00 tanggal sudah berubah
const getTodayDate = () => {
    const now = new Date();
    // Dapatkan tanggal di timezone Asia/Jakarta
    const jakartaDate = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).format(now);
    
    return jakartaDate;
};

// Check if date is today (menggunakan logika jam 7)
const isToday = (dateStr: string) => {
    return dateStr === getTodayDate();
};

const getFotoLokasiLabel = (foto: { lokasi?: string | null; latitude?: number | null; longitude?: number | null }): string | null => {
    if (foto.lokasi) {
        return foto.lokasi;
    }
    if (foto.latitude != null && foto.longitude != null) {
        return `${Number(foto.latitude).toFixed(6)}, ${Number(foto.longitude).toFixed(6)}`;
    }
    return null;
};

const getFotoMapsUrl = (foto: { latitude?: number | null; longitude?: number | null }): string | null => {
    if (foto.latitude == null || foto.longitude == null) {
        return null;
    }
    return `https://www.google.com/maps?q=${foto.latitude},${foto.longitude}`;
};

const getFotoWaktuLabel = (foto: { waktu_foto?: string | null }): string | null => {
    return foto.waktu_foto ? `${foto.waktu_foto} WIB` : null;
};

// Get jenis latihan label
const getJenisLatihanLabel = (value: string | null | undefined): string => {
    const labels: Record<string, string> = {
        'latihan_fisik': 'Latihan Fisik',
        'latihan_strategi': 'Latihan Strategi',
        'latihan_teknik': 'Latihan Teknik',
        'latihan_mental': 'Latihan Mental',
        'latihan_pemulihan': 'Latihan Pemulihan',
    };
    return value ? labels[value] || value : '';
};

// Open dialog for editing a date
const openEditDialog = (item: typeof calendarData.value[0]) => {
    // Prevent editing if not today
    if (!isToday(item.tanggal)) {
        toast({
            title: 'Tidak dapat mengedit tanggal ini. Hanya dapat input rekap absen untuk tanggal hari ini',
            variant: 'destructive',
        });
        return;
    }
    
    selectedDate.value = item.tanggal;
    // Create a deep copy to avoid mutating original
    selectedRekap.value = JSON.parse(JSON.stringify(item));
    jenisLatihan.value = item.jenis_latihan || '';
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
    fotoLocations.value = [];
    deletedMediaIds.value = []; // Reset deleted media IDs
    // Reset location state
    currentLocation.value = null;
    locationError.value = null;
    locationAddress.value = '';
    isDialogOpen.value = true;
};

// Get location before taking photo
const getLocation = (): Promise<void> => {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Geolocation tidak didukung oleh browser Anda'));
            return;
        }

        isGettingLocation.value = true;
        locationError.value = null;

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                currentLocation.value = position.coords;
                isGettingLocation.value = false;

                // Reverse geocoding untuk mendapatkan alamat
                try {
                    const response = await fetch(
                        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.coords.latitude}&lon=${position.coords.longitude}&zoom=18&addressdetails=1`,
                        {
                            headers: {
                                'User-Agent': 'Aidara App'
                            }
                        }
                    );
                    const data = await response.json();
                    if (data && data.display_name) {
                        locationAddress.value = data.display_name;
                    } else {
                        locationAddress.value = `${position.coords.latitude}, ${position.coords.longitude}`;
                    }
                } catch (error) {
                    locationAddress.value = `${position.coords.latitude}, ${position.coords.longitude}`;
                }

                resolve();
            },
            (error) => {
                isGettingLocation.value = false;
                let errorMessage = 'Gagal mendapatkan lokasi';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Akses lokasi ditolak. Silakan izinkan akses lokasi untuk melanjutkan.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'Informasi lokasi tidak tersedia.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'Waktu permintaan lokasi habis.';
                        break;
                }
                locationError.value = errorMessage;
                reject(new Error(errorMessage));
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    });
};

// Open camera for photo capture
const openCamera = async () => {
    try {
        // First, get location
        await getLocation();
        
        // Then open camera
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' } // Use back camera on mobile
        });
        
        videoStream.value = stream;
        isCameraOpen.value = true;
        
        // Wait for next tick to ensure video element is rendered
        await new Promise(resolve => setTimeout(resolve, 100));
        if (videoRef.value) {
            videoRef.value.srcObject = stream;
        }
    } catch (error: any) {
        if (error.message.includes('lokasi')) {
            toast({
                title: error.message,
                variant: 'destructive',
            });
        } else if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            toast({
                title: 'Akses kamera ditolak. Silakan izinkan akses kamera untuk mengambil foto.',
                variant: 'destructive',
            });
        } else {
            toast({
                title: 'Gagal membuka kamera. Pastikan perangkat Anda memiliki kamera dan akses telah diizinkan.',
                variant: 'destructive',
            });
        }
    }
};

// Close camera
const closeCamera = () => {
    if (videoStream.value) {
        videoStream.value.getTracks().forEach(track => track.stop());
        videoStream.value = null;
    }
    isCameraOpen.value = false;
    if (videoRef.value) {
        videoRef.value.srcObject = null;
    }
};

// Capture photo from camera
const capturePhoto = () => {
    if (!videoRef.value || !canvasRef.value) return;

    const video = videoRef.value;
    const canvas = canvasRef.value;
    const context = canvas.getContext('2d');

    if (!context) return;

    // Set canvas dimensions to match video
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    // Draw video frame to canvas
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Convert canvas to blob
    canvas.toBlob((blob) => {
        if (!blob) return;

        // Create File from blob with timestamp and location in name
        const now = new Date();
        const timestamp = now.toISOString().replace(/[:.]/g, '-');
        const fileName = `foto-absen-${timestamp}.jpg`;
        const file = new File([blob], fileName, { type: 'image/jpeg' });

        // Add to fotoFiles
        fotoFiles.value.push(file);

        fotoLocations.value.push({
            waktu_foto: getCurrentTime(),
            ...(currentLocation.value
                ? {
                    latitude: currentLocation.value.latitude,
                    longitude: currentLocation.value.longitude,
                    lokasi: locationAddress.value || `${currentLocation.value.latitude.toFixed(6)}, ${currentLocation.value.longitude.toFixed(6)}`,
                }
                : {}),
        });
        
        // Create preview URL
        const previewUrl = URL.createObjectURL(blob);
        fotoPreviewUrls.value.push(previewUrl);

        // Close camera
        closeCamera();
    }, 'image/jpeg', 0.9);
};

// Handle file selection (kept for compatibility, but won't be used for foto absen)
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
    fotoLocations.value.splice(index, 1);
};

const removeFileNilai = (index: number) => {
    fileNilaiFiles.value.splice(index, 1);
};

// Submit form
const submitForm = async () => {
    if (!selectedDate.value) return;

    if (!jenisLatihan.value) {
        toast({
            title: 'Jenis latihan harus diisi',
            variant: 'destructive',
        });
        isSubmitting.value = false;
        return;
    }

    isSubmitting.value = true;
    try {
        const formData = new FormData();
        formData.append('tanggal', selectedDate.value);
        formData.append('jenis_latihan', jenisLatihan.value);
        formData.append('keterangan', keterangan.value);

        fotoFiles.value.forEach((file, index) => {
            formData.append('foto_absen[]', file);
            const lokasi = fotoLocations.value[index];
            if (lokasi) {
                formData.append('foto_lokasi[]', JSON.stringify(lokasi));
            } else {
                formData.append('foto_lokasi[]', '');
            }
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
        fotoLocations.value = [];
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
    return item.rekap_id && (item.jenis_latihan || item.keterangan || item.foto_absen.length > 0 || item.file_nilai.length > 0);
};

// Toggle expand/collapse untuk show detail
const toggleExpand = (tanggal: string, event: Event) => {
    event.stopPropagation(); // Prevent triggering openEditDialog
    if (expandedDates.value.has(tanggal)) {
        expandedDates.value.delete(tanggal);
    } else {
        expandedDates.value.add(tanggal);
    }
};

// Check if date is expanded
const isExpanded = (tanggal: string) => {
    return expandedDates.value.has(tanggal);
};

// Watch for props changes to update local state
watch(() => props.calendar_data, (newData) => {
    calendarData.value = [...newData];
}, { deep: true, immediate: true });

// Watch dialog open/close to close camera if needed
watch(() => isDialogOpen.value, (isOpen) => {
    if (!isOpen && isCameraOpen.value) {
        closeCamera();
    }
});

// Cleanup preview URLs and camera stream on unmount
onBeforeUnmount(() => {
    fotoPreviewUrls.value.forEach(url => {
        if (url && typeof URL !== 'undefined' && URL.revokeObjectURL) {
            URL.revokeObjectURL(url);
        }
    });
    // Close camera if open
    closeCamera();
});

// Export PDF function
const exportToPDF = () => {
    try {
        const doc = new jsPDF('p', 'mm', 'a4');
        const pageWidth = doc.internal.pageSize.getWidth();
        const margin = 15;
        let yPos = margin;
        const rekapAbsenUrl = `${window.location.origin}/program-latihan/${props.program_latihan.id}/rekap-absen`;

        // Helper function untuk menambahkan halaman baru jika diperlukan
        const checkNewPage = (requiredSpace: number) => {
            if (yPos + requiredSpace > doc.internal.pageSize.getHeight() - margin) {
                doc.addPage();
                yPos = margin;
            }
        };

        // Header
        doc.setFontSize(18);
        doc.setFont('helvetica', 'bold');
        doc.text('LAPORAN REKAP ABSEN PROGRAM LATIHAN', pageWidth / 2, yPos, { align: 'center' });
        yPos += 8;
        
        doc.setFontSize(14);
        doc.setFont('helvetica', 'normal');
        doc.text(props.program_latihan.nama_program, pageWidth / 2, yPos, { align: 'center' });
        yPos += 6;
        
        doc.setFontSize(11);
        doc.text(`Periode: ${formatDate(props.program_latihan.periode_mulai)} - ${formatDate(props.program_latihan.periode_selesai)}`, 
                 pageWidth / 2, yPos, { align: 'center' });
        yPos += 8;
        
        // Link ke halaman rekap absen
        doc.setFontSize(10);
        doc.setTextColor(0, 0, 255); // Blue color for link
        doc.textWithLink('Lihat Rekap Absen Online', pageWidth / 2, yPos, {
            align: 'center',
            url: rekapAbsenUrl,
        });
        doc.setTextColor(0, 0, 0); // Reset to black
        yPos += 10;

        // Informasi Pelatih
        if (props.pelatih_data) {
            checkNewPage(30);
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('INFORMASI PELATIH', margin, yPos);
            yPos += 8;
            
            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            const pelatihInfo = [
                ['Nama Pelatih', props.pelatih_data.nama],
                ['Kategori Peserta', props.pelatih_data.kategori_peserta],
                ['Cabor', props.pelatih_data.cabor],
            ];
            
            autoTable(doc, {
                startY: yPos,
                head: [],
                body: pelatihInfo,
                theme: 'plain',
                styles: { fontSize: 11 },
                columnStyles: {
                    0: { fontStyle: 'bold', cellWidth: 60 },
                    1: { cellWidth: 110 },
                },
                margin: { left: margin, right: margin },
            });
            yPos = (doc as any).lastAutoTable.finalY + 10;
        }

        // Rekap Absen
        checkNewPage(30);
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text('REKAP ABSEN PER TANGGAL', margin, yPos);
        yPos += 8;
        
        // Filter hanya yang punya data
        const rekapData = calendarData.value.filter(item => hasData(item));
        
        if (rekapData.length > 0) {
            const tableData = rekapData.map(item => [
                formatDate(item.tanggal),
                getJenisLatihanLabel(item.jenis_latihan),
                item.keterangan || '-',
                `${item.foto_absen.length} foto, ${item.file_nilai.length} file`
            ]);
            
            autoTable(doc, {
                startY: yPos,
                head: [['Tanggal', 'Jenis Latihan', 'Keterangan', 'Foto/File']],
                body: tableData,
                theme: 'striped',
                styles: { fontSize: 10 },
                headStyles: { fillColor: [66, 139, 202], textColor: 255, fontStyle: 'bold' },
                margin: { left: margin, right: margin },
            });
            yPos = (doc as any).lastAutoTable.finalY + 15;
        } else {
            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            doc.text('Belum ada data rekap absen', margin, yPos);
            yPos += 10;
        }

        // Ringkasan
        checkNewPage(40);
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text('RINGKASAN', margin, yPos);
        yPos += 8;
        
        // Hitung statistik
        const stats = {
            total: rekapData.length,
            fisik: rekapData.filter(r => r.jenis_latihan === 'latihan_fisik').length,
            strategi: rekapData.filter(r => r.jenis_latihan === 'latihan_strategi').length,
            teknik: rekapData.filter(r => r.jenis_latihan === 'latihan_teknik').length,
            mental: rekapData.filter(r => r.jenis_latihan === 'latihan_mental').length,
            pemulihan: rekapData.filter(r => r.jenis_latihan === 'latihan_pemulihan').length,
        };
        
        const summaryData: string[][] = [
            ['Total Hari Latihan', stats.total.toString()],
            ['Latihan Fisik', stats.fisik.toString()],
            ['Latihan Strategi', stats.strategi.toString()],
            ['Latihan Teknik', stats.teknik.toString()],
            ['Latihan Mental', stats.mental.toString()],
            ['Latihan Pemulihan', stats.pemulihan.toString()],
        ];
        
        autoTable(doc, {
            startY: yPos,
            head: [],
            body: summaryData,
            theme: 'plain',
            styles: { fontSize: 11 },
            columnStyles: {
                0: { fontStyle: 'bold', cellWidth: 60 },
                1: { cellWidth: 110 },
            },
            margin: { left: margin, right: margin },
        });
        
        // Footer
        const totalPages = doc.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            doc.setPage(i);
            doc.setFontSize(8);
            doc.setFont('helvetica', 'italic');
            doc.text(`Halaman ${i} dari ${totalPages}`, pageWidth / 2, 
                     doc.internal.pageSize.getHeight() - 10, { align: 'center' });
            
            // Link ke rekap absen di footer
            doc.setTextColor(0, 0, 255); // Blue color for link
            doc.textWithLink('Rekap Absen Online', margin, 
                     doc.internal.pageSize.getHeight() - 10, { url: rekapAbsenUrl });
            doc.setTextColor(0, 0, 0); // Reset to black
            
            doc.text(`Dicetak pada: ${new Date().toLocaleString('id-ID')}`, 
                     pageWidth - margin, doc.internal.pageSize.getHeight() - 10, { align: 'right' });
        }
        
        const fileName = `Rekap_Absen_${props.program_latihan.nama_program.replace(/\s+/g, '_')}_${new Date().getTime()}.pdf`;
        doc.save(fileName);
        
        toast({ title: 'PDF berhasil diunduh', variant: 'success' });
    } catch (error: any) {
        console.error('Error exporting PDF:', error);
        toast({ 
            title: error.message || 'Gagal mengekspor PDF. Terjadi kesalahan saat mengekspor PDF',
            variant: 'destructive' 
        });
    }
};
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
                <div class="flex gap-2">
                    <Button 
                        v-if="calendarData.some((item) => hasData(item))"
                        variant="default"
                        @click="exportToPDF"
                    >
                        <Download class="h-4 w-4 mr-2" />
                        Export PDF
                    </Button>
                    <Button 
                        v-if="calendarData.some((item) => hasData(item))"
                        variant="outline"
                        @click="router.visit(`/program-latihan/${program_latihan.id}/rekap-absen/detail`)"
                    >
                        <Eye class="h-4 w-4 mr-2" />
                        Lihat Detail
                    </Button>
                    <Button variant="outline" @click="router.visit('/program-latihan')">
                        Kembali
                    </Button>
                </div>
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
                <div class="space-y-2">
                    <!-- Calendar Grid -->
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
                        <template v-for="item in items" :key="item.tanggal">
                            <div
                                class="border border-border rounded-lg p-2 min-h-[100px] transition-colors"
                                :class="{
                                    'bg-accent/50 border-accent hover:bg-accent cursor-pointer': hasData(item) && isToday(item.tanggal),
                                    'bg-muted/30 border-muted cursor-not-allowed opacity-40': !isToday(item.tanggal),
                                    'hover:bg-muted cursor-pointer': !hasData(item) && isToday(item.tanggal),
                                    'bg-card text-card-foreground': isToday(item.tanggal),
                                }"
                                @click="isToday(item.tanggal) ? openEditDialog(item) : null"
                            >
                        <div class="text-sm font-medium mb-1 text-foreground">
                            {{ new Date(item.tanggal).getDate() }}
                            <span v-if="isToday(item.tanggal)" class="ml-1 text-xs text-primary font-semibold">(Hari Ini)</span>
                        </div>
                        <div class="text-xs text-muted-foreground mb-2">
                            {{ getDayName(item.tanggal) }}
                        </div>
                        <div v-if="hasData(item)" class="space-y-1">
                            <div v-if="item.jenis_latihan" class="text-xs font-semibold text-primary">
                                {{ getJenisLatihanLabel(item.jenis_latihan) }}
                            </div>
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
                            <!-- Button Show Detail -->
                            <Button
                                v-if="hasData(item)"
                                variant="ghost"
                                size="sm"
                                class="w-full mt-2 h-6 text-xs"
                                @click.stop="toggleExpand(item.tanggal, $event)"
                            >
                                <Eye class="h-3 w-3 mr-1" />
                                {{ isExpanded(item.tanggal) ? 'Sembunyikan' : 'Lihat Detail' }}
                                <ChevronDown v-if="!isExpanded(item.tanggal)" class="h-3 w-3 ml-1" />
                                <ChevronUp v-else class="h-3 w-3 ml-1" />
                            </Button>
                        </div>
                            </div>
                        </template>
                    </div>
                    <!-- Expanded Detail Sections (outside grid) -->
                    <template v-for="item in items" :key="`detail-${item.tanggal}`">
                        <div
                            v-if="hasData(item) && isExpanded(item.tanggal)"
                            class="mt-2 p-4 border border-border rounded-lg bg-muted/50"
                        >
                            <div class="space-y-4">
                            <h3 class="font-semibold text-foreground">
                                Detail Rekap Absen - {{ formatDate(item.tanggal) }}
                            </h3>
                            
                            <!-- Jenis Latihan -->
                            <div v-if="item.jenis_latihan">
                                <label class="text-sm font-medium text-foreground">Jenis Latihan:</label>
                                <p class="text-sm text-foreground">{{ getJenisLatihanLabel(item.jenis_latihan) }}</p>
                            </div>
                            
                            <!-- Keterangan -->
                            <div v-if="item.keterangan">
                                <label class="text-sm font-medium text-foreground">Keterangan:</label>
                                <p class="text-sm text-foreground whitespace-pre-wrap">{{ item.keterangan }}</p>
                            </div>
                            
                            <!-- Foto Absen -->
                            <div v-if="item.foto_absen.length > 0">
                                <label class="text-sm font-medium text-foreground mb-2 block">
                                    Foto Absen ({{ item.foto_absen.length }}):
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div
                                        v-for="foto in item.foto_absen"
                                        :key="foto.id"
                                        class="space-y-2"
                                    >
                                        <a
                                            :href="foto.url"
                                            target="_blank"
                                            class="block w-full h-32 rounded border overflow-hidden hover:opacity-80 transition-opacity"
                                        >
                                            <img
                                                :src="foto.url"
                                                :alt="foto.name"
                                                class="w-full h-full object-cover"
                                            />
                                        </a>
                                        <div v-if="getFotoWaktuLabel(foto)" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <Clock class="h-3.5 w-3.5 shrink-0" />
                                            <span>{{ getFotoWaktuLabel(foto) }}</span>
                                        </div>
                                        <div v-if="getFotoLokasiLabel(foto)" class="flex items-start gap-1.5 text-xs text-muted-foreground">
                                            <MapPin class="h-3.5 w-3.5 shrink-0 mt-0.5" />
                                            <a
                                                v-if="getFotoMapsUrl(foto)"
                                                :href="getFotoMapsUrl(foto)!"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="hover:underline break-words"
                                            >
                                                {{ getFotoLokasiLabel(foto) }}
                                            </a>
                                            <span v-else class="break-words">{{ getFotoLokasiLabel(foto) }}</span>
                                        </div>
                                        <p v-if="!getFotoLokasiLabel(foto)" class="text-xs text-muted-foreground italic">Lokasi tidak tersedia</p>
                                        <p v-if="!getFotoWaktuLabel(foto)" class="text-xs text-muted-foreground italic">Jam tidak tersedia</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- File Nilai -->
                            <div v-if="item.file_nilai.length > 0">
                                <label class="text-sm font-medium text-foreground mb-2 block">
                                    File Nilai ({{ item.file_nilai.length }}):
                                </label>
                                <div class="space-y-2">
                                    <div
                                        v-for="file in item.file_nilai"
                                        :key="file.id"
                                        class="flex items-center justify-between p-2 border rounded bg-background"
                                    >
                                        <div class="flex items-center gap-2">
                                            <FileText class="h-4 w-4" />
                                            <a
                                                :href="file.url"
                                                target="_blank"
                                                class="text-sm hover:underline text-foreground"
                                            >
                                                {{ file.name }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>
                    </template>
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
                    <!-- Jenis Latihan -->
                    <div>
                        <label class="text-sm font-medium mb-2 block">Jenis Latihan *</label>
                        <select
                            v-model="jenisLatihan"
                            required
                            class="border-input bg-background text-foreground w-full rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="">Pilih Jenis Latihan</option>
                            <option value="latihan_fisik">Latihan Fisik</option>
                            <option value="latihan_strategi">Latihan Strategi</option>
                            <option value="latihan_teknik">Latihan Teknik</option>
                            <option value="latihan_mental">Latihan Mental</option>
                            <option value="latihan_pemulihan">Latihan Pemulihan</option>
                        </select>
                    </div>

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
                            <div v-if="selectedRekap?.foto_absen?.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div
                                    v-for="foto in selectedRekap.foto_absen"
                                    :key="foto.id"
                                    class="space-y-1"
                                >
                                    <div class="relative group">
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
                                    <div v-if="getFotoWaktuLabel(foto)" class="flex items-center gap-1 text-xs text-muted-foreground">
                                        <Clock class="h-3 w-3 shrink-0" />
                                        <span>{{ getFotoWaktuLabel(foto) }}</span>
                                    </div>
                                    <div v-if="getFotoLokasiLabel(foto)" class="flex items-start gap-1 text-xs text-muted-foreground">
                                        <MapPin class="h-3 w-3 shrink-0 mt-0.5" />
                                        <span class="break-words">{{ getFotoLokasiLabel(foto) }}</span>
                                    </div>
                                </div>
                            </div>
                            <!-- New photo files -->
                            <div v-if="fotoFiles.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div
                                    v-for="(file, index) in fotoFiles"
                                    :key="index"
                                    class="space-y-1"
                                >
                                    <div class="relative">
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
                                    <div v-if="fotoLocations[index]?.waktu_foto" class="flex items-center gap-1 text-xs text-muted-foreground">
                                        <Clock class="h-3 w-3 shrink-0" />
                                        <span>{{ fotoLocations[index].waktu_foto }} WIB</span>
                                    </div>
                                    <div v-if="fotoLocations[index]?.lokasi" class="flex items-start gap-1 text-xs text-muted-foreground">
                                        <MapPin class="h-3 w-3 shrink-0 mt-0.5" />
                                        <span class="break-words">{{ fotoLocations[index].lokasi }}</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Camera button -->
                            <div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="openCamera"
                                    class="w-full"
                                >
                                    <Camera class="h-4 w-4 mr-2" />
                                    Ambil Foto dengan Kamera
                                </Button>
                                <p class="text-xs text-muted-foreground mt-1">
                                    Lokasi harus diaktifkan sebelum mengambil foto
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

        <!-- Camera Dialog -->
        <Dialog v-model:open="isCameraOpen">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Ambil Foto Absen</DialogTitle>
                </DialogHeader>

                <div class="space-y-4">
                    <!-- Location and time info -->
                    <div v-if="currentLocation" class="bg-muted p-4 rounded-lg border border-border space-y-2">
                        <div class="flex items-center gap-2 text-sm">
                            <Clock class="h-4 w-4 text-primary" />
                            <span class="font-medium">{{ getCurrentTime() }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <CalendarIcon class="h-4 w-4 text-primary" />
                            <span class="font-medium">{{ getFormattedDateTime() }}</span>
                        </div>
                        <div class="flex items-start gap-2 text-sm">
                            <MapPin class="h-4 w-4 text-primary mt-0.5" />
                            <span class="font-medium break-words">{{ locationAddress || (currentLocation ? `${currentLocation.latitude.toFixed(6)}, ${currentLocation.longitude.toFixed(6)}` : 'Memuat lokasi...') }}</span>
                        </div>
                    </div>

                    <!-- Location error -->
                    <div v-if="locationError" class="bg-destructive/10 text-destructive p-4 rounded-lg border border-destructive/20">
                        <p class="text-sm font-medium">{{ locationError }}</p>
                        <p class="text-xs mt-1">Silakan aktifkan lokasi dan coba lagi.</p>
                    </div>

                    <!-- Loading location -->
                    <div v-if="isGettingLocation" class="bg-muted p-4 rounded-lg border border-border text-center">
                        <p class="text-sm text-muted-foreground">Mengambil lokasi...</p>
                    </div>

                    <!-- Video preview -->
                    <div v-if="!isGettingLocation && !locationError" class="relative bg-black rounded-lg overflow-hidden" style="aspect-ratio: 4/3;">
                        <video
                            ref="videoRef"
                            autoplay
                            playsinline
                            class="w-full h-full object-cover"
                        />
                        <canvas ref="canvasRef" class="hidden" />
                    </div>

                    <!-- Camera controls -->
                    <div class="flex justify-center gap-2">
                        <Button
                            variant="outline"
                            @click="closeCamera"
                        >
                            Batal
                        </Button>
                        <Button
                            v-if="currentLocation && !isGettingLocation && !locationError"
                            @click="capturePhoto"
                            class="bg-primary"
                        >
                            <Camera class="h-4 w-4 mr-2" />
                            Ambil Foto
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
    </AppLayout>
</template>

