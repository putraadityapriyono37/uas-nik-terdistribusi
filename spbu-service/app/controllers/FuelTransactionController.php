<?php

class FuelTransactionController
{
    private $db;
    private $app;

    public function __construct()
    {
        $this->db = getDatabaseConnection();
        $this->app = require __DIR__ . '/../config/app.php';
    }

    public function createTransaction()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            jsonResponse(false, 'Body request harus berupa JSON.', null, 400);
        }

        $nik = $input['nik'] ?? '';
        $jenisBbm = $input['jenis_bbm'] ?? 'Pertalite';
        $jumlahLiter = $input['jumlah_liter'] ?? 0;

        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            jsonResponse(false, 'Format NIK harus 16 digit angka.', null, 400);
        }

        if (!is_numeric($jumlahLiter) || $jumlahLiter <= 0) {
            jsonResponse(false, 'Jumlah liter harus berupa angka dan lebih dari 0.', null, 400);
        }

        $jumlahLiter = (float) $jumlahLiter;

        $ektpVerifyUrl = $this->app['ektp_base_url'] . '/api/citizen-status/' . $nik;
        $ektpResponse = sendGetRequest($ektpVerifyUrl);

        if (!$ektpResponse['success']) {
            jsonResponse(false, 'Gagal menghubungi E-KTP Service.', [
                'target_service' => 'E-KTP',
                'url' => $ektpVerifyUrl,
                'response' => $ektpResponse
            ], 502);
        }

        $ektpData = $ektpResponse['data'];

        if (!$ektpData || !$ektpData['success']) {
            jsonResponse(false, 'Transaksi ditolak karena NIK tidak valid berdasarkan E-KTP.', $ektpData, 404);
        }

        $citizen = $ektpData['data'];
        $kuotaSebelum = (float) $citizen['kuota_bbm'];

        if ($citizen['status_aktif'] !== 'aktif') {
            jsonResponse(false, 'Transaksi ditolak karena status warga tidak aktif.', $citizen, 403);
        }

        if ($kuotaSebelum < $jumlahLiter) {
            jsonResponse(false, 'Transaksi ditolak karena kuota BBM tidak mencukupi.', [
                'nik' => $nik,
                'nama' => $citizen['nama'],
                'kuota_tersedia' => number_format($kuotaSebelum, 2, '.', ''),
                'jumlah_diminta' => number_format($jumlahLiter, 2, '.', '')
            ], 422);
        }

        $bansosUrl = $this->app['bansos_base_url'] . '/api/bansos-status/' . $nik;
        $bansosResponse = sendGetRequest($bansosUrl);

        $statusBansos = 'tidak_terdaftar';

        if ($bansosResponse['success'] && isset($bansosResponse['data']['data'])) {
            $bansosData = $bansosResponse['data']['data'];
            $statusBansos = $bansosData['status_bansos'] ?? 'tidak_terdaftar';
        }

        $statusEkonomi = $citizen['status_ekonomi'];
        $hargaPerLiter = $this->determinePrice($statusEkonomi, $statusBansos);
        $totalHarga = $hargaPerLiter * $jumlahLiter;
        $kuotaSesudah = $kuotaSebelum - $jumlahLiter;

        $ektpUpdateQuotaUrl = $this->app['ektp_base_url'] . '/api/bbm-quota/' . $nik;
        $updateQuotaResponse = sendPutRequest($ektpUpdateQuotaUrl, [
            'kuota_bbm' => $kuotaSesudah
        ]);

        if (!$updateQuotaResponse['success']) {
            jsonResponse(false, 'Gagal mengupdate kuota BBM ke E-KTP Service.', [
                'target_service' => 'E-KTP',
                'url' => $ektpUpdateQuotaUrl,
                'response' => $updateQuotaResponse
            ], 502);
        }

        $stmt = $this->db->prepare("
            INSERT INTO fuel_transactions 
            (nik, nama, status_bansos, jenis_bbm, jumlah_liter, harga_per_liter, total_harga, kuota_sebelum, kuota_sesudah, keterangan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $keterangan = $this->buildTransactionNote($statusEkonomi, $statusBansos);

        $stmt->execute([
            $citizen['nik'],
            $citizen['nama'],
            $statusBansos,
            $jenisBbm,
            $jumlahLiter,
            $hargaPerLiter,
            $totalHarga,
            $kuotaSebelum,
            $kuotaSesudah,
            $keterangan
        ]);

        jsonResponse(true, 'Transaksi BBM berhasil diproses.', [
            'id' => $this->db->lastInsertId(),
            'nik' => $citizen['nik'],
            'nama' => $citizen['nama'],
            'status_ekonomi' => $statusEkonomi,
            'status_bansos' => $statusBansos,
            'jenis_bbm' => $jenisBbm,
            'jumlah_liter' => number_format($jumlahLiter, 2, '.', ''),
            'harga_per_liter' => number_format($hargaPerLiter, 2, '.', ''),
            'total_harga' => number_format($totalHarga, 2, '.', ''),
            'kuota_sebelum' => number_format($kuotaSebelum, 2, '.', ''),
            'kuota_sesudah' => number_format($kuotaSesudah, 2, '.', ''),
            'update_ektp' => $updateQuotaResponse['data']
        ], 201);
    }

    private function determinePrice($statusEkonomi, $statusBansos)
    {
        if ($statusBansos === 'aktif') {
            return 9000;
        }

        if ($statusEkonomi === 'kurang_mampu' || $statusEkonomi === 'rentan') {
            return 10000;
        }

        return 13000;
    }

    private function buildTransactionNote($statusEkonomi, $statusBansos)
    {
        if ($statusBansos === 'aktif') {
            return 'Penerima bansos aktif mendapatkan harga subsidi dan diskon.';
        }

        if ($statusEkonomi === 'kurang_mampu' || $statusEkonomi === 'rentan') {
            return 'Warga kurang mampu/rentan mendapatkan harga subsidi.';
        }

        return 'Warga umum mendapatkan harga non-subsidi.';
    }
}