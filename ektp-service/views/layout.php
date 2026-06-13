<?php
$app = require __DIR__ . '/../app/config/app.php';

$currentPath = getRequestPath();

$page = 'dashboard.php';
$pageTitle = 'Dashboard';

if ($currentPath === '/citizens') {
    $page = 'citizens.php';
    $pageTitle = 'Data Warga';
}

if ($currentPath === '/medical-records') {
    $page = 'medical_records.php';
    $pageTitle = 'Rekam Medis';
}

function isActiveMenu($path, $currentPath)
{
    if ($path === '/' && $currentPath === '/') {
        return true;
    }

    return $path !== '/' && strpos($currentPath, $path) === 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($app['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
    <div class="min-h-screen">
        <aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-slate-200 bg-white px-6 py-6 md:block">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                    Layanan NIK
                </p>
                <h1 class="mt-2 text-lg font-semibold text-slate-900">
                    E-KTP Service
                </h1>
                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Pusat verifikasi identitas warga.
                </p>
            </div>

            <nav class="mt-8 space-y-1">
                <a href="/"
                   class="block rounded-lg px-3 py-2 text-sm font-medium <?= isActiveMenu('/', $currentPath) ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-100' ?>">
                    Dashboard
                </a>

                <a href="/citizens"
                   class="block rounded-lg px-3 py-2 text-sm font-medium <?= isActiveMenu('/citizens', $currentPath) ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-100' ?>">
                    Data Warga
                </a>
                
                <a href="/medical-records"
                    class="block rounded-lg px-3 py-2 text-sm font-medium <?= isActiveMenu('/medical-records', $currentPath) ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-100' ?>">
                    Rekam Medis
                </a>

                <a href="/api/verify-nik/3302010101010001"
                   class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                    Tes Verifikasi NIK
                </a>

                <a href="/api/citizen-status/3302010101010001"
                   class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                    Tes Status Warga
                </a>
            </nav>

            <div class="absolute bottom-6 left-6 right-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-medium text-slate-500">Port Service</p>
                <p class="mt-1 font-mono text-sm text-slate-900">localhost:8000</p>
            </div>
        </aside>

        <main class="md:pl-64">
            <header class="border-b border-slate-200 bg-white px-6 py-4 md:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Sistem Integrasi Layanan Publik</p>
                        <p class="text-base font-semibold text-slate-900">
                            <?= htmlspecialchars($pageTitle) ?>
                        </p>
                    </div>

                    <div class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                        Service Aktif
                    </div>
                </div>
            </header>

            <div class="px-6 py-8 md:px-8">
                <?php require __DIR__ . '/' . $page; ?>
            </div>
        </main>
    </div>
</body>
</html>