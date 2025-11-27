<script setup lang="ts">
import AppTabs from '@/components/AppTabs.vue';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { computed } from 'vue';
import HeaderForm from './HeaderForm.vue';

const props = defineProps<{
    title: string;
    breadcrumbs: BreadcrumbItem[];
    backUrl?: string;
    tabsConfig?: {
        value: string;
        label: string;
        component: any;
        props?: Record<string, any>;
        disabled?: boolean;
    }[];
    activeTabValue?: string;
    useGrid?: boolean;
}>();

const emit = defineEmits(['cancel', 'update:activeTabValue']);

const handleTabChange = (value: string) => {
    emit('update:activeTabValue', value);
};

const useGrid = computed(() => props.useGrid !== true);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen space-y-4 bg-gray-100 p-4 dark:bg-neutral-950">
            <div v-if="useGrid" class="grid grid-cols-1 lg:grid-cols-12">
                <div class="col-span-1 lg:col-span-7 lg:col-start-1">
                    <!-- Navigasi Tabs di luar Card -->
                    <div v-if="tabsConfig && tabsConfig.length > 0" class="mb-4">
                        <AppTabs
                            :tabs="tabsConfig"
                            :model-value="activeTabValue"
                            @update:model-value="handleTabChange"
                            :default-value="tabsConfig[0]?.value"
                        />
                    </div>

                    <Card class="w-full">
                        <HeaderForm :title="props.title" :back-url="props.backUrl" />
                        <CardContent>
                            <template v-if="tabsConfig && tabsConfig.length">
                                <component
                                    :is="tabsConfig.find((tab) => tab.value === activeTabValue)?.component"
                                    v-bind="tabsConfig.find((tab) => tab.value === activeTabValue)?.props"
                                />
                            </template>
                            <template v-else>
                                <slot />
                            </template>
                        </CardContent>
                    </Card>
                </div>
            </div>
            <div v-else>
                <!-- Navigasi Tabs di luar Card -->
                <div v-if="tabsConfig && tabsConfig.length > 0" class="mb-4">
                    <AppTabs
                        :tabs="tabsConfig"
                        :model-value="activeTabValue"
                        @update:model-value="handleTabChange"
                        :default-value="tabsConfig[0]?.value"
                    />
                </div>
                <Card class="w-full">
                    <HeaderForm :title="props.title" :back-url="props.backUrl" />
                    <CardContent>
                        <template v-if="tabsConfig && tabsConfig.length">
                            <component
                                :is="tabsConfig.find((tab) => tab.value === activeTabValue)?.component"
                                v-bind="tabsConfig.find((tab) => tab.value === activeTabValue)?.props"
                            />
                        </template>
                        <template v-else>
                            <slot />
                        </template>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
