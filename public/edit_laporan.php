<?php
/**
 * edit_laporan.php
 * Form edit laporan penggunaan dana
 */
session_start();
require_once '../config/db.php';



// Pastikan penerima login
$username_penerima = $_SESSION['username'] ?? null;
if (!$username_penerima) {
    header('Location: ../auth/login_penerima.php');
    exit;
}

// Ambil id penerima
$stmt = oci_parse($conn, "SELECT id_penerima FROM penerima WHERE username = :u");
oci_bind_by_name($stmt, ":u", $username_penerima);
oci_execute($stmt);

$id_penerima = null;
if ($row = oci_fetch_assoc($stmt)) {
    $id_penerima = intval($row['ID_PENERIMA']);
}
oci_free_statement($stmt);

if (!$id_penerima) {
    header('Location: ../auth/login_penerima.php');
    exit;
}

// ===============================
// Ambil ID Laporan dari URL
// ===============================
$id_laporan = intval($_GET['id'] ?? 0);
if ($id_laporan <= 0) {
    header("Location: crud-laporan.php");
    exit;
}

// ===============================
// Ambil data laporan yang ingin diedit
// ===============================
$stmt_laporan = oci_parse(
    $conn,
    "SELECT 
        id_campaign,
        judul_laporan,
        total_dana_terkumpul,
        isi_laporan
     FROM laporan
     WHERE id_laporan = :id AND id_penerima = :p"
);

oci_bind_by_name($stmt_laporan, ":id", $id_laporan);
oci_bind_by_name($stmt_laporan, ":p",  $id_penerima);
oci_execute($stmt_laporan);

$data = oci_fetch_assoc($stmt_laporan);
oci_free_statement($stmt_laporan);

if (!$data) {
    echo "<h3>Laporan tidak ditemukan.</h3>";
    exit;
}

// ===============================
// Ambil daftar campaign milik penerima
// ===============================
$campaigns = [];
$cq = oci_parse(
    $conn,
    "SELECT id_campaign, judul_campaign 
     FROM campaign 
     WHERE id_penerima = :p
     ORDER BY id_campaign DESC"
);
oci_bind_by_name($cq, ":p", $id_penerima);
oci_execute($cq);

while ($r = oci_fetch_assoc($cq)) {
    $campaigns[] = $r;
}
oci_free_statement($cq);

$message = "";

// ===============================
// Jika form submit
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $judul              = trim($_POST['judul'] ?? '');
    $id_campaign        = intval($_POST['id_campaign'] ?? 0);
    $total              = floatval($_POST['total_dana'] ?? 0);
    $isi_laporan        = trim($_POST['isi_laporan'] ?? '');

    if ($judul == '' || $id_campaign == 0 || $total <= 0 || $isi_laporan == '') {
        $message = "Semua field wajib diisi.";
    } else {

        $stmt_up = oci_parse(
            $conn,
            "UPDATE laporan SET 
                id_campaign = :c,
                judul_laporan = :j,
                total_dana_terkumpul = :t,
                isi_laporan = :isi,
                tanggal_generate = SYSDATE
            WHERE id_laporan = :id AND id_penerima = :p"
        );

        oci_bind_by_name($stmt_up, ":c",   $id_campaign);
        oci_bind_by_name($stmt_up, ":j",   $judul);
        oci_bind_by_name($stmt_up, ":t",   $total);
        oci_bind_by_name($stmt_up, ":isi", $isi_laporan);
        oci_bind_by_name($stmt_up, ":id",  $id_laporan);
        oci_bind_by_name($stmt_up, ":p",   $id_penerima);

        if (@oci_execute($stmt_up, OCI_NO_AUTO_COMMIT)) {
            oci_commit($conn);
            header("Location: crud-laporan.php?msg=updated");
            exit;
        } else {
            oci_rollback($conn);
            $err = oci_error($stmt_up);
            $message = "Gagal memperbarui laporan: " . ($err['message'] ?? '');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Laporan</title>
    <link rel="stylesheet" href="../assets/css/crud-laporan.css">
<?php
/**
 * edit_laporan.php
 * Mengedit laporan penggunaan dana campaign
 */

// Ambil ID laporan
$id_laporan = intval($_GET['id'] ?? 0);
if ($id_laporan <= 0) {
    header("Location: crud-laporan.php");
    exit;
}

// Ambil id_penerima
$stmt = oci_parse($conn, "SELECT id_penerima FROM penerima WHERE username = :u");
oci_bind_by_name($stmt, ":u", $username_penerima);
oci_execute($stmt);

$id_penerima = null;
if ($row = oci_fetch_assoc($stmt)) {
    $id_penerima = intval($row['ID_PENERIMA']);
}
oci_free_statement($stmt);

if (!$id_penerima) {
    header('Location: ../auth/login_penerima.php');
    exit;
}

// ================================
// LOAD DATA LAPORAN
// ================================
$stmt = oci_parse(
    $conn,
    "SELECT 
        lr.id_laporan,
        lr.judul_laporan,
        lr.id_campaign,
        lr.total_dana_terkumpul,
        lr.isi_laporan
     FROM laporan lr
     WHERE lr.id_laporan = :id AND lr.id_penerima = :p"
);
oci_bind_by_name($stmt, ":id", $id_laporan);
oci_bind_by_name($stmt, ":p", $id_penerima);
oci_execute($stmt);

$data = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$data) {
    header("Location: crud-laporan.php?err=nodata");
    exit;
}

// Konversi ISI LAPORAN dari CLOB
$isi_laporan = "";
if (!empty($data['ISI_LAPORAN'])) {
    if (is_object($data['ISI_LAPORAN'])) {
        $isi_laporan = $data['ISI_LAPORAN']->load();
    } else {
        $isi_laporan = $data['ISI_LAPORAN'];
    }
}

$judul_lama = $data['JUDUL_LAPORAN'];
$total_lama = $data['TOTAL_DANA_TERKUMPUL'];
$id_campaign_lama = $data['ID_CAMPAIGN'];

// ================================
// LOAD CAMPAIGN LIST
// ================================
$campaigns = [];
$cq = oci_parse(
    $conn,
    "SELECT id_campaign, judul_campaign 
     FROM campaign 
     WHERE id_penerima = :p
     ORDER BY id_campaign DESC"
);
oci_bind_by_name($cq, ":p", $id_penerima);
oci_execute($cq);

while ($r = oci_fetch_assoc($cq)) {
    $campaigns[] = $r;
}
oci_free_statement($cq);

$message = "";

// ================================
// PROSES UPDATE
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $judul_baru   = trim($_POST['judul'] ?? '');
    $campaign_baru = intval($_POST['id_campaign'] ?? 0);
    $total_baru    = floatval($_POST['total_dana'] ?? 0);
    $isi_baru      = trim($_POST['isi_laporan'] ?? '');

    if ($judul_baru == '' || $campaign_baru == 0 || $total_baru <= 0 || $isi_baru == '') {
        $message = "Semua field wajib diisi.";
    } else {

        // UPDATE
        $stmt_update = oci_parse(
            $conn,
            "UPDATE laporan SET
                judul_laporan = :j,
                id_campaign = :c,
                total_dana_terkumpul = :t,
                isi_laporan = EMPTY_CLOB(),
                tanggal_generate = SYSDATE
             WHERE id_laporan = :id"
        );

        oci_bind_by_name($stmt_update, ":j", $judul_baru);
        oci_bind_by_name($stmt_update, ":c", $campaign_baru);
        oci_bind_by_name($stmt_update, ":t", $total_baru);
        oci_bind_by_name($stmt_update, ":id", $id_laporan);

        if (!oci_execute($stmt_update, OCI_NO_AUTO_COMMIT)) {
            $err = oci_error($stmt_update);
            $message = "Gagal update laporan: " . ($err['message'] ?? '');
            oci_free_statement($stmt_update);
        } else {
            oci_free_statement($stmt_update);

            // SIMPAN CLOB ISI LAPORAN
            $stmt_clob = oci_parse(
                $conn,
                "SELECT isi_laporan FROM laporan WHERE id_laporan = :id FOR UPDATE"
            );
            oci_bind_by_name($stmt_clob, ":id", $id_laporan);
            oci_execute($stmt_clob);

            if ($clob_row = oci_fetch_assoc($stmt_clob)) {
                $clob = $clob_row['ISI_LAPORAN'];
                if (is_object($clob)) {
                    $clob->save($isi_baru);
                }
            }
            oci_free_statement($stmt_clob);

            oci_commit($conn);
            header("Location: crud-laporan.php?msg=updated");
            exit;
        }
    }
}

?>

<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Laporan</title>
    <link rel="stylesheet" href="../assets/css/crud-laporan.css">


<style>
    .form-wrap {
        max-width: 650px;
        margin: auto;
        padding: 20px;
    }
    label {
        font-weight: 600;
        margin-top: 10px;
        display: block;
    }
    input, select, textarea {
        width: 100%;
        padding: 8px;
        margin-top: 4px;
        margin-bottom: 10px;
    }
    .btn {
        padding:10px 18px;
        background:#2f4a2f;
        color:#fff;
        border:none;
        cursor:pointer;
        border-radius:4px;
    }
    .back {
        display:inline-block;
        margin-bottom:15px;
    }
    .msg {
        background:#ffecec;
        border:1px solid #cc0000;
        padding:10px;
        margin-bottom:10px;
        color:#900;
    }
</style>


</head>

<body>

<div class="form-wrap">


<h2>Edit Laporan</h2>
<a href="crud-laporan.php" class="back">← Kembali</a>

<?php if ($message): ?>
    <div class="msg"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST">

    <label>Judul Laporan</label>
    <input type="text" name="judul" value="<?= htmlspecialchars($judul_lama) ?>" required>

    <label>Pilih Campaign</label>
    <select name="id_campaign" required>
        <?php foreach ($campaigns as $c): ?>
            <option value="<?= $c['ID_CAMPAIGN'] ?>"
                <?= ($c['ID_CAMPAIGN'] == $id_campaign_lama ? 'selected' : '') ?>>
                <?= htmlspecialchars($c['JUDUL_CAMPAIGN']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Total Dana Digunakan</label>
    <input type="number" min="1" name="total_dana" value="<?= $total_lama ?>" required>

    <label>Isi Laporan</label>
    <textarea name="isi_laporan" rows="8" required><?= htmlspecialchars($isi_laporan) ?></textarea>

    <button type="submit" class="btn">Simpan Perubahan</button>
</form>


</div>
</body>
</html>

<?php
oci_close($conn);
?>


