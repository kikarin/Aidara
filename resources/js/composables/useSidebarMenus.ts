import type { NavItem } from '@/types';
import axios from 'axios';
import { readonly, ref } from 'vue';

const mainNavItems = ref<NavItem[]>([]);
const atletNavItems = ref<NavItem[]>([]);
const caborNavItems = ref<NavItem[]>([]);
const trainingNavItems = ref<NavItem[]>([]);
const pemeriksaanNavItems = ref<NavItem[]>([]);
const settingNavItems = ref<NavItem[]>([]);
const isLoading = ref(false);
const isLoaded = ref(false);

let fetchPromise: Promise<void> | null = null;

export function useSidebarMenus(iconMap: Record<string, unknown>) {
    const transformMenuToNavItem = (menu: Record<string, unknown>): NavItem => {
        const navItem: NavItem = {
            title: (menu.nama as string) || (menu.name as string) || 'Tidak Diketahui',
            href: (menu.url as string) || '#',
            icon: menu.icon ? (iconMap[menu.icon as string] as NavItem['icon']) : undefined,
        };

        const children = menu.children;
        if (Array.isArray(children) && children.length > 0) {
            navItem.children = children.map((child) => transformMenuToNavItem(child as Record<string, unknown>));
        }

        return navItem;
    };

    const fetchMenus = async (force = false) => {
        if (isLoaded.value && !force) {
            return;
        }

        if (fetchPromise && !force) {
            return fetchPromise;
        }

        isLoading.value = true;

        fetchPromise = (async () => {
            try {
                const response = await axios.get('/api/users-menu');

                let menus = response.data;

                if (menus.data && Array.isArray(menus.data)) {
                    menus = menus.data;
                } else if (menus.menus && Array.isArray(menus.menus)) {
                    menus = menus.menus;
                } else if (!Array.isArray(menus)) {
                    mainNavItems.value = [];
                    settingNavItems.value = [];
                    return;
                }

                mainNavItems.value = menus
                    .filter((menu: Record<string, unknown>) => {
                        const urutan = (menu.urutan as number) || 0;
                        return urutan > 0 && urutan <= 10;
                    })
                    .map(transformMenuToNavItem);

                atletNavItems.value = menus
                    .filter((menu: Record<string, unknown>) => {
                        const urutan = (menu.urutan as number) || 0;
                        return urutan > 10 && urutan <= 20;
                    })
                    .map(transformMenuToNavItem);

                caborNavItems.value = menus
                    .filter((menu: Record<string, unknown>) => {
                        const urutan = (menu.urutan as number) || 0;
                        return urutan > 20 && urutan <= 30;
                    })
                    .map(transformMenuToNavItem);

                trainingNavItems.value = menus
                    .filter((menu: Record<string, unknown>) => {
                        const urutan = (menu.urutan as number) || 0;
                        return urutan > 30 && urutan <= 40;
                    })
                    .map(transformMenuToNavItem);

                pemeriksaanNavItems.value = menus
                    .filter((menu: Record<string, unknown>) => {
                        const urutan = (menu.urutan as number) || 0;
                        return urutan > 40 && urutan <= 50;
                    })
                    .map(transformMenuToNavItem);

                settingNavItems.value = menus
                    .filter((menu: Record<string, unknown>) => {
                        const urutan = (menu.urutan as number) || 0;
                        return urutan >= 100;
                    })
                    .map(transformMenuToNavItem);

                isLoaded.value = true;
            } catch (error) {
                console.error('Error fetching menus:', error);
                mainNavItems.value = [];
                settingNavItems.value = [];
            } finally {
                isLoading.value = false;
                fetchPromise = null;
            }
        })();

        return fetchPromise;
    };

    return {
        mainNavItems: readonly(mainNavItems),
        atletNavItems: readonly(atletNavItems),
        caborNavItems: readonly(caborNavItems),
        trainingNavItems: readonly(trainingNavItems),
        pemeriksaanNavItems: readonly(pemeriksaanNavItems),
        settingNavItems: readonly(settingNavItems),
        isLoading: readonly(isLoading),
        isLoaded: readonly(isLoaded),
        fetchMenus,
    };
}
