<?php

class PatientController
{
    private $db;
    private $app;

    public function __construct()
    {
        $this->db = getDatabaseConnection();
        $this->app = require __DIR__ . '/../config/app.php';
    }

    public function registerPatient()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            jsonResponse(false, 'Body request harus berupa JSON.', null, 400);
        }

        $nik = $input['nik'] ?? '';

        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            jsonResponse(false, 'Format NIK harus 16 digit angka.', null, 400);
        }

        $ektpUrl = $this->app['ektp_base_url'] . '/api/verify-nik/' . $nik;
        $ektpResponse = sendGetRequest($ektpUrl);

        if (!$ektpResponse['success']) {
            jsonResponse(false, 'Gagal menghubungi E-KTP Service.', [
                'target_service' => 'E-KTP',
                'url' => $ektpUrl,
                'response' => $ektpResponse
            ], 502);
        }

        $ektpData = $ektpResponse['data'];

        if (!$ektpData || !$ektpData['success']) {
            jsonResponse(false, 'NIK tidak valid berdasarkan data E-KTP.', $ektpData, 404);
        }

        $citizen = $ektpData['data'];

        $jenisPasien = 'umum';
        $tarif = 'Tarif Normal';

        $stmt = $this->db->prepare("
            INSERT INTO patients 
            (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, pekerjaan, jenis_pasien, tarif)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $citizen['nik'],
            $citizen['nama'],
            $citizen['tempat_lahir'] ?? null,
            $citizen['tanggal_lahir'] ?? null,
            $citizen['jenis_kelamin'] ?? null,
            $citizen['alamat'] ?? null,
            $citizen['pekerjaan'] ?? null,
            $jenisPasien,
            $tarif
        ]);

        jsonResponse(true, 'Pasien berhasil diregistrasi berdasarkan data E-KTP.', [
            'id' => $this->db->lastInsertId(),
            'nik' => $citizen['nik'],
            'nama' => $citizen['nama'],
            'jenis_pasien' => $jenisPasien,
            'tarif' => $tarif,
            'sumber_data' => 'E-KTP Service'
        ], 201);
    }
}