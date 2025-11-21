<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan — Dashboard Takmir</title>

    <!-- GLOBAL CSS CRUD LAPORAN -->
    <?php
/**
 * crud-laporan.php
 * Menampilkan & mengelola laporan penggunaan dana campaign
 * Database Oracle (oci8)
 */
session_start();
require_once '../config/db.php';

// -----------------------------------------------------
// Pastikan penerima login
// -----------------------------------------------------
$username_penerima = $_SESSION['username'] ?? null;

if (!$username_penerima) {
    header("Location: ../auth/login_penerima.php");
    exit;
}

// -----------------------------------------------------
// Ambil id_penerima berdasarkan username login
// -----------------------------------------------------
$id_penerima = null;
$stmt = oci_parse($conn, "SELECT id_penerima FROM penerima WHERE username = :username");
oci_bind_by_name($stmt, ":username", $username_penerima);
oci_execute($stmt);

if ($row = oci_fetch_assoc($stmt)) {
    $id_penerima = intval($row['ID_PENERIMA']);
}
oci_free_statement($stmt);

if (!$id_penerima) {
    header("Location: ../auth/login_penerima.php");
    exit;
}

// -----------------------------------------------------
// Jika klik HAPUS laporan
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id_laporan = intval($_POST['id_laporan']);

    $del = oci_parse($conn, "DELETE FROM laporan WHERE id_laporan = :id AND id_penerima = :p");
    oci_bind_by_name($del, ":id", $id_laporan);
    oci_bind_by_name($del, ":p", $id_penerima);

    if (@oci_execute($del, OCI_NO_AUTO_COMMIT)) {
        oci_commit($conn);
        header("Location: crud-laporan.php?msg=deleted");
        exit;
    } else {
        oci_rollback($conn);
        $err = oci_error($del);
        die("Gagal menghapus laporan: " . $err['message']);
    }
}

// -----------------------------------------------------
// Ambil semua laporan milik penerima
// -----------------------------------------------------
$laporan = [];

$sql = oci_parse(
    $conn,
    "SELECT
        l.id_laporan,
        l.judul_laporan,
        l.total_dana_terkumpul,
        l.tanggal_generate,
        c.judul_campaign
     FROM laporan l
     JOIN campaign c ON l.id_campaign = c.id_campaign
     WHERE l.id_penerima = :p
     ORDER BY l.tanggal_generate DESC"
);

oci_bind_by_name($sql, ":p", $id_penerima);
oci_execute($sql);

while ($row = oci_fetch_assoc($sql)) {
    $laporan[] = $row;
}

oci_free_statement($sql);

// -----------------------------------------------------
// Helper format rupiah
// -----------------------------------------------------
function rupiah($n) {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

?>

<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan — Dashboard Takmir</title>


<link rel="stylesheet" href="../assets/css/crud-donatur.css">
<link rel="stylesheet" href="../assets/css/crud-laporan.css">


</head>

<body>

<!-- ================= HEADER ================= -->

<header>



</header>

<!-- ================= WRAPPER ================= -->

<div class="wrap">


<!-- SIDEBAR -->
<aside class="sidebar">
    <h3 >Menu Admin</h3>

    <div class="side-list">
        <a href="dashboard_penerima.php">Dashboard</a>
        <a href="crud-campaign.php">Kelola Campaign</a>
        <a href="crud-donasi.php">Kelola Donasi</a>
        <a href="crud-donatur.php">Kelola Donatur</a>
        <a href="crud-laporan.php" class="active">Kelola Laporan</a>
    </div>
</aside>

<!-- CONTENT -->
<main class="content">

    <h1 style="color:#2f4a2f; font-size:26px; font-weight:700;">
        Kelola Laporan
    </h1>
    <p style="color:#777;">Transparansi penggunaan dana setiap campaign</p> <br><br>

    <!-- BUTTON TAMBAH -->
    <div style="margin-bottom:15px;">
        <a href="tambah_laporan.php" class="btn" style="padding:8px 14px;">
            + Tambah Laporan
        </a>
    </div>

    <!-- TABLE PANEL -->
    <div class="panel">
        <h3 style="margin-bottom:15px; color:#2f4a2f;">Daftar Laporan</h3>

        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Judul Laporan</th>
                    <th>Campaign</th>
                    <th>Total Dana</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($laporan)) : ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:#888;">
                            Belum ada laporan
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($laporan as $l) : ?>
                        <tr>
                            <td><?= htmlspecialchars($l['TANGGAL_GENERATE']) ?></td>
                            <td><?= htmlspecialchars($l['JUDUL_LAPORAN']) ?></td>
                            <td><?= htmlspecialchars($l['JUDUL_CAMPAIGN']) ?></td>
                            <td><?= rupiah($l['TOTAL_DANA_TERKUMPUL']) ?></td>
                            <td>

                                <a href="edit_laporan.php?id=<?= $l['ID_LAPORAN'] ?>"
                                   class="btn small secondary"
                                   style="text-decoration:none;">
                                   Edit
                                </a>

                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Yakin menghapus laporan ini?')">
                                    <input type="hidden" name="id_laporan" value="<?= $l['ID_LAPORAN'] ?>">
                                    <button type="submit" name="hapus" class="btn small danger">
                                        Hapus
                                    </button>
                                </form>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

            </tbody>
        </table>
    </div>

</main>


</div>

<!-- ================= FOOTER ================= -->

<footer>
    © 2025 Masjid Al-Falah — Dashboard Takmir
</footer>
<script src="../assets/js/crud-laporan.js"></script>

</body>
</html>

<?php oci_close($conn); ?>



