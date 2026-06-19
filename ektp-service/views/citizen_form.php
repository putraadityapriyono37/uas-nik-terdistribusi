<?php
$db = getDatabaseConnection();

$isEdit = getRequestPath() === '/citizens/edit';
$error = null;
$success = null;
$warning = null;

$app = require __DIR__ . '/../app/config/app.php';

$citizen = [
    'nik' => '',
    'nama' => '',
    'tempat_lahir' => '',
    'tanggal_lahir' => '',
    'jenis_kelamin' => 'L',
    'alamat' => '',
    'pekerjaan' => '',
    'status_ekonomi' => 'mampu',
    'kuota_bbm' => '30.00',
    'status_aktif' => 'aktif'
];

/**
 * Fungsi untuk menonaktifkan penerima bansos dari E-KTP Service.
 * Dipanggil ketika status ekonomi warga berubah dari kurang_mampu/rentan menjadi mampu.
 */
function deactivateBansosRecipient($bansosBaseUrl, $nik)
{
    if (empty($bansosBaseUrl)) {
        return [
            'success' => false,
            'message' => 'URL Bansos Service belum dikonfigurasi pada E-KTP Service.'
        ];
    }

    $url = rtrim($bansosBaseUrl, '/') . '/api/deactivate-recipient/' . urlencode($nik);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($response === false || !empty($curlError)) {
        return [
            'success' => false,
            'message' => 'Gagal menghubungi Bansos Service: ' . $curlError
        ];
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return [
            'success' => false,
            'message' => 'Response dari Bansos Service tidak valid.'
        ];
    }

    if ($httpCode >= 200 && $httpCode < 300 && !empty($decoded['success'])) {
        return [
            'success' => true,
            'message' => $decoded['message'] ?? 'Status bansos berhasil dinonaktifkan.'
        ];
    }

    return [
        'success' => false,
        'message' => $decoded['message'] ?? 'Gagal menonaktifkan status bansos.'
    ];
}

// Ambil data warga ketika mode edit
if ($isEdit) {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        $error = 'ID warga tidak ditemukan.';
    } else {
        $stmt = $db->prepare("SELECT * FROM citizens WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $existingCitizen = $stmt->fetch();

        if (!$existingCitizen) {
            $error = 'Data warga tidak ditemukan.';
        } else {
            $citizen = $existingCitizen;
        }
    }
}

// Proses simpan data create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = $_POST['nik'] ?? '';
    $nama = $_POST['nama'] ?? '';
    $tempatLahir = $_POST['tempat_lahir'] ?? '';
    $tanggalLahir = $_POST['tanggal_lahir'] ?? '';
    $jenisKelamin = $_POST['jenis_kelamin'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $pekerjaan = $_POST['pekerjaan'] ?? '';
    $statusEkonomi = $_POST['status_ekonomi'] ?? '';
    $kuotaBbm = $_POST['kuota_bbm'] ?? 30;
    $statusAktif = $_POST['status_aktif'] ?? 'aktif';

    $oldStatusEkonomi = $citizen['status_ekonomi'] ?? null;

    $citizen = [
        'nik' => $nik,
        'nama' => $nama,
        'tempat_lahir' => $tempatLahir,
        'tanggal_lahir' => $tanggalLahir,
        'jenis_kelamin' => $jenisKelamin,
        'alamat' => $alamat,
        'pekerjaan' => $pekerjaan,
        'status_ekonomi' => $statusEkonomi,
        'kuota_bbm' => $kuotaBbm,
        'status_aktif' => $statusAktif
    ];

    if (!preg_match('/^[0-9]{16}$/', $nik)) {
        $error = 'Format NIK harus 16 digit angka.';
    } elseif (trim($nama) === '') {
        $error = 'Nama warga wajib diisi.';
    } elseif (trim($tempatLahir) === '') {
        $error = 'Tempat lahir wajib diisi.';
    } elseif (trim($tanggalLahir) === '') {
        $error = 'Tanggal lahir wajib diisi.';
    } elseif (!in_array($jenisKelamin, ['L', 'P'], true)) {
        $error = 'Jenis kelamin tidak valid.';
    } elseif (trim($alamat) === '') {
        $error = 'Alamat wajib diisi.';
    } elseif (!in_array($statusEkonomi, ['mampu', 'kurang_mampu', 'rentan'], true)) {
        $error = 'Status ekonomi tidak valid.';
    } elseif (!is_numeric($kuotaBbm) || $kuotaBbm < 0) {
        $error = 'Kuota BBM harus berupa angka dan tidak boleh kurang dari 0.';
    } else {
        try {
            if ($isEdit) {
                $id = $_GET['id'] ?? null;

                if (!$id) {
                    $error = 'ID warga tidak ditemukan.';
                } else {
                    // Update data warga di E-KTP
                    $stmt = $db->prepare("
                        UPDATE citizens
                        SET 
                            nama = ?,
                            tempat_lahir = ?,
                            tanggal_lahir = ?,
                            jenis_kelamin = ?,
                            alamat = ?,
                            pekerjaan = ?,
                            status_ekonomi = ?,
                            kuota_bbm = ?,
                            status_aktif = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $nama,
                        $tempatLahir,
                        $tanggalLahir,
                        $jenisKelamin,
                        $alamat,
                        $pekerjaan,
                        $statusEkonomi,
                        $kuotaBbm,
                        $statusAktif,
                        $id
                    ]);

                    /**
                     * Sinkronisasi ke Bansos:
                     * Jika status ekonomi lama adalah kurang_mampu/rentan,
                     * lalu status baru menjadi mampu,
                     * maka penerima bansos dinonaktifkan otomatis.
                     */
                    $wasEligibleForBansos = in_array($oldStatusEkonomi, ['kurang_mampu', 'rentan'], true);
                    $isNowMampu = $statusEkonomi === 'mampu';

                    if ($wasEligibleForBansos && $isNowMampu) {
                        $syncResult = deactivateBansosRecipient(
                            $app['bansos_base_url'] ?? null,
                            $nik
                        );

                        if ($syncResult['success']) {
                            $success = 'Data warga berhasil diperbarui. Status penerima bansos juga otomatis dinonaktifkan karena warga berubah menjadi mampu.';
                        } else {
                            $success = 'Data warga berhasil diperbarui.';
                            $warning = 'Namun sinkronisasi ke Bansos gagal: ' . $syncResult['message'];
                        }
                    } else {
                        $success = 'Data warga berhasil diperbarui.';
                    }

                    // Ambil ulang data terbaru dari database setelah update
                    $stmt = $db->prepare("SELECT * FROM citizens WHERE id = ? LIMIT 1");
                    $stmt->execute([$id]);
                    $updatedCitizen = $stmt->fetch();

                    if ($updatedCitizen) {
                        $citizen = $updatedCitizen;
                    }
                }
            } else {
                // Cek NIK agar tidak duplikat
                $checkStmt = $db->prepare("SELECT id FROM citizens WHERE nik = ? LIMIT 1");
                $checkStmt->execute([$nik]);

                if ($checkStmt->fetch()) {
                    $error = 'NIK sudah terdaftar di database E-KTP.';
                } else {
                    // Tambah data warga baru
                    $stmt = $db->prepare("
                        INSERT INTO citizens
                        (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, pekerjaan, status_ekonomi, kuota_bbm, status_aktif)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $nik,
                        $nama,
                        $tempatLahir,
                        $tanggalLahir,
                        $jenisKelamin,
                        $alamat,
                        $pekerjaan,
                        $statusEkonomi,
                        $kuotaBbm,
                        $statusAktif
                    ]);

                    $success = 'Data warga berhasil ditambahkan.';

                    // Reset form setelah tambah berhasil
                    $citizen = [
                        'nik' => '',
                        'nama' => '',
                        'tempat_lahir' => '',
                        'tanggal_lahir' => '',
                        'jenis_kelamin' => 'L',
                        'alamat' => '',
                        'pekerjaan' => '',
                        'status_ekonomi' => 'mampu',
                        'kuota_bbm' => '30.00',
                        'status_aktif' => 'aktif'
                    ];
                }
            }
        } catch (Exception $errorException) {
            $error = 'Terjadi kesalahan saat menyimpan data warga.';
        }
    }
}
?>

<section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">Data Master</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
                <?= $isEdit ? 'Edit Data Warga' : 'Tambah Data Warga' ?>
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                <?= $isEdit ? 'Perbarui data warga yang tersimpan pada database E-KTP.' : 'Tambahkan data warga baru sebagai sumber verifikasi NIK untuk layanan lain.' ?>
            </p>
        </div>

        <a
            href="/citizens"
            class="inline-flex rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
            Kembali ke Data Warga
        </a>
    </div>

    <?php if ($error): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-semibold text-red-700">Gagal</p>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm font-semibold text-emerald-700">Berhasil</p>
            <p class="mt-1 text-sm text-emerald-700"><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($warning): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm font-semibold text-amber-700">Peringatan Sinkronisasi</p>
            <p class="mt-1 text-sm text-amber-700"><?= htmlspecialchars($warning) ?></p>
        </div>
    <?php endif; ?>

    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <form id="citizenForm" method="POST" class="grid gap-5 md:grid-cols-2">
            <input
                type="hidden"
                id="oldStatusEkonomi"
                value="<?= htmlspecialchars($citizen['status_ekonomi'] ?? '') ?>"
            >

            <div>
                <label class="block text-sm font-medium text-slate-700">NIK</label>
                <input
                    type="text"
                    name="nik"
                    maxlength="16"
                    value="<?= htmlspecialchars($citizen['nik']) ?>"
                    <?= $isEdit ? 'readonly' : '' ?>
                    class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100 <?= $isEdit ? 'bg-slate-100 text-slate-500' : '' ?>"
                    required
                >
                <?php if ($isEdit): ?>
                    <p class="mt-1 text-xs text-slate-500">NIK tidak dapat diubah agar integrasi antar-service tetap konsisten.</p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Nama Lengkap</label>
                <input
                    type="text"
                    name="nama"
                    value="<?= htmlspecialchars($citizen['nama']) ?>"
                    class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                    required
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Tempat Lahir</label>
                <input
                    type="text"
                    name="tempat_lahir"
                    value="<?= htmlspecialchars($citizen['tempat_lahir']) ?>"
                    class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                    required
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal Lahir</label>
                <input
                    type="date"
                    name="tanggal_lahir"
                    value="<?= htmlspecialchars($citizen['tanggal_lahir']) ?>"
                    class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                    required
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Jenis Kelamin</label>
                <select
                    name="jenis_kelamin"
                    class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                    required
                >
                    <option value="L" <?= $citizen['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="P" <?= $citizen['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Pekerjaan</label>
                <input
                    type="text"
                    name="pekerjaan"
                    value="<?= htmlspecialchars($citizen['pekerjaan'] ?? '') ?>"
                    class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Status Ekonomi</label>
                <select
                    id="statusEkonomi"
                    name="status_ekonomi"
                    class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                    required
                >
                    <option value="mampu" <?= $citizen['status_ekonomi'] === 'mampu' ? 'selected' : '' ?>>Mampu</option>
                    <option value="kurang_mampu" <?= $citizen['status_ekonomi'] === 'kurang_mampu' ? 'selected' : '' ?>>Kurang Mampu</option>
                    <option value="rentan" <?= $citizen['status_ekonomi'] === 'rentan' ? 'selected' : '' ?>>Rentan</option>
                </select>

                <?php if ($isEdit): ?>
                    <p class="mt-1 text-xs text-slate-500">
                        Jika status diubah dari Kurang Mampu/Rentan menjadi Mampu, sistem akan mencoba menonaktifkan status Bansos secara otomatis.
                    </p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Kuota BBM</label>
                <input
                    type="number"
                    name="kuota_bbm"
                    min="0"
                    step="0.01"
                    value="<?= htmlspecialchars($citizen['kuota_bbm']) ?>"
                    class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                    required
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Status Aktif</label>
                <select
                    name="status_aktif"
                    class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                    required
                >
                    <option value="aktif" <?= $citizen['status_aktif'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $citizen['status_aktif'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Alamat</label>
                <textarea
                    name="alamat"
                    rows="3"
                    class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                    required
                ><?= htmlspecialchars($citizen['alamat']) ?></textarea>
            </div>

            <div class="md:col-span-2">
                <button
                    type="submit"
                    class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    <?= $isEdit ? 'Simpan Perubahan' : 'Tambah Warga' ?>
                </button>
            </div>
        </form>
    </div>
</section>

<script>
    const citizenForm = document.getElementById('citizenForm');
    const statusEkonomi = document.getElementById('statusEkonomi');
    const oldStatusEkonomi = document.getElementById('oldStatusEkonomi');

    if (citizenForm && statusEkonomi && oldStatusEkonomi) {
        citizenForm.addEventListener('submit', function (event) {
            const oldValue = oldStatusEkonomi.value;
            const newValue = statusEkonomi.value;

            const wasEligibleForBansos = oldValue === 'kurang_mampu' || oldValue === 'rentan';
            const isNowMampu = newValue === 'mampu';

            if (wasEligibleForBansos && isNowMampu) {
                const confirmed = confirm(
                    'Status ekonomi warga akan diubah menjadi MAMPU.\n\n' +
                    'Jika warga sebelumnya terdaftar sebagai penerima Bansos, maka status Bansos akan dinonaktifkan otomatis.\n\n' +
                    'Lanjutkan perubahan data?'
                );

                if (!confirmed) {
                    event.preventDefault();
                }
            }
        });
    }
</script>