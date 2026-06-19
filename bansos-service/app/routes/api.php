<?php

$path = getRequestPath();
$method = getRequestMethod();

$recipientController = new RecipientController();

if ($method === 'POST' && $path === '/api/register-recipient') {
    $recipientController->registerRecipient();
    return;
}

if ($method === 'GET' && preg_match('#^/api/bansos-status/([0-9]{16})$#', $path, $matches)) {
    $recipientController->getBansosStatus($matches[1]);
    return;
}

if ($method === 'PUT' && preg_match('#^/api/deactivate-recipient/([0-9]{16})$#', $path, $matches)) {
    $nik = $matches[1];

    $db = getDatabaseConnection();

    // Ambil satu data penerima berdasarkan NIK
    $stmt = $db->prepare("
        SELECT id, nik, nama, status_bansos
        FROM recipients
        WHERE nik = ?
        LIMIT 1
    ");
    $stmt->execute([$nik]);
    $recipient = $stmt->fetch();

    if (!$recipient) {
        jsonResponse(
            false,
            'Penerima bansos dengan NIK tersebut tidak ditemukan.',
            [
                'nik' => $nik
            ],
            404
        );
        return;
    }

    if ($recipient['status_bansos'] === 'nonaktif') {
        jsonResponse(
            true,
            'Penerima bansos sudah dalam status nonaktif. Tidak ada data yang diubah.',
            [
                'id' => $recipient['id'],
                'nik' => $recipient['nik'],
                'nama' => $recipient['nama'],
                'status_bansos' => 'nonaktif',
                'affected_rows' => 0
            ],
            200
        );
        return;
    }

    // Update berdasarkan ID yang sudah ditemukan agar hanya satu baris yang berubah
    $update = $db->prepare("
        UPDATE recipients
        SET status_bansos = 'nonaktif'
        WHERE id = ?
        LIMIT 1
    ");
    $update->execute([$recipient['id']]);

    jsonResponse(
        true,
        'Status penerima bansos berhasil dinonaktifkan untuk satu data penerima saja.',
        [
            'id' => $recipient['id'],
            'nik' => $recipient['nik'],
            'nama' => $recipient['nama'],
            'status_bansos_sebelumnya' => $recipient['status_bansos'],
            'status_bansos_sekarang' => 'nonaktif',
            'affected_rows' => $update->rowCount()
        ],
        200
    );
    return;
}

jsonResponse(false, 'Endpoint tidak ditemukan.', null, 404);