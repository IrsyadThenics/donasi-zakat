<?php
/**
 * crud-campaign.php
 * Kelola campaign untuk penerima (list, edit, delete)
 * Database: Oracle (oci8)
 */
session_start();
require_once '../config/db.php';

// Pastikan penerima login
$username_penerima = $_SESSION['username'] ?? null;

if (!$username_penerima) {
	header('Location: ../auth/login_penerima.php');
	exit;
}

// Query untuk mendapatkan id_penerima dari username
$id_penerima = null;
$stmt_cek = oci_parse($conn, "SELECT id_penerima FROM penerima WHERE username = :username");
if ($stmt_cek) {
	oci_bind_by_name($stmt_cek, ':username', $username_penerima);
	if (oci_execute($stmt_cek)) {
		if ($row = oci_fetch_assoc($stmt_cek)) {
			$id_penerima = intval($row['ID_PENERIMA']);
		}
	}
	oci_free_statement($stmt_cek);
}

if (!$id_penerima) {
	header('Location: ../auth/login_penerima.php');
	exit;
}

function formatRupiah($num) {
	return 'Rp ' . number_format(floatval($num), 0, ',', '.');
}

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
	$id_campaign = intval($_POST['id_campaign'] ?? 0);
	if ($id_campaign > 0) {
		$stmt_del = oci_parse($conn, "DELETE FROM campaign WHERE id_campaign = :idc AND id_penerima = :idp");
		if ($stmt_del) {
			oci_bind_by_name($stmt_del, ':idc', $id_campaign);
			oci_bind_by_name($stmt_del, ':idp', $id_penerima);
			if (@oci_execute($stmt_del, OCI_NO_AUTO_COMMIT)) {
				if (oci_commit($conn)) {
					header('Location: crud-campaign.php?success=deleted');
					exit;
				}
			}
			oci_free_statement($stmt_del);
		}
	}
}

// ========================================
// QUERY STATISTIK CAMPAIGN
// ========================================

$total_campaign = 0;
$campaign_aktif = 0;
$campaign_ditutup = 0;
$total_target = 0;

$stmt_stat = oci_parse($conn, "
	SELECT 
		COUNT(*) as total_camp,
		COUNT(CASE WHEN status = 'Aktif' THEN 1 END) as aktif_count,
		COUNT(CASE WHEN status IN ('Selesai', 'Ditangguhkan') THEN 1 END) as ditutup_count,
		SUM(target_dana) as total_tgt
	FROM campaign
	WHERE id_penerima = :id_penerima
");

if ($stmt_stat) {
	oci_bind_by_name($stmt_stat, ':id_penerima', $id_penerima);
	if (oci_execute($stmt_stat)) {
		if ($row = oci_fetch_assoc($stmt_stat)) {
			$total_campaign = intval($row['TOTAL_CAMP'] ?? 0);
			$campaign_aktif = intval($row['AKTIF_COUNT'] ?? 0);
			$campaign_ditutup = intval($row['DITUTUP_COUNT'] ?? 0);
			$total_target = floatval($row['TOTAL_TGT'] ?? 0);
		}
	}
}
oci_free_statement($stmt_stat);

// ========================================
// QUERY CAMPAIGN LIST
// ========================================

$campaigns = [];
$stmt_list = oci_parse($conn, "
	SELECT 
		id_campaign,
		judul_campaign,
		target_dana,
		dana_terkumpul,
		status,
		tanggal_mulai,
		tanggal_deadline,
		deskripsi
	FROM campaign
	WHERE id_penerima = :id_penerima
	ORDER BY tanggal_mulai DESC
");

if ($stmt_list) {
	oci_bind_by_name($stmt_list, ':id_penerima', $id_penerima);
	if (oci_execute($stmt_list)) {
		while ($row = oci_fetch_assoc($stmt_list)) {
			$campaigns[] = $row;
		}
	}
}
oci_free_statement($stmt_list);

// Filter & Search (client-side)
$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

if ($search || $filter_status) {
	$campaigns = array_filter($campaigns, function($c) use ($search, $filter_status) {
		$match_search = empty($search) || stripos($c['JUDUL_CAMPAIGN'], $search) !== false;
		$match_status = empty($filter_status) || $c['STATUS'] === $filter_status;
		return $match_search && $match_status;
	});
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Campaign</title>

    <link rel="stylesheet" href="../assets/css/crud-campaign.css">
</head>

<body>

<!-- ========== HEADER (SAMA DONATUR) ========== -->
<header>

</header>


<!-- ========== WRAPPER ========== -->
<div class="wrap">

    <!-- SIDEBAR -->
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

    <!-- CONTENT -->
    <div class="content">

        <div class="page-head">
            <h1>Kelola Campaign</h1>
            <p>Tambah, edit, dan hapus data campaign</p>
        </div>

        <!-- SUMMARY CARDS (SAMA DONATUR) -->
        <div class="cards">
            <div class="card"><h4>Total Campaign</h4><div class="val"><?php echo htmlspecialchars($total_campaign); ?></div></div>
            <div class="card"><h4>Campaign Aktif</h4><div class="val"><?php echo htmlspecialchars($campaign_aktif); ?></div></div>
            <div class="card"><h4>Sudah Ditutup</h4><div class="val"><?php echo htmlspecialchars($campaign_ditutup); ?></div></div>
            <div class="card"><h4>Total Target</h4><div class="val"><?php echo formatRupiah($total_target); ?></div></div>
        </div>

        <!-- TOOLBAR (SAMA DONATUR) -->
        <div class="toolbar">
            <form method="GET" style="display:flex;gap:10px;flex:1;">
                <input type="text" name="search" class="input-search" placeholder="Cari campaign..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="status" class="filter-select">
                    <option value="">Semua Status</option>
                    <option value="Aktif" <?php echo $filter_status === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="Selesai" <?php echo $filter_status === 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                    <option value="Ditangguhkan" <?php echo $filter_status === 'Ditangguhkan' ? 'selected' : ''; ?>>Ditangguhkan</option>
                </select>
                <button type="submit" class="btn" style="padding:8px 12px;">Filter</button>
            </form>
            <button class="btn" onclick="location.href='create_campaign.php'">+ Tambah Campaign</button>
        </div>

        <!-- PANEL -->
        <div class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Campaign</th>
                            <th>Target</th>
                            <th>Terkumpul</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        if (empty($campaigns)) {
                            echo "<tr><td colspan='6' style='text-align:center;color:#999;'>Tidak ada campaign</td></tr>";
                        } else {
                            foreach ($campaigns as $c) {
                                $target = floatval($c['TARGET_DANA'] ?? 0);
                                $terkumpul = floatval($c['DANA_TERKUMPUL'] ?? 0);
                                $progress = $target > 0 ? min(max(($terkumpul / $target) * 100, 0), 100) : 0;
                                $status_class = match($c['STATUS'] ?? 'Aktif') {
                                    'Aktif' => 'active',
                                    'Selesai' => 'closed',
                                    'Ditangguhkan' => 'closed',
                                    default => 'closed'
                                };
                                
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($c['JUDUL_CAMPAIGN'] ?? '') . "</td>";
                                echo "<td>" . formatRupiah($target) . "</td>";
                                echo "<td>" . formatRupiah($terkumpul) . "</td>";
                                echo "<td><div style='background:#eee;height:24px;border-radius:4px;overflow:hidden;'>";
                                echo "<div style='background:#4CAF50;height:100%;width:" . intval($progress) . "%;display:flex;align-items:center;justify-content:center;color:white;font-size:12px;'>";
                                echo intval($progress) . "%";
                                echo "</div></div></td>";
                                echo "<td><span class='tag {$status_class}'>" . htmlspecialchars($c['STATUS'] ?? '') . "</span></td>";
                                echo "<td>";
                                echo "<a href='edit_campaign.php?id=" . urlencode($c['ID_CAMPAIGN']) . "' class='btn small secondary' style='text-decoration:none;'>Edit</a> ";
                                echo "<form method='POST' style='display:inline;' onsubmit='return confirm(\"Yakin hapus campaign ini?\")'>";
                                echo "<input type='hidden' name='action' value='delete'>";
                                echo "<input type='hidden' name='id_campaign' value='" . htmlspecialchars($c['ID_CAMPAIGN']) . "'>";
                                echo "<button type='submit' class='btn small danger'>Hapus</button>";
                                echo "</form>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>

                </table>
            </div>
        </div>

    </div>
</div>


<!-- FOOTER (SAMA DONATUR) -->
<footer>
  <p>© 2025 Takmir Masjid — Sistem Donasi</p>
</footer>

</body>
</html>

<?php
oci_close($conn);
?>
