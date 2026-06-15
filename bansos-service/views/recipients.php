<?php
$db = getDatabaseConnection();

$stmt = $db->query("
    SELECT 
        id,
        nik,
        nama,
        status_ekonomi,
        jenis_bantuan,
        periode_bantuan,
        status_bansos,
        keterangan,
        created_at
    FROM recipients
    ORDER BY created_at DESC
");

$recipients = $stmt->fetchAll();
?>

<section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm font-medium text-amber-700">Data Bantuan Sosial</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-stone-900">
                Data Penerima Bansos
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">
                Daftar warga yang terdaftar sebagai penerima bantuan sosial berdasarkan status ekonomi dari E-KTP Service.
            </p>
        </div>

        <div class="rounded-lg border border-amber-100 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Total Penerima</p>
            <p class="mt-1 text-xl font-semibold text-stone-900"><?= count($recipients) ?></p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-amber-100 bg-white">
        <div class="border-b border-amber-100 px-5 py-4">
            <h2 class="text-base font-semibold text-stone-900">Tabel Penerima Bansos</h2>
            <p class="mt-1 text-sm text-stone-500">
                Data ini dapat digunakan oleh SPBU Service untuk mengecek status penerima bansos.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-amber-50/60">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">ID</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">NIK</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Nama</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Status Ekonomi</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Jenis Bantuan</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Periode</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Status Bansos</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Keterangan</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Tanggal Daftar</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-stone-200 bg-white">
                    <?php if (count($recipients) === 0): ?>
                        <tr>
                            <td colspan="9" class="px-5 py-6 text-center text-stone-500">
                                Belum ada data penerima bansos.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($recipients as $recipient): ?>
                        <tr class="hover:bg-amber-50/40">
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-stone-500">
                                #<?= htmlspecialchars($recipient['id']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-stone-700">
                                <?= htmlspecialchars($recipient['nik']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-medium text-stone-900">
                                <?= htmlspecialchars($recipient['nama']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <?php
                                    $statusEkonomi = $recipient['status_ekonomi'];
                                    $ekonomiClass = 'border-stone-200 bg-stone-50 text-stone-700';

                                    if ($statusEkonomi === 'kurang_mampu') {
                                        $ekonomiClass = 'border-amber-200 bg-amber-50 text-amber-700';
                                    } elseif ($statusEkonomi === 'rentan') {
                                        $ekonomiClass = 'border-blue-200 bg-blue-50 text-blue-700';
                                    }
                                ?>

                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium <?= $ekonomiClass ?>">
                                    <?= htmlspecialchars(str_replace('_', ' ', $statusEkonomi)) ?>
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-stone-700">
                                <?= htmlspecialchars($recipient['jenis_bantuan']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-stone-600">
                                <?= htmlspecialchars($recipient['periode_bantuan']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <?php if ($recipient['status_bansos'] === 'aktif'): ?>
                                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                                        Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="min-w-56 px-5 py-4 text-stone-600">
                                <?= htmlspecialchars($recipient['keterangan'] ?? '-') ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-stone-500">
                                <?= htmlspecialchars($recipient['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>