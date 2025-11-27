<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowRight, HandHeart, HeartHandshake, UserCircle2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    registrationData?: Record<string, any>;
}>();

const emit = defineEmits<{
    submit: [data: { peserta_type: string }];
}>();

const selectedType = ref<string>(props.registrationData?.step_1?.peserta_type || '');

watch(
    () => props.registrationData?.step_1?.peserta_type,
    (val) => {
        if (val && val !== selectedType.value) {
            selectedType.value = val;
        }
    },
);

const pesertaTypes = [
    {
        value: 'atlet',
        label: 'Atlet',
        description: 'Daftar sebagai atlet/peserta olahraga',
        icon: UserCircle2,
    },
    {
        value: 'pelatih',
        label: 'Pelatih',
        description: 'Daftar sebagai pelatih/coach',
        icon: HandHeart,
    },
    {
        value: 'tenaga_pendukung',
        label: 'Tenaga Pendukung',
        description: 'Daftar sebagai tenaga pendukung',
        icon: HeartHandshake,
    },
];

const canSubmit = computed(() => selectedType.value !== '');

const handleSubmit = () => {
    if (canSubmit.value) {
        emit('submit', { peserta_type: selectedType.value });
    }
};
</script>

<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold">Pilih Jenis Peserta</h2>
            <p class="text-muted-foreground mt-2">
                Pilih jenis peserta yang sesuai dengan Anda. Setelah memilih, Anda akan diarahkan ke halaman edit profil untuk melengkapi data diri Anda.
            </p>
            <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <p class="text-sm text-blue-800">
                    <strong>Catatan:</strong> Setelah memilih jenis peserta, Anda akan langsung diarahkan ke halaman edit profil. 
                    Silakan lengkapi data diri Anda dan tunggu persetujuan dari administrator sebelum dapat mengakses fitur lainnya.
                </p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card
                v-for="type in pesertaTypes"
                :key="type.value"
                class="cursor-pointer rounded-xl border transition-all bg-primary-foreground"
                :class="selectedType === type.value ? 'border-primary ring-primary shadow-md ring-2' : 'hover:bg-muted/50'"
                @click="selectedType = type.value"
            >
                <CardHeader class="text-center">
                    <component
                        :is="type.icon"
                        class="mx-auto mb-2 h-12 w-12 transition-colors"
                        :class="selectedType === type.value ? 'text-primary' : 'text-muted-foreground'"
                    />
                    <CardTitle class="text-foreground">{{ type.label }}</CardTitle>
                    <CardDescription>{{ type.description }}</CardDescription>
                </CardHeader>

                <CardContent>
                    <div class="flex items-center justify-center">
                        <div
                            class="flex h-5 w-5 items-center justify-center rounded-full border-2 transition-colors"
                            :class="selectedType === type.value ? 'border-primary bg-primary' : 'border-muted'"
                        >
                            <div v-if="selectedType === type.value" class="h-2 w-2 rounded-full bg-white" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="flex justify-end">
            <Button @click="handleSubmit" :disabled="!canSubmit" size="lg">
                Lanjutkan
                <ArrowRight class="ml-2 h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
