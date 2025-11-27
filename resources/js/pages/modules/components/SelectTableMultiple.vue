<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import axios from 'axios';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{
    label: string;
    endpoint: string;
    columns: { key: string; label: string; format?: (row: any) => string }[];
    idKey: string;
    nameKey: string;
    selectedIds?: number[];
    autoSelectAll?: boolean; // improvement: auto select all saat create
}>();

const emit = defineEmits(['update:selectedIds']);

const items = ref<any[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const currentPage = ref(1);
const perPage = ref(10);
const total = ref(0);
const localSelected = ref<number[]>(props.selectedIds ? [...props.selectedIds] : []);

const fetchData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(props.endpoint, {
            params: {
                page: currentPage.value,
                per_page: perPage.value,
                search: searchQuery.value,
            },
        });
        items.value = response.data.data || [];
        total.value = response.data.meta?.total || 0;
        if (props.autoSelectAll && items.value.length > 0 && localSelected.value.length === 0) {
            localSelected.value = items.value.map((item) => item[props.idKey]);
        }
    } catch {
        items.value = [];
        total.value = 0;
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
watch([searchQuery, perPage], () => {
    currentPage.value = 1;
    fetchData();
});
watch(currentPage, fetchData);
watch(() => props.endpoint, fetchData);

watch(localSelected, (val) => {
    emit('update:selectedIds', [...val]);
});
watch(
    () => props.selectedIds,
    (val) => {
        if (val && JSON.stringify(val) !== JSON.stringify(localSelected.value)) {
            localSelected.value = [...val];
        }
    },
);

const toggleSelect = (id: number) => {
    const idx = localSelected.value.indexOf(id);
    if (idx > -1) {
        localSelected.value = localSelected.value.filter((v) => v !== id);
    } else {
        localSelected.value = [...localSelected.value, id];
    }
};
const toggleSelectAll = (checked: boolean) => {
    if (checked) {
        localSelected.value = items.value.map((item) => item[props.idKey]);
    } else {
        localSelected.value = [];
    }
};
const isSelected = (id: number) => localSelected.value.includes(id);
</script>
<template>
    <div class="space-y-6">
        <div class="mb-1 flex items-center gap-2">
            <span class="font-semibold">{{ label }}</span>
            <Badge variant="secondary">{{ localSelected.length }} dipilih</Badge>
        </div>
        <div class="mb-2 flex flex-col flex-wrap items-center justify-center gap-4 text-center sm:flex-row sm:justify-between">
            <div class="w-full sm:w-64">
                <Input v-model="searchQuery" placeholder="Search..." class="w-full" />
            </div>
        </div>
        <div v-if="loading" class="flex items-center justify-center py-8">
            <div class="border-primary h-8 w-8 animate-spin rounded-full border-b-2"></div>
            <span class="text-muted-foreground ml-2 text-sm">Memuat data...</span>
        </div>
        <div v-else-if="items.length === 0" class="py-8 text-center">
            <p class="text-muted-foreground">Tidak ada data tersedia</p>
        </div>
        <div v-else class="rounded-md shadow-sm">
            <div class="w-full overflow-x-auto">
                <Table class="min-w-max">
                    <TableHeader class="bg-muted">
                        <TableRow>
                            <TableHead class="w-12 text-center">No</TableHead>
                            <TableHead class="w-10 text-center">
                                <label
                                    class="bg-background relative inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded border border-gray-500"
                                >
                                    <input
                                        type="checkbox"
                                        class="peer sr-only"
                                        :checked="localSelected.length > 0 && localSelected.length === items.length"
                                        @change="(e) => toggleSelectAll((e.target as HTMLInputElement).checked)"
                                    />
                                    <div class="bg-primary h-3 w-3 scale-0 transform rounded-sm transition-all peer-checked:scale-100"></div>
                                </label>
                            </TableHead>
                            <TableHead v-for="col in props.columns" :key="col.key" class="cursor-pointer select-none">
                                <div class="flex items-center gap-1">{{ col.label }}</div>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(item, index) in items" :key="item[props.idKey]" class="hover:bg-muted/40 border-t transition">
                            <TableCell class="px-2 text-center text-xs break-words whitespace-normal sm:px-4 sm:text-sm">
                                {{ (currentPage - 1) * perPage + index + 1 }}
                            </TableCell>
                            <TableCell class="px-2 text-center text-xs break-words whitespace-normal sm:px-4 sm:text-sm">
                                <label
                                    class="bg-background relative inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded border border-gray-500"
                                >
                                    <input
                                        type="checkbox"
                                        class="peer sr-only"
                                        :checked="isSelected(item[props.idKey])"
                                        @change="() => toggleSelect(item[props.idKey])"
                                    />
                                    <svg
                                        class="text-primary h-4 w-4 scale-75 opacity-0 transition-all duration-200 peer-checked:scale-100 peer-checked:opacity-100"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="3"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </label>
                            </TableCell>
                            <TableCell v-for="col in props.columns" :key="col.key">
                                <span v-if="typeof col.format === 'function'" v-html="col.format(item)"></span>
                                <span v-else>{{ item[col.key] }}</span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </div>
</template>
