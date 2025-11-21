<?php
/**
 * crud-donatur.php
 * Menampilkan daftar donatur secara dinamis dari database Oracle.
 * FIX: Menambahkan kolom IS_ACTIVE di query dan logika tampilan status.
 * Database: Oracle (oci8)
 */
session_start();
require_once '../config/db.php'; // Sesuaikan path ke file koneksi Anda

// Cek apakah admin/penerima sudah login
if (!isset($_SESSION['email'])) {
    header('Location: ../auth/login_penerima.php');
    exit;
}

// ========================================
// AMBIL DATA DONATUR (TERMASUK IS_ACTIVE)
// ========================================
$donatur_list = [];
// PERBAIKAN: Tambahkan IS_ACTIVE ke dalam SELECT query
$stmt = oci_parse($conn, "SELECT ID_DONATUR, NAMA_DONATUR, EMAIL, TANGGAL_TERDAFTAR, PASSWORD_HASH, IS_ACTIVE FROM DONATUR ORDER BY TANGGAL_TERDAFTAR DESC");

if (oci_execute($stmt)) {
    while ($row = oci_fetch_assoc($stmt)) {
        // Menggunakan OCI_ASSOC agar key-nya sesuai nama kolom (uppercase jika tidak di-aliasing)
        $donatur_list[] = $row;
    }
}
oci_free_statement($stmt);

// Ambil notifikasi dari session (setelah redirect dari edit/hapus/toggle)
$message = $_SESSION['message'] ?? '';
$message_class = $_SESSION['message_class'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_class']);

function formatDate($dateString) {
    if (!$dateString) return '-';
    // Format tanggal dari Oracle
    $timestamp = strtotime($dateString);
    return date('Y-m-d', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Donatur</title>
    <link rel="stylesheet" href="../assets/css/crud-donatur.css">
    <style>
        /* Gaya tambahan untuk notifikasi dan tag */
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid;
        }
        .message.success {
            background: #e6ffed;
            color: #1e7e34;
            border-color: #2ecc71;
        }
        .message.warning { /* Gaya tambahan untuk pesan 0 rows affected */
            background: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }
        .message.error {
            background: #fff1f0;
            color: #c0392b;
            border-color: #e74c3c;
        }
        .tag {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        /* PERBAIKAN STYLING TAG SESUAI LOGIKA IS_ACTIVE */
        .tag.active { 
            background: #d4edda; 
            color: #155724; 
        } 
        .tag.closed { 
            background: #f8d7da; 
            color: #721c24; 
        } 
        /* PERBAIKAN STYLING TOMBOL */
        .btn.danger { background: #dc3545; color: white; }
        .btn.success { background: #28a745; color: white; }
        .btn.secondary { background: #6c757d; color: white; }
    </style>
</head>
<body>

<header>
    
</header>

<div class="wrap">

    <div class="sidebar">
        <h3>Menu Admin</h3>
        <div class="side-list">
            <a href="dashboard_penerima.php">Dashboard</a>
            <a href="crud-campaign.php">Kelola Campaign</a>
            <a href="crud-donasi.php">Kelola Donasi</a>
            <a href="crud-donatur.php" class="active">Kelola Donatur</a>
            <a href="crud-laporan.php">Kelola Laporan</a>
        </div>
    </div> 

    <div class="content">
        <div class="page-head">
            <h1>Kelola Donatur</h1>
            <p>Data para donatur yang terdaftar</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_class); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <button class="btn" onclick="location.href='donatur-tambah.php'">+ Tambah Donatur</button>

        <div class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Donatur</th>
                            <th>Email</th>
                            <th>Tanggal Daftar</th>
                            <th>Status Akun</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($donatur_list)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #999;">Tidak ada data donatur.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach ($donatur_list as $donatur): 
                                // PERBAIKAN: Menggunakan kolom IS_ACTIVE
                                $is_active = $donatur['IS_ACTIVE'] ?? 1; // 1=Aktif, 0=Nonaktif
                                $status = ($is_active == 1) ? 'Aktif' : 'Nonaktif';
                                $status_class = ($is_active == 1) ? 'active' : 'closed';
                                
                                // Donatur Anonim (ID_DONATUR 41) tidak diizinkan untuk diedit/dihapus
                                $is_anonim = ($donatur['ID_DONATUR'] == 41);
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($donatur['NAMA_DONATUR'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($donatur['EMAIL'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(formatDate($donatur['TANGGAL_TERDAFTAR'])); ?></td>
                                    <td><span class="tag <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                                    <td>
                                        <?php if (!$is_anonim): ?>
                                            <button class="btn small secondary" onclick="location.href='edit_donatur.php?id=<?php echo htmlspecialchars($donatur['ID_DONATUR']); ?>'">Edit</button>
                                            
                                            <?php 
                                            // LOGIKA TOMBOL TOGGLE STATUS (Memanggil edit_donatur.php)
                                            if ($is_active == 1) {
                                                $next_status = 0; 
                                                $link_text = 'Nonaktifkan';
                                                $link_class = 'danger'; // Menggunakan style danger (merah)
                                            } else {
                                                $next_status = 1; 
                                                $link_text = 'Aktifkan';
                                                $link_class = 'success'; // Menggunakan style success (hijau)
                                            }
                                            $link_url = "edit_donatur.php?id=" . urlencode($donatur['ID_DONATUR']) . "&action=toggle_status&status=" . urlencode($next_status);
                                            ?>
                                           <!--<a 
                                                href="<?php echo $link_url; ?>" 
                                                class="btn small <?php echo $link_class; ?>" 
                                                onclick="return confirm('Yakin ingin mengubah status Donatur <?php echo htmlspecialchars($donatur['NAMA_DONATUR']); ?> menjadi <?php echo $link_text; ?>?')"
                                            >
                                                <?php echo $link_text; ?>
                                            </a>-->
                                            
                                            <button 
                                                class="btn small secondary" 
                                                onclick="if(confirm('Yakin ingin menghapus donatur <?php echo htmlspecialchars($donatur['NAMA_DONATUR']); ?>? Semua donasi terkait juga akan terhapus.')) { location.href='../controller/hapus_donaturController.php?action=delete&id=<?php echo htmlspecialchars($donatur['ID_DONATUR']); ?>'; }"
                                            >
                                                Hapus
                                            </button>
                                        <?php else: ?>
                                            <span class="tag closed">Anonim</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<footer>
    <p>© 2025 Takmir Masjid — Sistem Donasi</p>
</footer>

</body>
</html>

<?php oci_close($conn); ?>