# E-KTP Service

E-KTP Service adalah pusat data warga dalam sistem integrasi layanan publik berbasis NIK. Service ini berperan sebagai hub utama yang menyediakan data identitas warga untuk digunakan oleh service lain seperti RSUD, Bansos, dan SPBU.

## Port

Service ini berjalan pada port:

```bash
localhost:8000
```

## Database

Database yang digunakan:

```text
db_ektp
```

## Endpoint

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

## Halaman Web

| Halaman     | URL                                     | Keterangan                             |
| ----------- | --------------------------------------- | -------------------------------------- |
| Dashboard   | `http://localhost:8000`                 | Ringkasan layanan E-KTP                |
| Data Warga  | `http://localhost:8000/citizens`        | Menampilkan data master warga          |
| Rekam Medis | `http://localhost:8000/medical-records` | Menampilkan data rekam medis dari RSUD |

## Cara Menjalankan

Masuk ke folder public E-KTP Service:

```bash
cd ektp-service/public
```

Jalankan server PHP:

```bash
php -S localhost:8000
```

Buka dashboard di browser:

```text
http://localhost:8000
```

## Struktur Folder

```text
ektp-service/
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

## Catatan

- Service ini menggunakan PHP Native.
- Koneksi database menggunakan PDO.
- Response API menggunakan format JSON.
- Service ini menjadi pusat verifikasi NIK untuk integrasi antar layanan.
