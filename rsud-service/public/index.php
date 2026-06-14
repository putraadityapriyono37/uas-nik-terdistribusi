<?php

require_once __DIR__ . '/../app/helpers/response.php';
require_once __DIR__ . '/../app/helpers/request.php';
require_once __DIR__ . '/../app/helpers/http_client.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/PatientController.php';

$path = getRequestPath();

if (strpos($path, '/api') === 0) {
    require_once __DIR__ . '/../app/routes/api.php';
    exit;
}

require_once __DIR__ . '/../views/layout.php';