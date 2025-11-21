<?php
/**
 * Dashboard Penerima (Recipient Dashboard)
 * Menampilkan ringkasan donasi, laporan, dan statistik penerima
 * Database: Oracle (oci8)
 */
session_start();
require_once '../config/db.php';

// Cek apakah user sudah login sebagai penerima
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

// ========================================
// QUERY STATISTIK PENERIMA
// ========================================

// Total donasi masuk
$total_donasi_masuk = 0;
$total_penyaluran = 0;
$campaign_aktif = 0;
$total_laporan = 0;

$stmt_stat = oci_parse($conn, "
	SELECT 
		SUM(d.jumlah_donasi) as total_masuk,
		COUNT(DISTINCT c.id_campaign) as campaign_count,
		COUNT(DISTINCT lr.id_laporan) as laporan_count
	FROM campaign c
	LEFT JOIN donasi d ON c.id_campaign = d.id_campaign
	LEFT JOIN laporan lr ON c.id_campaign = lr.id_campaign
	WHERE c.id_penerima = :id_penerima
");

if ($stmt_stat) {
	oci_bind_by_name($stmt_stat, ':id_penerima', $id_penerima);
	if (oci_execute($stmt_stat)) {
		if ($row = oci_fetch_assoc($stmt_stat)) {
			$total_donasi_masuk = floatval($row['TOTAL_MASUK'] ?? 0);
			$campaign_aktif = intval($row['CAMPAIGN_COUNT'] ?? 0);
			$total_laporan = intval($row['LAPORAN_COUNT'] ?? 0);
		}
	}
}
oci_free_statement($stmt_stat);

// Total penyaluran (dari laporan)
$stmt_penyaluran = oci_parse($conn, "
	SELECT SUM(total_dana_terkumpul) as total_penyaluran
	FROM laporan
	WHERE id_penerima = :id_penerima
");

if ($stmt_penyaluran) {
	oci_bind_by_name($stmt_penyaluran, ':id_penerima', $id_penerima);
	if (oci_execute($stmt_penyaluran)) {
		if ($row = oci_fetch_assoc($stmt_penyaluran)) {
			$total_penyaluran = floatval($row['TOTAL_PENYALURAN'] ?? 0);
		}
	}
}
oci_free_statement($stmt_penyaluran);

// ========================================
// QUERY AKTIVITAS TERBARU (Campaign + Laporan)
// ========================================
$aktivitas_list = [];

// Campaign terbaru
$stmt_campaign = oci_parse($conn, "
	SELECT 
		c.id_campaign,
		c.tanggal_mulai,
		'Campaign' as jenis,
		c.judul_campaign,
		c.target_dana,
		c.status,
		NULL as laporan_id
	FROM campaign c
	WHERE c.id_penerima = :id_penerima
	ORDER BY c.tanggal_mulai DESC
");

if ($stmt_campaign) {
	oci_bind_by_name($stmt_campaign, ':id_penerima', $id_penerima);
	if (oci_execute($stmt_campaign)) {
		while ($row = oci_fetch_assoc($stmt_campaign)) {
			$row['TANGGAL'] = $row['TANGGAL_MULAI'];
			$row['NOMINAL'] = $row['TARGET_DANA'];
			$aktivitas_list[] = $row;
		}
	}
}
oci_free_statement($stmt_campaign);

// Laporan terbaru
$stmt_laporan = oci_parse($conn, "
	SELECT 
		lr.id_laporan,
		lr.tanggal_generate,
		'Laporan' as jenis,
		lr.judul_laporan,
		lr.total_dana_terkumpul,
		'Disetujui' as status,
		lr.id_laporan as laporan_id
	FROM laporan lr
	WHERE lr.id_penerima = :id_penerima
	ORDER BY lr.tanggal_generate DESC
");

if ($stmt_laporan) {
	oci_bind_by_name($stmt_laporan, ':id_penerima', $id_penerima);
	if (oci_execute($stmt_laporan)) {
		while ($row = oci_fetch_assoc($stmt_laporan)) {
			$row['TANGGAL'] = $row['TANGGAL_GENERATE'];
			$row['NOMINAL'] = $row['TOTAL_DANA_TERKUMPUL'];
			$aktivitas_list[] = $row;
		}
	}
}
oci_free_statement($stmt_laporan);

// Sort by tanggal DESC
usort($aktivitas_list, function($a, $b) {
	$dateA = strtotime($a['TANGGAL'] ?? '');
	$dateB = strtotime($b['TANGGAL'] ?? '');
	return $dateB - $dateA;
});

// Limit to 10 most recent
$aktivitas_list = array_slice($aktivitas_list, 0, 10);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Takmir</title>
    <link rel="stylesheet" href="../assets/css/dashboard-penerima.css">
</head>

<body>

<!-- ============================
     HEADER (SAMA KAYA BERANDA)
============================ -->
<header>
    
</header>

<!-- WRAPPER -->
<div class="wrap">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <h3>Menu Admin</h3>

        <div class="side-list">
            <a href="dashboard_penerima.php" class="active">Dashboard</a>
            <a href="crud-campaign.php">Kelola Campaign</a>
            <a href="crud-donasi.php">Kelola Donasi</a>
            <a href="crud-donatur.php">Kelola Donatur</a>
            <a href="crud-laporan.php">Kelola Laporan</a>
            <a href="../controller/logout_penerimaController.php">Logout</a>
        </div>
    </aside>

    <!-- CONTENT -->
    <main class="content">

        <div class="page-head">
            <h1>Dashboard Takmir</h1>
            <p class="muted">Ringkasan aktivitas dan pengelolaan masjid</p>
        </div>

        <!-- CARDS -->
        <div class="cards">
            <div class="card">
                <h3>Total Donasi Masuk</h3>
                <div class="val"><?php echo formatRupiah($total_donasi_masuk); ?></div>
            </div>

            <div class="card">
                <h3>Total Penyaluran</h3>
                <div class="val"><?php echo formatRupiah($total_penyaluran); ?></div>
            </div>

            <div class="card">
                <h3>Campaign Aktif</h3>
                <div class="val"><?php echo htmlspecialchars($campaign_aktif); ?> Program</div>
            </div>

            <div class="card">
                <h3>Total Laporan Donatur</h3>
                <div class="val"><?php echo htmlspecialchars($total_laporan); ?> Laporan</div>
            </div>
        </div>

        <!-- TABEL -->
        <div class="panel">
            <h2>Aktivitas & Laporan Terbaru</h2>

            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Judul</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        
                    </tr>

                    <?php
                    if (empty($aktivitas_list)) {
                        echo "<tr><td colspan='6' style='text-align:center;color:#999;'>Belum ada aktivitas</td></tr>";
                    } else {
                        foreach ($aktivitas_list as $ak) {
                            $tanggal = !empty($ak['TANGGAL']) && strtotime($ak['TANGGAL']) !== false 
                                ? date('Y-m-d', strtotime($ak['TANGGAL'])) 
                                : '-';
                            $jenis = htmlspecialchars($ak['JENIS'] ?? '');
                            $judul = htmlspecialchars($ak['JENIS'] === 'Campaign' ? ($ak['JUDUL_CAMPAIGN'] ?? '') : ($ak['JUDUL_LAPORAN'] ?? ''));
                            $nominal = !empty($ak['NOMINAL']) ? formatRupiah($ak['NOMINAL']) : '-';
                            $status_class = match($ak['STATUS'] ?? 'Menunggu') {
                                'Aktif' => 'active',
                                'Disetujui' => 'active',
                                'Selesai' => 'closed',
                                'Ditangguhkan' => 'closed',
                                default => 'closed'
                            };
                            $status_text = $ak['STATUS'] ?? 'Menunggu';
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($tanggal) . "</td>";
                            echo "<td>" . htmlspecialchars($jenis) . "</td>";
                            echo "<td>" . htmlspecialchars($judul) . "</td>";
                            echo "<td>" . htmlspecialchars($nominal) . "</td>";
                            echo "<td><span class='tag {$status_class}'>" . htmlspecialchars($status_text) . "</span></td>";
                            echo "<td>";
                            
                            //if ($ak['JENIS'] === 'Campaign') {
                              //  echo "<a href='#' class='btn secondary small' style='text-decoration:none;'>Edit</a> ";
                                //echo "<a href='#' class='btn danger small' style='text-decoration:none;'>Hapus</a>";
                            //} else {
                              ////  echo "<a href='#' class='btn secondary small' style='text-decoration:none;'>Edit</a> ";
                                //echo "<a href='#' class='btn danger small' style='text-decoration:none;'>Hapus</a> ";
                                //echo "<a href='#' class='btn small' style='text-decoration:none;'>Verifikasi</a>";
                            //}
                            
                           // echo "</td>";
                            //echo "</tr>";
                        }
                    }
                    ?>

                </table>
            </div>
        </div>

    </main>
</div>

<!-- ============================
     FOOTER (SAMA KAYA BERANDA)
============================ -->
<footer>
    © 2025 Masjid Al-Falah. Semua Hak Dilindungi.
</footer>
<script src="../assets/js/dashboard_penerima.js"></script>

</body>
</html>

<?php
oci_close($conn);
?>
