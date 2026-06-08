<section class="space-y-8">
    <div>
        <p class="text-sm font-medium text-slate-500">Dashboard</p>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
            E-KTP Service
        </h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
            Pusat verifikasi identitas warga berbasis NIK untuk integrasi layanan RSUD, Bansos, dan SPBU.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Status Service</p>
            <p class="mt-2 text-xl font-semibold text-slate-900">Aktif</p>
            <p class="mt-1 text-xs text-slate-500">Berjalan pada port 8000</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Endpoint Utama</p>
            <p class="mt-2 text-xl font-semibold text-slate-900">2 Endpoint</p>
            <p class="mt-1 text-xs text-slate-500">verify NIK dan status warga</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Integrasi</p>
            <p class="mt-2 text-xl font-semibold text-slate-900">Siap</p>
            <p class="mt-1 text-xs text-slate-500">Untuk RSUD, Bansos, dan SPBU</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Endpoint E-KTP</h2>
            <p class="mt-1 text-sm text-slate-500">Daftar endpoint awal yang sudah tersedia.</p>
        </div>

        <div class="divide-y divide-slate-200">
            <div class="grid gap-2 px-5 py-4 md:grid-cols-3">
                <div class="text-sm font-medium text-slate-900">GET</div>
                <div class="font-mono text-sm text-slate-500">/api/verify/{nik}</div>
            </div>
        <div class="grid gap-2 px-5 py-4 md:grid-cols-3">
            <div class="text-sm font-medium text-slate-900">GET</div>
            <div class="font-mono text-sm text-slate-500">/api/citizen-status/{nik}</div>
        </div>
        </div>
    </div>
</section>