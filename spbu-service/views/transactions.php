<?php
$db = getDatabaseConnection();

$stmt = $db->query("
    SELECT 
        id,
        nik,
        nama,
        status_bansos,
        jenis_bbm,
        jumlah_liter,
        harga_per_liter,
        total_harga,
        kuota_sebelum,
        kuota_sesudah,
        keterangan,
        created_at
    FROM fuel_transactions
    ORDER BY created_at DESC
");

$transactions = $stmt->fetchAll();
?>

<section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm font-medium text-red-800">Data Transaksi</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900">
                Data Transaksi BBM
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-600">
                Daftar transaksi BBM yang telah diproses melalui verifikasi NIK, pengecekan bansos, dan update kuota ke E-KTP Service.
            </p>
        </div>

        <div class="rounded-lg border border-red-100 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Total Transaksi</p>
            <p class="mt-1 text-xl font-semibold text-zinc-900"><?= count($transactions) ?></p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-red-100 bg-white">
        <div class="border-b border-red-100 px-5 py-4">
            <h2 class="text-base font-semibold text-zinc-900">Tabel Transaksi BBM</h2>
            <p class="mt-1 text-sm text-zinc-500">
                Data ini menjadi bukti bahwa SPBU Service berkomunikasi dengan E-KTP dan Bansos Service.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-red-50/60">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">ID</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">NIK</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Nama</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Bansos</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">BBM</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Liter</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Harga/Liter</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Total</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Kuota</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Keterangan</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Waktu</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 bg-white">
                    <?php if (count($transactions) === 0): ?>
                        <tr>
                            <td colspan="11" class="px-5 py-6 text-center text-zinc-500">
                                Belum ada transaksi BBM.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($transactions as $transaction): ?>
                        <tr class="hover:bg-red-50/30">
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-zinc-500">
                                #<?= htmlspecialchars($transaction['id']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-zinc-700">
                                <?= htmlspecialchars($transaction['nik']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-medium text-zinc-900">
                                <?= htmlspecialchars($transaction['nama']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <?php if ($transaction['status_bansos'] === 'aktif'): ?>
                                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-medium text-zinc-700">
                                        Tidak Terdaftar
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-zinc-700">
                                <?= htmlspecialchars($transaction['jenis_bbm']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-zinc-700">
                                <?= htmlspecialchars($transaction['jumlah_liter']) ?> liter
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-zinc-700">
                                Rp <?= number_format((float) $transaction['harga_per_liter'], 0, ',', '.') ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-medium text-zinc-900">
                                Rp <?= number_format((float) $transaction['total_harga'], 0, ',', '.') ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-zinc-700">
                                <?= htmlspecialchars($transaction['kuota_sebelum']) ?>
                                →
                                <?= htmlspecialchars($transaction['kuota_sesudah']) ?>
                            </td>

                            <td class="min-w-64 px-5 py-4 text-zinc-600">
                                <?= htmlspecialchars($transaction['keterangan'] ?? '-') ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-zinc-500">
                                <?= htmlspecialchars($transaction['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>