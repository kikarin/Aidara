import type { BreadcrumbItemType } from '@/types';
import { ref } from 'vue';

const breadcrumbs = ref<BreadcrumbItemType[]>([]);

export function useLayoutBreadcrumbs() {
    const setBreadcrumbs = (items: BreadcrumbItemType[]) => {
        breadcrumbs.value = items;
    };

    return {
        breadcrumbs,
        setBreadcrumbs,
    };
}
