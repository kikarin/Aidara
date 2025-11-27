<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Tabs from '@/components/ui/tabs/Tabs.vue';
import TabsContent from '@/components/ui/tabs/TabsContent.vue';
import TabsList from '@/components/ui/tabs/TabsList.vue';

import ApexChart from '@/components/ApexChart.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ClipboardCheck, Flag, HandHeart, HeartHandshake, Stethoscope, Ungroup, UserCircle2 } from 'lucide-vue-next';

const props = defineProps<{
    stats?: any[];
    latest_programs?: any[];
    latest_pemeriksaan?: any[];
    latest_activities?: any[];
    chart_data?: {
        years: number[];
        series: Array<{
            name: string;
            data: number[];
        }>;
    };
    rekap_data?: Array<{
        id: number;
        cabor_nama: string;
        nama: string;
        jumlah_atlet: number;
        jumlah_pelatih: number;
        jumlah_tenaga_pendukung: number;
        total: number;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const stats = props.stats || [];

const iconMap: any = {
    UserCircle2,
    HandHeart,
    HeartHandshake,
    Flag,
    Ungroup,
    ClipboardCheck,
    Stethoscope,
};

// Chart data dan options
const chartData = props.chart_data || {
    years: [],
    series: [],
};

// Rekap data
const rekapData = props.rekap_data || [];

const chartOptions = {
    chart: {
        type: 'bar',
        height: 350,
        toolbar: {
            show: true,
            tools: {
                download: true,
                selection: false,
                zoom: false,
                zoomin: false,
                zoomout: false,
                pan: false,
                reset: false,
            },
            export: {
                csv: {
                    filename: 'statistik-bergabung-per-tahun',
                    columnDelimiter: ',',
                    headerCategory: 'Tahun',
                    headerValue: 'Jumlah',
                },
                png: {
                    filename: 'statistik-bergabung-per-tahun',
                },
                svg: {
                    filename: 'statistik-bergabung-per-tahun',
                },
                pdf: {
                    filename: 'statistik-bergabung-per-tahun',
                },
            },
        },
        background: 'transparent',
        foreColor: 'hsl(var(--foreground))',
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            borderRadius: 5,
            borderRadiusApplication: 'end',
        },
    },
    dataLabels: {
        enabled: false,
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent'],
    },
    xaxis: {
        categories: chartData.years.map((year) => year.toString()),
        labels: {
            style: {
                colors: 'hsl(var(--muted-foreground))',
            },
        },
        axisBorder: {
            color: 'hsl(var(--border))',
        },
        axisTicks: {
            color: 'hsl(var(--border))',
        },
    },
    yaxis: {
        title: {
            text: 'Jumlah',
            style: {
                color: 'hsl(var(--foreground))',
            },
        },
        labels: {
            style: {
                colors: 'hsl(var(--muted-foreground))',
            },
        },
    },
    fill: {
        opacity: 1,
    },
    tooltip: {
        theme: 'dark',
        style: {
            fontSize: '12px',
        },
        y: {
            formatter: function (val: number) {
                return val + ' orang';
            },
        },
    },
    colors: ['#3B82F6', '#10B981', '#F59E0B'],
    legend: {
        position: 'top',
        horizontalAlign: 'center',
        labels: {
            colors: 'hsl(var(--foreground))',
        },
    },
    grid: {
        borderColor: 'hsl(var(--border))',
        xaxis: {
            lines: {
                show: true,
                color: 'hsl(var(--border))',
            },
        },
        yaxis: {
            lines: {
                show: true,
                color: 'hsl(var(--border))',
            },
        },
    },
};

// const showMoreActions = ref(false);

// const defaultActions = [
//     {
//         title: 'Tambah Atlet',
//         icon: UserCircle2,
//         href: '/atlet/create',
//     },
//     {
//         title: 'Tambah Pelatih',
//         icon: HandHeart,
//         href: '/pelatih/create',
//     },
//     {
//         title: 'Tambah Tenaga Pendukung',
//         icon: HeartHandshake,
//         href: '/tenaga-pendukung/create',
//     },
// ];

// const additionalActions = [
//     {
//         title: 'Buat Program Latihan',
//         icon: ClipboardCheck,
//         href: '/program-latihan/create',
//     },
//     {
//         title: 'Buat Pemeriksaan',
//         icon: Stethoscope,
//         href: '/pemeriksaan/create',
//     },
//     {
//         title: 'Tambah Cabor',
//         icon: Flag,
//         href: '/cabor/create',
//     },
//     {
//         title: 'Tambah Kategori Cabor',
//         icon: Ungroup,
//         href: '/cabor-kategori/create',
//     },
// ];

// const quickActions = computed(() => {
//     return showMoreActions.value
//         ? [...defaultActions, ...additionalActions]
//         : defaultActions;
// });
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-4">
                <Card
                    v-for="stat in stats"
                    :key="stat.title"
                    class="cursor-pointer overflow-hidden transition-shadow hover:shadow-md"
                    @click="router.visit(stat.href)"
                >
                    <CardHeader class="flex items-center justify-between space-y-0">
                        <div class="flex flex-col gap-3">
                            <CardTitle class="text-sm font-medium">
                                {{ stat.title }}
                            </CardTitle>
                            <div class="text-2xl font-bold">
                                {{ stat.value }}
                            </div>
                        </div>

                        <div :class="[stat.bgColor, 'rounded-full p-3']">
                            <component :is="iconMap[stat.icon]" :class="stat.color" class="h-10 w-10" />
                        </div>
                    </CardHeader>
                </Card>
            </div>

            <!-- Main Content -->
            <!-- <div class="grid grid-cols-5 gap-6"> -->
            <!-- Recent Activities -->
            <!-- <Card class="col-span-3">
                    <CardHeader>
                        <CardTitle>Aktivitas Terbaru</CardTitle>
                        <CardDescription>Update terbaru dari sistem</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-4">
                            <div v-for="activity in recentActivities" :key="activity.id" class="flex items-start space-x-3 p-3 border rounded-lg hover:bg-muted/50 transition-colors">
                                <Avatar class="h-8 w-8 flex-shrink-0">
                                    <AvatarImage :src="activity.avatar" :alt="activity.initials" />
                                    <AvatarFallback class="text-xs">{{ activity.initials }}</AvatarFallback>
                                </Avatar>
                                <div class="flex-1 space-y-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ activity.title }}</p>
                                    <p class="text-muted-foreground text-xs truncate">{{ activity.causer_name }}</p>
                                    <p class="text-muted-foreground text-xs">{{ activity.time }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button variant="outline" class="w-full" @click="router.visit('/menu-permissions/logs')"> Lihat Semua Aktivitas </Button>
                    </CardFooter>
                </Card> -->

            <!-- Quick Actions -->
            <!-- <Card class="col-span-2">
                    <CardHeader>
                        <CardTitle>Aksi Cepat</CardTitle>
                        <CardDescription>Tugas umum dan pintasan</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col gap-4">
                            <Button v-for="action in quickActions" :key="action.title" variant="outline" class="gap-3 justify-start" @click="router.visit(action.href)">
                                <component :is="action.icon" class="h-4 w-4" />
                                <span class="text-sm">{{ action.title }}</span>
                            </Button>
                            <Button variant="outline" class="gap-3 justify-start" @click="showMoreActions = !showMoreActions">
                                <Plus class="h-4 w-4" />
                                <span class="text-sm">{{ showMoreActions ? 'Sembunyikan' : 'Aksi Lainnya' }}</span>
                            </Button>
                        </div>
                    </CardContent>
                </Card> -->
            <!-- </div> -->

            <!-- Chart Section dengan Tabs -->
            <Card>
                <CardHeader>
                    <CardTitle>Grafik Peserta Tahunan</CardTitle>
                    <p class="text-muted-foreground text-sm">Berdasarkan tanggal bergabung Peserta</p>
                </CardHeader>
                <CardContent>
                    <Tabs default-value="chart" class="w-full">
                        <TabsList class="grid w-full grid-cols-1"> </TabsList>
                        <TabsContent value="chart">
                            <div class="space-y-1">
                                <div class="rounded-lg border p-4">
                                    <ApexChart :options="chartOptions" :series="chartData.series" />
                                </div>
                            </div>
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>

            <!-- Rekap Section -->
            <Card>
                <CardHeader>
                    <CardTitle>Rekapitulasi Jumlah Peserta berdasarkan Cabor Kategori</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div class="overflow-hidden rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-[200px]">Cabor</TableHead>
                                        <TableHead class="w-[200px]">Kategori</TableHead>
                                        <TableHead class="w-[100px] text-center">Atlet</TableHead>
                                        <TableHead class="w-[100px] text-center">Pelatih</TableHead>
                                        <TableHead class="w-[150px] text-center">Tenaga Pendukung</TableHead>
                                        <TableHead class="w-[100px] text-center">Total</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="item in rekapData" :key="item.id" class="hover:bg-muted/50">
                                        <TableCell class="font-medium">{{ item.cabor_nama }}</TableCell>
                                        <TableCell>{{ item.nama }}</TableCell>
                                        <TableCell class="text-center">
                                            <span class="font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-100">
                                                {{ item.jumlah_atlet }}
                                            </span>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span
                                                class="font-semibold text-green-700 hover:text-green-800 dark:text-green-300 dark:hover:text-green-100"
                                            >
                                                {{ item.jumlah_pelatih }}
                                            </span>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span
                                                class="font-semibold text-yellow-400 hover:text-yellow-500 dark:text-yellow-300 dark:hover:text-yellow-100"
                                            >
                                                {{ item.jumlah_tenaga_pendukung }}
                                            </span>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span class="font-semibold text-gray-800 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100">
                                                {{ item.total }}
                                            </span>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="rekapData.length === 0">
                                        <TableCell colspan="6" class="text-muted-foreground py-8 text-center">
                                            Tidak ada data rekapitulasi
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Dua Section dalam Grid 2 Kolom -->
            <!-- <div class="grid grid-cols-1 md:grid-cols-2 gap-6"> -->
            <!-- Section Program Latihan -->
            <!-- <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Program Latihan Terbaru</h3>
                    <div class="space-y-3">
                        <div v-for="row in props.latest_programs" :key="row.id"
                            class="p-4 border rounded-lg hover:bg-muted/50 transition-colors">
                            <div class="flex flex-col gap-2">
                                <div class="flex flex-wrap gap-2">
                                    <span class="font-medium min-w-[125px] text-sm">Nama</span>
                                    <span class="text-foreground text-sm">{{ row.nama_program }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="font-medium min-w-[125px] text-sm">Cabor</span>
                                    <span class="text-foreground text-sm">{{ row.cabor_nama }} - {{
                                        row.cabor_kategori_nama }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="font-medium min-w-[125px] text-sm">Periode</span>
                                    <span class="text-foreground text-sm">{{ row.periode }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2 items-center">
                                    <span class="font-medium min-w-[125px] text-sm">Rencana Latihan</span>
                                    <div class="flex flex-wrap gap-2 items-center text-sm">
                                        <Badge variant="secondary" class="text-foreground ">
                                            {{ row.jumlah_rencana_latihan }}
                                        </Badge>
                                        <span v-if="row.rencana_latihan_list && row.rencana_latihan_list.length > 0"
                                            class="text-muted-foreground">
                                            {{ row.rencana_latihan_list.join(', ') }}
                                        </span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->

            <!-- Section Pemeriksaan -->
            <!-- <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Pemeriksaan Terbaru</h3>
                    <div class="space-y-3">
                        <div v-for="row in props.latest_pemeriksaan" :key="row.id"
                            class="p-4 border rounded-lg hover:bg-muted/50 transition-colors">
                            <div class="flex flex-col gap-2">
                                <div class="flex flex-wrap gap-2">
                                    <span class="font-medium min-w-[145px] text-sm">Nama</span>
                                    <span class="text-foreground text-sm">{{ row.nama_pemeriksaan }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="font-medium min-w-[145px] text-sm">Cabor</span>
                                    <span class="text-foreground text-sm">{{ row.cabor_nama }} - {{
                                        row.cabor_kategori_nama }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="font-medium min-w-[145px] text-sm">Tenaga Pendukung</span>
                                    <span class="text-foreground text-sm">{{ row.tenaga_pendukung_nama }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="font-medium min-w-[145px] text-sm">Tanggal</span>
                                    <span class="text-foreground text-sm">{{ row.tanggal_pemeriksaan }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2 items-center">
                                    <span class="font-medium min-w-[145px] text-sm">Status</span>
                                    <Badge
                                        :variant="row.status === 'selesai' ? 'success' : row.status === 'sebagian' ? 'warning' : 'destructive'">
                                        {{ row.status }}
                                    </Badge>
                                </div>
                                <div class="flex flex-wrap gap-2 items-center">
                                    <span class="font-medium min-w-[145px] text-sm">Parameter</span>
                                    <div class="flex flex-wrap gap-2 items-center text-sm">
                                        <Badge variant="secondary" class="text-foreground">
                                            {{ row.jumlah_parameter }}
                                        </Badge>
                                        <span v-if="row.parameter_list && row.parameter_list.length > 0"
                                            class="text-muted-foreground">
                                            {{ row.parameter_list.join(', ') }}
                                        </span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2 items-center">
                                    <span class="font-medium min-w-[145px] text-sm">Peserta</span>
                                    <BadgeGroup :badges="[
                                        {
                                            label: 'Atlet',
                                            value: row.jumlah_atlet || 0,
                                            colorClass: 'bg-blue-100 text-blue-800 hover:bg-blue-200',
                                        },
                                        {
                                            label: 'Pelatih',
                                            value: row.jumlah_pelatih || 0,
                                            colorClass: 'bg-green-100 text-green-800 hover:bg-green-200',
                                        },
                                        {
                                            label: 'Tenaga Pendukung',
                                            value: row.jumlah_tenaga_pendukung || 0,
                                            colorClass: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
                                        },
                                    ]" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->
            <!-- </div> -->
        </div>
    </AppLayout>
</template>
