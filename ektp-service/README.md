# E-KTP Service

E-KTP Service adalah pusat data warga dalam sistem integrasi layanan publik berbasis NIK. Service ini berperan sebagai hub utama yang menyediakan data identitas warga untuk digunakan oleh service lain seperti RSUD, Bansos, dan SPBU.

Service ini berjalan secara mandiri pada port `8000` dan memiliki database sendiri, yaitu `db_ektp`.

## Informasi Service

| Keterangan       | Detail                   |
| ---------------- | ------------------------ |
| Nama Service     | E-KTP Service            |
| Port             | `localhost:8000`         |
| Database         | `db_ektp`                |
| Bahasa           | PHP Native               |
| Format API       | JSON                     |
| Koneksi Database | PDO                      |
| Peran Sistem     | Hub utama verifikasi NIK |

## Database

Database yang digunakan:

```text
db_ektp
```

Tabel utama:

| Tabel             | Fungsi                                        |
| ----------------- | --------------------------------------------- |
| `citizens`        | Menyimpan data master warga                   |
| `medical_records` | Menyimpan rekam medis yang dikirim dari RSUD  |
| `audit_logs`      | Menyimpan riwayat request yang masuk ke E-KTP |

## Endpoint API

### 1. Verifikasi NIK

Endpoint ini digunakan untuk memverifikasi apakah NIK terdaftar di database E-KTP.

```http
GET /api/verify-nik/{nik}
```

Contoh request:

```text
http://localhost:8000/api/verify-nik/3302010101010001
```

Contoh response sukses:

```json
{
  "success": true,
  "message": "NIK valid dan terdaftar di E-KTP.",
  "data": {
    "nik": "3302010101010001",
    "nama": "Putra Aditya Priyono",
    "tempat_lahir": "Banyumas",
    "tanggal_lahir": "2004-01-01",
    "jenis_kelamin": "L",
    "alamat": "Purwokerto, Banyumas",
    "pekerjaan": "Mahasiswa",
    "status_aktif": "aktif"
  }
}
```

Contoh response gagal:

```json
{
  "success": false,
  "message": "NIK tidak ditemukan dalam database E-KTP.",
  "data": null
}
```

### 2. Cek Status Warga

Endpoint ini digunakan untuk melihat status ekonomi dan kuota BBM warga berdasarkan NIK.

```http
GET /api/citizen-status/{nik}
```

Contoh request:

```text
http://localhost:8000/api/citizen-status/3302010202020002
```

Contoh response sukses:

```json
{
  "success": true,
  "message": "Status warga berhasil ditemukan.",
  "data": {
    "nik": "3302010202020002",
    "nama": "Budi Santoso",
    "status_ekonomi": "kurang_mampu",
    "kuota_bbm": "30.00",
    "status_aktif": "aktif"
  }
}
```

### 3. Kirim Rekam Medis

Endpoint ini digunakan oleh RSUD Service untuk mengirim data rekam medis warga ke E-KTP.

```http
POST /api/medical-record
```

Contoh request:

```text
http://localhost:8000/api/medical-record
```

Header:

```text
Content-Type: application/json
```

Body request:

```json
{
  "nik": "3302010101010001",
  "diagnosis": "Demam dan batuk",
  "tindakan": "Pemeriksaan umum",
  "obat": "Paracetamol 500mg",
  "rumah_sakit": "RSUD Banyumas",
  "tanggal_periksa": "2026-06-10"
}
```

Contoh response sukses:

```json
{
  "success": true,
  "message": "Rekam medis berhasil dikirim ke E-KTP.",
  "data": {
    "id": "1",
    "nik": "3302010101010001",
    "nama": "Putra Aditya Priyono",
    "diagnosis": "Demam dan batuk",
    "tindakan": "Pemeriksaan umum",
    "obat": "Paracetamol 500mg",
    "rumah_sakit": "RSUD Banyumas",
    "tanggal_periksa": "2026-06-10"
  }
}
```

### 4. Update Kuota BBM

Endpoint ini digunakan oleh SPBU Service untuk memperbarui sisa kuota BBM warga setelah transaksi BBM.

```http
PUT /api/bbm-quota/{nik}
```

Contoh request:

```text
http://localhost:8000/api/bbm-quota/3302010101010001
```

Header:

```text
Content-Type: application/json
```

Body request:

```json
{
  "kuota_bbm": 24.5
}
```

Contoh response sukses:

```json
{
  "success": true,
  "message": "Kuota BBM berhasil diperbarui di E-KTP.",
  "data": {
    "nik": "3302010101010001",
    "nama": "Putra Aditya Priyono",
    "kuota_sebelumnya": "30.00",
    "kuota_sekarang": "24.50"
  }
}
```

## Halaman Web

| Halaman     | URL                                     | Keterangan                             |
| ----------- | --------------------------------------- | -------------------------------------- |
| Dashboard   | `http://localhost:8000`                 | Ringkasan layanan E-KTP                |
| Data Warga  | `http://localhost:8000/citizens`        | Menampilkan data master warga          |
| Rekam Medis | `http://localhost:8000/medical-records` | Menampilkan data rekam medis dari RSUD |

## Cara Menjalankan

Pastikan MySQL pada Laragon sudah aktif, lalu jalankan service dari folder `public`.

```bash
cd ektp-service/public
php -S localhost:8000
```

Buka dashboard di browser:

```text
http://localhost:8000
```

## Cara Testing API

API dapat dites menggunakan Postman.

Contoh endpoint yang dapat dites:

| Method | Endpoint                    | Keterangan                       |
| ------ | --------------------------- | -------------------------------- |
| GET    | `/api/verify-nik/{nik}`     | Verifikasi NIK warga             |
| GET    | `/api/citizen-status/{nik}` | Cek status ekonomi dan kuota BBM |
| POST   | `/api/medical-record`       | Kirim rekam medis dari RSUD      |
| PUT    | `/api/bbm-quota/{nik}`      | Update kuota BBM dari SPBU       |

## Struktur Folder

```text
ektp-service/
├── app/
│   ├── config/
│   │   ├── app.php
│   │   └── database.php
│   ├── controllers/
│   │   └── CitizenController.php
│   ├── helpers/
│   │   ├── request.php
│   │   └── response.php
│   └── routes/
│       └── api.php
├── database/
│   └── db_ektp.sql
├── public/
│   ├── index.php
│   └── assets/
├── views/
│   ├── dashboard.php
│   ├── citizens.php
│   ├── medical_records.php
│   └── layout.php
└── README.md
```

## Catatan Integrasi

- RSUD Service menggunakan endpoint E-KTP untuk verifikasi NIK dan mengirim rekam medis.
- Bansos Service menggunakan endpoint E-KTP untuk mengecek status ekonomi warga.
- SPBU Service menggunakan endpoint E-KTP untuk verifikasi NIK dan update kuota BBM.
- Setiap request penting yang masuk ke E-KTP dicatat pada tabel `audit_logs`.

## Catatan Teknis

- Service ini menggunakan PHP Native.
- Tampilan web menggunakan Tailwind CSS via CDN.
- Koneksi database menggunakan PDO.
- Response API menggunakan format JSON.
- Service ini menjadi pusat verifikasi data warga dalam sistem integrasi layanan publik berbasis NIK.
