<?php
$app = require __DIR__ . '/../app/config/app.php';
$db = getDatabaseConnection();

$error = null;
$success = null;
$preview = null;

// Mengecek apakah pasien sudah terdaftar
function findPatientByNik($db, $nik)
{
    $stmt = $db->prepare("SELECT * FROM patients WHERE nik = ? LIMIT 1");
    $stmt->execute([$nik]);
    return $stmt->fetch();
}

// Menentukan tarif otomatis berdasarkan status bansos dan ekonomi
function determinePatientTariff($statusEkonomi, $isBansosActive)
{
    if ($isBansosActive) {
        return [
            'jenis_pasien' => 'bansos',
            'tarif' => 'GRATIS',
            'keterangan' => 'Penerima bansos aktif mendapatkan layanan gratis.'
        ];
    }

    if (in_array($statusEkonomi, ['kurang_mampu', 'rentan'])) {
        return [
            'jenis_pasien' => 'kurang_mampu',
            'tarif' => 'Diskon 20%',
            'keterangan' => 'Warga kurang mampu atau rentan mendapatkan diskon 20%.'
        ];
    }

    return [
        'jenis_pasien' => 'umum',
        'tarif' => 'Tarif Normal',
        'keterangan' => 'Warga umum menggunakan tarif normal.'
    ];
}

// Mengambil data warga dari E-KTP
function getCitizenFromEktp($app, $nik)
{
    $verifyUrl = $app['ektp_base_url'] . '/api/verify-nik/' . $nik;
    $verifyResponse = sendGetRequest($verifyUrl);

    if (!$verifyResponse['success']) {
        return [
            'success' => false,
            'message' => 'Gagal menghubungi E-KTP Service. Pastikan E-KTP berjalan di localhost:8000.'
        ];
    }

    $verifyData = $verifyResponse['data'];

    if (!$verifyData || !($verifyData['success'] ?? false)) {
        return [
            'success' => false,
            'message' => $verifyData['message'] ?? 'NIK tidak ditemukan atau tidak aktif di E-KTP.'
        ];
    }

    $citizen = $verifyData['data'];

    // Ambil status ekonomi dari endpoint status warga agar lebih sesuai plan
    $statusUrl = $app['ektp_base_url'] . '/api/citizen-status/' . $nik;
    $statusResponse = sendGetRequest($statusUrl);

    if ($statusResponse['success'] && isset($statusResponse['data']['data'])) {
        $statusData = $statusResponse['data']['data'];
        $citizen['status_ekonomi'] = $statusData['status_ekonomi'] ?? ($citizen['status_ekonomi'] ?? 'mampu');
        $citizen['status_aktif'] = $statusData['status_aktif'] ?? ($citizen['status_aktif'] ?? 'aktif');
    }

    return [
        'success' => true,
        'data' => $citizen
    ];
}

// Mengecek apakah NIK penerima bansos aktif
function checkBansosStatus($app, $nik)
{
    $bansosUrl = $app['bansos_base_url'] . '/api/bansos-status/' . $nik;
    $bansosResponse = sendGetRequest($bansosUrl);

    if (!$bansosResponse['success']) {
        return [
            'success' => false,
            'active' => false,
            'message' => 'Bansos Service tidak dapat dihubungi. Tarif dihitung tanpa status bansos.'
        ];
    }

    $data = $bansosResponse['data'];

    if (!$data || !($data['success'] ?? false)) {
        return [
            'success' => true,
            'active' => false,
            'message' => $data['message'] ?? 'NIK tidak terdaftar sebagai penerima bansos aktif.'
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
            ? 'NIK terdaftar sebagai penerima bansos aktif.'
            : 'NIK tidak aktif sebagai penerima bansos.',
        'data' => $bansosData
    ];
}

// Proses tombol cek NIK atau konfirmasi registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'check';
    $nik = $_POST['nik'] ?? '';

    if (!preg_match('/^[0-9]{16}$/', $nik)) {
        $error = 'Format NIK harus 16 digit angka.';
    } else {
        $existingPatient = findPatientByNik($db, $nik);

        if ($existingPatient) {
            $error = 'Pasien dengan NIK ini sudah terdaftar di RSUD.';
        } else {
            $citizenResult = getCitizenFromEktp($app, $nik);

            if (!$citizenResult['success']) {
                $error = $citizenResult['message'];
            } else {
                $citizen = $citizenResult['data'];
                $statusEkonomi = $citizen['status_ekonomi'] ?? 'mampu';

                $bansosResult = checkBansosStatus($app, $nik);
                $tariff = determinePatientTariff($statusEkonomi, $bansosResult['active']);

                $preview = [
                    'citizen' => $citizen,
                    'bansos' => $bansosResult,
                    'tariff' => $tariff
                ];

                if ($action === 'confirm') {
                    try {
                        // Simpan pasien setelah petugas konfirmasi
                        $stmt = $db->prepare("
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
                            $tariff['jenis_pasien'],
                            $tariff['tarif']
                        ]);

                        $success = [
                            'message' => 'Pasien berhasil diregistrasi dengan tarif otomatis.',
                            'id' => $db->lastInsertId(),
                            'citizen' => $citizen,
                            'tariff' => $tariff
                        ];

                        $preview = null;
                    } catch (Exception $exception) {
                        $error = 'Gagal menyimpan data pasien ke database RSUD.';
                    }
                }
            }
        }
    }
}
?>

<section class="space-y-6">
    <div>
        <p class="text-sm font-medium text-emerald-700">Loket Registrasi</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
            Registrasi Pasien RSUD
        </h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
            Petugas memasukkan NIK pasien. Sistem akan mengambil data dari E-KTP, mengecek status bansos, lalu menentukan tarif otomatis.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Form cek NIK -->
        <div class="rounded-xl border border-emerald-100 bg-white p-5 lg:col-span-1">
            <h2 class="text-base font-semibold text-slate-900">
                Cek NIK Pasien
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Masukkan NIK untuk mengambil data dari E-KTP Service.
            </p>

            <form method="POST" class="mt-5 space-y-4">
                <input type="hidden" name="action" value="check">

                <div>
                    <label for="nik" class="block text-sm font-medium text-slate-700">
                        NIK
                    </label>
                    <input
                        type="text"
                        id="nik"
                        name="nik"
                        maxlength="16"
                        placeholder="Contoh: 3302010101010001"
                        value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>"
                        class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800"
                >
                    Cek Data Pasien
                </button>
            </form>

            <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Alur Integrasi
                </p>
                <ol class="mt-2 space-y-1 text-sm text-slate-600">
                    <li>1. RSUD input NIK</li>
                    <li>2. GET E-KTP verify-nik</li>
                    <li>3. GET Bansos status</li>
                    <li>4. Tarif otomatis</li>
                    <li>5. Simpan pasien</li>
                </ol>
            </div>
        </div>

        <!-- Hasil preview / registrasi -->
        <div class="space-y-5 lg:col-span-2">
            <?php if ($error): ?>
                <div class="rounded-xl border border-red-200 bg-red-50 p-5">
                    <p class="text-sm font-semibold text-red-700">Registrasi gagal</p>
                    <p class="mt-1 text-sm text-red-600">
                        <?= htmlspecialchars($error) ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-sm font-semibold text-emerald-700">
                        Registrasi berhasil
                    </p>
                    <p class="mt-1 text-sm text-emerald-700">
                        <?= htmlspecialchars($success['message']) ?>
                    </p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Nama Pasien</p>
                            <p class="mt-1 font-semibold text-slate-900">
                                <?= htmlspecialchars($success['citizen']['nama']) ?>
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tarif</p>
                            <p class="mt-1 font-semibold text-slate-900">
                                <?= htmlspecialchars($success['tariff']['tarif']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a
                            href="/patients"
                            class="inline-flex rounded-lg border border-emerald-200 bg-white px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                        >
                            Lihat Data Pasien
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($preview): ?>
                <?php
                    $citizen = $preview['citizen'];
                    $bansos = $preview['bansos'];
                    $tariff = $preview['tariff'];
                ?>

                <div class="rounded-xl border border-emerald-100 bg-white p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-emerald-700">
                                Data ditemukan
                            </p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900">
                                <?= htmlspecialchars($citizen['nama']) ?>
                            </h2>
                            <p class="mt-1 font-mono text-sm text-slate-500">
                                <?= htmlspecialchars($citizen['nik']) ?>
                            </p>
                        </div>

                        <div class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                            Terverifikasi E-KTP
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tempat, Tanggal Lahir</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                <?= htmlspecialchars($citizen['tempat_lahir'] ?? '-') ?>,
                                <?= htmlspecialchars($citizen['tanggal_lahir'] ?? '-') ?>
                            </p>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Jenis Kelamin</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                <?= htmlspecialchars($citizen['jenis_kelamin'] ?? '-') ?>
                            </p>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pekerjaan</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                <?= htmlspecialchars($citizen['pekerjaan'] ?? '-') ?>
                            </p>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Status Ekonomi</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                <?= htmlspecialchars(str_replace('_', ' ', $citizen['status_ekonomi'] ?? 'mampu')) ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg border <?= $bansos['active'] ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' ?> p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Status Bansos</p>
                            <p class="mt-1 text-sm font-semibold <?= $bansos['active'] ? 'text-emerald-700' : 'text-slate-700' ?>">
                                <?= $bansos['active'] ? 'Aktif' : 'Tidak Aktif' ?>
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                <?= htmlspecialchars($bansos['message']) ?>
                            </p>
                        </div>

                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">Tarif Otomatis</p>
                            <p class="mt-1 text-lg font-semibold text-emerald-900">
                                <?= htmlspecialchars($tariff['tarif']) ?>
                            </p>
                            <p class="mt-1 text-xs text-emerald-700">
                                <?= htmlspecialchars($tariff['keterangan']) ?>
                            </p>
                        </div>
                    </div>

                    <form method="POST" class="mt-5">
                        <input type="hidden" name="action" value="confirm">
                        <input type="hidden" name="nik" value="<?= htmlspecialchars($citizen['nik']) ?>">

                        <button
                            type="submit"
                            class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800"
                        >
                            Konfirmasi Registrasi Pasien
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (!$error && !$success && !$preview): ?>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-sm font-semibold text-slate-900">
                        Belum ada data pasien
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        Masukkan NIK pasien untuk menampilkan data warga dan tarif otomatis.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>