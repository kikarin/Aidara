<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { useSidebarMenus } from '@/composables/useSidebarMenus';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import SidebarMenuSkeleton from '@/components/ui/sidebar/SidebarMenuSkeleton.vue';
import { Link } from '@inertiajs/vue3';
import {
    Activity,
    BarChart,
    Calendar,
    CalendarCheck,
    CalendarSync,
    ClipboardCheck,
    ClipboardList,
    Download,
    Edit,
    FileStack,
    FileText,
    Filter,
    Flag,
    Folder,
    FolderKanban,
    HandHeart,
    HeartHandshake,
    Home,
    LayoutGrid,
    List,
    Menu,
    PieChart,
    Plus,
    Search,
    Settings,
    Shield,
    ShieldCheck,
    Stethoscope,
    Trash,
    Trophy,
    Ungroup,
    Upload,
    User,
    UserCircle2,
    UserRoundCheck,
    Users,
    Wrench,
    CircleCheckBig,
} from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import AppLogo from './AppLogo.vue';

const iconMap: Record<string, unknown> = {
    LayoutGrid,
    Flag,
    FolderKanban,
    FileStack,
    Users,
    Settings,
    FileText,
    Folder,
    Shield,
    User,
    List,
    Plus,
    Edit,
    Trash,
    Search,
    Filter,
    Download,
    Upload,
    Menu,
    Home,
    BarChart,
    PieChart,
    Calendar,
    ShieldCheck,
    ClipboardList,
    UserCircle2,
    CalendarCheck,
    CalendarSync,
    ClipboardCheck,
    HeartHandshake,
    HandHeart,
    Ungroup,
    Stethoscope,
    Wrench,
    Trophy,
    CircleCheckBig,
    UserRoundCheck,
    Activity,
};

const { mainNavItems, atletNavItems, caborNavItems, trainingNavItems, pemeriksaanNavItems, settingNavItems, isLoading, isLoaded, fetchMenus } =
    useSidebarMenus(iconMap);

const showSkeleton = computed(() => isLoading.value && !isLoaded.value);

onMounted(() => {
    fetchMenus();
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')" prefetch="hover">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div v-if="showSkeleton" class="space-y-1 px-2 py-2">
                <SidebarMenuSkeleton v-for="n in 8" :key="n" show-icon />
            </div>

            <NavMain v-if="mainNavItems.length > 0" :items="mainNavItems" section-title="Menu" section-id="main" />

            <NavMain v-if="atletNavItems.length > 0" :items="atletNavItems" section-title="Data Peserta" section-id="atlet" />

            <NavMain v-if="caborNavItems.length > 0" :items="caborNavItems" section-title="Cabang Olahraga" section-id="cabor" />

            <NavMain v-if="trainingNavItems.length > 0" :items="trainingNavItems" section-title="Turnamen" section-id="training" />

            <NavMain v-if="pemeriksaanNavItems.length > 0" :items="pemeriksaanNavItems" section-title="Pemeriksaan" section-id="pemeriksaan" />

            <NavMain v-if="settingNavItems.length > 0" :items="settingNavItems" section-title="Pengaturan" section-id="setting" />

            <div v-if="!isLoading && mainNavItems.length === 0 && settingNavItems.length === 0" class="text-muted-foreground px-4 py-2 text-sm">
                Tidak ada menu tersedia
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
