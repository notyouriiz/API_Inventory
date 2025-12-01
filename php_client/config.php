<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$apiBase  = "http://127.0.0.1:5000/api"; // categories/products
$authBase = $apiBase . "/auth";          // login/register/me
