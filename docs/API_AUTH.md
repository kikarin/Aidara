# API Auth - Mobile App

## Base URL
- Dev: `http://localhost:8000/api`
- Prod: `https://aidara.summitct.co.id/api`

## Flow Login
1. Login → kalau belum verified, kirim OTP
2. Verify OTP → dapet token
3. Pakai token untuk request lain

---

## Endpoints

### 1. Login
**POST** `/api/auth/login`

**Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "device_name": "iPhone" // optional
}
```

**Response (Sudah Verified):**
```json
{
  "status": "success",
  "message": "Login berhasil",
  "data": {
    "user": {...},
    "token": "1|xxxxx",
    "token_type": "Bearer"
  }
}
```

**Response (Belum Verified - Perlu OTP):**
```json
{
  "status": "otp_required",
  "message": "Kode OTP telah dikirim ke email Anda",
  "data": {
    "email": "user@example.com",
    "requires_otp": true
  }
}
```

**Error:**
```json
{
  "status": "error",
  "message": "Kredensial yang diberikan salah"
}
```

---

### 2. Verify OTP
**POST** `/api/auth/verify-otp`

**Body:**
```json
{
  "email": "user@example.com",
  "otp": "123456"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Email berhasil diverifikasi",
  "data": {
    "user": {...},
    "token": "1|xxxxx",
    "token_type": "Bearer"
  }
}
```

**Error:**
- OTP salah → `"Kode OTP tidak valid"`
- OTP expired → `"Kode OTP telah kedaluwarsa"`
- OTP tidak ada → `"Kode OTP tidak ditemukan"`

**Note:** OTP berlaku 10 menit, cuma bisa dipake sekali

---

### 3. Resend OTP
**POST** `/api/auth/resend-otp`

**Body:**
```json
{
  "email": "user@example.com"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Kode OTP baru telah dikirim ke email Anda"
}
```

**Error:**
- Cooldown → `"Tunggu X detik sebelum meminta kode OTP baru"` (60 detik cooldown)
- Sudah verified → `"Email sudah terverifikasi"`

---

### 4. Logout
**POST** `/api/auth/logout`

**Header:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": "success",
  "message": "Logout berhasil"
}
```

---

### 5. Get Profile
**GET** `/api/auth/profile`

**Header:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "name": "User Name",
      "role": {...}
    }
  }
}
```

---

### 6. Refresh Token
**POST** `/api/auth/refresh`

**Header:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": "success",
  "message": "Token berhasil diperbarui",
  "data": {
    "token": "2|xxxxx",
    "token_type": "Bearer"
  }
}
```

---

## Authentication
Semua endpoint yang butuh auth, tambahkan header:
```
Authorization: Bearer {token}
```

Token expired: 7 hari

---

## Error Format
```json
{
  "status": "error",
  "message": "Error message",
  "errors": {
    "field": ["Error detail"]
  }
}
```

---

## Testing di Postman

1. **Login** → simpan token ke environment variable
2. **Verify OTP** → kalau perlu
3. **Get Profile** → test dengan token

**Environment Variable:**
- `base_url`: `http://localhost:8000/api`
- `token`: (auto diisi setelah login)

**Pre-request Script (untuk endpoint yang butuh auth):**
```javascript
pm.request.headers.add({
    key: 'Authorization',
    value: 'Bearer ' + pm.environment.get('token')
});
```

**Tests Script (untuk login):**
```javascript
if (pm.response.code === 200) {
    const json = pm.response.json();
    if (json.data && json.data.token) {
        pm.environment.set('token', json.data.token);
    }
}
```

---

## Notes
- Token simpan di secure storage (Keychain/Keystore)
- Kalau dapet 401, redirect ke login
- OTP input: 6 digit numeric
- Resend OTP ada cooldown 60 detik (tampilkan countdown)
- Handle network error dengan retry

---

**Last Update:** 2024-01-XX

