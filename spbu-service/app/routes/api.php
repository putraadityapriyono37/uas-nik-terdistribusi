<?php

$path = getRequestPath();
$method = getRequestMethod();

$fuelTransactionController = new FuelTransactionController();

if ($method === 'POST' && $path === '/api/fuel-transaction') {
    $fuelTransactionController->createTransaction();
}

jsonResponse(false, 'Endpoint tidak ditemukan.', null, 404);