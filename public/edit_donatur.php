<?php
/**
 * edit_donatur.php
 * Formulir dan proses update data Donatur (Nama, Email, Telepon) 
 * serta fungsi toggle Status (Aktif/Nonaktif).
 * FIX: Perbaikan redirect form POST dan error Undefined Variable.
 * Database: Oracle (oci8)
 */
session_start();
require_once '../config/db.php'; 

// Cek apakah admin/penerima sudah login
if (!isset($_SESSION['email'])) { // Sesuaikan key SESSION Anda
    header('Location: ../auth/login_penerima.php');
    exit;
}

// ========================================
// INISIALISASI VARIABEL AWAL
// ========================================
$id_donatur = $_REQUEST['id'] ?? null; 
$data_donatur = null;
$message = '';
$message_class = '';

// Variabel untuk nilai input form
$current_nama = '';
$current_email = '';
$current_telepon = '';
$current_is_active = 1; // Default status aktif

if (!$id_donatur) {
    $_SESSION['message'] = 'ID Donatur tidak ditemukan.';
    $_SESSION['message_class'] = 'error';
    header('Location: crud-donatur.php');
    exit;
}

// ========================================
// 0. HANDLE STATUS TOGGLE (Aksi dari link/GET)
// ========================================
// Jika ada parameter 'action=toggle_status' di URL, proses status change dan redirect
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['status'])) {
    $new_status = $_GET['status']; // Nilai harus '0' atau '1'
    
    if (in_array($new_status, ['0', '1'])) {
        $status_label = ($new_status == '1') ? 'Aktif' : 'Nonaktif';
        
        $sql_update_status = "UPDATE DONATUR SET IS_ACTIVE = :new_status WHERE ID_DONATUR = :id";
        $stmt_update_status = oci_parse($conn, $sql_update_status);

        oci_bind_by_name($stmt_update_status, ':new_status', $new_status);
        oci_bind_by_name($stmt_update_status, ':id', $id_donatur);

        if (oci_execute($stmt_update_status)) {
            if (oci_num_rows($stmt_update_status) > 0) {
                if (oci_commit($conn)) { 
                    $_SESSION['message'] = 'Status Donatur berhasil diubah menjadi ' . $status_label . '.';
                    $_SESSION['message_class'] = 'success';
                } else {
                    $_SESSION['message'] = 'Gagal commit perubahan ke database.';
                    $_SESSION['message_class'] = 'error';
                    oci_rollback($conn);
                }
            } else {
                $_SESSION['message'] = 'Donatur ID tidak ditemukan.';
                $_SESSION['message_class'] = 'warning';
                oci_rollback($conn);
            }
            oci_free_statement($stmt_update_status);
        } else {
            $e = oci_error($stmt_update_status);
            $_SESSION['message'] = 'Gagal mengupdate status: ' . ($e['message'] ?? 'Error Database');
            $_SESSION['message_class'] = 'error';
            oci_rollback($conn);
            oci_free_statement($stmt_update_status);
        }
    } else {
        $_SESSION['message'] = 'Status yang diminta tidak valid.';
        $_SESSION['message_class'] = 'error';
    }
    
    // Redirect setelah pemrosesan status
    header('Location: crud-donatur.php');
    exit;
}

// ========================================
// 1. Load Data Donatur LAMA (termasuk status)
// ========================================
$stmt_load = oci_parse($conn, "SELECT NAMA_DONATUR, EMAIL, NOMOR_TELEPON, IS_ACTIVE FROM DONATUR WHERE ID_DONATUR = :id");
oci_bind_by_name($stmt_load, ':id', $id_donatur);

if (!oci_execute($stmt_load)) {
    $e = oci_error($stmt_load);
    $_SESSION['message'] = 'Gagal memuat data donatur: ' . ($e['message'] ?? 'Error Database');
    $_SESSION['message_class'] = 'error';
    header('Location: crud-donatur.php');
    exit;
} 

// PERBAIKAN: Ambil data langsung dan gunakan key UPPERCASE yang dikembalikan OCI_ASSOC
$data_donatur = oci_fetch_array($stmt_load, OCI_ASSOC); 
oci_free_statement($stmt_load);

if (!$data_donatur) {
    $_SESSION['message'] = 'Data donatur dengan ID tersebut tidak ditemukan.';
    $_SESSION['message_class'] = 'error';
    header('Location: crud-donatur.php');
    exit;
}

// Mengisi nilai awal form dengan data lama (Gunakan UPPERCASE keys)
$current_nama = htmlspecialchars($data_donatur['NAMA_DONATUR'] ?? '');
$current_email = htmlspecialchars($data_donatur['EMAIL'] ?? '');
$current_telepon = htmlspecialchars($data_donatur['NOMOR_TELEPON'] ?? '');
$current_is_active = $data_donatur['IS_ACTIVE'] ?? 1;


// ========================================
// 2. Proses Update (POST) - (Form Submission)
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');

    // Update variabel current untuk menampilkan data POST jika validasi gagal
    $current_nama = htmlspecialchars($nama);
    $current_email = htmlspecialchars($email);
    $current_telepon = htmlspecialchars($telepon);
    
    if (empty($nama) || empty($email)) {
        $message = 'Nama dan Email tidak boleh kosong.';
        $message_class = 'error';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Format email tidak valid.';
        $message_class = 'error';
    } else {
        // Cek duplikasi email
        $stmt_check = oci_parse($conn, "SELECT COUNT(*) FROM DONATUR WHERE EMAIL = :email AND ID_DONATUR != :id");
        oci_bind_by_name($stmt_check, ':email', $email);
        oci_bind_by_name($stmt_check, ':id', $id_donatur);
        oci_execute($stmt_check);
        $row_count = oci_fetch_array($stmt_check)[0];
        oci_free_statement($stmt_check);

        if ($row_count > 0) {
            $message = 'Email sudah terdaftar untuk donatur lain.';
            $message_class = 'error';
        } else {
            // Lakukan Update (Nama, Email, Telepon)
            $sql_update = "UPDATE DONATUR SET NAMA_DONATUR = :nama, EMAIL = :email, NOMOR_TELEPON = :telepon WHERE ID_DONATUR = :id";
            $stmt_update = oci_parse($conn, $sql_update);
            
            oci_bind_by_name($stmt_update, ':nama', $nama);
            oci_bind_by_name($stmt_update, ':email', $email);
            oci_bind_by_name($stmt_update, ':telepon', $telepon);
            oci_bind_by_name($stmt_update, ':id', $id_donatur);

            if (oci_execute($stmt_update)) {
                $rows_affected = oci_num_rows($stmt_update); 
                
                if ($rows_affected > 0) {
                    if (oci_commit($conn)) { 
                        $_SESSION['message'] = 'Data donatur berhasil diupdate!';
                        $_SESSION['message_class'] = 'success';
                    } else {
                        $message = 'Gagal commit perubahan ke database.';
                        $message_class = 'error';
                        oci_rollback($conn);
                    }
                } else {
                    $message = 'Tidak ada perubahan data yang disimpan (Data yang Anda masukkan sama dengan yang tersimpan).';
                    $message_class = 'warning'; 
                    oci_rollback($conn); 
                }
                
                oci_free_statement($stmt_update);
                
                if ($message_class === 'success' || $message_class === 'warning') {
                    // Berhasil update atau tidak ada perubahan, REDIRECT ke halaman daftar
                    $_SESSION['message'] = $message;
                    $_SESSION['message_class'] = $message_class;
                    header('Location: crud-donatur.php');
                    exit;
                }

            } else {
                $e = oci_error($stmt_update);
                $message = 'Gagal mengupdate data: ' . ($e['message'] ?? 'Error Database');
                $message_class = 'error';
                oci_rollback($conn);
                oci_free_statement($stmt_update);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Donatur</title>
    <link rel="stylesheet" href="../assets/css/edit_donatur.css"> 
    
</head>
<body>
    <div class="form-container">
        <h1>Edit Data Donatur</h1>
        
        <?php 
        // Menampilkan pesan error/warning dari proses POST (jika gagal redirect)
        if ($message): 
        ?>
            <div class="message <?php echo htmlspecialchars($message_class); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="status-info">
            Status Donatur Saat Ini: 
            <?php 
            $status_label_display = ($current_is_active == 1) ? 'Aktif' : 'Nonaktif';
            $status_class = ($current_is_active == 1) ? 'status-active' : 'status-inactive';
            echo '<span class="' . $status_class . '">' . $status_label_display . '</span>';
            ?>
        </div>
        
        <div class="btn-group-status">
            <?php 
            if ($current_is_active == 1) {
                $next_status = 0; 
                $link_text = 'Nonaktifkan Donatur Ini';
                $link_class = 'btn-danger';
            } else {
                $next_status = 1; 
                $link_text = 'Aktifkan Donatur Ini';
                $link_class = 'btn-success';
            }
            
            $link_url = "edit_donatur.php?id=" . urlencode($id_donatur) . "&action=toggle_status&status=" . urlencode($next_status);
            ?>
            <a href="<?php echo $link_url; ?>" 
               class="btn <?php echo $link_class; ?>" 
               style="width: 100%;"
               onclick="return confirm('Anda yakin ingin mengubah status Donatur ini menjadi <?php echo ($next_status == 1 ? 'Aktif' : 'Nonaktif'); ?>? Ini akan mengarahkan Anda ke halaman daftar.');">
                <?php echo $link_text; ?>
            </a>
        </div>
        
        <hr>

        <form method="POST" action="edit_donatur.php?id=<?php echo htmlspecialchars($id_donatur); ?>">
            
            <label for="nama">Nama Donatur</label>
            <input type="text" id="nama" name="nama" value="<?php echo $current_nama; ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo $current_email; ?>" required>

            <label for="telepon">Nomor Telepon (Opsional)</label>
            <input type="tel" id="telepon" name="telepon" value="<?php echo $current_telepon; ?>">

            <div class="btn-group">
                <button type="submit" class="btn primary">Simpan Perubahan Data</button>
                <a href="crud-donatur.php" class="btn secondary">Batal & Kembali</a>
            </div>
        </form>
    </div>
</body>
</html>

<?php oci_close($conn); ?>