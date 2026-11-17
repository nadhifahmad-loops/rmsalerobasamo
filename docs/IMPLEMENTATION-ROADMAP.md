# Implementation Roadmap

Roadmap ini mengutamakan satu alur end-to-end yang benar dan dapat diuji. Jangan membangun semua halaman secara paralel tanpa backend dan kontrak data yang jelas.

## Phase 0 — Validasi dan project setup

**Output:** keputusan bisnis final untuk MVP, repository siap, dokumentasi disepakati.

- Konfirmasi asumsi pada PRD dan Business Rules dengan pemilik.
- Periksa perangkat kasir dan printer yang akan digunakan.
- Periksa apakah kasir wajib membuka shift.
- Konfirmasi aturan order takeaway, pembatalan, harga variasi, dan split bill.
- Setup Laravel + React + TypeScript + Inertia + Tailwind + MySQL dari starter kit yang kompatibel.
- Buat `.env.example`, README setup, formatter/lint/test command.
- Buat migration dan seeder role awal.

**Gate:** aplikasi bisa dijalankan lokal, migration bersih, login dasar bekerja.

## Phase 1 — Foundation dan access control

- Authentication.
- Roles/permissions.
- Layout desktop dan mobile.
- Design tokens dan UI primitives.
- Profile/logout.
- Audit logging dasar.

**Gate:** test login, logout, akses tanpa login, dan akses lintas role lulus.

## Phase 2 — Catalog

- Kategori.
- Produk.
- Variasi harga.
- Status aktif/tersedia.
- Pencarian dan filter katalog.
- Snapshot harga saat item masuk order.

**Gate:** admin dapat mengelola katalog; kasir hanya melihat dan memilih produk valid.

## Phase 3 — Tables dan Open Order

- CRUD meja.
- Status meja turunan.
- Create DINE_IN Open Order.
- Tambah/ubah/hapus item pada order aktif.
- Simpan note.
- Lock meja dan cegah double booking.
- Persistence setelah refresh.

**Gate:** dua kasir tidak bisa membuat active order pada meja yang sama; Open Order tetap ada setelah refresh.

## Phase 4 — Checkout dan payment

- Ringkasan checkout.
- Perhitungan backend.
- Tunai dan kembalian.
- Pencatatan QRIS/transfer/debit manual.
- Idempotency.
- Atomic payment.
- Status Paid dan release table.
- Receipt dan print stylesheet.

**Gate:** alur dine-in lengkap berjalan end-to-end; payment tidak ganda; total tervalidasi backend.

## Phase 5 — Takeaway, riwayat, shift, dan laporan

- Takeaway tanpa meja.
- Riwayat dan filter.
- Detail order/payment.
- Shift open/close.
- Laporan penjualan.
- Menu terlaris.
- Laporan metode pembayaran.
- Export sederhana bila masih masuk scope.

**Gate:** laporan cocok dengan fixture transaksi yang telah diuji.

**Status Phase 5:** selesai dan terverifikasi. Takeaway, riwayat/filter, detail payment, shift, laporan, menu terlaris, metode pembayaran, dan export CSV sudah tersambung ke backend. Gate terpenuhi: fixture smoke menghasilkan omzet 50.000 dari satu order Paid dengan expected cash 150.000 dan selisih -1.000; angka halaman laporan dan CSV sama.

## Phase 6 — Quality assurance dan deployment

- Regression testing.
- Security review.
- Query/performance review.
- Responsive review pada perangkat nyata.
- Print review dengan printer nyata.
- Backup/restore test.
- Production configuration.
- Deployment guide.
- Dokumentasi operator/kasir singkat.

**Gate:** seluruh acceptance criteria MVP terpenuhi; bug blocker/critical ditutup.

**Status Phase 6:** selesai dan terverifikasi di environment pengembangan. Regression test, security review, query review, production configuration, deployment guide, panduan operator, dan uji backup/restore telah dilakukan. Gate terpenuhi untuk lingkungan uji: seluruh test otomatis lulus, build produksi sukses, dependency audit ditindaklanjuti, dan tidak ada bug blocker/critical yang diketahui. Pemeriksaan printer thermal nyata dan perangkat kasir nyata masih menjadi keterbatasan environment dan tercatat di `docs/TESTING-CHECKLIST.md`; jangan menyatakan dukungan final sebelum QA perangkat nyata dilakukan.

## Vertical slice pertama yang disarankan

Sebelum membuat keseluruhan produk, prioritaskan satu alur nyata:

`Login kasir → pilih meja tersedia → pilih produk dan variasi → simpan Open Order → buka kembali order → checkout → pembayaran tunai → order Paid → meja tersedia → tampilkan struk.`

Vertical slice ini memvalidasi fondasi arsitektur, schema, business rules, UI desktop/mobile, dan transaksi database sekaligus.
