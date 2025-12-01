<?php
require_once "config.php";

// make request to API
// get raw response, HTTP code, and decoded JSON
function rawAPI($method, $url, $data = null, $token = null) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // json response
    $headers = ["Accept: application/json"];
    if (!empty($token)) {
        $headers[] = "Authorization: Bearer $token";
    }

    $method = strtoupper($method);
    // send data for POST, PUT, PATCH, DELETE
    if (in_array($method, ["POST", "PUT", "PATCH", "DELETE"])) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data !== null) {
            $json = json_encode($data, JSON_UNESCAPED_SLASHES);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            $headers[] = "Content-Type: application/json";
        }
    } elseif ($method !== "GET") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // execute request
    $raw = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // cURL error
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return [
            "http" => 0,
            "json" => null,
            "raw"  => "cURL error: $err"
        ];
    }

    curl_close($ch);

    // decode JSON response
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $json = null; // non-JSON response
    }

    return [
        "http" => $http,
        "json" => $json,
        "raw"  => $raw
    ];
}

// call API with token refresh handling
function callAPI($method, $url, $data = null) {
    global $authBase;

    // reads stored tokens
    $access  = $_SESSION['access_token'] ?? null;
    $refresh = $_SESSION['refresh_token'] ?? null;

    $res = rawAPI($method, $url, $data, $access);

    if ($res['http'] === 500) {
        // retry after a small delay
        usleep(150000); // 150ms
        $res = rawAPI($method, $url, $data, $access);
    }
    
    if ($res['http'] === 401 && $refresh && $url !== "$authBase/refresh") {
        $refreshRes = rawAPI("POST", "$authBase/refresh", null, $refresh);

        $newAccess  = $refreshRes['json']['access_token'] ?? null;
        $newRefresh = $refreshRes['json']['refresh_token'] ?? $refresh;

        if ($newAccess) {
            $_SESSION['access_token']  = $newAccess;
            $_SESSION['refresh_token'] = $newRefresh;

            // Retry original request with new access token
            $res = rawAPI($method, $url, $data, $newAccess);

            if ($res['http'] === 500) {
                // if 500 after 401 refresh
                usleep(150000);
                $res = rawAPI($method, $url, $data, $access);
            }
        } else {
            // refresh failed → no new token returned
            unset($_SESSION['access_token'], $_SESSION['refresh_token']);
            header("Location: login.php");
            exit;
        }
        
    }
    return $res;
}

// this is just to check
// can delete later
function isAccessTokenExpired($token) {
    echo "<pre>Token Expiration Check</pre>";
    if (!$token) {
        echo "<pre>No token provided.</pre>";
        return true;
    }

    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        echo "<pre>Invalid token format.</pre>";
        return true;
    }

    $payload = json_decode(base64_decode($parts[1]), true);
    if (!$payload || !isset($payload['exp'])) {
        echo "<pre>No expiration found in token.</pre>";
        return true;
    }

    $isExpired = $payload['exp'] < time();
    echo "<pre>Token expired: " . ($isExpired ? "Yes" : "No") . "</pre>";
    return $isExpired;
}
