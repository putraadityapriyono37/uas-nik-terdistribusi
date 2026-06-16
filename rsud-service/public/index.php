<?php

// Helper response JSON
require_once __DIR__ . '/../app/helpers/response.php';

// Helper membaca URL dan method request
require_once __DIR__ . '/../app/helpers/request.php';

// Helper HTTP client untuk komunikasi antar-service
require_once __DIR__ . '/../app/helpers/http_client.php';

// Koneksi database RSUD
require_once __DIR__ . '/../app/config/database.php';

// Controller utama RSUD
require_once __DIR__ . '/../app/controllers/PatientController.php';

$path = getRequestPath();

// Jika URL diawali /api, arahkan ke route API
if (strpos($path, '/api') === 0) {
    require_once __DIR__ . '/../app/routes/api.php';
    exit;
}

// Semua halaman web harus lewat layout
require_once __DIR__ . '/../views/layout.php';