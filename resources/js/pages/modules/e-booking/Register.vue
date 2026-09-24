<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    nama: '',
    email: '',
    password: '',
    password_confirmation: '',
    no_hp: '',
    nik: '',
    alamat: '',
    instansi: '',
    kategori_default: 'non_pemerintah',
    device_name: 'booking-web',
});

const submit = () => {
    form.post(route('e-booking.register.store'));
};
</script>

<template>
    <SeoHead title="Daftar Penyewa" description="Buat akun penyewa E-Booking Fasilitas UPT Dispora." />

    <EBookingLayout active="auth">
        <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[0.85fr_1.15fr]">
            <section class="rounded-[32px] bg-[linear-gradient(135deg,#0f3d87_0%,#1479d1_58%,#44b6ff_100%)] p-7 text-white shadow-[0_20px_60px_rgba(20,121,209,0.25)] sm:p-8">
                <p class="text-sm font-semibold text-white/80">Buat akun dengan cepat</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight">Supaya proses booking, pembayaran, dan pelacakan pesanan jadi lebih mudah.</h1>
                <div class="mt-6 space-y-3 text-sm text-white/90">
                    <p>• Cukup isi data penting yang bisa dihubungi.</p>
                    <p>• Setelah akun jadi, Anda bisa langsung kirim pengajuan.</p>
                    <p>• Bukti pembayaran dan status pesanan ada di satu halaman.</p>
                </div>
            </section>

            <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-2xl font-bold text-slate-900">Buat akun</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Siapkan email dan nomor HP yang aktif agar pengelola mudah menghubungi Anda bila diperlukan.
                </p>

                <form class="mt-8 grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-800" for="nama">Nama lengkap</label>
                        <input
                            id="nama"
                            v-model="form.nama"
                            type="text"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        />
                        <InputError :message="form.errors.nama" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-800" for="email">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-800" for="no_hp">Nomor HP</label>
                        <input
                            id="no_hp"
                            v-model="form.no_hp"
                            type="text"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        />
                        <InputError :message="form.errors.no_hp" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-800" for="password">Kata sandi</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            minlength="8"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-800" for="password_confirmation">Ulangi kata sandi</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-800" for="instansi">Instansi (boleh dikosongkan)</label>
                        <input
                            id="instansi"
                            v-model="form.instansi"
                            type="text"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-800" for="kategori_default">Jenis pemohon</label>
                        <SimpleSelect
                            id="kategori_default"
                            v-model="form.kategori_default"
                            :options="[
                                { value: 'non_pemerintah', label: 'Umum / non pemerintah' },
                                { value: 'pemerintah', label: 'Instansi pemerintah' },
                            ]"
                            placeholder="Pilih jenis pemohon"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-[var(--brand-green,#2e7d32)] px-5 py-3 text-sm font-semibold text-white disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            Buat akun sekarang
                        </button>
                    </div>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Sudah punya akun?
                    <Link :href="route('e-booking.login')" class="font-semibold text-[var(--brand-green,#2e7d32)] hover:underline">
                        Masuk di sini
                    </Link>
                </p>
            </section>
        </div>
    </EBookingLayout>
</template>
