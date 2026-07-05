<script setup lang="ts">
import KarakteristikPanel from '@/components/dashboard/KarakteristikPanel.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Tabs from '@/components/ui/tabs/Tabs.vue';
import TabsContent from '@/components/ui/tabs/TabsContent.vue';
import TabsList from '@/components/ui/tabs/TabsList.vue';
import TabsTrigger from '@/components/ui/tabs/TabsTrigger.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const tabs = [
    {
        value: 'atlet',
        label: 'Atlet',
        apiEndpoint: '/atlet/api-karakteristik',
        pesertaLabel: 'atlet',
    },
    {
        value: 'pelatih',
        label: 'Pelatih',
        apiEndpoint: '/pelatih/api-karakteristik',
        pesertaLabel: 'pelatih',
    },
    {
        value: 'tenaga-pendukung',
        label: 'Tenaga Pendukung',
        apiEndpoint: '/tenaga-pendukung/api-karakteristik',
        pesertaLabel: 'tenaga pendukung',
    },
] as const;

type StatistikTab = (typeof tabs)[number]['value'];

const validTabs = tabs.map((tab) => tab.value);

function getTabFromUrl(url: string): StatistikTab {
    if (!url.includes('statistik=')) {
        return 'atlet';
    }

    const value = new URLSearchParams(url.split('?')[1] || '').get('statistik') as StatistikTab | null;
    return value && validTabs.includes(value) ? value : 'atlet';
}

const page = usePage();
const activeTab = ref<StatistikTab>(getTabFromUrl(page.url));
const loadedTabs = ref<Set<StatistikTab>>(new Set([activeTab.value]));

watch(activeTab, (value) => {
    loadedTabs.value.add(value);

    const url = `/dashboard?statistik=${value}`;
    router.visit(url, { replace: true, preserveState: true, preserveScroll: true, only: [] });
});

watch(
    () => page.url,
    (newUrl) => {
        const tab = getTabFromUrl(newUrl);
        if (tab !== activeTab.value) {
            activeTab.value = tab;
            loadedTabs.value.add(tab);
        }
    },
);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Statistik Karakteristik</CardTitle>
            <CardDescription>Analisis karakteristik peserta berdasarkan rentang tanggal</CardDescription>
        </CardHeader>
        <CardContent>
            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="bg-muted/70 mb-6 grid h-auto w-full grid-cols-3 gap-1 rounded-lg border p-1.5 shadow-sm">
                    <TabsTrigger
                        v-for="tab in tabs"
                        :key="tab.value"
                        :value="tab.value"
                        class="rounded-md border border-transparent px-3 py-2.5 text-sm font-semibold transition-all data-[state=active]:border-border data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm data-[state=inactive]:text-muted-foreground data-[state=inactive]:hover:bg-background/70 data-[state=inactive]:hover:text-foreground"
                    >
                        {{ tab.label }}
                    </TabsTrigger>
                </TabsList>

                <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value" force-mount class="mt-0">
                    <KarakteristikPanel
                        v-if="loadedTabs.has(tab.value)"
                        v-show="activeTab === tab.value"
                        :api-endpoint="tab.apiEndpoint"
                        :peserta-label="tab.pesertaLabel"
                    />
                </TabsContent>
            </Tabs>
        </CardContent>
    </Card>
</template>
