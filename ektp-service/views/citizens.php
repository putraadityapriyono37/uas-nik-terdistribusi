<?php
$db = getDatabaseConnection();

$stmt = $db->query("
    SELECT nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, pekerjaan, status_ekonomi, kuota_bbm, status_aktif
    FROM citizens
    ORDER BY id ASC
");

$citizens = $stmt->fetchAll();
?>

<section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">Data Master</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
                Data Warga E-KTP
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Daftar warga yang tersimpan pada database E-KTP dan digunakan sebagai sumber verifikasi NIK oleh service lain.
            </p>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Warga</p>
            <p class="mt-1 text-xl font-semibold text-slate-900"><?= count($citizens) ?></p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Tabel Warga</h2>
            <p class="mt-1 text-sm text-slate-500">
                Data ini akan digunakan oleh endpoint verifikasi NIK dan status warga.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">NIK</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Nama</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">TTL</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">JK</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Pekerjaan</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Ekonomi</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Kuota BBM</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">
                    <?php if (count($citizens) === 0): ?>
                        <tr>
                            <td colspan="8" class="px-5 py-6 text-center text-slate-500">
                                Belum ada data warga.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($citizens as $citizen): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-700">
                                <?= htmlspecialchars($citizen['nik']) ?>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-900">
                                <?= htmlspecialchars($citizen['nama']) ?>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                <?= htmlspecialchars($citizen['tempat_lahir']) ?>,
                                <?= htmlspecialchars($citizen['tanggal_lahir']) ?>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                <?= htmlspecialchars($citizen['jenis_kelamin']) ?>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                <?= htmlspecialchars($citizen['pekerjaan'] ?? '-') ?>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <?php
                                    $statusEkonomi = $citizen['status_ekonomi'];
                                    $badgeClass = 'bg-slate-100 text-slate-700';

                                    if ($statusEkonomi === 'kurang_mampu') {
                                        $badgeClass = 'bg-amber-50 text-amber-700 border border-amber-200';
                                    } elseif ($statusEkonomi === 'rentan') {
                                        $badgeClass = 'bg-blue-50 text-blue-700 border border-blue-200';
                                    } elseif ($statusEkonomi === 'mampu') {
                                        $badgeClass = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                    }
                                ?>

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= $badgeClass ?>">
                                    <?= htmlspecialchars(str_replace('_', ' ', $statusEkonomi)) ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                <?= htmlspecialchars($citizen['kuota_bbm']) ?> liter
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <?php if ($citizen['status_aktif'] === 'aktif'): ?>
                                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                                        Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>