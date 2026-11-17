# Panduan Operator dan Kasir

Panduan singkat untuk pengguna harian. Seluruh angka yang ditampilkan di struk dan laporan berasal dari server, bukan dari browser.

## 1. Masuk dan keluar

1. Buka URL aplikasi.
2. Masukkan username atau email serta password.
3. Jika akun tidak aktif, hubungi administrator. Pesan error sengaja generik.
4. Keluar lewat menu profil → Logout setelah selesai bekerja.

Sesi akan otomatis dicabut bila akun dinonaktifkan administrator.

## 2. Membuka shift

1. Buka menu **Shift**.
2. Masukkan modal awal laci kas, misalnya `100000`.
3. Tekan **Buka shift**.

Hanya boleh ada satu shift aktif per kasir. Tutup shift yang masih aktif sebelum membuka shift baru. Nominal tunai yang dihitung sebagai kas diharapkan adalah modal awal ditambah pembayaran tunai pada order yang dibayar selama shift tersebut.

## 3. Pesanan makan di tempat

1. Buka menu **Meja**.
2. Pilih meja berstatus tersedia.
3. Pilih produk dan variasi yang tersedia, lalu tekan **Tambah**.
4. Periksa jumlah dan catatan sebelum checkout.
5. Item masih bisa diubah selama status pesanan `OPEN` atau `MENUNGGU BAYAR`.
6. Tekan **Siap dibayar** saat pesanan selesai.
7. Buka checkout, pilih metode pembayaran, masukkan nominal diterima untuk tunai, lalu konfirmasi.
8. Setelah pembayaran berhasil, status berubah menjadi `PAID` dan meja kembali tersedia.

Jangan menekan konfirmasi pembayaran dua kali. Aplikasi menolak pembayaran ganda; bila ragu, buka riwayat pesanan dan periksa nomor pembayaran sebelum mengulang.

## 4. Pesanan takeaway

1. Buka menu **Takeaway**.
2. Cari produk, pilih variasi, lalu masukkan ke keranjang.
3. Periksa total, lalu lanjut ke checkout.
4. Bayar dengan metode yang tersedia.
5. Cetak struk bila pelanggan membutuhkannya.

Takeaway tidak memakai meja.

## 5. Struk

Struk tersedia lewat halaman detail pesanan atau otomatis setelah pembayaran. Pilih lebar kertas `58 mm` atau `80 mm`, lalu tekan **Cetak struk**.

Pastikan printer thermal sudah terpasang dan driver/kertas sesuai. Cetak ulang tidak membuat pembayaran baru; struk selalu memakai data order dan pembayaran yang tersimpan.

## 6. Riwayat dan detail pesanan

1. Buka menu **Pesanan**.
2. Pilih tab **Aktif** atau **Riwayat**.
3. Gunakan filter tanggal, kasir, metode, tipe, atau status bila perlu.
4. Klik nomor pesanan untuk melihat item, total, pembayaran, dan struk.

Riwayat menampilkan pesanan `PAID` dan `VOIDED`. Pesanan `OPEN`/`AWAITING_PAYMENT` bukan omzet.

## 7. Menutup shift

1. Buka menu **Shift**.
2. Pilih shift aktif milik sendiri.
3. Hitung uang tunai di laci, masukkan nominal aktual.
4. Tekan **Tutup shift**.
5. Periksa selisih. Selisih negatif berarti kas aktif kurang dari kas diharapkan.

Administrator/owner dapat menutup shift kasir lain bila memiliki permission `manage-shifts`. Penutupan shift dicatat di audit log.

## 8. Laporan untuk admin/owner

1. Buka menu **Laporan**.
2. Pilih tanggal mulai dan akhir dalam timezone `Asia/Jakarta`.
3. Periksa omzet, transaksi paid, item terjual, metode pembayaran, menu terlaris, dan rekap shift.
4. Gunakan **Export CSV** untuk dibuka di spreadsheet.

Laporan hanya menghitung order `PAID` berdasarkan `paid_at`. Export CSV memakai filter dan angka yang sama dengan halaman laporan.

## 9. Masalah umum

| Masalah | Tindakan |
|---|---|
| Tidak bisa login | Periksa username/password. Bila masih gagal, hubungi administrator. Terlalu banyak percobaan akan diblokir sementara. |
| Meja tidak tersedia | Ada order aktif pada meja tersebut. Buka order itu atau pilih meja lain. |
| Tombol bayar tidak muncul | Pastikan pesanan sudah ditandai siap dibayar dan statusnya belum `PAID`. |
| Total berubah setelah harga produk diubah | Total dihitung saat item ditambahkan dan disimpan sebagai snapshot. Harga master baru hanya berlaku untuk order berikutnya. |
| Struk kosong/terpotong | Periksa kertas dan pilih lebar 58/80 mm yang sesuai. Cetak ulang dari detail pesanan. |
| Laporan berbeda dengan kas | Periksa filter tanggal, metode pembayaran non-tunai, dan shift yang belum ditutup. Hubungi administrator bila angka masih tidak cocok. |

## 10. Eskalasi

Laporkan ke administrator bila terjadi:

- pembayaran gagal tetapi status meja tidak kembali tersedia;
- duplikat nomor order/pembayaran;
- perubahan total yang tidak sesuai struk;
- akun tidak bisa login setelah password diperbarui;
- selisih kas yang tidak dapat dijelaskan.

Sertakan nomor order, nomor pembayaran, waktu, dan nama kasir. Jangan membagikan password atau token di chat/laporan.

## 11. Foto produk

Admin/Owner dapat membuka menu **Produk**, lalu Tambah atau Edit produk.

- Pilih foto JPG, PNG, atau WebP maksimal 5 MB; pratinjau muncul sebelum disimpan.
- Gunakan **Hapus foto** bila produk tidak perlu gambar. Produk tetap dapat disimpan tanpa foto.
- Foto diganti otomatis saat berkas baru dipilih dan disimpan.
- Jika upload gagal, pesan error muncul dan foto lama tetap terpasang.
- Kasir hanya melihat foto di katalog, Open Order, dan keranjang takeaway; kasir tidak dapat mengganti atau menghapus foto.
- Produk tanpa foto menampilkan placeholder bertuliskan “Belum ada foto”. Ini bukan error.
