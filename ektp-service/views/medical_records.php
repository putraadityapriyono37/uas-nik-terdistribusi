<?php
$db = getDatabaseConnection();

// Ambil data rekam medis terbaru di atas
$stmt = $db->query("
    SELECT 
        medical_records.id,
        medical_records.nik,
        citizens.nama,
        medical_records.diagnosis,
        medical_records.tindakan,
        medical_records.obat,
        medical_records.rumah_sakit,
        medical_records.tanggal_periksa,
        medical_records.created_at
    FROM medical_records
    LEFT JOIN citizens ON citizens.nik = medical_records.nik
    ORDER BY medical_records.id DESC
");

$medicalRecords = $stmt->fetchAll();
?>

<section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">Integrasi RSUD</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
                Rekam Medis Warga
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Data rekam medis yang dikirim oleh RSUD ke pusat E-KTP melalui endpoint API.
            </p>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Total Rekam Medis
            </p>
            <p class="mt-1 text-xl font-semibold text-slate-900">
                <?= count($medicalRecords) ?>
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">
                Tabel Rekam Medis
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Riwayat ini menjadi bukti komunikasi data antara RSUD Service dan E-KTP Service.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">No</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">NIK</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Nama</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Diagnosis</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Tindakan</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Obat</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Rumah Sakit</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Tanggal</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">
                    <?php if (count($medicalRecords) === 0): ?>
                        <tr>
                            <td colspan="8" class="px-5 py-6 text-center text-slate-500">
                                Belum ada data rekam medis.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php $no = 1; ?>
                    <?php foreach ($medicalRecords as $record): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                <?= $no++ ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-700">
                                <?= htmlspecialchars($record['nik']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-900">
                                <?= htmlspecialchars($record['nama'] ?? '-') ?>
                            </td>

                            <td class="min-w-48 px-5 py-4 text-slate-700">
                                <?= htmlspecialchars($record['diagnosis']) ?>
                            </td>

                            <td class="min-w-56 px-5 py-4 text-slate-700">
                                <?= htmlspecialchars($record['tindakan']) ?>
                            </td>

                            <td class="min-w-40 px-5 py-4 text-slate-700">
                                <?= htmlspecialchars($record['obat']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                <?= htmlspecialchars($record['rumah_sakit']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                <?= htmlspecialchars($record['tanggal_periksa']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>