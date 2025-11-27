<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import VueJsonPretty from 'vue-json-pretty';
import 'vue-json-pretty/lib/styles.css';

const { toast } = useToast();

const props = defineProps<{
    fields: Array<{ label: string; value: string }>;
    actionFields: Array<{ label: string; value: string }>;
    backUrl?: string;
}>();

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Activity Logs', href: '/menu-permissions/logs' },
    { title: 'Detail Log', href: '#' },
];

// Ambil data dari field 'Data' dan parse JSON-nya
const jsonData = ref<any>(null);

onMounted(() => {
    const field = props.fields.find((f) => f.label === 'Data');
    if (field) {
        try {
            jsonData.value = JSON.parse(field.value);
        } catch {
            jsonData.value = field.value;
        }
    }
});

// Format fields lainnya, kecuali 'Data'
const formattedFields = computed(() => {
    return props.fields
        .filter((field) => field.label !== 'Data') // exclude 'Data'
        .map((field) => {
            let formattedValue = field.value;
            let className = '';

            if (field.label === 'Subject Type') {
                formattedValue = field.value.replace('App\\Models\\', '');
            }

            if (field.label === 'Subject ID') {
                className = 'font-mono';
            }

            return {
                ...field,
                value: formattedValue,
                className: className,
            };
        });
});

const handleDelete = () => {
    const logId = window.location.pathname.split('/').pop();
    if (logId) {
        router.delete(`/menu-permissions/logs/${logId}`, {
            onSuccess: () => {
                toast({ title: 'Log berhasil dihapus', variant: 'success' });
                router.visit('/menu-permissions/logs');
            },
            onError: () => {
                toast({ title: 'Gagal menghapus log', variant: 'destructive' });
            },
        });
    }
};
</script>

<template>
    <PageShow
        title="Activity Log"
        :breadcrumbs="breadcrumbs"
        :fields="formattedFields"
        :action-fields="props.actionFields"
        :back-url="props.backUrl || '/menu-permissions/logs'"
        :on-delete="handleDelete"
    >
        <!-- Tampilkan JSON interaktif -->
        <template #custom>
            <div v-if="jsonData" class="mb-6">
                <h4 class="mb-2 font-semibold">Data</h4>
                <VueJsonPretty :data="jsonData" :deep="3" class="rounded border p-3 text-sm" />
            </div>
        </template>
    </PageShow>
</template>
