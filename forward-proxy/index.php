<?php

// var_dump($_SERVER);
// die;

# server is started on 127.0.0.1:8989
echo 'starting proxy server on 127.0.0.1:8989' . PHP_EOL;

# when a request is received parse it to extract the target host, request method, ip etc
$targetHost = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestIp = $_SERVER['REMOTE_ADDR'];

// check for forbidden hosts
$filePath = './forbidden-hosts.txt';
$forbiddenHosts = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($forbiddenHosts as $forbiddenHost) {
    $position = stripos($targetHost, $forbiddenHost);
    if ($position !== false) {
        http_response_code(403);
        echo 'forbidden';
        die();
    }
}

if (!empty($targetHost)) {
    # hop-by-hop headers that we are getting from $_SERVER
    $hopHeaders = ['HTTP_CONNECTION', 'HTTP_UPGRADE_INSECURE_REQUESTS'];

    # create a new socket connection
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetHost);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    # set the headers minus hop headers
    $headers = [];
    foreach ($_SERVER as $key => $value) {

        if (in_array($key, $hopHeaders)) {
            continue;
        }
        # remove the HTTP_ prefix to keep a standard pascal case headers
        if (substr($key, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
        }
    }
    # add x-forwarded-for header
    $headers['X-Forwarded-For'] = $requestIp;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    # set the request type
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestMethod);
    
    # TODO: set the request body if the method is POST,PUT,PATCH

    $response = curl_exec($ch);

    if ($response === false) {
        printf("error %s", curl_error($ch));
        die();
    }

    # set response headers
    // $responseHeaders = curl_getinfo($ch);

    // foreach ($responseHeader as $key => $value) {
    //     header($key . ': ' . $value);
    // }

    # send response to client
    // echo $response;

    echo 'Request made. ' . 'Target: ' . $targetHost . ' Client: ' . $requestIp;

    curl_close($ch);
    die();
}

echo 'no requested url';
