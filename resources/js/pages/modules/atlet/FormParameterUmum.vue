<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useToast } from '@/components/ui/toast/useToast';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const props = defineProps<{ mode: 'edit'; atletId: number | null }>();
const { toast } = useToast();

const loading = ref(false);
const saving = ref(false);
const parameters = ref<
    Array<{ id: number; nama: string; satuan: string | null; nilai_target: string | null; performa_arah: string | null; nilai: string | null }>
>([]);

const loadData = async () => {
    if (!props.atletId) return;
    loading.value = true;
    try {
        const res = await axios.get(`/api/atlet/${props.atletId}/parameter-umum`);
        if (res.data?.success) {
            parameters.value = (res.data.data || []).map((p: any) => ({
                id: p.id,
                nama: p.nama,
                satuan: p.satuan,
                nilai_target: p.nilai_target,
                performa_arah: p.performa_arah,
                nilai: p.nilai,
            }));
        } else {
            toast({ title: res.data?.message || 'Gagal memuat parameter umum', variant: 'destructive' });
        }
    } catch (e: any) {
        toast({ title: e.response?.data?.message || 'Gagal memuat parameter umum', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

const handleSave = async () => {
    if (!props.atletId) return;
    saving.value = true;
    try {
        const payload = {
            parameter_umum: parameters.value.map((p) => ({ mst_parameter_id: p.id, nilai: p.nilai || '' })),
        };
        const res = await axios.post(`/api/atlet/${props.atletId}/parameter-umum`, payload);
        if (res.data?.success) {
            toast({ title: res.data?.message || 'Parameter umum berhasil disimpan', variant: 'success' });
        } else {
            toast({ title: res.data?.message || 'Gagal menyimpan parameter umum', variant: 'destructive' });
        }
    } catch (e: any) {
        toast({ title: e.response?.data?.message || 'Gagal menyimpan parameter umum', variant: 'destructive' });
    } finally {
        saving.value = false;
    }
};

onMounted(loadData);
</script>

<template>
    <div class="space-y-4">
        <Card>
            <CardHeader>
                <CardTitle>Parameter Umum</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="loading" class="text-muted-foreground py-8 text-center">Memuat data...</div>
                <div v-else>
                    <div v-if="parameters.length === 0" class="text-muted-foreground py-8 text-center">Tidak ada parameter umum di master data.</div>
                    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div v-for="p in parameters" :key="p.id" class="space-y-2">
                            <label class="text-sm font-medium">
                                {{ p.nama }}
                                <span v-if="p.satuan" class="text-muted-foreground">({{ p.satuan }})</span>
                                <span v-if="p.nilai_target" class="text-muted-foreground text-xs"> - Target: {{ p.nilai_target }}</span>
                            </label>
                            <Input v-model="p.nilai" type="text" :placeholder="`Masukkan nilai ${p.nama}`" />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <Button :disabled="saving" @click="handleSave">{{ saving ? 'Menyimpan...' : 'Simpan' }}</Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
