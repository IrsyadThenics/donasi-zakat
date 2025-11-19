<?php
/**
 * crud-donasi.php
 * Kelola donasi untuk penerima (list, detail, delete)
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
	$id_donasi = intval($_POST['id_donasi'] ?? 0);
	if ($id_donasi > 0) {
		// Pastikan donasi ini milik campaign penerima ini
		$stmt_cek_del = oci_parse($conn, "
			SELECT d.id_donasi FROM donasi d
			JOIN campaign c ON d.id_campaign = c.id_campaign
			WHERE d.id_donasi = :id_donasi AND c.id_penerima = :id_penerima
		");
		if ($stmt_cek_del) {
			oci_bind_by_name($stmt_cek_del, ':id_donasi', $id_donasi);
			oci_bind_by_name($stmt_cek_del, ':id_penerima', $id_penerima);
			if (oci_execute($stmt_cek_del)) {
				if ($row_cek = oci_fetch_assoc($stmt_cek_del)) {
					// Donasi milik campaign penerima, boleh delete
					$stmt_del = oci_parse($conn, "DELETE FROM donasi WHERE id_donasi = :id_donasi");
					if ($stmt_del) {
						oci_bind_by_name($stmt_del, ':id_donasi', $id_donasi);
						if (@oci_execute($stmt_del, OCI_NO_AUTO_COMMIT)) {
							if (oci_commit($conn)) {
								header('Location: crud-donasi.php?success=deleted');
								exit;
							}
						}
						oci_free_statement($stmt_del);
					}
				}
			}
			oci_free_statement($stmt_cek_del);
		}
	}
}

// ========================================
// QUERY STATISTIK DONASI
// ========================================

$total_donasi = 0;
$total_nominal = 0;
$donatur_unik = 0;
$campaign_didukung = 0;

$stmt_stat = oci_parse($conn, "
	SELECT 
		COUNT(d.id_donasi) as total_donasi_count,
		SUM(d.jumlah_donasi) as total_nominal,
		COUNT(DISTINCT d.id_donatur) as donatur_unik,
		COUNT(DISTINCT d.id_campaign) as campaign_didukung
	FROM donasi d
	JOIN campaign c ON d.id_campaign = c.id_campaign
	WHERE c.id_penerima = :id_penerima
");

if ($stmt_stat) {
	oci_bind_by_name($stmt_stat, ':id_penerima', $id_penerima);
	if (oci_execute($stmt_stat)) {
		if ($row = oci_fetch_assoc($stmt_stat)) {
			$total_donasi = intval($row['TOTAL_DONASI_COUNT'] ?? 0);
			$total_nominal = floatval($row['TOTAL_NOMINAL'] ?? 0);
			$donatur_unik = intval($row['DONATUR_UNIK'] ?? 0);
			$campaign_didukung = intval($row['CAMPAIGN_DIDUKUNG'] ?? 0);
		}
	}
}
oci_free_statement($stmt_stat);

// ========================================
// QUERY DONASI LIST
// ========================================

$donasi_list = [];
$stmt_list = oci_parse($conn, "
	SELECT 
		d.id_donasi,
		d.id_donatur,
		d.jumlah_donasi,
		d.tanggal_donasi,
		d.metode,
		d.pesan,
		d.is_anonim,
		c.judul_campaign,
		c.id_campaign,
		NVL(dt.nama_donatur, 'Anonim') as nama_donatur
	FROM donasi d
	JOIN campaign c ON d.id_campaign = c.id_campaign
	LEFT JOIN donatur dt ON d.id_donatur = dt.id_donatur
	WHERE c.id_penerima = :id_penerima
	ORDER BY d.tanggal_donasi DESC
");

if ($stmt_list) {
	oci_bind_by_name($stmt_list, ':id_penerima', $id_penerima);
	if (oci_execute($stmt_list)) {
		while ($row = oci_fetch_assoc($stmt_list)) {
			$donasi_list[] = $row;
		}
	}
}
oci_free_statement($stmt_list);

// Filter & Search (client-side)
$search = trim($_GET['search'] ?? '');
$filter_metode = trim($_GET['metode'] ?? '');

if ($search || $filter_metode) {
	$donasi_list = array_filter($donasi_list, function($d) use ($search, $filter_metode) {
		$match_search = empty($search) || 
						stripos($d['NAMA_DONATUR'], $search) !== false ||
						stripos($d['JUDUL_CAMPAIGN'], $search) !== false;
		$match_metode = empty($filter_metode) || $d['METODE'] === $filter_metode;
		return $match_search && $match_metode;
	});
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Donasi</title>

    <link rel="stylesheet" href="../assets/css/crud-donasi.css">
</head>

<body>

<!-- ========== HEADER ========== -->
<header>
  <div class="nav-container">
    <h2 style="color:#4CAF50;">Takmir</h2>
    <ul class="nav-links">
      <li><a href="dashboard_penerima.php">Dashboard</a></li>
      <li><a href="crud-campaign.php">Campaign</a></li>
      <li><a class="active" href="crud-donasi.php">Donasi</a></li>
      <li><a href="crud-donatur.php">Donatur</a></li>
      <li><a href="../controller/logout_penerimaController.php">Logout</a></li>
    </ul>
  </div>
</header>


<!-- ========== WRAPPER ========== -->
<div class="wrap">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h3>Menu Admin</h3>
        <div class="side-list">
            <a href="dashboard_penerima.php">Dashboard</a>
            <a href="crud-campaign.php">Kelola Campaign</a>
            <a class="active" href="crud-donasi.php">Kelola Donasi</a>
            <a href="crud-donatur.php">Kelola Donatur</a>
            <a href="crud-laporan.php">Kelola Laporan</a>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <div class="page-head">
            <h1>Kelola Donasi</h1>
            <p>Daftar seluruh transaksi donasi</p>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="cards">
            <div class="card"><h4>Total Donasi</h4><div class="val"><?php echo htmlspecialchars($total_donasi); ?></div></div>
            <div class="card"><h4>Total Nominal</h4><div class="val"><?php echo formatRupiah($total_nominal); ?></div></div>
            <div class="card"><h4>Donatur Unik</h4><div class="val"><?php echo htmlspecialchars($donatur_unik); ?></div></div>
            <div class="card"><h4>Campaign Didukung</h4><div class="val"><?php echo htmlspecialchars($campaign_didukung); ?></div></div>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar">
            <form method="GET" style="display:flex;gap:10px;flex:1;">
                <input type="text" name="search" class="input-search" placeholder="Cari donasi..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="metode" class="filter-select">
                    <option value="">Semua Metode</option>
                    <option value="Transfer" <?php echo $filter_metode === 'Transfer' ? 'selected' : ''; ?>>Transfer</option>
                    <option value="Offline" <?php echo $filter_metode === 'Offline' ? 'selected' : ''; ?>>Offline</option>
                    <option value="QRIS" <?php echo $filter_metode === 'QRIS' ? 'selected' : ''; ?>>QRIS</option>
                </select>
                <button type="submit" class="btn" style="padding:8px 12px;">Filter</button>
            </form>
        </div>

        <!-- PANEL -->
        <div class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Donatur</th>
                            <th>Campaign</th>
                            <th>Jumlah</th>
                            <th>Tanggal</th>
                            <th>Metode</th>
                            <th>Pesan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        if (empty($donasi_list)) {
                            echo "<tr><td colspan='7' style='text-align:center;color:#999;'>Tidak ada donasi</td></tr>";
                        } else {
                            foreach ($donasi_list as $d) {
                                $nama = htmlspecialchars($d['NAMA_DONATUR'] ?? 'Anonim');
                                $campaign = htmlspecialchars($d['JUDUL_CAMPAIGN'] ?? '');
                                $nominal = formatRupiah($d['JUMLAH_DONASI'] ?? 0);
                                $tanggal = $d['TANGGAL_DONASI'] ?? '';
                                $metode = htmlspecialchars($d['METODE'] ?? '');
                                $pesan = htmlspecialchars(substr($d['PESAN'] ?? '', 0, 50));
                                $id_donasi = intval($d['ID_DONASI']);
                                
                                echo "<tr>";
                                echo "<td>" . $nama . "</td>";
                                echo "<td>" . $campaign . "</td>";
                                echo "<td>" . $nominal . "</td>";
                                echo "<td>" . $tanggal . "</td>";
                                echo "<td>" . $metode . "</td>";
                                echo "<td>" . ($pesan ? $pesan . '...' : '-') . "</td>";
                                echo "<td>";
                                echo "<form method='POST' style='display:inline;' onsubmit='return confirm(\"Yakin hapus donasi ini?\")'>";
                                echo "<input type='hidden' name='action' value='delete'>";
                                echo "<input type='hidden' name='id_donasi' value='" . htmlspecialchars($id_donasi) . "'>";
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


<!-- FOOTER -->
<footer>
  <p>© 2025 Takmir Masjid — Sistem Donasi</p>
</footer>

</body>
</html>

<?php
oci_close($conn);
?>
