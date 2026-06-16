# Sistem Layanan Publik Terdistribusi Berbasis NIK

Project ini merupakan implementasi sistem layanan publik terdistribusi berbasis NIK yang terdiri dari empat service mandiri, yaitu E-KTP Service, RSUD Service, Bansos Service, dan SPBU Service. Setiap service berjalan pada port berbeda, memiliki database masing-masing, serta saling berkomunikasi menggunakan REST API berbasis HTTP.

Project ini dibuat untuk memenuhi tugas UAS mata kuliah Komputasi Paralel dan Terdistribusi.

---

## Anggota dan Pembagian Service

| Nama    | Service        | Tanggung Jawab                                                      |
| ------- | -------------- | ------------------------------------------------------------------- |
| Putra   | E-KTP Service  | Pusat data warga, verifikasi NIK, audit log, rekam medis, kuota BBM |
| Billy   | RSUD Service   | Registrasi pasien, tarif otomatis, pengiriman rekam medis           |
| Anggita | Bansos Service | Registrasi penerima bantuan sosial dan status bansos                |
| Nunu    | SPBU Service   | Transaksi BBM berbasis NIK dan update kuota BBM                     |

---

## Arsitektur Sistem

Sistem ini menggunakan pendekatan microservice sederhana. Setiap service memiliki database sendiri dan berkomunikasi melalui endpoint API.

```text
+------------------+          +------------------+
|   RSUD Service   | -------> |   E-KTP Service  |
| localhost:8001   |          | localhost:8000   |
+------------------+          +------------------+
          |                            ^
          |                            |
          v                            |
+------------------+          +------------------+
|  Bansos Service  | <------- |   SPBU Service   |
| localhost:8003   |          | localhost:8002   |
+------------------+          +------------------+
```

---

## Daftar Service

| Service        | Port | Database  | Fungsi Utama                      |
| -------------- | ---: | --------- | --------------------------------- |
| E-KTP Service  | 8000 | db_ektp   | Pusat verifikasi identitas warga  |
| RSUD Service   | 8001 | db_rsud   | Registrasi pasien dan rekam medis |
| SPBU Service   | 8002 | db_spbu   | Transaksi BBM berbasis NIK        |
| Bansos Service | 8003 | db_bansos | Data penerima bantuan sosial      |

---

## Teknologi yang Digunakan

* PHP Native
* MySQL / MariaDB
* PDO
* cURL
* REST API
* Tailwind CSS CDN
* Laragon
* Visual Studio Code
* Git dan GitHub
* Postman untuk pengujian API

---

## Struktur Folder

```text
uas-nik-terdistribusi/
├── ektp-service/
│   ├── app/
│   ├── public/
│   ├── views/
│   ├── database/
│   └── README.md
│
├── rsud-service/
│   ├── app/
│   ├── public/
│   ├── views/
│   ├── database/
│   └── README.md
│
├── bansos-service/
│   ├── app/
│   ├── public/
│   ├── views/
│   ├── database/
│   └── README.md
│
├── spbu-service/
│   ├── app/
│   ├── public/
│   ├── views/
│   ├── database/
│   └── README.md
│
└── docs/
    └── README.md
```

---

# 1. E-KTP Service

## Informasi Service

| Item     | Keterangan                         |
| -------- | ---------------------------------- |
| Port     | localhost:8000                     |
| Database | db_ektp                            |
| Peran    | Pusat data warga dan integrasi NIK |

## Fitur

* Dashboard E-KTP
* CRUD data warga
* Tambah data warga
* Edit data warga
* Nonaktifkan / aktifkan warga
* Verifikasi NIK
* Cek status warga
* Menerima rekam medis dari RSUD
* Update kuota BBM dari SPBU
* Audit log integrasi antar-service

## Endpoint API

### Verifikasi NIK

```http
GET /api/verify-nik/{nik}
```

Contoh:

```text
http://localhost:8000/api/verify-nik/3302010101010001
```

Fungsi endpoint ini adalah memverifikasi apakah NIK terdaftar dan aktif di database E-KTP.

---

### Cek Status Warga

```http
GET /api/citizen-status/{nik}
```

Contoh:

```text
http://localhost:8000/api/citizen-status/3302010101010001
```

Endpoint ini digunakan oleh RSUD, Bansos, dan SPBU untuk membaca status ekonomi, status aktif, dan kuota BBM warga.

---

### Simpan Rekam Medis

```http
POST /api/medical-record
```

Contoh:

```text
http://localhost:8000/api/medical-record
```

Header:

```text
Content-Type: application/json
```

Body:

```json
{
  "nik": "3302010101010001",
  "diagnosis": "Demam tinggi",
  "tindakan": "Pemeriksaan suhu dan tekanan darah",
  "obat": "Paracetamol 500mg",
  "rumah_sakit": "RSUD Service",
  "tanggal_periksa": "2026-06-16"
}
```

Endpoint ini digunakan oleh RSUD Service untuk mengirim riwayat medis pasien ke E-KTP Service.

---

### Update Kuota BBM

```http
PUT /api/bbm-quota/{nik}
```

Contoh:

```text
http://localhost:8000/api/bbm-quota/3302010101010001
```

Header:

```text
Content-Type: application/json
```

Body:

```json
{
  "kuota_bbm": 25
}
```

Endpoint ini digunakan oleh SPBU Service untuk memperbarui sisa kuota BBM setelah transaksi.

---

## Halaman Web

| Halaman      | URL                                   |
| ------------ | ------------------------------------- |
| Dashboard    | http://localhost:8000                 |
| Data Warga   | http://localhost:8000/citizens        |
| Tambah Warga | http://localhost:8000/citizens/create |
| Rekam Medis  | http://localhost:8000/medical-records |
| Audit Log    | http://localhost:8000/audit-logs      |

---

# 2. RSUD Service

## Informasi Service

| Item     | Keterangan                                   |
| -------- | -------------------------------------------- |
| Port     | localhost:8001                               |
| Database | db_rsud                                      |
| Peran    | Registrasi pasien dan pengiriman rekam medis |

## Fitur

* Dashboard RSUD
* Data pasien
* Registrasi pasien berbasis NIK
* Verifikasi NIK ke E-KTP Service
* Cek status bansos ke Bansos Service
* Logika tarif otomatis
* Hapus data pasien
* Sinkronisasi ulang tarif pasien
* Form rekam medis digital
* Kirim rekam medis ke E-KTP Service

## Logika Tarif Otomatis

| Kondisi                              | Jenis Pasien | Tarif        |
| ------------------------------------ | ------------ | ------------ |
| Penerima bansos aktif                | bansos       | GRATIS       |
| Status ekonomi kurang mampu / rentan | kurang_mampu | Diskon 20%   |
| Warga umum                           | umum         | Tarif Normal |

## Endpoint API

### Registrasi Pasien

```http
POST /api/register-patient
```

Contoh:

```text
http://localhost:8001/api/register-patient
```

Header:

```text
Content-Type: application/json
```

Body:

```json
{
  "nik": "3302010101010001"
}
```

Endpoint ini digunakan untuk registrasi pasien berdasarkan data warga yang diverifikasi dari E-KTP Service.

---

### Kirim Rekam Medis

```http
POST /api/medical-record
```

Contoh:

```text
http://localhost:8001/api/medical-record
```

Header:

```text
Content-Type: application/json
```

Body:

```json
{
  "nik": "3302010101010001",
  "diagnosis": "Demam tinggi",
  "tindakan": "Pemeriksaan fisik",
  "obat": "Paracetamol",
  "tanggal_periksa": "2026-06-16"
}
```

Endpoint ini digunakan untuk mengirim data rekam medis pasien dari RSUD Service ke E-KTP Service.

---

## Halaman Web

| Halaman           | URL                                    |
| ----------------- | -------------------------------------- |
| Dashboard         | http://localhost:8001                  |
| Data Pasien       | http://localhost:8001/patients         |
| Registrasi Pasien | http://localhost:8001/register-patient |
| Kirim Rekam Medis | http://localhost:8001/medical-record   |

---

# 3. Bansos Service

## Informasi Service

| Item     | Keterangan                          |
| -------- | ----------------------------------- |
| Port     | localhost:8003                      |
| Database | db_bansos                           |
| Peran    | Pengelolaan penerima bantuan sosial |

## Fitur

* Dashboard Bansos
* Registrasi penerima bansos
* Validasi status ekonomi ke E-KTP Service
* Data penerima bansos
* Aktifkan penerima
* Nonaktifkan penerima
* Hapus penerima
* Endpoint status bansos untuk RSUD dan SPBU

## Aturan Kelayakan

| Status Ekonomi dari E-KTP | Kelayakan                   |
| ------------------------- | --------------------------- |
| kurang_mampu              | Layak menerima bansos       |
| rentan                    | Layak menerima bansos       |
| mampu                     | Tidak layak menerima bansos |

## Endpoint API

### Registrasi Penerima Bansos

```http
POST /api/register-recipient
```

Contoh:

```text
http://localhost:8003/api/register-recipient
```

Header:

```text
Content-Type: application/json
```

Body:

```json
{
  "nik": "3302010202020002",
  "jenis_bantuan": "Bantuan Sosial Pokok"
}
```

Endpoint ini digunakan untuk mendaftarkan warga sebagai penerima bansos setelah divalidasi status ekonominya ke E-KTP Service.

---

### Cek Status Bansos

```http
GET /api/bansos-status/{nik}
```

Contoh:

```text
http://localhost:8003/api/bansos-status/3302010202020002
```

Endpoint ini digunakan oleh RSUD dan SPBU untuk mengetahui apakah seorang warga merupakan penerima bansos aktif.

---

## Halaman Web

| Halaman             | URL                                      |
| ------------------- | ---------------------------------------- |
| Dashboard           | http://localhost:8003                    |
| Data Penerima       | http://localhost:8003/recipients         |
| Registrasi Penerima | http://localhost:8003/register-recipient |

---

# 4. SPBU Service

## Informasi Service

| Item     | Keterangan                 |
| -------- | -------------------------- |
| Port     | localhost:8002             |
| Database | db_spbu                    |
| Peran    | Transaksi BBM berbasis NIK |

## Fitur

* Dashboard SPBU
* Transaksi BBM berbasis NIK
* Cek status warga ke E-KTP Service
* Cek status bansos ke Bansos Service
* Hitung harga BBM otomatis
* Update kuota BBM ke E-KTP Service
* Data transaksi BBM
* Edit keterangan transaksi
* Hapus riwayat transaksi lokal

## Logika Harga BBM

| Kondisi                              | Harga per Liter |
| ------------------------------------ | --------------: |
| Penerima bansos aktif                |        Rp 9.000 |
| Status ekonomi kurang mampu / rentan |       Rp 10.000 |
| Warga umum                           |       Rp 13.000 |

## Catatan CRUD Transaksi

Pada SPBU Service, fitur update hanya digunakan untuk mengubah keterangan transaksi. Fitur hapus hanya menghapus riwayat transaksi lokal pada SPBU Service dan tidak mengembalikan kuota BBM di E-KTP Service. Hal ini dilakukan agar data kuota tetap konsisten.

## Endpoint API

### Transaksi BBM

```http
POST /api/fuel-transaction
```

Contoh:

```text
http://localhost:8002/api/fuel-transaction
```

Header:

```text
Content-Type: application/json
```

Body:

```json
{
  "nik": "3302010202020002",
  "jenis_bbm": "Pertalite",
  "jumlah_liter": 5,
  "keterangan": "Transaksi loket 1"
}
```

Alur endpoint:

```text
1. SPBU menerima input NIK dan jumlah liter.
2. SPBU memanggil E-KTP Service untuk mengecek status warga dan kuota BBM.
3. SPBU memanggil Bansos Service untuk mengecek status bansos.
4. SPBU menghitung harga BBM otomatis.
5. SPBU mengirim update kuota ke E-KTP Service.
6. SPBU menyimpan transaksi ke database lokal db_spbu.
```

---

## Halaman Web

| Halaman        | URL                                    |
| -------------- | -------------------------------------- |
| Dashboard      | http://localhost:8002                  |
| Data Transaksi | http://localhost:8002/transactions     |
| Transaksi BBM  | http://localhost:8002/fuel-transaction |

---

# Cara Menjalankan Project

Pastikan Laragon, MySQL, dan PHP sudah aktif.

Buka 4 terminal berbeda.

## Jalankan E-KTP Service

```bash
cd D:\laragon\www\uas-nik-terdistribusi\ektp-service\public
php -S localhost:8000
```

## Jalankan RSUD Service

```bash
cd D:\laragon\www\uas-nik-terdistribusi\rsud-service\public
php -S localhost:8001
```

## Jalankan SPBU Service

```bash
cd D:\laragon\www\uas-nik-terdistribusi\spbu-service\public
php -S localhost:8002
```

## Jalankan Bansos Service

```bash
cd D:\laragon\www\uas-nik-terdistribusi\bansos-service\public
php -S localhost:8003
```

---

# Database

Project ini menggunakan empat database terpisah:

```text
db_ektp
db_rsud
db_spbu
db_bansos
```

Setiap database berada pada service masing-masing. File SQL berada di folder `database` pada setiap service.

Contoh:

```text
ektp-service/database/db_ektp.sql
rsud-service/database/db_rsud.sql
spbu-service/database/db_spbu.sql
bansos-service/database/db_bansos.sql
```

Import file SQL melalui phpMyAdmin atau MySQL client sebelum menjalankan service.

---

# Skenario Testing End-to-End

## Skenario 1: Tambah Warga di E-KTP

1. Buka:

```text
http://localhost:8000/citizens/create
```

2. Tambahkan warga baru dengan status ekonomi `kurang_mampu` atau `rentan`.
3. Pastikan data muncul di:

```text
http://localhost:8000/citizens
```

---

## Skenario 2: Registrasi Penerima Bansos

1. Buka:

```text
http://localhost:8003/register-recipient
```

2. Input NIK warga yang status ekonominya `kurang_mampu` atau `rentan`.
3. Klik cek kelayakan.
4. Klik konfirmasi registrasi penerima.
5. Pastikan data muncul di:

```text
http://localhost:8003/recipients
```

---

## Skenario 3: Registrasi Pasien RSUD

1. Buka:

```text
http://localhost:8001/register-patient
```

2. Input NIK warga.
3. Sistem mengambil data dari E-KTP dan mengecek status bansos.
4. Sistem menampilkan tarif otomatis.
5. Klik konfirmasi registrasi pasien.
6. Pastikan data muncul di:

```text
http://localhost:8001/patients
```

Hasil yang diharapkan:

```text
Jika bansos aktif     → GRATIS
Jika kurang mampu     → Diskon 20%
Jika warga umum       → Tarif Normal
```

---

## Skenario 4: Kirim Rekam Medis RSUD ke E-KTP

1. Buka:

```text
http://localhost:8001/medical-record
```

2. Pilih pasien.
3. Isi diagnosis, tindakan, dan obat.
4. Klik kirim rekam medis.
5. Buka:

```text
http://localhost:8000/medical-records
```

6. Pastikan data rekam medis muncul pada E-KTP Service.

---

## Skenario 5: Transaksi BBM SPBU

1. Buka:

```text
http://localhost:8002/fuel-transaction
```

2. Input NIK warga.
3. Input jenis BBM dan jumlah liter.
4. Klik cek transaksi.
5. Sistem mengecek:

   * status warga dari E-KTP,
   * status bansos dari Bansos,
   * kuota BBM dari E-KTP.
6. Klik konfirmasi transaksi.
7. Buka:

```text
http://localhost:8002/transactions
```

8. Pastikan transaksi tersimpan.
9. Buka:

```text
http://localhost:8000/citizens
```

10. Pastikan kuota BBM warga berkurang.

---

# Bukti Konsep Komputasi Terdistribusi

Project ini memenuhi konsep sistem terdistribusi karena:

1. Terdiri dari beberapa service yang berjalan secara terpisah.
2. Setiap service memiliki database sendiri.
3. Service berkomunikasi menggunakan REST API.
4. Setiap service memiliki tanggung jawab berbeda.
5. Kegagalan satu service dapat memengaruhi proses integrasi service lain.
6. Data dipertukarkan melalui jaringan lokal menggunakan HTTP.
7. Sistem mendukung integrasi lintas layanan seperti RSUD ke E-KTP, SPBU ke E-KTP, dan SPBU ke Bansos.

---

# Alur Integrasi Utama

## RSUD ke E-KTP

```text
RSUD input NIK
→ RSUD call E-KTP /api/verify-nik/{nik}
→ RSUD menyimpan pasien
```

## RSUD ke Bansos

```text
RSUD input NIK
→ RSUD call Bansos /api/bansos-status/{nik}
→ RSUD menentukan tarif otomatis
```

## RSUD Kirim Rekam Medis ke E-KTP

```text
RSUD input rekam medis
→ RSUD call E-KTP /api/medical-record
→ E-KTP menyimpan riwayat medis
```

## Bansos ke E-KTP

```text
Bansos input NIK
→ Bansos call E-KTP /api/citizen-status/{nik}
→ Bansos menentukan kelayakan penerima
```

## SPBU ke E-KTP dan Bansos

```text
SPBU input transaksi
→ SPBU call E-KTP /api/citizen-status/{nik}
→ SPBU call Bansos /api/bansos-status/{nik}
→ SPBU hitung harga otomatis
→ SPBU call E-KTP /api/bbm-quota/{nik}
→ SPBU simpan transaksi lokal
```

---

# Penanganan Error

Sistem menangani beberapa kondisi error berikut:

| Kondisi                         | Respons Sistem                              |
| ------------------------------- | ------------------------------------------- |
| NIK tidak valid                 | Menampilkan pesan format NIK harus 16 digit |
| NIK tidak ditemukan             | Menampilkan pesan NIK tidak tersedia        |
| Warga nonaktif                  | Proses verifikasi ditolak                   |
| Service tujuan mati             | Menampilkan pesan gagal menghubungi service |
| Kuota BBM tidak cukup           | Transaksi BBM ditolak                       |
| Pasien sudah terdaftar          | Registrasi pasien ditolak                   |
| Penerima bansos sudah terdaftar | Registrasi bansos ditolak                   |

---

# Status Implementasi

| Modul                     | Status  |
| ------------------------- | ------- |
| E-KTP Service             | Selesai |
| RSUD Service              | Selesai |
| Bansos Service            | Selesai |
| SPBU Service              | Selesai |
| Integrasi antar-service   | Selesai |
| UI responsif              | Selesai |
| CRUD dasar setiap service | Selesai |
| Testing end-to-end        | Selesai |

---

# Pengembangan Lanjutan

Beberapa fitur yang dapat dikembangkan selanjutnya:

* Login operator
* Role admin per service
* API key antar-service
* Export laporan PDF / Excel
* Grafik dashboard
* Pagination tabel
* Search dan filter data
* Deployment server
* Docker Compose
* Unit testing dan integration testing otomatis
* Scheduler reset kuota BBM bulanan
* Soft delete untuk semua data transaksi penting

---

# Kesimpulan

Project ini berhasil mengimplementasikan sistem layanan publik terdistribusi berbasis NIK dengan empat service mandiri. E-KTP Service berperan sebagai pusat data warga, RSUD Service mengelola registrasi pasien dan rekam medis, Bansos Service mengelola penerima bantuan sosial, sedangkan SPBU Service mengelola transaksi BBM berbasis NIK.

Setiap service berjalan pada port berbeda, memiliki database sendiri, serta saling terhubung melalui REST API. Dengan demikian, project ini menunjukkan implementasi dasar sistem terdistribusi yang fungsional, terintegrasi, dan dapat didemonstrasikan secara end-to-end.
