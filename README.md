# 🛒 Toko Kasir - POS & FIFO Inventory System

Aplikasi **Point of Sale (POS) dan Manajemen Inventaris berbasis Web** yang dirancang untuk membantu toko dalam mengelola transaksi penjualan, stok barang, laporan keuangan, serta absensi pegawai.

Sistem menggunakan metode **FIFO (First-In, First-Out)** untuk membantu pengelolaan stok berdasarkan urutan barang masuk. Aplikasi juga dilengkapi dengan pencetakan struk thermal 58mm, dashboard interaktif, pencarian barcode, dan berbagai metode pembayaran.

---

## 📸 Preview

> Tambahkan screenshot aplikasi di folder `screenshots/`, kemudian tampilkan di sini.

```text
screenshots/
├── dashboard.png
├── kasir.png
├── inventory.png
├── laporan.png
└── absensi.png
```

Contoh:

```markdown
![Dashboard](screenshots/dashboard.png)
```

---

## ✨ Fitur Utama

### 📊 Dashboard

* Statistik transaksi dan penjualan.
* Grafik penjualan harian.
* Informasi laba kotor dan laba bersih.
* Informasi total pengeluaran.
* Monitoring stok barang.
* Peringatan stok yang hampir habis.
* Visualisasi data secara dinamis.

### 🏪 Point of Sale (Kasir)

* Pencarian produk dengan cepat.
* Pencarian produk menggunakan barcode.
* Keranjang transaksi.
* Perhitungan subtotal otomatis.
* Perhitungan diskon secara real-time.
* Perhitungan total pembayaran otomatis.
* Perhitungan uang kembalian.
* Mendukung beberapa metode pembayaran:

  * 💵 Cash
  * 📱 QRIS
  * 🏦 Bank Transfer

### 🧾 Cetak Struk

* Struk dioptimalkan untuk printer thermal **58mm**.
* Informasi transaksi ditampilkan secara ringkas.
* Mendukung pencetakan langsung menggunakan `window.print()`.
* Nomor transaksi dan detail pembelian ditampilkan pada struk.

### 📦 Manajemen Inventaris

* CRUD data barang.
* Pengelolaan stok barang.
* Pengelolaan harga beli dan harga jual.
* Pencatatan stok berdasarkan **lot**.
* Implementasi metode **FIFO (First-In, First-Out)**.
* Monitoring stok yang tersedia.
* Upload gambar produk.
* Penghapusan file gambar otomatis ketika produk dihapus.

### 🔄 Sistem FIFO

Sistem FIFO digunakan untuk menentukan stok barang yang harus digunakan terlebih dahulu berdasarkan urutan barang masuk.

Contoh:

```text
Lot A → 10 barang → Masuk 1 Januari
Lot B → 15 barang → Masuk 5 Januari
Lot C → 20 barang → Masuk 10 Januari
```

Jika terjadi penjualan sebanyak 12 barang:

```text
Lot A → 10 barang → Habis
Lot B → 2 barang → Terpakai
Lot B → 13 barang → Tersisa
```

Dengan demikian, stok yang lebih lama akan digunakan terlebih dahulu.

### 👥 Absensi Pegawai

* Pencatatan absensi harian.
* Status kehadiran pegawai.
* Penguncian status absensi harian.
* Modal konfirmasi sebelum melakukan tindakan tertentu.
* Mencegah perubahan data secara tidak sengaja.

### 📈 Laporan

* Laporan transaksi.
* Laporan penjualan.
* Laporan stok.
* Informasi pemasukan dan pengeluaran.
* Visualisasi data transaksi.
* Export data untuk kebutuhan laporan.

---

## 🛠️ Tech Stack

### Backend

* PHP Native
* PDO / MySQLi

### Database

* MySQL
* MariaDB

### Frontend

* HTML5
* Tailwind CSS 3.x
* JavaScript ES6+
* Fetch API / AJAX

### Libraries

* [Chart.js](https://www.chartjs.org/) — Visualisasi grafik
* [SweetAlert2](https://sweetalert2.github.io/) — Notifikasi dan modal
* [jsPDF](https://github.com/parallax/jsPDF) — Export PDF
* [SheetJS](https://sheetjs.com/) — Export Excel

---

## 📋 Requirements

Sebelum menjalankan aplikasi, pastikan perangkat sudah memiliki:

* PHP **7.4 atau lebih baru**
* MySQL / MariaDB
* Apache Web Server
* XAMPP / Laragon / WampServer
* Web Browser modern:

  * Google Chrome
  * Microsoft Edge
  * Mozilla Firefox

---

# 🚀 Instalasi

## 1. Clone Repository

```bash
git clone https://github.com/muhammadmuharrikimadudin-cell/toko_kasir.git
```

Masuk ke folder project:

```bash
cd toko_kasir
```

---

## 2. Jalankan XAMPP / Laragon

Jika menggunakan **XAMPP**, aktifkan:

```text
Apache
MySQL
```

Jika menggunakan **Laragon**, jalankan Laragon dan pastikan Apache serta MySQL aktif.

---

## 3. Pindahkan Project

### XAMPP

Letakkan project di:

```text
C:\xampp\htdocs\toko_kasir
```

### Laragon

Letakkan project di:

```text
C:\laragon\www\toko_kasir
```

---

## 4. Buat Database

Buka:

```text
http://localhost/phpmyadmin
```

Buat database baru, misalnya:

```text
toko_kasir
```

Kemudian import file database `.sql` yang tersedia di repository.

Contoh:

```text
database/
└── toko_kasir.sql
```

---

## 5. Konfigurasi Database

Sesuaikan konfigurasi database pada file koneksi project.

Contoh:

```php
$host = "localhost";
$dbname = "toko_kasir";
$username = "root";
$password = "";
```

Jika menggunakan konfigurasi berbeda, sesuaikan dengan database lokal masing-masing.

---

## 6. Jalankan Aplikasi

Jika menggunakan XAMPP:

```text
http://localhost/toko_kasir
```

Jika menggunakan virtual host Laragon:

```text
http://toko_kasir.test
```

---

# 📁 Struktur Project

Struktur project secara umum:

```text
toko_kasir/
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── config/
│   └── database.php
│
├── dashboard/
│
├── kasir/
│
├── barang/
│
├── supplier/
│
├── transaksi/
│
├── laporan/
│
├── absensi/
│
├── uploads/
│   └── produk/
│
├── database/
│   └── toko_kasir.sql
│
├── index.php
├── login.php
└── README.md
```

> Struktur folder dapat berbeda tergantung versi project yang digunakan.

---

# 💾 Database

Sistem menggunakan database relasional berbasis MySQL/MariaDB.

Beberapa data utama yang dikelola:

```text
User
│
├── Pegawai
├── Barang
├── Supplier
├── Stok / Lot
├── Transaksi
├── Detail Transaksi
├── Pengeluaran
└── Absensi
```

Relasi tersebut digunakan untuk menghubungkan data barang, stok, transaksi, dan laporan.

---

# 🔄 Alur Transaksi

Secara umum proses transaksi berjalan seperti berikut:

```text
Pilih Produk
     ↓
Masukkan ke Keranjang
     ↓
Hitung Subtotal
     ↓
Masukkan Diskon
     ↓
Pilih Metode Pembayaran
     ↓
Hitung Total & Kembalian
     ↓
Simpan Transaksi
     ↓
Kurangi Stok FIFO
     ↓
Cetak Struk
```

---

# 🧮 Implementasi FIFO

Ketika transaksi dilakukan, sistem akan mengambil stok berdasarkan lot yang memiliki tanggal masuk paling lama.

Contoh:

| Lot     | Tanggal Masuk | Stok |
| ------- | ------------- | ---: |
| LOT-001 | 01-01-2026    |   10 |
| LOT-002 | 05-01-2026    |   15 |
| LOT-003 | 10-01-2026    |   20 |

Jika pelanggan membeli **12 barang**, sistem akan mengambil:

```text
LOT-001 → 10 barang
LOT-002 → 2 barang
```

Sisa:

```text
LOT-001 → 0
LOT-002 → 13
LOT-003 → 20
```

---

# 🧾 Format Struk

Struk dirancang untuk printer thermal **58mm**.

Informasi yang ditampilkan antara lain:

```text
================================
          TOKO KASIR
================================
No. Transaksi : TRX-00001
Tanggal       : 08-10-2026
Kasir         : Admin
--------------------------------
Produk        Qty       Harga
--------------------------------
Produk A       2       20.000
Produk B       1       15.000
--------------------------------
Subtotal              55.000
Diskon                 5.000
Total                 50.000
Bayar                 60.000
Kembalian             10.000
--------------------------------
       TERIMA KASIH
================================
```

---

# 🌐 Deployment

Project dirancang menggunakan **relative path** sehingga lebih mudah dipindahkan ke server.

Project dapat dikembangkan untuk deployment menggunakan:

* Shared Hosting
* cPanel
* VPS
* Hosting PHP/MySQL lainnya

> Untuk deployment production, pastikan konfigurasi database, permission folder, dan environment server sudah disesuaikan.

---

# 🔐 Keamanan

Beberapa praktik keamanan yang digunakan dalam pengembangan:

* Prepared Statement untuk query database.
* Validasi input pengguna.
* Session untuk autentikasi.
* Konfirmasi sebelum operasi penting.
* Validasi upload file.
* Penghapusan file menggunakan `unlink()` ketika data gambar dihapus.

Untuk production, disarankan menambahkan:

* Password hashing menggunakan `password_hash()`.
* CSRF protection.
* Validasi MIME type upload.
* Role & permission yang lebih ketat.
* HTTPS.
* Backup database secara berkala.

---

# 🧪 Testing

Pengujian dapat dilakukan pada beberapa modul utama:

| Modul      | Pengujian                        |
| ---------- | -------------------------------- |
| Login      | Validasi username & password     |
| Barang     | Tambah, edit, hapus barang       |
| Stok       | Penambahan dan pengurangan stok  |
| FIFO       | Pengambilan stok berdasarkan lot |
| Kasir      | Proses transaksi                 |
| Pembayaran | Cash, QRIS, Transfer             |
| Diskon     | Perhitungan diskon               |
| Struk      | Cetak thermal 58mm               |
| Absensi    | Pencatatan kehadiran             |
| Laporan    | Tampilan dan export laporan      |

---

# 📊 Contoh Skenario Testing FIFO

**Kondisi awal:**

```text
Lot 1 = 10
Lot 2 = 20
```

**Transaksi:**

```text
Pembelian = 15 barang
```

**Expected Result:**

```text
Lot 1 = 0
Lot 2 = 15
```

Sistem tidak boleh mengambil 15 barang langsung dari Lot 2 karena Lot 1 harus digunakan terlebih dahulu.

---

# 🔮 Pengembangan Selanjutnya

Beberapa fitur yang dapat dikembangkan:

* 📱 Responsive mobile dashboard.
* 📷 Scan barcode menggunakan kamera.
* 🖨️ Integrasi printer thermal secara langsung.
* 👤 Role-based access control.
* ☁️ Cloud backup database.
* 📊 Laporan keuangan lebih lengkap.
* 📦 Notifikasi stok minimum.
* 📱 Progressive Web App (PWA).
* 🔔 Notifikasi transaksi secara real-time.

---

# 👨‍💻 Developer

**Muhammad Muharrik Imaduddin**

Mahasiswa **D4 Teknologi Rekayasa Perangkat Lunak**

Project ini dikembangkan sebagai aplikasi **Point of Sale (POS) dan Inventory Management** dengan fokus pada pengelolaan transaksi, stok berbasis FIFO, dan digitalisasi operasional toko.

---

## 📄 License

Project ini dibuat untuk keperluan pembelajaran, pengembangan portofolio, dan pengembangan sistem.

---

## ⭐ Support

Jika project ini bermanfaat, jangan lupa memberikan ⭐ pada repository GitHub.

```text
Made with ❤️ using PHP, MySQL, Tailwind CSS & JavaScript
```
