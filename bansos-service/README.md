# Bansos Service

Bansos Service merupakan service yang mengelola data penerima bantuan sosial pada sistem layanan publik terdistribusi berbasis NIK. Service ini terintegrasi dengan E-KTP Service untuk mengecek status ekonomi warga dan menyediakan endpoint status bansos untuk digunakan oleh RSUD Service dan SPBU Service.

## Informasi Service

| Item         | Keterangan                                     |
| ------------ | ---------------------------------------------- |
| Nama Service | Bansos Service                                 |
| Port         | localhost:8003                                 |
| Database     | db_bansos                                      |
| Teknologi    | PHP Native, MySQL, PDO, REST API, Tailwind CSS |

## Fitur Utama

- Dashboard Bansos
- Registrasi penerima bansos
- Validasi status ekonomi ke E-KTP Service
- Data penerima bansos
- Aktifkan penerima
- Nonaktifkan penerima
- Hapus penerima
- Endpoint status bansos untuk RSUD dan SPBU

## Halaman Web

| Halaman             | URL                                      |
| ------------------- | ---------------------------------------- |
| Dashboard           | http://localhost:8003                    |
| Data Penerima       | http://localhost:8003/recipients         |
| Registrasi Penerima | http://localhost:8003/register-recipient |

## Aturan Kelayakan

| Status Ekonomi dari E-KTP | Kelayakan                   |
| ------------------------- | --------------------------- |
| kurang_mampu              | Layak menerima bansos       |
| rentan                    | Layak menerima bansos       |
| mampu                     | Tidak layak menerima bansos |

## Endpoint API

### 1. Registrasi Penerima Bansos

```http
POST /api/register-recipient
```

Contoh body:

```json
{
  "nik": "3302010202020002",
  "jenis_bantuan": "Bantuan Sosial Pokok"
}
```

Fungsi:

```text
Mendaftarkan warga sebagai penerima bansos setelah status ekonominya divalidasi ke E-KTP Service.
```

Alur integrasi:

```text
1. Bansos menerima input NIK.
2. Bansos memanggil E-KTP Service untuk membaca status ekonomi warga.
3. Sistem mengecek apakah warga layak menerima bansos.
4. Jika layak, data penerima disimpan ke database db_bansos.
```

---

### 2. Cek Status Bansos

```http
GET /api/bansos-status/{nik}
```

Contoh:

```text
http://localhost:8003/api/bansos-status/3302010202020002
```

Fungsi:

```text
Memberikan informasi apakah NIK tertentu merupakan penerima bansos aktif.
```

Contoh response sukses:

```json
{
  "success": true,
  "data": {
    "nik": "3302010202020002",
    "status_bansos": true,
    "jenis_bantuan": "Bantuan Sosial Pokok",
    "status": "aktif"
  }
}
```

Endpoint ini digunakan oleh:

```text
RSUD Service untuk menentukan tarif pasien.
SPBU Service untuk menentukan harga BBM.
```

## Struktur Database

Database yang digunakan:

```text
db_bansos
```

Tabel utama:

```text
recipients
```

## Cara Menjalankan

Jalankan perintah berikut dari terminal:

```bash
cd D:\laragon\www\uas-nik-terdistribusi\bansos-service\public
php -S localhost:8003
```

Kemudian buka:

```text
http://localhost:8003
```

## Service yang Dibutuhkan

Agar registrasi penerima bansos berjalan penuh, service berikut perlu aktif:

```text
E-KTP Service  : localhost:8000
Bansos Service : localhost:8003
```

## Skenario Pengujian

1. Buka halaman registrasi penerima.
2. Input NIK warga yang ada di E-KTP.
3. Sistem mengambil status ekonomi dari E-KTP.
4. Jika status ekonomi `kurang_mampu` atau `rentan`, warga dapat diregistrasi.
5. Cek data penerima.
6. Coba nonaktifkan penerima.
7. Coba aktifkan kembali penerima.
8. Coba hapus penerima.
9. Cek endpoint status bansos melalui browser atau Postman.

## Peran dalam Sistem Terdistribusi

Bansos Service menunjukkan konsep service mandiri karena memiliki database sendiri dan menyediakan data status bansos untuk digunakan oleh service lain, yaitu RSUD Service dan SPBU Service.

## Status Implementasi

| Fitur                   | Status  |
| ----------------------- | ------- |
| Dashboard               | Selesai |
| Registrasi penerima     | Selesai |
| Validasi ke E-KTP       | Selesai |
| Data penerima           | Selesai |
| Aktif/nonaktif penerima | Selesai |
| Hapus penerima          | Selesai |
| Endpoint status bansos  | Selesai |
