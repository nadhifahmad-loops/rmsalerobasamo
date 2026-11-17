# User Flows — POS Rumah Makan Padang

Dokumen ini mendeskripsikan urutan interaksi utama. Diagram menggunakan Mermaid dan dapat dirender di editor Markdown yang mendukung Mermaid.

## 1. Dine-in: buat Open Order sampai pembayaran

```mermaid
flowchart TD
    A[Kasir login] --> B[Masuk halaman Meja]
    B --> C{Pilih meja}
    C -->|Tersedia| D[Buka POS untuk meja]
    C -->|Terisi| E[Buka Open Order yang ada]
    E --> F[Tambah item bila diperlukan]
    D --> G[Pilih kategori dan menu]
    G --> H[Pilih variasi bila tersedia]
    H --> I[Atur jumlah dan catatan]
    I --> J[Simpan Open Order]
    F --> J
    J --> K[Pelanggan makan]
    K --> L{Pesan tambahan?}
    L -->|Ya| M[Buka order dan tambah item]
    M --> K
    L -->|Tidak, selesai makan| N[Checkout / Siapkan tagihan]
    N --> O[Periksa ringkasan dan total]
    O --> P[Pilih metode pembayaran]
    P --> Q{Pembayaran valid?}
    Q -->|Tidak| R[Tampilkan error, order tetap belum dibayar]
    R --> P
    Q -->|Ya| S[Backend simpan payment dan tandai Paid secara atomic]
    S --> T[Meja menjadi tersedia]
    T --> U[Tampilkan/cetak struk]
```

## 2. Takeaway

```mermaid
flowchart TD
    A[Kasir login] --> B[Pilih Takeaway]
    B --> C[Pilih menu]
    C --> D[Pilih variasi dan jumlah]
    D --> E[Periksa keranjang]
    E --> F[Checkout]
    F --> G[Pilih metode pembayaran]
    G --> H{Nominal valid?}
    H -->|Tidak| I[Tampilkan kesalahan]
    I --> G
    H -->|Ya| J[Simpan order dan payment]
    J --> K[Tampilkan/cetak struk]
```

Default MVP adalah takeaway langsung checkout. Jika rumah makan perlu menyimpan takeaway belum dibayar atau menunggu dibuat, validasi dan ubah flow sebelum implementasi.

Implementasi Phase 5: saat tombol checkout takeaway ditekan, backend membuat order bertipe `TAKEAWAY` tanpa `dining_table_id`, menyimpan snapshot item, lalu mengarahkan ke halaman checkout yang sama dengan dine-in. Pembayaran penuh dibuat sekali di endpoint `orders.pay`.

## 3. Tambah item pada Open Order

```mermaid
flowchart TD
    A[Kasir buka daftar meja] --> B[Pilih meja terisi]
    B --> C[Backend memeriksa order masih Open]
    C --> D{Order masih dapat diubah?}
    D -->|Tidak| E[Tampilkan status dan larang perubahan]
    D -->|Ya| F[Pilih produk]
    F --> G[Backend validasi produk, variasi, dan harga]
    G --> H[Simpan item dan snapshot harga]
    H --> I[Hitung ulang total di backend]
    I --> J[Tampilkan order terbaru]
```

## 4. Pembayaran dan idempotency

```mermaid
flowchart TD
    A[Kasir konfirmasi pembayaran] --> B[Frontend mengirim request + idempotency key]
    B --> C[Backend validasi user dan permission]
    C --> D[Lock order dan baca ulang status serta total]
    D --> E{Order masih dapat dibayar?}
    E -->|Tidak| F[Return status terbaru tanpa membuat payment baru]
    E -->|Ya| G[Validasi nominal/metode]
    G --> H{Pembayaran valid?}
    H -->|Tidak| I[Rollback dan return error]
    H -->|Ya| J[Create payment]
    J --> K[Set order Paid dan paid_at]
    K --> L[Commit transaction]
    L --> M[Return receipt data]
```

## 5. Buka dan tutup shift

```mermaid
flowchart TD
    A[Kasir login] --> B{Ada shift aktif?}
    B -->|Tidak| C[Masukkan modal awal]
    C --> D[Backend membuat shift]
    B -->|Ya| E[Masuk POS]
    D --> E
    E --> F[Proses transaksi]
    F --> G[Kasir pilih Tutup Shift]
    G --> H[Backend hitung kas yang diharapkan]
    H --> I[Kasir hitung dan masukkan kas aktual]
    I --> J[Simpan selisih dan waktu tutup]
    J --> K[Shift ditutup]
```

Implementasi Phase 5: shift aktif tidak diwajibkan, tetapi bila ada, order yang dibuat kasir tersebut otomatis mendapat `shift_id`. Expected cash = modal awal + total pembayaran tunai bersih pada shift; non-tunai tidak masuk kas fisik. Kasir hanya dapat menutup shift miliknya; admin/owner dengan `manage-shifts` dapat menutup shift siapa pun.

## 6. Role dan akses halaman

| Area | Kasir | Admin | Owner |
|---|---:|---:|---:|
| POS dan Open Order | Ya | Ya | Sesuai kebutuhan |
| Meja | Lihat/pakai | Kelola | Lihat/kelola sesuai permission |
| Produk dan variasi | Lihat | Kelola | Kelola bila diizinkan |
| Riwayat transaksi | Ya (semua order) | Ya | Ya |
| Laporan | Tidak (403 dari server) | Ya | Ya |
| User management | Tidak | Ya | Ya |
| Pengaturan usaha | Tidak | Ya | Ya |
| Void/correction Paid | Tidak | Dengan permission | Dengan permission |

Authorization sebenarnya harus diperiksa backend; tabel ini bukan pengganti policy/gate.

## 7. Error dan kondisi khusus yang harus didesain

- Meja telah diambil kasir lain sebelum request tersimpan.
- Produk/variasi habis setelah halaman POS dibuka.
- Harga berubah ketika kasir masih membuka katalog.
- Order telah dibayar dari sesi lain.
- Request pembayaran terkirim dua kali.
- Browser refresh ketika menyimpan order.
- Jaringan terputus ketika checkout.
- Shift ditutup sebelum transaksi baru dibuat.
- Produk dinonaktifkan saat masih ada di Open Order.
- Printer tidak terhubung atau gagal mencetak.

Pada semua kondisi tersebut, tampilkan status yang jelas dan jangan membuat data transaksi ganda atau setengah tersimpan.

## 8. Kelola foto produk

1. Admin/Owner membuka halaman tambah atau edit produk.
2. Pilih berkas JPG, PNG, atau WebP maksimal 5 MB; pratinjau muncul sebelum submit.
3. Simpan produk. Backend memvalidasi ulang MIME dari konten berkas, ukuran, dan dimensi.
4. Pada edit, memilih berkas baru mengganti foto lama; tombol “Hapus foto” menghapus foto dan mengosongkan `image_path`.
5. File lama hanya dihapus setelah transaksi database berhasil. Jika upload gagal, foto lama tetap terpasang.
6. Kasir melihat foto pada kartu katalog, detail Open Order, dan keranjang takeaway; foto tidak mengubah snapshot transaksi.
