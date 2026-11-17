# UI/UX Guidelines — POS Rumah Makan Padang

## 1. Arah desain

Buat produk POS yang terasa seperti alat operasional bisnis: tenang, rapi, jelas, konsisten, dan cepat dipahami. Jangan mendesainnya seperti landing page atau dashboard startup generik.

Prinsip:
- Task-first: tindakan utama mudah ditemukan.
- Clear hierarchy: total, status meja, dan status pembayaran mudah dipahami.
- Low friction: kurangi langkah kasir.
- Predictable: pola tombol dan komponen konsisten.
- Accessible: kontras, label, fokus keyboard, dan target sentuh memadai.
- Responsive by design: desktop, tablet, dan mobile dirancang sebagai konteks penggunaan.

## 2. Hindari AI slop

Jangan:
- Memenuhi dashboard dengan kartu angka yang tidak digunakan.
- Menggunakan gradient, glassmorphism, glow, atau shadow berlebihan.
- Menambahkan animasi dekoratif tanpa tujuan.
- Menggunakan emoji sebagai icon kontrol.
- Menggunakan warna berbeda-beda tanpa token desain.
- Membuat tabel padat yang sulit dibaca.
- Menambahkan grafik tanpa pertanyaan bisnis yang dijawab.
- Menggunakan lorem ipsum atau data dummy pada tampilan produksi.
- Membuat tombol tanpa aksi nyata.

Gunakan komponen yang konsisten, ikon dari satu library, dan desain yang sesuai konteks operasional.

## 3. Struktur navigasi

### Desktop

Sidebar sesuai role:
- Dashboard
- POS / Pesanan
- Meja (relevan untuk kasir dan admin)
- Riwayat
- Shift
- Laporan (admin/owner)
- Menu dan Kategori (admin)
- Pengguna (admin)
- Pengaturan (admin/owner sesuai permission)
- Profil/logout

Jangan menampilkan menu yang tidak dapat diakses role tersebut.

### Mobile

Gunakan bottom navigation untuk 4–5 tujuan yang paling sering dipakai. Item dapat berbeda berdasarkan role.

Rekomendasi kasir:
- Beranda
- Meja
- POS/Pesanan
- Riwayat
- Akun

Fitur yang jarang digunakan dapat ditempatkan dalam menu tambahan, bukan memenuhi bottom navigation.

## 4. Halaman utama

### 4.1 Login

- Form ringkas.
- Label input terlihat.
- Password dapat ditampilkan/disembunyikan.
- Error login tidak membocorkan apakah username/email terdaftar.
- Loading state saat submit.

### 4.2 Dashboard kasir

Tampilkan status shift, tindakan mulai POS, status meja, pesanan aktif, dan ringkasan shift. Jangan menampilkan analitik yang tidak diperlukan kasir.

### 4.3 Dashboard owner/admin

Tampilkan ringkasan penjualan periode yang jelas, jumlah order Paid, rata-rata nilai order, metode pembayaran, dan menu terlaris. Gunakan filter tanggal. Jangan mencampur Open Order dengan omzet.

### 4.4 POS desktop

Layout dua area:
- Area katalog: pencarian, filter kategori, grid produk, harga/variasi, ketersediaan.
- Area order: tipe order, meja jika dine-in, daftar item, qty, catatan, subtotal, total, dan aksi simpan/checkout.

Pertahankan ringkasan keranjang mudah terlihat selama pemilihan menu. Jangan membuat modal untuk setiap tambah item.

### 4.5 POS mobile

- Header ringkas dan pencarian yang mudah dijangkau.
- Kategori dapat digeser horizontal.
- Katalog menggunakan satu atau dua kolom sesuai lebar layar.
- Tombol tambah harus cukup besar.
- Gunakan fixed bottom action untuk jumlah item dan total sementara.
- Keranjang dapat dibuka dengan bottom sheet atau halaman khusus.
- Checkout menggunakan halaman/panel yang fokus pada pembayaran.
- Pastikan fixed element tidak menutupi konten atau keyboard.

### 4.6 Daftar meja

- Tampilkan nomor/nama meja dan status dengan teks serta indikator visual.
- Meja terisi dapat menampilkan durasi dan total berjalan jika bermanfaat.
- Klik/tap meja membuka order aktif.
- Jangan mengandalkan warna saja untuk membedakan status.
- Pada mobile gunakan grid yang mudah disentuh.

### 4.7 Checkout

- Ringkasan item dan total terlihat jelas.
- Metode pembayaran mudah dipilih.
- Input tunai menggunakan input numerik yang sesuai di mobile.
- Tampilkan nominal diterima dan kembalian.
- Konfirmasi pembayaran jelas dan tidak mudah tertekan tidak sengaja.
- Tombol submit menunjukkan loading dan dinonaktifkan selama request.
- Jika request gagal, pertahankan konteks order dan tampilkan status dari server.

### 4.8 Riwayat dan laporan

- Desktop dapat menggunakan tabel dengan filter dan pagination.
- Mobile gunakan list/card ringkas atau tabel yang memang perlu horizontal scroll.
- Tampilkan tanggal dan status yang mudah dibaca.
- Bedakan Open Order dan Paid.
- Sediakan empty state yang menjelaskan cara mendapatkan data.

Catatan implementasi Phase 5: halaman takeaway dan detail order memakai layout satu kolom di mobile serta dua kolom (katalog + keranjang) pada `lg` ke atas dengan keranjang sticky. Filter riwayat dan laporan memakai grid yang rapat di mobile dan kolom penuh di desktop; tabel laporan/shift menggunakan `overflow-x-auto` dengan lebar minimum agar tidak merusak layout. Bottom navigation hanya memuat lima tujuan dan tersembunyi sejak `md`; tautan Takeaway, Riwayat, dan Shift tetap tersedia di sidebar, dashboard, dan halaman Pesanan.

### 4.9 Kartu produk visual

- Foto menempati area atas kartu dengan rasio `4:3`, `object-fit: cover`, `loading="lazy"`, dan placeholder lokal saat tidak ada gambar atau URL gagal.
- Nama, harga, kategori, satuan, dan status tetap berada di bawah foto dengan kontras yang cukup.
- Satu variasi dapat langsung ditambahkan. Beberapa variasi membuka dialog pemilihan; jangan memilih default secara otomatis.
- Produk habis tetap menampilkan foto, tetapi aksi tambah dinonaktifkan dan diberi label “Habis”.
- Grid memakai satu kolom di layar kecil, dua kolom pada `sm`, dan tiga kolom hanya ketika ruang katalog cukup lebar.
- Thumbnail keranjang lebih kecil dari foto katalog dan tidak boleh membuat daftar item terlalu padat.

## 5. Design system

Buat design tokens untuk:
- Warna utama, netral, border, background, dan status.
- Typography scale.
- Spacing scale.
- Radius.
- Shadow/elevation minimal.
- Breakpoints.
- Tinggi input dan button.

Identitas **Salero Basamo Jati** memakai arah *Modern Padang Heritage*: profesional, bersih, hangat, dan fungsional. Theme terang adalah default. Token didefinisikan sekali di `resources/css/app.css` dan dipetakan menjadi utility Tailwind v4; jangan mengulang nilai hex di komponen.

| Token | Nilai | Peran |
| --- | --- | --- |
| `brand` | `#D50112` | Identitas dan aksi utama |
| `brand-hover` | `#B9000F` | Hover aksi utama / destruktif |
| `gold` | `#FDCE02` | Aksen kecil dan highlight |
| `background` | `#FAF9F6` | Permukaan aplikasi |
| `surface` | `#FFFFFF` | Card, input, dan sidebar |
| `foreground` | `#211D1B` | Teks utama |
| `muted-foreground` | `#746E68` | Teks sekunder |
| `border` | `#E9E4DD` | Border dan input |
| `soft-red` | `#FFF0F1` | Permukaan sekunder bernuansa brand |
| `soft-yellow` | `#FFF8D9` | Permukaan aksen kuning |

Merah dipakai untuk identitas dan aksi utama, bukan untuk seluruh permukaan. Kuning hanya untuk aksen kecil, informasi penting, dan highlight; bukan background utama. Status minimal success, warning, error, dan info tetap memakai label teks, bukan warna saja.

Logo resmi adalah `public/images/brand/logo-salero-basamo-jati.png`. Jangan menggambar ulang, mengubah proporsi, atau mengubah warnanya. Gunakan `object-fit: contain`, jangan letakkan di atas background gelap pada logo utama, dan gunakan berkas PNG yang sama sebagai favicon karena versi simbol terpisah tidak tersedia. Nama brand tidak boleh diganti.

## 6. Responsive rules

Breakpoints adalah titik awal dan dapat disesuaikan hasil pengujian:
- Mobile: di bawah 768 CSS px.
- Tablet: 768–1023 CSS px.
- Desktop: 1024 CSS px ke atas.

Aturan:
- Tidak ada horizontal overflow yang tidak disengaja pada halaman operasional.
- Form satu kolom pada layar sempit.
- Target sentuh sekitar 44–48 CSS px atau lebih.
- Berikan jarak yang cukup antar kontrol.
- Jangan mengandalkan hover.
- Gunakan safe-area inset untuk perangkat dengan notch bila fixed navigation digunakan.
- Pastikan modal/bottom sheet dapat discroll dan tidak tertutup keyboard.
- Uji portrait dan landscape pada halaman POS.
- Tabel boleh memiliki horizontal scroll bila kolom memang tidak bisa diringkas, tetapi jangan jadikan itu solusi default untuk seluruh mobile UI.

## 7. State dan feedback

Setiap halaman/fitur harus memiliki:
- Loading state.
- Empty state.
- Error state.
- Success feedback.
- Disabled state.
- Confirmation untuk tindakan destruktif/sensitif.

Gunakan toast untuk feedback singkat. Gunakan inline validation untuk kesalahan input. Error harus menjelaskan cara memperbaikinya dan tidak menampilkan stack trace atau detail internal.

## 8. Accessibility dan interaction

- Input memiliki label yang terlihat.
- Semua fungsi penting dapat dioperasikan dengan keyboard.
- Fokus keyboard terlihat.
- Tombol memiliki nama yang bermakna untuk screen reader.
- Kontras teks memadai.
- Jangan menggunakan warna sebagai satu-satunya informasi status.
- Animasi harus ringan dan tidak mengganggu.
- Hormati preferensi reduced motion.

## 9. Print layout

Struk mempunyai stylesheet khusus cetak; jangan mencetak layout aplikasi biasa.

- Mendukung konfigurasi lebar 58 mm dan 80 mm.
- Font dan jarak dapat dibaca pada thermal printer.
- Hindari elemen navigasi, tombol, dan dekorasi aplikasi dalam hasil cetak.
- Cetak ulang menggunakan data receipt tersimpan.
- Uji hasil dengan printer nyata sebelum menyatakan dukungan final.

Catatan implementasi Phase 4: struk disajikan sebagai halaman server-render terpisah dengan stylesheet cetak sendiri, bukan layout aplikasi. Pilihan lebar 58 mm dan 80 mm tersedia; validasi akhir dengan printer thermal nyata masih menjadi bagian dari QA deployment.

## 10. Kriteria review desain

Sebelum menyelesaikan halaman, periksa:
1. Apa tugas utama pengguna di halaman ini?
2. Apakah tindakan utama terlihat tanpa mencari-cari?
3. Apakah informasi paling penting mendapat prioritas visual?
4. Apakah halaman tetap nyaman di HP?
5. Apakah semua tombol punya fungsi nyata?
6. Apakah loading, empty, dan error state tersedia?
7. Apakah tampilan terlalu dekoratif atau berulang?
8. Apakah kasir dapat menyelesaikan tugas dengan langkah sesedikit mungkin tanpa mengorbankan validasi?

Catatan Phase 6: review layout responsif dan stylesheet struk sudah dilakukan kembali terhadap halaman POS, takeaway, pesanan, shift, dan laporan. Tidak ada perubahan breakpoint. Penyempurnaan identitas visual memakai token warna terpusat dan logo PNG resmi di sidebar, header mobile, login, favicon, dan struk tanpa mengubah alur kerja. Pengujian pada smartphone/tablet fisik dan printer thermal 58/80 mm nyata belum dapat dilakukan di environment pengembangan; hasil review kode tidak boleh diklaim sebagai uji perangkat nyata.
