export type Column = {
    key: string;
    label: string;
    className?: string | ((row: any) => string);
    searchable?: boolean;
    orderable?: boolean;
    visible?: boolean;
    format?: (row: any) => string;
};

export type Sort = {
    key: string;
    order: 'asc' | 'desc';
};

export type DataTableProps = {
    columns: Column[];
    rows: any[];
    actions: (row: any) => { label: string; onClick: () => void; permission?: string }[];
    selected: number[];
    baseUrl: string;
    total: number;
    search: string;
    sort: Sort;
    page: number;
    perPage: number;
    hidePagination?: boolean;
    disableLength?: boolean;
    hideSearch?: boolean;
    hideSelectAll?: boolean;
    hideSelect?: boolean;
    moduleName?: string;
    permissions?: {
        detail?: boolean;
        edit?: boolean;
        delete?: boolean;
    };
    detailUrl?: string;
    editUrl?: string;
    deleteUrl?: string;
};
