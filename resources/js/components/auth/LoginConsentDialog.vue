<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Link } from '@inertiajs/vue3';
import { LoaderCircle, ShieldCheck } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    loading?: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();

const agreed = ref(false);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            agreed.value = false;
        }
    },
);

const close = () => emit('update:open', false);

const handleConfirm = () => {
    if (!agreed.value || props.loading) return;
    emit('confirm');
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-[400px] gap-0 overflow-hidden rounded-2xl p-0">
            <div class="border-b border-border bg-gradient-to-br from-primary/10 via-background to-background px-6 pt-6 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                        <ShieldCheck class="h-5 w-5 text-primary" />
                    </div>
                    <DialogHeader class="space-y-1 text-left">
                        <DialogTitle class="text-base">Persetujuan Pengguna</DialogTitle>
                        <DialogDescription class="text-xs">
                            Baca dan setujui kebijakan sebelum masuk ke Aidara.
                        </DialogDescription>
                    </DialogHeader>
                </div>
            </div>

            <div class="max-h-[50vh] space-y-4 overflow-y-auto px-6 py-4">
                <div class="space-y-3 text-sm leading-relaxed text-muted-foreground">
                    <p>
                        Aidara memproses data pribadi Anda (identitas, data olahraga, pemeriksaan, dan dokumen)
                        untuk keperluan manajemen keolahragaan Dispora Kabupaten Bogor.
                    </p>
                    <p>
                        Dengan melanjutkan, Anda menyetujui pemrosesan data sesuai
                        <strong class="font-medium text-foreground">UU Perlindungan Data Pribadi</strong>
                        dan kebijakan berikut:
                    </p>
                </div>

                <ul class="space-y-2 text-sm">
                    <li>
                        <Link :href="route('legal.show', { slug: 'terms' })" class="font-medium text-primary hover:underline">
                            Syarat & Ketentuan
                        </Link>
                        <span class="text-muted-foreground"> — aturan penggunaan layanan Aidara</span>
                    </li>
                    <li>
                        <Link :href="route('legal.show', { slug: 'privacy' })" class="font-medium text-primary hover:underline">
                            Kebijakan Privasi
                        </Link>
                        <span class="text-muted-foreground"> — cara data pribadi dikumpulkan dan digunakan</span>
                    </li>
                    <li>
                        <Link :href="route('legal.show', { slug: 'pdp' })" class="font-medium text-primary hover:underline">
                            Perlindungan Data Pribadi (PDP)
                        </Link>
                        <span class="text-muted-foreground"> — hak dan kewajiban terkait data pribadi</span>
                    </li>
                </ul>

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-muted/40 p-3">
                    <Checkbox id="login-consent" v-model="agreed" class="mt-0.5" />
                    <span class="text-sm leading-relaxed text-foreground">
                        Saya telah membaca dan menyetujui
                        <span class="font-medium">Syarat & Ketentuan</span>,
                        <span class="font-medium">Kebijakan Privasi</span>, dan
                        <span class="font-medium">Perlindungan Data Pribadi (PDP)</span>.
                    </span>
                </label>
            </div>

            <DialogFooter class="gap-2 border-t border-border px-6 py-4 sm:justify-stretch">
                <Button type="button" variant="outline" class="flex-1" :disabled="loading" @click="close">
                    Batal
                </Button>
                <Button type="button" class="flex-1" :disabled="!agreed || loading" @click="handleConfirm">
                    <LoaderCircle v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
                    Lanjutkan Masuk
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
