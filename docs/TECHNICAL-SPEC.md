# Technical Specification — POS Rumah Makan Padang

## 1. Stack

Rekomendasi implementasi:
- PHP dengan Laravel.
- React + TypeScript untuk UI.
- Inertia.js untuk integrasi Laravel dan React dalam satu aplikasi.
- Tailwind CSS untuk styling.
- shadcn/ui atau komponen accessible setara sebagai dasar komponen.
- MySQL sebagai relational database.
- Vite untuk frontend build.
- Pest untuk backend tests.
- Playwright untuk end-to-end browser tests.

Gunakan versi stabil yang kompatibel pada saat project dibuat. Verifikasi syarat versi resmi PHP/Laravel/Node/package sebelum instalasi. Jangan mengunci versi berdasarkan asumsi atau menyalin versi lama.

## 2. Arsitektur aplikasi

Gunakan **modular monolith** untuk MVP. Frontend dan backend berada dalam satu repository/deployment Laravel dengan Inertia; jangan memisahkan microservices tanpa kebutuhan yang terbukti.

Pemisahan tanggung jawab:
- Routes: deklarasi endpoint/halaman.
- Controllers: menerima request, memanggil use case, mengembalikan response.
- Form Requests: validasi input.
- Policies/Gates: authorization.
- Models: representasi data dan relasi.
- Actions/Services: proses bisnis yang perlu dipusatkan, terutama order/payment/shift.
- React pages/components: tampilan dan interaksi.
- Database migrations/seeders: schema dan data awal non-produksi.

Jangan membuat abstraction layer berlebihan. Ikuti konvensi framework dan pola repository yang sudah tersedia.

## 3. Batas modul

Modul awal:
1. Authentication & Access Control.
2. Catalog (categories, products, variants).
3. Dining Tables.
4. Orders & Open Orders.
5. Payments & Receipts.
6. Shifts.
7. Reports.
8. Audit & Settings (profil usaha, pengaturan struk, pajak/biaya sesuai kebijakan).

Setiap modul sebaiknya memiliki controller/request/policy/action/model/test sesuai kebutuhan. Jangan memaksakan struktur folder yang kompleks bila konvensi Laravel sudah cukup.

## 4. Struktur folder konseptual

```text
app/
  Actions/
    Orders/
    Payments/
    Shifts/
  Http/
    Controllers/
    Requests/
  Models/
  Policies/
  Services/
resources/
  js/
    components/
      ui/
      layout/
      pos/
      orders/
      tables/
    pages/
      Dashboard/
      POS/
      Tables/
      Orders/
      Reports/
      Products/
      Settings/
routes/
  web.php
database/
  migrations/
  seeders/
tests/
  Feature/
  Unit/
```

Struktur ini adalah panduan, bukan keharusan mutlak. Periksa struktur starter kit aktual sebelum membuat direktori.

## 5. Kontrak proses backend

Nama route dan payload final mengikuti konvensi proyek, tetapi proses bisnis minimum harus tersedia.

| Aksi | Tujuan | Aturan penting |
|---|---|---|
| List tables | Menampilkan meja dan status | Status diturunkan dari active order |
| Create dine-in order | Membuat Open Order pada meja | Lock meja; cegah active order kedua |
| Create takeaway order | Membuat order tanpa meja | Validasi tipe order dan produk |
| Show order | Detail Open Order | Periksa permission dan status |
| Add/update/remove item | Mengubah order aktif | Harga diambil server; snapshot disimpan |
| Set awaiting payment | Menandai siap checkout | Hanya transisi yang sah |
| Pay order | Menyelesaikan pembayaran | Atomic, idempotent, hitung ulang total |
| Void order | Membatalkan order | Permission, alasan, audit |
| List history | Riwayat order | Filter, pagination, authorization |
| Report sales | Laporan | Paid only, filter timezone konsisten |
| Open/close shift | Rekonsiliasi kas | Lock dan validasi shift aktif |

Semua endpoint mutasi harus memerlukan autentikasi dan authorization. Request dari browser tidak boleh menentukan harga final, paid status, cashier ID, atau permission.

Kontrak Phase 5 yang terimplementasi:
- `GET/POST /takeaway` — `StoreTakeawayOrderRequest` memvalidasi `items[*].product_id`, `product_variant_id`, `quantity`, dan `notes`; `OrderService::createTakeawayOrder()` mengambil harga server, menyimpan snapshot, dan redirect ke `orders.checkout`.
- `GET /orders?view=active|history` — `OrderHistoryRequest` memvalidasi filter; default `active` menjaga kompatibilitas. Filter tanggal memakai `ResolveDateRange`.
- `GET /orders/{order}` — detail order memuat relasi `payment.processor` dan `shift` untuk inscription pembayaran.
- `GET/POST /shifts`, `PUT /shifts/{shift}/close` — `OpenShiftRequest`/`CloseShiftRequest` + `ShiftService` dengan lock dan audit.
- `GET /reports`, `GET /reports/export` — `ReportRequest` + `ReportService`; keduanya dilindungi `can:view-reports` dan juga memeriksa authorization di FormRequest.

`ReportService` memisahkan query dasar order Paid lalu meng-clone untuk tiap agregat; semua kolom yang dipakai setelah join diberi nama tabel agar tidak ambigu (mis. `orders.status`, `payments.status`).

## 6. Perhitungan dan money handling

- Gunakan integer Rupiah/fixed precision.
- Semua perhitungan final di backend.
- Frontend boleh menampilkan preview, tetapi backend tetap menghitung ulang.
- Tetapkan satu fungsi/domain rule untuk subtotal, diskon, pajak, service charge, total, received, dan change.
- Jangan melakukan perhitungan yang sama dengan aturan berbeda di banyak komponen.
- Tentukan batas nominal dan validasi overflow.

## 7. Authentication dan authorization

- Gunakan mekanisme autentikasi resmi framework/starter kit yang dirawat.
- Session/cookie aman.
- Proteksi CSRF sesuai framework.
- Gunakan policies/gates untuk model/action sensitif.
- Otorisasi di backend setiap request, termasuk akses berdasarkan ownership jika diperlukan.
- Frontend boleh menyembunyikan item navigasi, tetapi itu bukan security boundary.
- Rate limit login.
- Nonaktifkan akun dengan mencabut akses/session sesuai kemampuan framework.

## 8. Security baseline

- Hash password dengan algoritma yang direkomendasikan framework.
- Validasi input server-side.
- ORM/query parameterization.
- CSRF protection.
- Escape output dan hindari raw HTML tidak tepercaya.
- Batasi upload file.
- Gunakan HTTPS production.
- Secrets hanya pada environment/secret manager.
- Jangan commit `.env`.
- Berikan least privilege untuk database user.
- Redaksi data sensitif pada log.
- Terapkan backup dan uji restore.
- Perbarui dependency dan lakukan audit dependency.

## 9. Concurrency, transaction, dan idempotency

### Open Order per meja

Gunakan transaction dan row lock pada meja saat membuat Open Order. Semua operasi terkait harus konsisten menggunakan lock order meja yang sama untuk mencegah dua kasir membuat pesanan aktif bersamaan.

### Payment

- Gunakan `idempotency_key` unik.
- Lock order sebelum pembayaran.
- Verifikasi order belum Paid/Voided.
- Hitung ulang nilai dari data server.
- Simpan payment dan update order Paid dalam satu DB transaction.
- Duplicate request tidak boleh membuat payment baru.

### Order item update

- Lock order.
- Pastikan status masih bisa diedit.
- Ambil ulang harga/availability produk dan variasi.
- Simpan item dan snapshot.
- Hitung ulang total.

## 10. Responsive frontend

- Desktop: sidebar + content; POS dua area katalog/keranjang.
- Tablet: navigasi ringkas; katalog dan keranjang menyesuaikan lebar.
- Mobile: bottom navigation sesuai role, katalog mobile, keranjang bottom sheet/halaman khusus, checkout fokus, meja dalam grid.
- Gunakan komponen reusable dan design tokens.
- Jangan mengandalkan hover.
- Hindari horizontal overflow pada alur kasir.
- Uji pada viewport kecil, tablet, dan desktop.

PWA/offline dapat dipertimbangkan kemudian. Jangan membuat klaim dukungan offline sebelum sinkronisasi, konflik, dan retry dirancang serta diuji.

## 11. Printing

Gunakan halaman/komponen receipt dengan print stylesheet khusus. Sediakan konfigurasi ukuran 58 mm dan 80 mm. Browser dan printer berbeda dapat menghasilkan output berbeda; validasi harus memakai perangkat nyata.

Cetak ulang menggunakan data order/payment tersimpan dan tidak boleh memanggil proses create payment.

## 12. Environment configuration

Gunakan `.env.example` untuk mendokumentasikan variabel yang diperlukan. Jangan menaruh credential asli. Validasi konfigurasi penting saat aplikasi boot/deploy.

Minimal konfigurasi yang perlu ditentukan:
- APP environment/debug/url/timezone/locale.
- DB connection.
- Session/cookie.
- Cache/queue jika digunakan.
- Mail hanya jika fitur email ditambahkan.
- Logging.

Simpan timezone penyimpanan dan timezone tampilan dengan sengaja. Default tampilan operasional: `Asia/Jakarta`.

## 13. Observability dan error handling

- Log error backend dengan correlation/request ID jika memungkinkan.
- Jangan log password, session token, atau secret.
- Error pengguna harus aman dan dapat ditindaklanjuti.
- Catat kegagalan payment dan order update tanpa membuat data setengah jadi.
- Sediakan health check untuk deployment bila diperlukan.
- Pantau backup dan kapasitas storage/database.

## 14. Quality gates

Sebelum merge/release:
- Formatter dan lint lolos.
- Static analysis yang tersedia lolos.
- Automated tests lolos.
- Production frontend build berhasil.
- Migration dapat dijalankan pada database bersih.
- Authorization negatif diuji.
- Core payment flow dan concurrency diuji.
- Halaman utama diperiksa pada desktop/mobile.
- Tidak ada secret committed.
- Dokumentasi dan `.env.example` diperbarui.

## 15. Catatan implementasi Phase 6

- `EnsureUserIsActive` ditambahkan pada web middleware group. Permission tidak lagi bertahan pada session lama setelah `users.is_active = false`.
- `ReportService` mengganti N+1 query expected cash dengan satu agregat `GROUP BY orders.shift_id`; rumus kas tetap identik dengan `ShiftService::expectedCash()`.
- `scripts/verify-backup-restore.php` menjadi verifikasi portabel tanpa `mysqldump`: export JSON → migrate SQLite isolat → restore → bandingkan count/checksum → `PRAGMA foreign_key_check`.
- `.env.example` mendokumentasikan variabel seed user dan opsi secure cookie. Nilai seed password sengaja kosong.
- Panduan operasional ada di `docs/OPERATOR-GUIDE.md`, deployment di `docs/DEPLOYMENT.md`, dan backup/restore di `docs/BACKUP-RESTORE.md`.
- Dependency audit: Composer bersih. npm audit masih menyisakan advisory high pada toolchain build (`picomatch`/`vite`/`laravel-vite-plugin`) yang perbaikannya bersifat breaking; tidak di-force agar stack tidak berubah.
- Printer nyata dan perangkat mobile/tablet nyata belum tersedia; status dan keterbatasannya dicatat di `docs/TESTING-CHECKLIST.md`.

## 16. Foto produk

- Tidak ada integrasi Supabase Storage pada MVP ini. Foto produk memakai disk `public` Laravel yang sudah tersedia; `php artisan storage:link` wajib dijalankan saat setup dan deploy.
- Browser hanya menerima `image_url` hasil accessor model, bukan path internal disk.
- Upload/create memakai `POST /products` dengan `multipart/form-data`. Update memakai `POST /products/{product}` dengan `_method=PUT` karena PHP tidak mengisi `$_FILES` untuk request `PUT` multipart. Route backend tetap `PUT`.
- Validasi backend: `mimes:jpg,jpeg,png,webp`, `mimetypes:image/jpeg,image/png,image/webp`, maksimal 5 MB, dan dimensi maksimal 2400×1800. Nama file asli tidak dipakai; `Storage::putFile('products', ...)` menghasilkan nama unik dari MIME yang terdeteksi.
- Tidak ada GD/Imagick/Intervention di environment ini, sehingga tidak ada resizelossy. Dependency baru tidak ditambahkan; optimasi dibatasi pada batas ukuran, dimensi, lazy loading, dan `aspect-ratio`.
- `CatalogService` mengelola lifecycle file: upload baru sebelum update DB; rollback menghapus file baru; file lama dihapus setelah transaksi berhasil. Kegagalan penghapusan file lama dicatat di log, bukan ditelan.
- Soft delete dan penonaktifan produk tidak menghapus file gambar.
- Snapshot `order_items` tidak menyimpan gambar. Thumbnail Open Order dan takeaway berasal dari master produk aktif; riwayat Paid sengaja tidak memakai thumbnail agar histori tidak berubah saat master gambar diganti.
