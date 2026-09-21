<script setup lang="ts">
import { getChartBorderColor, getChartForeColor, getChartMutedColor, isDarkTheme } from '@/lib/theme';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import '../../../resources/css/app.css';

declare global {
    interface Window {
        ApexCharts: any;
    }
}

const chartRef = ref<HTMLElement | null>(null);
let chart: any = null;
let themeObserver: MutationObserver | null = null;

const props = defineProps<{
    options: any;
    series: any[];
}>();

const buildThemeOptions = (options: any) => {
    const isDark = isDarkTheme();
    const muted = getChartMutedColor();
    const border = getChartBorderColor();

    return {
        ...options,
        chart: {
            ...options.chart,
            background: 'transparent',
            foreColor: getChartForeColor(),
        },
        tooltip: {
            ...options.tooltip,
            theme: isDark ? 'dark' : 'light',
            style: {
                fontSize: '12px',
            },
        },
        xaxis: {
            ...options.xaxis,
            labels: {
                ...options.xaxis?.labels,
                style: {
                    colors: muted,
                },
            },
            axisBorder: {
                color: border,
            },
            axisTicks: {
                color: border,
            },
        },
        yaxis: {
            ...options.yaxis,
            labels: {
                ...options.yaxis?.labels,
                style: {
                    colors: muted,
                },
            },
        },
        grid: {
            ...options.grid,
            borderColor: border,
            xaxis: {
                lines: {
                    show: true,
                    color: border,
                },
            },
            yaxis: {
                lines: {
                    show: true,
                    color: border,
                },
            },
        },
    };
};

const loadApexCharts = (): Promise<any> => {
    return new Promise((resolve, reject) => {
        if (window.ApexCharts) {
            resolve(window.ApexCharts);
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js';
        script.onload = () => {
            if (window.ApexCharts) {
                resolve(window.ApexCharts);
            } else {
                reject(new Error('ApexCharts not loaded'));
            }
        };
        script.onerror = () => reject(new Error('Failed to load ApexCharts'));
        document.head.appendChild(script);
    });
};

const renderChart = async () => {
    if (!chartRef.value) {
        return;
    }

    try {
        const ApexCharts = await loadApexCharts();

        if (chart) {
            chart.destroy();
        }

        await nextTick();

        chart = new ApexCharts(chartRef.value, {
            ...buildThemeOptions(props.options),
            series: props.series,
        });

        chart.render();
    } catch (error) {
        console.error('Error loading ApexCharts:', error);
        if (chartRef.value) {
            chartRef.value.innerHTML = '<div class="p-4 text-center text-muted-foreground">Grafik sedang dimuat...</div>';
        }
    }
};

onMounted(() => {
    renderChart();

    themeObserver = new MutationObserver(() => {
        renderChart();
    });

    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class', 'data-theme'],
    });
});

watch(
    [() => props.options, () => props.series],
    () => {
        if (chart?.updateOptions) {
            chart.updateOptions({
                ...buildThemeOptions(props.options),
                series: props.series,
            });
        }
    },
    { deep: true },
);

onUnmounted(() => {
    themeObserver?.disconnect();
    chart?.destroy?.();
});
</script>

<template>
    <div ref="chartRef" class="apex-chart-container h-[350px] w-full"></div>
</template>
