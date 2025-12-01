<?php
require_once "config.php";

// clear session
$_SESSION = [];

// expire any session used
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params['path'], $params['domain'],
		$params['secure'], $params['httponly']
	);
}

// destroy session on server
session_destroy();

// redirect to login
header('Location: login.php');
exit;
