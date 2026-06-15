# SPBU Service

SPBU Service adalah layanan transaksi BBM berbasis verifikasi NIK, status bansos, dan kuota BBM. Service ini berkomunikasi dengan E-KTP Service dan Bansos Service untuk menentukan validitas transaksi serta harga BBM.

## Informasi Service

| Keterangan       | Detail                           |
| ---------------- | -------------------------------- |
| Nama Service     | SPBU Service                     |
| Port             | `localhost:8002`                 |
| Database         | `db_spbu`                        |
| Bahasa           | PHP Native                       |
| Format API       | JSON                             |
| Koneksi Database | PDO                              |
| Integrasi Utama  | E-KTP Service dan Bansos Service |

## Database

Database yang digunakan:

```text
db_spbu

Tabel utama:

Tabel	Fungsi
fuel_transactions	Menyimpan riwayat transaksi BBM
Endpoint API
1. Transaksi BBM

Endpoint ini digunakan untuk memproses transaksi BBM berdasarkan NIK. SPBU akan memverifikasi status warga ke E-KTP, mengecek status bansos ke Bansos, lalu mengupdate kuota BBM ke E-KTP.

POST /api/fuel-transaction

Contoh request:

http://localhost:8002/api/fuel-transaction

Header:

Content-Type: application/json

Body request:

{
  "nik": "3302010202020002",
  "jenis_bbm": "Pertalite",
  "jumlah_liter": 5
}

Contoh response sukses:

{
  "success": true,
  "message": "Transaksi BBM berhasil diproses.",
  "data": {
    "id": "1",
    "nik": "3302010202020002",
    "nama": "Budi Santoso",
    "status_ekonomi": "kurang_mampu",
    "status_bansos": "aktif",
    "jenis_bbm": "Pertalite",
    "jumlah_liter": "5.00",
    "harga_per_liter": "9000.00",
    "total_harga": "45000.00",
    "kuota_sebelum": "30.00",
    "kuota_sesudah": "25.00"
  }
}
Halaman Web
Halaman	URL	Keterangan
Dashboard	http://localhost:8002	Ringkasan layanan SPBU
Data Transaksi	http://localhost:8002/transactions	Menampilkan data transaksi BBM
Cara Menjalankan

Pastikan database db_spbu sudah dibuat dan MySQL Laragon aktif.

cd spbu-service/public
php -S localhost:8002

Buka dashboard di browser:

http://localhost:8002
Cara Testing API

API dapat dites menggunakan Postman.

Method	Endpoint	Keterangan
POST	/api/fuel-transaction	Proses transaksi BBM
Struktur Folder
spbu-service/
├── app/
│   ├── config/
│   │   ├── app.php
│   │   └── database.php
│   ├── controllers/
│   │   └── FuelTransactionController.php
│   ├── helpers/
│   │   ├── http_client.php
│   │   ├── request.php
│   │   └── response.php
│   └── routes/
│       └── api.php
├── database/
│   └── db_spbu.sql
├── public/
│   ├── index.php
│   └── assets/
├── views/
│   ├── dashboard.php
│   ├── transactions.php
│   └── layout.php
└── README.md
Catatan Integrasi
- SPBU Service memanggil E-KTP Service melalui endpoint GET /api/citizen-status/{nik}.
- SPBU Service memanggil Bansos Service melalui endpoint GET /api/bansos-status/{nik}.
- SPBU Service mengupdate kuota BBM ke E-KTP melalui endpoint PUT /api/bbm-quota/{nik}.
- Jika penerima bansos aktif, harga BBM menjadi lebih murah.
- Jika status ekonomi warga kurang mampu atau rentan, warga mendapatkan harga subsidi.
- Jika warga umum, harga yang digunakan adalah harga non-subsidi.
```
