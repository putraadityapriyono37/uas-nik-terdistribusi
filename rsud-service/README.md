# RSUD Service

## Deskripsi

**RSUD Service** merupakan layanan registrasi pasien berbasis verifikasi NIK dalam sistem layanan publik terdistribusi. Service ini melakukan integrasi dengan **E-KTP Service** untuk memastikan bahwa NIK pasien valid dan terdaftar sebelum data pasien disimpan ke database RSUD.

Dengan mekanisme ini, data pasien dapat diperoleh secara otomatis berdasarkan data kependudukan yang tersimpan pada E-KTP Service sehingga mengurangi duplikasi dan kesalahan input data.

---

## Informasi Service

| Keterangan         | Detail           |
| ------------------ | ---------------- |
| Nama Service       | RSUD Service     |
| Port               | `localhost:8001` |
| Database           | `db_rsud`        |
| Bahasa Pemrograman | PHP Native       |
| Format API         | JSON             |
| Koneksi Database   | PDO              |
| Integrasi Utama    | E-KTP Service    |

---

## Database

Database yang digunakan:

```text
db_rsud
```

### Tabel Utama

| Tabel    | Fungsi                                 |
| -------- | -------------------------------------- |
| patients | Menyimpan data pasien hasil registrasi |

---

## Endpoint API

### 1. Registrasi Pasien

Endpoint ini digunakan untuk mendaftarkan pasien berdasarkan NIK. Sebelum data disimpan, RSUD Service akan memanggil E-KTP Service untuk melakukan verifikasi NIK.

#### Request

```http
POST /api/register-patient
```

#### URL

```text
http://localhost:8001/api/register-patient
```

#### Header

```http
Content-Type: application/json
```

#### Body Request

```json
{
  "nik": "3302010101010001"
}
```

#### Response Sukses

```json
{
  "success": true,
  "message": "Pasien berhasil diregistrasi berdasarkan data E-KTP.",
  "data": {
    "id": "1",
    "nik": "3302010101010001",
    "nama": "Putra Aditya Priyono",
    "jenis_pasien": "umum",
    "tarif": "Tarif Normal",
    "sumber_data": "E-KTP Service"
  }
}
```

---

## Halaman Web

| Halaman     | URL                            | Keterangan                                      |
| ----------- | ------------------------------ | ----------------------------------------------- |
| Dashboard   | http://localhost:8001          | Ringkasan layanan RSUD                          |
| Data Pasien | http://localhost:8001/patients | Menampilkan data pasien yang telah diregistrasi |

---

## Cara Menjalankan

Pastikan:

- MySQL Laragon aktif.
- Database `db_rsud` telah dibuat dan diimport.
- E-KTP Service sedang berjalan pada port `8000`.

Masuk ke folder public:

```bash
cd rsud-service/public
```

Jalankan PHP Development Server:

```bash
php -S localhost:8001
```

Buka browser:

```text
http://localhost:8001
```

---

## Cara Testing API

API dapat diuji menggunakan Postman.

### Registrasi Pasien

**Request**

```http
POST http://localhost:8001/api/register-patient
```

**Body**

```json
{
  "nik": "3302010202020002"
}
```

### Verifikasi Hasil

Jika registrasi berhasil, buka:

```text
http://localhost:8001/patients
```

Data pasien yang baru diregistrasi akan tampil pada tabel data pasien.

---

## Struktur Folder

```text
rsud-service/
├── app/
│   ├── config/
│   │   ├── app.php
│   │   └── database.php
│   ├── controllers/
│   │   └── PatientController.php
│   ├── helpers/
│   │   ├── http_client.php
│   │   ├── request.php
│   │   └── response.php
│   └── routes/
│       └── api.php
├── database/
│   └── db_rsud.sql
├── public/
│   ├── index.php
│   └── assets/
├── views/
│   ├── dashboard.php
│   ├── patients.php
│   └── layout.php
└── README.md
```

---

## Alur Integrasi

1. Pengguna mengirim NIK melalui endpoint registrasi pasien.
2. RSUD Service memanggil endpoint E-KTP Service:

```http
GET /api/verify-nik/{nik}
```

3. E-KTP Service melakukan validasi NIK.
4. Jika NIK valid, data warga dikembalikan ke RSUD Service.
5. RSUD Service menyimpan data sebagai pasien baru.
6. Response sukses dikirim ke client.

---

## Penanganan Error

### NIK Tidak Ditemukan

```json
{
  "success": false,
  "message": "NIK tidak ditemukan."
}
```

### E-KTP Service Tidak Dapat Diakses

```json
{
  "success": false,
  "message": "Gagal terhubung ke E-KTP Service."
}
```

---

## Teknologi yang Digunakan

- PHP Native
- PDO (PHP Data Objects)
- MySQL / MariaDB
- REST API JSON
- HTTP Client berbasis cURL

---

## Peran dalam Sistem Terdistribusi

RSUD Service bertanggung jawab untuk:

- Registrasi pasien berbasis NIK.
- Verifikasi identitas melalui E-KTP Service.
- Penyimpanan data pasien.
- Menyediakan data layanan kesehatan yang terintegrasi dengan sistem kependudukan.

Dengan arsitektur ini, data pasien selalu mengacu pada sumber data kependudukan yang sama sehingga konsistensi data antar layanan dapat terjaga.

---

## Author

**Putra Aditya Priyono**
UAS Komputasi Paralel dan Terdistribusi
Program Studi Informatika
