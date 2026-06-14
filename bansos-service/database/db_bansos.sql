CREATE DATABASE IF NOT EXISTS db_bansos;
USE db_bansos;

DROP TABLE IF EXISTS recipients;

CREATE TABLE recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(16) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    status_ekonomi ENUM('mampu', 'kurang_mampu', 'rentan') NOT NULL,
    jenis_bantuan VARCHAR(100) NOT NULL DEFAULT 'Bantuan Sosial Pokok',
    periode_bantuan VARCHAR(50) NOT NULL DEFAULT '2026',
    status_bansos ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    keterangan TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);