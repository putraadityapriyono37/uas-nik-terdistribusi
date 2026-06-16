# Dokumentasi Utama — Sistem Integrasi Layanan Publik Berbasis NIK

Project ini merupakan simulasi sistem layanan publik terdistribusi berbasis NIK. Sistem dibagi menjadi empat service terpisah yang berjalan pada port berbeda dan saling berkomunikasi menggunakan HTTP API.

Project ini dibuat untuk tugas UAS Komputasi Paralel dan Terdistribusi.

## Anggota dan Pembagian Service

| Nama    | Service        | Peran                                |
| ------- | -------------- | ------------------------------------ |
| Putra   | E-KTP Service  | Pusat data warga dan hub utama       |
| Billy   | RSUD Service   | Registrasi pasien dan rekam medis    |
| Anggita | Bansos Service | Registrasi penerima bantuan sosial   |
| Nunu    | SPBU Service   | Transaksi BBM berbasis NIK dan kuota |

## Tujuan Sistem

Sistem ini dibuat untuk mensimulasikan integrasi antar layanan publik menggunakan NIK sebagai identitas utama warga.

Setiap service memiliki database sendiri dan berjalan secara mandiri. Integrasi antar service dilakukan menggunakan HTTP API, sehingga sistem ini menggambarkan konsep dasar arsitektur terdistribusi.

## Arsitektur Sistem

```text
uas-nik-terdistribusi/
├── ektp-service/
├── rsud-service/
├── bansos-service/
├── spbu-service/
└── docs/
```

Setiap service memiliki struktur internal yang relatif sama:

```text
service-name/
├── app/
│   ├── config/
│   ├── controllers/
│   ├── helpers/
│   └── routes/
├── database/
├── public/
├── views/
└── README.md
```

## Daftar Service

| Service        | Port             | Database    | Fungsi Utama                                                           |
| -------------- | ---------------- | ----------- | ---------------------------------------------------------------------- |
| E-KTP Service  | `localhost:8000` | `db_ektp`   | Pusat data warga, verifikasi NIK, status warga, rekam medis, kuota BBM |
| RSUD Service   | `localhost:8001` | `db_rsud`   | Registrasi pasien dan pengiriman rekam medis ke E-KTP                  |
| SPBU Service   | `localhost:8002` | `db_spbu`   | Transaksi BBM, cek bansos, dan update kuota BBM                        |
| Bansos Service | `localhost:8003` | `db_bansos` | Registrasi penerima bansos dan pengecekan status bansos                |

## Diagram Integrasi

```text
                         ┌──────────────────────────┐
                         │      E-KTP Service        │
                         │      localhost:8000       │
                         │      Database: db_ektp    │
                         └─────────────┬────────────┘
                                       │
              ┌────────────────────────┼────────────────────────┐
              │                        │                        │
              ▼                        ▼                        ▼
┌────────────────────────┐  ┌────────────────────────┐  ┌────────────────────────┐
│      RSUD Service       │  │     Bansos Service      │  │      SPBU Service       │
│      localhost:8001     │  │     localhost:8003      │  │      localhost:8002     │
│      Database: db_rsud  │  │     Database: db_bansos │  │      Database: db_spbu  │
└────────────────────────┘  └─────────────┬──────────┘  └─────────────┬──────────┘
                                          │                           │
                                          └─────────── dipanggil ─────┘
                                               oleh SPBU Service
```

## Alur Komunikasi Antar Service

### 1. RSUD ke E-KTP

RSUD memanggil E-KTP untuk memverifikasi NIK pasien.

```text
RSUD Service → E-KTP Service
POST /api/register-patient
GET  /api/verify-nik/{nik}
```

Alur:

```text
1. User mengirim NIK ke RSUD.
2. RSUD memanggil E-KTP untuk verifikasi NIK.
3. Jika NIK valid, data warga disimpan sebagai pasien RSUD.
```

### 2. RSUD Mengirim Rekam Medis ke E-KTP

RSUD mengirim data rekam medis pasien ke E-KTP.

```text
RSUD Service → E-KTP Service
POST /api/medical-record
```

Alur:

```text
1. RSUD menerima data rekam medis.
2. RSUD memverifikasi NIK ke E-KTP.
3. Jika valid, RSUD mengirim data rekam medis ke E-KTP.
4. E-KTP menyimpan data pada tabel medical_records.
```

### 3. Bansos ke E-KTP

Bansos memanggil E-KTP untuk mengecek status ekonomi warga.

```text
Bansos Service → E-KTP Service
GET /api/citizen-status/{nik}
```

Alur:

```text
1. User mengirim NIK ke Bansos.
2. Bansos memanggil E-KTP untuk mengecek status ekonomi.
3. Jika status ekonomi kurang_mampu atau rentan, warga dapat didaftarkan sebagai penerima bansos.
```

### 4. SPBU ke E-KTP dan Bansos

SPBU memanggil E-KTP dan Bansos dalam satu proses transaksi BBM.

```text
SPBU Service → E-KTP Service
GET /api/citizen-status/{nik}

SPBU Service → Bansos Service
GET /api/bansos-status/{nik}

SPBU Service → E-KTP Service
PUT /api/bbm-quota/{nik}
```

Alur:

```text
1. User melakukan transaksi BBM menggunakan NIK.
2. SPBU mengecek status warga dan kuota BBM ke E-KTP.
3. SPBU mengecek status bansos ke Bansos.
4. SPBU menentukan harga BBM berdasarkan status ekonomi dan status bansos.
5. SPBU mengurangi kuota BBM.
6. SPBU mengirim update kuota BBM ke E-KTP.
7. Transaksi disimpan di database SPBU.
```

## Endpoint Utama

### E-KTP Service

Base URL:

```text
http://localhost:8000
```

| Method | Endpoint                    | Fungsi                                 |
| ------ | --------------------------- | -------------------------------------- |
| GET    | `/api/verify-nik/{nik}`     | Verifikasi NIK warga                   |
| GET    | `/api/citizen-status/{nik}` | Cek status ekonomi dan kuota BBM warga |
| POST   | `/api/medical-record`       | Menerima rekam medis dari RSUD         |
| PUT    | `/api/bbm-quota/{nik}`      | Update kuota BBM dari SPBU             |

### RSUD Service

Base URL:

```text
http://localhost:8001
```

| Method | Endpoint                | Fungsi                            |
| ------ | ----------------------- | --------------------------------- |
| POST   | `/api/register-patient` | Registrasi pasien berdasarkan NIK |
| POST   | `/api/medical-record`   | Mengirim rekam medis ke E-KTP     |

### SPBU Service

Base URL:

```text
http://localhost:8002
```

| Method | Endpoint                | Fungsi                               |
| ------ | ----------------------- | ------------------------------------ |
| POST   | `/api/fuel-transaction` | Memproses transaksi BBM berbasis NIK |

### Bansos Service

Base URL:

```text
http://localhost:8003
```

| Method | Endpoint                   | Fungsi                     |
| ------ | -------------------------- | -------------------------- |
| POST   | `/api/register-recipient`  | Registrasi penerima bansos |
| GET    | `/api/bansos-status/{nik}` | Cek status penerima bansos |

## Halaman Web

### E-KTP Service

| Halaman     | URL                                     | Keterangan                          |
| ----------- | --------------------------------------- | ----------------------------------- |
| Dashboard   | `http://localhost:8000`                 | Ringkasan layanan E-KTP             |
| Data Warga  | `http://localhost:8000/citizens`        | Data master warga                   |
| Rekam Medis | `http://localhost:8000/medical-records` | Data rekam medis dari RSUD          |
| Audit Log   | `http://localhost:8000/audit-logs`      | Riwayat request yang masuk ke E-KTP |

### RSUD Service

| Halaman     | URL                              | Keterangan                          |
| ----------- | -------------------------------- | ----------------------------------- |
| Dashboard   | `http://localhost:8001`          | Ringkasan layanan RSUD              |
| Data Pasien | `http://localhost:8001/patients` | Data pasien yang telah diregistrasi |

### SPBU Service

| Halaman        | URL                                  | Keterangan             |
| -------------- | ------------------------------------ | ---------------------- |
| Dashboard      | `http://localhost:8002`              | Ringkasan layanan SPBU |
| Data Transaksi | `http://localhost:8002/transactions` | Data transaksi BBM     |

### Bansos Service

| Halaman       | URL                                | Keterangan                   |
| ------------- | ---------------------------------- | ---------------------------- |
| Dashboard     | `http://localhost:8003`            | Ringkasan layanan Bansos     |
| Data Penerima | `http://localhost:8003/recipients` | Data penerima bantuan sosial |

## Teknologi yang Digunakan

| Kebutuhan         | Teknologi               |
| ----------------- | ----------------------- |
| Backend           | PHP Native              |
| Database          | MySQL / MariaDB         |
| UI                | HTML + Tailwind CSS CDN |
| HTTP Client       | cURL                    |
| Format API        | JSON                    |
| Database Access   | PDO                     |
| Testing API       | Postman                 |
| Version Control   | Git dan GitHub          |
| Local Development | Laragon dan VSCode      |

## Cara Menjalankan Project

Pastikan Laragon sudah aktif, terutama service MySQL.

### 1. Jalankan E-KTP Service

```bash
cd ektp-service/public
php -S localhost:8000
```

### 2. Jalankan RSUD Service

```bash
cd rsud-service/public
php -S localhost:8001
```

### 3. Jalankan SPBU Service

```bash
cd spbu-service/public
php -S localhost:8002
```

### 4. Jalankan Bansos Service

```bash
cd bansos-service/public
php -S localhost:8003
```

Semua service harus dijalankan pada terminal yang berbeda.

## Cara Import Database

Setiap service memiliki file SQL masing-masing di folder `database`.

| Service | File SQL                                | Database    |
| ------- | --------------------------------------- | ----------- |
| E-KTP   | `ektp-service/database/db_ektp.sql`     | `db_ektp`   |
| RSUD    | `rsud-service/database/db_rsud.sql`     | `db_rsud`   |
| SPBU    | `spbu-service/database/db_spbu.sql`     | `db_spbu`   |
| Bansos  | `bansos-service/database/db_bansos.sql` | `db_bansos` |

Langkah umum:

```text
1. Buka phpMyAdmin.
2. Buat database sesuai nama service.
3. Pilih database.
4. Masuk ke tab Import.
5. Pilih file SQL dari folder database.
6. Klik Go / Kirim.
```

## Contoh Testing API Menggunakan Postman

### 1. Verifikasi NIK E-KTP

```http
GET http://localhost:8000/api/verify-nik/3302010101010001
```

### 2. Registrasi Pasien RSUD

```http
POST http://localhost:8001/api/register-patient
```

Body:

```json
{
  "nik": "3302010101010001"
}
```

### 3. Kirim Rekam Medis dari RSUD ke E-KTP

```http
POST http://localhost:8001/api/medical-record
```

Body:

```json
{
  "nik": "3302010101010001",
  "diagnosis": "Demam tinggi",
  "tindakan": "Pemeriksaan suhu dan tekanan darah",
  "obat": "Paracetamol 500mg",
  "tanggal_periksa": "2026-06-10"
}
```

### 4. Registrasi Penerima Bansos

```http
POST http://localhost:8003/api/register-recipient
```

Body:

```json
{
  "nik": "3302010202020002",
  "jenis_bantuan": "Bantuan Sembako",
  "periode_bantuan": "2026",
  "keterangan": "Penerima bantuan berdasarkan status ekonomi kurang mampu"
}
```

### 5. Cek Status Bansos

```http
GET http://localhost:8003/api/bansos-status/3302010202020002
```

### 6. Transaksi BBM SPBU

```http
POST http://localhost:8002/api/fuel-transaction
```

Body:

```json
{
  "nik": "3302010202020002",
  "jenis_bbm": "Pertalite",
  "jumlah_liter": 5
}
```

## Skenario Demo Integrasi

Skenario demo yang disarankan saat presentasi:

```text
1. Jalankan semua service pada port 8000, 8001, 8002, dan 8003.
2. Buka dashboard E-KTP, RSUD, Bansos, dan SPBU.
3. Tes verifikasi NIK melalui E-KTP.
4. Registrasikan pasien melalui RSUD.
5. Kirim rekam medis melalui RSUD dan cek hasilnya di E-KTP.
6. Registrasikan penerima bansos melalui Bansos.
7. Lakukan transaksi BBM melalui SPBU.
8. Cek kuota BBM di E-KTP.
9. Cek audit log E-KTP untuk melihat riwayat request antar service.
```

## Bukti Konsep Komputasi Terdistribusi

Project ini memenuhi konsep sistem terdistribusi karena:

```text
1. Terdapat empat service yang berjalan secara terpisah.
2. Setiap service memiliki database sendiri.
3. Setiap service berjalan pada port berbeda.
4. Integrasi dilakukan melalui HTTP API.
5. Service dapat saling memanggil untuk menyelesaikan proses bisnis.
6. Jika salah satu service mati, proses integrasi yang bergantung pada service tersebut akan gagal dan menghasilkan error handling.
```

## Bukti Konsep Komputasi Paralel

Project ini juga dapat menunjukkan konsep paralel secara sederhana karena:

```text
1. Empat service dapat berjalan bersamaan pada empat terminal berbeda.
2. Setiap service dapat menerima request secara mandiri.
3. SPBU melakukan proses transaksi dengan memanggil lebih dari satu service dalam satu alur.
4. Testing dapat dilakukan dengan beberapa request API menggunakan Postman.
```

## Error Handling

Beberapa kondisi error yang sudah ditangani:

| Kondisi                              | Response                                                  |
| ------------------------------------ | --------------------------------------------------------- |
| Format NIK tidak 16 digit            | Request ditolak                                           |
| NIK tidak ditemukan                  | Response gagal dengan status tidak ditemukan              |
| Service tujuan mati                  | Response gagal dengan pesan service tidak dapat dihubungi |
| Kuota BBM tidak cukup                | Transaksi SPBU ditolak                                    |
| Warga tidak memenuhi kriteria bansos | Registrasi bansos ditolak                                 |
| Body request bukan JSON              | Request ditolak                                           |

## Catatan Pengembangan

Saat ini input data utama dilakukan menggunakan Postman. Halaman web berfungsi sebagai dashboard dan monitoring data.

Tahap pengembangan berikutnya adalah membuat interaksi web yang lebih dinamis, seperti:

```text
1. Form registrasi pasien di RSUD.
2. Form pengiriman rekam medis di RSUD.
3. Form registrasi penerima bansos.
4. Form transaksi BBM di SPBU.
5. Tombol submit dari halaman web yang langsung memanggil endpoint API masing-masing service.
```

Dengan pengembangan tersebut, sistem tidak hanya dapat diuji melalui Postman, tetapi juga dapat digunakan langsung melalui tampilan web.

## Kesimpulan

Sistem Integrasi Layanan Publik Berbasis NIK ini merupakan simulasi sistem terdistribusi yang terdiri dari E-KTP, RSUD, Bansos, dan SPBU. Setiap service berjalan mandiri, memiliki database sendiri, dan saling berkomunikasi menggunakan HTTP API.

Project ini menunjukkan bagaimana beberapa layanan publik dapat terintegrasi menggunakan NIK sebagai identitas utama warga.
