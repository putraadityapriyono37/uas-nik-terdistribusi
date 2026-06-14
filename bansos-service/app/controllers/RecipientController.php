<?php

class RecipientController
{
    private $db;
    private $app;

    public function __construct()
    {
        $this->db = getDatabaseConnection();
        $this->app = require __DIR__ . '/../config/app.php';
    }

    public function registerRecipient()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            jsonResponse(false, 'Body request harus berupa JSON.', null, 400);
        }

        $nik = $input['nik'] ?? '';
        $jenisBantuan = $input['jenis_bantuan'] ?? 'Bantuan Sosial Pokok';
        $periodeBantuan = $input['periode_bantuan'] ?? '2026';
        $keterangan = $input['keterangan'] ?? null;

        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            jsonResponse(false, 'Format NIK harus 16 digit angka.', null, 400);
        }

        $ektpUrl = $this->app['ektp_base_url'] . '/api/citizen-status/' . $nik;
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
        $statusEkonomi = $citizen['status_ekonomi'];

        if ($citizen['status_aktif'] !== 'aktif') {
            jsonResponse(false, 'Warga tidak dapat didaftarkan karena status NIK tidak aktif.', $citizen, 403);
        }

        if (!in_array($statusEkonomi, ['kurang_mampu', 'rentan'])) {
            jsonResponse(false, 'Warga tidak memenuhi kriteria penerima bansos.', [
                'nik' => $citizen['nik'],
                'nama' => $citizen['nama'],
                'status_ekonomi' => $statusEkonomi,
                'alasan' => 'Hanya warga kurang mampu atau rentan yang dapat didaftarkan.'
            ], 422);
        }

        $stmt = $this->db->prepare("SELECT id FROM recipients WHERE nik = ? LIMIT 1");
        $stmt->execute([$nik]);
        $existing = $stmt->fetch();

        if ($existing) {
            jsonResponse(false, 'NIK sudah terdaftar sebagai penerima bansos.', [
                'nik' => $nik
            ], 409);
        }

        $stmt = $this->db->prepare("
            INSERT INTO recipients 
            (nik, nama, status_ekonomi, jenis_bantuan, periode_bantuan, status_bansos, keterangan)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $citizen['nik'],
            $citizen['nama'],
            $statusEkonomi,
            $jenisBantuan,
            $periodeBantuan,
            'aktif',
            $keterangan
        ]);

        jsonResponse(true, 'Penerima bansos berhasil didaftarkan berdasarkan data E-KTP.', [
            'id' => $this->db->lastInsertId(),
            'nik' => $citizen['nik'],
            'nama' => $citizen['nama'],
            'status_ekonomi' => $statusEkonomi,
            'jenis_bantuan' => $jenisBantuan,
            'periode_bantuan' => $periodeBantuan,
            'status_bansos' => 'aktif',
            'sumber_data' => 'E-KTP Service'
        ], 201);
    }

    public function getBansosStatus($nik)
    {
        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            jsonResponse(false, 'Format NIK harus 16 digit angka.', null, 400);
        }

        $stmt = $this->db->prepare("
            SELECT 
                nik,
                nama,
                status_ekonomi,
                jenis_bantuan,
                periode_bantuan,
                status_bansos
            FROM recipients 
            WHERE nik = ? 
            LIMIT 1
        ");

        $stmt->execute([$nik]);
        $recipient = $stmt->fetch();

        if (!$recipient) {
            jsonResponse(false, 'NIK tidak terdaftar sebagai penerima bansos.', [
                'nik' => $nik,
                'status_bansos' => 'tidak_terdaftar'
            ], 404);
        }

        jsonResponse(true, 'Status bansos berhasil ditemukan.', $recipient);
    }
}