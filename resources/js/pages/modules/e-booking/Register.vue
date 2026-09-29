<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SiBolaMark from '@/components/sibola/SiBolaMark.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { library, type IconName } from '@fortawesome/fontawesome-svg-core';
import { faCircleCheck, faCircleNotch, faClipboardList, faUserPlus } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm } from '@inertiajs/vue3';

library.add(faCircleCheck, faCircleNotch, faClipboardList, faUserPlus);

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

const points: Array<{ icon: IconName; text: string }> = [
    { icon: 'user-plus', text: 'Cukup isi data yang bisa dihubungi pengelola.' },
    { icon: 'circle-check', text: 'Setelah akun jadi, Anda bisa langsung mengirim pengajuan sewa.' },
    { icon: 'clipboard-list', text: 'Status pesanan dan bukti pembayaran tersimpan di satu halaman.' },
];
</script>

<template>
    <SeoHead title="Daftar Penyewa" description="Buat akun penyewa Si Bola, layanan sewa venue UPT Dispora Kabupaten Bogor." />

    <EBookingLayout active="auth">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] lg:items-start">
            <section aria-labelledby="register-title" class="sb-card p-6 sm:p-8">
                <p class="wp-eyebrow">Akun penyewa</p>
                <h1 id="register-title" class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl">Buat akun Si Bola</h1>
                <p class="text-muted-foreground mt-2 max-w-2xl text-sm leading-relaxed">
                    Siapkan email dan nomor HP yang aktif agar pengelola bisa menghubungi Anda bila diperlukan.
                </p>

                <form class="mt-8 grid gap-5 sm:grid-cols-2" @submit.prevent="submit">
                    <div class="sm:col-span-2">
                        <label class="sb-label" for="nama">Nama lengkap</label>
                        <input
                            id="nama"
                            v-model="form.nama"
                            type="text"
                            name="nama"
                            required
                            autocomplete="name"
                            class="sb-input"
                            :aria-invalid="form.errors.nama ? 'true' : undefined"
                            :aria-describedby="form.errors.nama ? 'nama-error' : undefined"
                        />
                        <p v-if="form.errors.nama" id="nama-error" class="sb-error">{{ form.errors.nama }}</p>
                    </div>
                    <div>
                        <label class="sb-label" for="email">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            name="email"
                            required
                            autocomplete="email"
                            inputmode="email"
                            class="sb-input"
                            :aria-invalid="form.errors.email ? 'true' : undefined"
                            :aria-describedby="form.errors.email ? 'email-error' : undefined"
                        />
                        <p v-if="form.errors.email" id="email-error" class="sb-error">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="sb-label" for="no_hp">Nomor HP</label>
                        <input
                            id="no_hp"
                            v-model="form.no_hp"
                            type="text"
                            name="no_hp"
                            required
                            autocomplete="tel"
                            inputmode="tel"
                            class="sb-input tabular-nums"
                            :aria-invalid="form.errors.no_hp ? 'true' : undefined"
                            :aria-describedby="form.errors.no_hp ? 'no_hp-error' : undefined"
                        />
                        <p v-if="form.errors.no_hp" id="no_hp-error" class="sb-error">{{ form.errors.no_hp }}</p>
                    </div>
                    <div>
                        <label class="sb-label" for="password">Kata sandi</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            name="password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            class="sb-input"
                            :aria-invalid="form.errors.password ? 'true' : undefined"
                            :aria-describedby="form.errors.password ? 'password-hint password-error' : 'password-hint'"
                        />
                        <p id="password-hint" class="sb-hint">Minimal 8 karakter.</p>
                        <p v-if="form.errors.password" id="password-error" class="sb-error">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="sb-label" for="password_confirmation">Ulangi kata sandi</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            class="sb-input"
                        />
                    </div>
                    <div>
                        <label class="sb-label" for="instansi">
                            Instansi
                            <span class="text-muted-foreground font-normal">(boleh dikosongkan)</span>
                        </label>
                        <input id="instansi" v-model="form.instansi" type="text" name="instansi" autocomplete="organization" class="sb-input" />
                    </div>
                    <div>
                        <label class="sb-label" for="kategori_default">Jenis pemohon</label>
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
                    <div class="pt-2 sm:col-span-2">
                        <button type="submit" class="wp-btn wp-btn-primary w-full px-5 py-3 text-sm" :disabled="form.processing">
                            <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'circle-notch']" class="size-4 animate-spin" aria-hidden="true" />
                            Buat akun sekarang
                        </button>
                    </div>
                </form>

                <p class="text-muted-foreground mt-6 text-center text-sm">
                    Sudah punya akun?
                    <Link
                        :href="route('e-booking.login')"
                        class="rounded-sm font-semibold text-(--wp-accent-strong) underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                    >
                        Masuk di sini
                    </Link>
                </p>
            </section>

            <aside aria-labelledby="register-info-title" class="sb-card-muted p-6 sm:p-8 lg:sticky lg:top-24">
                <SiBolaMark class="size-10" />
                <h2 id="register-info-title" class="mt-5 text-lg font-semibold tracking-tight">Satu akun untuk semua pengajuan</h2>
                <p class="text-muted-foreground mt-2 text-sm leading-relaxed">
                    Si Bola melayani sewa venue milik Pemkab Bogor yang dikelola UPT Dispora.
                </p>
                <ul class="mt-6 space-y-4">
                    <li v-for="point in points" :key="point.icon" class="flex items-start gap-3">
                        <span class="wp-icon size-8 shrink-0">
                            <FontAwesomeIcon :icon="['fas', point.icon]" class="size-3.5" aria-hidden="true" />
                        </span>
                        <span class="pt-1.5 text-sm leading-relaxed">{{ point.text }}</span>
                    </li>
                </ul>
            </aside>
        </div>
    </EBookingLayout>
</template>
