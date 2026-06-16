<?php
// Menampung hasil proses form
$result = null;
$error = null;

// Proses form ketika tombol submit ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = $_POST['nik'] ?? '';

    if (!preg_match('/^[0-9]{16}$/', $nik)) {
        $error = 'Format NIK harus 16 digit angka.';
    } else {
        $app = require __DIR__ . '/../app/config/app.php';
        $db = getDatabaseConnection();

        // Cek apakah pasien sudah pernah diregistrasi
        $checkStmt = $db->prepare("SELECT id, nik, nama FROM patients WHERE nik = ? LIMIT 1");
        $checkStmt->execute([$nik]);
        $existingPatient = $checkStmt->fetch();

        if ($existingPatient) {
            $error = 'Pasien dengan NIK ini sudah terdaftar di RSUD.';
        } else {
            // Memanggil E-KTP Service untuk verifikasi NIK
            $ektpUrl = $app['ektp_base_url'] . '/api/verify-nik/' . $nik;
            $ektpResponse = sendGetRequest($ektpUrl);

            if (!$ektpResponse['success']) {
                $error = 'Gagal menghubungi E-KTP Service. Pastikan E-KTP berjalan di localhost:8000.';
            } else {
                $ektpData = $ektpResponse['data'];

                if (!$ektpData || !$ektpData['success']) {
                    $error = $ektpData['message'] ?? 'NIK tidak valid berdasarkan data E-KTP.';
                } else {
                    $citizen = $ektpData['data'];

                    // Untuk tahap awal, jenis pasien masih umum
                    $jenisPasien = 'umum';
                    $tarif = 'Tarif Normal';

                    // Simpan data pasien ke database RSUD
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
                        $jenisPasien,
                        $tarif
                    ]);

                    $result = [
                        'message' => 'Pasien berhasil diregistrasi berdasarkan data E-KTP.',
                        'data' => [
                            'id' => $db->lastInsertId(),
                            'nik' => $citizen['nik'],
                            'nama' => $citizen['nama'],
                            'jenis_pasien' => $jenisPasien,
                            'tarif' => $tarif,
                            'sumber_data' => 'E-KTP Service'
                        ]
                    ];
                }
            }
        }
    }
}
?>

<section class="space-y-6">
    <div>
        <p class="text-sm font-medium text-emerald-700">Form Registrasi</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
            Registrasi Pasien RSUD
        </h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
            Masukkan NIK pasien. Sistem RSUD akan memverifikasi data pasien ke E-KTP Service sebelum menyimpan data ke database RSUD.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Form input NIK -->
        <div class="rounded-xl border border-emerald-100 bg-white p-5 lg:col-span-1">
            <h2 class="text-base font-semibold text-slate-900">
                Input NIK Pasien
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Gunakan NIK yang sudah tersedia pada database E-KTP.
            </p>

            <form method="POST" class="mt-5 space-y-4">
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
                    Registrasi Pasien
                </button>
            </form>
        </div>

        <!-- Area hasil proses -->
        <div class="rounded-xl border border-emerald-100 bg-white p-5 lg:col-span-2">
            <h2 class="text-base font-semibold text-slate-900">
                Hasil Registrasi
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Hasil verifikasi E-KTP dan penyimpanan data pasien akan tampil di sini.
            </p>

            <?php if ($error): ?>
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4">
                    <p class="text-sm font-semibold text-red-700">Registrasi gagal</p>
                    <p class="mt-1 text-sm text-red-600">
                        <?= htmlspecialchars($error) ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($result): ?>
                <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-sm font-semibold text-emerald-700">
                        Registrasi berhasil
                    </p>
                    <p class="mt-1 text-sm text-emerald-700">
                        <?= htmlspecialchars($result['message']) ?>
                    </p>
                </div>

                <div class="mt-5 overflow-hidden rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr>
                                <td class="w-40 px-4 py-3 font-medium text-slate-600">ID Pasien</td>
                                <td class="px-4 py-3 font-mono text-slate-900">
                                    #<?= htmlspecialchars($result['data']['id']) ?>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-600">NIK</td>
                                <td class="px-4 py-3 font-mono text-slate-900">
                                    <?= htmlspecialchars($result['data']['nik']) ?>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-600">Nama</td>
                                <td class="px-4 py-3 text-slate-900">
                                    <?= htmlspecialchars($result['data']['nama']) ?>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-600">Jenis Pasien</td>
                                <td class="px-4 py-3 text-slate-900">
                                    <?= htmlspecialchars($result['data']['jenis_pasien']) ?>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-600">Tarif</td>
                                <td class="px-4 py-3 text-slate-900">
                                    <?= htmlspecialchars($result['data']['tarif']) ?>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-600">Sumber Data</td>
                                <td class="px-4 py-3 text-slate-900">
                                    <?= htmlspecialchars($result['data']['sumber_data']) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    <a
                        href="/patients"
                        class="inline-flex rounded-lg border border-emerald-200 bg-white px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                    >
                        Lihat Data Pasien
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!$error && !$result): ?>
                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm text-slate-600">
                        Belum ada proses registrasi. Masukkan NIK pasien lalu klik tombol registrasi.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>