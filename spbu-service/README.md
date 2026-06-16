# SPBU Service

SPBU Service merupakan service yang menangani transaksi BBM berbasis NIK pada sistem layanan publik terdistribusi. Service ini terintegrasi dengan E-KTP Service untuk mengecek data warga dan memperbarui kuota BBM, serta terintegrasi dengan Bansos Service untuk menentukan harga BBM secara otomatis.

## Informasi Service

| Item         | Keterangan                                     |
| ------------ | ---------------------------------------------- |
| Nama Service | SPBU Service                                   |
| Port         | localhost:8002                                 |
| Database     | db_spbu                                        |
| Teknologi    | PHP Native, MySQL, PDO, REST API, Tailwind CSS |

## Fitur Utama

- Dashboard SPBU
- Transaksi BBM berbasis NIK
- Cek status warga ke E-KTP Service
- Cek status bansos ke Bansos Service
- Hitung harga BBM otomatis
- Update kuota BBM ke E-KTP Service
- Data transaksi BBM
- Edit keterangan transaksi
- Hapus riwayat transaksi lokal

## Halaman Web

| Halaman        | URL                                    |
| -------------- | -------------------------------------- |
| Dashboard      | http://localhost:8002                  |
| Data Transaksi | http://localhost:8002/transactions     |
| Transaksi BBM  | http://localhost:8002/fuel-transaction |

## Logika Harga BBM

| Kondisi                              | Harga per Liter |
| ------------------------------------ | --------------: |
| Penerima bansos aktif                |        Rp 9.000 |
| Status ekonomi kurang mampu / rentan |       Rp 10.000 |
| Warga umum                           |       Rp 13.000 |

## Catatan CRUD Transaksi

Pada SPBU Service, fitur update hanya digunakan untuk mengubah keterangan transaksi. Fitur hapus hanya menghapus riwayat transaksi lokal pada database SPBU dan tidak mengembalikan kuota BBM pada E-KTP Service. Hal ini dilakukan agar data kuota tetap konsisten.

## Endpoint API

### 1. Transaksi BBM

```http
POST /api/fuel-transaction
```

Contoh body:

```json
{
  "nik": "3302010202020002",
  "jenis_bbm": "Pertalite",
  "jumlah_liter": 5,
  "keterangan": "Transaksi loket 1"
}
```

Fungsi:

```text
Memproses transaksi BBM berbasis NIK, menghitung harga otomatis, mengurangi kuota BBM, dan menyimpan transaksi ke database SPBU.
```

Alur integrasi:

```text
1. SPBU menerima input NIK, jenis BBM, dan jumlah liter.
2. SPBU memanggil E-KTP Service untuk membaca status warga dan kuota BBM.
3. SPBU memanggil Bansos Service untuk membaca status bansos.
4. Sistem menentukan harga per liter secara otomatis.
5. Sistem menghitung total harga transaksi.
6. SPBU mengirim update kuota BBM ke E-KTP Service.
7. SPBU menyimpan transaksi ke database lokal db_spbu.
```

### Contoh Response Sukses

```json
{
  "success": true,
  "message": "Transaksi berhasil diproses.",
  "data": {
    "nik": "3302010202020002",
    "nama": "Budi Santoso",
    "jenis_bbm": "Pertalite",
    "jumlah_liter": 5,
    "harga_per_liter": 9000,
    "total_harga": 45000,
    "kuota_sebelum": 30,
    "kuota_sesudah": 25,
    "status_bansos": true
  }
}
```

## Struktur Database

Database yang digunakan:

```text
db_spbu
```

Tabel utama:

```text
fuel_transactions
```

Kolom penting:

```text
nik
nama
status_bansos
jenis_bbm
jumlah_liter
harga_per_liter
total_harga
kuota_sebelum
kuota_sesudah
keterangan
created_at
```

## Cara Menjalankan

Jalankan perintah berikut dari terminal:

```bash
cd D:\laragon\www\uas-nik-terdistribusi\spbu-service\public
php -S localhost:8002
```

Kemudian buka:

```text
http://localhost:8002
```

## Service yang Dibutuhkan

Agar transaksi BBM berjalan penuh, service berikut perlu aktif:

```text
E-KTP Service   : localhost:8000
Bansos Service  : localhost:8003
SPBU Service    : localhost:8002
```

## Skenario Pengujian

1. Buka halaman transaksi BBM.
2. Input NIK warga yang ada di E-KTP.
3. Input jenis BBM dan jumlah liter.
4. Klik cek transaksi.
5. Sistem menampilkan status ekonomi, status bansos, harga per liter, total harga, kuota sebelum, dan kuota sesudah.
6. Klik konfirmasi transaksi.
7. Cek data transaksi pada halaman Data Transaksi.
8. Cek data warga pada E-KTP Service.
9. Pastikan kuota BBM warga berkurang.
10. Coba edit keterangan transaksi.
11. Coba hapus riwayat transaksi lokal.

## Peran dalam Sistem Terdistribusi

SPBU Service menunjukkan integrasi antar-service karena memanggil dua service lain dalam satu proses transaksi, yaitu E-KTP Service dan Bansos Service. Hasil transaksi kemudian disimpan ke database lokal SPBU, sementara kuota BBM diperbarui pada E-KTP Service.

## Status Implementasi

| Fitur                     | Status  |
| ------------------------- | ------- |
| Dashboard                 | Selesai |
| Form transaksi BBM        | Selesai |
| Cek status warga ke E-KTP | Selesai |
| Cek status bansos         | Selesai |
| Hitung harga otomatis     | Selesai |
| Update kuota E-KTP        | Selesai |
| Data transaksi            | Selesai |
| Edit keterangan           | Selesai |
| Hapus transaksi lokal     | Selesai |
