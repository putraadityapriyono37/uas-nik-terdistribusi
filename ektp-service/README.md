# E-KTP Service

E-KTP Service merupakan pusat data warga pada sistem layanan publik terdistribusi berbasis NIK. Service ini digunakan oleh RSUD Service, Bansos Service, dan SPBU Service untuk memverifikasi identitas warga, membaca status ekonomi, menyimpan rekam medis, serta memperbarui kuota BBM.

## Informasi Service

| Item         | Keterangan                                     |
| ------------ | ---------------------------------------------- |
| Nama Service | E-KTP Service                                  |
| Port         | localhost:8000                                 |
| Database     | db_ektp                                        |
| Teknologi    | PHP Native, MySQL, PDO, REST API, Tailwind CSS |

## Fitur Utama

- Dashboard E-KTP
- CRUD data warga
- Tambah data warga
- Edit data warga
- Aktifkan / nonaktifkan warga
- Verifikasi NIK
- Cek status warga
- Menerima rekam medis dari RSUD
- Update kuota BBM dari SPBU
- Audit log aktivitas integrasi antar-service

## Halaman Web

| Halaman      | URL                                   |
| ------------ | ------------------------------------- |
| Dashboard    | http://localhost:8000                 |
| Data Warga   | http://localhost:8000/citizens        |
| Tambah Warga | http://localhost:8000/citizens/create |
| Rekam Medis  | http://localhost:8000/medical-records |
| Audit Log    | http://localhost:8000/audit-logs      |

## Endpoint API

### 1. Verifikasi NIK

```http
GET /api/verify-nik/{nik}
```

Contoh:

```text
http://localhost:8000/api/verify-nik/3302010101010001
```

Fungsi:

```text
Memverifikasi apakah NIK terdaftar dan aktif di database E-KTP.
```

---

### 2. Cek Status Warga

```http
GET /api/citizen-status/{nik}
```

Contoh:

```text
http://localhost:8000/api/citizen-status/3302010101010001
```

Fungsi:

```text
Mengambil status warga, status ekonomi, status aktif, dan kuota BBM.
```

Endpoint ini digunakan oleh:

```text
RSUD Service
Bansos Service
SPBU Service
```

---

### 3. Simpan Rekam Medis

```http
POST /api/medical-record
```

Contoh body:

```json
{
  "nik": "3302010101010001",
  "diagnosis": "Demam tinggi",
  "tindakan": "Pemeriksaan fisik",
  "obat": "Paracetamol",
  "rumah_sakit": "RSUD Service",
  "tanggal_periksa": "2026-06-16"
}
```

Fungsi:

```text
Menyimpan riwayat rekam medis warga yang dikirim dari RSUD Service.
```

---

### 4. Update Kuota BBM

```http
PUT /api/bbm-quota/{nik}
```

Contoh body:

```json
{
  "kuota_bbm": 25
}
```

Fungsi:

```text
Memperbarui kuota BBM warga setelah transaksi dilakukan oleh SPBU Service.
```

## Struktur Database

Database yang digunakan:

```text
db_ektp
```

Tabel utama:

```text
citizens
medical_records
audit_logs
```

## Cara Menjalankan

Jalankan perintah berikut dari terminal:

```bash
cd D:\laragon\www\uas-nik-terdistribusi\ektp-service\public
php -S localhost:8000
```

Kemudian buka:

```text
http://localhost:8000
```

## Peran dalam Sistem Terdistribusi

E-KTP Service berperan sebagai pusat data utama. Service lain bergantung pada E-KTP untuk memverifikasi NIK, membaca status warga, mengirim rekam medis, dan memperbarui kuota BBM.

## Status Implementasi

| Fitur            | Status  |
| ---------------- | ------- |
| Dashboard        | Selesai |
| CRUD warga       | Selesai |
| Verifikasi NIK   | Selesai |
| Cek status warga | Selesai |
| Rekam medis      | Selesai |
| Update kuota BBM | Selesai |
| Audit log        | Selesai |
