<?php
// need access token to view this page
require_once "auth_check.php";
require_once "api.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>PHP API Client</title>
    <style>
        body { font-family: Arial, sans-serif; margin:20px; background:#f7f7f7; }
        nav { margin-bottom: 20px; }
        nav a { margin-right: 10px; text-decoration:none; color:#06c; }
        .flash { padding:10px; background:#e8ffe8; border:1px solid #b7f0b7; margin-bottom:12px; border-radius:6px; }
    </style>
</head>
<body>
<nav>
<?php if (!empty($_SESSION['access_token'])): ?>
    <a href="index.php">Dashboard</a>
    <a href="categories.php">Categories</a>
    <a href="products.php">Products</a>
    <a href="me.php">My Profile</a>
    <a href="logout.php">Logout</a>
<?php endif; ?>
</nav>

<main style="padding-bottom: 5%;">

