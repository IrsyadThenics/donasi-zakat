<?php
/**
 * create_campaign.php
 * Form dan handler untuk penerima membuat campaign
 */
session_start();
require_once '../config/db.php';

// Pastikan penerima login
if (!isset($_SESSION['id_penerima'])) {
    header('Location: ../auth/login_penerima.php');
    exit;
}

$message = '';

function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $target = floatval(str_replace([',','.' ], ['',''], $_POST['target'] ?? 0));
    $mulai = $_POST['tanggal_mulai'] ?? null;
    $deadline = $_POST['tanggal_deadline'] ?? null;

    if ($judul === '' || $deskripsi === '' || $target <= 0 || !$mulai || !$deadline) {
        $message = 'Semua field wajib diisi dengan nilai yang valid.';
    } else {
        // Insert campaign using sequence
        $sql = "INSERT INTO campaign (id_campaign, id_penerima, judul_campaign, deskripsi, target_dana, dana_terkumpul, tanggal_mulai, tanggal_deadline, status, created_at, updated_at)
                VALUES (seq_campaign.nextval, :id_penerima, :judul, :deskripsi, :target, 0, TO_DATE(:mulai,'YYYY-MM-DD'), TO_DATE(:deadline,'YYYY-MM-DD'), 'Aktif', SYSDATE, SYSDATE)";

        $stmt = oci_parse($conn, $sql);
        if ($stmt) {
            $id_penerima = $_SESSION['id_penerima'];
            oci_bind_by_name($stmt, ':id_penerima', $id_penerima);
            oci_bind_by_name($stmt, ':judul', $judul);
            oci_bind_by_name($stmt, ':deskripsi', $deskripsi);
            oci_bind_by_name($stmt, ':target', $target);
            oci_bind_by_name($stmt, ':mulai', $mulai);
            oci_bind_by_name($stmt, ':deadline', $deadline);

            if (@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                if (oci_commit($conn)) {
                    // success -> redirect to campaign listing
                    header('Location: campaign.php');
                    exit;
                } else {
                    $err = oci_error($conn);
                    $message = 'Gagal commit: ' . ($err['message'] ?? '');
                }
            } else {
                $err = oci_error($stmt);
                $message = 'Gagal menyimpan campaign: ' . ($err['message'] ?? '');
            }
        } else {
            $err = oci_error($conn);
            $message = 'Gagal mempersiapkan query: ' . ($err['message'] ?? '');
        }
    }
}

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Buat Campaign - Donasi Masjid & Amal</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
    .card{max-width:800px;margin:24px auto;padding:20px;border:1px solid #eee;border-radius:8px}
    input,textarea{width:100%;padding:8px;margin-bottom:10px}
    .btn{padding:10px 14px;background:#007bff;color:#fff;border:none;border-radius:4px}
    .msg{padding:10px;margin-bottom:12px;border-radius:4px}
    .msg.error{background:#fff1f0;border:1px solid #e74c3c;color:#c0392b}
    </style>
</head>
<body>
    <div class="card">
        <h1>Buat Campaign Baru</h1>
        <?php if ($message): ?><div class="msg error"><?php echo esc($message); ?></div><?php endif; ?>
        <form method="post" action="">
            <label>Judul Campaign</label>
            <input type="text" name="judul" value="<?php echo esc($_POST['judul'] ?? ''); ?>" required>

            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="6" required><?php echo esc($_POST['deskripsi'] ?? ''); ?></textarea>

            <label>Target Dana (angka, tanpa simbol)</label>
            <input type="text" name="target" value="<?php echo esc($_POST['target'] ?? ''); ?>" required>

            <label>Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" value="<?php echo esc($_POST['tanggal_mulai'] ?? date('Y-m-d')); ?>" required>

            <label>Tanggal Deadline</label>
            <input type="date" name="tanggal_deadline" value="<?php echo esc($_POST['tanggal_deadline'] ?? date('Y-m-d', strtotime('+30 days'))); ?>" required>

            <div style="display:flex;gap:10px">
                <button class="btn" type="submit">Buat Campaign</button>
                <a class="btn" style="background:#6c757d;text-decoration:none;color:#fff;display:inline-block;padding:10px 14px" href="campaign.php">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>

<?php
oci_free_statement($stmt ?? null);
?>
