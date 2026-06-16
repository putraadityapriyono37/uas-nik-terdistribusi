<?php
$app = require __DIR__ . '/../app/config/app.php';
$db = getDatabaseConnection();

$error = null;
$success = null;
$preview = null;

// Cek apakah NIK sudah terdaftar sebagai penerima
function findRecipientByNik($db, $nik)
{
    $stmt = $db->prepare("SELECT * FROM recipients WHERE nik = ? LIMIT 1");
    $stmt->execute([$nik]);
    return $stmt->fetch();
}

// Ambil data warga dari E-KTP
function getCitizenForBansos($app, $nik)
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

// Proses cek NIK atau konfirmasi registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'check';
    $nik = $_POST['nik'] ?? '';
    $jenisBantuan = $_POST['jenis_bantuan'] ?? 'Bantuan Sosial Pokok';

    if (!preg_match('/^[0-9]{16}$/', $nik)) {
        $error = 'Format NIK harus 16 digit angka.';
    } else {
        $existingRecipient = findRecipientByNik($db, $nik);

        if ($existingRecipient) {
            $error = 'NIK ini sudah terdaftar sebagai penerima bansos.';
        } else {
            $citizenResult = getCitizenForBansos($app, $nik);

            if (!$citizenResult['success']) {
                $error = $citizenResult['message'];
            } else {
                $citizen = $citizenResult['data'];
                $statusEkonomi = $citizen['status_ekonomi'] ?? 'mampu';

                $isEligible = in_array($statusEkonomi, ['kurang_mampu', 'rentan']);

                $preview = [
                    'citizen' => $citizen,
                    'jenis_bantuan' => $jenisBantuan,
                    'eligible' => $isEligible,
                    'reason' => $isEligible
                        ? 'Warga memenuhi syarat karena status ekonomi kurang mampu/rentan.'
                        : 'Warga tidak memenuhi syarat karena status ekonomi mampu.'
                ];

                if ($action === 'confirm') {
                    if (!$isEligible) {
                        $error = 'Warga tidak memenuhi syarat sebagai penerima bansos.';
                    } else {
                        try {
                            // Simpan penerima bansos lokal
                            $stmt = $db->prepare("
                                INSERT INTO recipients
                                (nik, nama, status_ekonomi, jenis_bantuan, status_bansos)
                                VALUES (?, ?, ?, ?, ?)
                            ");

                            $stmt->execute([
                                $citizen['nik'],
                                $citizen['nama'],
                                $statusEkonomi,
                                $jenisBantuan,
                                'aktif'
                            ]);

                            $success = [
                                'message' => 'Penerima bansos berhasil diregistrasi.',
                                'nik' => $citizen['nik'],
                                'nama' => $citizen['nama'],
                                'status_ekonomi' => $statusEkonomi,
                                'jenis_bantuan' => $jenisBantuan,
                                'status_bansos' => 'aktif'
                            ];

                            $preview = null;
                        } catch (Exception $exception) {
                            $error = 'Gagal menyimpan data penerima bansos.';
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
        <p class="text-sm font-medium text-amber-700">Loket Bansos</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-stone-900">
            Registrasi Penerima Bansos
        </h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-600">
            Petugas memasukkan NIK warga. Sistem akan mengecek status ekonomi ke E-KTP Service sebelum menyimpan penerima bansos.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Form cek NIK -->
        <div class="rounded-xl border border-amber-100 bg-white p-5 lg:col-span-1">
            <h2 class="text-base font-semibold text-stone-900">
                Cek Kelayakan Warga
            </h2>
            <p class="mt-1 text-sm text-stone-500">
                Warga dengan status kurang mampu/rentan dapat diregistrasi sebagai penerima bansos.
            </p>

            <form method="POST" class="mt-5 space-y-4">
                <input type="hidden" name="action" value="check">

                <div>
                    <label for="nik" class="block text-sm font-medium text-stone-700">
                        NIK
                    </label>
                    <input
                        type="text"
                        id="nik"
                        name="nik"
                        maxlength="16"
                        placeholder="Contoh: 3302010202020002"
                        value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>"
                        class="mt-2 w-full rounded-lg border border-stone-200 px-3 py-2 text-sm outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                        required
                    >
                </div>

                <div>
                    <label for="jenis_bantuan" class="block text-sm font-medium text-stone-700">
                        Jenis Bantuan
                    </label>
                    <select
                        id="jenis_bantuan"
                        name="jenis_bantuan"
                        class="mt-2 w-full rounded-lg border border-stone-200 px-3 py-2 text-sm outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                    >
                        <option value="Bantuan Sosial Pokok" <?= ($_POST['jenis_bantuan'] ?? '') === 'Bantuan Sosial Pokok' ? 'selected' : '' ?>>
                            Bantuan Sosial Pokok
                        </option>
                        <option value="Bantuan Pangan" <?= ($_POST['jenis_bantuan'] ?? '') === 'Bantuan Pangan' ? 'selected' : '' ?>>
                            Bantuan Pangan
                        </option>
                        <option value="Bantuan Kesehatan" <?= ($_POST['jenis_bantuan'] ?? '') === 'Bantuan Kesehatan' ? 'selected' : '' ?>>
                            Bantuan Kesehatan
                        </option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-amber-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-800"
                >
                    Cek Kelayakan
                </button>
            </form>

            <div class="mt-5 rounded-lg border border-stone-200 bg-stone-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">
                    Alur Integrasi
                </p>
                <ol class="mt-2 space-y-1 text-sm text-stone-600">
                    <li>1. Bansos input NIK</li>
                    <li>2. GET E-KTP citizen-status</li>
                    <li>3. Validasi status ekonomi</li>
                    <li>4. Simpan penerima</li>
                </ol>
            </div>
        </div>

        <!-- Hasil cek -->
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
                    <p class="text-sm font-semibold text-emerald-700">Registrasi berhasil</p>
                    <p class="mt-1 text-sm text-emerald-700">
                        <?= htmlspecialchars($success['message']) ?>
                    </p>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Nama</p>
                            <p class="mt-1 font-semibold text-stone-900">
                                <?= htmlspecialchars($success['nama']) ?>
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Status Bansos</p>
                            <p class="mt-1 font-semibold text-emerald-700">
                                <?= htmlspecialchars($success['status_bansos']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <a
                            href="/recipients"
                            class="inline-flex rounded-lg border border-emerald-200 bg-white px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                        >
                            Lihat Data Penerima
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($preview): ?>
                <?php $citizen = $preview['citizen']; ?>

                <div class="rounded-xl border border-amber-100 bg-white p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-amber-700">
                                Data warga ditemukan
                            </p>
                            <h2 class="mt-1 text-xl font-semibold text-stone-900">
                                <?= htmlspecialchars($citizen['nama']) ?>
                            </h2>
                            <p class="mt-1 font-mono text-sm text-stone-500">
                                <?= htmlspecialchars($citizen['nik']) ?>
                            </p>
                        </div>

                        <?php if ($preview['eligible']): ?>
                            <div class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                                Layak Bansos
                            </div>
                        <?php else: ?>
                            <div class="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-medium text-red-700">
                                Tidak Layak
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-stone-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Status Ekonomi</p>
                            <p class="mt-1 text-sm font-semibold text-stone-900">
                                <?= htmlspecialchars(str_replace('_', ' ', $citizen['status_ekonomi'] ?? '-')) ?>
                            </p>
                        </div>

                        <div class="rounded-lg border border-stone-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Jenis Bantuan</p>
                            <p class="mt-1 text-sm font-semibold text-stone-900">
                                <?= htmlspecialchars($preview['jenis_bantuan']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-lg border <?= $preview['eligible'] ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' ?> p-4">
                        <p class="text-sm font-semibold <?= $preview['eligible'] ? 'text-emerald-700' : 'text-red-700' ?>">
                            <?= htmlspecialchars($preview['reason']) ?>
                        </p>
                    </div>

                    <?php if ($preview['eligible']): ?>
                        <form method="POST" class="mt-5">
                            <input type="hidden" name="action" value="confirm">
                            <input type="hidden" name="nik" value="<?= htmlspecialchars($citizen['nik']) ?>">
                            <input type="hidden" name="jenis_bantuan" value="<?= htmlspecialchars($preview['jenis_bantuan']) ?>">

                            <button
                                type="submit"
                                class="rounded-lg bg-amber-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-800"
                            >
                                Konfirmasi Registrasi Penerima
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!$error && !$success && !$preview): ?>
                <div class="rounded-xl border border-stone-200 bg-white p-5">
                    <p class="text-sm font-semibold text-stone-900">
                        Belum ada pengecekan
                    </p>
                    <p class="mt-1 text-sm text-stone-500">
                        Masukkan NIK warga untuk mengecek kelayakan penerima bansos.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>