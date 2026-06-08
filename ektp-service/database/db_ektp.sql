CREATE DATABASE IF NOT EXISTS db_ektp;
USE db_ektp;

DROP TABLE IF EXISTS medical_records;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS citizens;

CREATE TABLE citizens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(16) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    tempat_lahir VARCHAR(100) NOT NULL,
    tanggal_lahir DATE NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    alamat TEXT NOT NULL,
    pekerjaan VARCHAR(100) DEFAULT NULL,
    status_ekonomi ENUM('mampu', 'kurang_mampu', 'rentan') NOT NULL DEFAULT 'mampu',
    kuota_bbm DECIMAL(10,2) NOT NULL DEFAULT 30.00,
    status_aktif ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(16) NOT NULL,
    diagnosis VARCHAR(255) NOT NULL,
    tindakan VARCHAR(255) DEFAULT NULL,
    obat TEXT DEFAULT NULL,
    rumah_sakit VARCHAR(100) DEFAULT NULL,
    tanggal_periksa DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    nik VARCHAR(16) DEFAULT NULL,
    status VARCHAR(50) NOT NULL,
    message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO citizens 
(nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, pekerjaan, status_ekonomi, kuota_bbm, status_aktif)
VALUES
('3302010101010001', 'Putra Aditya Priyono', 'Banyumas', '2004-01-01', 'L', 'Purwokerto, Banyumas', 'Mahasiswa', 'mampu', 30.00, 'aktif'),
('3302010202020002', 'Budi Santoso', 'Cilacap', '1988-02-02', 'L', 'Cilacap Tengah', 'Petani', 'kurang_mampu', 30.00, 'aktif'),
('3302010303030003', 'Siti Aminah', 'Purbalingga', '1992-03-03', 'P', 'Purbalingga Lor', 'Ibu Rumah Tangga', 'rentan', 30.00, 'aktif'),
('3302010404040004', 'Dewi Lestari', 'Banjarnegara', '1995-04-04', 'P', 'Banjarnegara Kota', 'Karyawan Swasta', 'mampu', 30.00, 'aktif'),
('3302010505050005', 'Ahmad Fauzi', 'Kebumen', '1985-05-05', 'L', 'Kebumen Barat', 'Buruh Harian', 'kurang_mampu', 30.00, 'aktif');