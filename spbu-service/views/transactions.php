<?php
$db = getDatabaseConnection();

$success = null;
$error = null;

// Proses edit keterangan dan hapus transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;

    if (!$id) {
        $error = 'ID transaksi tidak valid.';
    } else {
        $stmt = $db->prepare("SELECT * FROM fuel_transactions WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            $error = 'Data transaksi tidak ditemukan.';
        } else {
            try {
                if ($action === 'update_note') {
                    $keterangan = $_POST['keterangan'] ?? '-';

                    // Update hanya keterangan agar kuota tetap konsisten
                    $updateStmt = $db->prepare("
                        UPDATE fuel_transactions
                        SET keterangan = ?
                        WHERE id = ?
                    ");

                    $updateStmt->execute([
                        $keterangan,
                        $id
                    ]);

                    $success = 'Keterangan transaksi berhasil diperbarui.';
                }

                if ($action === 'delete') {
                    // Hapus riwayat lokal, kuota E-KTP tidak dikembalikan otomatis
                    $deleteStmt = $db->prepare("DELETE FROM fuel_transactions WHERE id = ?");
                    $deleteStmt->execute([$id]);

                    $success = 'Riwayat transaksi berhasil dihapus dari SPBU.';
                }
            } catch (Exception $exception) {
                $error = 'Gagal memproses data transaksi.';
            }
        }
    }
}

$stmt = $db->query("
    SELECT id, nik, nama, status_bansos, jenis_bbm, jumlah_liter, harga_per_liter, total_harga, kuota_sebelum, kuota_sesudah, keterangan, created_at
    FROM fuel_transactions
    ORDER BY id DESC
");

$transactions = $stmt->fetchAll();

$totalLiter = 0;
$totalPendapatan = 0;

foreach ($transactions as $transaction) {
    $totalLiter += (float) $transaction['jumlah_liter'];
    $totalPendapatan += (float) $transaction['total_harga'];
}
?>

<section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm font-medium text-red-800">Data BBM</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900">
                Data Transaksi SPBU
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-600">
                Riwayat transaksi BBM yang telah memvalidasi NIK, mengecek bansos, dan memperbarui kuota E-KTP.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="rounded-lg border border-red-100 bg-white px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Total Transaksi</p>
                <p class="mt-1 text-xl font-semibold text-zinc-900"><?= count($transactions) ?></p>
            </div>

            <div class="rounded-lg border border-red-100 bg-white px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Total Liter</p>
                <p class="mt-1 text-xl font-semibold text-zinc-900"><?= number_format($totalLiter, 1, ',', '.') ?></p>
            </div>

            <a
                href="/fuel-transaction"
                class="inline-flex rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800"
            >
                Transaksi BBM
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm font-semibold text-emerald-700">Berhasil</p>
            <p class="mt-1 text-sm text-emerald-700"><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-semibold text-red-700">Gagal</p>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <div class="rounded-xl border border-red-100 bg-white p-5">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Total Pendapatan</p>
                <p class="mt-1 text-xl font-semibold text-zinc-900">
                    Rp <?= number_format($totalPendapatan, 0, ',', '.') ?>
                </p>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Integrasi</p>
                <p class="mt-1 text-sm font-semibold text-zinc-900">
                    E-KTP + Bansos
                </p>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Catatan CRUD</p>
                <p class="mt-1 text-sm font-semibold text-zinc-900">
                    Edit hanya keterangan
                </p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-red-100 bg-white">
        <div class="border-b border-red-100 px-5 py-4">
            <h2 class="text-base font-semibold text-zinc-900">Tabel Transaksi</h2>
            <p class="mt-1 text-sm text-zinc-500">
                Hapus transaksi hanya menghapus riwayat lokal SPBU dan tidak mengembalikan kuota E-KTP.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-red-50/60">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">No</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">NIK</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Nama</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">BBM</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Liter</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Harga/Liter</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Total</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Bansos</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Kuota</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Keterangan</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Tanggal</th>
                        <th class="px-5 py-3 text-left font-semibold text-zinc-600">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 bg-white">
                    <?php if (count($transactions) === 0): ?>
                        <tr>
                            <td colspan="12" class="px-5 py-6 text-center text-zinc-500">
                                Belum ada data transaksi.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php $no = 1; ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr class="hover:bg-red-50/30">
                            <td class="whitespace-nowrap px-5 py-4 text-zinc-600">
                                <?= $no++ ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-zinc-700">
                                <?= htmlspecialchars($transaction['nik']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-medium text-zinc-900">
                                <?= htmlspecialchars($transaction['nama']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-zinc-600">
                                <?= htmlspecialchars($transaction['jenis_bbm']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-zinc-600">
                                <?= htmlspecialchars($transaction['jumlah_liter']) ?> L
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-zinc-600">
                                Rp <?= number_format($transaction['harga_per_liter'], 0, ',', '.') ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-medium text-zinc-800">
                                Rp <?= number_format($transaction['total_harga'], 0, ',', '.') ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <?php if ($transaction['status_bansos'] === 'aktif'): ?>
                                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-medium text-zinc-700">
                                        Tidak Aktif
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-zinc-600">
                                <?= htmlspecialchars($transaction['kuota_sebelum']) ?> L
                                <span class="text-zinc-400">→</span>
                                <?= htmlspecialchars($transaction['kuota_sesudah']) ?> L
                            </td>

                            <td class="min-w-64 px-5 py-4 text-zinc-600">
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($transaction['id']) ?>">
                                    <input type="hidden" name="action" value="update_note">

                                    <input
                                        type="text"
                                        name="keterangan"
                                        value="<?= htmlspecialchars($transaction['keterangan'] ?? '-') ?>"
                                        class="w-44 rounded-lg border border-zinc-200 px-3 py-1.5 text-xs outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100"
                                    >

                                    <button
                                        type="submit"
                                        class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100"
                                    >
                                        Simpan
                                    </button>
                                </form>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-zinc-500">
                                <?= htmlspecialchars($transaction['created_at']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <form method="POST" onsubmit="return confirm('Hapus riwayat transaksi ini? Kuota E-KTP tidak akan dikembalikan.')">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($transaction['id']) ?>">
                                    <input type="hidden" name="action" value="delete">

                                    <button
                                        type="submit"
                                        class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100"
                                    >
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>