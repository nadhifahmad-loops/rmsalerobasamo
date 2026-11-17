# AGENTS.md — Instruksi untuk AI Coding Agent

Dokumen ini adalah instruksi proyek yang harus dibaca sebelum melakukan perubahan kode. Dokumen produk yang menjadi sumber kebutuhan berada di folder `docs/`.

## 1. Sumber kebenaran dan prioritas

Baca dokumen berikut sebelum coding:
1. `docs/PRD.md`
2. `docs/BUSINESS-RULES.md`
3. `docs/USER-FLOWS.md`
4. `docs/DATABASE-DESIGN.md`
5. `docs/TECHNICAL-SPEC.md`
6. `docs/UI-UX-GUIDELINES.md`
7. `docs/TESTING-CHECKLIST.md`

Jika ada konflik:
- Aturan integritas transaksi dan keamanan tidak boleh dilemahkan demi kemudahan implementasi.
- `BUSINESS-RULES.md` mengatur perilaku operasional.
- `PRD.md` mengatur scope dan acceptance criteria.
- `TECHNICAL-SPEC.md` mengatur pola implementasi.
- Jika konflik tidak dapat diselesaikan dari dokumen, jelaskan konflik dan ajukan keputusan; jangan menebak diam-diam.

Jangan memperluas scope MVP tanpa alasan dan persetujuan.

## 2. Perilaku sebelum mengubah kode

Sebelum coding:
1. Periksa repository, framework, dependency, dan pola kode yang sudah ada.
2. Jangan mengasumsikan repository kosong.
3. Jangan mengganti stack atau melakukan rewrite besar tanpa alasan teknis yang kuat.
4. Identifikasi file yang akan dibuat/diubah.
5. Jelaskan rencana singkat, dampak database, risiko, dan test yang akan dijalankan.
6. Untuk pekerjaan yang menyentuh aturan bisnis, tunjukkan aturan terkait yang sedang diimplementasikan.

Jika informasi operasional belum dipastikan, gunakan asumsi yang secara eksplisit dicatat di dokumen. Jangan mengarang kebijakan rumah makan.

## 3. Prinsip arsitektur dan kualitas kode

- Gunakan modular monolith untuk MVP.
- Ikuti konvensi Laravel dan pola yang sudah ada di repository.
- Gunakan PHP dengan type declarations dan return types bila sesuai.
- Gunakan TypeScript; hindari `any` kecuali ada alasan yang dijelaskan.
- Pisahkan business logic dari komponen presentasi.
- Hindari controller besar, komponen raksasa, duplikasi logic, dan abstraksi berlebihan.
- Gunakan Form Request atau mekanisme validasi backend yang setara.
- Gunakan service/action class hanya ketika membantu memusatkan proses bisnis yang nyata; jangan membuat lapisan abstraksi tanpa manfaat.
- Gunakan database migrations untuk perubahan schema.
- Gunakan nama variabel, fungsi, tabel, dan komponen yang jelas.
- Tangani error secara eksplisit. Jangan menelan exception atau membuat fallback yang menyembunyikan kerusakan data.
- Jangan menambahkan dependency tanpa kebutuhan yang jelas.
- Jangan meninggalkan TODO atau placeholder pada alur utama tanpa melaporkannya.

## 4. Integritas transaksi — aturan kritis

- Backend adalah sumber kebenaran untuk harga, subtotal, diskon, pajak, biaya layanan, dan total.
- Jangan mempercayai nilai harga/total yang dikirim browser.
- Ambil harga aktif dari database ketika item ditambahkan atau pesanan diperbarui.
- Simpan snapshot nama produk, variasi, satuan, dan harga pada `order_items`.
- Gunakan database transaction untuk perubahan yang harus berhasil/gagal sebagai satu kesatuan.
- Cegah pembayaran ganda menggunakan idempotency key atau mekanisme setara dengan unique constraint.
- Pastikan order hanya dapat dibayar satu kali pada alur MVP.
- Pesanan Open/Awaiting Payment bukan omzet.
- Pesanan Paid tidak boleh dihapus permanen.
- Pembatalan harus menyimpan alasan, pengguna, waktu, dan jejak audit.
- Mutasi item pada pesanan hanya diperbolehkan sebelum pembayaran berhasil.
- Jangan mengubah histori harga ketika master product/variant diperbarui.
- Gunakan lock/constraint untuk mencegah dua kasir membuat Open Order aktif pada meja yang sama.
- Jangan menganggap respons frontend sebagai bukti transaksi berhasil; tunggu konfirmasi backend.

## 5. Keamanan

- Jangan menyimpan password dalam bentuk plain text.
- Gunakan autentikasi/session framework yang terawat.
- Terapkan authorization di server untuk setiap route/action yang dilindungi.
- Jangan hanya menyembunyikan tombol atau menu di frontend.
- Terapkan validasi server-side untuk seluruh input.
- Gunakan ORM/query parameterization; hindari query SQL dengan interpolasi input pengguna.
- Lindungi form/session sesuai mekanisme CSRF framework.
- Terapkan rate limiting pada login dan endpoint sensitif.
- Jangan memasukkan secret, credential, token, atau data pelanggan ke repository/log.
- Gunakan `.env.example` hanya untuk nama dan contoh nilai non-rahasia.
- Batasi upload berdasarkan MIME type, ukuran, dan ekstensi; validasi isi file.
- Gunakan HTTPS di production.
- Terapkan least privilege pada akun database dan pengguna aplikasi.
- Jangan mengklaim aplikasi aman sebelum pemeriksaan dilakukan.

## 6. Aturan UI/UX dan anti-AI slop

- Ikuti `docs/UI-UX-GUIDELINES.md`.
- Desain aplikasi sebagai alat kerja POS, bukan landing page atau template dashboard generik.
- Hindari gradient, glassmorphism, shadow, badge, dan animasi dekoratif yang berlebihan.
- Jangan memenuhi dashboard dengan KPI yang tidak membantu keputusan.
- Jangan memakai emoji sebagai pengganti icon UI.
- Gunakan typography, spacing, warna, dan komponen yang konsisten.
- Setiap tombol harus mempunyai aksi nyata.
- Semua form harus memiliki label, validasi, dan pesan error yang berguna.
- Sediakan loading, empty, success, dan error state.
- Jangan gunakan data dummy dalam lingkungan production.
- Jangan membuat chart kecuali data dan tujuan pembacaannya jelas.
- Mobile bukan desktop yang diperkecil: optimalkan navigasi, POS, keranjang, checkout, meja, dan laporan untuk layar sentuh.
- Jangan mengandalkan hover sebagai satu-satunya interaksi.

## 7. Testing wajib

- Tambahkan atau perbarui automated tests untuk perubahan business logic.
- Uji authorization negatif, bukan hanya akses yang diizinkan.
- Uji nominal pembayaran dan batas nilai.
- Uji request berulang, race condition relevan, dan rollback.
- Uji perubahan harga produk setelah transaksi dibuat.
- Uji Open Order setelah refresh/re-login sesuai session policy.
- Uji laporan agar hanya menghitung transaksi yang memenuhi aturan.
- Jalankan formatter, lint, static analysis, unit/integration tests, dan build yang tersedia.
- Laporkan perintah yang dijalankan dan hasilnya. Jangan mengklaim test lulus jika belum dijalankan.

## 8. Cara bekerja per fase

Kerjakan satu fase atau vertical slice pada satu waktu:
1. Baca requirement terkait.
2. Buat rencana file dan perubahan schema.
3. Implementasikan backend dan frontend yang dibutuhkan.
4. Tambahkan tests.
5. Jalankan tests dan pemeriksaan kualitas.
6. Periksa ulang akses, validasi, dan responsive layout.
7. Ringkas hasil, file yang berubah, hasil test, risiko, dan pekerjaan tersisa.

Jangan membuat semua halaman sekaligus menggunakan data statis. Bangun alur end-to-end yang benar-benar tersambung ke backend dan database.

## 9. Definisi selesai

Pekerjaan hanya boleh dinyatakan selesai jika:
- Acceptance criteria untuk scope tersebut terpenuhi.
- Tidak ada tombol utama yang hanya placeholder.
- Validasi dan authorization server-side tersedia.
- Test relevan dijalankan dan hasilnya dilaporkan.
- Tidak ada perubahan histori transaksi yang tidak disengaja.
- Error state ditangani.
- Desktop dan mobile diperiksa untuk halaman terkait.
- Dokumentasi diperbarui jika kontrak, schema, atau perilaku berubah.
