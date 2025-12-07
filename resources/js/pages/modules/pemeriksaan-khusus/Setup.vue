<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import SetupAspekItemTes from './SetupAspekItemTes.vue';
import { router } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import axios from 'axios';

const props = defineProps<{ 
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Pemeriksaan Khusus', href: '/pemeriksaan-khusus' },
    { title: 'Setup Aspek & Item Tes', href: `/pemeriksaan-khusus/${props.item.id}/setup` },
];

const aspekItemTes = ref<any[]>([]);
const loading = ref(false);

const loadAspekItemTes = async () => {
    loading.value = true;
    try {
        const res = await axios.get(`/api/pemeriksaan-khusus/${props.item.id}/aspek-item-tes`);
        aspekItemTes.value = res.data.aspek || [];
    } catch (error) {
        console.error('Error loading aspek-item tes:', error);
        aspekItemTes.value = [];
    } finally {
        loading.value = false;
    }
};

const handleSaved = () => {
    // Reload data
    loadAspekItemTes();
    
    // Redirect ke detail atau index
    router.visit(`/pemeriksaan-khusus/${props.item.id}`);
};

onMounted(() => {
    loadAspekItemTes();
});
</script>

<template>
    <PageEdit title="Setup Aspek & Item Tes" :breadcrumbs="breadcrumbs" back-url="/pemeriksaan-khusus">
        <div v-if="loading" class="flex items-center justify-center py-12">
            <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
            <span class="ml-2 text-muted-foreground">Memuat data...</span>
        </div>
        <SetupAspekItemTes
            v-else
            :pemeriksaan-khusus-id="item.id"
            :cabor-id="item.cabor_id"
            :initial-aspek="aspekItemTes"
            @saved="handleSaved"
            @cancel="router.visit(`/pemeriksaan-khusus/${item.id}`)"
        />
    </PageEdit>
</template>

