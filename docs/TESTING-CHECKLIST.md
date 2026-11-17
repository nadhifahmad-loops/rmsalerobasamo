# Testing Checklist — POS Rumah Makan Padang

Gunakan checklist ini pada setiap fase. Catat hasil, environment, data uji, dan bug yang ditemukan. Jangan mencentang test yang belum benar-benar dijalankan.

## 1. Authentication dan authorization

- [ ] Login dengan kredensial valid berhasil.
- [ ] Login dengan password salah ditolak dengan pesan aman.
- [ ] Akun nonaktif tidak dapat login.
- [ ] Logout mengakhiri session.
- [ ] User tanpa login tidak dapat mengakses halaman/endpoint internal.
- [ ] Kasir tidak dapat mengakses endpoint pengelolaan user.
- [ ] Kasir tidak dapat mengubah harga master melalui request langsung.
- [ ] Kasir tidak dapat membatalkan Paid order tanpa permission.
- [ ] Admin/Owner hanya dapat melakukan aksi sesuai permission.
- [ ] Rate limit login bekerja.

## 2. Catalog dan variasi

- [ ] Admin dapat membuat kategori.
- [ ] Admin dapat membuat produk.
- [ ] Admin dapat membuat satu variasi default.
- [ ] Admin dapat membuat beberapa variasi dengan harga berbeda.
- [ ] Kasir wajib memilih variasi jika lebih dari satu pilihan tersedia.
- [ ] Variasi nonaktif tidak dapat dipilih.
- [ ] Produk habis/nonaktif tidak dapat ditambahkan ke order baru.
- [ ] Harga negatif ditolak.
- [ ] Perubahan harga master tidak mengubah order item lama.
- [ ] Produk yang memiliki histori tidak dapat dihapus permanen.

## 3. Meja dan concurrency

- [ ] Meja aktif terlihat di daftar.
- [ ] Meja nonaktif tidak tersedia untuk order baru.
- [ ] Kasir dapat memilih meja tersedia.
- [ ] DINE_IN tanpa meja ditolak backend.
- [ ] TAKEAWAY dengan meja ditolak atau dinormalisasi sesuai kontrak.
- [ ] Membuat order kedua untuk meja yang sama ditolak.
- [ ] Dua request bersamaan untuk satu meja tidak menghasilkan dua Open Order.
- [ ] Meja kembali tersedia setelah order Paid.
- [ ] Meja kembali tersedia setelah order Void yang sah.
- [ ] Order aktif tetap dapat diselesaikan walau meja dinonaktifkan setelah order dibuat.

## 4. Open Order

- [ ] Kasir dapat membuat DINE_IN Open Order.
- [ ] Order tersimpan di database.
- [ ] Refresh browser tidak menghilangkan order.
- [ ] Navigasi ke halaman lain tidak menghilangkan order.
- [ ] Kasir dengan permission dapat membuka order yang sedang aktif.
- [ ] Kasir dapat menambah item sebelum Paid.
- [ ] Kasir dapat mengubah quantity sebelum Paid.
- [ ] Kasir dapat menghapus item sebelum Paid sesuai aturan.
- [ ] Produk/variasi yang sudah tidak tersedia ditangani sesuai aturan saat add item.
- [ ] Harga snapshot item lama tidak berubah.
- [ ] Open/Awaiting Payment tidak masuk omzet.
- [ ] Order Paid tidak dapat diubah itemnya melalui endpoint biasa.

## 5. Perhitungan

- [ ] Subtotal item = harga snapshot × quantity.
- [ ] Subtotal order adalah jumlah subtotal item.
- [ ] Total menghitung diskon, pajak, dan service charge sesuai konfigurasi.
- [ ] Total tidak negatif.
- [ ] Nominal besar tidak menyebabkan overflow.
- [ ] Nilai dari browser yang dimanipulasi diabaikan/dihitung ulang backend.
- [ ] Perubahan harga master tidak memengaruhi item yang sudah tersimpan.
- [ ] Semua tampilan nominal menggunakan format Rupiah secara konsisten.

## 6. Payment

- [ ] Tunai dengan nominal tepat berhasil.
- [ ] Tunai dengan nominal lebih besar menghitung kembalian.
- [ ] Tunai dengan nominal kurang ditolak.
- [ ] QRIS manual dapat dicatat setelah konfirmasi kasir.
- [ ] Transfer/debit dicatat sesuai metode yang diaktifkan.
- [ ] Order Paid tidak dapat dibayar lagi.
- [ ] Order Voided tidak dapat dibayar.
- [ ] Double click tidak membuat dua payment.
- [ ] Retry request dengan idempotency key yang sama tidak membuat payment baru.
- [ ] Pembayaran dan update order terjadi atomic.
- [ ] Simulasikan error di tengah proses: tidak ada payment tanpa update order Paid atau sebaliknya.
- [ ] Meja dilepas setelah pembayaran commit.
- [ ] Pembayaran gagal mempertahankan order dalam status yang benar.

## 7. Receipt

- [ ] Receipt menampilkan nomor order/transaksi.
- [ ] Receipt menampilkan tanggal/waktu dan kasir.
- [ ] Receipt menampilkan tipe order dan meja bila dine-in.
- [ ] Receipt menampilkan nama/variasi, qty, harga, dan subtotal.
- [ ] Receipt menampilkan total dan metode pembayaran.
- [ ] Receipt tunai menampilkan uang diterima dan kembalian.
- [ ] Layout cetak 58 mm diperiksa dengan printer nyata.
- [ ] Layout cetak 80 mm diperiksa dengan printer nyata.
- [ ] Cetak ulang tidak membuat payment baru.
- [ ] Printer tidak tersedia menghasilkan feedback yang jelas.

## 8. Riwayat dan laporan

- [ ] Riwayat dapat dicari berdasarkan nomor.
- [ ] Filter tanggal bekerja pada batas awal/akhir hari.
- [ ] Filter kasir, tipe order, metode, dan status bekerja.
- [ ] Pagination bekerja.
- [ ] Open/Awaiting Payment tidak masuk omzet.
- [ ] Voided tidak masuk omzet.
- [ ] Paid masuk omzet berdasarkan paid_at.
- [ ] Jumlah transaksi sesuai jumlah Paid order.
- [ ] Menu terlaris menghitung item dari Paid order.
- [ ] Laporan per metode sesuai payment.
- [ ] Hari laporan mengikuti Asia/Jakarta.
- [ ] Rata-rata saat tidak ada transaksi tidak menyebabkan pembagian nol.

## 9. Shift

- [ ] Kasir membuka shift dengan modal awal valid.
- [ ] Modal awal negatif ditolak.
- [ ] Shift aktif dikaitkan dengan order yang dibuat.
- [ ] Pembayaran tunai menambah expected cash.
- [ ] Pembayaran non-tunai tidak menambah kas fisik.
- [ ] Kas aktual dapat dimasukkan.
- [ ] Selisih dihitung benar.
- [ ] Shift yang sudah ditutup tidak dapat dipakai untuk order baru.
- [ ] Kasir tidak dapat menutup shift milik kasir lain tanpa permission.

## 10. Responsive dan usability

Uji minimal pada viewport berikut (atau perangkat nyata yang mendekati):
- [ ] 360 × 800 mobile kecil.
- [ ] 390 × 844 mobile umum.
- [ ] 768 × 1024 tablet.
- [ ] 1024 × 768 tablet/desktop kecil.
- [ ] 1366 × 768 desktop.
- [ ] 1440 × 900 desktop.

Periksa:
- [ ] Tidak ada horizontal overflow yang tidak disengaja.
- [ ] Bottom navigation tidak menutupi konten.
- [ ] Kategori dapat digeser di mobile.
- [ ] Tombol cukup besar untuk disentuh.
- [ ] Keranjang mobile mudah ditemukan.
- [ ] Bottom sheet dapat discroll.
- [ ] Keyboard mobile tidak menutupi input nominal.
- [ ] Checkout dapat diselesaikan pada mobile.
- [ ] Status meja dapat dibedakan tanpa mengandalkan warna saja.
- [ ] Tabel riwayat/laporan memiliki presentasi mobile yang layak.
- [ ] Fokus keyboard dan label form berfungsi.

## 11. Security dan reliability

- [ ] Validasi server-side diuji dengan payload invalid.
- [ ] Authorization diuji dengan request langsung, bukan hanya UI.
- [ ] CSRF protection aktif sesuai framework.
- [ ] SQL injection/XSS baseline diperiksa.
- [ ] File upload dibatasi (jika upload tersedia).
- [ ] Secret tidak masuk repository atau frontend bundle.
- [ ] Log tidak memuat password/token.
- [ ] Error tidak mengekspos stack trace pada production.
- [ ] Database backup dapat dibuat.
- [ ] Backup dapat direstore ke environment uji.
- [ ] Migration dapat dijalankan dari database bersih.

## 12. Release gate

- [ ] Semua test kritis untuk flow login → meja → Open Order → add item → checkout → payment → receipt lulus.
- [ ] Tidak ada bug blocker/critical terbuka.
- [ ] Automated test dijalankan dan hasil disimpan.
- [ ] Build production berhasil.
- [ ] Migration dan seeder awal ditinjau.
- [ ] `.env.example` mutakhir tanpa secret.
- [ ] README setup/deploy mutakhir.
- [ ] Prosedur backup/restore tersedia.
- [ ] Uji pada perangkat/printer operasional sudah dilakukan atau keterbatasannya dinyatakan.

## 13. Status verifikasi Phase 5

Perintah dan hasil yang dijalankan sebelum Phase 5 dinyatakan selesai:

- `php artisan test --colors=never --compact` — **170 passed, 1070 assertions** (termasuk test baru Takeaway, OrderHistory, Shift, dan Report).
- `./vendor/bin/pint.bat --test` — **PASS**, 133 file.
- `npx tsc --noEmit`, `npx eslint .`, `npm run format:check` — bersih.
- `npm run build` — sukses.
- `migrate:fresh` → `migrate:rollback --step=2` → `migrate` pada SQLite isolat — sukses untuk migration `000008` dan `000009`.
- Smoke HTTP 32/32 langkah: login kasir → buka shift → takeaway → bayar tunai → retry idempotency → riwayat/filter/detail → tutup shift → authorization negatif → login admin → laporan → export CSV.
- Integritas DB smoke: `fk_violations=0`, satu order `PAID` takeaway dengan `shift_id`, `subtotal=total=sum_items=50000`, `paid_at` terisi, snapshot harga tersimpan, `expected_cash=150000`, `actual_cash=149000`, `difference=-1000`, audit `shifts.opened`, `shifts.closed`, `orders.created`, `orders.paid`, `payments.created` tercatat, dan ringkasan laporan sama dengan CSV.

Keterbatasan yang masih terbawa ke Phase 6: desktop browser tidak terhubung sehingga pemeriksaan viewport 360×800 sampai 1440×900 dilakukan melalui review kelas responsif, bukan screenshot perangkat; printer thermal 58/80 mm nyata belum diuji; koneksi MySQL lokal tidak tersedia saat verifikasi sehingga smoke memakai SQLite isolat tanpa mengubah `.env`.

## 14. Status verifikasi Phase 6

Perintah dan hasil yang dijalankan pada 18 November 2026:

- `php artisan test --colors=never --compact` — **172 passed, 1092 assertions**.
- `./vendor/bin/pint.bat --test` — **PASS**, 134 file setelah perbaikan style `bootstrap/app.php`.
- `npx tsc --noEmit`, `npx eslint .`, `npm run format:check` — bersih setelah dependency frontend dipulihkan.
- `npm run build` — sukses, Vite 6.4.6, 2043 modules.
- `composer audit --locked --no-dev --format=summary` — **No security vulnerability advisories found**.
- `npm audit fix --omit=dev` — menghapus advisory high/moderate pada `axios`, `@babel/*`, `form-data`, dan `shell-quote`.
- `npm install` — memulihkan dev dependency yang ikut terhapus saat audit fix dengan `--omit=dev`.
- `npm audit --omit=dev --audit-level=high` — masih melaporkan 5 high pada rantai `picomatch` → `vite` → `laravel-vite-plugin`. Perbaikan otomatisnya memaksa downgrade breaking ke `laravel-vite-plugin@0.2.4`, sehingga tidak dijalankan. Risiko ini hanya pada toolchain build frontend, bukan runtime aplikasi yang dikirim ke browser.
- `php scripts/verify-backup-restore.php` dengan source SQLite isolat — **RESULT=OK**, 20 baris, checksum sumber `84ce84fe…c4cbd2` sama dengan checksum restore, `fk_violations=0`.
- Migration `000001`–`000009` berhasil dijalankan dari database SQLite kosong pada proses restore di atas.
- `migrate:fresh` → `migrate:rollback --step=9` → `migrate` pada SQLite isolat — sukses untuk seluruh migration.
- Middleware `EnsureUserIsActive` diuji dengan skenario akun dinonaktifkan setelah login; sesi dicabut dan pengguna diarahkan ke login dengan pesan `auth.inactive`.
- Optimasi `ReportService` diuji dengan dua shift dan pembayaran tunai/QRIS; kas diharapkan masing-masing shift tetap benar dengan satu query agregat.

Bug yang ditemukan dan diperbaiki selama Phase 6:

- Test rekap shift gagal setelah pergantian tanggal karena fixture memakai `now()`; sekarang memakai `opened_at` eksplisit di dalam periode uji.
- Akun nonaktif masih bisa memakai session aktif; sekarang dicabut oleh middleware.
- Laporan melakukan query per shift untuk expected cash; sekarang memakai satu query `GROUP BY orders.shift_id`.

Keterbatasan yang tetap terbuka:

- Printer thermal 58/80 mm nyata belum diuji; stylesheet cetak sudah ditinjau tetapi belum diverifikasi dengan perangkat.
- Pengujian responsive nyata pada smartphone/tablet belum dilakukan; review masih berbasis breakpoints dan struktur layout.
- MySQL lokal sudah diperbaiki dengan menjalankan seluruh migration yang pending. Verifikasi backup/restore tetap memakai source SQLite isolat agar tidak bergantung pada ketersediaan MySQL; prosedur `mysqldump` untuk produksi tetap didokumentasikan.
- 5 advisory high pada toolchain build frontend masih terbuka karena perbaikannya membutuhkan downgrade breaking; perlu dipantau saat upgrade Vite/Laravel plugin berikutnya.

## 15. Verifikasi data dummy lokal

Data dummy dibuat untuk memudahkan smoke alur end-to-end di lingkungan lokal, bukan untuk produksi. Perintah dan hasil yang dijalankan:

- `php artisan db:seed --class=DemoDataSeeder --no-interaction` — sukses; katalog Padang, 12 meja, dan transaksi contoh terbentuk.
- `php artisan db:seed --class=DemoDataSeeder --no-interaction` dijalankan ulang — sukses, tidak menambah data duplikat (idempoten).
- `php scripts/verify-demo-data.php` — **RESULT=OK**: 5 kategori, 26 produk, 52 variasi, 12 meja, 4 shift total (3 shift dummy; 1 shift OPEN milik kasir lain sudah ada sebelumnya), 10 order, 25 order item, 7 payment, 24 audit log.
- Pemeriksaan integritas dummy: jumlah payment sama dengan order `PAID`, nominal payment sama dengan `order.total`, `sum(order_items.subtotal) = order.total`, idempotency key dan nomor order unik, tidak ada kasir dengan lebih dari satu shift `OPEN`, dan `expected_cash`/`difference` shift tertutup sesuai rumus kas tunai.
- `php artisan test --colors=never --compact` setelah seeding — **172 passed, 1092 assertions**.
- `./vendor/bin/pint.bat --test` — **PASS**, 140 file.

Seeder dummy tidak dipanggil oleh `DatabaseSeeder` dan menolak dijalankan saat `APP_ENV=production`. Prosedur lengkap ada di `README.md` bagian “Data dummy lokal (opsional)”.

## 16. Verifikasi identitas visual dan theme

Pembaruan branding tidak mengubah schema, business logic, auth, role, route, atau API contract. Token didefinisikan sekali di `resources/css/app.css`; logo PNG resmi dipakai di sidebar, header mobile, login, favicon, dan struk.

Perintah dan hasil yang dijalankan:

- `npm run build` — sukses, Vite 6.4.6, 2042 modules.
- `npx tsc --noEmit` — bersih.
- `npx eslint .` — bersih.
- `npm run format:check` — bersih setelah Prettier dijalankan pada file yang terdampak.
- `./vendor/bin/pint.bat --test` — **PASS**, 140 file.
- `php artisan test --colors=never --compact` — **172 passed, 1092 assertions**, mencakup login, katalog/POS, item/keranjang, checkout/pembayaran, struk, meja, dashboard, riwayat, shift, laporan, profil, dan pengguna.
- `Invoke-WebRequest http://127.0.0.1:8000/login` — **200**, tautan favicon PNG resmi ditemukan, tidak ada referensi `logo.svg` bawaan.
- Pemeriksaan bundle produksi — path `logo-salero-basamo-jati.png` ditemukan pada chunk `app-layout` dan `login`; nilai token `#d50112`, `#b9000f`, `#fdce02`, `#faf9f6`, `#211d1b`, `#746e68`, `#e9e4dd`, `#fff0f1`, dan `#fff8d9` ditemukan pada CSS hasil build.
- `grep` pada `resources/` — tidak ada lagi `border-gray-300`, `border-amber-500`, `text-neutral-600`, `text-red-600`, `hsl(var(--sidebar-...))`, atau `bg-neutral-*` pada komponen aplikasi yang terdampak. Warna hardcoded tersisa hanya pada definisi token dan stylesheet cetak struk yang memang berdiri sendiri.

Keterbatasan pada verifikasi ini:

- Browser desktop tidak terhubung ke sesi agent, jadi console browser dan screenshot viewport nyata tidak dapat diperiksa; tidak ada backend error pada 172 test dan halaman login merespons 200.
- Responsive desktop/tablet/mobile masih diverifikasi melalui review struktur dan kelas Tailwind, bukan perangkat fisik.
- Logo asli berukuran 1774×887 (rasio 2:1) dipasang dengan `object-contain`; tidak ada versi simbol terpisah, sehingga favicon memakai PNG yang sama sesuai aturan “apabila tersedia”.

## 17. Verifikasi katalog visual dan foto produk

Perubahan ini menambah `products.image_path` nullable, disk `public` untuk file, kartu katalog visual, dialog variasi, dan lifecycle foto. Tidak ada perubahan harga, variasi, snapshot order, auth, role, atau alur pembayaran.

Perintah dan hasil yang dijalankan:

- `php artisan migrate:status` — kolom baru tercatat **Pending** sebelum migrasi.
- `php artisan migrate --force` — `2026_11_18_000001_add_image_path_to_products_table` **DONE**; tidak ada `migrate:fresh` dan tidak ada data existing yang dihapus.
- `php artisan storage:link` — symlink `public/storage` → `storage/app/public` berhasil dibuat.
- `php artisan test --filter=ProductImageTest --colors=never` — **8 passed, 57 assertions**: create dengan foto, tanpa foto, format ditolak, ukuran >5 MB ditolak, replace foto, hapus foto, simulasi upload gagal, dan authorization kasir ditolak.
- `php artisan test --colors=never --compact` — **180 passed, 1149 assertions**.
- `./vendor/bin/pint.bat --test` — **PASS**, 142 file.
- `npx tsc --noEmit` — bersih.
- `npx eslint .` — bersih.
- `npm run format:check` — bersih.
- `npm run build` — sukses, Vite 6.4.6, 2045 modules.
- `Invoke-WebRequest http://127.0.0.1:8000/images/products/placeholder.svg` — **200**, 804 byte.
- `Invoke-WebRequest http://127.0.0.1:8000/storage/products/http-check.svg` — **200**, 804 byte; berkas uji dihapus setelah pemeriksaan.
- Verifikasi Inertia: `GET /catalog` mengembalikan `products.data.0.image_url` sesuai URL disk untuk produk berfoto dan `null` untuk produk tanpa foto.

Keterbatasan yang tetap terbuka:

- Browser desktop tidak terhubung ke sesi agent, sehingga console browser, screenshot, dan interaksi sentuh nyata tidak dapat diperiksa. Responsive diverifikasi melalui struktur/kelas Tailwind dan build, bukan perangkat fisik.
- Printer thermal 58/80 mm nyata masih belum diuji.
- Tidak ada GD/Imagick/Intervention; gambar tidak di-resize di server. Validasi ukuran/dimensi dan lazy loading dipakai sebagai pengganti, tanpa menambah dependency baru.
- Belum ada uji beban dengan ratusan foto; pagination katalog tetap dipakai sehingga jumlah gambar per halaman dibatasi konfigurasi POS.

## 18. Verifikasi dummy foto produk lokal

Dummy foto dibuat sebagai ilustrasi SVG bernama produk, bukan foto raster. Ini hanya untuk data lokal dan tidak mengubah aturan upload production yang tetap membatasi JPG/PNG/WebP.

Perintah dan hasil yang dijalankan:

- `php artisan db:seed --class=DemoProductImageSeeder --force` — **INFO Seeding database**, 26 produk dummy mendapat `products.image_path` ke `products/demo/<slug>.svg`.
- `php scripts/verify-demo-data.php` — **RESULT=OK**, `products=26`, `demo_product_images=26`; seluruh path dummy memiliki file sumber dan file storage.
- `Get-ChildItem public/images/products/demo` — **26 file SVG**.
- `Get-ChildItem storage/app/public/products/demo` — **26 file SVG**.
- Parsing XML seluruh aset — **26 valid, 0 invalid**.
- Pemeriksaan teks SVG — **26/26** nama produk tertulis sesuai `CatalogSeeder`; tidak ada yang missing.
- `Invoke-WebRequest http://127.0.0.1:8000/storage/products/demo/rendang-daging.svg` — **200**, 1485 byte, `Content-Type: image/svg+xml`.
- Uji transaksional rollback pada product `Rendang Daging`: `custom_preserved=YES`, `null_backfilled=YES`, `idempotent=YES`, `restored=YES`. Foto upload custom tidak ditimpa; path kosong diisi ulang; seeder bisa dijalankan berulang; data uji dikembalikan setelah verifikasi.
- `DemoDataSeeder` sekarang memanggil `DemoProductImageSeeder`, sehingga setup dummy baru otomatis mendapat foto tanpa perintah terpisah.

Catatan: dummy SVG sengaja dibuat oleh seeder dan ditulis langsung ke disk `public`. File ini bukan hasil upload pengguna, jadi tidak melalui validasi MIME upload JPG/PNG/WebP. Tidak ada data transaksi yang diubah oleh seeder gambar ini.
