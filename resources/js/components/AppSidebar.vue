<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import {
  LayoutGrid, Flag, FolderKanban, FileStack, Users, Settings, FileText, Folder,
  Shield, User, List, Plus, Edit, Trash, Search, Filter, Download, Upload, Menu,
  Home, BarChart, PieChart, Calendar, ShieldCheck, ClipboardList, UserCircle2,
  CalendarCheck, CalendarSync, ClipboardCheck, HeartHandshake, HandHeart,
  Ungroup, Stethoscope, Wrench, Trophy, CircleCheckBig, UserRoundCheck
} from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';
import AppLogo from './AppLogo.vue';

const mainNavItems = ref<NavItem[]>([]);
const atletNavItems = ref<NavItem[]>([]);
const caborNavItems = ref<NavItem[]>([]);
const trainingNavItems = ref<NavItem[]>([]);
const pemeriksaanNavItems = ref<NavItem[]>([]);
const settingNavItems = ref<NavItem[]>([]);
const isLoading = ref(false);
const iconMap: Record<string, any> = {
  LayoutGrid, Flag, FolderKanban, FileStack, Users, Settings, FileText, Folder,
  Shield, User, List, Plus, Edit, Trash, Search, Filter, Download, Upload, Menu,
  Home, BarChart, PieChart, Calendar, ShieldCheck, ClipboardList, UserCircle2,
  CalendarCheck, CalendarSync, ClipboardCheck, HeartHandshake, HandHeart,
  Ungroup, Stethoscope, Wrench, Trophy, CircleCheckBig, UserRoundCheck
};


const fetchMenus = async () => {
    if (isLoading.value) return;

    try {
        isLoading.value = true;
        const response = await axios.get('/api/users-menu');

        console.log('API Response:', response.data);

        let menus = response.data;

        if (menus.data && Array.isArray(menus.data)) {
            menus = menus.data;
        } else if (menus.menus && Array.isArray(menus.menus)) {
            menus = menus.menus;
        } else if (!Array.isArray(menus)) {
            console.error('Invalid menu data format:', menus);
            mainNavItems.value = [];
            settingNavItems.value = [];
            return;
        }

        const transformMenuToNavItem = (menu: any): NavItem => {
            const navItem: NavItem = {
                title: menu.nama || menu.name || 'Unknown',
                href: menu.url || '#',
                icon: menu.icon ? iconMap[menu.icon] : undefined,
            };

            if (menu.children && Array.isArray(menu.children) && menu.children.length > 0) {
                navItem.children = menu.children.map(transformMenuToNavItem);
            }

            return navItem;
        };

        const mainMenus = menus.filter((menu: any) => {
            const urutan = menu.urutan || 0;
            return urutan > 0 && urutan <= 10;
        });

        const atletMenus = menus.filter((menu: any) => {
            const urutan = menu.urutan || 0;
            return urutan > 10 && urutan <= 20;
        });
        const caborMenus = menus.filter((menu: any) => {
            const urutan = menu.urutan || 0;
            return urutan > 20 && urutan <= 30;
        });
        const trainingMenus = menus.filter((menu: any) => {
            const urutan = menu.urutan || 0;
            return urutan > 30 && urutan <= 40;
        });
        const pesertaMenus = menus.filter((menu: any) => {
            const urutan = menu.urutan || 0;
            return urutan > 40 && urutan <= 50;
        });
        const settingMenus = menus.filter((menu: any) => {
            const urutan = menu.urutan || 0;
            return urutan >= 100;
        });

        mainNavItems.value = mainMenus.map(transformMenuToNavItem);
        atletNavItems.value = atletMenus.map(transformMenuToNavItem);
        caborNavItems.value = caborMenus.map(transformMenuToNavItem);
        trainingNavItems.value = trainingMenus.map(transformMenuToNavItem);
        pemeriksaanNavItems.value = pesertaMenus.map(transformMenuToNavItem);
        settingNavItems.value = settingMenus.map(transformMenuToNavItem);

        console.log('Main Menus:', mainNavItems.value);
        console.log('Setting Menus:', settingNavItems.value);
    } catch (error) {
        console.error('Error fetching menus:', error);

        mainNavItems.value = [];
        settingNavItems.value = [];
    } finally {
        isLoading.value = false;
    }
};


onMounted(() => {
    fetchMenus();
});

onUnmounted(() => {

});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div v-if="isLoading && mainNavItems.length === 0 && settingNavItems.length === 0" class="text-muted-foreground px-4 py-2 text-sm">
                Loading menus...
            </div>

            <NavMain v-if="mainNavItems.length > 0" :items="mainNavItems" section-title="Menu" section-id="main" />

            <NavMain v-if="atletNavItems.length > 0" :items="atletNavItems" section-title="Data Peserta" section-id="atlet" />

            <NavMain v-if="caborNavItems.length > 0" :items="caborNavItems" section-title="Cabang Olahraga" section-id="cabor" />

            <NavMain v-if="trainingNavItems.length > 0" :items="trainingNavItems" section-title="Turnamen" section-id="training" />

            <NavMain v-if="pemeriksaanNavItems.length > 0" :items="pemeriksaanNavItems" section-title="Pemeriksaan" section-id="pemeriksaan" />

            <NavMain v-if="settingNavItems.length > 0" :items="settingNavItems" section-title="Settings" section-id="setting" />

            <div v-if="!isLoading && mainNavItems.length === 0 && settingNavItems.length === 0" class="text-muted-foreground px-4 py-2 text-sm">
                No menu items available
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
