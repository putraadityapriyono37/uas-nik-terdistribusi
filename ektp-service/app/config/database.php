<?php

function getDatabaseConnection()
{
    $host = '127.0.0.1';
    $database = 'db_ektp';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        return $pdo;
    } catch (PDOException $error) {
        jsonResponse(false, 'Koneksi database E-KTP gagal.', [
            'error' => $error->getMessage()
        ], 500);
    }
}