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
        body { 
            font-family: 'Segoe UI', Roboto, Arial, sans-serif; 
            margin: 0; 
            background: #f7f7f7; 
        }
        nav {
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            padding: 15px 30px; 
            background: #0073e6; 
            color: #fff; 
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            border-radius: 0 0 10px 10px;
            position: sticky;
            top: 0;
            z-index: auto;
        }
        nav a { 
            color: #fff; 
            text-decoration: none; 
            margin-right: 15px; 
            padding: 8px 12px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        nav a:hover { 
            background: rgba(255,255,255,0.2);
        }
        nav a:active {
            font-weight: bold;
        }
        nav .logo {
            font-weight: bold;
            font-size: 1.2em;
        }
        main {
            padding: 20px 30px 50px 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .flash {
            padding:10px; 
            background:#e8ffe8; 
            border:1px solid #b7f0b7; 
            margin-bottom:12px; 
            border-radius:6px; 
        }
    </style>
</head>
<body>
<nav>
    <div class="logo">Inventory System</div>
    <div class="nav-links">
        <?php if (!empty($_SESSION['access_token'])): ?>
            <a href="index.php">Dashboard</a>
            <a href="categories.php">Categories</a>
            <a href="products.php">Products</a>
            <a href="me.php">My Profile</a>
            <a href="logout.php" style="background:#d9534f;">Logout</a>
        <?php endif; ?>
    </div>
</nav>

<main>
