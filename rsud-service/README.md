# RSUD Service

RSUD Service merupakan service yang menangani registrasi pasien dan pengiriman rekam medis pada sistem layanan publik terdistribusi berbasis NIK. Service ini terintegrasi dengan E-KTP Service untuk verifikasi data warga dan dengan Bansos Service untuk menentukan tarif otomatis pasien.

## Informasi Service

| Item         | Keterangan                                     |
| ------------ | ---------------------------------------------- |
| Nama Service | RSUD Service                                   |
| Port         | localhost:8001                                 |
| Database     | db_rsud                                        |
| Teknologi    | PHP Native, MySQL, PDO, REST API, Tailwind CSS |

## Fitur Utama

- Dashboard RSUD
- Registrasi pasien berbasis NIK
- Verifikasi data pasien ke E-KTP Service
- Cek status bansos ke Bansos Service
- Logika tarif otomatis
- Data pasien
- Sinkronisasi ulang tarif pasien
- Hapus data pasien
- Form rekam medis digital
- Kirim rekam medis ke E-KTP Service

## Halaman Web

| Halaman           | URL                                    |
| ----------------- | -------------------------------------- |
| Dashboard         | http://localhost:8001                  |
| Data Pasien       | http://localhost:8001/patients         |
| Registrasi Pasien | http://localhost:8001/register-patient |
| Kirim Rekam Medis | http://localhost:8001/medical-record   |

## Logika Tarif Otomatis

| Kondisi                              | Jenis Pasien | Tarif        |
| ------------------------------------ | ------------ | ------------ |
| Penerima bansos aktif                | bansos       | GRATIS       |
| Status ekonomi kurang mampu / rentan | kurang_mampu | Diskon 20%   |
| Warga umum                           | umum         | Tarif Normal |

## Endpoint API

### 1. Registrasi Pasien

```http
POST /api/register-patient
```

Contoh body:

```json
{
  "nik": "3302010101010001"
}
```

Fungsi:

```text
Mendaftarkan pasien berdasarkan data warga dari E-KTP Service.
```

Alur integrasi:

```text
1. RSUD menerima input NIK.
2. RSUD memanggil E-KTP Service untuk verifikasi NIK.
3. RSUD memanggil Bansos Service untuk mengecek status bansos.
4. RSUD menentukan tarif otomatis.
5. RSUD menyimpan data pasien ke database lokal db_rsud.
```

---

### 2. Kirim Rekam Medis

```http
POST /api/medical-record
```

Contoh body:

```json
{
  "nik": "3302010101010001",
  "diagnosis": "Demam tinggi",
  "tindakan": "Pemeriksaan suhu dan tekanan darah",
  "obat": "Paracetamol 500mg",
  "tanggal_periksa": "2026-06-10"
}
```

Fungsi:

```text
Mengirim data rekam medis pasien dari RSUD Service ke E-KTP Service.
```

Contoh response sukses:

```json
{
  "success": true,
  "message": "Rekam medis berhasil dikirim dari RSUD ke E-KTP.",
  "data": {
    "nik": "3302010101010001",
    "nama": "Putra Aditya Priyono",
    "diagnosis": "Demam tinggi",
    "tindakan": "Pemeriksaan suhu dan tekanan darah",
    "obat": "Paracetamol 500mg",
    "tanggal_periksa": "2026-06-10",
    "sumber_pengiriman": "RSUD Service",
    "target_service": "E-KTP Service"
  }
}
```

Alur integrasi:

```text
1. RSUD menerima data rekam medis pasien.
2. RSUD memverifikasi NIK ke E-KTP Service.
3. Jika NIK valid, data rekam medis dikirim ke E-KTP Service.
4. E-KTP Service menyimpan data ke tabel medical_records.
5. E-KTP Service mencatat aktivitas pada audit_logs.
6. Response sukses dikembalikan ke client.
```

## Struktur Database

Database yang digunakan:

```text
db_rsud
```

Tabel utama:

```text
patients
```

## Cara Menjalankan

Jalankan perintah berikut dari terminal:

```bash
cd D:\laragon\www\uas-nik-terdistribusi\rsud-service\public
php -S localhost:8001
```

Kemudian buka:

```text
http://localhost:8001
```

## Service yang Dibutuhkan

Agar semua fitur RSUD berjalan penuh, service berikut perlu aktif:

```text
E-KTP Service  : localhost:8000
Bansos Service : localhost:8003
RSUD Service   : localhost:8001
```

## Skenario Pengujian

1. Buka halaman registrasi pasien.
2. Input NIK warga yang ada di E-KTP.
3. Sistem menampilkan data warga dan tarif otomatis.
4. Klik konfirmasi registrasi pasien.
5. Cek data pasien.
6. Buka halaman kirim rekam medis.
7. Pilih pasien, isi diagnosis, tindakan, dan obat.
8. Kirim rekam medis.
9. Cek halaman rekam medis di E-KTP Service.

## Peran dalam Sistem Terdistribusi

RSUD Service menunjukkan komunikasi antar-service karena melakukan request ke E-KTP Service dan Bansos Service sebelum menyimpan data pasien. RSUD juga mengirim data rekam medis ke E-KTP Service.

## Status Implementasi

| Fitur                      | Status  |
| -------------------------- | ------- |
| Dashboard                  | Selesai |
| Registrasi pasien          | Selesai |
| Tarif otomatis             | Selesai |
| Data pasien                | Selesai |
| Sinkron tarif              | Selesai |
| Hapus pasien               | Selesai |
| Form rekam medis           | Selesai |
| Kirim rekam medis ke E-KTP | Selesai |
