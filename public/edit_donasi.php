<?php
/**
 * Form Edit Donasi
 * - Mengambil data donasi berdasarkan ID
 * - Menampilkan formulir untuk diubah
 * - Memproses POST untuk update donasi
 * Database: Oracle (oci8)
 */
session_start();
require_once '../config/db.php';

// Pastikan Donatur sudah login
$email_donatur = $_SESSION['email'] ?? null;
if (!$email_donatur) {
    header('Location: ../auth/login_donatur.php');
    exit;
}

function formatRupiah($num) {
    return 'Rp ' . number_format(floatval($num), 0, ',', '.');
}

$message = '';
$message_class = '';
$data_donasi = null;
$id_donasi = $_REQUEST['id_donasi'] ?? null;
$jumlah_lama = 0; // Untuk menghitung selisih dana_terkumpul

// 1. Ambil ID Donatur
$id_donatur = null;
$stmt_cek = oci_parse($conn, "SELECT id_donatur FROM donatur WHERE email = :email");
if ($stmt_cek) {
    oci_bind_by_name($stmt_cek, ':email', $email_donatur);
    if (oci_execute($stmt_cek)) {
        if ($row = oci_fetch_assoc($stmt_cek)) {
            $id_donatur = intval($row['ID_DONATUR']);
        }
    }
    oci_free_statement($stmt_cek);
}

if (!$id_donasi || !$id_donatur) {
    header('Location: dashboard_donatur.php'); // Redirect jika ID tidak valid
    exit;
}

// 2. Load Data Donasi
// Tambahkan judul_campaign untuk ditampilkan di ringkasan
$stmt_donasi = oci_parse($conn, "SELECT d.*, c.judul_campaign FROM donasi d JOIN campaign c ON d.id_campaign = c.id_campaign WHERE d.id_donasi = :id_donasi AND d.id_donatur = :id_donatur");
if ($stmt_donasi) {
    oci_bind_by_name($stmt_donasi, ':id_donasi', $id_donasi);
    oci_bind_by_name($stmt_donasi, ':id_donatur', $id_donatur);
    if (oci_execute($stmt_donasi)) {
        $data_donasi = oci_fetch_assoc($stmt_donasi);
        if ($data_donasi) {
            $jumlah_lama = floatval($data_donasi['JUMLAH_DONASI']);
        }
    }
    oci_free_statement($stmt_donasi);
}

if (!$data_donasi) {
    // Jika data tidak ditemukan saat load awal, set pesan dan keluar
    $_SESSION['message'] = 'Donasi tidak ditemukan atau Anda tidak memiliki akses.';
    $_SESSION['message_class'] = 'error';
    header('Location: dashboard_donatur.php');
    exit;
}


// 3. Proses Update Donasi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data_donasi) {
    // Bersihkan format input jumlah
    $jumlah_baru_str = isset($_POST['jumlah']) ? str_replace([',','.' ], ['',''], $_POST['jumlah']) : '';
    $jumlah_baru = floatval($jumlah_baru_str);
    $metode = trim($_POST['metode'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');

    if ($jumlah_baru <= 0) {
        $message = 'Masukkan jumlah donasi yang valid (lebih besar dari 0).';
        $message_class = 'error';
    } else {
        $ok = true;
        
        // Hitung selisih perubahan
        $selisih = $jumlah_baru - $jumlah_lama;
        $id_campaign = $data_donasi['ID_CAMPAIGN'];

        // A. Update tabel Donasi
        $sql_upd_donasi = "UPDATE donasi SET jumlah_donasi = :jml_baru, metode = :met, pesan = :pes WHERE id_donasi = :iddn AND id_donatur = :iddr";
        $stmt_upd_donasi = oci_parse($conn, $sql_upd_donasi);
        
        if ($stmt_upd_donasi) {
            oci_bind_by_name($stmt_upd_donasi, ':jml_baru', $jumlah_baru);
            oci_bind_by_name($stmt_upd_donasi, ':met', $metode);
            oci_bind_by_name($stmt_upd_donasi, ':pes', $pesan);
            oci_bind_by_name($stmt_upd_donasi, ':iddn', $id_donasi);
            oci_bind_by_name($stmt_upd_donasi, ':iddr', $id_donatur);
            
            if (!@oci_execute($stmt_upd_donasi, OCI_NO_AUTO_COMMIT)) {
                $ok = false;
                $err = oci_error($stmt_upd_donasi);
                $message = 'Gagal mengupdate donasi: ' . ($err['message'] ?? '');
                $message_class = 'error';
            }
            oci_free_statement($stmt_upd_donasi);
        } else {
            $ok = false;
            $message = 'Gagal mempersiapkan query update donasi.';
            $message_class = 'error';
        }
        
        // B. Update dana_terkumpul di Campaign jika ada selisih
        if ($ok && $selisih != 0) {
            $sql_upd_campaign = "UPDATE campaign SET dana_terkumpul = NVL(dana_terkumpul,0) + :selisih WHERE id_campaign = :idc";
            $stmt_upd_campaign = oci_parse($conn, $sql_upd_campaign);
            
            if ($stmt_upd_campaign) {
                oci_bind_by_name($stmt_upd_campaign, ':selisih', $selisih);
                oci_bind_by_name($stmt_upd_campaign, ':idc', $id_campaign);
                
                if (!@oci_execute($stmt_upd_campaign, OCI_NO_AUTO_COMMIT)) {
                    $ok = false;
                    $err = oci_error($stmt_upd_campaign);
                    $message = 'Gagal mengupdate campaign: ' . ($err['message'] ?? '');
                    $message_class = 'error';
                }
                oci_free_statement($stmt_upd_campaign);
            } else {
                $ok = false;
                $message = 'Gagal mempersiapkan query update campaign.';
                $message_class = 'error';
            }
        }
        
        if ($ok) {
            if (oci_commit($conn)) {
                $_SESSION['message'] = 'Donasi berhasil diperbarui!';
                $_SESSION['message_class'] = 'success';
                
                // Update data_donasi dengan nilai baru agar form tetap menampilkan nilai yang benar
                $data_donasi['JUMLAH_DONASI'] = $jumlah_baru;
                $data_donasi['METODE'] = $metode;
                $data_donasi['PESAN'] = $pesan;
                $jumlah_lama = $jumlah_baru; // Set jumlah_lama ke jumlah_baru
                
                // Redirect untuk mencegah resubmission form
                header("Location: dashboard_donatur.php"); 
                exit;
            } else {
                $message = 'Gagal commit transaksi.';
                $message_class = 'error';
                @oci_rollback($conn);
            }
        } else {
            @oci_rollback($conn);
        }
    }
}

// Gunakan data yang sudah di-load atau data POST jika gagal
$current_jumlah_raw = isset($_POST['jumlah']) ? $_POST['jumlah'] : ($data_donasi ? $data_donasi['JUMLAH_DONASI'] : '');
$current_jumlah = htmlspecialchars($current_jumlah_raw);
$current_metode = isset($_POST['metode']) ? $_POST['metode'] : ($data_donasi ? $data_donasi['METODE'] : '');
$current_pesan = isset($_POST['pesan']) ? htmlspecialchars($_POST['pesan']) : ($data_donasi ? htmlspecialchars($data_donasi['PESAN']) : '');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Donasi - Donasi Masjid & Amal</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        .form-card{max-width:720px;margin:20px auto;padding:20px;border:1px solid #e2e2e2;border-radius:8px;background:#fff}
        label{display:block;margin-bottom:6px;font-weight:600}
        input[type=text], input[type=number], textarea, select{width:100%;padding:10px;margin-bottom:12px;border:1px solid #ccc;border-radius:4px}
        .btn{display:inline-block;padding:10px 16px;background:#007bff;color:#fff;border-radius:4px;text-decoration:none;border:none;cursor:pointer}
        .message{padding:10px;margin-bottom:12px;border-radius:4px;border:1px solid}
        .message.success{background:#e6ffed;border-color:#2ecc71;color:#1e7e34}
        .message.error{background:#fff1f0;border-color:#e74c3c;color:#c0392b}
        .campaign-summary{background:#f0f0ff;padding:10px;border:1px solid #8888ff;border-radius:4px;margin-bottom:12px}
    </style>
</head>
<body>
    <div class="form-card">
        <h1>Edit Donasi Anda</h1>
        
        <?php if ($data_donasi): ?>
            <div class="campaign-summary">
                <strong>Campaign:</strong> <?php echo htmlspecialchars($data_donasi['JUDUL_CAMPAIGN'] ?? 'N/A'); ?>
                <br>
                <strong>Tanggal Donasi:</strong> <?php echo date('d M Y H:i', strtotime($data_donasi['TANGGAL_DONASI'] ?? 'now')); ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_class); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($data_donasi): ?>
            <form method="post" action="edit_donasi.php?id_donasi=<?php echo htmlspecialchars($id_donasi); ?>">
                <input type="hidden" name="id_donasi" value="<?php echo htmlspecialchars($id_donasi); ?>">

                <label for="jumlah">Jumlah Donasi (angka, tanpa simbol)</label>
                <input type="text" name="jumlah" id="jumlah" placeholder="Mis. 50000" value="<?php echo $current_jumlah; ?>" required>

                <label for="metode">Metode Pembayaran</label>
                <select name="metode" id="metode">
                    <option value="Transfer Bank" <?php if($current_metode==='Transfer Bank') echo 'selected'; ?>>Transfer Bank</option>
                    <option value="E-Wallet" <?php if($current_metode==='E-Wallet') echo 'selected'; ?>>E-Wallet</option>
                    <option value="Tunai" <?php if($current_metode==='Tunai') echo 'selected'; ?>>Tunai</option>
                </select>

                <label for="pesan">Pesan (opsional)</label>
                <textarea name="pesan" id="pesan" rows="4"><?php echo $current_pesan; ?></textarea>

                <div style="display:flex;gap:12px;align-items:center">
                    <button type="submit" class="btn" style="background:#28a745">Simpan Perubahan</button>
                    <a href="dashboard_donatur.php" class="btn" style="background:#6c757d">Batal & Kembali</a>
                </div>
            </form>
        <?php else: ?>
            <p class="message error">Data donasi tidak dapat dimuat.</p>
            <a href="dashboard_donatur.php" class="btn" style="background:#6c757d">Kembali ke Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
oci_close($conn);
?>