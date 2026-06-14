<?php

$path = getRequestPath();
$method = getRequestMethod();

$recipientController = new RecipientController();

if ($method === 'POST' && $path === '/api/register-recipient') {
    $recipientController->registerRecipient();
}

if ($method === 'GET' && preg_match('#^/api/bansos-status/([0-9]+)$#', $path, $matches)) {
    $recipientController->getBansosStatus($matches[1]);
}

jsonResponse(false, 'Endpoint tidak ditemukan.', null, 404);