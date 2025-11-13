<?php
/**
 * logout_penerimaController.php
 * Controller sederhana untuk melakukan logout penerima
 * - Hancurkan session
 * - Hapus cookie sesi jika ada
 * - Redirect ke halaman login penerima
 */

// Pastikan tidak ada output sebelum header redirect
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params['path'], $params['domain'],
		$params['secure'], $params['httponly']
	);
}

// Finally, destroy the session.
session_destroy();

// Regenerate id for safety (starts a new session without data)
session_start();
session_regenerate_id(true);
session_write_close();

// Redirect back to donatur login page (adjust path if different)
header('Location: ../auth/login_penerima.php');
exit;
