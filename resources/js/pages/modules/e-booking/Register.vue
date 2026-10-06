<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SbPitchLines from '@/components/sibola/SbPitchLines.vue';
import SiBolaMark from '@/components/sibola/SiBolaMark.vue';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { library, type IconName } from '@fortawesome/fontawesome-svg-core';
import {
    faBuildingColumns,
    faCheck,
    faCircleCheck,
    faCircleNotch,
    faClipboardList,
    faEye,
    faEyeSlash,
    faUserGroup,
    faUserPlus,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

library.add(faBuildingColumns, faCheck, faCircleCheck, faCircleNotch, faClipboardList, faEye, faEyeSlash, faUserGroup, faUserPlus);

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

const showPassword = ref(false);

const passwordScore = computed(() => {
    const value = form.password;
    if (!value) return 0;

    let score = value.length >= 8 ? 1 : 0;
    if (value.length >= 12) score++;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
    if (/\d/.test(value) || /[^A-Za-z0-9]/.test(value)) score++;

    return Math.min(score, 4);
});

const passwordLabel = computed(() => ['Terlalu pendek', 'Cukup', 'Lumayan kuat', 'Kuat', 'Sangat kuat'][passwordScore.value]);

const passwordsMatch = computed(() => form.password_confirmation !== '' && form.password === form.password_confirmation);
const passwordsMismatch = computed(() => form.password_confirmation !== '' && form.password !== form.password_confirmation);

const kategoriOptions: Array<{ value: string; title: string; body: string; icon: IconName }> = [
    { value: 'non_pemerintah', title: 'Umum', body: 'Perorangan, komunitas, klub, sekolah swasta, atau perusahaan.', icon: 'user-group' },
    { value: 'pemerintah', title: 'Instansi pemerintah', body: 'Dinas, kecamatan, desa, atau lembaga pemerintah lain.', icon: 'building-columns' },
];

const cardName = computed(() => form.nama.trim() || 'Nama Anda');
const cardKategori = computed(() => (form.kategori_default === 'pemerintah' ? 'Instansi pemerintah' : 'Umum'));
const cardInitial = computed(() => form.nama.trim().charAt(0).toUpperCase() || 'S');

const sections = computed(() => [
    { label: 'Data diri', done: form.nama.trim() !== '' },
    { label: 'Kontak', done: form.email.trim() !== '' && form.no_hp.trim() !== '' },
    { label: 'Keamanan', done: form.password.length >= 8 && passwordsMatch.value },
    { label: 'Jenis pemohon', done: form.kategori_default !== '' },
]);

const progress = computed(() => sections.value.filter((section) => section.done).length);

const points: Array<{ icon: IconName; text: string }> = [
    { icon: 'user-plus', text: 'Satu akun untuk semua venue Pemkab Bogor yang dikelola UPT Dispora.' },
    { icon: 'circle-check', text: 'Begitu akun jadi, Anda bisa langsung mengirim pengajuan sewa.' },
    { icon: 'clipboard-list', text: 'Status pengajuan, surat, dan bukti bayar tersimpan di satu tempat.' },
];
</script>

<template>
    <SeoHead title="Daftar Penyewa" description="Buat akun penyewa Si Bola, layanan sewa venue UPT Dispora Kabupaten Bogor." />

    <EBookingLayout active="auth">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)] lg:items-start">
            <section aria-labelledby="register-title" class="sb-card p-6 sm:p-10">
                <p class="wp-eyebrow">Akun penyewa</p>
                <h1 id="register-title" class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl">Buat akun Si Bola</h1>
                <p class="text-muted-foreground mt-3 max-w-xl text-sm leading-relaxed">
                    Pakai email dan nomor WhatsApp yang aktif. Pengelola mengirim undangan meeting dan surat resmi lewat dua kontak ini.
                </p>

                <div class="mt-8" aria-hidden="true">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-muted-foreground">Kelengkapan data</span>
                        <span class="font-semibold tabular-nums">{{ progress }}/4</span>
                    </div>
                    <div class="mt-2 grid grid-cols-4 gap-1.5">
                        <span
                            v-for="section in sections"
                            :key="section.label"
                            class="h-1.5 rounded-full transition-colors duration-500"
                            :class="section.done ? 'bg-(--wp-accent)' : 'bg-muted'"
                        ></span>
                    </div>
                </div>

                <form class="mt-8 space-y-10" @submit.prevent="submit">
                    <fieldset class="grid gap-5 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                        <legend class="contents">
                            <span
                                class="grid size-10 place-items-center rounded-full text-sm font-bold tabular-nums transition-colors"
                                :class="
                                    sections[0].done
                                        ? 'bg-(--wp-accent) text-(--wp-accent-contrast)'
                                        : 'bg-(--wp-accent-soft) text-(--wp-accent-strong)'
                                "
                                aria-hidden="true"
                            >
                                <FontAwesomeIcon v-if="sections[0].done" :icon="['fas', 'check']" class="size-3.5" />
                                <template v-else>1</template>
                            </span>
                        </legend>
                        <div class="space-y-5">
                            <h2 class="pt-2 font-semibold tracking-tight">Data diri</h2>
                            <div>
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
                                <label class="sb-label" for="instansi">
                                    Instansi atau organisasi
                                    <span class="text-muted-foreground font-normal">(boleh dikosongkan)</span>
                                </label>
                                <input
                                    id="instansi"
                                    v-model="form.instansi"
                                    type="text"
                                    name="instansi"
                                    autocomplete="organization"
                                    placeholder="Contoh: Klub Sepak Bola Cibinong"
                                    class="sb-input"
                                    :aria-invalid="form.errors.instansi ? 'true' : undefined"
                                />
                                <p v-if="form.errors.instansi" class="sb-error">{{ form.errors.instansi }}</p>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="grid gap-5 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                        <legend class="contents">
                            <span
                                class="grid size-10 place-items-center rounded-full text-sm font-bold tabular-nums transition-colors"
                                :class="
                                    sections[1].done
                                        ? 'bg-(--wp-accent) text-(--wp-accent-contrast)'
                                        : 'bg-(--wp-accent-soft) text-(--wp-accent-strong)'
                                "
                                aria-hidden="true"
                            >
                                <FontAwesomeIcon v-if="sections[1].done" :icon="['fas', 'check']" class="size-3.5" />
                                <template v-else>2</template>
                            </span>
                        </legend>
                        <div class="space-y-5">
                            <h2 class="pt-2 font-semibold tracking-tight">Kontak</h2>
                            <div class="grid gap-5 sm:grid-cols-2">
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
                                    <label class="sb-label" for="no_hp">Nomor WhatsApp</label>
                                    <input
                                        id="no_hp"
                                        v-model="form.no_hp"
                                        type="tel"
                                        name="no_hp"
                                        required
                                        autocomplete="tel"
                                        inputmode="tel"
                                        placeholder="081234567890"
                                        class="sb-input tabular-nums"
                                        :aria-invalid="form.errors.no_hp ? 'true' : undefined"
                                        :aria-describedby="form.errors.no_hp ? 'no_hp-hint no_hp-error' : 'no_hp-hint'"
                                    />
                                    <p id="no_hp-hint" class="sb-hint">Untuk undangan meeting dan surat resmi.</p>
                                    <p v-if="form.errors.no_hp" id="no_hp-error" class="sb-error">{{ form.errors.no_hp }}</p>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="grid gap-5 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                        <legend class="contents">
                            <span
                                class="grid size-10 place-items-center rounded-full text-sm font-bold tabular-nums transition-colors"
                                :class="
                                    sections[2].done
                                        ? 'bg-(--wp-accent) text-(--wp-accent-contrast)'
                                        : 'bg-(--wp-accent-soft) text-(--wp-accent-strong)'
                                "
                                aria-hidden="true"
                            >
                                <FontAwesomeIcon v-if="sections[2].done" :icon="['fas', 'check']" class="size-3.5" />
                                <template v-else>3</template>
                            </span>
                        </legend>
                        <div class="space-y-5">
                            <h2 class="pt-2 font-semibold tracking-tight">Keamanan</h2>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="sb-label" for="password">Kata sandi</label>
                                    <div class="relative">
                                        <input
                                            id="password"
                                            v-model="form.password"
                                            :type="showPassword ? 'text' : 'password'"
                                            name="password"
                                            required
                                            minlength="8"
                                            autocomplete="new-password"
                                            class="sb-input pr-11"
                                            :aria-invalid="form.errors.password ? 'true' : undefined"
                                            :aria-describedby="form.errors.password ? 'password-hint password-error' : 'password-hint'"
                                        />
                                        <button
                                            type="button"
                                            class="text-muted-foreground hover:text-foreground absolute top-1/2 right-1.5 grid size-8 -translate-y-1/2 place-items-center rounded-lg transition-colors focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                            :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                            :aria-pressed="showPassword"
                                            @click="showPassword = !showPassword"
                                        >
                                            <FontAwesomeIcon
                                                :icon="['fas', showPassword ? 'eye-slash' : 'eye']"
                                                class="size-3.5"
                                                aria-hidden="true"
                                            />
                                        </button>
                                    </div>
                                    <div class="mt-2 grid grid-cols-4 gap-1" aria-hidden="true">
                                        <span
                                            v-for="n in 4"
                                            :key="n"
                                            class="h-1 rounded-full transition-colors duration-300"
                                            :class="n <= passwordScore ? (passwordScore <= 1 ? 'bg-(--sb-warning)' : 'bg-(--wp-accent)') : 'bg-muted'"
                                        ></span>
                                    </div>
                                    <p id="password-hint" class="sb-hint" aria-live="polite">
                                        {{ form.password ? passwordLabel : 'Minimal 8 karakter.' }}
                                    </p>
                                    <p v-if="form.errors.password" id="password-error" class="sb-error">{{ form.errors.password }}</p>
                                </div>
                                <div>
                                    <label class="sb-label" for="password_confirmation">Ulangi kata sandi</label>
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        :type="showPassword ? 'text' : 'password'"
                                        name="password_confirmation"
                                        required
                                        autocomplete="new-password"
                                        class="sb-input"
                                        :aria-invalid="passwordsMismatch ? 'true' : undefined"
                                        aria-describedby="password-match"
                                    />
                                    <p id="password-match" class="sb-hint" aria-live="polite">
                                        <span v-if="passwordsMatch" class="inline-flex items-center gap-1 text-(--wp-accent-strong)">
                                            <FontAwesomeIcon :icon="['fas', 'check']" class="size-3" aria-hidden="true" />
                                            Kata sandi sama
                                        </span>
                                        <span v-else-if="passwordsMismatch" class="text-(--sb-danger)">Belum sama dengan kata sandi di sebelah.</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="grid gap-5 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                        <legend class="contents">
                            <span
                                class="grid size-10 place-items-center rounded-full bg-(--wp-accent) text-sm font-bold text-(--wp-accent-contrast) tabular-nums"
                                aria-hidden="true"
                            >
                                <FontAwesomeIcon :icon="['fas', 'check']" class="size-3.5" />
                            </span>
                        </legend>
                        <div class="space-y-4">
                            <div class="pt-2">
                                <h2 id="kategori-title" class="font-semibold tracking-tight">Jenis pemohon</h2>
                                <p class="text-muted-foreground mt-1 text-sm">Menentukan tarif yang dipakai saat Anda mengajukan sewa.</p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-labelledby="kategori-title">
                                <label
                                    v-for="option in kategoriOptions"
                                    :key="option.value"
                                    class="group relative flex cursor-pointer gap-3 rounded-2xl p-4 transition duration-300"
                                    :class="
                                        form.kategori_default === option.value
                                            ? 'bg-(--wp-accent-soft) ring-2 ring-(--wp-accent)'
                                            : 'hover:bg-muted/60 ring-1 ring-(--wp-hairline)'
                                    "
                                >
                                    <input
                                        v-model="form.kategori_default"
                                        type="radio"
                                        name="kategori_default"
                                        :value="option.value"
                                        class="sr-only"
                                    />
                                    <span
                                        class="grid size-10 shrink-0 place-items-center rounded-xl transition-colors"
                                        :class="
                                            form.kategori_default === option.value
                                                ? 'bg-(--wp-accent) text-(--wp-accent-contrast)'
                                                : 'bg-muted text-muted-foreground'
                                        "
                                    >
                                        <FontAwesomeIcon :icon="['fas', option.icon]" class="size-4" aria-hidden="true" />
                                    </span>
                                    <span>
                                        <span class="block text-sm font-semibold">{{ option.title }}</span>
                                        <span class="text-muted-foreground mt-0.5 block text-xs leading-relaxed">{{ option.body }}</span>
                                    </span>
                                    <span
                                        class="absolute top-3 right-3 grid size-5 place-items-center rounded-full transition"
                                        :class="
                                            form.kategori_default === option.value
                                                ? 'bg-(--wp-accent) text-(--wp-accent-contrast)'
                                                : 'ring-1 ring-(--wp-hairline)'
                                        "
                                        aria-hidden="true"
                                    >
                                        <FontAwesomeIcon v-if="form.kategori_default === option.value" :icon="['fas', 'check']" class="size-2.5" />
                                    </span>
                                </label>
                            </div>
                            <p v-if="form.errors.kategori_default" class="sb-error">{{ form.errors.kategori_default }}</p>
                        </div>
                    </fieldset>

                    <div class="border-t border-(--wp-hairline) pt-8">
                        <button type="submit" class="wp-btn wp-btn-primary w-full justify-center px-5 py-3.5 text-sm" :disabled="form.processing">
                            <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'circle-notch']" class="size-4 animate-spin" aria-hidden="true" />
                            Buat akun
                        </button>
                        <p class="text-muted-foreground mt-5 text-center text-sm">
                            Sudah punya akun?
                            <Link
                                :href="route('e-booking.login')"
                                class="rounded-sm font-semibold text-(--wp-accent-strong) underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                            >
                                Masuk di sini
                            </Link>
                        </p>
                    </div>
                </form>
            </section>

            <aside aria-labelledby="register-info-title" class="space-y-6 lg:sticky lg:top-24">
                <div class="sb-card relative isolate overflow-hidden p-6 sm:p-8">
                    <div class="sb-mow absolute inset-0 -z-20" aria-hidden="true"></div>
                    <SbPitchLines class="absolute inset-0 -z-10 size-full text-(--wp-accent) opacity-[0.16]" />

                    <h2 id="register-info-title" class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">
                        Pratinjau kartu penyewa
                    </h2>

                    <div
                        class="mt-4 rounded-2xl bg-(--wp-accent) p-5 text-(--wp-accent-contrast) shadow-[0_24px_48px_-24px_var(--wp-shadow)]"
                        aria-hidden="true"
                    >
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-sm font-bold">
                                <SiBolaMark class="size-7 bg-(--wp-accent-contrast)! text-(--wp-accent)!" />
                                Si Bola
                            </span>
                            <span class="text-[11px] font-semibold tracking-wide uppercase opacity-80">Kartu penyewa</span>
                        </div>
                        <div class="mt-8 flex items-end gap-3">
                            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-(--wp-accent-contrast)/15 text-lg font-bold">
                                {{ cardInitial }}
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-lg leading-tight font-bold">{{ cardName }}</span>
                                <span class="block truncate text-xs opacity-80">{{ form.instansi.trim() || 'Perorangan' }}</span>
                            </span>
                        </div>
                        <div class="mt-6 flex items-center justify-between border-t border-dashed border-(--wp-accent-contrast)/30 pt-4 text-xs">
                            <span>
                                <span class="block opacity-70">Jenis pemohon</span>
                                <span class="font-semibold">{{ cardKategori }}</span>
                            </span>
                            <span class="text-right">
                                <span class="block opacity-70">Layanan</span>
                                <span class="font-semibold">UPT Dispora Kab. Bogor</span>
                            </span>
                        </div>
                    </div>
                </div>

                <ul class="sb-card-muted space-y-4 p-6">
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
