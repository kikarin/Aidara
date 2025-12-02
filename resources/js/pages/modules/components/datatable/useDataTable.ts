import { computed } from 'vue';
import type { DataTableProps } from './types';

export function useDataTable(props: DataTableProps, emit: (event: string, ...args: any[]) => void) {
    const visibleColumns = computed(() => props.columns.filter((col) => col.visible !== false));

    const totalPages = computed(() => {
        return props.hidePagination ? 1 : Math.ceil(props.total / props.perPage);
    });

    const getPageNumbers = () => {
        const pages = [];
        const maxPages = 5;
        let start = Math.max(1, props.page - Math.floor(maxPages / 2));
        const end = Math.min(totalPages.value, start + maxPages - 1);

        if (end - start + 1 < maxPages) {
            start = Math.max(1, end - maxPages + 1);
        }

        for (let i = start; i <= end; i++) pages.push(i);
        return pages;
    };

    const sortBy = (key: string) => {
        const col = props.columns.find((c) => c.key === key);
        if (!col || col.orderable === false) return;
        const order = props.sort.key === key && props.sort.order === 'asc' ? 'desc' : 'asc';
        emit('update:sort', { key, order });
    };

    const toggleSelect = (rowId: number) => {
        if (props.selected.includes(rowId)) {
            emit(
                'update:selected',
                props.selected.filter((id) => id !== rowId),
            );
        } else {
            emit('update:selected', [...props.selected, rowId]);
        }
    };

    const toggleSelectAll = (checked: boolean) => {
        emit('update:selected', checked ? props.rows.map((row) => row.id) : []);
    };

    return {
        visibleColumns,
        totalPages,
        getPageNumbers,
        sortBy,
        toggleSelect,
        toggleSelectAll,
    };
}
