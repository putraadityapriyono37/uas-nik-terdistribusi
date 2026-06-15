CREATE DATABASE IF NOT EXISTS db_spbu;
USE db_spbu;

DROP TABLE IF EXISTS fuel_transactions;

CREATE TABLE fuel_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(16) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    status_bansos VARCHAR(50) NOT NULL DEFAULT 'tidak_terdaftar',
    jenis_bbm VARCHAR(50) NOT NULL DEFAULT 'Pertalite',
    jumlah_liter DECIMAL(10,2) NOT NULL,
    harga_per_liter DECIMAL(12,2) NOT NULL,
    total_harga DECIMAL(12,2) NOT NULL,
    kuota_sebelum DECIMAL(10,2) NOT NULL,
    kuota_sesudah DECIMAL(10,2) NOT NULL,
    keterangan VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);