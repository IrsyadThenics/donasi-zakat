<?php
/**
 * donaturController.php
 * Controller untuk memproses aksi Hapus Donatur.
 * Database: Oracle (oci8)
 */
session_start();
// Pastikan path ini benar
require_once '../config/db.php'; 

// Cek apakah admin/penerima sudah login
if (!isset($_SESSION['email'])) {
    // Sesuaikan path ke login penerima
    header('Location: ../auth/login_penerima.php');
    exit;
}

$action = $_GET['action'] ?? '';
$id_donatur = $_GET['id'] ?? null;

if ($action === 'delete' && $id_donatur) {
    // Pencegahan: Donatur Anonim (ID 41 dari skema SQL) tidak boleh dihapus
    if ($id_donatur == 41) {
        $_SESSION['message'] = 'Donatur Anonim tidak dapat dihapus.';
        $_SESSION['message_class'] = 'error';
        // Sesuaikan path ke view/crud-donatur.php
        header('Location: ../view/crud-donatur.php');
        exit;
    }

    $ok = true;
    
    // Hapus Donatur
    // Karena Anda menggunakan ON DELETE CASCADE pada tabel DONASI, 
    // semua donasi yang dibuat oleh donatur ini akan ikut terhapus otomatis.
    $sql_delete = "DELETE FROM DONATUR WHERE ID_DONATUR = :id";
    $stmt_delete = oci_parse($conn, $sql_delete);
    oci_bind_by_name($stmt_delete, ':id', $id_donatur);

    if (!@oci_execute($stmt_delete, OCI_NO_AUTO_COMMIT)) {
        $ok = false;
        $e = oci_error($stmt_delete);
        $_SESSION['message'] = 'Gagal menghapus donatur: ' . ($e['message'] ?? 'Error Database');
        $_SESSION['message_class'] = 'error';
    }
    oci_free_statement($stmt_delete);
    
    if ($ok) {
        if (oci_commit($conn)) {
            $_SESSION['message'] = 'Donatur berhasil dihapus.';
            $_SESSION['message_class'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal commit transaksi penghapusan.';
            $_SESSION['message_class'] = 'error';
            oci_rollback($conn);
        }
    } else {
        oci_rollback($conn);
    }
} else {
    $_SESSION['message'] = 'Aksi tidak valid atau ID tidak ditemukan.';
    $_SESSION['message_class'] = 'error';
}

oci_close($conn);
// Sesuaikan path ke view/crud-donatur.php
header('Location: ../public/crud-donatur.php');
exit;
?>