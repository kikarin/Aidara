<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import RowActions from '@/pages/modules/components/tables/RowActions.vue';

import { computed } from 'vue';
import type { Column, Sort } from './datatable/types';
import { useDataTable } from './datatable/useDataTable';

interface DataTableProps {
    columns: Column[];
    rows: any[];
    actions: (row: any) => { label: string; onClick: () => void; permission?: string }[];
    selected: number[];
    baseUrl: string;
    moduleName: string;
    total: number;
    search: string;
    sort: Sort;
    page: number;
    perPage: number;
    hidePagination: boolean;
    disableLength: boolean;
    hideSearch: boolean;
    hideSelectAll: boolean;
    hideSelect: boolean;
    permissions?: {
        detail?: boolean;
        edit?: boolean;
        delete?: boolean;
    };
    detailUrl?: string;
    editUrl?: string;
    deleteUrl?: string;
}

const props = withDefaults(defineProps<DataTableProps>(), {
    selected: () => [],
    baseUrl: '',
    moduleName: '',
    hidePagination: false,
    disableLength: false,
    hideSearch: false,
    hideSelectAll: false,
    hideSelect: false,
    permissions: undefined,
    detailUrl: undefined,
    editUrl: undefined,
    deleteUrl: undefined,
});

const emit = defineEmits<{
    'update:selected': [value: number[]];
    'update:search': [value: string];
    'update:sort': [value: Sort];
    'update:page': [value: number];
    'update:perPage': [value: number];
    deleted: [];
    detail: [id: string | number];
    edit: [id: string | number];
    delete: [id: string | number];
}>();

const { visibleColumns, totalPages, getPageNumbers, sortBy, toggleSelect, toggleSelectAll } = useDataTable(
    props,
    emit as (event: string, ...args: any[]) => void,
);

const selectLabel = computed(() => {
    if (props.perPage === -1) return 'All';
    return props.perPage.toString();
});

const getValue = (obj: any, key: string) => {
    return key.split('.').reduce((o, i) => (o ? o[i] : undefined), obj);
};

const handleDetail = (id: string | number) => {
    emit('detail', id);
};

const handleEdit = (id: string | number) => {
    emit('edit', id);
};

const handleDelete = (id: string | number) => {
    emit('delete', id);
};
</script>

<template>
    <div class="space-y-4">
        <!-- Search dan Length -->
        <div class="ml-1.5 flex flex-col flex-wrap items-center justify-center gap-4 text-center sm:flex-row sm:justify-between">
            <!-- Length -->
            <div v-if="!props.disableLength" class="ml-2 flex items-center gap-2">
                <span class="text-muted-foreground text-sm">Show</span>
                <Select
                    :model-value="props.perPage.toString()"
                    @update:model-value="(val: string) => emit('update:perPage', val === 'all' ? -1 : Number(val))"
                >
                    <SelectTrigger class="w-24">
                        <SelectValue :placeholder="selectLabel" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="10">10</SelectItem>
                        <SelectItem value="25">25</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                        <SelectItem value="100">100</SelectItem>
                        <SelectItem value="500">500</SelectItem>
                        <SelectItem value="all">All</SelectItem>
                    </SelectContent>
                </Select>
                <span class="text-muted-foreground text-sm">entries</span>
            </div>

            <!-- Search (selalu tampil di kanan) -->
            <div v-if="!props.hideSearch" class="mr-2 w-full sm:w-64">
                <Input
                    :model-value="props.search"
                    @update:model-value="(val: string) => emit('update:search', val)"
                    placeholder="Search..."
                    class="w-full"
                />
            </div>
        </div>
        <!-- Table -->
        <div class="rounded-md shadow-sm">
            <div class="w-full overflow-x-auto">
                <Table class="min-w-max">
                    <TableHeader class="bg-muted">
                        <TableRow>
                            <TableHead class="w-12 text-center">No</TableHead>
                            <TableHead v-if="!props.hideSelectAll" class="w-10 text-center">
                                <label
                                    class="bg-background relative inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded border border-gray-500"
                                >
                                    <input
                                        type="checkbox"
                                        class="peer sr-only"
                                        :checked="props.selected.length > 0 && props.selected.length === props.rows.length"
                                        @change="(e: Event) => toggleSelectAll((e.target as HTMLInputElement).checked)"
                                    />
                                    <div class="bg-primary h-3 w-3 scale-0 transform rounded-sm transition-all peer-checked:scale-100"></div>
                                </label>
                            </TableHead>
                            <TableHead class="w-28 px-2 text-center text-xs break-words whitespace-normal sm:px-4 sm:text-sm">Actions</TableHead>
                            <TableHead
                                v-for="col in visibleColumns"
                                :key="col.key"
                                class="cursor-pointer select-none"
                                @click="col.orderable === false ? null : sortBy(col.key)"
                            >
                                <div class="flex items-center gap-1">
                                    {{ col.label }}
                                    <span v-if="props.sort.key === col.key">
                                        <span v-if="props.sort.order === 'asc'">▲</span>
                                        <span v-else>▼</span>
                                    </span>
                                </div>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(row, index) in props.rows" :key="index" class="hover:bg-muted/40 border-t transition">
                            <TableCell class="px-2 text-center text-xs break-words whitespace-normal sm:px-4 sm:text-sm">
                                {{ (props.page - 1) * props.perPage + index + 1 }}
                            </TableCell>
                            <TableCell v-if="!props.hideSelect" class="px-2 text-center text-xs break-words whitespace-normal sm:px-4 sm:text-sm">
                                <label
                                    class="bg-background relative inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded border border-gray-500"
                                >
                                    <input
                                        type="checkbox"
                                        class="peer sr-only"
                                        :checked="props.selected.includes(row.id)"
                                        @change="() => toggleSelect(row.id)"
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

                            <TableCell class="px-2 text-center text-xs break-words whitespace-normal sm:px-4 sm:text-sm">
                                <RowActions
                                    :id="row.id"
                                    :base-url="props.baseUrl"
                                    :module-name="props.moduleName"
                                    :actions="props.actions(row)"
                                    :permissions="props.permissions"
                                    :detail-url="props.detailUrl"
                                    :edit-url="props.editUrl"
                                    :delete-url="props.deleteUrl"
                                    @detail="handleDetail"
                                    @edit="handleEdit"
                                    @delete="handleDelete"
                                />
                            </TableCell>
                            <TableCell
                                v-for="col in visibleColumns"
                                :key="col.key"
                                :class="typeof col.className === 'function' ? col.className(row) : col.className"
                            >
                                <slot :name="`cell-${col.key}`" :row="row">
                                    <span v-if="typeof col.format === 'function'" v-html="col.format(row)"></span>
                                    <span v-else>{{ getValue(row, col.key) }}</span>
                                </slot>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <!-- Pagination Info -->
            <div
                v-if="!props.hidePagination"
                class="text-muted-foreground flex flex-col items-center justify-center gap-2 border-t p-4 text-center text-sm md:flex-row md:justify-between"
            >
                <span>
                    Showing {{ (props.page - 1) * props.perPage + 1 }} to {{ Math.min(props.page * props.perPage, props.total) }} of
                    {{ props.total }} entries
                </span>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <Button size="sm" :disabled="props.page === 1" @click="emit('update:page', props.page - 1)" class="bg-muted/40 text-foreground">
                        Previous
                    </Button>
                    <div class="flex flex-wrap items-center gap-1">
                        <Button
                            v-for="page in getPageNumbers()"
                            :key="page"
                            size="sm"
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="[
                                props.page === page
                                    ? 'bg-primary text-primary-foreground border-primary'
                                    : 'bg-muted border-input text-black dark:text-white',
                            ]"
                            @click="emit('update:page', page)"
                        >
                            {{ page }}
                        </Button>
                    </div>
                    <Button
                        size="sm"
                        :disabled="props.page === totalPages"
                        @click="emit('update:page', props.page + 1)"
                        class="bg-muted/40 text-foreground"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
