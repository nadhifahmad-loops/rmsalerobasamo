# Product Requirements Document (PRD)

**Produk:** POS Rumah Makan Padang  
**Versi:** 1.2  
**Status:** Draft untuk validasi operasional dan implementasi MVP  
**Platform:** Responsive web application  
**Bahasa UI:** Bahasa Indonesia  
**Mata uang:** Rupiah (IDR)  
**Zona waktu tampilan:** Asia/Jakarta

## 1. Ringkasan produk

Aplikasi Point of Sale (POS) berbasis web untuk mendukung pencatatan pesanan, pengelolaan meja, pembayaran, struk, shift kasir, dan laporan penjualan rumah makan Padang.

Sistem harus dapat digunakan melalui desktop, tablet, dan smartphone. Pada layar mobile, pengalaman harus dirancang menyerupai aplikasi mobile dengan navigasi dan interaksi yang sesuai layar sentuh.

### Tujuan produk

1. Mempercepat pencatatan pesanan dan pembayaran.
2. Mendukung pola dine-in: pesanan dicatat lebih dahulu, pelanggan makan, lalu membayar setelah selesai.
3. Memudahkan kasir mengelola meja dan Open Order.
4. Mendukung menu dengan variasi harga/porsi.
5. Mengurangi kesalahan perhitungan dan pencatatan.
6. Memberikan laporan yang dapat dipercaya pemilik.

### Prinsip produk

- Operational first, bukan feature first.
- Akurasi transaksi lebih penting daripada banyak fitur.
- Alur kasir harus cepat dan dapat dipelajari tanpa pelatihan teknis panjang.
- Semua angka berasal dari database dan aturan perhitungan yang konsisten.
- Desktop dan mobile sama-sama merupakan target penggunaan.

## 2. Target pengguna dan hak akses

### Kasir

Dapat:
- Login/logout.
- Membuka dan menutup shift.
- Melihat status meja.
- Membuat pesanan dine-in dan takeaway.
- Menambah item ke Open Order.
- Membuka pesanan meja yang sedang aktif.
- Melakukan checkout dan mencatat pembayaran.
- Melihat transaksi sesuai kebijakan akses.
- Melihat dan mencetak ulang struk.

Tidak dapat:
- Mengubah harga master produk.
- Mengelola akun dan permission.
- Menghapus transaksi Paid.
- Mengubah laporan atau pengaturan sensitif.
- Membatalkan transaksi Paid tanpa otorisasi.

### Admin

Dapat:
- Mengelola produk, variasi, kategori, dan meja.
- Mengelola akun kasir.
- Mengakses transaksi dan laporan.
- Mengelola konfigurasi usaha, biaya, dan metode pembayaran.
- Melakukan tindakan koreksi sesuai permission dan audit trail.

### Owner

Dapat:
- Melihat dashboard dan seluruh laporan.
- Melihat transaksi dan shift.
- Mengelola atau menyetujui tindakan sensitif sesuai kebijakan.

Untuk MVP, Owner dapat menggunakan permission Admin bila pemilik belum memerlukan pemisahan akses. Struktur RBAC tetap harus memungkinkan role terpisah.

## 3. Ruang lingkup MVP

### Must have

1. Authentication dan role-based access control.
2. Dashboard ringkas sesuai role.
3. Pengelolaan kategori, produk, variasi, harga, dan status ketersediaan.
4. Pengelolaan meja.
5. POS untuk dine-in dan takeaway.
6. Open Order untuk dine-in.
7. Penambahan item ke pesanan yang masih terbuka.
8. Checkout dan pencatatan pembayaran tunai/non-tunai.
9. Struk dan cetak ulang.
10. Riwayat transaksi dan detail pesanan.
11. Laporan penjualan dasar.
12. Shift kasir dan rekonsiliasi kas dasar.
13. Audit trail untuk tindakan penting.
14. Tampilan responsive desktop, tablet, dan mobile.

### Tidak termasuk MVP

- Pengelolaan stok bahan baku, resep, dan food costing.
- Supplier dan pembelian bahan baku.
- Split bill atau pembayaran parsial.
- Penggabungan/pemindahan meja.
- Floor plan drag-and-drop.
- Multi-cabang.
- Integrasi payment gateway/QRIS otomatis.
- Order online dan QR ordering.
- Program loyalitas/CRM.
- Offline transaction synchronization.
- Prediksi penjualan.

Fitur di luar MVP tidak boleh dibuat hanya untuk mempercantik demo. Prioritaskan berdasarkan kebutuhan usaha yang telah divalidasi.

## 4. Modul fungsional

### 4.1 Authentication

- Login dengan username atau email dan password.
- Logout mengakhiri session.
- Route dan action dilindungi autentikasi.
- Permission diperiksa di backend.
- Akun nonaktif tidak dapat login.
- Terapkan rate limit percobaan login.

### 4.2 Dashboard

**Kasir:** status shift, shortcut POS, jumlah transaksi pada shift, meja tersedia/terisi, dan ringkasan operasional.

**Admin/Owner:** penjualan hari ini, jumlah transaksi Paid, rata-rata nilai transaksi, penjualan per metode pembayaran, dan menu terlaris. Grafik hanya ditampilkan jika membantu membaca tren.

Semua angka harus memiliki definisi periode dan sumber perhitungan yang jelas. Open Order tidak boleh masuk omzet.

### 4.3 Product management

Setiap produk memiliki kategori, nama, satuan, status aktif, dan status tersedia/habis. Produk dapat memiliki satu atau banyak variasi harga.

Contoh konseptual:
- Rendang: 1 potong, 2 potong, atau paket tertentu.
- Ayam: 1 potong atau ukuran/porsi yang tersedia.
- Minuman: gelas atau botol.

Harga pada contoh bukan harga produksi. Admin mengatur harga aktual.

Aturan:
- Produk dengan satu harga tetap memiliki satu variasi default atau mekanisme harga tunggal yang konsisten.
- Produk dengan banyak variasi mengharuskan kasir memilih variasi.
- Variasi nonaktif/habis tidak dapat dipilih untuk pesanan baru.
- Perubahan harga tidak memengaruhi snapshot pesanan yang sudah dibuat.
- Data produk yang pernah dipakai dalam order tidak dihapus permanen.

### 4.4 Table management

- Admin dapat membuat, mengubah label, mengaktifkan, dan menonaktifkan meja.
- Kasir melihat status meja: Tersedia, Terisi, atau Menunggu Pembayaran.
- Status diturunkan dari order aktif; jangan mengandalkan perubahan status manual yang terpisah.
- Satu meja hanya mempunyai satu order aktif pada satu waktu.
- Meja yang memiliki order aktif tidak dapat dipakai untuk order aktif lain.
- Meja kembali tersedia ketika order dibayar atau dibatalkan secara sah.

### 4.5 POS dan Open Order

Dua tipe order:

**Dine-in:** wajib memilih meja, membuat order, menyimpan sebagai Open, memungkinkan penambahan item, lalu checkout setelah pelanggan selesai.

**Takeaway:** tidak memerlukan meja. Default MVP adalah checkout langsung setelah item dipilih. Jika praktik usaha berbeda, ubah aturan ini setelah validasi.

Fitur POS:
- Cari produk.
- Filter kategori.
- Pilih variasi.
- Tambah/kurangi kuantitas.
- Tambahkan catatan item.
- Hapus item sebelum pembayaran.
- Lihat subtotal dan total.
- Simpan dan buka kembali Open Order.

Open Order harus tersimpan di server/database sehingga tidak hilang saat browser refresh atau kasir berpindah halaman. Status Open/Awaiting Payment bukan pendapatan.

### 4.6 Checkout dan payment

Checkout menampilkan tipe order, meja jika dine-in, item dan variasi, kuantitas, subtotal, diskon, pajak/biaya layanan jika berlaku, dan total akhir.

Metode MVP:
- Tunai.
- QRIS (pencatatan manual setelah kasir memverifikasi pembayaran).
- Transfer.
- Debit jika digunakan usaha.

Tunai: masukkan nominal diterima, hitung kembalian, tolak nominal kurang dari total.

Non-tunai: pilih metode dan catat nominal; kasir hanya mengonfirmasi pembayaran setelah menerima/mengecek bukti pembayaran sesuai SOP usaha. MVP tidak mengklaim verifikasi otomatis.

Penyimpanan pembayaran, perubahan status order, dan pelepasan meja harus dilakukan secara atomic. Pencegahan double submission wajib.

### 4.7 Receipt

Struk menampilkan nama/informasi rumah makan, nomor order/transaksi, tanggal dan waktu, kasir, tipe order, meja (jika dine-in), item/variasi, qty, harga satuan, subtotal, diskon/biaya tambahan jika ada, total, metode pembayaran, serta uang diterima dan kembalian untuk tunai.

Struk dapat ditampilkan dan dicetak. Cetak ulang tidak membuat pembayaran baru. Sediakan layout yang dapat dikonfigurasi untuk printer thermal 58 mm dan 80 mm; pengujian tetap memerlukan perangkat printer nyata.

### 4.8 Riwayat transaksi

- Pencarian nomor order/transaksi.
- Filter tanggal, kasir, metode pembayaran, tipe order, dan status.
- Detail item dan pembayaran.
- Cetak ulang struk.
- Open Order ditampilkan terpisah dari transaksi Paid.
- Transaksi Paid tidak dihapus permanen.

### 4.9 Reports

Laporan MVP:
- Penjualan harian.
- Penjualan berdasarkan rentang tanggal.
- Jumlah transaksi Paid.
- Total item terjual.
- Rata-rata nilai transaksi.
- Penjualan per metode pembayaran.
- Menu/variasi terlaris.
- Rekap shift dan selisih kas.

Definisi omzet dan periode harus eksplisit. Laporan berdasarkan waktu pembayaran (`paid_at`) sebagai default, bukan waktu order dibuat. Refund/void yang mungkin ditambahkan kemudian harus disajikan secara konsisten dan tidak boleh menghapus jejak transaksi.

### 4.10 Shift

- Kasir membuka shift dengan modal awal.
- Satu kasir tidak boleh mempunyai lebih dari satu shift aktif bila aturan usaha memilih satu shift aktif per kasir.
- Order yang dibuat saat shift aktif dihubungkan ke shift tersebut.
- Saat tutup shift, sistem menghitung kas yang diharapkan.
- Kasir memasukkan kas aktual.
- Sistem mencatat selisih dan waktu tutup.
- Penjualan non-tunai tidak ditambahkan ke kas fisik.

### 4.11 Katalog visual dan foto produk

- Setiap kartu menu POS menampilkan foto produk, nama, kategori, harga atau harga mulai dari, status ketersediaan, dan aksi tambah.
- Produk dengan beberapa variasi aktif membuka dialog pemilihan variasi; tidak ada variasi yang dipilih otomatis.
- Produk tanpa foto memakai placeholder lokal yang konsisten.
- Admin/Owner dapat mengunggah, mengganti, melihat pratinjau, dan menghapus foto produk pada halaman tambah/edit produk.
- Kasir hanya melihat foto produk; tidak dapat mengelola file gambar.
- Foto produk tidak mengubah snapshot item pesanan maupun aturan harga.

## 5. Kebutuhan non-fungsional

### Performance
- POS tidak memuat seluruh histori transaksi.
- Pencarian produk terasa responsif pada jaringan normal.
- Gunakan pagination untuk riwayat.
- Optimalkan gambar dan query.
- Hindari request dan render yang tidak diperlukan.

### Reliability
- Operasi penting menggunakan database transaction.
- Gagal menyimpan order/payment harus menghasilkan rollback yang benar.
- Tampilkan status request dengan jelas.
- Jangan menyatakan transaksi berhasil sebelum backend mengonfirmasi.
- Open Order tersimpan persisten di server.

### Security
- Password di-hash.
- Session dan cookie aman.
- Server-side authorization dan validation.
- Prepared query/ORM.
- CSRF protection sesuai mekanisme framework.
- Rate limiting login.
- Secret hanya di environment.
- Audit tindakan sensitif.
- Backup dan prosedur restore terdokumentasi.

### Compatibility

Browser modern desktop, Android, dan iOS. Halaman operasional utama harus dapat digunakan di desktop, tablet, dan smartphone.

## 6. Acceptance criteria tingkat produk

- Kasir dapat login dan hanya melihat aksi sesuai permission.
- Kasir dapat membuat dine-in dengan meja tersedia.
- Meja yang sudah memiliki Open Order tidak dapat dipesan oleh kasir lain.
- Open Order tetap tersimpan setelah refresh.
- Kasir dapat menambah item dan variasi ke Open Order.
- Order dine-in baru dibayar setelah pelanggan selesai.
- Kasir dapat membuat takeaway tanpa meja.
- Backend menghitung ulang seluruh nominal.
- Pembayaran tidak dapat diproses dua kali.
- Pembayaran sukses mengubah order menjadi Paid dan melepaskan meja secara atomic.
- Open Order tidak dihitung sebagai omzet.
- Harga transaksi lama tidak berubah saat master harga diperbarui.
- Riwayat dan laporan cocok dengan data pembayaran.
- Struk dapat dilihat dan dicetak.
- Halaman utama nyaman digunakan pada desktop dan mobile.
- Tidak ada tombol utama yang hanya menjadi placeholder.

Catatan implementasi Phase 5: takeaway dibuat sebagai order tanpa meja dalam satu transaction lalu langsung diarahkan ke checkout; pembayaran penuh tetap satu payment per order. Riwayat memakai filter `view=active|history` pada endpoint `/orders` yang sama. Shift dapat dibuka/ditutup kasir, dan laporan penjualan + CSV tersedia untuk admin/owner. Laporan hanya menghitung order `PAID` berdasarkan `paid_at`; `OPEN`, `AWAITING_PAYMENT`, dan `VOIDED` bukan omzet.

## 7. Asumsi yang perlu dikonfirmasi

Dokumen menggunakan asumsi berikut sampai pemilik mengonfirmasi:
1. Satu meja hanya memiliki satu order aktif.
2. Takeaway dibayar saat checkout langsung.
3. Tidak ada split bill atau pembayaran parsial pada MVP.
4. Tambah item diperbolehkan selama dine-in masih Open.
5. Pajak, biaya layanan, dan diskon manual nonaktif secara default.
6. QRIS/transfer diverifikasi manual oleh kasir pada MVP.
7. Satu lokasi rumah makan pada MVP.
8. Transaksi membutuhkan koneksi internet dan tidak dapat diselesaikan offline.
9. Satu order menggunakan satu metode pembayaran pada MVP.
10. Kasir perlu membuka shift sebelum mencatat transaksi; validasi kebijakan ini dengan pemilik. Implementasi Phase 5 belum mewajibkannya—order tanpa shift aktif tetap dicatat dengan `shift_id = null`.

Perbarui dokumen jika jawaban pemilik berbeda. Jangan diam-diam mengubah perilaku aplikasi.
