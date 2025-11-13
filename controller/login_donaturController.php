<?php
include "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use filter_input to avoid undefined index warnings and sanitize input
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $password = trim(filter_input(INPUT_POST, 'password') ?? '');

    // Basic validation: ensure both fields were provided
    if ($email === '' || $password === '') {
        echo "<script>alert('Silakan isi email dan password');history.back();</script>";
        exit;
    }

    // Query ambil data berdasarkan email
    $sql = "SELECT NAMA_DONATUR, EMAIL, PASSWORD_HASH 
            FROM DONATUR 
            WHERE UPPER(EMAIL) = UPPER(:email)";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':email', $email);
    oci_execute($stmt);

    $row = oci_fetch_assoc($stmt);

    if ($row) {
        // Debug: cek raw values
        $stored_hash = $row['PASSWORD_HASH'];
        $verify_result = password_verify($password, $stored_hash);
        
        // Tampilkan debug info
        echo "<!-- DEBUG INFO -->\n";
        echo "Password input: " . htmlspecialchars($password) . " (len: " . strlen($password) . ")\n";
        echo "Hash stored: " . htmlspecialchars($stored_hash) . "\n";
        echo "password_verify result: " . ($verify_result ? "TRUE" : "FALSE") . "\n";
        echo "Hash info: " . json_encode(password_get_info($stored_hash)) . "\n";
        echo "<!-- END DEBUG -->\n";
        
        // Verifikasi password
        if ($verify_result) {
            session_start();
            $_SESSION['email'] = $row['EMAIL'];
            $_SESSION['nama'] = $row['NAMA_DONATUR'];
            $_SESSION['role'] = 'donatur';

            header("Location: ../public/dashboard_donatur.php");
            exit;
        } else {
            echo "<script>alert('Email atau password salah!');history.back();</script>";
        }
    } else {
        echo "email tidak ditemukan!";
    }

    oci_free_statement($stmt);
    oci_close($conn);
}
?>
