<?php
$app = require __DIR__ . '/../app/config/app.php';
$currentPath = getRequestPath();

$page = 'dashboard.php';
$pageTitle = 'Dashboard';

if ($currentPath === '/transactions') {
    $page = 'transactions.php';
    $pageTitle = 'Data Transaksi';
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
<body class="bg-red-50/30 text-zinc-800">
    <div class="min-h-screen">
        <aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-red-200 bg-zinc-950 px-6 py-6 md:block">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-red-300">
                    Layanan Energi
                </p>
                <h1 class="mt-2 text-lg font-semibold text-white">
                    SPBU Service
                </h1>
                <p class="mt-1 text-xs leading-5 text-zinc-400">
                    Transaksi BBM berbasis NIK.
                </p>
            </div>

            <nav class="mt-8 space-y-1">
                <a href="/"
                class="block rounded-lg px-3 py-2 text-sm font-medium <?= isActiveMenu('/', $currentPath) ? 'bg-white text-zinc-950' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white' ?>">
                    Dashboard
                </a>

                <a href="/transactions"
                class="block rounded-lg px-3 py-2 text-sm font-medium <?= isActiveMenu('/transactions', $currentPath) ? 'bg-white text-zinc-950' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white' ?>">
                    Data Transaksi
                </a>
            </nav>

            <div class="absolute bottom-6 left-6 right-6 rounded-lg border border-zinc-700 bg-zinc-900 p-4">
                <p class="text-xs font-medium text-zinc-400">Port Service</p>
                <p class="mt-1 font-mono text-sm text-white">localhost:8002</p>
            </div>
        </aside>

        <main class="md:pl-64">
            <header class="border-b border-red-100 bg-white px-6 py-4 md:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500">Sistem Integrasi Layanan Publik</p>
                        <p class="text-base font-semibold text-zinc-900">
                            <?= htmlspecialchars($pageTitle) ?>
                        </p>
                    </div>

                    <div class="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-medium text-red-800">
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