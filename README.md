# Kantin Kampus - Online Campus Food Delivery

Aplikasi web pemesanan makanan kantin kampus modern, mobile-first, dan terintegrasi dengan pembayaran **Midtrans QRIS Sandbox**. Dirancang untuk mahasiswa dan penjual kantin kampus dengan alur pemesanan yang cepat, praktis, dan transparan.

---

## Fitur Utama

- **Pembeli (Buyer Experience)**:
  - Beranda interaktif dengan pencarian cepat nama kantin/menu dan chip filter kategori (*Makanan berat, Minuman, Camilan, Di bawah Rp10.000*).
  - Tampilan menu per kantin dengan label ketersediaan *Tersedia / Habis* dan status buka/tutup kantin.
  - Keranjang belanja berbasis sesi (tanpa login) dengan aturan ketat **satu kantin per pesanan** dan dialog konfirmasi otomatis.
  - Pemilihan lokasi duduk 8 zona kampus (dilengkapi foto thumbnail dan penanda visual border sage).
  - Integrasi pembayaran **Midtrans QRIS** dengan countdown timer, download QRIS, auto-redirect setelah bayar, serta fallback mode simulasi demo.
  - Pelacakan pesanan real-time (*Menunggu pembayaran &rarr; Diproses &rarr; Diantar &rarr; Selesai*) dengan polling otomatis setiap 5 detik.
  - Riwayat pesanan berbasis sesi dan shortcut WhatsApp langsung ke pengelola kantin.

- **Penjual / Pengelola Kantin (Kitchen Admin Experience)**:
  - Autentikasi sesi terpisah (`guard: admin`) khusus pengelola dapur.
  - **Isolasi multi-tenant penuh**: setiap admin hanya dapat mengakses menu dan pesanan kantinnya sendiri.
  - Dashboard pesanan mobile-friendly dengan toggle cepat status Buka/Tutup kantin.
  - Tab status pemesanan (*Baru, Diproses, Diantar, Selesai*) yang hanya menampilkan pesanan terbayar (*dibayar*).
  - Notifikasi suara sintetis Web Audio saat ada pesanan baru masuk (*Aktifkan Suara*).
  - Satu tombol aksi cepat per kartu pesanan untuk memajukan alur (*Proses &rarr; Antar &rarr; Selesai*) yang otomatis mengisi timestamp (`processed_at`, `delivered_at`, `completed_at`).
  - Manajemen katalog menu: toggle ketersediaan instan (*Tersedia / Habis*), form tambah menu dengan upload foto (maks. 2 MB), edit, dan hapus menu dengan konfirmasi.

---

## Persyaratan Sistem

- **PHP**: ^8.2 atau ^8.5
- **Composer**: ^2.x
- **SQLite** atau **MySQL**
- **Ekstensi PHP**: `curl`, `json`, `openssl`, `pdo`, `mbstring`

---

## Langkah Instalasi & Setup

1. **Clone atau buka folder proyek**:
   ```bash
   cd kantin
   ```

2. **Pasang dependensi Composer**:
   ```bash
   composer install
   ```

3. **Konfigurasi Environment (`.env`)**:
   Salin file konfigurasi jika belum ada:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Pastikan konfigurasi Midtrans di dalam `.env` sudah terisi:
   ```env
   SERVER_KEY=SB-Mid-server-demo-key
   CLIENT_KEY=SB-Mid-client-demo-key
   IS_PRODUCTION=false
   MIDTRANS_SERVER_KEY=SB-Mid-server-demo-key
   MIDTRANS_CLIENT_KEY=SB-Mid-client-demo-key
   MIDTRANS_IS_PRODUCTION=false
   PAYMENT_SIMULATION=true
   ```

4. **Migrasi Database & Seeder**:
   Jalankan migrasi fresh beserta seed data kantin, admin, zona, dan menu:
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Symlink Storage Publik (untuk upload gambar menu)**:
   ```bash
   php artisan storage:link
   ```

6. **Jalankan Development Server**:
   ```bash
   php artisan serve
   ```
   Akses aplikasi di browser pada: `http://127.0.0.1:8000`

---

## Akun Admin Bawaan (Seeded Admin Accounts)

Setelah menjalankan `php artisan db:seed` atau `migrate:fresh --seed`, tersedia 4 akun admin dapur untuk masing-masing kantin:

| Kantin | Username | Password | URL Login | Spesialisasi |
|---|---|---|---|---|
| **Kantin 1** | `kantin1` | `password` | `/admin/login` | Aneka Nasi Goreng & Masakan Tradisional |
| **Kantin 2** | `kantin2` | `password` | `/admin/login` | Pojok Mie Ayam & Bakso Sapi |
| **Kantin 3** | `kantin3` | `password` | `/admin/login` | Ayam Geprek Sambal Mantap |
| **Kantin 4** | `kantin4` | `password` | `/admin/login` | Camilan, Gorengan & Minuman Segar *(Status Awal: Tutup)* |

### Mengganti Password Admin Kantin
Gunakan Artisan command berikut untuk mengganti password bawaan sebelum menyerahkan akun ke penjual kantin:
```bash
php artisan admin:set-password kantin1
```
Prompt akan meminta input password baru secara tersembunyi (*hidden input*).

### Pembersihan Otomatis Foto Privasi & Reset Porsi Harian (Scheduler)
Untuk menjalankan jadwal otomatis (membersihkan foto tempat duduk >24 jam dan mereset sisa porsi harian menu setiap jam 00:05), jalankan Laravel scheduler:
```bash
php artisan schedule:work
```
Perintah terjadwal yang aktif:
- `orders:purge-photos`: membersihkan foto lokasi pesanan lama setiap jam.
- `menus:reset-stock`: mereset sisa porsi harian (`daily_stock`) menjadi `null` setiap hari pukul 00:05 WIB.

Untuk menjalankan reset stok secara manual:
```bash
php artisan menus:reset-stock
```

---

## Checklist Pengujian Manual (Manual Test Checklist)

### 1. Beranda Pembeli (`/`)
- [ ] Buka halaman utama `/`. Pastikan salam `"Mau makan apa hari ini?"` tampil.
- [ ] Ketik kata kunci pada kotak pencarian (misal `"Ayam"` atau `"Nasi"`). Daftar kantin tersaring otomatis.
- [ ] Klik filter chip (*Makanan berat*, *Minuman*, *Camilan*, *Di bawah Rp10.000*). Pastikan filter aktif dan tombol *Reset* berfungsi.
- [ ] Periksa kartu kantin: menampilkan foto, nama, badge Buka/Tutup, dan cuplikan dua menu populer.

### 2. Halaman Menu Kantin (`/kantin/{id}`)
- [ ] Buka salah satu kantin yang buka (misal Kantin 1: `/kantin/1`).
- [ ] Pastikan terdapat tombol panah kembali ke `/`, nama kantin, jam operasional, dan badge *Buka*.
- [ ] Pastikan menu memiliki foto, nama, deskripsi singkat, harga Rupiah, dan tombol bulat `+`.
- [ ] Menu yang habis menampilkan label merah *"Habis"*, foto redup/grayscale, dan tombol `+` dinonaktifkan.
- [ ] Buka Kantin 4 (`/kantin/4`) yang sedang tutup: pastikan banner peringatan kuning *"Kantin Sedang Tutup"* muncul dan tombol pemesanan tidak aktif.

### 3. Keranjang Belanja (`/keranjang`)
- [ ] Tekan tombol `+` pada menu di Kantin 1. Sticky bottom bar muncul di bawah (`X item • Rp...` + tombol *"Lihat keranjang"*).
- [ ] Buka Kantin 2 dan coba tambahkan menu: Modal konfirmasi *"Ganti Kantin?"* muncul memperingatkan bahwa keranjang hanya boleh berisi menu dari satu kantin.
- [ ] Klik *"Ya, Ganti Kantin"*: keranjang lama terhapus dan digantikan menu baru.
- [ ] Buka `/keranjang`: Stepper kuantitas (`- 1 +`) berfungsi menaikkan/menurunkan jumlah porsi. Kuantitas 0 menghapus item.
- [ ] Input catatan per item (mis. `"tanpa es"`, `"pedas sedang"`).
- [ ] Saat keranjang kosong: Tampil empty state *"Keranjangmu masih kosong"* dan tombol kembali ke beranda.
- [ ] Klik *"Lanjut"* untuk menuju ke halaman pengantaran.

### 4. Detail Pengantaran & Buat Pesanan (`/pengantaran`)
- [ ] Halaman bertajuk `"Kamu duduk di mana?"`.
- [ ] Isi field *"Nama pemesan"* (wajib).
- [ ] Field *"Nomor WhatsApp"* (opsional, divalidasi format Indonesia `08...` / `+628...`).
- [ ] Pilih lokasi duduk pada grid 2-kolom zona kampus. Kartu yang dipilih memiliki **border sage** dan ikon centang.
- [ ] Pilih zona *"Tidak yakin / lainnya"*: Field keterangan lokasi otomatis berstatus **wajib diisi**.
- [ ] Tekan *"Lanjut ke pembayaran"*: Pesanan dibuat via database transaction, harga unit diambil langsung dari tabel `menus` di database (bukan dari client/sesi), dan keranjang belanja dikosongkan.

### 5. Pembayaran QRIS Midtrans (`/pembayaran/{order_code}`)
- [ ] Halaman menampilkan total tagihan tebal (mis. `Rp25.000`).
- [ ] Kode QRIS terpusat di dalam kartu putih berbingkai.
- [ ] Hitung mundur waktu pembayaran *"Bayar dalam MM:SS"* aktif.
- [ ] Tersedia instruksi scan dan tombol sekunder *"Simpan QR"* untuk mendownload gambar QRIS.
- [ ] Rincian pesanan dapat dibuka-tutup (collapsible *"Ringkasan pesanan"*).
- [ ] **Mode Pengujian**: Jika `PAYMENT_SIMULATION=true`, klik *"Simulasi Bayar Berhasil"*. Halaman otomatis redirect ke status pesanan (`/pesanan/{order_code}`).
- [ ] Jika pembayaran kedaluwarsa: Muncul peringatan merah *"Pembayaran kedaluwarsa"* beserta tombol *"Buat QRIS Baru dan Coba Lagi"*.

### 6. Pelacakan Status Pesanan (`/pesanan/{order_code}`)
- [ ] Judul halaman bertuliskan `"Pesananmu"`.
- [ ] Progress tracker vertikal 4 tahap: *Menunggu pembayaran*, *Diproses*, *Diantar*, *Selesai*.
- [ ] Tahap yang sudah selesai diberi centang hijau, tahap yang sedang aktif disorot dengan warna **sage green**.
- [ ] Kalimat status ramah kontekstual (mis. *"Pesananmu sedang disiapkan di dapur"*).
- [ ] Halaman melakukan auto-refresh setiap 5 detik tanpa reload layar penuh.
- [ ] Tombol sekunder *"Hubungi Kantin via WhatsApp"* membuka tautan chat WA ke nomor telepon kantin.
- [ ] Navigasi bawah:
  - Tab *"Pesanan"* membuka pesanan aktif terakhir dalam sesi.
  - Tab *"Riwayat"* (`/riwayat`) menampilkan daftar pesanan sebelumnya terurut dari yang terbaru.

### 7. Panel Dapur / Admin (`/admin/pesanan`)
- [ ] Buka `/admin/login`, masukkan username `kantin1` dan password `password`.
- [ ] Header menampilkan nama kantin dan switch toggle Buka/Tutup kantin secara langsung.
- [ ] Tombol *"Aktifkan Suara"* untuk mengaktifkan notifikasi audio Web Audio saat ada pesanan baru masuk.
- [ ] Tab status: *Baru*, *Diproses*, *Diantar*, *Selesai* dengan badge jumlah pesanan.
  - **Hanya pesanan yang sudah dibayar (`payment_status = 'dibayar'`) yang muncul di tab dapur.**
  - Tab *Baru* menampilkan pesanan baru dengan **border kiri sage**.
- [ ] Kartu pesanan besar menampilkan: kode pesanan, nama pemesan, nomor WA, **ZONA PENGANTARAN DALAM TEKS BESAR TEBAL**, ciri lokasi, daftar menu dengan catatan khusus berlabel kuning, dan waktu pemesanan (*"3 menit lalu"*).
- [ ] Tombol satu-satunya di bawah kartu memajukan status secara berurutan:
  - Tab Baru: *"Proses Pesanan"* &rarr; mengisi `processed_at` otomatis.
  - Tab Diproses: *"Antar Pesanan"* &rarr; mengisi `delivered_at` otomatis.
  - Tab Diantar: *"Selesaikan Pesanan"* &rarr; mengisi `completed_at` otomatis.
- [ ] **Isolasi Akses**: Admin Kantin 1 tidak dapat melihat maupun memproses pesanan milik Kantin 2, 3, atau 4.

### 8. Manajemen Menu Admin (`/admin/menu`)
- [ ] Klik link *"Menu"* di header admin: halaman bertajuk *"Menu Kantin X"*.
- [ ] Daftar menu kantin menampilkan foto, nama, kategori, dan harga.
- [ ] Toggle switch *"Tersedia / Habis"* memperbarui status ketersediaan menu secara instan via Alpine.js + fetch.
- [ ] Tombol floating `+ Tambah Menu`: Form tambah menu dengan upload foto (maksimal 2 MB), nama, harga bulat, kategori, dan deskripsi.
- [ ] Tombol *Edit* dan *Hapus* (dengan konfirmasi dialog JavaScript).
- [ ] Admin hanya dapat mengelola menu milik kantinnya sendiri.

---

## Menjalankan Pengujian Otomatis (Automated Tests)

Seluruh fitur dan skenario edge case telah diuji dengan PHPUnit / Pest Feature Tests:

```bash
# Menjalankan seluruh test suite
php artisan test

# Menjalankan tes khusus verifikasi (Fase 9)
php artisan test --filter=PolishAndTestingTest

# Menjalankan tes integrasi pembayaran QRIS Midtrans (Fase 8)
php artisan test --filter=MidtransPaymentTest

# Menjalankan tes alur pesanan pembeli (Fase 4 & 5)
php artisan test --filter=OrderCreationTest
```

**Hasil Pengujian:**
- 49 test cases lolos (240 assertions), mencakup perhitungan total keranjang, validasi harga sisi server, timestamp otomatis alur pesanan, webhook Midtrans, dan isolasi akses antar admin kantin.
