# Troubleshooting Email OTP Tidak Terkirim

## Masalah: Email OTP tidak masuk ke inbox Gmail

### Solusi 1: Cek Konfigurasi Email di .env

Pastikan file `.env` memiliki konfigurasi email yang benar:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

**PENTING untuk Gmail:**
- Gunakan **App Password**, bukan password biasa
- Cara membuat App Password:
  1. Buka https://myaccount.google.com/apppasswords
  2. Pilih "Mail" dan "Other (Custom name)"
  3. Masukkan nama aplikasi (misal: "Aidara App")
  4. Copy password yang dihasilkan
  5. Gunakan password tersebut di `MAIL_PASSWORD`

### Solusi 2: Cek Folder Spam

Email mungkin masuk ke folder **Spam** atau **Promotions** di Gmail. Silakan cek:
- Folder Spam
- Tab Promotions
- All Mail

### Solusi 3: Cek Log Laravel

Cek file log untuk melihat apakah ada error:
```bash
tail -f storage/logs/laravel.log
```

Atau cek log terbaru:
```bash
cat storage/logs/laravel.log | tail -50
```

### Solusi 4: Test Email Langsung

Untuk testing, email sekarang dikirim langsung (tidak melalui queue). Jika masih tidak terkirim:

1. **Cek konfigurasi SMTP** - pastikan benar
2. **Cek firewall/network** - pastikan port 587 tidak diblokir
3. **Test dengan mailtrap.io** untuk development:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=your-mailtrap-username
   MAIL_PASSWORD=your-mailtrap-password
   ```

### Solusi 5: Jika Menggunakan Queue (Production)

Jika ingin menggunakan queue untuk production, pastikan queue worker berjalan:

```bash
php artisan queue:work
```

Atau gunakan supervisor untuk production.

### Solusi 6: Cek Database

Cek apakah OTP sudah tersimpan di database:
```sql
SELECT email, email_otp, email_otp_expires_at FROM users WHERE email = 'akunamazon4811@gmail.com';
```

### Debugging Steps:

1. ✅ Queue sudah dinonaktifkan - email langsung terkirim
2. ⚠️ Cek konfigurasi `.env` - pastikan SMTP benar
3. ⚠️ Cek folder Spam di Gmail
4. ⚠️ Cek log Laravel untuk error
5. ⚠️ Test dengan email lain untuk memastikan

### Jika Masih Tidak Terkirim:

1. Pastikan `MAIL_MAILER=smtp` (bukan `log`)
2. Pastikan menggunakan App Password untuk Gmail
3. Cek apakah port 587 tidak diblokir firewall
4. Coba dengan email provider lain (Yahoo, Outlook) untuk testing

