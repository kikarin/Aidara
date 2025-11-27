<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import permissionService from '@/services/permissionService';
import { Link } from '@inertiajs/vue3';
import { BarChart3, Import, SlidersHorizontal, Trash } from 'lucide-vue-next';

const props = defineProps<{
    title: string;
    moduleName: string;
    createUrl?: string;
    createMultipleUrl?: string;
    showMultipleButton?: boolean;
    selected: number[];
    onDeleteSelected: () => void;
    showImport: boolean;
    showKehadiran?: boolean;
    showKelola?: boolean;
    kelolaUrl?: string;
    kelolaLabel?: string;
    showDelete?: boolean;
    permissions?: {
        create?: boolean;
        delete?: boolean;
        import?: boolean;
        kelola?: boolean;
    };
    showStatistik?: boolean;
    statistikUrl?: string;
    showFilter?: boolean;
    showBulkApprove?: boolean;
    showBulkReject?: boolean;
}>();

const canCreate = () => {
    if (props.permissions?.create !== undefined) return props.permissions.create;
    return permissionService.canCreate(props.moduleName);
};

const canDelete = () => {
    if (props.permissions?.delete !== undefined) return props.permissions.delete;
    return permissionService.canDelete(props.moduleName);
};

const canImport = () => {
    if (props.permissions?.import !== undefined) return props.permissions.import;
    return permissionService.hasPermission(`${props.moduleName} Import`);
};

const canKelola = () => {
    if (props.permissions?.kelola !== undefined) return props.permissions.kelola;
    return permissionService.hasPermission(`${props.moduleName} Kelola`);
};
</script>

<template>
    <div class="mx-auto flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-xl font-semibold tracking-tight dark:text-white">
            {{ title }}
        </h1>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Button Filter -->
            <Button v-if="props.showFilter" variant="outline" size="sm" class="flex items-center gap-2" @click="$emit('filter')">
                <SlidersHorizontal class="h-4 w-4" />
                Filter
            </Button>
            <Button
                v-if="props.showBulkApprove"
                variant="default"
                size="sm"
                class="flex items-center gap-2"
                :disabled="selected.length === 0"
                @click="$emit('bulk-approve')"
            >
                Setujui Terpilih ({{ selected.length }})
            </Button>
            <Button
                v-if="props.showBulkReject"
                variant="destructive"
                size="sm"
                class="flex items-center gap-2"
                :disabled="selected.length === 0"
                @click="$emit('bulk-reject')"
            >
                Tolak Terpilih ({{ selected.length }})
            </Button>
            <!-- Button Import -->
            <Button v-if="props.showImport && canImport()" variant="outline" size="sm" @click="$emit('import')">
                <Import class="h-4 w-4" />
                Import Excel
            </Button>

            <!-- Button Tambah Multiple -->
            <Link v-if="props.showMultipleButton && props.createMultipleUrl && canCreate()" :href="props.createMultipleUrl">
                <Button variant="outline" size="sm">+ Tambah Multiple</Button>
            </Link>

            <!-- Button Create -->
            <Link v-if="props.createUrl && canCreate()" :href="props.createUrl">
                <Button variant="outline" size="sm">+ Create</Button>
            </Link>

            <!-- Button Kelola -->
            <Link v-if="props.showKelola && props.kelolaUrl && canKelola()" :href="props.kelolaUrl">
                <Button variant="outline" size="sm">{{ props.kelolaLabel || 'Kelola' }}</Button>
            </Link>

            <!-- Button Statistik -->
            <Link v-if="props.showStatistik && props.statistikUrl" :href="props.statistikUrl">
                <Button variant="outline" size="sm" class="flex items-center gap-2">
                    <BarChart3 class="h-4 w-4" />
                    Statistik
                </Button>
            </Link>

            <!-- Dropdown Set Kehadiran -->
            <DropdownMenu v-if="props.showKehadiran">
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="sm" :disabled="selected.length === 0" class="mr-2" type="button"> Set Kehadiran </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="$emit('setKehadiran', 'Hadir')">Hadir</DropdownMenuItem>
                    <DropdownMenuItem @click="$emit('setKehadiran', 'Tidak Hadir')">Tidak Hadir</DropdownMenuItem>
                    <DropdownMenuItem @click="$emit('setKehadiran', 'Izin')">Izin</DropdownMenuItem>
                    <DropdownMenuItem @click="$emit('setKehadiran', 'Sakit')">Sakit</DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Button Delete Selected -->
            <Button
                v-if="props.showDelete !== true && canDelete()"
                variant="destructive"
                size="sm"
                :disabled="selected.length === 0"
                @click="onDeleteSelected"
            >
                <Trash class="h-4 w-4" />
                Delete Selected ({{ selected.length }})
            </Button>
        </div>
    </div>
</template>
