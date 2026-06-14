CREATE DATABASE IF NOT EXISTS db_rsud;
USE db_rsud;

DROP TABLE IF EXISTS patients;

CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(16) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    tempat_lahir VARCHAR(100) DEFAULT NULL,
    tanggal_lahir DATE DEFAULT NULL,
    jenis_kelamin ENUM('L', 'P') DEFAULT NULL,
    alamat TEXT DEFAULT NULL,
    pekerjaan VARCHAR(100) DEFAULT NULL,
    jenis_pasien ENUM('umum', 'kurang_mampu', 'bansos') NOT NULL DEFAULT 'umum',
    tarif VARCHAR(50) NOT NULL DEFAULT 'Tarif Normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);