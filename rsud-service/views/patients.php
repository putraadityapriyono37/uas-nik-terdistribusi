<?php
$app = require __DIR__ . '/../app/config/app.php';
$db = getDatabaseConnection();

$success = null;
$error = null;

// Menentukan tarif otomatis berdasarkan status bansos dan ekonomi
function determinePatientTariffFromStatus($statusEkonomi, $isBansosActive)
{
    if ($isBansosActive) {
        return [
            'jenis_pasien' => 'bansos',
            'tarif' => 'GRATIS'
        ];
    }

    if (in_array($statusEkonomi, ['kurang_mampu', 'rentan'])) {
        return [
            'jenis_pasien' => 'kurang_mampu',
            'tarif' => 'Diskon 20%'
        ];
    }

    return [
        'jenis_pasien' => 'umum',
        'tarif' => 'Tarif Normal'
    ];
}

// Ambil status ekonomi terbaru dari E-KTP
function getCitizenStatusForTariff($app, $nik)
{
    $url = $app['ektp_base_url'] . '/api/citizen-status/' . $nik;
    $response = sendGetRequest($url);

    if (!$response['success']) {
        return [
            'success' => false,
            'message' => 'Gagal menghubungi E-KTP Service.'
        ];
    }

    $data = $response['data'];

    if (!$data || !($data['success'] ?? false)) {
        return [
            'success' => false,
            'message' => $data['message'] ?? 'Status warga tidak ditemukan.'
        ];
    }

    return [
        'success' => true,
        'data' => $data['data']
    ];
}

// Cek status bansos terbaru
function getBansosStatusForTariff($app, $nik)
{
    $url = $app['bansos_base_url'] . '/api/bansos-status/' . $nik;
    $response = sendGetRequest($url);

    if (!$response['success']) {
        return [
            'success' => false,
            'active' => false,
            'message' => 'Bansos Service tidak dapat dihubungi.'
        ];
    }

    $data = $response['data'];

    if (!$data || !($data['success'] ?? false)) {
        return [
            'success' => true,
            'active' => false,
            'message' => 'Tidak terdaftar sebagai penerima bansos aktif.'
        ];
    }

    $bansosData = $data['data'] ?? [];

    $statusBansos = $bansosData['status_bansos']
        ?? $bansosData['status']
        ?? $bansosData['status_penerima']
        ?? 'nonaktif';

    return [
        'success' => true,
        'active' => $statusBansos === 'aktif',
        'message' => $statusBansos === 'aktif'
            ? 'Penerima bansos aktif.'
            : 'Penerima bansos tidak aktif.'
    ];
}

// Proses aksi sinkron tarif atau hapus pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;

    if (!$id) {
        $error = 'ID pasien tidak valid.';
    } else {
        $stmt = $db->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $patient = $stmt->fetch();

        if (!$patient) {
            $error = 'Data pasien tidak ditemukan.';
        } else {
            if ($action === 'delete') {
                try {
                    // Hapus data pasien lokal RSUD
                    $deleteStmt = $db->prepare("DELETE FROM patients WHERE id = ?");
                    $deleteStmt->execute([$id]);

                    $success = 'Data pasien berhasil dihapus.';
                } catch (Exception $exception) {
                    $error = 'Gagal menghapus data pasien.';
                }
            }

            if ($action === 'sync_tariff') {
                $nik = $patient['nik'];

                $citizenStatus = getCitizenStatusForTariff($app, $nik);

                if (!$citizenStatus['success']) {
                    $error = $citizenStatus['message'];
                } else {
                    $statusEkonomi = $citizenStatus['data']['status_ekonomi'] ?? 'mampu';
                    $bansosStatus = getBansosStatusForTariff($app, $nik);

                    $tariff = determinePatientTariffFromStatus(
                        $statusEkonomi,
                        $bansosStatus['active']
                    );

                    try {
                        // Update jenis pasien dan tarif sesuai data terbaru
                        $updateStmt = $db->prepare("
                            UPDATE patients
                            SET jenis_pasien = ?, tarif = ?
                            WHERE id = ?
                        ");

                        $updateStmt->execute([
                            $tariff['jenis_pasien'],
                            $tariff['tarif'],
                            $id
                        ]);

                        $success = 'Tarif pasien berhasil disinkronkan ulang berdasarkan data E-KTP dan Bansos.';
                    } catch (Exception $exception) {
                        $error = 'Gagal memperbarui tarif pasien.';
                    }
                }
            }
        }
    }
}

$stmt = $db->query("
    SELECT id, nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, pekerjaan, jenis_pasien, tarif, created_at
    FROM patients
    ORDER BY id DESC
");

$patients = $stmt->fetchAll();
?>

<section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm font-medium text-emerald-700">Data Registrasi</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
                Data Pasien RSUD
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Daftar pasien yang telah diregistrasi melalui verifikasi NIK ke E-KTP Service.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="rounded-lg border border-emerald-100 bg-white px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Pasien</p>
                <p class="mt-1 text-xl font-semibold text-slate-900"><?= count($patients) ?></p>
            </div>

            <a
                href="/register-patient"
                class="inline-flex rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800"
            >
                Registrasi Pasien
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm font-semibold text-emerald-700">Berhasil</p>
            <p class="mt-1 text-sm text-emerald-700"><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-semibold text-red-700">Gagal</p>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <div class="overflow-hidden rounded-xl border border-emerald-100 bg-white">
        <div class="border-b border-emerald-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Tabel Pasien</h2>
            <p class="mt-1 text-sm text-slate-500">
                Gunakan tombol sinkron untuk memperbarui tarif berdasarkan status E-KTP dan Bansos terbaru.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-emerald-50/60">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">No</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">NIK</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Nama</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">TTL</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">JK</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Pekerjaan</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Jenis Pasien</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Tarif</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Tanggal Registrasi</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">
                    <?php if (count($patients) === 0): ?>
                        <tr>
                            <td colspan="10" class="px-5 py-6 text-center text-slate-500">
                                Belum ada data pasien.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php $no = 1; ?>
                    <?php foreach ($patients as $patient): ?>
                        <tr class="hover:bg-emerald-50/30">
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                <?= $no++ ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-700">
                                <?= htmlspecialchars($patient['nik']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-900">
                                <?= htmlspecialchars($patient['nama']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                <?= htmlspecialchars($patient['tempat_lahir'] ?? '-') ?>,
                                <?= htmlspecialchars($patient['tanggal_lahir'] ?? '-') ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                <?= htmlspecialchars($patient['jenis_kelamin'] ?? '-') ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                <?= htmlspecialchars($patient['pekerjaan'] ?? '-') ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <?php
                                    $jenisPasien = $patient['jenis_pasien'];
                                    $badgeClass = 'border border-slate-200 bg-slate-50 text-slate-700';

                                    if ($jenisPasien === 'bansos') {
                                        $badgeClass = 'border border-emerald-200 bg-emerald-50 text-emerald-700';
                                    } elseif ($jenisPasien === 'kurang_mampu') {
                                        $badgeClass = 'border border-amber-200 bg-amber-50 text-amber-700';
                                    } elseif ($jenisPasien === 'umum') {
                                        $badgeClass = 'border border-blue-200 bg-blue-50 text-blue-700';
                                    }
                                ?>

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= $badgeClass ?>">
                                    <?= htmlspecialchars(str_replace('_', ' ', $jenisPasien)) ?>
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-700">
                                <?= htmlspecialchars($patient['tarif']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                                <?= htmlspecialchars($patient['created_at']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex flex-col gap-2">
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($patient['id']) ?>">
                                        <input type="hidden" name="action" value="sync_tariff">

                                        <button
                                            type="submit"
                                            class="w-full rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100"
                                        >
                                            Sinkron Tarif
                                        </button>
                                    </form>

                                    <form method="POST" onsubmit="return confirm('Hapus data pasien ini dari RSUD?')">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($patient['id']) ?>">
                                        <input type="hidden" name="action" value="delete">

                                        <button
                                            type="submit"
                                            class="w-full rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>