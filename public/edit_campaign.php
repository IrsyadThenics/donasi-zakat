<?php
/**
 * edit_campaign.php
 * Edit campaign untuk penerima — versi aman & stabil
 */

session_start();
require_once '../config/db.php';

// ================================
// Fungsi Helper
// ================================

// htmlspecialchars yang aman
function safe($v){
    if (is_array($v) || is_object($v)) return "";
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// Oracle CLOB reader
function readClob($v){
    if (is_object($v) && method_exists($v, "load")) {
        return $v->load();
    }
    return $v;
}

function formatRupiah($num) {
    return 'Rp ' . number_format(floatval($num), 0, ',', '.');
}

// ================================
// Pastikan penerima login
// ================================
$username_penerima = $_SESSION['username'] ?? null;
if (!$username_penerima) {
    header('Location: ../auth/login_penerima.php');
    exit;
}

// Ambil id_penerima
$id_penerima = null;
$stmt_chk = oci_parse($conn, "SELECT id_penerima FROM penerima WHERE username = :username");
oci_bind_by_name($stmt_chk, ':username', $username_penerima);

if (oci_execute($stmt_chk) && ($r = oci_fetch_assoc($stmt_chk))) {
    $id_penerima = intval($r['ID_PENERIMA']);
}
oci_free_statement($stmt_chk);

if (!$id_penerima) {
    header('Location: ../auth/login_penerima.php');
    exit;
}

// ================================
// Ambil ID Campaign
// ================================
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: crud-campaign.php');
    exit;
}

// Ambil campaign (harus punya penerima ini)
$campaign = null;
$stmt = oci_parse($conn, "SELECT * FROM campaign WHERE id_campaign = :id AND id_penerima = :idp");
oci_bind_by_name($stmt, ':id', $id);
oci_bind_by_name($stmt, ':idp', $id_penerima);

if (oci_execute($stmt)) {
    $campaign = oci_fetch_assoc($stmt);
}
oci_free_statement($stmt);

if (!$campaign) {
    header('Location: crud-campaign.php');
    exit;
}

// Convert CLOB agar tidak error
if (isset($campaign['DESKRIPSI'])) {
    $campaign['DESKRIPSI'] = readClob($campaign['DESKRIPSI']);
}

// ================================
// PROSES UPDATE
// ================================
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $target = floatval($_POST['target'] ?? 0);
    $mulai = $_POST['tanggal_mulai'] ?? null;
    $deadline = $_POST['tanggal_deadline'] ?? null;
    $status = $_POST['status'] ?? 'Aktif';
    $dana_terkumpul = floatval($_POST['dana_terkumpul'] ?? 0);

    if ($judul === '' || $deskripsi === '' || $target <= 0 || !$mulai || !$deadline) {
        $message = 'Semua field wajib diisi dan target harus > 0.';
    } else {

        // Upload poster jika ada
        $poster_filename = null;

        if (!empty($_FILES['poster']['name'])) {
            $up = $_FILES['poster'];
            $allowed = ['image/jpeg', 'image/png', 'image/jpg'];

            if ($up['error'] === 0 && in_array($up['type'], $allowed) && $up['size'] <= 2 * 1024 * 1024) {

                $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
                $poster_dir = __DIR__ . '/../assets/img/campaign/';

                if (!is_dir($poster_dir)) mkdir($poster_dir, 0755, true);

                $poster_filename = uniqid('cp_') . '.' . $ext;

                if (!move_uploaded_file($up['tmp_name'], $poster_dir . $poster_filename)) {
                    $poster_filename = null;
                }
            }
        }

        // Update query
        $sql = "
            UPDATE campaign SET
                judul_campaign = :judul,
                deskripsi = :deskripsi,
                target_dana = :target,
                dana_terkumpul = :dana,
                poster = NVL(:poster, poster),
                tanggal_mulai = TO_DATE(:mulai, 'YYYY-MM-DD'),
                tanggal_deadline = TO_DATE(:deadline, 'YYYY-MM-DD'),
                status = :status,
                updated_at = SYSDATE
            WHERE id_campaign = :id AND id_penerima = :idp
        ";

        $s2 = oci_parse($conn, $sql);

        oci_bind_by_name($s2, ':judul', $judul);
        oci_bind_by_name($s2, ':deskripsi', $deskripsi);
        oci_bind_by_name($s2, ':target', $target);
        oci_bind_by_name($s2, ':dana', $dana_terkumpul);
        oci_bind_by_name($s2, ':mulai', $mulai);
        oci_bind_by_name($s2, ':deadline', $deadline);
        oci_bind_by_name($s2, ':status', $status);
        oci_bind_by_name($s2, ':id', $id);
        oci_bind_by_name($s2, ':idp', $id_penerima);

        // poster binding
        if ($poster_filename !== null) {
            oci_bind_by_name($s2, ':poster', $poster_filename);
        } else {
            $nullPoster = null;
            oci_bind_by_name($s2, ':poster', $nullPoster);
        }

        if (@oci_execute($s2, OCI_NO_AUTO_COMMIT)) {
            oci_commit($conn);
            header('Location: crud-campaign.php?success=updated');
            exit;
        } else {
            $err = oci_error($s2);
            $message = 'Gagal update: ' . ($err['message'] ?? '(unknown)');
        }

        oci_free_statement($s2);
    }
}

// Refresh data terbaru
$stmtR = oci_parse($conn, "SELECT * FROM campaign WHERE id_campaign = :id AND id_penerima = :idp");
oci_bind_by_name($stmtR, ':id', $id);
oci_bind_by_name($stmtR, ':idp', $id_penerima);
oci_execute($stmtR);
$campaign = oci_fetch_assoc($stmtR);
oci_free_statement($stmtR);

// Convert CLOB lagi
if (isset($campaign['DESKRIPSI'])) {
    $campaign['DESKRIPSI'] = readClob($campaign['DESKRIPSI']);
}

// Hitung progress
$target_val = floatval($campaign['TARGET_DANA'] ?? 0);
$terkumpul_val = floatval($campaign['DANA_TERKUMPUL'] ?? 0);
$progress = $target_val > 0 ? min(max(($terkumpul_val / $target_val) * 100, 0), 100) : 0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Campaign</title>
    <link rel="stylesheet" href="../assets/css/crud-campaign.css">
</head>

<body>

<!-- Header, Sidebar, dan seluruh HTML tetap sama -->
<!-- Saya tidak hapus agar struktur kamu tidak berubah -->

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
            <h1>Edit Campaign</h1>
            <p>Perbarui data campaign</p>
        </div>

        <div class="cards" style="max-width:900px;">
            <div class="card" style="flex:1;">
                <h4>Progress</h4>
                <div style="margin-top:8px;">
                    <?= formatRupiah($terkumpul_val) ?> terkumpul dari <?= formatRupiah($target_val) ?>
                </div>
                <div style="background:#eee;height:26px;border-radius:6px;overflow:hidden;margin-top:8px;">
                    <div style="background:#4CAF50;height:100%;width:<?= intval($progress) ?>%; display:flex;align-items:center;justify-content:center;color:white;font-size:13px;">
                        
                        <?= intval($progress) ?>%
                    </div>
                </div>
            </div>
        </div>

        <div class="panel" style="max-width:700px;">

            <?php if ($message): ?>
                <div style="padding:12px;margin-bottom:16px;border-radius:8px;background:#fff1f0;border:1px solid #e74c3c;color:#c0392b;">
                    <?= safe($message) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="?id=<?= safe($id) ?>" enctype="multipart/form-data">

                <div style="margin-bottom:12px;">
                    <label>Judul Campaign</label>
                    <input type="text" name="judul"
                        value="<?= safe($campaign['JUDUL_CAMPAIGN']) ?>" required>
                </div>

                <div style="margin-bottom:12px;">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="6" required><?= safe($campaign['DESKRIPSI']) ?></textarea>
                </div>

                <div style="margin-bottom:12px;">
                    <label>Target Dana</label>
                    <input type="text" name="target" value="<?= safe($campaign['TARGET_DANA']) ?>" required>
                </div>

                <div style="margin-bottom:12px;">
                    <label>Dana Terkumpul</label>
                    <input type="text" name="dana_terkumpul" value="<?= safe($campaign['DANA_TERKUMPUL']) ?>">
                </div>

                <div style="margin-bottom:12px;">
                    <label>Poster</label>
                    <?php if (!empty($campaign['POSTER'])): ?>
                        <div>
                            <img src="../assets/img/campaign/<?= safe($campaign['POSTER']) ?>" 
                                style="max-width:180px;border-radius:6px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="poster" accept="image/*">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai"
                            value="<?= date('Y-m-d', strtotime($campaign['TANGGAL_MULAI'])) ?>" required>
                    </div>
                    <div>
                        <label>Tanggal Deadline</label>
                        <input type="date" name="tanggal_deadline"
                            value="<?= date('Y-m-d', strtotime($campaign['TANGGAL_DEADLINE'])) ?>" required>
                    </div>
                </div>

                <div style="margin-bottom:12px;">
                    <label>Status</label>
                    <select name="status">
                        <option value="Aktif" <?= $campaign['STATUS']=='Aktif'?'selected':'' ?>>Aktif</option>
                        <option value="Selesai" <?= $campaign['STATUS']=='Selesai'?'selected':'' ?>>Selesai</option>
                        <option value="Ditangguhkan" <?= $campaign['STATUS']=='Ditangguhkan'?'selected':'' ?>>Ditangguhkan</option>
                    </select>
                </div>

                <button class="btn" type="submit">Simpan Perubahan</button>
                <a href="crud-campaign.php" class="btn secondary">Batal</a>

            </form>

        </div>

    </div>
</div>

<footer>
  <p>© 2025 Takmir Masjid — Sistem Donasi</p>
</footer>

</body>
</html>

<?php oci_close($conn); ?>
