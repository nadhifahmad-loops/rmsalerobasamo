# Backup dan Restore

Backup harus dibuat sebelum migrasi, deployment besar, atau perubahan data massal. Simpan file backup di lokasi terpisah dari server aplikasi dan batasi aksesnya. Backup mengandung hash password dan data transaksi, jadi perlakukan sebagai data sensitif.

## 1. Backup produksi dengan MySQL

Gunakan akun dengan hak `SELECT`, `LOCK TABLES`, dan `SHOW VIEW`. Jalankan dari host yang memiliki akses ke database produksi:

```bash
mysqldump \
  --host=DB_HOST \
  --port=DB_PORT \
  --user=DB_USER \
  --password \
  --single-transaction \
  --routines \
  --triggers \
  --set-gtid-purged=OFF \
  --default-character-set=utf8mb4 \
  DB_NAME > backup-YYYYMMDD-HHMMSS.sql
```

Tambahkan kompresi bila diperlukan:

```bash
gzip backup-YYYYMMDD-HHMMSS.sql
```

Jangan menaruh password pada argumen command line karena dapat terbaca di process list. Opsi `--password` tanpa nilai akan memprompt.

Verifikasi file backup:

```bash
ls -lh backup-YYYYMMDD-HHMMSS.sql.gz
gzip -t backup-YYYYMMDD-HHMMSS.sql.gz
```

## 2. Restore ke environment uji

Buat database uji terpisah. Jangan pernah restore langsung ke database produksi tanpa prosedur dan persetujuan yang jelas.

```bash
mysql --host=DB_HOST --port=DB_PORT --user=DB_USER --password \
  -e "CREATE DATABASE padang_pos_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

mysql --host=DB_HOST --port=DB_PORT --user=DB_USER --password \
  padang_pos_restore < backup-YYYYMMDD-HHMMSS.sql
```

Arahkan `.env` environment uji ke database tersebut, lalu jalankan:

```bash
php artisan migrate:status
php artisan db:show
php artisan test --colors=never --compact
```

Periksa pula:

- jumlah baris tabel `roles`, `users`, `orders`, `order_items`, `payments`, `shifts`, dan `audit_logs`;
- tidak ada order `PAID` tanpa payment;
- `orders.total` sama dengan jumlah `order_items.subtotal`;
- `payments.amount` sama dengan `orders.total`;
- `snapshot` harga pada `order_items` tidak berubah;
- laporan pada periode uji menampilkan angka yang sama dengan fixture sumber.

## 3. Verifikasi portabel tanpa `mysqldump`

Repository menyediakan skrip verifikasi yang tidak memerlukan `mysqldump`. Skrip ini membaca data dari koneksi database aktif, menulis backup JSON ke file sementara, membuat database SQLite isolat, menjalankan migration, mengisi data, lalu membandingkan jumlah baris dan checksum.

```bash
php scripts/verify-backup-restore.php
```

Skrip tidak mengubah `.env` dan tidak menulis ke database sumber. File backup dan restore dibuat di direktori temp sistem serta dihapus di akhir proses. Keluar dengan kode `0` bila verifikasi berhasil.

Skrip ini adalah pengujian prosedur, bukan pengganti backup produksi. Untuk produksi tetap gunakan `mysqldump` atau tool backup terkelola yang mendukung point-in-time recovery.

## 4. Jadwal yang disarankan

- Backup harian otomatis dengan retensi minimal 14 hari.
- Backup sebelum dan sesudah deployment yang menyentuh schema.
- Uji restore minimal sekali sebulan ke environment uji.
- Simpan hasil uji restore bersama catatan deployment.

## 5. Status verifikasi Phase 6

Hasil eksekusi aktual dicatat di `docs/TESTING-CHECKLIST.md` bagian Phase 6. Jangan mengklaim backup/restore lulus hanya karena skrip tersedia; sertakan output pemeriksaan yang benar-benar dijalankan.
