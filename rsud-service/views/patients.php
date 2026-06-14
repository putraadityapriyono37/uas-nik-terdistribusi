<?php
$db = getDatabaseConnection();

$stmt = $db->query("
    SELECT 
        id,
        nik,
        nama,
        tempat_lahir,
        tanggal_lahir,
        jenis_kelamin,
        alamat,
        pekerjaan,
        jenis_pasien,
        tarif,
        created_at
    FROM patients
    ORDER BY created_at DESC
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

        <div class="rounded-lg border border-emerald-100 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Pasien</p>
            <p class="mt-1 text-xl font-semibold text-slate-900"><?= count($patients) ?></p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-emerald-100 bg-white">
        <div class="border-b border-emerald-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Tabel Pasien</h2>
            <p class="mt-1 text-sm text-slate-500">
                Data pasien bersumber dari hasil response E-KTP saat proses registrasi.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-emerald-50/60">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">ID</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">NIK</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Nama</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">TTL</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">JK</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Pekerjaan</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Jenis Pasien</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Tarif</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Tanggal Registrasi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">
                    <?php if (count($patients) === 0): ?>
                        <tr>
                            <td colspan="9" class="px-5 py-6 text-center text-slate-500">
                                Belum ada data pasien.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($patients as $patient): ?>
                        <tr class="hover:bg-emerald-50/40">
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-500">
                                #<?= htmlspecialchars($patient['id']) ?>
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
                                    $badgeClass = 'border-slate-200 bg-slate-50 text-slate-700';

                                    if ($jenisPasien === 'kurang_mampu') {
                                        $badgeClass = 'border-amber-200 bg-amber-50 text-amber-700';
                                    } elseif ($jenisPasien === 'bansos') {
                                        $badgeClass = 'border-blue-200 bg-blue-50 text-blue-700';
                                    } elseif ($jenisPasien === 'umum') {
                                        $badgeClass = 'border-emerald-200 bg-emerald-50 text-emerald-700';
                                    }
                                ?>

                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium <?= $badgeClass ?>">
                                    <?= htmlspecialchars(str_replace('_', ' ', $jenisPasien)) ?>
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-700">
                                <?= htmlspecialchars($patient['tarif']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                                <?= htmlspecialchars($patient['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>