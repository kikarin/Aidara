<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        rows?: number;
        columns?: number;
        columnLabels?: string[];
        showCheckbox?: boolean;
        showActions?: boolean;
        showRowNumber?: boolean;
    }>(),
    {
        rows: 8,
        columns: 4,
        showCheckbox: false,
        showActions: false,
        showRowNumber: true,
    },
);

const headerLabels = computed(() => {
    if (props.columnLabels?.length) {
        return props.columnLabels;
    }

    return Array.from({ length: props.columns }, (_, i) => '');
});

const bodyColumns = computed(() => props.columnLabels?.length || props.columns);
</script>

<template>
    <div class="w-full overflow-x-auto rounded-md">
        <Table class="min-w-max">
            <TableHeader class="bg-muted">
                <TableRow>
                    <TableHead v-if="showRowNumber" class="w-12 text-center">
                        <Skeleton class="mx-auto h-4 w-6" />
                    </TableHead>
                    <TableHead v-if="showCheckbox" class="w-10 text-center">
                        <Skeleton class="mx-auto h-5 w-5 rounded" />
                    </TableHead>
                    <TableHead v-if="showActions" class="w-28 text-center">
                        <Skeleton class="mx-auto h-4 w-12" />
                    </TableHead>
                    <TableHead v-for="(label, index) in headerLabels" :key="index">
                        <Skeleton v-if="!label" class="h-4 w-24" />
                        <span v-else class="text-xs font-medium sm:text-sm">{{ label }}</span>
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="row in rows" :key="row" class="border-t">
                    <TableCell v-if="showRowNumber" class="text-center">
                        <Skeleton class="mx-auto h-4 w-6" />
                    </TableCell>
                    <TableCell v-if="showCheckbox" class="text-center">
                        <Skeleton class="mx-auto h-5 w-5 rounded" />
                    </TableCell>
                    <TableCell v-if="showActions" class="text-center">
                        <Skeleton class="mx-auto h-8 w-20 rounded-md" />
                    </TableCell>
                    <TableCell v-for="col in bodyColumns" :key="col">
                        <Skeleton class="h-4" :class="col % 3 === 0 ? 'w-full max-w-[180px]' : col % 3 === 1 ? 'w-3/4 max-w-[140px]' : 'w-1/2 max-w-[100px]'" />
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
