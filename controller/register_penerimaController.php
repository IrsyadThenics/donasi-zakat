<?php
include "../config/db.php"; // koneksi ke Oracle

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitasi input
    $username = trim(filter_input(INPUT_POST, 'username') ?? '');
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $password_plain = trim(filter_input(INPUT_POST, 'password') ?? '');
    $password_confirm = trim(filter_input(INPUT_POST, 'password_confirm') ?? '');

    // Validasi input tidak kosong
    if ($username === '' || $email === '' || $password_plain === '' || $password_confirm === '') {
        die("<script>alert('Semua field harus diisi!');history.back();</script>");
    }

    // Validasi konfirmasi password
    if ($password_plain !== $password_confirm) {
        die("<script>alert('Konfirmasi password tidak sama!');history.back();</script>");
    }

    $password_hash = password_hash($password_plain, PASSWORD_BCRYPT);

    // Generate ID baru pakai sequence
    $sql_id = "SELECT seq_penerima.NEXTVAL AS ID FROM dual";
    $stmt_id = oci_parse($conn, $sql_id);
    oci_execute($stmt_id);
    $row_id = oci_fetch_assoc($stmt_id);
    $id_penerima = $row_id['ID'];
    oci_free_statement($stmt_id);

    $tanggal = date('d-M-y'); // format Oracle
    $created = $tanggal;
    $updated = $tanggal;

    // Query tanpa kolom STATUS dan TANGGAL_DAFTAR
    $sql = "INSERT INTO PENERIMA 
            (ID_PENERIMA, USERNAME, EMAIL, PASSWORD_HASH, CREATED_AT, UPDATED_AT)
            VALUES (:id, :username, :email, :password_hash,
                    TO_DATE(:created, 'DD-MON-RR'), TO_DATE(:updated, 'DD-MON-RR'))";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $id_penerima);
    oci_bind_by_name($stmt, ':username', $username);
    oci_bind_by_name($stmt, ':email', $email);
    oci_bind_by_name($stmt, ':password_hash', $password_hash);
    oci_bind_by_name($stmt, ':created', $created);
    oci_bind_by_name($stmt, ':updated', $updated);

    $result = oci_execute($stmt);

    if ($result) {
        echo "<script>alert('Registrasi berhasil! Silakan login.');window.location='../auth/login_penerima.php';</script>";
    } else {
        $e = oci_error($stmt);
        echo "Error: " . htmlentities($e['message']);
    }

    oci_free_statement($stmt);
    oci_close($conn);
}
?>
