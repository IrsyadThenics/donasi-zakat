<?php
include "../config/db.php"; // koneksi ke Oracle (pakai oci_connect)

// Proses register donatur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil data dari form
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password_plain = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Validasi konfirmasi password
    if ($password_plain !== $password_confirm) {
        die("<script>alert('Konfirmasi password tidak sama!');history.back();</script>");
    }

    // Hash password agar aman
    $password_hash = password_hash($password_plain, PASSWORD_BCRYPT);

    // Siapkan query insert ke tabel DONATUR
    $sql = "INSERT INTO DONATUR (
                ID_DONATUR, 
                NAMA_DONATUR, 
                EMAIL, 
                PASSWORD_HASH
            ) VALUES (
                SEQ_DONATUR.NEXTVAL, 
                :nama, 
                :email, 
                :password_hash
            )";

    $stmt = oci_parse($conn, $sql);

    // Binding parameter
    oci_bind_by_name($stmt, ':nama', $name);
    oci_bind_by_name($stmt, ':email', $email);
    oci_bind_by_name($stmt, ':password_hash', $password_hash);

    // Eksekusi query
    $result = oci_execute($stmt);

    if ($result) {
        echo "<script>alert('Registrasi Donatur Berhasil! Silakan login.');window.location='../auth/login_donatur.php';</script>";
    } else {
        $e = oci_error($stmt);
        echo "<script>alert('Gagal mendaftar: " . htmlentities($e['message']) . "');history.back();</script>";
    }

    // Bersihkan statement dan tutup koneksi
    oci_free_statement($stmt);
    oci_close($conn);
}
?>
