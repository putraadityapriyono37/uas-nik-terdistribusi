# Bansos Service

Bansos Service adalah layanan pengelolaan penerima bantuan sosial berbasis NIK. Service ini memanggil E-KTP Service untuk mengecek status ekonomi warga sebelum mendaftarkan penerima bantuan.

## Informasi Service

| Keterangan       | Detail           |
| ---------------- | ---------------- |
| Nama Service     | Bansos Service   |
| Port             | `localhost:8003` |
| Database         | `db_bansos`      |
| Bahasa           | PHP Native       |
| Format API       | JSON             |
| Koneksi Database | PDO              |
| Integrasi Utama  | E-KTP Service    |

## Database

Database yang digunakan:

```text
db_bansos

Tabel utama:

Tabel	Fungsi
recipients	Menyimpan data penerima bantuan sosial
Endpoint API
1. Registrasi Penerima Bansos

Endpoint ini digunakan untuk mendaftarkan warga sebagai penerima bansos berdasarkan status ekonomi dari E-KTP Service.

POST /api/register-recipient

Contoh request:

http://localhost:8003/api/register-recipient

Header:

Content-Type: application/json

Body request:

{
  "nik": "3302010202020002",
  "jenis_bantuan": "Bantuan Sembako",
  "periode_bantuan": "2026",
  "keterangan": "Penerima bantuan berdasarkan status ekonomi kurang mampu"
}

Contoh response sukses:

{
  "success": true,
  "message": "Penerima bansos berhasil didaftarkan berdasarkan data E-KTP.",
  "data": {
    "id": "1",
    "nik": "3302010202020002",
    "nama": "Budi Santoso",
    "status_ekonomi": "kurang_mampu",
    "jenis_bantuan": "Bantuan Sembako",
    "periode_bantuan": "2026",
    "status_bansos": "aktif",
    "sumber_data": "E-KTP Service"
  }
}
2. Cek Status Bansos

Endpoint ini digunakan untuk mengecek apakah NIK terdaftar sebagai penerima bansos aktif. Endpoint ini nantinya digunakan oleh SPBU Service.

GET /api/bansos-status/{nik}

Contoh request:

http://localhost:8003/api/bansos-status/3302010202020002

Contoh response sukses:

{
  "success": true,
  "message": "Status bansos berhasil ditemukan.",
  "data": {
    "nik": "3302010202020002",
    "nama": "Budi Santoso",
    "status_ekonomi": "kurang_mampu",
    "jenis_bantuan": "Bantuan Sembako",
    "periode_bantuan": "2026",
    "status_bansos": "aktif"
  }
}
Halaman Web
Halaman	URL	Keterangan
Dashboard	http://localhost:8003	Ringkasan layanan Bansos
Data Penerima	http://localhost:8003/recipients	Menampilkan data penerima bantuan sosial
Cara Menjalankan

Pastikan database db_bansos sudah dibuat dan MySQL Laragon aktif.

cd bansos-service/public
php -S localhost:8003

Buka dashboard di browser:

http://localhost:8003
Cara Testing API

API dapat dites menggunakan Postman.

Method	Endpoint	Keterangan
POST	/api/register-recipient	Registrasi penerima bansos
GET	/api/bansos-status/{nik}	Cek status penerima bansos
Struktur Folder
bansos-service/
├── app/
│   ├── config/
│   │   ├── app.php
│   │   └── database.php
│   ├── controllers/
│   │   └── RecipientController.php
│   ├── helpers/
│   │   ├── http_client.php
│   │   ├── request.php
│   │   └── response.php
│   └── routes/
│       └── api.php
├── database/
│   └── db_bansos.sql
├── public/
│   ├── index.php
│   └── assets/
├── views/
│   ├── dashboard.php
│   ├── recipients.php
│   └── layout.php
└── README.md
Catatan Integrasi
Bansos Service memanggil E-KTP Service melalui endpoint GET /api/citizen-status/{nik}.
Jika status ekonomi warga kurang_mampu atau rentan, warga dapat didaftarkan sebagai penerima bansos.
Endpoint GET /api/bansos-status/{nik} akan digunakan oleh SPBU Service untuk menentukan harga subsidi dan diskon.
```
