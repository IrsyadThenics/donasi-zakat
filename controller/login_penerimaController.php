<?php
include "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        echo "<script>alert('Silakan isi email dan password');history.back();</script>";
        exit;
    }

    $sql = "SELECT ID_PENERIMA, USERNAME, EMAIL, PASSWORD_HASH 
            FROM PENERIMA 
            WHERE UPPER(EMAIL) = UPPER(:email)";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':email', $email);
    oci_execute($stmt);

    $row = oci_fetch_assoc($stmt);
    
    // Debug: cek berapa banyak penerima di database
    $sql_count = "SELECT COUNT(*) as total FROM PENERIMA";
    $stmt_count = oci_parse($conn, $sql_count);
    oci_execute($stmt_count);
    $row_count = oci_fetch_assoc($stmt_count);
    echo "<!-- DEBUG: Total penerima di DB: " . $row_count['total'] . " -->\n";
    echo "<!-- DEBUG: Email yang dicari: " . htmlspecialchars($email) . " -->\n";
    echo "<!-- DEBUG: Email ditemukan: " . ($row ? "YES" : "NO") . " -->\n";
    if ($row) echo "<!-- DEBUG: Email di DB: " . htmlspecialchars($row['EMAIL']) . " -->\n";
    oci_free_statement($stmt_count);

    if ($row) {
        $stored_hash = trim($row['PASSWORD_HASH']);

        if (password_verify($password, $stored_hash)) {
            session_start();
            $_SESSION['id'] = $row['ID_PENERIMA'];
            $_SESSION['email'] = $row['EMAIL'];
            $_SESSION['username'] = $row['USERNAME'];
            $_SESSION['role'] = 'penerima';

            header("Location: ../public/dashboard_penerima.php");
            exit;
        } else {
            echo "<script>alert('Email atau password salah!');history.back();</script>";
        }
    } else {
        echo "<script>alert('Email tidak ditemukan!');history.back();</script>";
    }

    oci_free_statement($stmt);
    oci_close($conn);
}
?>
