<?php
$db = getDatabaseConnection();

$stmt = $db->query("
    SELECT 
        id,
        service_name,
        endpoint,
        method,
        nik,
        status,
        message,
        created_at
    FROM audit_logs
    ORDER BY created_at DESC
");

$auditLogs = $stmt->fetchAll();
?>

<section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">Monitoring Integrasi</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
                Audit Log E-KTP
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Riwayat request yang masuk ke E-KTP dari proses verifikasi NIK, cek status warga, rekam medis, dan update kuota BBM.
            </p>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Log</p>
            <p class="mt-1 text-xl font-semibold text-slate-900"><?= count($auditLogs) ?></p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Tabel Audit Log</h2>
            <p class="mt-1 text-sm text-slate-500">
                Data ini digunakan untuk memantau aktivitas komunikasi antar-service.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">ID</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Service</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Method</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Endpoint</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">NIK</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Pesan</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600">Waktu</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">
                    <?php if (count($auditLogs) === 0): ?>
                        <tr>
                            <td colspan="8" class="px-5 py-6 text-center text-slate-500">
                                Belum ada audit log.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($auditLogs as $log): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-500">
                                #<?= htmlspecialchars($log['id']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    <?= htmlspecialchars($log['service_name']) ?>
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <?php
                                    $method = $log['method'];
                                    $methodClass = 'border-slate-200 bg-slate-50 text-slate-700';

                                    if ($method === 'GET') {
                                        $methodClass = 'border-blue-200 bg-blue-50 text-blue-700';
                                    } elseif ($method === 'POST') {
                                        $methodClass = 'border-emerald-200 bg-emerald-50 text-emerald-700';
                                    } elseif ($method === 'PUT') {
                                        $methodClass = 'border-amber-200 bg-amber-50 text-amber-700';
                                    }
                                ?>

                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold <?= $methodClass ?>">
                                    <?= htmlspecialchars($method) ?>
                                </span>
                            </td>

                            <td class="min-w-56 px-5 py-4 font-mono text-xs text-slate-700">
                                <?= htmlspecialchars($log['endpoint']) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-600">
                                <?= htmlspecialchars($log['nik'] ?? '-') ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <?php
                                    $status = $log['status'];
                                    $statusClass = 'border-slate-200 bg-slate-50 text-slate-700';

                                    if ($status === 'success') {
                                        $statusClass = 'border-emerald-200 bg-emerald-50 text-emerald-700';
                                    } elseif ($status === 'failed') {
                                        $statusClass = 'border-red-200 bg-red-50 text-red-700';
                                    } elseif ($status === 'not_found') {
                                        $statusClass = 'border-amber-200 bg-amber-50 text-amber-700';
                                    } elseif ($status === 'inactive') {
                                        $statusClass = 'border-orange-200 bg-orange-50 text-orange-700';
                                    }
                                ?>

                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium <?= $statusClass ?>">
                                    <?= htmlspecialchars(str_replace('_', ' ', $status)) ?>
                                </span>
                            </td>

                            <td class="min-w-64 px-5 py-4 text-slate-600">
                                <?= htmlspecialchars($log['message'] ?? '-') ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                                <?= htmlspecialchars($log['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>