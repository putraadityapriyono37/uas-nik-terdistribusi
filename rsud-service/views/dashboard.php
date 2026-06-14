<section class="space-y-8">
    <div>
        <p class="text-sm font-medium text-emerald-700">Dashboard</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
            RSUD Service
        </h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
            Layanan registrasi pasien berbasis verifikasi NIK dari E-KTP Service.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-emerald-100 bg-white p-5">
            <p class="text-sm text-slate-500">Status Service</p>
            <p class="mt-2 text-xl font-semibold text-slate-900">Aktif</p>
            <p class="mt-1 text-xs text-slate-500">Berjalan pada port 8001</p>
        </div>

        <div class="rounded-xl border border-emerald-100 bg-white p-5">
            <p class="text-sm text-slate-500">Integrasi</p>
            <p class="mt-2 text-xl font-semibold text-slate-900">E-KTP</p>
            <p class="mt-1 text-xs text-slate-500">Verifikasi NIK pasien</p>
        </div>

        <div class="rounded-xl border border-emerald-100 bg-white p-5">
            <p class="text-sm text-slate-500">Endpoint Utama</p>
            <p class="mt-2 text-xl font-semibold text-slate-900">2 Endpoint</p>
            <p class="mt-1 text-xs text-slate-500">Registrasi pasien dan rekam medis</p>
        </div>
    </div>

    <div class="rounded-xl border border-emerald-100 bg-white">
        <div class="border-b border-emerald-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Endpoint RSUD</h2>
            <p class="mt-1 text-sm text-slate-500">Endpoint awal yang tersedia pada RSUD Service.</p>
        </div>

        <div class="divide-y divide-emerald-100">
            <div class="grid gap-2 px-5 py-4 md:grid-cols-3">
                <div class="text-sm font-medium text-emerald-700">POST</div>
                <div class="font-mono text-sm text-slate-700 md:col-span-2">
                    /api/register-patient
                </div>
            </div>

            <div class="grid gap-2 px-5 py-4 md:grid-cols-3">
                <div class="text-sm font-medium text-emerald-700">POST</div>
                <div class="font-mono text-sm text-slate-700 md:col-span-2">
                    /api/medical-record
                </div>
            </div>
        </div>
    </div>
</section>