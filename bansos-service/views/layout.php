<?php
$app = require __DIR__ . '/../app/config/app.php';

$currentPath = getRequestPath();

$page = 'dashboard.php';
$pageTitle = 'Dashboard';

if ($currentPath === '/recipients') {
    $page = 'recipients.php';
    $pageTitle = 'Data Penerima';
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

    <!-- Responsif untuk mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($app['name']) ?></title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-amber-50/40 text-stone-800">
    <div class="min-h-screen">

        <!-- Mobile Header -->
        <header class="sticky top-0 z-40 border-b border-amber-100 bg-white px-4 py-3 md:hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">
                        Layanan Sosial
                    </p>
                    <h1 class="text-base font-semibold text-stone-900">
                        Bansos Service
                    </h1>
                </div>

                <!-- Tombol hamburger mobile -->
                <button
                    type="button"
                    onclick="toggleMobileMenu()"
                    class="inline-flex items-center justify-center rounded-lg border border-amber-100 bg-white px-3 py-2 text-amber-800"
                    aria-label="Buka menu"
                >
                    <span class="text-lg leading-none">☰</span>
                </button>
            </div>
        </header>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden border-b border-amber-100 bg-stone-950 px-4 py-4 md:hidden">
            <nav class="space-y-1">
                <a href="/"
                   class="block rounded-lg px-3 py-2 text-sm font-medium <?= isActiveMenu('/', $currentPath) ? 'bg-amber-100 text-stone-950' : 'text-stone-300 hover:bg-stone-800 hover:text-white' ?>">
                    Dashboard
                </a>

                <a href="/recipients"
                   class="block rounded-lg px-3 py-2 text-sm font-medium <?= isActiveMenu('/recipients', $currentPath) ? 'bg-amber-100 text-stone-950' : 'text-stone-300 hover:bg-stone-800 hover:text-white' ?>">
                    Data Penerima
                </a>
            </nav>
        </div>

        <!-- Desktop Sidebar -->
        <aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-amber-200 bg-stone-950 px-6 py-6 md:block">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-300">
                    Layanan Sosial
                </p>
                <h1 class="mt-2 text-lg font-semibold text-white">
                    Bansos Service
                </h1>
                <p class="mt-1 text-xs leading-5 text-stone-300">
                    Penerima bantuan berbasis NIK.
                </p>
            </div>

            <nav class="mt-8 space-y-1">
                <a href="/"
                   class="block rounded-lg px-3 py-2 text-sm font-medium <?= isActiveMenu('/', $currentPath) ? 'bg-amber-100 text-stone-950' : 'text-stone-300 hover:bg-stone-800 hover:text-white' ?>">
                    Dashboard
                </a>

                <a href="/recipients"
                   class="block rounded-lg px-3 py-2 text-sm font-medium <?= isActiveMenu('/recipients', $currentPath) ? 'bg-amber-100 text-stone-950' : 'text-stone-300 hover:bg-stone-800 hover:text-white' ?>">
                    Data Penerima
                </a>
            </nav>

            <div class="absolute bottom-6 left-6 right-6 rounded-lg border border-stone-700 bg-stone-900 p-4">
                <p class="text-xs font-medium text-stone-400">Port Service</p>
                <p class="mt-1 font-mono text-sm text-white">localhost:8003</p>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="md:pl-64">
            <!-- Header desktop -->
            <header class="hidden border-b border-amber-100 bg-white px-6 py-4 md:block md:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-stone-500">Sistem Integrasi Layanan Publik</p>
                        <p class="text-base font-semibold text-stone-900">
                            <?= htmlspecialchars($pageTitle) ?>
                        </p>
                    </div>

                    <div class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
                        Service Aktif
                    </div>
                </div>
            </header>

            <!-- Konten halaman -->
            <div class="px-4 py-6 md:px-8 md:py-8">
                <?php require __DIR__ . '/' . $page; ?>
            </div>
        </main>
    </div>

    <!-- Toggle hamburger menu -->
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }
    </script>
</body>
</html>