<?php
$app = require __DIR__ . '/../app/config/app.php';
$db = getDatabaseConnection();

$error = null;
$success = null;
$preview = null;

// Ambil status warga dari E-KTP
function getCitizenForFuel($app, $nik)
{
    $url = $app['ektp_base_url'] . '/api/citizen-status/' . $nik;
    $response = sendGetRequest($url);

    if (!$response['success']) {
        return [
            'success' => false,
            'message' => 'Gagal menghubungi E-KTP Service. Pastikan E-KTP berjalan di localhost:8000.'
        ];
    }

    $data = $response['data'];

    if (!$data || !($data['success'] ?? false)) {
        return [
            'success' => false,
            'message' => $data['message'] ?? 'NIK tidak ditemukan di E-KTP.'
        ];
    }

    return [
        'success' => true,
        'data' => $data['data']
    ];
}

// Cek status bansos dari Bansos Service
function getBansosForFuel($app, $nik)
{
    $url = $app['bansos_base_url'] . '/api/bansos-status/' . $nik;
    $response = sendGetRequest($url);

    if (!$response['success']) {
        return [
            'success' => false,
            'active' => false,
            'message' => 'Bansos Service tidak dapat dihubungi. Harga dihitung tanpa status bansos.'
        ];
    }

    $data = $response['data'];

    if (!$data || !($data['success'] ?? false)) {
        return [
            'success' => true,
            'active' => false,
            'message' => 'NIK tidak terdaftar sebagai penerima bansos aktif.'
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

// Menentukan harga per liter
function determineFuelPrice($statusEkonomi, $isBansosActive)
{
    if ($isBansosActive) {
        return [
            'kategori' => 'bansos',
            'harga_per_liter' => 9000,
            'keterangan' => 'Penerima bansos aktif mendapatkan harga subsidi utama.'
        ];
    }

    if (in_array($statusEkonomi, ['kurang_mampu', 'rentan'])) {
        return [
            'kategori' => 'subsidi',
            'harga_per_liter' => 10000,
            'keterangan' => 'Warga kurang mampu/rentan mendapatkan harga subsidi.'
        ];
    }

    return [
        'kategori' => 'umum',
        'harga_per_liter' => 13000,
        'keterangan' => 'Warga umum menggunakan harga normal.'
    ];
}

// Update kuota BBM di E-KTP
function updateFuelQuotaToEktp($app, $nik, $newQuota)
{
    $url = $app['ektp_base_url'] . '/api/bbm-quota/' . $nik;

    $payload = [
        'kuota_bbm' => $newQuota
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($curlError) {
        return [
            'success' => false,
            'message' => 'Gagal menghubungi endpoint update kuota E-KTP.'
        ];
    }

    $decodedResponse = json_decode($response, true);

    if (!$decodedResponse || !($decodedResponse['success'] ?? false)) {
        return [
            'success' => false,
            'message' => $decodedResponse['message'] ?? 'Gagal memperbarui kuota BBM di E-KTP.'
        ];
    }

    return [
        'success' => true,
        'data' => $decodedResponse['data']
    ];
}

// Proses cek atau konfirmasi transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'check';
    $nik = $_POST['nik'] ?? '';
    $jenisBbm = $_POST['jenis_bbm'] ?? 'Pertalite';
    $jumlahLiter = $_POST['jumlah_liter'] ?? 0;
    $keterangan = $_POST['keterangan'] ?? '-';

    if (!preg_match('/^[0-9]{16}$/', $nik)) {
        $error = 'Format NIK harus 16 digit angka.';
    } elseif (!is_numeric($jumlahLiter) || $jumlahLiter <= 0) {
        $error = 'Jumlah liter harus lebih dari 0.';
    } else {
        $citizenResult = getCitizenForFuel($app, $nik);

        if (!$citizenResult['success']) {
            $error = $citizenResult['message'];
        } else {
            $citizen = $citizenResult['data'];
            $statusEkonomi = $citizen['status_ekonomi'] ?? 'mampu';
            $kuotaSebelum = (float) ($citizen['kuota_bbm'] ?? 0);
            $jumlahLiter = (float) $jumlahLiter;

            if ($jumlahLiter > $kuotaSebelum) {
                $error = 'Kuota BBM tidak mencukupi. Sisa kuota saat ini: ' . $kuotaSebelum . ' liter.';
            } else {
                $bansosResult = getBansosForFuel($app, $nik);
                $priceRule = determineFuelPrice($statusEkonomi, $bansosResult['active']);
                $totalHarga = $jumlahLiter * $priceRule['harga_per_liter'];
                $kuotaSesudah = $kuotaSebelum - $jumlahLiter;

                $preview = [
                    'citizen' => $citizen,
                    'bansos' => $bansosResult,
                    'price_rule' => $priceRule,
                    'jenis_bbm' => $jenisBbm,
                    'jumlah_liter' => $jumlahLiter,
                    'total_harga' => $totalHarga,
                    'kuota_sebelum' => $kuotaSebelum,
                    'kuota_sesudah' => $kuotaSesudah,
                    'keterangan' => $keterangan
                ];

                if ($action === 'confirm') {
                    $quotaUpdate = updateFuelQuotaToEktp($app, $nik, $kuotaSesudah);

                    if (!$quotaUpdate['success']) {
                        $error = $quotaUpdate['message'];
                    } else {
                        try {
                            // Simpan transaksi lokal sesuai struktur tabel db_spbu
                            $stmt = $db->prepare("
                                INSERT INTO fuel_transactions
                                (nik, nama, status_bansos, jenis_bbm, jumlah_liter, harga_per_liter, total_harga, kuota_sebelum, kuota_sesudah, keterangan)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");

                            $stmt->execute([
                                $citizen['nik'],
                                $citizen['nama'],
                                $bansosResult['active'] ? 'aktif' : 'nonaktif',
                                $jenisBbm,
                                $jumlahLiter,
                                $priceRule['harga_per_liter'],
                                $totalHarga,
                                $kuotaSebelum,
                                $kuotaSesudah,
                                $keterangan
                            ]);

                            $success = [
                                'message' => 'Transaksi BBM berhasil diproses dan kuota E-KTP diperbarui.',
                                'citizen' => $citizen,
                                'jenis_bbm' => $jenisBbm,
                                'jumlah_liter' => $jumlahLiter,
                                'harga_per_liter' => $priceRule['harga_per_liter'],
                                'total_harga' => $totalHarga,
                                'kuota_sebelum' => $kuotaSebelum,
                                'kuota_sesudah' => $kuotaSesudah
                            ];

                            $preview = null;
                        } catch (Exception $exception) {
                            $error = 'Kuota E-KTP sudah diperbarui, tetapi transaksi gagal disimpan di SPBU.';
                        }
                    }
                }
            }
        }
    }
}
?>

<section class="space-y-6">
    <div>
        <p class="text-sm font-medium text-red-800">Loket SPBU</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900">
            Transaksi BBM Berbasis NIK
        </h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-600">
            Petugas memasukkan NIK dan jumlah BBM. Sistem mengecek status warga ke E-KTP, mengecek bansos, menghitung harga otomatis, lalu mengurangi kuota BBM.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-red-100 bg-white p-5 lg:col-span-1">
            <h2 class="text-base font-semibold text-zinc-900">
                Input Transaksi
            </h2>
            <p class="mt-1 text-sm text-zinc-500">
                Cek transaksi terlebih dahulu sebelum konfirmasi.
            </p>

            <form method="POST" class="mt-5 space-y-4">
                <input type="hidden" name="action" value="check">

                <div>
                    <label for="nik" class="block text-sm font-medium text-zinc-700">
                        NIK
                    </label>
                    <input
                        type="text"
                        id="nik"
                        name="nik"
                        maxlength="16"
                        placeholder="Contoh: 3302010202020002"
                        value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>"
                        class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100"
                        required
                    >
                </div>

                <div>
                    <label for="jenis_bbm" class="block text-sm font-medium text-zinc-700">
                        Jenis BBM
                    </label>
                    <select
                        id="jenis_bbm"
                        name="jenis_bbm"
                        class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100"
                    >
                        <option value="Pertalite" <?= ($_POST['jenis_bbm'] ?? '') === 'Pertalite' ? 'selected' : '' ?>>Pertalite</option>
                        <option value="Solar" <?= ($_POST['jenis_bbm'] ?? '') === 'Solar' ? 'selected' : '' ?>>Solar</option>
                        <option value="Pertamax" <?= ($_POST['jenis_bbm'] ?? '') === 'Pertamax' ? 'selected' : '' ?>>Pertamax</option>
                    </select>
                </div>

                <div>
                    <label for="jumlah_liter" class="block text-sm font-medium text-zinc-700">
                        Jumlah Liter
                    </label>
                    <input
                        type="number"
                        id="jumlah_liter"
                        name="jumlah_liter"
                        min="0.1"
                        step="0.1"
                        placeholder="Contoh: 5"
                        value="<?= htmlspecialchars($_POST['jumlah_liter'] ?? '') ?>"
                        class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100"
                        required
                    >
                </div>

                <div>
                    <label for="keterangan" class="block text-sm font-medium text-zinc-700">
                        Keterangan
                    </label>
                    <textarea
                        id="keterangan"
                        name="keterangan"
                        rows="2"
                        placeholder="Contoh: Transaksi loket 1"
                        class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100"
                    ><?= htmlspecialchars($_POST['keterangan'] ?? '') ?></textarea>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800"
                >
                    Cek Transaksi
                </button>
            </form>

            <div class="mt-5 rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    Alur Integrasi
                </p>
                <ol class="mt-2 space-y-1 text-sm text-zinc-600">
                    <li>1. SPBU input NIK</li>
                    <li>2. GET E-KTP status warga</li>
                    <li>3. GET Bansos status</li>
                    <li>4. Hitung harga otomatis</li>
                    <li>5. PUT update kuota E-KTP</li>
                </ol>
            </div>
        </div>

        <div class="space-y-5 lg:col-span-2">
            <?php if ($error): ?>
                <div class="rounded-xl border border-red-200 bg-red-50 p-5">
                    <p class="text-sm font-semibold text-red-700">Transaksi gagal</p>
                    <p class="mt-1 text-sm text-red-600">
                        <?= htmlspecialchars($error) ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-sm font-semibold text-emerald-700">
                        Transaksi berhasil
                    </p>
                    <p class="mt-1 text-sm text-emerald-700">
                        <?= htmlspecialchars($success['message']) ?>
                    </p>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Nama</p>
                            <p class="mt-1 font-semibold text-zinc-900">
                                <?= htmlspecialchars($success['citizen']['nama']) ?>
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Kuota</p>
                            <p class="mt-1 font-semibold text-zinc-900">
                                <?= htmlspecialchars($success['kuota_sebelum']) ?> L
                                <span class="text-zinc-400">→</span>
                                <?= htmlspecialchars($success['kuota_sesudah']) ?> L
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Total Harga</p>
                            <p class="mt-1 font-semibold text-zinc-900">
                                Rp <?= number_format($success['total_harga'], 0, ',', '.') ?>
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Harga / Liter</p>
                            <p class="mt-1 font-semibold text-zinc-900">
                                Rp <?= number_format($success['harga_per_liter'], 0, ',', '.') ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <a
                            href="/transactions"
                            class="inline-flex rounded-lg border border-emerald-200 bg-white px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                        >
                            Lihat Data Transaksi
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($preview): ?>
                <?php
                    $citizen = $preview['citizen'];
                    $bansos = $preview['bansos'];
                    $priceRule = $preview['price_rule'];
                ?>

                <div class="rounded-xl border border-red-100 bg-white p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-red-700">
                                Data transaksi valid
                            </p>
                            <h2 class="mt-1 text-xl font-semibold text-zinc-900">
                                <?= htmlspecialchars($citizen['nama']) ?>
                            </h2>
                            <p class="mt-1 font-mono text-sm text-zinc-500">
                                <?= htmlspecialchars($citizen['nik']) ?>
                            </p>
                        </div>

                        <div class="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-medium text-red-700">
                            Siap Diproses
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-zinc-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Status Ekonomi</p>
                            <p class="mt-1 text-sm font-semibold text-zinc-900">
                                <?= htmlspecialchars(str_replace('_', ' ', $citizen['status_ekonomi'] ?? 'mampu')) ?>
                            </p>
                        </div>

                        <div class="rounded-lg border <?= $bansos['active'] ? 'border-emerald-200 bg-emerald-50' : 'border-zinc-200 bg-zinc-50' ?> p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Status Bansos</p>
                            <p class="mt-1 text-sm font-semibold <?= $bansos['active'] ? 'text-emerald-700' : 'text-zinc-700' ?>">
                                <?= $bansos['active'] ? 'Aktif' : 'Tidak Aktif' ?>
                            </p>
                            <p class="mt-1 text-xs text-zinc-500">
                                <?= htmlspecialchars($bansos['message']) ?>
                            </p>
                        </div>

                        <div class="rounded-lg border border-zinc-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Kuota Sebelum</p>
                            <p class="mt-1 text-sm font-semibold text-zinc-900">
                                <?= htmlspecialchars($preview['kuota_sebelum']) ?> liter
                            </p>
                        </div>

                        <div class="rounded-lg border border-zinc-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Kuota Sesudah</p>
                            <p class="mt-1 text-sm font-semibold text-zinc-900">
                                <?= htmlspecialchars($preview['kuota_sesudah']) ?> liter
                            </p>
                        </div>

                        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-red-700">Harga / Liter</p>
                            <p class="mt-1 text-lg font-semibold text-red-900">
                                Rp <?= number_format($priceRule['harga_per_liter'], 0, ',', '.') ?>
                            </p>
                            <p class="mt-1 text-xs text-red-700">
                                <?= htmlspecialchars($priceRule['keterangan']) ?>
                            </p>
                        </div>

                        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-red-700">Total Harga</p>
                            <p class="mt-1 text-lg font-semibold text-red-900">
                                Rp <?= number_format($preview['total_harga'], 0, ',', '.') ?>
                            </p>
                            <p class="mt-1 text-xs text-red-700">
                                <?= htmlspecialchars($preview['jumlah_liter']) ?> liter <?= htmlspecialchars($preview['jenis_bbm']) ?>
                            </p>
                        </div>
                    </div>

                    <form method="POST" class="mt-5">
                        <input type="hidden" name="action" value="confirm">
                        <input type="hidden" name="nik" value="<?= htmlspecialchars($citizen['nik']) ?>">
                        <input type="hidden" name="jenis_bbm" value="<?= htmlspecialchars($preview['jenis_bbm']) ?>">
                        <input type="hidden" name="jumlah_liter" value="<?= htmlspecialchars($preview['jumlah_liter']) ?>">
                        <input type="hidden" name="keterangan" value="<?= htmlspecialchars($preview['keterangan']) ?>">

                        <button
                            type="submit"
                            class="rounded-lg bg-red-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-800"
                        >
                            Konfirmasi Transaksi BBM
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (!$error && !$success && !$preview): ?>
                <div class="rounded-xl border border-zinc-200 bg-white p-5">
                    <p class="text-sm font-semibold text-zinc-900">
                        Belum ada transaksi
                    </p>
                    <p class="mt-1 text-sm text-zinc-500">
                        Masukkan NIK dan jumlah liter BBM untuk mengecek harga serta kuota.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>