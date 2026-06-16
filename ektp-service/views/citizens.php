<?php
$db = getDatabaseConnection();
$success = null;
$error = null;

// Proses aktif/nonaktif warga
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;

    if (!$id) {
        $error = 'ID warga tidak valid.';
    } else {
        try {
            if ($action === 'deactivate') {
                $stmt = $db->prepare("UPDATE citizens SET status_aktif = 'nonaktif' WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Status warga berhasil dinonaktifkan.';
            }

            if ($action === 'activate') {
                $stmt = $db->prepare("UPDATE citizens SET status_aktif = 'aktif' WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Status warga berhasil diaktifkan kembali.';
            }
        } catch (Exception $exception) {
            $error = 'Gagal memperbarui status warga.';
        }
    }
}

$stmt = $db->query("
    SELECT id, nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, pekerjaan, status_ekonomi, kuota_bbm, status_aktif
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

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Warga</p>
                <p class="mt-1 text-xl font-semibold text-slate-900"><?= count($citizens) ?></p>
            </div>

            <a
                href="/citizens/create"
                class="inline-flex rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
            >
                Tambah Warga
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
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">
                    <?php if (count($citizens) === 0): ?>
                        <tr>
                            <td colspan="9" class="px-5 py-6 text-center text-slate-500">
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
                                    $badgeClass = 'bg-slate-100 text-slate-700 border border-slate-200';

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

                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <a
                                        href="/citizens/edit?id=<?= htmlspecialchars($citizen['id']) ?>"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Edit
                                    </a>

                                    <?php if ($citizen['status_aktif'] === 'aktif'): ?>
                                        <form method="POST" onsubmit="return confirm('Nonaktifkan warga ini?')">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($citizen['id']) ?>">
                                            <input type="hidden" name="action" value="deactivate">
                                            <button
                                                type="submit"
                                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100"
                                            >
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" onsubmit="return confirm('Aktifkan kembali warga ini?')">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($citizen['id']) ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button
                                                type="submit"
                                                class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100"
                                            >
                                                Aktifkan
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>