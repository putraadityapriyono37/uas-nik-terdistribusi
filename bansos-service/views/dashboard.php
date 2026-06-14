<section class="space-y-8">
    <div>
        <p class="text-sm font-medium text-amber-700">Dashboard</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-stone-900">
            Bansos Service
        </h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">
            Layanan pendaftaran dan pengecekan penerima bantuan sosial berbasis NIK dan status ekonomi dari E-KTP Service.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-amber-100 bg-white p-5">
            <p class="text-sm text-stone-500">Status Service</p>
            <p class="mt-2 text-xl font-semibold text-stone-900">Aktif</p>
            <p class="mt-1 text-xs text-stone-500">Berjalan pada port 8003</p>
        </div>

        <div class="rounded-xl border border-amber-100 bg-white p-5">
            <p class="text-sm text-stone-500">Integrasi</p>
            <p class="mt-2 text-xl font-semibold text-stone-900">E-KTP</p>
            <p class="mt-1 text-xs text-stone-500">Cek status ekonomi warga</p>
        </div>

        <div class="rounded-xl border border-amber-100 bg-white p-5">
            <p class="text-sm text-stone-500">Endpoint Utama</p>
            <p class="mt-2 text-xl font-semibold text-stone-900">2 Endpoint</p>
            <p class="mt-1 text-xs text-stone-500">Registrasi dan status bansos</p>
        </div>
    </div>

    <div class="rounded-xl border border-amber-100 bg-white">
        <div class="border-b border-amber-100 px-5 py-4">
            <h2 class="text-base font-semibold text-stone-900">Endpoint Bansos</h2>
            <p class="mt-1 text-sm text-stone-500">Endpoint awal yang tersedia pada Bansos Service.</p>
        </div>

        <div class="divide-y divide-amber-100">
            <div class="grid gap-2 px-5 py-4 md:grid-cols-3">
                <div class="text-sm font-medium text-amber-700">POST</div>
                <div class="font-mono text-sm text-stone-700 md:col-span-2">
                    /api/register-recipient
                </div>
            </div>

            <div class="grid gap-2 px-5 py-4 md:grid-cols-3">
                <div class="text-sm font-medium text-amber-700">GET</div>
                <div class="font-mono text-sm text-stone-700 md:col-span-2">
                    /api/bansos-status/{nik}
                </div>
            </div>
        </div>
    </div>
</section>