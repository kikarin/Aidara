<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/toast/useToast';
import { router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import Step1SelectPeserta from './Step1SelectPeserta.vue';

const { toast } = useToast();

const showConfirmDialog = ref(false);

const props = defineProps<{
    step: number;
    registration?: {
        id: number;
        peserta_type: string;
        step_current: number;
        data_json: Record<string, any>;
        status: string;
    } | null;
    registrationData?: Record<string, any>;
    kecamatanOptions?: Array<{ id: number; nama: string }>;
    kategoriPesertaOptions?: Array<{ id: number; nama: string }>;
    parameterUmumMaster?: Array<any>;
}>();

const currentStep = computed(() => props.step || 1);
const registrationData = computed(() => props.registrationData || {});
const kecamatanOptions = computed(() => props.kecamatanOptions || []);
const kategoriPesertaOptions = computed(() => props.kategoriPesertaOptions || []);
type OptionItem = { id: number; nama: string };
type KategoriOption = { id: number; nama: string } | { value: number; label: string };

const resolveKategoriOption = (opt: KategoriOption) => ({
    value: 'value' in opt ? opt.value : opt.id,
    label: 'label' in opt ? opt.label : opt.nama,
});

const kecamatanMap = computed<Record<number, string>>(() => {
    const map: Record<number, string> = {};
    (props.kecamatanOptions || []).forEach((item: OptionItem) => {
        map[item.id] = item.nama;
    });
    return map;
});

const selectedPesertaType = ref<string | null>(props.registration?.peserta_type || registrationData.value?.step_1?.peserta_type || null);

watch(
    () => registrationData.value?.step_1?.peserta_type,
    (val) => {
        if (val) {
            selectedPesertaType.value = val;
        }
    },
    { immediate: true },
);

// Auto-save draft ke localStorage
const saveDraftToLocalStorage = (step: number, data: any) => {
    try {
        const key = `registration_draft_step_${step}`;
        localStorage.setItem(key, JSON.stringify(data));
    } catch (e) {
        console.error('Error saving draft to localStorage:', e);
    }
};

const loadDraftFromLocalStorage = (step: number): any => {
    try {
        const key = `registration_draft_step_${step}`;
        const data = localStorage.getItem(key);
        return data ? JSON.parse(data) : null;
    } catch (e) {
        console.error('Error loading draft from localStorage:', e);
        return null;
    }
};

const clearDraftFromLocalStorage = () => {
    try {
        for (let i = 1; i <= 5; i++) {
            localStorage.removeItem(`registration_draft_step_${i}`);
        }
    } catch (e) {
        console.error('Error clearing draft from localStorage:', e);
    }
};

// Handle step navigation
const goToStep = (step: number) => {
    router.visit(route('registration.steps', { step }), {
        preserveState: true,
        preserveScroll: true,
    });
};

type StepPayload = Record<string, any>;

const finalizeRegistration = () => {
    const form = useForm({});
    form.post(route('registration.steps.submit'), {
        onSuccess: () => {
            clearDraftFromLocalStorage();
            router.visit(route('registration.success'));
        },
    });
};

const handleStepSubmit = async (step: number, data: StepPayload) => {
    console.log(`Steps/Index: handleStepSubmit called for step ${step}`, data);

    // Save draft to localStorage (without files)
    const draftData = { ...data };
    if (step === 3 && draftData.sertifikat) {
        draftData.sertifikat = draftData.sertifikat.map((s: any) => ({
            ...s,
            file: null, // Don't save file to localStorage
        }));
    }
    if (step === 5 && draftData.dokumen) {
        draftData.dokumen = draftData.dokumen.map((d: any) => ({
            ...d,
            file: null, // Don't save file to localStorage
        }));
    }
    saveDraftToLocalStorage(step, draftData);

    // Prepare form data - handle file uploads properly
    // Inertia will automatically convert to FormData when files are present
    const form = useForm(data);
    const routeName = `registration.steps.${step}` as any;

    console.log(`Steps/Index: Submitting to route ${routeName}`, form.data());

    form.post(route(routeName), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page: any) => {
            console.log(`Steps/Index: Step ${step} submitted successfully`, page);
            // Clear draft after successful submit
            localStorage.removeItem(`registration_draft_step_${step}`);

            // Step 1 sekarang langsung redirect ke edit page, tidak ke step 2
            if (step === 1) {
                // Backend akan handle redirect ke edit page
                // Tidak perlu navigate manual karena backend sudah redirect
                return;
            }

            if (step < 5) {
                goToStep(step + 1);
            } else {
                // Show confirmation dialog before finalizing registration
                showConfirmDialog.value = true;
            }
        },
        onError: (errors: Record<string, string | string[]>) => {
            console.error('Steps/Index: Validation errors:', errors);
            console.error('Steps/Index: Error keys:', Object.keys(errors));
            console.error('Steps/Index: Error values:', Object.values(errors));
            
            // Extract error messages
            const errorMessages: string[] = [];
            Object.entries(errors).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    errorMessages.push(...value);
                } else if (typeof value === 'string') {
                    errorMessages.push(value);
                } else {
                    errorMessages.push(`${key}: ${JSON.stringify(value)}`);
                }
            });
            
            const errorMessage = errorMessages.length > 0 
                ? errorMessages.join(', ') 
                : 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
            
            toast({
                title: 'Terjadi kesalahan',
                description: errorMessage,
                variant: 'destructive',
            });
        },
    });
};

const confirmFinalize = () => {
    showConfirmDialog.value = false;
    finalizeRegistration();
};

const handleSkip = (step: number) => {
    // Save empty data untuk step yang di-skip
    saveDraftToLocalStorage(step, {});
    if (step >= 5) {
        // Show confirmation dialog before finalizing registration
        showConfirmDialog.value = true;
    } else {
        goToStep(step + 1);
    }
};

const handleBack = () => {
    if (currentStep.value > 1) {
        goToStep(currentStep.value - 1);
    }
};


const handleStep1Submit = (data: { peserta_type: string }) => {
    selectedPesertaType.value = data.peserta_type;
    handleStepSubmit(1, data);
};

const handleStep2Submit = (data: StepPayload) => handleStepSubmit(2, data);
const handleStep3Submit = (data: StepPayload) => handleStepSubmit(3, data);
const handleStep4Submit = (data: StepPayload) => handleStepSubmit(4, data);
const handleStep5Submit = (data: StepPayload) => handleStepSubmit(5, data);

const resolvedRegistrationData = computed(() => {
    const data = { ...registrationData.value };
    if (data.step_2) {
        const step2 = { ...data.step_2 };
        if (step2.kecamatan_id && kecamatanMap.value[Number(step2.kecamatan_id)]) {
            step2.kecamatan_name = kecamatanMap.value[Number(step2.kecamatan_id)];
        }
        if (Array.isArray(step2.kategori_pesertas) && step2.kategori_pesertas.length) {
            step2.kategori_peserta_labels = step2.kategori_pesertas
                .map((id: number | string) => {
                    const option = (props.kategoriPesertaOptions || []).find(
                        (opt: KategoriOption) => Number(resolveKategoriOption(opt).value) === Number(id),
                    );
                    return option ? resolveKategoriOption(option).label : `ID ${id}`;
                })
                .filter(Boolean);
        }
        data.step_2 = step2;
    }
    return data;
});

// Load draft on mount
onMounted(() => {
    const draft = loadDraftFromLocalStorage(currentStep.value);
    if (draft && !registrationData.value[`step_${currentStep.value}`]) {
        // Auto-fill dari draft jika belum ada data dari server
        console.log('Loaded draft for step', currentStep.value, draft);
    }
});

// Clear draft setelah submit final
watch(
    () => props.registration?.status,
    (newStatus) => {
        if (newStatus === 'submitted') {
            clearDraftFromLocalStorage();
        }
    },
);
</script>

<template>
    <!-- Untuk step 1, gunakan layout sederhana tanpa step wizard -->
    <div v-if="currentStep === 1" class="bg-background min-h-screen">
        <div class="mx-auto max-w-4xl px-4 py-8">
            <div class="bg-card border-border rounded-xl border p-6 shadow-sm">
                <Step1SelectPeserta :registration-data="resolvedRegistrationData" @submit="handleStep1Submit" />
            </div>
        </div>
    </div>
    <!-- Untuk step lainnya, gunakan layout dengan step wizard -->
    <RegistrationLayout v-else :current-step="currentStep" :total-steps="5">
        <div class="space-y-6">
            <!-- Step Content -->
            <div>
                <Step2DataDiri
                    v-if="currentStep === 2"
                    :peserta-type="registration?.peserta_type"
                    :selected-peserta-type="selectedPesertaType"
                    :registration-data="resolvedRegistrationData"
                    :kecamatan-options="kecamatanOptions"
                    :kategori-peserta-options="kategoriPesertaOptions"
                    :parameter-umum-master="parameterUmumMaster"
                    @submit="handleStep2Submit"
                />

                <Step3Sertifikat
                    v-else-if="currentStep === 3"
                    :peserta-type="registration?.peserta_type"
                    :registration-data="resolvedRegistrationData"
                    @submit="handleStep3Submit"
                    @skip="() => handleSkip(3)"
                />

                <Step4Prestasi
                    v-else-if="currentStep === 4"
                    :peserta-type="registration?.peserta_type"
                    :registration-data="resolvedRegistrationData"
                    @submit="handleStep4Submit"
                    @skip="() => handleSkip(4)"
                />

                <Step5Dokumen
                    v-else-if="currentStep === 5"
                    :peserta-type="registration?.peserta_type"
                    :registration-data="resolvedRegistrationData"
                    @submit="handleStep5Submit"
                    @skip="() => handleSkip(5)"
                />
            </div>

            <!-- Navigation Buttons -->
            <div class="flex items-center justify-between border-t pt-6">
                <Button v-if="currentStep > 1" type="button" variant="outline" @click="handleBack">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Kembali
                </Button>
                <div v-else></div>

                <div class="flex gap-2">
                    <Button v-if="currentStep >= 3 && currentStep <= 5" type="button" variant="ghost" @click="handleSkip(currentStep)">
                        Lewati
                    </Button>
                </div>
            </div>
        </div>

        <!-- Konfirmasi Pengajuan Dialog -->
        <Dialog v-model:open="showConfirmDialog">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Konfirmasi Pengajuan Registrasi</DialogTitle>
                    <DialogDescription class="space-y-3 pt-2">
                        <p>Apakah Anda yakin ingin mengajukan registrasi ini?</p>
                        <div class="bg-muted rounded-lg p-3 text-sm">
                            <p class="mb-2 font-medium">Dengan mengajukan registrasi, Anda menyetujui bahwa:</p>
                            <ul class="text-muted-foreground list-inside list-disc space-y-1">
                                <li>Semua data yang diberikan adalah benar dan dapat dipertanggungjawabkan</li>
                                <li>Data yang salah atau tidak valid dapat menyebabkan pengajuan ditolak</li>
                                <li>Data ini akan digunakan untuk keperluan administrasi dan verifikasi peserta</li>
                            </ul>
                        </div>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showConfirmDialog = false"> Batal </Button>
                    <Button @click="confirmFinalize"> Ya, Ajukan Pendaftaran </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </RegistrationLayout>
</template>
