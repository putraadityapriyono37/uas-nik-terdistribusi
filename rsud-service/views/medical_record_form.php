<?php
$app = require __DIR__ . '/../app/config/app.php';
$db = getDatabaseConnection();

$error = null;
$success = null;
$selectedPatient = null;

// Ambil daftar pasien untuk pilihan cepat
$patientStmt = $db->query("
    SELECT id, nik, nama, jenis_pasien, tarif
    FROM patients
    ORDER BY nama ASC
");

$patients = $patientStmt->fetchAll();

// Proses kirim rekam medis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = $_POST['nik'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $tindakan = $_POST['tindakan'] ?? '';
    $obat = $_POST['obat'] ?? '';
    $rumahSakit = 'RSUD Service';
    $tanggalPeriksa = $_POST['tanggal_periksa'] ?? date('Y-m-d');

    if (!preg_match('/^[0-9]{16}$/', $nik)) {
        $error = 'Format NIK harus 16 digit angka.';
    } elseif (trim($diagnosis) === '') {
        $error = 'Diagnosis wajib diisi.';
    } elseif (trim($tindakan) === '') {
        $error = 'Tindakan wajib diisi.';
    } elseif (trim($obat) === '') {
        $error = 'Obat wajib diisi.';
    } else {
        // Pastikan pasien sudah terdaftar di RSUD
        $checkStmt = $db->prepare("SELECT * FROM patients WHERE nik = ? LIMIT 1");
        $checkStmt->execute([$nik]);
        $selectedPatient = $checkStmt->fetch();

        if (!$selectedPatient) {
            $error = 'Pasien belum terdaftar di RSUD. Registrasikan pasien terlebih dahulu.';
        } else {
            // Kirim rekam medis ke E-KTP Service
            $url = $app['ektp_base_url'] . '/api/medical-record';

            $payload = [
                'nik' => $nik,
                'diagnosis' => $diagnosis,
                'tindakan' => $tindakan,
                'obat' => $obat,
                'rumah_sakit' => $rumahSakit,
                'tanggal_periksa' => $tanggalPeriksa
            ];

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => json_encode($payload)
            ]);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);

            curl_close($ch);

            if ($curlError) {
                $error = 'Gagal menghubungi E-KTP Service. Pastikan E-KTP berjalan di localhost:8000.';
            } else {
                $decodedResponse = json_decode($response, true);

                if ($decodedResponse && ($decodedResponse['success'] ?? false)) {
                    $success = [
                        'message' => 'Rekam medis berhasil dikirim dan disimpan di E-KTP Service.',
                        'patient' => $selectedPatient,
                        'record' => $payload
                    ];
                } else {
                    $error = $decodedResponse['message'] ?? 'Gagal menyimpan rekam medis di E-KTP Service.';
                }
            }
        }
    }
}
?>

<section class="space-y-6">
    <div>
        <p class="text-sm font-medium text-emerald-700">Rekam Medis Digital</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
            Kirim Rekam Medis ke E-KTP
        </h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
            Petugas RSUD mengisi diagnosis, tindakan, dan obat. Data rekam medis dikirim ke E-KTP Service agar riwayat kesehatan warga tersimpan terpusat berdasarkan NIK.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Form rekam medis -->
        <div class="rounded-xl border border-emerald-100 bg-white p-5 lg:col-span-1">
            <h2 class="text-base font-semibold text-slate-900">
                Form Rekam Medis
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Pilih pasien yang sudah terdaftar di RSUD.
            </p>

            <form method="POST" class="mt-5 space-y-4">
                <div>
                    <label for="nik" class="block text-sm font-medium text-slate-700">
                        Pasien / NIK
                    </label>

                    <select
                        id="nik"
                        name="nik"
                        class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                        required
                    >
                        <option value="">Pilih pasien</option>
                        <?php foreach ($patients as $patient): ?>
                            <option
                                value="<?= htmlspecialchars($patient['nik']) ?>"
                                <?= ($_POST['nik'] ?? '') === $patient['nik'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($patient['nama']) ?> - <?= htmlspecialchars($patient['nik']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="tanggal_periksa" class="block text-sm font-medium text-slate-700">
                        Tanggal Periksa
                    </label>
                    <input
                        type="date"
                        id="tanggal_periksa"
                        name="tanggal_periksa"
                        value="<?= htmlspecialchars($_POST['tanggal_periksa'] ?? date('Y-m-d')) ?>"
                        class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                        required
                    >
                </div>

                <div>
                    <label for="diagnosis" class="block text-sm font-medium text-slate-700">
                        Diagnosis
                    </label>
                    <textarea
                        id="diagnosis"
                        name="diagnosis"
                        rows="3"
                        placeholder="Contoh: Demam tinggi dan infeksi saluran pernapasan"
                        class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                        required
                    ><?= htmlspecialchars($_POST['diagnosis'] ?? '') ?></textarea>
                </div>

                <div>
                    <label for="tindakan" class="block text-sm font-medium text-slate-700">
                        Tindakan
                    </label>
                    <textarea
                        id="tindakan"
                        name="tindakan"
                        rows="3"
                        placeholder="Contoh: Pemeriksaan fisik dan pemberian terapi awal"
                        class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                        required
                    ><?= htmlspecialchars($_POST['tindakan'] ?? '') ?></textarea>
                </div>

                <div>
                    <label for="obat" class="block text-sm font-medium text-slate-700">
                        Obat
                    </label>
                    <textarea
                        id="obat"
                        name="obat"
                        rows="2"
                        placeholder="Contoh: Paracetamol, Amoxicillin"
                        class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                        required
                    ><?= htmlspecialchars($_POST['obat'] ?? '') ?></textarea>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800"
                >
                    Kirim Rekam Medis
                </button>
            </form>
        </div>

        <!-- Hasil pengiriman -->
        <div class="space-y-5 lg:col-span-2">
            <?php if ($error): ?>
                <div class="rounded-xl border border-red-200 bg-red-50 p-5">
                    <p class="text-sm font-semibold text-red-700">Pengiriman gagal</p>
                    <p class="mt-1 text-sm text-red-600">
                        <?= htmlspecialchars($error) ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-sm font-semibold text-emerald-700">
                        Pengiriman berhasil
                    </p>
                    <p class="mt-1 text-sm text-emerald-700">
                        <?= htmlspecialchars($success['message']) ?>
                    </p>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Nama Pasien</p>
                            <p class="mt-1 font-semibold text-slate-900">
                                <?= htmlspecialchars($success['patient']['nama']) ?>
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">NIK</p>
                            <p class="mt-1 font-mono text-sm font-semibold text-slate-900">
                                <?= htmlspecialchars($success['patient']['nik']) ?>
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-4 md:col-span-2">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Diagnosis</p>
                            <p class="mt-1 text-sm text-slate-800">
                                <?= htmlspecialchars($success['record']['diagnosis']) ?>
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tindakan</p>
                            <p class="mt-1 text-sm text-slate-800">
                                <?= htmlspecialchars($success['record']['tindakan']) ?>
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Obat</p>
                            <p class="mt-1 text-sm text-slate-800">
                                <?= htmlspecialchars($success['record']['obat']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                        <a
                            href="/medical-record"
                            class="inline-flex rounded-lg border border-emerald-200 bg-white px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                        >
                            Input Rekam Medis Lagi
                        </a>

                        <a
                            href="http://localhost:8000/medical-records"
                            target="_blank"
                            class="inline-flex rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800"
                        >
                            Lihat di E-KTP
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="rounded-xl border border-emerald-100 bg-white p-5">
                <h2 class="text-base font-semibold text-slate-900">
                    Alur Integrasi Rekam Medis
                </h2>

                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Step 1
                        </p>
                        <p class="mt-1 text-sm font-medium text-slate-900">
                            RSUD memilih pasien berdasarkan NIK.
                        </p>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Step 2
                        </p>
                        <p class="mt-1 text-sm font-medium text-slate-900">
                            RSUD mengirim diagnosis, tindakan, dan obat.
                        </p>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Step 3
                        </p>
                        <p class="mt-1 text-sm font-medium text-slate-900">
                            E-KTP menyimpan riwayat medis berdasarkan NIK.
                        </p>
                    </div>
                </div>

                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Endpoint Tujuan
                    </p>
                    <p class="mt-1 font-mono text-sm text-slate-800">
                        POST localhost:8000/api/medical-record
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>