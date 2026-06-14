<?php

function sendGetRequest($url)
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($error) {
        return [
            'success' => false,
            'status_code' => 500,
            'message' => 'Service tujuan tidak dapat dihubungi.',
            'data' => null
        ];
    }

    return [
        'success' => $statusCode >= 200 && $statusCode < 300,
        'status_code' => $statusCode,
        'message' => 'Request selesai.',
        'data' => json_decode($response, true)
    ];
}