<?php
session_start();
require_once '../config/db.php';

// Pastikan penerima login
$username_penerima = $_SESSION['username'] ?? null;
if (!$username_penerima) {
    header("Location: ../auth/login_penerima.php");
    exit;
}

// Ambil ID penerima
$stmt_penerima = oci_parse($conn, "SELECT id_penerima FROM penerima WHERE username = :usr");
oci_bind_by_name($stmt_penerima, ":usr", $username_penerima);
oci_execute($stmt_penerima);

if ($row = oci_fetch_assoc($stmt_penerima)) {
    $id_penerima = $row['ID_PENERIMA'];
} else {
    header("Location: ../auth/login_penerima.php");
    exit;
}
oci_free_statement($stmt_penerima);

// Ambil ID donasi yg ingin diedit
$id_donasi = $_GET['id'] ?? $_GET['id_donasi'] ?? null;


if (!$id_donasi) {
    header("Location: crud-donasi.php");
    exit;
}

$message = "";
$message_class = "";

// Ambil data donasi
$sql = "
SELECT d.*, c.judul_campaign 
FROM donasi d 
JOIN campaign c ON d.id_campaign = c.id_campaign 
WHERE d.id_donasi = :id
";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":id", $id_donasi);
oci_execute($stmt);

$data = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$data) {
    $_SESSION['message'] = "Data donasi tidak ditemukan!";
    $_SESSION['message_class'] = "error";
    header("Location: crud-donasi.php");
    exit;
}

$jumlah_lama = floatval($data['JUMLAH_DONASI']);

// PROSES UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil input
    $jumlah_baru = floatval(str_replace(['.', ','], '', $_POST['jumlah'] ?? 0));
    $metode = trim($_POST['metode'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');

    if ($jumlah_baru <= 0) {
        $message = "Jumlah tidak valid!";
        $message_class = "error";
    } else {

        $selisih = $jumlah_baru - $jumlah_lama;

        // Update donasi
        $sql_up = "
        UPDATE donasi SET jumlah_donasi = :jml, metode = :met, pesan = :pes
        WHERE id_donasi = :id
        ";

        $st_up = oci_parse($conn, $sql_up);
        oci_bind_by_name($st_up, ":jml", $jumlah_baru);
        oci_bind_by_name($st_up, ":met", $metode);
        oci_bind_by_name($st_up, ":pes", $pesan);
        oci_bind_by_name($st_up, ":id", $id_donasi);

        if (!oci_execute($st_up, OCI_NO_AUTO_COMMIT)) {
            $message = "Gagal update donasi!";
            $message_class = "error";
            oci_rollback($conn);
        } else {

            // Update dana campaign
            if ($selisih != 0) {
                $sql_cam = "
                UPDATE campaign SET dana_terkumpul = NVL(dana_terkumpul,0) + :s
                WHERE id_campaign = :cid
                ";
                $st_cam = oci_parse($conn, $sql_cam);
                oci_bind_by_name($st_cam, ":s", $selisih);
                oci_bind_by_name($st_cam, ":cid", $data['ID_CAMPAIGN']);

                if (!oci_execute($st_cam, OCI_NO_AUTO_COMMIT)) {
                    $message = "Gagal update dana campaign!";
                    $message_class = "error";
                    oci_rollback($conn);
                }
            }

            if (oci_commit($conn)) {
                $_SESSION['message'] = "Donasi berhasil diperbarui!";
                $_SESSION['message_class'] = "success";
                header("Location: edit_donasi-penerima.php");
                exit;
            }
        }
    }
}

// Data untuk ditampilkan pada form
$current_jumlah = $_POST['jumlah'] ?? $data['JUMLAH_DONASI'];
$current_metode = $_POST['metode'] ?? $data['METODE'];
$current_pesan = $_POST['pesan'] ?? $data['PESAN'];
?>

<!DOCTYPE html>

<html>
<head>
    <meta charset="utf-8">
    <title>Edit Donasi - Dashboard Penerima</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        .form-card {
            max-width:650px;
            margin:20px auto;
            background:#fff;
            padding:20px;
            border-radius:8px;
            border:1px solid #ddd;
        }
        label { font-weight:bold; margin-top:8px; display:block; }
        input, textarea, select {
            width:100%;
            padding:10px;
            border:1px solid #ccc;
            margin-top:4px;
            border-radius:6px;
        }
        .btn {
            padding:10px 18px;
            background:#28a745;
            border:none;
            color:white;
            border-radius:6px;
            text-decoration:none;
            cursor:pointer;
        }
        .btn-back { background:#6c757d; }
        .message {
            margin-bottom:12px;
            padding:10px;
            border-radius:6px;
            font-weight:bold;
        }
        .message.success { background:#e5ffe8; border:1px solid #28a745; color:#1e7e34; }
        .message.error { background:#ffe5e5; border:1px solid #cc0000; color:#a00000; }
    </style>
</head>
<body>

<div class="form-card">


<h2>Edit Donasi</h2>
<p><strong>Campaign:</strong> <?= htmlspecialchars($data['JUDUL_CAMPAIGN']) ?></p>

<?php if ($message): ?>
    <div class="message <?= $message_class ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="post">

    <label>Jumlah Donasi</label>
    <input type="text" name="jumlah" value="<?= htmlspecialchars($current_jumlah) ?>" required>

    <label>Metode Pembayaran</label>
    <select name="metode">
        <option value="Transfer Bank" <?= $current_metode == 'Transfer Bank' ? 'selected' : '' ?>>Transfer Bank</option>
        <option value="E-Wallet" <?= $current_metode == 'E-Wallet' ? 'selected' : '' ?>>E-Wallet</option>
        <option value="Tunai" <?= $current_metode == 'Tunai' ? 'selected' : '' ?>>Tunai</option>
    </select>

    <label>Pesan (opsional)</label>
    <textarea name="pesan" rows="4"><?= htmlspecialchars($current_pesan) ?></textarea>

    <br><br>
    <button type="submit" class="btn">Simpan</button>
    <a href="crud-donasi.php" class="btn btn-back">Batal</a>
</form>


</div>

</body>
</html>
<?php oci_close($conn); ?>
