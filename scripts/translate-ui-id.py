#!/usr/bin/env python3
import os
import re
import sys

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'resources', 'js'))
if not os.path.isdir(ROOT):
    ROOT = '/var/www/Aidara/resources/js'

if not os.path.isdir(ROOT):
    print(f'ROOT not found: {ROOT}', file=sys.stderr)
    sys.exit(1)

replacements = [
    ("label: 'Edit'", "label: 'Ubah'"),
    ("label: 'Delete'", "label: 'Hapus'"),
    ("label: 'Set Permissions'", "label: 'Atur Izin'"),
    ("'Created At'", "'Dibuat Pada'"),
    ("'Created By'", "'Dibuat Oleh'"),
    ("'Updated At'", "'Diperbarui Pada'"),
    ("'Updated By'", "'Diperbarui Oleh'"),
    ("label: 'All Roles'", "label: 'Semua Peran'"),
    ("label: 'Default Page'", "label: 'Halaman Default'"),
    ("{ value: 1, label: 'Active' }", "{ value: 1, label: 'Aktif' }"),
    ("{ value: 0, label: 'Inactive' }", "{ value: 0, label: 'Nonaktif' }"),
    ("? 'Active' : 'Inactive'", "? 'Aktif' : 'Nonaktif'"),
    ('rounded-full">Active<', 'rounded-full">Aktif<'),
    ('rounded-full">Inactive<', 'rounded-full">Nonaktif<'),
    ('placeholder="Search..."', 'placeholder="Cari..."'),
    ('Loading data...', 'Memuat data...'),
    ('> Export PDF<', '> Ekspor PDF<'),
    ("placeholder: 'Max peserta'", "placeholder: 'Maks. peserta'"),
    (">Upload Foto Baru<", ">Unggah Foto Baru<"),
    ("saveText || 'Save'", "saveText || 'Simpan'"),
    ("{ title: 'Create'", "{ title: 'Tambah'"),
    ("{ title: 'Edit'", "{ title: 'Ubah'"),
    ("{ title: 'Set Permissions'", "{ title: 'Atur Izin'"),
    ("{ title: 'Dashboard'", "{ title: 'Dasbor'"),
    (" title: 'Dashboard'", " title: 'Dasbor'"),
    ("? 'Create' : 'Edit'", "? 'Tambah' : 'Ubah'"),
    ("                ? 'Create'", "                ? 'Tambah'"),
    ("                  : 'Edit'", "                  : 'Ubah'"),
    ('<AlertTitle>Error</AlertTitle>', '<AlertTitle>Kesalahan</AlertTitle>'),
    ("|| 'Unknown'", "|| 'Tidak Diketahui'"),
    ('> Reset <', '> Atur Ulang <'),
    ('> Previous <', '> Sebelumnya <'),
    ('> Next <', '> Selanjutnya <'),
    ('> Previous</', '> Sebelumnya</'),
    ('> Next</', '> Selanjutnya</'),
    ('<span class="text-muted-foreground text-sm">entries</span>', '<span class="text-muted-foreground text-sm">data</span>'),
    ('<span class="text-muted-foreground text-sm">Show</span>', '<span class="text-muted-foreground text-sm">Tampilkan</span>'),
    ('>All</SelectItem>', '>Semua</SelectItem>'),
    ('title="Edit Tempat"', 'title="Ubah Tempat"'),
    ('{ title: \'Edit Tempat\'', '{ title: \'Ubah Tempat\''),
    ('Tambah Multiple', 'Tambah Banyak'),
    ('Export PDF', 'Ekspor PDF'),
    ("label: 'Login As'", "label: 'Masuk Sebagai'"),
    ("label: 'Can Login'", "label: 'Bisa Login'"),
    ("{ value: 'Settings', label: 'Settings' }", "{ value: 'Settings', label: 'Pengaturan' }"),
    ('<Head title="Dashboard" />', '<Head title="Dasbor" />'),
    ('section-title="Settings"', 'section-title="Pengaturan"'),
    ('<Head title="Welcome">', '<Head title="Selamat Datang">'),
    ('>Dashboard<', '>Dasbor<'),
    ('>Login<', '>Masuk<'),
    ('>Register<', '>Daftar<'),
    ('Ke Dashboard', 'Ke Dasbor'),
    ('<Head title="Login" />', '<Head title="Masuk" />'),
    ('<Head title="Register" />', '<Head title="Daftar" />'),
    ('>Log in<', '>Masuk<'),
    ("title: 'Profile settings'", "title: 'Pengaturan Profil'"),
    ('<Head title="Profile settings" />', '<Head title="Pengaturan Profil" />'),
    ("title: 'Password settings'", "title: 'Pengaturan Kata Sandi'"),
    ('<Head title="Password settings" />', '<Head title="Pengaturan Kata Sandi" />'),
    ("title: 'Appearance settings'", "title: 'Pengaturan Tampilan'"),
    ('<Head title="Appearance settings" />', '<Head title="Pengaturan Tampilan" />'),
    ("title: 'Profile'", "title: 'Profil'"),
    ("title: 'Password'", "title: 'Kata Sandi'"),
    ("title: 'Appearance'", "title: 'Tampilan'"),
    ('<Heading title="Settings"', '<Heading title="Pengaturan"'),
    ('description="Manage your profile and account settings"', 'description="Kelola profil dan pengaturan akun Anda"'),
    ('title="Profile information"', 'title="Informasi Profil"'),
    ('description="Update your name and email address"', 'description="Perbarui nama dan alamat email Anda"'),
    ('<Label for="name">Name</Label>', '<Label for="name">Nama</Label>'),
    ('placeholder="Full name"', 'placeholder="Nama lengkap"'),
    ('<Label for="email">Email address</Label>', '<Label for="email">Alamat email</Label>'),
    ('placeholder="Email address"', 'placeholder="Alamat email"'),
    ('Your email address is unverified.', 'Alamat email Anda belum diverifikasi.'),
    ('Click here to resend the verification email.', 'Klik di sini untuk kirim ulang email verifikasi.'),
    ('A new verification link has been sent to your email address.', 'Tautan verifikasi baru telah dikirim ke email Anda.'),
    ('>Save</Button>', '>Simpan</Button>'),
    ('>Saved.</p>', '>Tersimpan.</p>'),
    ('title="Update password"', 'title="Perbarui Kata Sandi"'),
    ('description="Ensure your account is using a long, random password to stay secure"', 'description="Pastikan akun Anda menggunakan kata sandi yang panjang dan aman"'),
    ('>Current password</Label>', '>Kata sandi saat ini</Label>'),
    ('placeholder="Current password"', 'placeholder="Kata sandi saat ini"'),
    ('>New password</Label>', '>Kata sandi baru</Label>'),
    ('placeholder="New password"', 'placeholder="Kata sandi baru"'),
    ('>Confirm password</Label>', '>Konfirmasi kata sandi</Label>'),
    ('placeholder="Confirm password"', 'placeholder="Konfirmasi kata sandi"'),
    ('>Save password</Button>', '>Simpan kata sandi</Button>'),
    ('description="Update your account\'s appearance settings"', 'description="Perbarui pengaturan tampilan akun Anda"'),
    ('title="Forgot password"', 'title="Lupa Kata Sandi"'),
    ('description="Enter your email to receive a password reset link"', 'description="Masukkan email untuk menerima tautan reset kata sandi"'),
    ('<Head title="Forgot password" />', '<Head title="Lupa Kata Sandi" />'),
    ('title="Reset password"', 'title="Reset Kata Sandi"'),
    ('description="Please enter your new password below"', 'description="Masukkan kata sandi baru Anda di bawah ini"'),
    ('<Head title="Reset password" />', '<Head title="Reset Kata Sandi" />'),
    ('> Confirm Password </Label>', '> Konfirmasi Kata Sandi </Label>'),
    ('>Confirm Password</Label>', '>Konfirmasi Kata Sandi</Label>'),
    ('>Confirm Password<', '>Konfirmasi Kata Sandi<'),
    ('>Reset password</Button>', '>Reset kata sandi</Button>'),
    ('<AlertTitle>Error</AlertTitle>', '<AlertTitle>Kesalahan</AlertTitle>'),
    ('<AlertTitle>Error</AlertTitle>', '<AlertTitle>Kesalahan</AlertTitle>'),
    ('<AlertTitle>Warning</AlertTitle>', '<AlertTitle>Peringatan</AlertTitle>'),
    ('<p class="font-medium">Warning</p>', '<p class="font-medium">Peringatan</p>'),
    ('<span class="sr-only">Close</span>', '<span class="sr-only">Tutup</span>'),
    ('<span class="sr-only">More</span>', '<span class="sr-only">Lainnya</span>'),
    ("label: 'Password'", "label: 'Kata Sandi'"),
    ("label: 'Konfirmasi Password'", "label: 'Konfirmasi Kata Sandi'"),
    ('help: \'Password harus', "help: 'Kata sandi harus"),
    ('help: \'Password harus sama', "help: 'Kata sandi harus sama"),
    ('<Label for="password" class="text-sm font-medium">Password</Label>', '<Label for="password" class="text-sm font-medium">Kata Sandi</Label>'),
    ('placeholder="Masukkan password Anda"', 'placeholder="Masukkan kata sandi Anda"'),
    ('title: \'Dashboard\'', "title: 'Dasbor'"),
    ('<AlertTitle>Error</AlertTitle>', '<AlertTitle>Kesalahan</AlertTitle>'),
]

pagination_patterns = [
    (
        r'Showing \{\{ \(currentPage - 1\) \* perPage \+ 1 \}\} to \{\{ Math\.min\(currentPage \* perPage, total\) \}\} of \{\{ total \}\} entries',
        'Menampilkan {{ (currentPage - 1) * perPage + 1 }} sampai {{ Math.min(currentPage * perPage, total) }} dari {{ total }} data',
    ),
    (
        r'<span> Showing \{\{ \(currentPage - 1\) \* perPage \+ 1 \}\} to \{\{ Math\.min\(currentPage \* perPage, total\) \}\} of \{\{ total \}\} entries </span>',
        '<span> Menampilkan {{ (currentPage - 1) * perPage + 1 }} sampai {{ Math.min(currentPage * perPage, total) }} dari {{ total }} data </span>',
    ),
]

count = 0
for dirpath, _, filenames in os.walk(ROOT):
    for fn in filenames:
        if not fn.endswith('.vue'):
            continue
        path = os.path.join(dirpath, fn)
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
        orig = content
        for old, new in replacements:
            content = content.replace(old, new)
        for pattern, repl in pagination_patterns:
            content = re.sub(pattern, repl, content)
        if content != orig:
            with open(path, 'w', encoding='utf-8') as f:
                f.write(content)
            count += 1

print(f'Updated {count} files under {ROOT}')
