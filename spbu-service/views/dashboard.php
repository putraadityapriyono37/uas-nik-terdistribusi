<section class="space-y-8">
    <div>
        <p class="text-sm font-medium text-red-800">Dashboard</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900">
            SPBU Service
        </h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-600">
            Layanan transaksi BBM berbasis verifikasi NIK, status bansos, dan kuota BBM dari E-KTP Service.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-red-100 bg-white p-5">
            <p class="text-sm text-zinc-500">Status Service</p>
            <p class="mt-2 text-xl font-semibold text-zinc-900">Aktif</p>
            <p class="mt-1 text-xs text-zinc-500">Berjalan pada port 8002</p>
        </div>

        <div class="rounded-xl border border-red-100 bg-white p-5">
            <p class="text-sm text-zinc-500">Integrasi</p>
            <p class="mt-2 text-xl font-semibold text-zinc-900">E-KTP + Bansos</p>
            <p class="mt-1 text-xs text-zinc-500">Verifikasi dan status penerima</p>
        </div>

        <div class="rounded-xl border border-red-100 bg-white p-5">
            <p class="text-sm text-zinc-500">Endpoint Utama</p>
            <p class="mt-2 text-xl font-semibold text-zinc-900">1 Endpoint</p>
            <p class="mt-1 text-xs text-zinc-500">Transaksi BBM</p>
        </div>
    </div>

    <div class="rounded-xl border border-red-100 bg-white">
        <div class="border-b border-red-100 px-5 py-4">
            <h2 class="text-base font-semibold text-zinc-900">Endpoint SPBU</h2>
            <p class="mt-1 text-sm text-zinc-500">Endpoint awal yang tersedia pada SPBU Service.</p>
        </div>

        <div class="grid gap-2 px-5 py-4 md:grid-cols-3">
            <div class="text-sm font-medium text-red-800">POST</div>
            <div class="font-mono text-sm text-zinc-700 md:col-span-2">
                /api/fuel-transaction
            </div>
        </div>
    </div>
</section>