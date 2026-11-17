# Business Rules — POS Rumah Makan Padang

Dokumen ini menetapkan aturan operasional yang harus dipatuhi backend, frontend, database, laporan, dan test. Jika aturan berubah, perbarui dokumen dan test sebelum mengubah implementasi.

## 1. Definisi dan status

### Tipe order
- `DINE_IN`: makan di tempat dan wajib mempunyai meja.
- `TAKEAWAY`: dibawa pulang dan tidak boleh mempunyai meja.

### Status order
- `OPEN`: pesanan dicatat dan masih dapat diubah.
- `AWAITING_PAYMENT`: pesanan siap dibayar; belum menjadi omzet.
- `PAID`: pembayaran berhasil dan order terkunci dari perubahan item.
- `VOIDED`: order dibatalkan dengan alasan dan audit trail.

Perubahan status hanya boleh melalui transisi yang diizinkan. Jangan menerima status bebas dari browser.

### Status meja turunan
- `AVAILABLE`: tidak ada DINE_IN order aktif.
- `OCCUPIED`: terdapat DINE_IN order `OPEN`.
- `AWAITING_PAYMENT`: terdapat DINE_IN order `AWAITING_PAYMENT`.

Status meja dihitung dari order aktif. Hindari menyimpan status meja sebagai sumber kebenaran kedua yang mudah tidak sinkron.

## 2. Aturan meja

1. DINE_IN wajib mempunyai `dining_table_id` yang aktif.
2. TAKEAWAY harus mempunyai `dining_table_id = null`.
3. Satu meja hanya boleh memiliki satu order aktif (`OPEN` atau `AWAITING_PAYMENT`).
4. Pembuatan order aktif harus mengunci record meja atau memakai mekanisme concurrency control setara.
5. Pemeriksaan ketersediaan dan pembuatan order harus berada dalam satu database transaction.
6. Kasir tidak dapat mengambil meja yang telah memiliki order aktif.
7. Meja menjadi tersedia setelah order berubah menjadi `PAID` atau `VOIDED`.
8. Meja yang dinonaktifkan tidak dapat dipilih untuk order baru, tetapi order aktif yang sudah ada tetap harus dapat diselesaikan.
9. Label/nomor meja disimpan sebagai snapshot pada order untuk keperluan histori.

**Catatan implementasi:** MySQL tidak menyediakan partial unique index sederhana untuk “hanya satu status tertentu per meja”. Gunakan row lock pada record `dining_tables` saat create/close order, dan pastikan semua jalur perubahan order mengikuti service/action yang sama. Pertimbangkan strategi constraint tambahan bila arsitektur membutuhkannya.

## 3. Aturan item dan variasi menu

1. Setiap order item mengacu ke produk dan variasi yang valid pada saat item ditambahkan.
2. Produk dan variasi harus aktif serta tersedia untuk pesanan baru.
3. Bila produk mempunyai beberapa variasi aktif, kasir wajib memilih satu variasi.
4. Variasi harus berasal dari produk yang dipilih; jangan mempercayai pasangan ID dari client tanpa verifikasi.
5. Harga diambil dari database oleh backend.
6. Setiap `order_item` menyimpan snapshot nama produk, nama variasi, satuan, dan harga.
7. Kuantitas harus bilangan positif sesuai batas operasional yang ditetapkan.
8. Catatan item tidak mengubah harga kecuali ada item/variasi berbayar tersendiri.
9. Produk/variasi nonaktif atau habis tidak boleh ditambahkan ke order baru.
10. Perubahan harga master tidak mengubah snapshot order yang telah dibuat.
11. Perubahan item hanya diperbolehkan selama order `OPEN` atau `AWAITING_PAYMENT` sesuai kebijakan checkout. Setelah `PAID`, item terkunci.
12. Jika perubahan harga terjadi ketika Open Order telah tersimpan, harga item yang sudah ada tidak berubah otomatis. Item baru memakai harga aktif saat item baru ditambahkan. Kebijakan ini harus ditampilkan konsisten kepada kasir.

## 4. Aturan perhitungan uang

- Simpan nilai Rupiah sebagai integer dalam satuan Rupiah (misalnya `BIGINT`), atau tipe fixed precision yang konsisten. Jangan gunakan floating point untuk nominal.
- Backend menghitung subtotal item: `unit_price_snapshot × quantity`.
- Subtotal order adalah penjumlahan seluruh subtotal item.
- Total order: `subtotal - discount + tax + service_charge`.
- Total tidak boleh negatif.
- Diskon, pajak, dan biaya layanan harus mempunyai aturan dan dasar perhitungan yang jelas di konfigurasi.
- Default pajak dan biaya layanan nonaktif.
- Diskon manual juga nonaktif secara default sampai pemilik menetapkan siapa yang boleh memberi diskon, jenis diskon, batas maksimal, dan apakah alasan wajib dicatat.
- Jika diskon diaktifkan, backend memvalidasi permission, batas, dasar perhitungan, dan audit log; kasir tidak boleh mengirim nominal diskon bebas tanpa validasi.
- Jangan menerima nilai subtotal/total hasil perhitungan browser sebagai sumber kebenaran.
- Jika aturan pembulatan diperlukan, dokumentasikan dan implementasikan satu kali di backend.
- Validasi batas maksimum nominal untuk mencegah overflow dan input tidak wajar.

## 5. Aturan Open Order

1. DINE_IN dibuat sebagai `OPEN` tanpa pembayaran.
2. Open Order tersimpan di database.
3. Refresh browser, navigasi, atau logout tidak boleh menghapus order.
4. Order dapat dibuka kembali oleh kasir yang memiliki permission.
5. Item dapat ditambahkan selama order belum `PAID` atau `VOIDED`.
6. Setiap perubahan order harus disimpan di server sebelum UI menganggap perubahan berhasil.
7. Open Order tidak masuk laporan omzet, jumlah transaksi Paid, atau rata-rata nilai transaksi.
8. Order yang siap ditagih dapat berubah ke `AWAITING_PAYMENT`.
9. Order tidak boleh dianggap Paid hanya karena tombol checkout ditekan.
10. Open Order yang ditinggalkan tidak dihapus otomatis. Sediakan laporan/daftar order lama untuk ditindaklanjuti; kebijakan auto-void memerlukan persetujuan pemilik dan audit trail.

## 6. Aturan pembayaran

1. MVP mendukung satu pembayaran penuh untuk satu order.
2. Pembayaran harus sama dengan total order; pembayaran tunai dapat menerima nominal lebih besar dan menghitung kembalian.
3. Pembayaran kurang ditolak.
4. Untuk tunai: `received_amount >= amount`, `change_amount = received_amount - amount`.
5. Untuk QRIS/transfer/debit: nominal pembayaran harus sama dengan total order pada MVP.
6. Pembayaran non-tunai manual hanya dikonfirmasi setelah kasir melakukan verifikasi berdasarkan SOP usaha.
7. Backend memeriksa status order sebelum menerima pembayaran.
8. Order `PAID` atau `VOIDED` tidak dapat dibayar lagi.
9. Pembayaran, perubahan order ke `PAID`, pencatatan paid_at, dan pembebasan meja dilakukan atomic.
10. Gunakan idempotency key unik untuk mencegah request pembayaran berulang.
11. Simpan pengguna yang memproses pembayaran dan waktu pembayarannya.
12. Cetak ulang struk tidak membuat payment baru.
13. Refund/void setelah Paid belum menjadi alur kasir biasa MVP; jika diperlukan, harus dibuat sebagai proses koreksi terotorisasi dengan audit trail, bukan menghapus payment.
14. Pembayaran menolak order tanpa item. Checkout hanyalah ringkasan; status Paid hanya terjadi setelah payment tersimpan.
15. Idempotency key unik dipakai untuk retry aman. Kunci yang sama tidak boleh dipakai untuk order lain.
16. Nama usaha pada struk Phase 4 memakai `APP_NAME` sampai modul pengaturan usaha tersedia.

## 7. Aturan pembatalan

- Order Open dapat dibatalkan oleh role/permission yang ditentukan.
- Pembatalan wajib mencatat alasan, pelaku, dan waktu.
- Order Paid tidak boleh dihapus atau diubah status secara langsung tanpa proses koreksi yang terotorisasi.
- Laporan harus mengecualikan order Voided dari omzet.
- Data order dan item tetap dipertahankan untuk audit.

## 8. Aturan shift dan kas

- Kasir membuka shift dengan modal awal.
- Rekomendasi MVP: satu shift aktif per kasir; konfirmasi aturan ini dengan pemilik.
- Order baru yang dibuat kasir pada shift aktif dikaitkan dengan shift tersebut.
- Kas yang diharapkan = modal awal + pembayaran tunai - pengembalian tunai - pengeluaran kas yang tercatat.
- Pembayaran QRIS/transfer/debit tidak menambah kas fisik.
- Selisih = kas aktual - kas yang diharapkan.
- Penutupan shift mencatat kas aktual, expected cash, selisih, pelaku, dan waktu.
- Shift yang sudah ditutup tidak dapat dibuka ulang tanpa tindakan khusus yang diaudit.
- Pengeluaran kas belum masuk MVP kecuali pemilik menyatakan wajib; jangan memasukkan pengeluaran yang tidak dicatat ke perhitungan.

Keputusan implementasi Phase 5 (asumsi, masih perlu konfirmasi pemilik): satu shift aktif per kasir diberlakukan oleh service; shift belum diwajibkan sebelum membuat order. Order hanya dihubungkan ke shift bila kasir memiliki shift aktif saat order dibuat. Karena `PaymentService` menyimpan `amount` sebagai tagihan akhir dan `received_amount - change_amount = amount`, expected cash dihitung sebagai `opening_cash + SUM(payments.amount tunai)`; pembayaran non-tunai tidak menambah kas fisik. Kasir dapat menutup shift miliknya; admin/owner dengan `manage-shifts` dapat menutup shift siapa pun. Pembukaan dan penutupan shift dicatat di `audit_logs`.

## 9. Aturan laporan

- Omzet dihitung dari order yang berstatus `PAID` berdasarkan `paid_at`.
- Open, Awaiting Payment, dan Voided tidak dihitung sebagai omzet.
- Jumlah transaksi adalah jumlah order Paid, bukan jumlah order dibuat.
- Menu terlaris menggunakan quantity dari item pada order Paid.
- Rata-rata nilai transaksi = omzet / jumlah order Paid; jika jumlah transaksi nol, tampilkan nol atau tanda kosong yang jelas, bukan error.
- Periode laporan menggunakan timezone usaha Asia/Jakarta untuk batas hari, lalu dikonversi konsisten ke penyimpanan waktu.
- Tanggal mulai dan akhir filter harus inklusif sesuai definisi bisnis dan diuji pada batas tengah malam.
- Laporan metode pembayaran berasal dari payment yang berhasil.
- Setiap angka laporan harus dapat ditelusuri ke order/payment sumbernya.

Implementasi Phase 5: halaman dan export CSV laporan hanya untuk admin/owner (`view-reports`); kasir mendapat 403 dari server. Riwayat dapat diakses semua pengguna terautentikasi, menampilkan `PAID` dan `VOIDED`, dengan filter nomor, tanggal, kasir, tipe order, metode bayar, status, dan pagination. Mode aktif tetap menampilkan `OPEN`/`AWAITING_PAYMENT` agar alur lama tidak berubah. export CSV memakai filter dan angka yang sama dengan halaman laporan.

## 10. Aturan keamanan dan audit

Audit minimal untuk:
- Perubahan harga/variasi.
- Perubahan status ketersediaan.
- Pembuatan dan pembatalan order.
- Perubahan item pada order aktif.
- Pembayaran.
- Penutupan shift.
- Perubahan role/akun.
- Perubahan pengaturan pajak/biaya.

Audit log mencatat actor, action, entity, entity ID, timestamp, dan metadata yang aman. Jangan log password, token, atau informasi sensitif.

## 11. Asumsi untuk dikonfirmasi

- Apakah order dine-in boleh dibatalkan oleh kasir atau hanya admin/owner?
- Apakah kasir wajib membuka shift sebelum membuat order? (Implementasi saat ini: tidak wajib; order tetap dibuat tanpa `shift_id` bila tidak ada shift aktif.)
- Apakah pelanggan boleh membayar terpisah (split bill)?
- Apakah satu meja boleh dipakai untuk rombongan dengan pembayaran terpisah?
- Apakah harga item yang sudah masuk Open Order tetap mengikuti harga saat ditambahkan? Default dokumen: ya.
- Bagaimana SOP verifikasi QRIS/transfer?
- Apakah ada pengeluaran kas yang perlu masuk rekonsiliasi shift?
