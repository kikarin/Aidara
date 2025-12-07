<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/toast/useToast';
import axios from 'axios';
import { AlertCircle, Loader2, Plus, Save, Trash2, X } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    pemeriksaanKhususId: number;
    caborId: number;
    initialAspek?: any[];
}>();

const emit = defineEmits(['saved', 'cancel']);

// State
const loading = ref(false);
const loadingTemplate = ref(false);
const hasTemplate = ref(false);
const templateData = ref<any>(null);
const showConfirmSaveTemplate = ref(false);

// Aspek & Item Tes data
interface ItemTes {
    id?: number;
    tempId?: string;
    nama: string;
    satuan: string;
    target_laki_laki: string;
    target_perempuan: string;
    performa_arah: 'max' | 'min';
    urutan: number;
}

interface Aspek {
    id?: number;
    tempId?: string;
    nama: string;
    urutan: number;
    item_tes: ItemTes[];
}

const aspekList = ref<Aspek[]>([]);
let tempIdCounter = 1;

// Computed
const totalAspek = computed(() => aspekList.value.length);
const totalItemTes = computed(() => {
    return aspekList.value.reduce((total, aspek) => total + aspek.item_tes.length, 0);
});

// Methods
const checkTemplate = async () => {
    if (!props.caborId) return;

    loadingTemplate.value = true;
    try {
        const res = await axios.get(`/api/pemeriksaan-khusus/check-template/${props.caborId}`);
        hasTemplate.value = res.data.has_template || false;
        templateData.value = res.data.template || null;
    } catch (error: any) {
        console.error('Error checking template:', error);
        toast({
            title: 'Gagal memuat template',
            variant: 'destructive',
        });
    } finally {
        loadingTemplate.value = false;
    }
};

const loadFromTemplate = () => {
    if (!templateData.value) return;

    aspekList.value = templateData.value.map((aspek: any) => ({
        tempId: `temp_${tempIdCounter++}`,
        nama: aspek.nama,
        urutan: aspek.urutan || 0,
        item_tes: aspek.item_tes.map((item: any) => ({
            tempId: `temp_${tempIdCounter++}`,
            nama: item.nama,
            satuan: item.satuan || '',
            target_laki_laki: item.target_laki_laki || '',
            target_perempuan: item.target_perempuan || '',
            performa_arah: item.performa_arah || 'max',
            urutan: item.urutan || 0,
        })),
    }));

    toast({
        title: 'Template berhasil dimuat',
        variant: 'success',
    });
};

const addAspek = () => {
    aspekList.value.push({
        tempId: `temp_${tempIdCounter++}`,
        nama: '',
        urutan: aspekList.value.length + 1,
        item_tes: [],
    });
};

const removeAspek = (index: number) => {
    if (aspekList.value.length > 1) {
        aspekList.value.splice(index, 1);
        // Update urutan
        aspekList.value.forEach((aspek, idx) => {
            aspek.urutan = idx + 1;
        });
    } else {
        toast({
            title: 'Minimal harus ada 1 aspek',
            variant: 'destructive',
        });
    }
};

const addItemTes = (aspekIndex: number) => {
    const aspek = aspekList.value[aspekIndex];
    aspek.item_tes.push({
        tempId: `temp_${tempIdCounter++}`,
        nama: '',
        satuan: '',
        target_laki_laki: '',
        target_perempuan: '',
        performa_arah: 'max',
        urutan: aspek.item_tes.length + 1,
    });
};

const removeItemTes = (aspekIndex: number, itemIndex: number) => {
    const aspek = aspekList.value[aspekIndex];
    if (aspek.item_tes.length > 1) {
        aspek.item_tes.splice(itemIndex, 1);
        // Update urutan
        aspek.item_tes.forEach((item, idx) => {
            item.urutan = idx + 1;
        });
    } else {
        toast({
            title: 'Minimal harus ada 1 item tes per aspek',
            variant: 'destructive',
        });
    }
};

const validateForm = (): boolean => {
    if (aspekList.value.length === 0) {
        toast({
            title: 'Minimal harus ada 1 aspek',
            variant: 'destructive',
        });
        return false;
    }

    for (const aspek of aspekList.value) {
        if (!aspek.nama || aspek.nama.trim() === '') {
            toast({
                title: `Nama aspek ke-${aspek.urutan} wajib diisi`,
                variant: 'destructive',
            });
            return false;
        }

        if (aspek.item_tes.length === 0) {
            toast({
                title: `Aspek "${aspek.nama}" harus memiliki minimal 1 item tes`,
                variant: 'destructive',
            });
            return false;
        }

        for (const item of aspek.item_tes) {
            if (!item.nama || item.nama.trim() === '') {
                toast({
                    title: `Nama item tes di aspek "${aspek.nama}" wajib diisi`,
                    variant: 'destructive',
                });
                return false;
            }
        }
    }

    return true;
};

const handleSave = async () => {
    if (!validateForm()) {
        return;
    }

    loading.value = true;
    try {
        // Prepare data untuk dikirim (filter unique untuk menghindari duplicate)
        const uniqueAspekMap = new Map<string, any>(); // Use nama + urutan as key
        
        aspekList.value.forEach((aspek) => {
            const aspekKey = `${aspek.nama.trim()}_${aspek.urutan}`;
            if (!uniqueAspekMap.has(aspekKey)) {
                const uniqueItemTesMap = new Map<string, any>();
                
                aspek.item_tes.forEach((item: any) => {
                    const itemKey = `${item.nama.trim()}_${aspek.nama.trim()}`;
                    if (!uniqueItemTesMap.has(itemKey)) {
                        uniqueItemTesMap.set(itemKey, {
                            nama: item.nama.trim(),
                            satuan: item.satuan?.trim() || null,
                            target_laki_laki: item.target_laki_laki?.trim() || null,
                            target_perempuan: item.target_perempuan?.trim() || null,
                            performa_arah: item.performa_arah,
                            urutan: item.urutan,
                        });
                    }
                });

                uniqueAspekMap.set(aspekKey, {
                    nama: aspek.nama.trim(),
                    urutan: aspek.urutan,
                    item_tes: Array.from(uniqueItemTesMap.values()),
                });
            }
        });

        const dataToSave = {
            pemeriksaan_khusus_id: props.pemeriksaanKhususId,
            aspek: Array.from(uniqueAspekMap.values()),
        };

        await axios.post('/api/pemeriksaan-khusus/save-aspek-item-tes', dataToSave);

        toast({
            title: 'Aspek & item tes berhasil disimpan',
            variant: 'success',
        });

        emit('saved');
    } catch (error: any) {
        console.error('Error saving aspek-item tes:', error);
        toast({
            title: error.response?.data?.message || 'Gagal menyimpan aspek & item tes',
            variant: 'destructive',
        });
    } finally {
        loading.value = false;
    }
};

const handleSaveAsTemplate = () => {
    if (!validateForm()) {
        return;
    }

    showConfirmSaveTemplate.value = true;
};

const confirmSaveAsTemplate = async () => {
    showConfirmSaveTemplate.value = false;
    
    loading.value = true;
    try {
        const dataToSave = {
            cabor_id: props.caborId,
            aspek: aspekList.value.map((aspek) => ({
                nama: aspek.nama.trim(),
                urutan: aspek.urutan,
                item_tes: aspek.item_tes.map((item) => ({
                    nama: item.nama.trim(),
                    satuan: item.satuan?.trim() || null,
                    target_laki_laki: item.target_laki_laki?.trim() || null,
                    target_perempuan: item.target_perempuan?.trim() || null,
                    performa_arah: item.performa_arah,
                    urutan: item.urutan,
                })),
            })),
        };

        await axios.post('/api/pemeriksaan-khusus/save-as-template', dataToSave);

        toast({
            title: 'Template berhasil disimpan',
            variant: 'success',
        });

        // Reload template check
        await checkTemplate();
    } catch (error: any) {
        console.error('Error saving template:', error);
        toast({
            title: error.response?.data?.message || 'Gagal menyimpan template',
            variant: 'destructive',
        });
    } finally {
        loading.value = false;
    }
};

// Load initial data
onMounted(async () => {
    await checkTemplate();

    // Load existing aspek-item tes jika ada
    if (props.initialAspek && props.initialAspek.length > 0) {
        // Filter unique aspek berdasarkan id
        const uniqueAspekMap = new Map<number, any>();
        props.initialAspek.forEach((aspek: any) => {
            if (aspek.id && !uniqueAspekMap.has(aspek.id)) {
                // Filter unique item tes dalam aspek ini
                const uniqueItemTesMap = new Map<number, any>();
                (aspek.item_tes || []).forEach((item: any) => {
                    if (item.id && !uniqueItemTesMap.has(item.id)) {
                        uniqueItemTesMap.set(item.id, item);
                    }
                });

                uniqueAspekMap.set(aspek.id, {
                    ...aspek,
                    item_tes: Array.from(uniqueItemTesMap.values()),
                });
            }
        });

        aspekList.value = Array.from(uniqueAspekMap.values()).map((aspek: any) => ({
            id: aspek.id,
            tempId: aspek.id ? undefined : `temp_${tempIdCounter++}`,
            nama: aspek.nama,
            urutan: aspek.urutan || 0,
            item_tes: (aspek.item_tes || []).map((item: any) => ({
                id: item.id,
                tempId: item.id ? undefined : `temp_${tempIdCounter++}`,
                nama: item.nama,
                satuan: item.satuan || '',
                target_laki_laki: item.target_laki_laki || '',
                target_perempuan: item.target_perempuan || '',
                performa_arah: item.performa_arah || 'max',
                urutan: item.urutan || 0,
            })),
        }));
    } else {
        // Default: 1 aspek kosong
        addAspek();
        addItemTes(0);
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Template Info & Actions -->
        <Card v-if="hasTemplate || loadingTemplate">
            <CardHeader>
                <CardTitle>Template Tersedia</CardTitle>
                <CardDescription>
                    Template untuk cabor ini sudah tersedia. Anda bisa menggunakan template atau membuat manual.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="loadingTemplate" class="flex items-center gap-2 text-muted-foreground">
                    <Loader2 class="h-4 w-4 animate-spin" />
                    <span>Memeriksa template...</span>
                </div>
                <div v-else class="flex gap-2">
                    <Button @click="loadFromTemplate" variant="outline" :disabled="loading">
                        <Plus class="h-4 w-4 mr-2" />
                        Gunakan Template
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Form Aspek & Item Tes -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>Setup Aspek & Item Tes</CardTitle>
                        <CardDescription>
                            Tambahkan aspek yang akan dinilai beserta item tes-nya. Total: {{ totalAspek }} aspek,
                            {{ totalItemTes }} item tes
                        </CardDescription>
                    </div>
                    <Button @click="addAspek" variant="outline" size="sm">
                        <Plus class="h-4 w-4 mr-2" />
                        Tambah Aspek
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- Empty state -->
                <div v-if="aspekList.length === 0" class="py-12 text-center text-muted-foreground">
                    <AlertCircle class="h-12 w-12 mx-auto mb-4 opacity-50" />
                    <p>Belum ada aspek. Klik "Tambah Aspek" untuk mulai.</p>
                </div>

                <!-- Aspek List -->
                <div v-for="(aspek, aspekIndex) in aspekList" :key="aspek.tempId || aspek.id" class="space-y-4 border rounded-lg p-4">
                    <!-- Aspek Header -->
                    <div class="flex items-start gap-4">
                        <div class="flex-1 space-y-4">
                            <div class="flex items-center gap-2">
                                <Badge variant="outline">Aspek {{ aspekIndex + 1 }}</Badge>
                                <Label class="text-sm font-medium text-muted-foreground">Urutan: {{ aspek.urutan }}</Label>
                            </div>
                            <div>
                                <Label for="aspek-nama">Nama Aspek *</Label>
                                <Input
                                    id="aspek-nama"
                                    v-model="aspek.nama"
                                    placeholder="Contoh: Kecepatan, Kelentukan, dll"
                                    class="mt-1"
                                    required
                                />
                            </div>
                        </div>
                        <Button
                            @click="removeAspek(aspekIndex)"
                            variant="ghost"
                            size="icon"
                            :disabled="aspekList.length === 1"
                            class="text-destructive"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Item Tes List -->
                    <div class="ml-6 space-y-4 border-l-2 pl-4">
                        <div class="flex items-center justify-between">
                            <Label class="text-sm font-medium">Item Tes ({{ aspek.item_tes.length }})</Label>
                            <Button @click="addItemTes(aspekIndex)" variant="outline" size="sm">
                                <Plus class="h-4 w-4 mr-2" />
                                Tambah Item Tes
                            </Button>
                        </div>

                        <div v-if="aspek.item_tes.length === 0" class="text-sm text-muted-foreground py-4 text-center border rounded">
                            Belum ada item tes. Tambahkan minimal 1 item tes untuk aspek ini.
                        </div>

                        <div
                            v-for="(item, itemIndex) in aspek.item_tes"
                            :key="item.tempId || item.id"
                            class="space-y-3 p-4 border rounded-lg bg-muted/50"
                        >
                            <div class="flex items-start justify-between">
                                <Badge variant="secondary">Item {{ itemIndex + 1 }}</Badge>
                                <Button
                                    @click="removeItemTes(aspekIndex, itemIndex)"
                                    variant="ghost"
                                    size="icon"
                                    :disabled="aspek.item_tes.length === 1"
                                    class="h-6 w-6 text-destructive"
                                >
                                    <X class="h-3 w-3" />
                                </Button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label>Nama Item Tes *</Label>
                                    <Input
                                        v-model="item.nama"
                                        placeholder="Contoh: Sprint 20m, Sit and Reach"
                                        class="mt-1"
                                        required
                                    />
                                </div>
                                <div>
                                    <Label>Satuan</Label>
                                    <Input
                                        v-model="item.satuan"
                                        placeholder="Contoh: detik, cm, repetisi"
                                        class="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label>Target Laki-laki</Label>
                                    <Input
                                        v-model="item.target_laki_laki"
                                        placeholder="Contoh: 3,70"
                                        class="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label>Target Perempuan</Label>
                                    <Input
                                        v-model="item.target_perempuan"
                                        placeholder="Contoh: 3,90"
                                        class="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label>Arah Performa *</Label>
                                    <Select v-model="item.performa_arah" required>
                                        <SelectTrigger class="mt-1">
                                            <SelectValue placeholder="Pilih arah performa" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="max">
                                                Max - Semakin Besar Semakin Baik
                                            </SelectItem>
                                            <SelectItem value="min">
                                                Min - Semakin Kecil Semakin Baik
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <Button @click="handleSaveAsTemplate" variant="outline" :disabled="loading || totalAspek === 0">
                <Save class="h-4 w-4 mr-2" />
                Simpan sebagai Template
            </Button>
            <div class="flex gap-2">
                <Button @click="$emit('cancel')" variant="outline" :disabled="loading">
                    Batal
                </Button>
                <Button @click="handleSave" :disabled="loading || totalAspek === 0">
                    <Loader2 v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
                    <Save v-else class="h-4 w-4 mr-2" />
                    Simpan
                </Button>
            </div>
        </div>

        <!-- Confirm Save Template Dialog -->
        <Dialog v-model:open="showConfirmSaveTemplate">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Simpan sebagai Template?</DialogTitle>
                    <DialogDescription>
                        Simpan sebagai template untuk cabor ini? Template lama akan diganti.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showConfirmSaveTemplate = false" :disabled="loading">
                        Batal
                    </Button>
                    <Button @click="confirmSaveAsTemplate" :disabled="loading">
                        <Loader2 v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
                        Simpan Template
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

