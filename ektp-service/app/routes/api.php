<?php

$path = getRequestPath();
$method = getRequestMethod();

$citizenController = new CitizenController();

if ($method === 'GET' && preg_match('#^/api/verify-nik/([0-9]+)$#', $path, $matches)) {
    $citizenController->verifyNik($matches[1]);
}

if ($method === 'GET' && preg_match('#^/api/citizen-status/([0-9]+)$#', $path, $matches)) {
    $citizenController->citizenStatus($matches[1]);
}

if ($method === 'POST' && $path === '/api/medical-record') {
    $citizenController->storeMedicalRecord();
}

jsonResponse(false, 'Endpoint tidak ditemukan', null, 404);