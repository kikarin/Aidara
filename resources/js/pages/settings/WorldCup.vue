<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { WorldCupApiStatus, WorldCupSettings } from '@/types/worldcup';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { watch } from 'vue';
import { useToast } from '@/components/ui/toast/useToast';

const props = defineProps<{
    settings: WorldCupSettings;
    apiStatus: WorldCupApiStatus;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Live Score Piala Dunia',
        href: '/dashboard/settings/worldcup',
    },
];

const { toast } = useToast();
const page = usePage();

const form = useForm({
    enabled: props.settings.enabled,
    show_on_landing: props.settings.show_on_landing,
    preview_count: props.settings.preview_count,
    section_title: props.settings.section_title,
});

watch(
    () => page.props.flash,
    (flash) => {
        const message = (flash as { success?: string })?.success;
        if (message) {
            toast({ title: 'Berhasil', description: message });
        }
    },
    { deep: true, immediate: true },
);

const submit = () => {
    form.put(route('settings.worldcup.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Live Score Piala Dunia" />

        <div class="mx-auto max-w-3xl space-y-6 p-4 lg:p-6">
            <HeadingSmall
                title="Live Score Piala Dunia 2026"
                description="Aktifkan atau nonaktifkan widget jadwal dan skor Piala Dunia di landing page AIDARA."
            />

            <Card>
                <CardHeader>
                    <CardTitle>Status API</CardTitle>
                    <CardDescription>Informasi koneksi ke worldcup26.ir</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-muted-foreground">Token API dikonfigurasi</span>
                        <span :class="apiStatus.token_configured ? 'text-green-600' : 'text-red-600'">
                            {{ apiStatus.token_configured ? 'Ya' : 'Belum' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-muted-foreground">Health check</span>
                        <span :class="apiStatus.healthy ? 'text-green-600' : 'text-red-600'">
                            {{ apiStatus.healthy ? 'Sehat' : 'Tidak tersedia' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-muted-foreground">Jumlah pertandingan di cache</span>
                        <span class="font-medium">{{ apiStatus.games_count }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-muted-foreground">Cache TTL (live / games)</span>
                        <span class="font-medium">{{ apiStatus.cache_ttl_seconds }} detik</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-muted-foreground">Cache TTL (teams / stadiums)</span>
                        <span class="font-medium">{{ apiStatus.cache_ttl_static_seconds }} detik</span>
                    </div>
                </CardContent>
            </Card>

            <form @submit.prevent="submit" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Pengaturan Tampilan</CardTitle>
                        <CardDescription>Kontrol visibilitas fitur World Cup di website publik</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4">
                            <Checkbox v-model="form.enabled" />
                            <span>
                                <span class="block font-medium">Aktifkan Live Score Piala Dunia</span>
                                <span class="text-muted-foreground text-sm">
                                    Jika dinonaktifkan, section landing dan halaman /piala-dunia tidak dapat diakses.
                                </span>
                            </span>
                        </label>

                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4">
                            <Checkbox v-model="form.show_on_landing" :disabled="!form.enabled" />
                            <span>
                                <span class="block font-medium">Tampilkan di Landing Page</span>
                                <span class="text-muted-foreground text-sm">
                                    Menampilkan preview pertandingan knockout mendatang di halaman beranda.
                                </span>
                            </span>
                        </label>

                        <div class="grid gap-2">
                            <Label for="preview_count">Jumlah pertandingan preview</Label>
                            <Input
                                id="preview_count"
                                type="number"
                                min="1"
                                max="20"
                                v-model.number="form.preview_count"
                                :disabled="!form.enabled"
                            />
                            <InputError :message="form.errors.preview_count" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="section_title">Judul section landing</Label>
                            <Input
                                id="section_title"
                                type="text"
                                maxlength="100"
                                v-model="form.section_title"
                                :disabled="!form.enabled"
                            />
                            <InputError :message="form.errors.section_title" />
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Simpan Pengaturan
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
