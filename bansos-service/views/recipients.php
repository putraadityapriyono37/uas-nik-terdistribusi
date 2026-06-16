<?php
$db = getDatabaseConnection();

$success = null;
$error = null;

// Proses update status dan hapus penerima
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;

    if (!$id) {
        $error = 'ID penerima tidak valid.';
    } else {
        $stmt = $db->prepare("SELECT * FROM recipients WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $recipient = $stmt->fetch();

        if (!$recipient) {
            $error = 'Data penerima tidak ditemukan.';
        } else {
            try {
                if ($action === 'activate') {
                    $updateStmt = $db->prepare("UPDATE recipients SET status_bansos = 'aktif' WHERE id = ?");
                    $updateStmt->execute([$id]);
                    $success = 'Status penerima berhasil diaktifkan.';
                }

                if ($action === 'deactivate') {
                    $updateStmt = $db->prepare("UPDATE recipients SET status_bansos = 'nonaktif' WHERE id = ?");
                    $updateStmt->execute([$id]);
                    $success = 'Status penerima berhasil dinonaktifkan.';
                }

                if ($action === 'delete') {
                    $deleteStmt = $db->prepare("DELETE FROM recipients WHERE id = ?");
                    $deleteStmt->execute([$id]);
                    $success = 'Data penerima berhasil dihapus.';
                }
            } catch (Exception $exception) {
                $error = 'Gagal memproses data penerima.';
            }
        }
    }
}

$stmt = $db->query("
    SELECT id, nik, nama, status_ekonomi, jenis_bantuan, status_bansos, created_at
    FROM recipients
    ORDER BY id DESC
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
                Daftar penerima bantuan sosial yang telah divalidasi berdasarkan status ekonomi dari E-KTP Service.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="rounded-lg border border-amber-100 bg-white px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Total Penerima</p>
                <p class="mt-1 text-xl font-semibold text-stone-900"><?= count($recipients) ?></p>
            </div>

            <a
                href="/register-recipient"
                class="inline-flex rounded-lg bg-amber-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-800"
            >
                Registrasi Penerima
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

    <div class="overflow-hidden rounded-xl border border-amber-100 bg-white">
        <div class="border-b border-amber-100 px-5 py-4">
            <h2 class="text-base font-semibold text-stone-900">Tabel Penerima</h2>
            <p class="mt-1 text-sm text-stone-500">
                Status aktif digunakan oleh service RSUD dan SPBU untuk menentukan subsidi atau tarif khusus.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-amber-50/70">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">No</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">NIK</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Nama</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Ekonomi</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Jenis Bantuan</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Status</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Tanggal Daftar</th>
                        <th class="px-5 py-3 text-left font-semibold text-stone-600">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-stone-200 bg-white">
                    <?php if (count($recipients) === 0): ?>
                        <tr>
                            <td colspan="8" class="px-5 py-6 text-center text-stone-500">
                                Belum ada data penerima bansos.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php $no = 1; ?>
                    <?php foreach ($recipients as $recipient): ?>
                        <tr class="hover:bg-amber-50/30">
                            <td class="whitespace-nowrap px-5 py-4 text-stone-600">
                                <?= $no++ ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-stone-700">
                                <?= htmlspecialchars($recipient['nik']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-medium text-stone-900">
                                <?= htmlspecialchars($recipient['nama']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                    <?= htmlspecialchars(str_replace('_', ' ', $recipient['status_ekonomi'])) ?>
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-stone-600">
                                <?= htmlspecialchars($recipient['jenis_bantuan']) ?>
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

                            <td class="whitespace-nowrap px-5 py-4 text-stone-500">
                                <?= htmlspecialchars($recipient['created_at']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex flex-col gap-2">
                                    <?php if ($recipient['status_bansos'] === 'aktif'): ?>
                                        <form method="POST" onsubmit="return confirm('Nonaktifkan penerima ini?')">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($recipient['id']) ?>">
                                            <input type="hidden" name="action" value="deactivate">

                                            <button
                                                type="submit"
                                                class="w-full rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100"
                                            >
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" onsubmit="return confirm('Aktifkan penerima ini?')">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($recipient['id']) ?>">
                                            <input type="hidden" name="action" value="activate">

                                            <button
                                                type="submit"
                                                class="w-full rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100"
                                            >
                                                Aktifkan
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" onsubmit="return confirm('Hapus data penerima ini?')">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($recipient['id']) ?>">
                                        <input type="hidden" name="action" value="delete">

                                        <button
                                            type="submit"
                                            class="w-full rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>