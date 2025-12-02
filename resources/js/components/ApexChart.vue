<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import '../../../resources/css/app.css';

// Extend Window interface for ApexCharts
declare global {
    interface Window {
        ApexCharts: any;
    }
}

const chartRef = ref<HTMLElement | null>(null);
let chart: any = null;

const props = defineProps<{
    options: any;
    series: any[];
}>();

const loadApexCharts = (): Promise<any> => {
    return new Promise((resolve, reject) => {
        // Check if ApexCharts is already loaded
        if (window.ApexCharts) {
            resolve(window.ApexCharts);
            return;
        }

        // Load ApexCharts from CDN
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
    if (chartRef.value) {
        try {
            const ApexCharts = await loadApexCharts();

            // Destroy existing chart if any
            if (chart) {
                chart.destroy();
            }

            // Wait for next tick to ensure DOM is ready
            await nextTick();

            // Detect current theme
            const isDark = document.documentElement.classList.contains('dark');

            // Update chart options based on theme
            const themeOptions = {
                ...props.options,
                chart: {
                    ...props.options.chart,
                    background: 'transparent',
                    foreColor: isDark ? '#ffffff' : '#000000',
                },
                tooltip: {
                    ...props.options.tooltip,
                    theme: isDark ? 'dark' : 'light',
                    style: {
                        fontSize: '12px',
                    },
                },
                xaxis: {
                    ...props.options.xaxis,
                    labels: {
                        ...props.options.xaxis?.labels,
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280',
                        },
                    },
                    axisBorder: {
                        color: isDark ? '#374151' : '#e5e7eb',
                    },
                    axisTicks: {
                        color: isDark ? '#374151' : '#e5e7eb',
                    },
                },
                yaxis: {
                    ...props.options.yaxis,
                    labels: {
                        ...props.options.yaxis?.labels,
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280',
                        },
                    },
                },
                grid: {
                    ...props.options.grid,
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                    xaxis: {
                        lines: {
                            show: true,
                            color: isDark ? '#374151' : '#e5e7eb',
                        },
                    },
                    yaxis: {
                        lines: {
                            show: true,
                            color: isDark ? '#374151' : '#e5e7eb',
                        },
                    },
                },
            };

            chart = new ApexCharts(chartRef.value, {
                ...themeOptions,
                series: props.series,
            });

            chart.render();
        } catch (error) {
            console.error('Error loading ApexCharts:', error);
            // Fallback: show a simple message
            if (chartRef.value) {
                chartRef.value.innerHTML = '<div class="p-4 text-center text-muted-foreground">Grafik sedang dimuat...</div>';
            }
        }
    }
};

onMounted(() => {
    renderChart();

    // Listen for theme changes
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                // Re-render chart when theme changes
                renderChart();
            }
        });
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
});

// Watch for changes in props and update chart
watch(
    [() => props.options, () => props.series],
    () => {
        if (chart && chart.updateOptions) {
            // Detect current theme
            const isDark = document.documentElement.classList.contains('dark');

            // Update chart options based on theme
            const themeOptions = {
                ...props.options,
                chart: {
                    ...props.options.chart,
                    background: 'transparent',
                    foreColor: isDark ? '#ffffff' : '#000000',
                },
                tooltip: {
                    ...props.options.tooltip,
                    theme: isDark ? 'dark' : 'light',
                },
                xaxis: {
                    ...props.options.xaxis,
                    labels: {
                        ...props.options.xaxis?.labels,
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280',
                        },
                    },
                    axisBorder: {
                        color: isDark ? '#374151' : '#e5e7eb',
                    },
                    axisTicks: {
                        color: isDark ? '#374151' : '#e5e7eb',
                    },
                },
                yaxis: {
                    ...props.options.yaxis,
                    labels: {
                        ...props.options.yaxis?.labels,
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280',
                        },
                    },
                },
                grid: {
                    ...props.options.grid,
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                    xaxis: {
                        lines: {
                            show: true,
                            color: isDark ? '#374151' : '#e5e7eb',
                        },
                    },
                    yaxis: {
                        lines: {
                            show: true,
                            color: isDark ? '#374151' : '#e5e7eb',
                        },
                    },
                },
            };

            chart.updateOptions({
                ...themeOptions,
                series: props.series,
            });
        }
    },
    { deep: true },
);

onUnmounted(() => {
    if (chart && chart.destroy) {
        chart.destroy();
    }
});
</script>

<template>
    <div ref="chartRef" class="apex-chart-container h-[350px] w-full"></div>
</template>
\
