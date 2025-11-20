<?php
/**
 * create_campaign.php
 * Form dan handler untuk penerima membuat campaign
 */
session_start();
require_once '../config/db.php'; // Pastikan path ke db.php sudah benar

// 1. INIALISASI DAN FUNGSI HELPER
// Inisialisasi variabel OCI8 resource. Tidak perlu $stmt_cek karena akan dibebaskan langsung.
$stmt = null; 
$message = ''; 

// Fungsi helper untuk escaping output HTML (Hanya didefinisikan satu kali!)
function esc($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}

// 2. CEK LOGIN DAN AMBIL ID PENERIMA
$username_penerima = $_SESSION['username'] ?? null;

if (!$username_penerima) {
    header('Location: ../auth/login_penerima.php');
    exit;
}

$id_penerima = null;
$sql_cek = "SELECT id_penerima FROM penerima WHERE username = :username";
$stmt_cek = oci_parse($conn, $sql_cek); 

if ($stmt_cek) {
    oci_bind_by_name($stmt_cek, ':username', $username_penerima);
    if (oci_execute($stmt_cek)) {
        if ($row = oci_fetch_assoc($stmt_cek)) {
            $id_penerima = intval($row['ID_PENERIMA']);
        }
    }
    // BEBASKAN $stmt_cek segera setelah selesai digunakan
    oci_free_statement($stmt_cek); 
} else {
    $err = oci_error($conn);
    $message = 'Gagal mempersiapkan query cek penerima: ' . ($err['message'] ?? '');
}


if (!$id_penerima) {
    header('Location: ../auth/login_penerima.php');
    exit;
}


// 3. HANDLE FORM POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    
    // Pembersihan input numerik
    $target_input = $_POST['target'] ?? 0;
    $dana_terkumpul_input = $_POST['dana_terkumpul'] ?? 0;

    // Mengganti koma dengan titik untuk float/numeric input yang lebih aman
    $target = (float)preg_replace('/[^0-9\.]/', '', str_replace(',', '.', $target_input));
    $dana_terkumpul = (float)preg_replace('/[^0-9\.]/', '', str_replace(',', '.', $dana_terkumpul_input));
    
    $mulai = $_POST['tanggal_mulai'] ?? null;
    $deadline = $_POST['tanggal_deadline'] ?? null;

    // Validasi
    if ($judul === '' || $deskripsi === '' || $target <= 0 || !$mulai || !$deadline) {
        $message = 'Semua field wajib diisi dengan nilai yang valid.';
    } else {

        // ============== UPLOAD POSTER ===================
//        $poster_filename = null;
//        $upload_error = false;

//        if (!empty($_FILES['poster']['name'])) {

//            $up = $_FILES['poster'];
//            $ext = strtolower(pathinfo($up['name'], PATHINFO_EXTENSION));
//            $validExt = ['png', 'jpg', 'jpeg'];
//            $poster_dir = __DIR__ . '/../assets/img/campaign/';

//            if ($up['error'] !== 0) {
//                 $message = "Gagal upload poster. Error Code: " . $up['error'];
//                 $upload_error = true;
//            } elseif (!in_array($ext, $validExt)) {
//                 $message = "Format file harus JPG/PNG.";
//                 $upload_error = true;
//            } elseif ($up['size'] > 2 * 1024 * 1024) {
//                 $message = "Ukuran file lebih dari 2MB.";
//                 $upload_error = true;
//            }

//            if (!$upload_error) {
//                if (!is_dir($poster_dir)) {
//                    if (!mkdir($poster_dir, 0755, true)) {
//                        $message = "Gagal membuat direktori poster.";
//                        $upload_error = true;
//                    }
//                }
            
//                if (!$upload_error) {
//                    $poster_filename = uniqid("poster_") . "." . $ext;
//                    $dst = $poster_dir . $poster_filename;

//                    if (!move_uploaded_file($up['tmp_name'], $dst)) {
//                        $message = "Gagal memindahkan file poster.";
//                        $poster_filename = null; 
//                        $upload_error = true;
//                    }
//                }
//            }
//        }
        
        // Hanya lanjutkan INSERT jika tidak ada error dari upload atau validasi
        if (empty($message) && !$upload_error) { 

            // ============== INSERT INTO DATABASE ==================
            $sql = "
            INSERT INTO campaign (
                id_campaign, id_penerima, judul_campaign, deskripsi, target_dana, 
                dana_terkumpul, tanggal_mulai, tanggal_deadline, status, 
                created_at, updated_at
            ) VALUES (
                seq_campaign.nextval, :id_penerima, :judul, :deskripsi, :target,
                :dana, TO_DATE(:mulai,'YYYY-MM-DD'), TO_DATE(:deadline,'YYYY-MM-DD'),
                'Aktif', SYSDATE, SYSDATE
            )";

            $stmt = oci_parse($conn, $sql);
            
            $bindPoster = $poster_filename ?: null;

            if ($stmt) {
                oci_bind_by_name($stmt, ':id_penerima', $id_penerima);
                oci_bind_by_name($stmt, ':judul', $judul);
                oci_bind_by_name($stmt, ':deskripsi', $deskripsi);
                oci_bind_by_name($stmt, ':target', $target);
                oci_bind_by_name($stmt, ':dana', $dana_terkumpul);
                //oci_bind_by_name($stmt, ':poster', $bindPoster);
                oci_bind_by_name($stmt, ':mulai', $mulai);
                oci_bind_by_name($stmt, ':deadline', $deadline);

                if (@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                    if (oci_commit($conn)) {
                        // SUKSES: Bebaskan statement sebelum redirect
                        oci_free_statement($stmt); 
                        header('Location: crud-campaign.php');
                        exit;
                    } else {
                        $err = oci_error($conn);
                        $message = 'Gagal commit: ' . ($err['message'] ?? '');
                    }
                } else {
                    $err = oci_error($stmt);
                    $message = "Gagal menyimpan data: " . ($err['message'] ?? '');
                }

                // BEBASKAN $stmt jika eksekusi/commit gagal (hanya jika kode mencapai baris ini)
                oci_free_statement($stmt); 
            } else {
                $err = oci_error($conn);
                $message = 'Gagal mempersiapkan query: ' . ($err['message'] ?? '');
            }
        }
    }
}

// Catatan: Tidak ada oci_free_statement() di bagian paling akhir file.
// Semua resource dibebaskan di dalam blok logikanya masing-masing.
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Campaign - Takmir</title>
    <link rel="stylesheet" href="../assets/css/crud-campaign.css"> 
</head>

<body>

<header>
  <div class="nav-container">
    <h2 style="color:#4CAF50;">Takmir</h2>
    <ul class="nav-links">
      <li><a href="dashboard_penerima.php">Dashboard</a></li>
      <li><a class="active" href="crud-campaign.php">Campaign</a></li>
      <li><a href="crud-donasi.php">Donasi</a></li>
      <li><a href="crud-donatur.php">Donatur</a></li>
      <li><a href="../controller/logout_penerimaController.php">Logout</a></li>
    </ul>
  </div>
</header>


<div class="wrap">

    <div class="sidebar">
        <h3>Menu Admin</h3>
        <div class="side-list">
            <a href="dashboard_penerima.php">Dashboard</a>
            <a class="active" href="crud-campaign.php">Kelola Campaign</a>
            <a href="crud-donasi.php">Kelola Donasi</a>
            <a href="crud-donatur.php">Kelola Donatur</a>
            <a href="crud-laporan.php">Kelola Laporan</a>
        </div>
    </div>

    <div class="content">

        <div class="page-head">
            <h1>Buat Campaign Baru</h1>
            <p>Tambahkan campaign donasi baru</p>
        </div>

        <div class="panel" style="max-width:600px;">

            <?php if ($message): ?>
            <div style="padding:12px;margin-bottom:16px;border-radius:8px;background:#fff1f0;border:1px solid #e74c3c;color:#c0392b;">
                <?= esc($message) ?>
            </div>
            <?php endif; ?>

            <form method="post" action="" enctype="multipart/form-data">

                <div style="margin-bottom:16px;">
                    <label>Judul Campaign</label>
                    <input type="text" name="judul"
                                 value="<?= esc($_POST['judul'] ?? '') ?>"
                                 required>
                </div>

                <div style="margin-bottom:16px;">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="5" required><?= esc($_POST['deskripsi'] ?? '') ?></textarea>
                </div>
                

                <div style="margin-bottom:16px;">
                    <label>Target Dana (misal: 5000000)</label>
                    <input type="text" name="target"
                                 value="<?= esc($_POST['target'] ?? '') ?>"
                                 required>
                </div>

                <div style="margin-bottom:16px;">
                    <label>Dana Terkumpul (opsional, misal: 1000000)</label>
                    <input type="text" name="dana_terkumpul" id="dana_terkumpul"
                                 value="<?= esc($_POST['dana_terkumpul'] ?? '0') ?>">
                </div>

                <div style="display:flex;gap:10px;margin-bottom:16px;">
                    <div style="flex:1">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai"
                                     value="<?= esc($_POST['tanggal_mulai'] ?? date('Y-m-d')) ?>"
                                     required>
                    </div>
                    <div style="flex:1">
                        <label>Tanggal Deadline</label>
                        <input type="date" name="tanggal_deadline"
                                     value="<?= esc($_POST['tanggal_deadline'] ?? date('Y-m-d', strtotime('+30 days'))) ?>"
                                     required>
                    </div>
                </div>

                <div style="display:flex;gap:12px;">
                    <button class="btn" type="submit">Buat Campaign</button>
                    <a href="crud-campaign.php" class="btn secondary">Batal</a>
                </div>

            </form>

            <script>
                // Preview progress calculation based on target & dana_terkumpul
                function parseNum(v){
                    // Membersihkan input dari non-digit, lalu konversi ke integer
                    if(!v) return 0;
                    return parseInt(String(v).replace(/[^0-9]/g, ''), 10) || 0;
                }
                function updatePreview(){
                    var target = parseNum(document.querySelector('input[name="target"]').value);
                    var dana = parseNum(document.getElementById('dana_terkumpul').value);
                    var pct = (target>0)? Math.min(Math.max(Math.round((dana/target)*100),0),100) : 0;
                    var previewBar = document.getElementById('preview_bar');
                    var previewAmount = document.getElementById('preview_amount');
                    
                    // Format angka ke Rupiah
                    var formatRupiah = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format;

                    if(previewBar) previewBar.style.width = pct + '%';
                    if(previewBar) previewBar.textContent = pct + '%';
                    if(previewAmount) previewAmount.textContent = formatRupiah(dana) + ' terkumpul dari ' + formatRupiah(target);
                }
                
                // attach preview elements
                (function(){
                    var container = document.createElement('div');
                    container.style.marginTop = '18px';
                    container.innerHTML = '\n                        <h4 style="margin-bottom:8px;">Preview Progress</h4>\n                        <div id="preview_amount">Rp 0 terkumpul dari Rp 0</div>\n                        <div style="background:#eee;height:24px;border-radius:6px;overflow:hidden;margin-top:8px;">\n                            <div id="preview_bar" style="background:#4CAF50;height:100%;width:0%;display:flex;align-items:center;justify-content:center;color:white;font-size:12px;">0%</div>\n                        </div>\n                    ';
                    var form = document.querySelector('form');
                    if(form) form.appendChild(container);
                    var targetInput = document.querySelector('input[name="target"]');
                    var danaInput = document.getElementById('dana_terkumpul');
                    if(targetInput) targetInput.addEventListener('input', updatePreview);
                    if(danaInput) danaInput.addEventListener('input', updatePreview);
                    // initialize preview
                    updatePreview();
                })();
            </script>
        </div>

    </div>
</div>


<footer>
  <p>© 2025 Takmir Masjid — Sistem Donasi</p>
</footer>

</body>
</html>