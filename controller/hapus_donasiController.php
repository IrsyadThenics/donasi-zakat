<?php
/**
 * Controller untuk Hapus Donasi
 * - Menghapus donasi dari tabel donasi
 * - Mengurangi dana_terkumpul dari tabel campaign
 * Database: Oracle (oci8)
 */
session_start();
require_once '../config/db.php';

// Pastikan Donatur sudah login
$email_donatur = $_SESSION['email'] ?? null;
if (!$email_donatur) {
    $_SESSION['message'] = 'Anda harus login untuk menghapus donasi.';
    $_SESSION['message_class'] = 'error';
    header('Location: ../auth/login_donatur.php');
    exit;
}

$id_donasi = $_GET['id_donasi'] ?? null;

if (!$id_donasi) {
    $_SESSION['message'] = 'ID Donasi tidak valid.';
    $_SESSION['message_class'] = 'error';
    header('Location: ../view/dashboard_donatur.php');
    exit;
}

// 1. Ambil ID Donatur dan data donasi yang akan dihapus
$id_donatur = null;
$jumlah_donasi = 0;
$id_campaign = null;
$ok = false;

// Dapatkan ID Donatur dari Session
$stmt_iddr = oci_parse($conn, "SELECT id_donatur FROM donatur WHERE email = :email");
oci_bind_by_name($stmt_iddr, ':email', $email_donatur);
oci_execute($stmt_iddr);
if ($row = oci_fetch_assoc($stmt_iddr)) {
    $id_donatur = intval($row['ID_DONATUR']);
}
oci_free_statement($stmt_iddr);

if ($id_donatur) {
    // Ambil detail donasi (Jumlah dan ID Campaign)
    $stmt_detail = oci_parse($conn, "SELECT jumlah_donasi, id_campaign FROM donasi WHERE id_donasi = :iddn AND id_donatur = :iddr");
    oci_bind_by_name($stmt_detail, ':iddn', $id_donasi);
    oci_bind_by_name($stmt_detail, ':iddr', $id_donatur);
    oci_execute($stmt_detail);

    if ($row = oci_fetch_assoc($stmt_detail)) {
        $jumlah_donasi = floatval($row['JUMLAH_DONASI']);
        $id_campaign = intval($row['ID_CAMPAIGN']);
        $ok = true; // Data donasi ditemukan dan milik donatur
    }
    oci_free_statement($stmt_detail);
}

if (!$ok) {
    $_SESSION['message'] = 'Donasi tidak ditemukan atau Anda tidak memiliki izin untuk menghapusnya.';
    $_SESSION['message_class'] = 'error';
    header('Location: ../view/dashboard_donatur.php');
    exit;
}

// 2. Proses Penghapusan Donasi dan Update Campaign (menggunakan Transaksi)
try {
    // Mulai transaksi
    oci_execute(oci_parse($conn, "ALTER SESSION SET TRANSACTION READ WRITE")); 

    // A. Hapus Donasi dari tabel DONASI
    $sql_del = "DELETE FROM donasi WHERE id_donasi = :iddn AND id_donatur = :iddr";
    $stmt_del = oci_parse($conn, $sql_del);
    oci_bind_by_name($stmt_del, ':iddn', $id_donasi);
    oci_bind_by_name($stmt_del, ':iddr', $id_donatur);
    
    if (!@oci_execute($stmt_del, OCI_NO_AUTO_COMMIT)) {
        throw new Exception("Gagal menghapus donasi.");
    }
    oci_free_statement($stmt_del);

    // B. Kurangi dana_terkumpul di tabel CAMPAIGN
    $sql_upd = "UPDATE campaign SET dana_terkumpul = dana_terkumpul - :jml WHERE id_campaign = :idc";
    $stmt_upd = oci_parse($conn, $sql_upd);
    oci_bind_by_name($stmt_upd, ':jml', $jumlah_donasi);
    oci_bind_by_name($stmt_upd, ':idc', $id_campaign);

    if (!@oci_execute($stmt_upd, OCI_NO_AUTO_COMMIT)) {
        throw new Exception("Gagal mengupdate dana campaign.");
    }
    oci_free_statement($stmt_upd);

    // C. Commit Transaksi
    if (oci_commit($conn)) {
        $_SESSION['message'] = 'Donasi berhasil dihapus dan dana dikembalikan dari campaign.';
        $_SESSION['message_class'] = 'success';
    } else {
        throw new Exception("Gagal commit transaksi.");
    }

} catch (Exception $e) {
    @oci_rollback($conn);
    $_SESSION['message'] = 'Gagal menghapus donasi: ' . $e->getMessage();
    $_SESSION['message_class'] = 'error';
}

oci_close($conn);

// Redirect kembali ke dashboard
header('Location: ../public/dashboard_donatur.php');
exit;
?>