<?php

class CitizenController
{
    private $db;

    public function __construct()
    {
        $this->db = getDatabaseConnection();
    }

    public function verifyNik($nik)
    {
        if (!$this->isValidNik($nik)) {
            $this->saveAuditLog('E-KTP', '/api/verify-nik/' . $nik, 'GET', $nik, 'failed', 'Format NIK tidak valid');
            jsonResponse(false, 'Format NIK harus 16 digit angka.', null, 400);
        }

        $stmt = $this->db->prepare("SELECT nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, pekerjaan, status_aktif FROM citizens WHERE nik = ? LIMIT 1");
        $stmt->execute([$nik]);
        $citizen = $stmt->fetch();

        if (!$citizen) {
            $this->saveAuditLog('E-KTP', '/api/verify-nik/' . $nik, 'GET', $nik, 'not_found', 'NIK tidak ditemukan');
            jsonResponse(false, 'NIK tidak ditemukan dalam database E-KTP.', null, 404);
        }

        if ($citizen['status_aktif'] !== 'aktif') {
            $this->saveAuditLog('E-KTP', '/api/verify-nik/' . $nik, 'GET', $nik, 'inactive', 'NIK nonaktif');
            jsonResponse(false, 'NIK ditemukan, tetapi status warga tidak aktif.', $citizen, 403);
        }

        $this->saveAuditLog('E-KTP', '/api/verify-nik/' . $nik, 'GET', $nik, 'success', 'NIK berhasil diverifikasi');

        jsonResponse(true, 'NIK valid dan terdaftar di E-KTP.', $citizen);
    }

    public function citizenStatus($nik)
    {
        if (!$this->isValidNik($nik)) {
            $this->saveAuditLog('E-KTP', '/api/citizen-status/' . $nik, 'GET', $nik, 'failed', 'Format NIK tidak valid');
            jsonResponse(false, 'Format NIK harus 16 digit angka.', null, 400);
        }

        $stmt = $this->db->prepare("SELECT nik, nama, status_ekonomi, kuota_bbm, status_aktif FROM citizens WHERE nik = ? LIMIT 1");
        $stmt->execute([$nik]);
        $citizen = $stmt->fetch();

        if (!$citizen) {
            $this->saveAuditLog('E-KTP', '/api/citizen-status/' . $nik, 'GET', $nik, 'not_found', 'NIK tidak ditemukan');
            jsonResponse(false, 'Status warga tidak ditemukan karena NIK tidak terdaftar.', null, 404);
        }

        $this->saveAuditLog('E-KTP', '/api/citizen-status/' . $nik, 'GET', $nik, 'success', 'Status warga berhasil diambil');

        jsonResponse(true, 'Status warga berhasil ditemukan.', $citizen);
    }

    private function isValidNik($nik)
    {
        return preg_match('/^[0-9]{16}$/', $nik);
    }

    private function saveAuditLog($serviceName, $endpoint, $method, $nik, $status, $message)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (service_name, endpoint, method, nik, status, message)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $serviceName,
                $endpoint,
                $method,
                $nik,
                $status,
                $message
            ]);
        } catch (Exception $error) {
            // Audit log tidak boleh menghentikan response utama.
        }
    }
}