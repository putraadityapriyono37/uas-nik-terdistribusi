<?php

$path = getRequestPath();
$method = getRequestMethod();

$patientController = new PatientController();

if ($method === 'POST' && $path === '/api/register-patient') {
    $patientController->registerPatient();
}

if ($method === 'POST' && $path === '/api/medical-record') {
    $patientController->sendMedicalRecord();
}

jsonResponse(false, 'Endpoint tidak ditemukan.', null, 404);