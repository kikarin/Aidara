# Panduan Operasional CMS Aidara (Chatbot)

> Dokumen ini melengkapi API docs. **Nama menu** mengikuti data di Menu & Permissions (`users_menus`), bukan nama file dokumentasi.

## Nama menu vs kode modul

Admin dapat mengubah **nama tampilan menu** di `/menu-permissions/menus`. Yang tetap konsisten:

| Kode menu | URL utama | Catatan |
|-----------|-----------|---------|
| PROGRAM-LATIHAN | /program-latihan | Rekap Absen bukan menu terpisah |
| PEMERIKSAAN | /pemeriksaan | Pemeriksaan umum (parameter peserta) |
| PEMERIKSAAN-KHUSUS | /pemeriksaan-khusus | Bisa ditampilkan sebagai "Pemeriksaan Kondisi Fisik/Kebugaran" atau nama lain |

Jika user menyebut **pemeriksaan kondisi fisik**, **pemeriksaan kebugaran**, atau **tes kondisi fisik** → arahkan ke modul **PEMERIKSAAN-KHUSUS** (`/pemeriksaan-khusus`), BUKAN modul Pemeriksaan (`/pemeriksaan`).

## Program Latihan → Rekap Absen (alur lengkap)

1. Buka menu **Program Latihan** (`/program-latihan`).
2. Klik program yang ingin direkap → halaman **Detail**.
3. Klik tombol **Rekap Absen** → masuk ke daftar rekap absen program tersebut (`/program-latihan/{id}/rekap-absen`).
4. Di halaman rekap absen:
   - Lihat daftar rekap per tanggal.
   - **Tambah rekap** untuk tanggal hari ini (Asia/Jakarta): isi jenis latihan, keterangan, foto absen (kamera + GPS), file nilai jika ada.
   - Rekap tanggal **lampau** hanya bisa dilihat (read-only), tidak bisa diubah.
   - Satu rekap per program per tanggal; tanggal harus dalam periode program.
5. Klik item rekap untuk **detail/edit** rekap hari tersebut (`/program-latihan/{id}/rekap-absen/{rekapId}`).

## Pemeriksaan Khusus (kode PEMERIKSAAN-KHUSUS)

Gunakan **nama menu** dari daftar modul CMS (mis. "Pemeriksaan Kondisi Fisik/Kebugaran").

### 5 aksi di tabel daftar (`/pemeriksaan-khusus`)

| Aksi | Fungsi |
|------|--------|
| **Detail** | Melihat ringkasan pemeriksaan, peserta, dan navigasi ke fitur lain |
| **Input Hasil Tes** | Mengisi/memperbarui nilai hasil tes per peserta (`/pemeriksaan-khusus/{id}/input-hasil-tes`) |
| **Setup** | Mengatur aspek & item tes sebelum input hasil (`/pemeriksaan-khusus/{id}/setup`) |
| **Edit** | Mengubah data dasar pemeriksaan (nama, tanggal, cabor, dll.) |
| **Delete** | Menghapus data pemeriksaan (dengan konfirmasi) |

**Urutan kerja disarankan:** buat pemeriksaan → **Setup** aspek & item tes → **Input Hasil Tes** → **Detail** untuk review.

### Input hasil tes kondisi fisik/kebugaran

Selalu modul **PEMERIKSAAN-KHUSUS**, aksi **Input Hasil Tes**, bukan modul Pemeriksaan umum.
