<?php
require_once "config.php";
require_once "api.php";

// If both tokens missing → must login
if (!isset($_SESSION['access_token']) && !isset($_SESSION['refresh_token'])) {
    header("Location: login.php");
    exit;
}