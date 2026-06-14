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

    public function storeMedicalRecord()
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonResponse(false, 'Body request harus berupa JSON.', null, 400);
    }

    $nik = $input['nik'] ?? '';
    $diagnosis = $input['diagnosis'] ?? '';
    $tindakan = $input['tindakan'] ?? null;
    $obat = $input['obat'] ?? null;
    $rumahSakit = $input['rumah_sakit'] ?? 'RSUD Service';
    $tanggalPeriksa = $input['tanggal_periksa'] ?? date('Y-m-d');

    if (!$this->isValidNik($nik)) {
        $this->saveAuditLog('RSUD', '/api/medical-record', 'POST', $nik, 'failed', 'Format NIK tidak valid');
        jsonResponse(false, 'Format NIK harus 16 digit angka.', null, 400);
    }

    if (trim($diagnosis) === '') {
        $this->saveAuditLog('RSUD', '/api/medical-record', 'POST', $nik, 'failed', 'Diagnosis wajib diisi');
        jsonResponse(false, 'Diagnosis wajib diisi.', null, 400);
    }

    $stmt = $this->db->prepare("SELECT nik, nama, status_aktif FROM citizens WHERE nik = ? LIMIT 1");
    $stmt->execute([$nik]);
    $citizen = $stmt->fetch();

    if (!$citizen) {
        $this->saveAuditLog('RSUD', '/api/medical-record', 'POST', $nik, 'not_found', 'NIK tidak ditemukan');
        jsonResponse(false, 'NIK tidak ditemukan dalam database E-KTP.', null, 404);
    }

    if ($citizen['status_aktif'] !== 'aktif') {
        $this->saveAuditLog('RSUD', '/api/medical-record', 'POST', $nik, 'inactive', 'NIK nonaktif');
        jsonResponse(false, 'Data rekam medis tidak dapat disimpan karena status warga tidak aktif.', null, 403);
    }

    $stmt = $this->db->prepare("
        INSERT INTO medical_records (nik, diagnosis, tindakan, obat, rumah_sakit, tanggal_periksa)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $nik,
        $diagnosis,
        $tindakan,
        $obat,
        $rumahSakit,
        $tanggalPeriksa
    ]);

    $this->saveAuditLog('RSUD', '/api/medical-record', 'POST', $nik, 'success', 'Rekam medis berhasil disimpan');

    jsonResponse(true, 'Rekam medis berhasil dikirim ke E-KTP.', [
        'id' => $this->db->lastInsertId(),
        'nik' => $nik,
        'nama' => $citizen['nama'],
        'diagnosis' => $diagnosis,
        'tindakan' => $tindakan,
        'obat' => $obat,
        'rumah_sakit' => $rumahSakit,
        'tanggal_periksa' => $tanggalPeriksa
    ], 201);
}

    public function updateBbmQuota($nik)
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonResponse(false, 'Body request harus berupa JSON.', null, 400);
    }

    if (!$this->isValidNik($nik)) {
        $this->saveAuditLog('SPBU', '/api/bbm-quota/' . $nik, 'PUT', $nik, 'failed', 'Format NIK tidak valid');
        jsonResponse(false, 'Format NIK harus 16 digit angka.', null, 400);
    }

    if (!isset($input['kuota_bbm'])) {
        $this->saveAuditLog('SPBU', '/api/bbm-quota/' . $nik, 'PUT', $nik, 'failed', 'Kuota BBM wajib dikirim');
        jsonResponse(false, 'Kuota BBM wajib dikirim.', null, 400);
    }

    $kuotaBbm = $input['kuota_bbm'];

    if (!is_numeric($kuotaBbm)) {
        $this->saveAuditLog('SPBU', '/api/bbm-quota/' . $nik, 'PUT', $nik, 'failed', 'Kuota BBM harus berupa angka');
        jsonResponse(false, 'Kuota BBM harus berupa angka.', null, 400);
    }

    if ($kuotaBbm < 0) {
        $this->saveAuditLog('SPBU', '/api/bbm-quota/' . $nik, 'PUT', $nik, 'failed', 'Kuota BBM tidak boleh kurang dari 0');
        jsonResponse(false, 'Kuota BBM tidak boleh kurang dari 0.', null, 400);
    }

    $stmt = $this->db->prepare("SELECT nik, nama, kuota_bbm, status_aktif FROM citizens WHERE nik = ? LIMIT 1");
    $stmt->execute([$nik]);
    $citizen = $stmt->fetch();

    if (!$citizen) {
        $this->saveAuditLog('SPBU', '/api/bbm-quota/' . $nik, 'PUT', $nik, 'not_found', 'NIK tidak ditemukan');
        jsonResponse(false, 'NIK tidak ditemukan dalam database E-KTP.', null, 404);
    }

    if ($citizen['status_aktif'] !== 'aktif') {
        $this->saveAuditLog('SPBU', '/api/bbm-quota/' . $nik, 'PUT', $nik, 'inactive', 'NIK nonaktif');
        jsonResponse(false, 'Kuota BBM tidak dapat diperbarui karena status warga tidak aktif.', null, 403);
    }

    $stmt = $this->db->prepare("
        UPDATE citizens 
        SET kuota_bbm = ?
        WHERE nik = ?
    ");

    $stmt->execute([
        $kuotaBbm,
        $nik
    ]);

    $this->saveAuditLog('SPBU', '/api/bbm-quota/' . $nik, 'PUT', $nik, 'success', 'Kuota BBM berhasil diperbarui');

    jsonResponse(true, 'Kuota BBM berhasil diperbarui di E-KTP.', [
        'nik' => $nik,
        'nama' => $citizen['nama'],
        'kuota_sebelumnya' => $citizen['kuota_bbm'],
        'kuota_sekarang' => number_format((float) $kuotaBbm, 2, '.', '')
    ]);
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