<?php
$db = getDatabaseConnection();

/**
 * =========================
 * 1. Ambil ringkasan umum
 * =========================
 */
$summary = $db->query("
    SELECT
        COUNT(*) AS total_warga,
        SUM(CASE WHEN status_aktif = 'aktif' THEN 1 ELSE 0 END) AS warga_aktif,
        SUM(CASE WHEN status_aktif = 'nonaktif' THEN 1 ELSE 0 END) AS warga_nonaktif,
        SUM(CASE WHEN status_ekonomi = 'kurang_mampu' THEN 1 ELSE 0 END) AS kurang_mampu,
        SUM(CASE WHEN status_ekonomi = 'rentan' THEN 1 ELSE 0 END) AS rentan,
        SUM(CASE WHEN kuota_bbm <= 5 THEN 1 ELSE 0 END) AS kuota_kritis
    FROM citizens
")->fetch();

$medicalTotal = $db->query("
    SELECT COUNT(*) AS total_rekam_medis
    FROM medical_records
")->fetch();

/**
 * ==========================================
 * 2. Grafik RSUD: rekam medis 6 bulan terakhir
 * ==========================================
 */
$medicalRows = $db->query("
    SELECT
        DATE_FORMAT(tanggal_periksa, '%Y-%m') AS periode,
        COUNT(*) AS total
    FROM medical_records
    WHERE tanggal_periksa >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
    GROUP BY DATE_FORMAT(tanggal_periksa, '%Y-%m')
    ORDER BY periode ASC
")->fetchAll();

$monthNames = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
    9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
];

$medicalMap = [];
$startMonth = new DateTime('first day of -5 months');

for ($i = 0; $i < 6; $i++) {
    $key = $startMonth->format('Y-m');
    $label = $monthNames[(int) $startMonth->format('n')] . ' ' . $startMonth->format('Y');

    $medicalMap[$key] = [
        'label' => $label,
        'total' => 0
    ];

    $startMonth->modify('+1 month');
}

foreach ($medicalRows as $row) {
    if (isset($medicalMap[$row['periode']])) {
        $medicalMap[$row['periode']]['total'] = (int) $row['total'];
    }
}

$rsudLabels = [];
$rsudData = [];

foreach ($medicalMap as $item) {
    $rsudLabels[] = $item['label'];
    $rsudData[] = $item['total'];
}

/**
 * ==========================================
 * 3. Grafik Bansos: distribusi status ekonomi
 * ==========================================
 */
$statusRows = $db->query("
    SELECT status_ekonomi, COUNT(*) AS total
    FROM citizens
    GROUP BY status_ekonomi
")->fetchAll();

$bansosMap = [
    'mampu' => 0,
    'rentan' => 0,
    'kurang_mampu' => 0
];

foreach ($statusRows as $row) {
    $status = $row['status_ekonomi'] ?? 'mampu';
    if (isset($bansosMap[$status])) {
        $bansosMap[$status] = (int) $row['total'];
    }
}

$bansosLabels = ['Mampu', 'Rentan', 'Kurang Mampu'];
$bansosData = [
    $bansosMap['mampu'],
    $bansosMap['rentan'],
    $bansosMap['kurang_mampu']
];

/**
 * ======================================
 * 4. Grafik SPBU: distribusi kuota BBM
 * ======================================
 */
$quota = $db->query("
    SELECT
        SUM(CASE WHEN kuota_bbm <= 5 THEN 1 ELSE 0 END) AS sangat_rendah,
        SUM(CASE WHEN kuota_bbm > 5 AND kuota_bbm <= 15 THEN 1 ELSE 0 END) AS rendah,
        SUM(CASE WHEN kuota_bbm > 15 AND kuota_bbm <= 30 THEN 1 ELSE 0 END) AS normal,
        SUM(CASE WHEN kuota_bbm > 30 THEN 1 ELSE 0 END) AS tinggi
    FROM citizens
")->fetch();

$spbuLabels = ['0-5 Liter', '6-15 Liter', '16-30 Liter', '> 30 Liter'];
$spbuData = [
    (int) ($quota['sangat_rendah'] ?? 0),
    (int) ($quota['rendah'] ?? 0),
    (int) ($quota['normal'] ?? 0),
    (int) ($quota['tinggi'] ?? 0)
];

/**
 * ======================================
 * 5. Ringkasan tambahan untuk dashboard
 * ======================================
 */
$totalWarga = (int) ($summary['total_warga'] ?? 0);
$wargaAktif = (int) ($summary['warga_aktif'] ?? 0);
$wargaNonaktif = (int) ($summary['warga_nonaktif'] ?? 0);
$kurangMampu = (int) ($summary['kurang_mampu'] ?? 0);
$rentan = (int) ($summary['rentan'] ?? 0);
$potensiBansos = $kurangMampu + $rentan;
$kuotaKritis = (int) ($summary['kuota_kritis'] ?? 0);
$totalRekamMedis = (int) ($medicalTotal['total_rekam_medis'] ?? 0);
?>

<section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">Pusat Integrasi Layanan Publik</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
                Dashboard E-KTP Service
            </h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                Dashboard ini menampilkan ringkasan pusat data warga serta grafik integrasi layanan RSUD, Bansos, dan SPBU berdasarkan data yang sudah masuk ke E-KTP Service.
            </p>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Service Utama
            </p>
            <p class="mt-1 text-lg font-semibold text-slate-900">
                E-KTP Service
            </p>
        </div>
    </div>

    <!-- Ringkasan utama -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Warga</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900"><?= $totalWarga ?></p>
            <p class="mt-2 text-sm text-slate-500">
                Seluruh data master warga pada E-KTP.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Warga Aktif</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900"><?= $wargaAktif ?></p>
            <p class="mt-2 text-sm text-slate-500">
                Warga yang dapat digunakan untuk proses layanan.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Rekam Medis Masuk</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900"><?= $totalRekamMedis ?></p>
            <p class="mt-2 text-sm text-slate-500">
                Riwayat medis yang dikirim dari RSUD Service.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Potensi Penerima Bansos</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900"><?= $potensiBansos ?></p>
            <p class="mt-2 text-sm text-slate-500">
                Warga dengan status ekonomi rentan atau kurang mampu.
            </p>
        </div>
    </div>

    <!-- Ringkasan tambahan -->
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Warga Nonaktif</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900"><?= $wargaNonaktif ?></p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Status Ekonomi Kurang Mampu</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900"><?= $kurangMampu ?></p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Kuota BBM Kritis (≤ 5 Liter)</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900"><?= $kuotaKritis ?></p>
        </div>
    </div>

    <!-- Grafik layanan -->
    <div class="grid gap-6 xl:grid-cols-3">
        <!-- Grafik RSUD -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 xl:col-span-1">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Grafik RSUD</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900">
                        Rekam Medis 6 Bulan Terakhir
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Menampilkan jumlah rekam medis yang dikirim dari RSUD ke E-KTP setiap bulan.
                    </p>
                </div>

                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                    RSUD
                </span>
            </div>

            <div class="mt-5 h-72">
                <canvas id="rsudChart"></canvas>
            </div>
        </div>

        <!-- Grafik Bansos -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 xl:col-span-1">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-amber-700">Grafik Bansos</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900">
                        Distribusi Status Ekonomi Warga
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Menampilkan dasar potensi kelayakan bansos berdasarkan status ekonomi pada data E-KTP.
                    </p>
                </div>

                <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
                    Bansos
                </span>
            </div>

            <div class="mt-5 h-72">
                <canvas id="bansosChart"></canvas>
            </div>
        </div>

        <!-- Grafik SPBU -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 xl:col-span-1">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-red-700">Grafik SPBU</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900">
                        Distribusi Kuota BBM Warga
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Menampilkan sebaran kuota BBM terkini warga yang sudah diperbarui oleh transaksi SPBU.
                    </p>
                </div>

                <span class="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-medium text-red-700">
                    SPBU
                </span>
            </div>

            <div class="mt-5 h-72">
                <canvas id="spbuChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Penjelasan dashboard -->
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-base font-semibold text-slate-900">
            Catatan Dashboard Integrasi
        </h2>

        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-emerald-700">RSUD</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Grafik RSUD berasal dari data <span class="font-medium">medical_records</span> yang dikirim dari RSUD ke E-KTP.
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-amber-700">Bansos</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Grafik Bansos menggunakan status ekonomi warga sebagai indikator potensi penerima bantuan sosial.
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-red-700">SPBU</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Grafik SPBU menggunakan data kuota BBM pada E-KTP yang telah diperbarui setiap selesai transaksi BBM.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Data grafik dari PHP
    const rsudLabels = <?= json_encode($rsudLabels) ?>;
    const rsudData = <?= json_encode($rsudData) ?>;

    const bansosLabels = <?= json_encode($bansosLabels) ?>;
    const bansosData = <?= json_encode($bansosData) ?>;

    const spbuLabels = <?= json_encode($spbuLabels) ?>;
    const spbuData = <?= json_encode($spbuData) ?>;

    // Grafik RSUD
    new Chart(document.getElementById('rsudChart'), {
        type: 'bar',
        data: {
            labels: rsudLabels,
            datasets: [{
                label: 'Jumlah Rekam Medis',
                data: rsudData,
                backgroundColor: 'rgba(16, 185, 129, 0.70)',
                borderColor: 'rgba(5, 150, 105, 1)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Grafik Bansos
    new Chart(document.getElementById('bansosChart'), {
        type: 'pie',
        data: {
            labels: bansosLabels,
            datasets: [{
                data: bansosData,
                backgroundColor: [
                    'rgba(148, 163, 184, 0.85)',
                    'rgba(245, 158, 11, 0.85)',
                    'rgba(234, 179, 8, 0.85)'
                ],
                borderColor: [
                    'rgba(100, 116, 139, 1)',
                    'rgba(217, 119, 6, 1)',
                    'rgba(202, 138, 4, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Grafik SPBU
    new Chart(document.getElementById('spbuChart'), {
        type: 'doughnut',
        data: {
            labels: spbuLabels,
            datasets: [{
                data: spbuData,
                backgroundColor: [
                    'rgba(239, 68, 68, 0.85)',
                    'rgba(249, 115, 22, 0.85)',
                    'rgba(59, 130, 246, 0.85)',
                    'rgba(16, 185, 129, 0.85)'
                ],
                borderColor: [
                    'rgba(220, 38, 38, 1)',
                    'rgba(234, 88, 12, 1)',
                    'rgba(37, 99, 235, 1)',
                    'rgba(5, 150, 105, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '58%',
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>