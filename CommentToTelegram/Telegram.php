<?php

function sendTelegramRequest($apiToken, $data, $action, $proxy = NULL)
{
    $url = 'https://api.telegram.org/bot' . $apiToken . '/' . $action;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    if (isset($proxy)) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
//file_put_contents(__DIR__ . '/log.txt', 'response: '.$response.PHP_EOL, FILE_APPEND);

