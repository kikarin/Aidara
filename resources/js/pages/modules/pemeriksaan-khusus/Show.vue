<script setup lang="ts">
import AppTabs from '@/components/AppTabs.vue';
import ApexChart from '@/components/ApexChart.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Activity, BarChart3, Info, Loader2, Users, Download } from 'lucide-vue-next';
import permissionService from '@/services/permissionService';
import { computed, onMounted, ref, watch } from 'vue';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

const { toast } = useToast();

const props = defineProps<{ item: Record<string, any> }>();

// Tab management
function getTabFromUrl(url: string, fallback = 'informasi-data') {
    if (url.includes('tab=')) {
        return new URLSearchParams(url.split('?')[1]).get('tab') || fallback;
    }
    return fallback;
}

const page = usePage();
const initialTab = getTabFromUrl(page.url);
const activeTab = ref(initialTab);

watch(activeTab, (val) => {
    const url = `/pemeriksaan-khusus/${props.item.id}?tab=${val}`;
    router.visit(url, { replace: true, preserveState: true, preserveScroll: true, only: [] });
});

watch(
    () => page.url,
    (newUrl) => {
        const tab = getTabFromUrl(newUrl);
        if (tab !== activeTab.value) {
            activeTab.value = tab;
        }
    },
);

const dynamicTitle = computed(() => {
    if (activeTab.value === 'informasi-data') {
        return `Informasi : ${props.item.nama_pemeriksaan || 'Pemeriksaan Khusus'}`;
    } else if (activeTab.value === 'visualisasi-data') {
        return `Visualisasi : ${props.item.nama_pemeriksaan || 'Pemeriksaan Khusus'}`;
    }
    return `Pemeriksaan Khusus : ${props.item.nama_pemeriksaan || ''}`;
});

const breadcrumbs = [
    { title: 'Pemeriksaan Khusus', href: '/pemeriksaan-khusus' },
    { title: 'Detail Pemeriksaan Khusus', href: `/pemeriksaan-khusus/${props.item.id}` },
];

const tabsConfig = [
    {
        value: 'informasi-data',
        label: 'Informasi',
    },
    {
        value: 'visualisasi-data',
        label: 'Visualisasi',
    },
];

const fields = computed(() => {
    const status = props.item?.status;
    const statusMap = {
        belum: {
            label: 'Belum',
            class: 'text-red-800 bg-red-300',
        },
        sebagian: {
            label: 'Sebagian',
            class: 'text-yellow-800 bg-yellow-100',
        },
        selesai: {
            label: 'Selesai',
            class: 'text-green-800 bg-green-100',
        },
    };

    const statusValue = statusMap[status as keyof typeof statusMap] || { label: '-', class: 'text-gray-500' };

    return [
        { label: 'Cabor', value: props.item?.cabor?.nama || '-' },
        { label: 'Kategori', value: props.item?.cabor_kategori?.nama || '-' },
        { label: 'Nama Pemeriksaan', value: props.item?.nama_pemeriksaan || '-' },
        {
            label: 'Tanggal Pemeriksaan',
            value: props.item?.tanggal_pemeriksaan
                ? new Date(props.item.tanggal_pemeriksaan).toLocaleDateString('id-ID')
                : '-',
        },
        {
            label: 'Status',
            value: statusValue.label,
            className: `inline-block px-2 py-1 text-xs font-semibold rounded-full ${statusValue.class}`,
        },
    ];
});

const actionFields = computed(() => {
    if (activeTab.value !== 'informasi-data') return [];
    return [
        { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
        { label: 'Created By', value: props.item.created_by_user?.name || '-' },
        { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
        { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
    ];
});

const handleDelete = () => {
    router.delete(`/pemeriksaan-khusus/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data pemeriksaan khusus berhasil dihapus', variant: 'success' });
            router.visit('/pemeriksaan-khusus');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data pemeriksaan khusus', variant: 'destructive' });
        },
    });
};

// Visualisasi data
const loadingVisualisasi = ref(false);
const visualisasiData = ref<any[]>([]);
const aspekList = ref<any[]>([]);
const selectedPeserta = ref<any>(null);

// Load data visualisasi
const loadVisualisasi = async () => {
    if (activeTab.value !== 'visualisasi-data') return;
    
    loadingVisualisasi.value = true;
    try {
        const res = await axios.get(`/api/pemeriksaan-khusus/${props.item.id}/visualisasi`);
        if (res.data.success) {
            visualisasiData.value = res.data.data || [];
            aspekList.value = res.data.aspek_list || [];

            // Set peserta pertama sebagai default
            if (visualisasiData.value.length > 0) {
                selectedPeserta.value = visualisasiData.value[0];
            }
        }
    } catch (error: any) {
        console.error('Error loading visualisasi:', error);
        toast({
            title: error.response?.data?.message || 'Gagal memuat data visualisasi',
            variant: 'destructive',
        });
    } finally {
        loadingVisualisasi.value = false;
    }
};

// Load visualisasi when tab changes to visualisasi
watch(activeTab, (newTab) => {
    if (newTab === 'visualisasi-data') {
        loadVisualisasi();
    }
});

// Helper: Convert to number safely
const toNumber = (value: any): number | null => {
    if (value === null || value === undefined || value === '') return null;
    const num = typeof value === 'string' ? parseFloat(value) : Number(value);
    return isNaN(num) ? null : num;
};

// Helper: Format percentage safely
const formatPersentase = (value: any): string => {
    const num = toNumber(value);
    if (num === null) return '-';
    return `${num.toFixed(1)}%`;
};

// Helper: Get predikat label
const getPredikatLabel = (predikat: string | null): string => {
    if (!predikat) return '-';
    const labels: Record<string, string> = {
        sangat_kurang: 'Sangat Kurang',
        kurang: 'Kurang',
        sedang: 'Sedang',
        mendekati_target: 'Mendekati Target',
        target: 'Target',
    };
    return labels[predikat] || predikat;
};

// Helper: Get predikat color
const getPredikatColor = (predikat: string | null): string => {
    if (!predikat) return 'bg-gray-300 text-gray-600';
    const colors: Record<string, string> = {
        sangat_kurang: 'bg-red-500 text-white',
        kurang: 'bg-orange-500 text-white',
        sedang: 'bg-yellow-500 text-white',
        mendekati_target: 'bg-green-400 text-white',
        target: 'bg-green-600 text-white',
    };
    return colors[predikat] || 'bg-gray-500 text-white';
};

// Helper: Get predikat color for PDF (RGB array)
const getPredikatColorRGB = (predikat: string | null): number[] => {
    if (!predikat) return [156, 163, 175]; // gray-400
    const colors: Record<string, number[]> = {
        sangat_kurang: [239, 68, 68], // red-500
        kurang: [249, 115, 22], // orange-500
        sedang: [234, 179, 8], // yellow-500
        mendekati_target: [74, 222, 128], // green-400
        target: [22, 163, 74], // green-600
    };
    return colors[predikat] || [107, 114, 128]; // gray-500
};

// Radar chart options untuk aspek
const radarChartOptions = computed(() => {
    const isDark = document.documentElement.classList.contains('dark');

    return {
        chart: {
            type: 'radar',
            toolbar: { show: false },
            background: 'transparent',
        },
        stroke: {
            width: 2,
        },
        fill: {
            opacity: 0.3,
        },
        markers: {
            size: 4,
        },
        xaxis: {
            categories: aspekList.value.length > 0 ? aspekList.value.map((a) => a.nama) : [''],
            labels: {
                style: {
                    colors: isDark ? '#9ca3af' : '#6b7280',
                    fontSize: '12px',
                },
            },
        },
        yaxis: {
            min: 0,
            max: 100,
            tickAmount: 5,
            labels: {
                style: {
                    colors: isDark ? '#9ca3af' : '#6b7280',
                },
                formatter: (val: number) => `${val}%`,
            },
        },
        tooltip: {
            theme: isDark ? 'dark' : 'light',
            y: {
                formatter: (val: any) => {
                    const num = toNumber(val);
                    return num !== null ? `${num.toFixed(1)}%` : '0%';
                },
            },
        },
        plotOptions: {
            radar: {
                polygons: {
                    strokeColors: isDark ? '#374151' : '#e5e7eb',
                    connectorColors: isDark ? '#374151' : '#e5e7eb',
                    fill: {
                        colors: isDark ? ['#1f2937'] : ['#f9fafb'],
                    },
                },
            },
        },
        colors: ['#3b82f6'],
    };
});

// Radar chart series untuk aspek
const radarChartSeries = computed(() => {
    if (!selectedPeserta.value || aspekList.value.length === 0) return [];

    return [
        {
            name: selectedPeserta.value.peserta.nama,
            data: aspekList.value.map((aspek) => {
                const hasilAspek = selectedPeserta.value?.aspek?.find((a: any) => a.aspek_id === aspek.id);
                const nilai = toNumber(hasilAspek?.nilai_performa);
                return nilai !== null ? nilai : 0;
            }),
        },
    ];
});

// Gauge chart options untuk nilai keseluruhan
const gaugeChartOptions = computed(() => {
    const isDark = document.documentElement.classList.contains('dark');

    return {
        chart: {
            type: 'radialBar',
            toolbar: { show: false },
            background: 'transparent',
        },
        plotOptions: {
            radialBar: {
                startAngle: -90,
                endAngle: 90,
                track: {
                    background: isDark ? '#374151' : '#e5e7eb',
                    strokeWidth: '97%',
                    margin: 5,
                },
                dataLabels: {
                    name: {
                        show: true,
                        fontSize: '16px',
                        fontWeight: 600,
                        offsetY: -10,
                        color: isDark ? '#ffffff' : '#000000',
                    },
                    value: {
                        show: true,
                        fontSize: '30px',
                        fontWeight: 700,
                        offsetY: 16,
                        color: isDark ? '#ffffff' : '#000000',
                        formatter: (val: any) => {
                            const num = toNumber(val);
                            return num !== null ? `${num.toFixed(1)}%` : '0%';
                        },
                    },
                },
            },
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'horizontal',
                shadeIntensity: 0.5,
                gradientToColors: ['#10b981'],
                inverseColors: true,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100],
            },
        },
        labels: ['Nilai Keseluruhan'],
        colors: ['#3b82f6'],
    };
});

// Gauge chart series untuk nilai keseluruhan
const gaugeChartSeries = computed(() => {
    if (!selectedPeserta.value) return [0];
    const nilai = toNumber(selectedPeserta.value.nilai_keseluruhan);
    return nilai !== null ? [nilai] : [0];
});

// Helper: Get item tes by aspek (sorted by urutan)
const getItemTesByAspek = (itemTesList: any[], aspekId: number) => {
    if (!itemTesList || !Array.isArray(itemTesList)) return [];
    return itemTesList
        .filter((item) => item.aspek_id === aspekId)
        .sort((a, b) => (a.urutan || 0) - (b.urutan || 0));
};

// Permission checks
const canSetup = computed(() => {
    return permissionService.hasPermission('Pemeriksaan Khusus Setup');
});

const canInputHasilTes = computed(() => {
    return permissionService.hasPermission('Pemeriksaan Khusus Input Hasil Tes');
});

// Get peserta count by type
const pesertaCount = computed(() => {
    const peserta = props.item?.pemeriksaan_khusus_peserta || [];
    let atlet = 0;
    let pelatih = 0;
    let tenagaPendukung = 0;

    peserta.forEach((p: any) => {
        const type = p.peserta_type;
        if (type === 'App\\Models\\Atlet') atlet++;
        else if (type === 'App\\Models\\Pelatih') pelatih++;
        else if (type === 'App\\Models\\TenagaPendukung') tenagaPendukung++;
    });

    return { atlet, pelatih, tenagaPendukung, total: peserta.length };
});

// Export PDF function
const exportToPDF = () => {
    if (!selectedPeserta.value) {
        toast({
            title: 'Pilih peserta terlebih dahulu',
            variant: 'destructive',
        });
        return;
    }

    try {
        const doc = new jsPDF('p', 'mm', 'a4');
        const peserta = selectedPeserta.value;
        const pageWidth = doc.internal.pageSize.getWidth();
        const margin = 15;
        let yPos = margin;

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
        doc.text('Laporan Hasil Pemeriksaan Khusus', pageWidth / 2, yPos, { align: 'center' });
        yPos += 10;

        doc.setFontSize(14);
        doc.setFont('helvetica', 'normal');
        doc.text(props.item.nama_pemeriksaan || 'Pemeriksaan Khusus', pageWidth / 2, yPos, { align: 'center' });
        yPos += 15;

        // Informasi Atlet
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text('Informasi Atlet', margin, yPos);
        yPos += 8;

        doc.setFontSize(11);
        doc.setFont('helvetica', 'normal');
        const infoData = [
            ['Nama', peserta.peserta.nama || '-'],
            ['Posisi', peserta.peserta.posisi || '-'],
            ['Umur', peserta.peserta.umur !== '-' ? `${peserta.peserta.umur} tahun` : '-'],
            [
                'Jenis Kelamin',
                peserta.peserta.jenis_kelamin === 'L' || peserta.peserta.jenis_kelamin === 'Laki-laki'
                    ? 'Laki-laki'
                    : peserta.peserta.jenis_kelamin === 'P' || peserta.peserta.jenis_kelamin === 'Perempuan'
                      ? 'Perempuan'
                      : '-',
            ],
            ['Cabor', peserta.peserta.cabor || '-'],
        ];

        autoTable(doc, {
            startY: yPos,
            head: [],
            body: infoData,
            theme: 'plain',
            styles: { fontSize: 10 },
            columnStyles: {
                0: { fontStyle: 'bold', cellWidth: 50 },
                1: { cellWidth: 120 },
            },
            margin: { left: margin, right: margin },
        });

        yPos = (doc as any).lastAutoTable.finalY + 10;
        checkNewPage(20);

        // Detail Nilai Item Tes
        if (peserta.item_tes && peserta.item_tes.length > 0) {
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('Detail Nilai Item Tes', margin, yPos);
            yPos += 8;

            // Group item tes by aspek
            const itemTesByAspek: Record<string, any[]> = {};
            aspekList.value.forEach((aspek) => {
                const items = getItemTesByAspek(peserta.item_tes, aspek.id);
                if (items.length > 0) {
                    itemTesByAspek[aspek.nama] = items;
                }
            });

            Object.keys(itemTesByAspek).forEach((aspekNama) => {
                checkNewPage(30);
                doc.setFontSize(12);
                doc.setFont('helvetica', 'bold');
                doc.text(aspekNama, margin, yPos);
                yPos += 6;

                const items = itemTesByAspek[aspekNama];
                const tableData = items.map((item) => [
                    item.nama || '-',
                    item.satuan || '-',
                    item.target || '-',
                    item.nilai || '-',
                    formatPersentase(item.persentase_performa),
                    getPredikatLabel(item.predikat),
                ]);

                autoTable(doc, {
                    startY: yPos,
                    head: [['Item Tes', 'Satuan', 'Target', 'Nilai', 'Persentase', 'Predikat']],
                    body: tableData,
                    theme: 'striped',
                    styles: { fontSize: 9 },
                    headStyles: { fontStyle: 'bold' },
                    didParseCell: (data: any) => {
                        // Add color to predikat column (index 5) - only for body rows, not header
                        if (data.column.index === 5 && data.row.index >= 0 && data.section !== 'head') {
                            const item = items[data.row.index];
                            const color = getPredikatColorRGB(item.predikat);
                            data.cell.styles.fillColor = color;
                            data.cell.styles.textColor = [255, 255, 255];
                        }
                    },
                    margin: { left: margin, right: margin },
                });

                yPos = (doc as any).lastAutoTable.finalY + 8;
            });
        }

        checkNewPage(30);

        // Detail Nilai Per Aspek
        if (peserta.aspek && peserta.aspek.length > 0) {
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('Detail Nilai Per Aspek', margin, yPos);
            yPos += 8;

            const aspekData = peserta.aspek.map((a: any) => [
                a.nama || '-',
                formatPersentase(a.nilai_performa),
                getPredikatLabel(a.predikat),
            ]);

            autoTable(doc, {
                startY: yPos,
                head: [['Aspek', 'Nilai Performa', 'Predikat']],
                body: aspekData,
                theme: 'striped',
                styles: { fontSize: 10 },
                headStyles: { fontStyle: 'bold' },
                didParseCell: (data: any) => {
                    // Add color to predikat column (index 2) - only for body rows, not header
                    if (data.column.index === 2 && data.row.index >= 0 && data.section !== 'head') {
                        const aspek = peserta.aspek[data.row.index];
                        const color = getPredikatColorRGB(aspek.predikat);
                        data.cell.styles.fillColor = color;
                        data.cell.styles.textColor = [255, 255, 255];
                    }
                },
                margin: { left: margin, right: margin },
            });

            yPos = (doc as any).lastAutoTable.finalY + 10;
        }

        checkNewPage(80);

        // Performa Aspek - Radar Chart
        if (peserta.aspek && peserta.aspek.length > 0) {
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('Performa Aspek (Radar Chart)', margin, yPos);
            yPos += 10;

            // Draw radar chart
            const chartCenterX = pageWidth / 2;
            const chartCenterY = yPos + 50;
            const chartRadius = 40;
            const numAspek = peserta.aspek.length;

            // Draw grid circles (0%, 25%, 50%, 75%, 100%)
            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(0.1);
            for (let i = 1; i <= 5; i++) {
                const radius = (chartRadius * i) / 5;
                doc.circle(chartCenterX, chartCenterY, radius, 'S');
            }

            // Draw grid lines (spokes)
            for (let i = 0; i < numAspek; i++) {
                const angle = (i * 2 * Math.PI) / numAspek - Math.PI / 2;
                const x1 = chartCenterX + chartRadius * Math.cos(angle);
                const y1 = chartCenterY + chartRadius * Math.sin(angle);
                doc.line(chartCenterX, chartCenterY, x1, y1);
            }

            // Draw data polygon
            const points: number[][] = [];
            peserta.aspek.forEach((a: any, index: number) => {
                const angle = (index * 2 * Math.PI) / numAspek - Math.PI / 2;
                const nilai = toNumber(a.nilai_performa) || 0;
                const radius = (chartRadius * nilai) / 100;
                const x = chartCenterX + radius * Math.cos(angle);
                const y = chartCenterY + radius * Math.sin(angle);
                points.push([x, y]);
            });

            // Draw polygon outline and fill effect
            if (points.length > 0) {
                // Draw filled polygon by drawing triangles from center
                doc.setFillColor(59, 130, 246);
                doc.setDrawColor(59, 130, 246);
                
                // Draw filled area using lines from center (creates visual fill effect)
                doc.setLineWidth(0.3);
                for (let i = 0; i < points.length; i++) {
                    const nextIndex = (i + 1) % points.length;
                    const p1 = points[i];
                    const p2 = points[nextIndex];
                    
                    // Draw lines to create filled triangle effect
                    // Draw line from center to point 1
                    doc.line(chartCenterX, chartCenterY, p1[0], p1[1]);
                    // Draw line from center to point 2
                    doc.line(chartCenterX, chartCenterY, p2[0], p2[1]);
                    // Draw line connecting the two points
                    doc.line(p1[0], p1[1], p2[0], p2[1]);
                }
                
                // Draw polygon outline (thicker lines)
                doc.setDrawColor(59, 130, 246);
                doc.setLineWidth(2);
                for (let i = 0; i < points.length; i++) {
                    const nextIndex = (i + 1) % points.length;
                    doc.line(points[i][0], points[i][1], points[nextIndex][0], points[nextIndex][1]);
                }
            }

            // Draw data points
            doc.setFillColor(59, 130, 246);
            points.forEach((point) => {
                doc.circle(point[0], point[1], 2, 'F');
            });

            // Draw labels with clearer positioning
            doc.setFontSize(9);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(0, 0, 0);
            peserta.aspek.forEach((a: any, index: number) => {
                const angle = (index * 2 * Math.PI) / numAspek - Math.PI / 2;
                const labelRadius = chartRadius + 18;
                const x = chartCenterX + labelRadius * Math.cos(angle);
                const y = chartCenterY + labelRadius * Math.sin(angle);
                const nilai = toNumber(a.nilai_performa) || 0;
                const nilaiText = formatPersentase(a.nilai_performa);
                
                // Draw aspek name (bold)
                doc.text(a.nama || '-', x, y - 5, { align: 'center' });
                // Draw nilai below (smaller, but clear)
                doc.setFontSize(8);
                doc.setFont('helvetica', 'normal');
                doc.text(nilaiText, x, y + 2, { align: 'center' });
                doc.setFontSize(9);
                doc.setFont('helvetica', 'bold');
            });

            // Draw scale labels more clearly - 0% at center, 100% at outer edge
            doc.setFontSize(8);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(100, 100, 100);
            // 0% at center (tengah chart)
            doc.text('0%', chartCenterX, chartCenterY, { align: 'center' });
            // 25% at first circle
            doc.text('20%', chartCenterX, chartCenterY - (chartRadius * 0.20) , { align: 'center' });
            // 50% at second circle
            doc.text('40%', chartCenterX, chartCenterY - (chartRadius * 0.40) , { align: 'center' });
            // 75% at third circle
            doc.text('60%', chartCenterX, chartCenterY - (chartRadius * 0.60) , { align: 'center' });
            // 100% at outer edge (outermost circle)
            doc.text('80%', chartCenterX, chartCenterY - (chartRadius * 0.80) , { align: 'center' });
            doc.text('100%', chartCenterX, chartCenterY - (chartRadius * 1) , { align: 'center' });
            doc.setTextColor(0, 0, 0);

            yPos = chartCenterY + chartRadius + 25;
            
            // Add table with detailed data below chart for clarity
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text('Detail Data Performa Aspek:', margin, yPos);
            yPos += 6;
            
            const chartTableData = peserta.aspek.map((a: any) => [
                a.nama || '-',
                formatPersentase(a.nilai_performa),
                getPredikatLabel(a.predikat),
            ]);
            
            autoTable(doc, {
                startY: yPos,
                head: [['Aspek', 'Nilai Performa', 'Predikat']],
                body: chartTableData,
                theme: 'striped',
                styles: { fontSize: 9 },
                headStyles: { fontStyle: 'bold' },
                didParseCell: (data: any) => {
                    // Add color to predikat column (index 2) - only for body rows, not header
                    if (data.column.index === 2 && data.row.index >= 0 && data.section !== 'head') {
                        const aspek = peserta.aspek[data.row.index];
                        const color = getPredikatColorRGB(aspek.predikat);
                        data.cell.styles.fillColor = color;
                        data.cell.styles.textColor = [255, 255, 255];
                    }
                },
                margin: { left: margin, right: margin },
            });
            
            yPos = (doc as any).lastAutoTable.finalY + 10;
        }

        checkNewPage(30);

        // Nilai Keseluruhan dan Predikat
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text('Nilai Keseluruhan', margin, yPos);
        yPos += 8;

        doc.setFontSize(11);
        doc.setFont('helvetica', 'normal');
        const nilaiKeseluruhan = formatPersentase(peserta.nilai_keseluruhan);
        const predikatKeseluruhan = getPredikatLabel(peserta.predikat_keseluruhan);

        const predikatColor = getPredikatColorRGB(peserta.predikat_keseluruhan);
        autoTable(doc, {
            startY: yPos,
            head: [],
            body: [
                ['Nilai Keseluruhan', nilaiKeseluruhan],
                ['Predikat', predikatKeseluruhan],
            ],
            theme: 'plain',
            styles: { fontSize: 11 },
            columnStyles: {
                0: { fontStyle: 'bold', cellWidth: 60 },
                1: { cellWidth: 110, fontSize: 12, fontStyle: 'bold' },
            },
            didParseCell: (data: any) => {
                // Add color to predikat row, second column
                if (data.row.index === 1 && data.column.index === 1) {
                    data.cell.styles.fillColor = predikatColor;
                    data.cell.styles.textColor = [255, 255, 255];
                }
            },
            margin: { left: margin, right: margin },
        });

        // Footer
        const totalPages = doc.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            doc.setPage(i);
            doc.setFontSize(8);
            doc.setFont('helvetica', 'italic');
            doc.text(
                `Halaman ${i} dari ${totalPages}`,
                pageWidth / 2,
                doc.internal.pageSize.getHeight() - 10,
                { align: 'center' },
            );
            doc.text(
                `Dicetak pada: ${new Date().toLocaleString('id-ID')}`,
                pageWidth - margin,
                doc.internal.pageSize.getHeight() - 10,
                { align: 'right' },
            );
        }

        // Save PDF
        const fileName = `Laporan_Pemeriksaan_${peserta.peserta.nama?.replace(/\s+/g, '_') || 'Peserta'}_${new Date().getTime()}.pdf`;
        doc.save(fileName);

        toast({
            title: 'PDF berhasil diunduh',
            variant: 'success',
        });
    } catch (error: any) {
        console.error('Error exporting PDF:', error);
        toast({
            title: 'Gagal mengekspor PDF',
            description: error.message || 'Terjadi kesalahan saat mengekspor PDF',
            variant: 'destructive',
        });
    }
};

onMounted(() => {
    if (activeTab.value === 'visualisasi-data') {
        loadVisualisasi();
    }
});
</script>

<template>
    <PageShow
        :title="dynamicTitle"
        :breadcrumbs="breadcrumbs"
        :fields="activeTab === 'informasi-data' ? fields : []"
        :action-fields="actionFields"
        :back-url="'/pemeriksaan-khusus'"
        :on-edit="() => router.visit(`/pemeriksaan-khusus/${props.item.id}/edit`)"
        :on-delete="activeTab === 'informasi-data' ? handleDelete : undefined"
    >
        <template #tabs>
            <AppTabs :tabs="tabsConfig" :default-value="'informasi-data'" v-model="activeTab" />
        </template>

        <template #custom-action>
            <div v-if="activeTab === 'visualisasi-data'" class="flex gap-2">
                <Button
                    v-if="selectedPeserta && !loadingVisualisasi"
                    variant="default"
                    @click="exportToPDF"
                >
                    <Download class="h-4 w-4 mr-2" />
                    Export PDF
                </Button>
                <Button
                    v-if="canInputHasilTes"
                    variant="outline"
                    @click="() => router.visit(`/pemeriksaan-khusus/${props.item.id}/input-hasil-tes`)"
                >
                    <Activity class="h-4 w-4 mr-2" />
                    Input Hasil Tes
                </Button>
            </div>
        </template>

        <template #custom>
            <!-- Tab Informasi -->
            <div v-if="activeTab === 'informasi-data'" class="space-y-6">
                <!-- Informasi Peserta -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Informasi Peserta
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">Total Peserta</div>
                                <div class="text-2xl font-bold">{{ pesertaCount.total }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">Atlet</div>
                                <div class="text-2xl font-bold text-blue-600">{{ pesertaCount.atlet }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">Pelatih</div>
                                <div class="text-2xl font-bold text-green-600">{{ pesertaCount.pelatih }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">Tenaga Pendukung</div>
                                <div class="text-2xl font-bold text-yellow-600">{{ pesertaCount.tenagaPendukung }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Informasi Aspek & Item Tes -->
                <Card v-if="props.item.aspek && props.item.aspek.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Info class="h-5 w-5" />
                            Aspek & Item Tes
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-6">
                            <div
                                v-for="(aspek, aspekIdx) in props.item.aspek"
                                :key="aspek.id"
                                class="space-y-3"
                                :class="{ 'border-t pt-6': aspekIdx > 0 }"
                            >
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold">{{ aspek.nama }}</h3>
                                    <Badge variant="outline">Urutan: {{ aspek.urutan }}</Badge>
                                </div>
                                <div v-if="aspek.item_tes && aspek.item_tes.length > 0" class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b">
                                                <th class="px-4 py-2 text-left">Item Tes</th>
                                                <th class="px-4 py-2 text-left">Satuan</th>
                                                <th class="px-4 py-2 text-center">Target Laki-laki</th>
                                                <th class="px-4 py-2 text-center">Target Perempuan</th>
                                                <th class="px-4 py-2 text-center">Performa Arah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="itemTes in aspek.item_tes"
                                                :key="itemTes.id"
                                                class="border-b hover:bg-muted/50"
                                            >
                                                <td class="px-4 py-2 font-medium">{{ itemTes.nama }}</td>
                                                <td class="px-4 py-2">{{ itemTes.satuan || '-' }}</td>
                                                <td class="px-4 py-2 text-center">{{ itemTes.target_laki_laki || '-' }}</td>
                                                <td class="px-4 py-2 text-center">{{ itemTes.target_perempuan || '-' }}</td>
                                                <td class="px-4 py-2 text-center">
                                                    <Badge
                                                        :class="
                                                            itemTes.performa_arah === 'max'
                                                                ? 'bg-green-500 text-white'
                                                                : 'bg-red-500 text-white'
                                                        "
                                                    >
                                                        {{ itemTes.performa_arah === 'max' ? 'Maksimal' : 'Minimal' }}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="text-sm text-muted-foreground italic">Belum ada item tes</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card v-else>
                    <CardContent class="py-8 text-center text-muted-foreground">
                        <Info class="h-12 w-12 mx-auto mb-4 opacity-50" />
                        <p>Belum ada aspek yang ditambahkan</p>
                        <Button
                            v-if="canSetup"
                            class="mt-4"
                            variant="outline"
                            @click="() => router.visit(`/pemeriksaan-khusus/${props.item.id}/setup`)"
                        >
                            Setup Aspek & Item Tes
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Tab Visualisasi -->
            <div v-if="activeTab === 'visualisasi-data'" class="space-y-6">
                <!-- Loading State -->
                <div v-if="loadingVisualisasi" class="flex items-center justify-center py-12">
                    <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
                    <span class="ml-2 text-muted-foreground">Memuat data visualisasi...</span>
                </div>

                <!-- Empty State -->
                <div v-else-if="visualisasiData.length === 0" class="text-center py-12">
                    <BarChart3 class="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
                    <p class="text-muted-foreground">Belum ada data visualisasi</p>
                    <Button
                        v-if="canInputHasilTes"
                        class="mt-4"
                        @click="() => router.visit(`/pemeriksaan-khusus/${props.item.id}/input-hasil-tes`)"
                    >
                        Input Hasil Tes
                    </Button>
                </div>

                <!-- Visualisasi Content -->
                <div v-else class="space-y-6">
                    <!-- Peserta Selector -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Pilih Peserta</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
                                <button
                                    v-for="peserta in visualisasiData"
                                    :key="peserta.peserta_id"
                                    :class="[
                                        'p-3 rounded-lg border text-left transition-all',
                                        selectedPeserta?.peserta_id === peserta.peserta_id
                                            ? 'border-primary bg-primary/10'
                                            : 'border-border hover:bg-muted',
                                    ]"
                                    @click="selectedPeserta = peserta"
                                >
                                    <div class="font-medium">{{ peserta.peserta.nama }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{
                                            peserta.peserta.jenis_kelamin === 'L' || peserta.peserta.jenis_kelamin === 'Laki-laki'
                                                ? 'Laki-laki'
                                                : 'Perempuan'
                                        }}
                                    </div>
                                </button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Charts untuk Peserta Terpilih -->
                    <div v-if="selectedPeserta && aspekList.length > 0" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Radar Chart - Aspek -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Performa Aspek</CardTitle>
                                <CardDescription>Radar chart untuk menampilkan performa setiap aspek</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ApexChart
                                    v-if="radarChartSeries.length > 0"
                                    :options="radarChartOptions"
                                    :series="radarChartSeries"
                                />
                                <div v-else class="flex items-center justify-center py-12 text-muted-foreground">
                                    Belum ada data aspek
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Gauge Chart - Nilai Keseluruhan -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Nilai Keseluruhan</CardTitle>
                                <CardDescription>Gauge chart untuk menampilkan nilai keseluruhan</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <ApexChart :options="gaugeChartOptions" :series="gaugeChartSeries" />
                                    <div class="text-center">
                                        <Badge :class="`${getPredikatColor(selectedPeserta.predikat_keseluruhan)} text-sm`">
                                            {{ getPredikatLabel(selectedPeserta.predikat_keseluruhan) }}
                                        </Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Detail Table untuk Peserta Terpilih -->
                    <div v-if="selectedPeserta" class="space-y-6">
                        <!-- Tabel Detail Per Aspek -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Detail Per Aspek</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b">
                                                <th class="px-4 py-2 text-left">Aspek</th>
                                                <th class="px-4 py-2 text-right">Nilai Performa</th>
                                                <th class="px-4 py-2 text-center">Predikat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="hasilAspek in selectedPeserta.aspek"
                                                :key="hasilAspek.aspek_id"
                                                class="border-b hover:bg-muted/50"
                                            >
                                                <td class="px-4 py-2 font-medium">{{ hasilAspek.nama }}</td>
                                                <td class="px-4 py-2 text-right">{{ formatPersentase(hasilAspek.nilai_performa) }}</td>
                                                <td class="px-4 py-2 text-center">
                                                    <Badge :class="getPredikatColor(hasilAspek.predikat)" class="text-xs">
                                                        {{ getPredikatLabel(hasilAspek.predikat) }}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Tabel Detail Item Tes -->
                        <Card v-if="selectedPeserta.item_tes && selectedPeserta.item_tes.length > 0">
                            <CardHeader>
                                <CardTitle>Detail Item Tes</CardTitle>
                                <CardDescription>
                                    Detail hasil tes per item dengan target dan predikat
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-6">
                                    <div
                                        v-for="aspek in aspekList"
                                        :key="aspek.id"
                                        v-show="getItemTesByAspek(selectedPeserta.item_tes, aspek.id).length > 0"
                                        class="space-y-3"
                                    >
                                        <h3 class="text-lg font-semibold">{{ aspek.nama }}</h3>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="border-b">
                                                        <th class="px-4 py-2 text-left">Item Tes</th>
                                                        <th class="px-4 py-2 text-left">Satuan</th>
                                                        <th class="px-4 py-2 text-center">Target</th>
                                                        <th class="px-4 py-2 text-center">Nilai</th>
                                                        <th class="px-4 py-2 text-right">Persentase</th>
                                                        <th class="px-4 py-2 text-center">Predikat</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="itemTes in getItemTesByAspek(selectedPeserta.item_tes, aspek.id)"
                                                        :key="itemTes.item_tes_id"
                                                        class="border-b hover:bg-muted/50"
                                                    >
                                                        <td class="px-4 py-2 font-medium">{{ itemTes.nama }}</td>
                                                        <td class="px-4 py-2">{{ itemTes.satuan || '-' }}</td>
                                                        <td class="px-4 py-2 text-center">{{ itemTes.target || '-' }}</td>
                                                        <td class="px-4 py-2 text-center">{{ itemTes.nilai || '-' }}</td>
                                                        <td class="px-4 py-2 text-right">{{ formatPersentase(itemTes.persentase_performa) }}</td>
                                                        <td class="px-4 py-2 text-center">
                                                            <Badge :class="getPredikatColor(itemTes.predikat)" class="text-xs">
                                                                {{ getPredikatLabel(itemTes.predikat) }}
                                                            </Badge>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </template>
    </PageShow>
</template>
