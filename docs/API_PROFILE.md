# API Profile Documentation

Dokumentasi API untuk Profile (Biodata, Sertifikat, Prestasi, Dokumen) untuk Mobile App.

## Base URL

- **Development**: `http://localhost:8000/api`
- **Production**: `https://aidara.summitct.co.id/api`

## Authentication

Semua endpoint memerlukan authentication menggunakan Bearer Token.

**Header yang diperlukan:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

Untuk file upload (sertifikat & dokumen), gunakan:
```
Content-Type: multipart/form-data
```

---

## Options/Dropdown Endpoints

Endpoint untuk mendapatkan list options/dropdown untuk form.

### Get All Options (Recommended)

Mendapatkan semua options sekaligus berdasarkan role user yang login.

**Endpoint:** `GET /api/options/all`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "status": "success",
  "data": {
    "kecamatan": [
      { "id": 1, "nama": "Kecamatan A" }
    ],
    "tingkat": [
      { "id": 1, "nama": "Nasional" }
    ],
    "jenis_dokumen": [
      { "id": 1, "nama": "KTP" }
    ],
    "kategori_peserta": [
      { "id": 1, "nama": "Kategori A" }
    ],
    "cabor": [
      { "id": 1, "nama": "Cabor A", "kategori_peserta_id": 1 }
    ],
    "kategori_atlet": [
      { "id": 1, "nama": "Kategori Atlet A" }
    ],
    "posisi_atlet": [
      { "id": 1, "nama": "Posisi A" }
    ],
    "kategori_prestasi_pelatih": [
      { "id": 1, "nama": "Kategori Prestasi A" }
    ]
  }
}
```

**Catatan:** Field yang muncul berbeda berdasarkan role:
- **Atlet**: `kategori_atlet`, `posisi_atlet`
- **Pelatih**: `kategori_prestasi_pelatih`, `kategori_atlet`
- **Tenaga Pendukung**: Tidak ada field khusus

### Individual Options Endpoints

Jika ingin mengambil options secara terpisah:

- `GET /api/options/kecamatan` - List kecamatan
- `GET /api/options/kelurahan/{kecamatanId}` - List kelurahan berdasarkan kecamatan
- `GET /api/options/tingkat` - List tingkat (untuk Prestasi)
- `GET /api/options/kategori-prestasi-pelatih` - List kategori prestasi pelatih
- `GET /api/options/kategori-atlet` - List kategori atlet
- `GET /api/options/jenis-dokumen` - List jenis dokumen
- `GET /api/options/posisi-atlet` - List posisi atlet
- `GET /api/options/kategori-peserta` - List kategori peserta
- `GET /api/options/cabor` - List cabor (bisa filter dengan `?kategori_peserta_id=1`)
- `GET /api/options/cabor-kategori/{caborId}` - List cabor kategori berdasarkan cabor ID

**Contoh Response:**
```json
{
  "status": "success",
  "data": [
    { "id": 1, "nama": "Option 1" },
    { "id": 2, "nama": "Option 2" }
  ]
}
```

---

## Endpoints

### 1. Get Biodata

Mendapatkan biodata sesuai role user yang login (auto-detect: atlet/pelatih/tenaga_pendukung).

**Endpoint:** `GET /api/profile/biodata`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "status": "success",
  "data": {
    "role": "atlet",
    "biodata": {
      "id": 1,
      "nik": "1234567890123456",
      "nisn": "12345678901",
      "nama": "Nama Atlet",
      "jenis_kelamin": "L",
      "tempat_lahir": "Jakarta",
      "tanggal_lahir": "2000-01-01",
      "agama": "Islam",
      "tanggal_bergabung": "2024-01-01",
      "alamat": "Jl. Contoh No. 123",
      "sekolah": "SMA 1",
      "kelas_sekolah": "XII",
      "ukuran_baju": "L",
      "ukuran_celana": "32",
      "ukuran_sepatu": "42",
      "disabilitas": null,
      "klasifikasi": null,
      "iq": null,
      "kecamatan": {
        "id": 1,
        "nama": "Kecamatan A"
      },
      "kelurahan": {
        "id": 1,
        "nama": "Kelurahan A"
      },
      "no_hp": "081234567890",
      "email": "atlet@example.com",
      "foto": "http://...",
      "foto_thumbnail": "http://...",
      "kategori_atlet": {
        "id": 1,
        "nama": "Kategori A"
      },
      "posisi_atlet": {
        "id": 1,
        "nama": "Posisi A"
      },
      "cabor": [
        "Renang",
        "TENIS MEJA TUNET"
      ],
      "kategori_peserta": [
        {
          "id": 1,
          "nama": "Kategori Peserta A"
        }
      ]
    },
    "permissions": [
      "Atlet Show",
      "Atlet Edit",
      "Atlet Sertifikat Show",
      "Atlet Sertifikat Add",
      "Atlet Sertifikat Delete",
      "Atlet Prestasi Show",
      "Atlet Prestasi Add",
      "Atlet Prestasi Delete",
      "Atlet Dokumen Show",
      "Atlet Dokumen Add",
      "Atlet Dokumen Delete"
    ]
  }
}
```

**Response Error (403):**
```json
{
  "status": "error",
  "message": "Anda tidak memiliki izin untuk melihat biodata."
}
```

**Response Error (404):**
```json
{
  "status": "error",
  "message": "Data peserta tidak ditemukan."
}
```

---

### 2. Update Biodata

Mengupdate biodata sesuai role user yang login. **Support partial update** - hanya kirim field yang ingin diupdate.

**Endpoint:** `PUT /api/profile/biodata`

**Catatan Penting:**
- ✅ **Partial Update**: Bisa update foto saja tanpa mengirim semua field
- ✅ **Field Nullable**: Field nullable bisa dikosongkan (set ke `null`)
- ✅ **Format Tanggal**: `YYYY-MM-DD` (contoh: `2024-01-01`)

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body (Atlet) - Full Update:**
```json
{
  "nik": "1234567890123456",
  "nisn": "12345678901",
  "nama": "Nama Atlet Updated",
  "jenis_kelamin": "L",
  "tempat_lahir": "Jakarta",
  "tanggal_lahir": "2000-01-01",
  "agama": "Islam",
  "tanggal_bergabung": "2024-01-01",
  "alamat": "Jl. Contoh No. 123",
  "sekolah": "SMA 1",
  "kelas_sekolah": "XII",
  "ukuran_baju": "L",
  "ukuran_celana": "32",
  "ukuran_sepatu": "42",
  "disabilitas": null,
  "klasifikasi": null,
  "iq": null,
  "kecamatan_id": 1,
  "kelurahan_id": 1,
  "no_hp": "081234567890",
  "email": "atlet@example.com",
  "is_delete_foto": false
}
```

**Request Body - Partial Update (Update Foto Saja):**
```json
{
  "nama": "Nama Updated"
}
```

**Request Body - Update Foto dengan multipart/form-data:**
```
file: [binary file]
nama: "Nama Updated"
```

**Request Body (Pelatih):**
```json
{
  "nik": "1234567890123456",
  "nama": "Nama Pelatih Updated",
  "jenis_kelamin": "L",
  "tempat_lahir": "Jakarta",
  "tanggal_lahir": "1980-01-01",
  "tanggal_bergabung": "2024-01-01",
  "alamat": "Jl. Contoh No. 123",
  "kecamatan_id": 1,
  "kelurahan_id": 1,
  "no_hp": "081234567890",
  "email": "pelatih@example.com",
  "pekerjaan_selain_melatih": "Guru",
  "is_delete_foto": false
}
```

**Request Body (Tenaga Pendukung):**
```json
{
  "nik": "1234567890123456",
  "nama": "Nama Tenaga Pendukung Updated",
  "jenis_kelamin": "L",
  "tempat_lahir": "Jakarta",
  "tanggal_lahir": "1985-01-01",
  "tanggal_bergabung": "2024-01-01",
  "alamat": "Jl. Contoh No. 123",
  "kecamatan_id": 1,
  "kelurahan_id": 1,
  "no_hp": "081234567890",
  "email": "tenaga@example.com",
  "is_delete_foto": false
}
```

**File Upload (Foto):**
Untuk upload foto, gunakan `multipart/form-data`:
```
file: [binary file]
nama: "Nama Atlet"
jenis_kelamin: "L"
...
```

**Response Success (200):**
```json
{
  "status": "success",
  "message": "Biodata berhasil diperbarui.",
  "data": {
    "role": "atlet",
    "biodata": { ... },
    "permissions": [ ... ]
  }
}
```

**Response Error (403):**
```json
{
  "status": "error",
  "message": "Anda tidak memiliki izin untuk mengedit biodata."
}
```

---

### 3. Get Sertifikat

Mendapatkan list semua sertifikat user yang login.

**Endpoint:** `GET /api/profile/sertifikat`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "status": "success",
  "data": {
    "sertifikat": [
      {
        "id": 1,
        "nama_sertifikat": "Sertifikat Pelatihan",
        "penyelenggara": "Organisasi X",
        "tanggal_terbit": "2024-01-01",
        "file_url": "http://..."
      }
    ],
    "permissions": [ ... ]
  }
}
```

---

### 4. Store Sertifikat

Menambahkan sertifikat baru.

**Endpoint:** `POST /api/profile/sertifikat`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: multipart/form-data
```

**Request Body (multipart/form-data):**
```
nama_sertifikat: "Sertifikat Pelatihan"
penyelenggara: "Organisasi X"
tanggal_terbit: "2024-01-01"
file: [binary file - jpg, png, jpeg, pdf, webp, max 4MB]
```

**Response Success (201):**
```json
{
  "status": "success",
  "message": "Sertifikat berhasil ditambahkan.",
  "data": {
    "sertifikat": {
      "id": 1,
      "nama_sertifikat": "Sertifikat Pelatihan",
      "penyelenggara": "Organisasi X",
      "tanggal_terbit": "2024-01-01",
      "file_url": "http://..."
    },
    "permissions": [ ... ]
  }
}
```

**Response Error (403):**
```json
{
  "status": "error",
  "message": "Anda tidak memiliki izin untuk menambah sertifikat."
}
```

---

### 5. Delete Sertifikat

Menghapus sertifikat.

**Endpoint:** `DELETE /api/profile/sertifikat/{id}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "status": "success",
  "message": "Sertifikat berhasil dihapus.",
  "data": {
    "permissions": [ ... ]
  }
}
```

**Response Error (403):**
```json
{
  "status": "error",
  "message": "Anda tidak memiliki izin untuk menghapus sertifikat."
}
```

**Response Error (404):**
```json
{
  "status": "error",
  "message": "Sertifikat tidak ditemukan."
}
```

---

### 6. Get Prestasi

Mendapatkan list semua prestasi user yang login.

**Endpoint:** `GET /api/profile/prestasi`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200) - Atlet/Tenaga Pendukung:**
```json
{
  "status": "success",
  "data": {
    "prestasi": [
      {
        "id": 1,
        "nama_event": "Kejuaraan Nasional",
        "tingkat": {
          "id": 1,
          "nama": "Nasional"
        },
        "tanggal": "2024-01-01",
        "peringkat": "Juara 1",
        "keterangan": "Medali Emas",
        "bonus": 1000000
      }
    ],
    "permissions": [ ... ]
  }
}
```

**Response Success (200) - Pelatih:**
```json
{
  "status": "success",
  "data": {
    "prestasi": [
      {
        "id": 1,
        "nama_event": "Kejuaraan Nasional",
        "kategori_prestasi_pelatih": {
          "id": 1,
          "nama": "Kategori A"
        },
        "kategori_atlet": {
          "id": 1,
          "nama": "Kategori Atlet A"
        },
        "tingkat": {
          "id": 1,
          "nama": "Nasional"
        },
        "tanggal": "2024-01-01",
        "peringkat": "Juara 1",
        "keterangan": "Medali Emas",
        "bonus": 1000000
      }
    ],
    "permissions": [ ... ]
  }
}
```

---

### 7. Store Prestasi

Menambahkan prestasi baru.

**Endpoint:** `POST /api/profile/prestasi`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body (Atlet/Tenaga Pendukung):**
```json
{
  "nama_event": "Kejuaraan Nasional",
  "tingkat_id": 1,
  "tanggal": "2024-01-01",
  "peringkat": "Juara 1",
  "keterangan": "Medali Emas",
  "bonus": 1000000
}
```

**Request Body (Pelatih):**
```json
{
  "nama_event": "Kejuaraan Nasional",
  "kategori_prestasi_pelatih_id": 1,
  "kategori_atlet_id": 1,
  "tingkat_id": 1,
  "tanggal": "2024-01-01",
  "peringkat": "Juara 1",
  "keterangan": "Medali Emas",
  "bonus": 1000000
}
```

**Response Success (201):**
```json
{
  "status": "success",
  "message": "Prestasi berhasil ditambahkan.",
  "data": {
    "prestasi": { ... },
    "permissions": [ ... ]
  }
}
```

---

### 8. Delete Prestasi

Menghapus prestasi.

**Endpoint:** `DELETE /api/profile/prestasi/{id}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "status": "success",
  "message": "Prestasi berhasil dihapus.",
  "data": {
    "permissions": [ ... ]
  }
}
```

---

### 9. Get Dokumen

Mendapatkan list semua dokumen user yang login.

**Endpoint:** `GET /api/profile/dokumen`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "status": "success",
  "data": {
    "dokumen": [
      {
        "id": 1,
        "jenis_dokumen": {
          "id": 1,
          "nama": "KTP"
        },
        "nomor": "1234567890123456",
        "file_url": "http://..."
      }
    ],
    "permissions": [ ... ]
  }
}
```

---

### 10. Store Dokumen

Menambahkan dokumen baru.

**Endpoint:** `POST /api/profile/dokumen`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: multipart/form-data
```

**Request Body (multipart/form-data):**
```
jenis_dokumen_id: 1
nomor: "1234567890123456"
file: [binary file - jpg, png, jpeg, pdf, webp, max 4MB]
```

**Response Success (201):**
```json
{
  "status": "success",
  "message": "Dokumen berhasil ditambahkan.",
  "data": {
    "dokumen": {
      "id": 1,
      "jenis_dokumen": {
        "id": 1,
        "nama": "KTP"
      },
      "nomor": "1234567890123456",
      "file_url": "http://..."
    },
    "permissions": [ ... ]
  }
}
```

---

### 11. Delete Dokumen

Menghapus dokumen.

**Endpoint:** `DELETE /api/profile/dokumen/{id}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "status": "success",
  "message": "Dokumen berhasil dihapus.",
  "data": {
    "permissions": [ ... ]
  }
}
```

---

## Permission System

Semua endpoint akan return `permissions` array di response. Mobile app bisa menggunakan ini untuk hide/show button berdasarkan permission user.

**Contoh Permission Names:**
- `Atlet Show` / `Pelatih Show` / `Tenaga Pendukung Show`
- `Atlet Edit` / `Pelatih Edit` / `Tenaga Pendukung Edit`
- `Atlet Sertifikat Show` / `Atlet Sertifikat Add` / `Atlet Sertifikat Delete`
- `Atlet Prestasi Show` / `Atlet Prestasi Add` / `Atlet Prestasi Delete`
- `Atlet Dokumen Show` / `Atlet Dokumen Add` / `Atlet Dokumen Delete`

**Catatan:**
- Permission akan otomatis update real-time ketika diubah di web
- Spatie Permission package auto-flush cache ketika permission di-update

---

## Error Handling

Semua endpoint mengembalikan response dengan format:

**Success:**
```json
{
  "status": "success",
  "message": "...",
  "data": { ... }
}
```

**Error:**
```json
{
  "status": "error",
  "message": "..."
}
```

**HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `403` - Forbidden (tidak punya permission)
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## Field Spesifik per Role

### Atlet
- `nisn`, `agama`, `sekolah`, `kelas_sekolah`
- `ukuran_baju`, `ukuran_celana`, `ukuran_sepatu`
- `disabilitas`, `klasifikasi`, `iq`
- `kategori_atlet`, `posisi_atlet`
- `cabor` (array of strings) - List nama cabor yang dimiliki atlet, format: `["Renang", "TENIS MEJA TUNET"]`
- `kategori_peserta` (array) - List kategori peserta

### Pelatih
- `pekerjaan_selain_melatih`
- `jenis_pelatih`
- `cabor` (array of strings) - List nama cabor yang dimiliki pelatih, format: `["Renang", "TENIS MEJA TUNET"]`
- `kategori_peserta` (array) - List kategori peserta
- Prestasi: `kategori_prestasi_pelatih_id`, `kategori_atlet_id`

### Tenaga Pendukung
- `cabor` (array of strings) - List nama cabor yang dimiliki tenaga pendukung, format: `["Renang", "TENIS MEJA TUNET"]`
- `kategori_peserta` (array) - List kategori peserta

---

## Notes

1. **Auto-detect Role**: Semua endpoint otomatis detect role dari user yang login (`atlet` / `pelatih` / `tenaga_pendukung`)
2. **Ownership Check**: User hanya bisa akses/edit data mereka sendiri
3. **Partial Update**: Update biodata support partial update - hanya kirim field yang ingin diupdate
4. **Nullable Fields**: Field nullable bisa dikosongkan (set ke `null`)
5. **Format Tanggal**: Semua tanggal menggunakan format `YYYY-MM-DD` (contoh: `2024-01-01`)
6. **File Upload**: 
   - Maksimal 4MB untuk sertifikat & dokumen
   - Maksimal 2MB untuk foto
   - Bisa update foto saja tanpa mengirim semua field
7. **File Types**: 
   - Foto: `jpg, png, jpeg, webp`
   - Sertifikat/Dokumen: `jpg, png, jpeg, pdf, webp`
8. **Return All**: Sertifikat, Prestasi, dan Dokumen return semua data (tidak ada pagination)
9. **Options Endpoint**: Gunakan `/api/options/all` untuk mendapatkan semua dropdown options sekaligus

---

## Testing di Postman

1. **Login** dulu untuk mendapatkan token
2. Set **Authorization** header: `Bearer {token}`
3. Untuk file upload, gunakan **form-data** (bukan raw JSON)
4. Test semua endpoint sesuai role user yang login

---

**Happy Coding! 🚀**

