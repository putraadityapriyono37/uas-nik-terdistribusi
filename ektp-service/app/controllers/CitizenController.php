<?php

class CitizenController
{
    public function verify($nik)
    {
        if (!preg_match('/^[0-9]{16}$/', $nik))
            {
                jsonResponse(false, 'Format NIK harus 16 digit angka', null, 400);
            }

            jsonResponse(true, 'Endpoint verifikasi NIK sudah aktif.', [
                'nik' => $nik,
                'service' => 'E-KTP'
            ]);
    }

    public function citizenStatus($nik)
    {
        if (!preg_match('/^[0-9]{16}$/', $nik))
            {
                jsonResponse(false, 'Format NIK harus 16 digit angka', null, 400);
            }

            jsonResponse(true, 'Endpoint status warga sudah aktif.', [
                'nik' => $nik,
                'status_ekonomi' => 'belum_terhubung_database'
            ]);
    }
}