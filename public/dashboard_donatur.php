<?php
/**
 * Dashboard Donatur (Donor Dashboard)
 * Menampilkan riwayat donasi, laporan penggunaan dana, dan statistik donor
 * Database: Oracle (oci8)
 */
session_start();
require_once '../config/db.php';

// Cek apakah user sudah login
$email_donatur = $_SESSION['email'] ?? null;

if (!$email_donatur) {
	header('Location: ../auth/login_donatur.php');
	exit;
}

// Query untuk mendapatkan id_donatur dari email
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

if (!$id_donatur) {
	header('Location: ../auth/login_donatur.php');
	exit;
}

function formatRupiah($num) {
	return 'Rp ' . number_format(floatval($num), 0, ',', '.');
}

// ========================================
// QUERY DATA STATISTIK DONATUR
// ========================================

// 1. Total donasi count
$total_donasi = 0;
$total_nominal = 0;
$transaksi_sukses = 0;
$program_aktif = 0;

$stmt_stat = oci_parse($conn, "
	SELECT 
		COUNT(DISTINCT d.id_donasi) as total_donasi,
		SUM(d.jumlah_donasi) as total_nominal,
		COUNT(DISTINCT c.id_campaign) as program_count,
		AVG(d.jumlah_donasi) as rata_rata_donasi
	FROM donasi d
	LEFT JOIN campaign c ON d.id_campaign = c.id_campaign
	WHERE d.id_donatur = :id_donatur
");

if ($stmt_stat) {
	oci_bind_by_name($stmt_stat, ':id_donatur', $id_donatur);
	if (oci_execute($stmt_stat)) {
		if ($row = oci_fetch_assoc($stmt_stat)) {
			$total_donasi = intval($row['TOTAL_DONASI'] ?? 0);
			$total_nominal = floatval($row['TOTAL_NOMINAL'] ?? 0);
			$program_aktif = intval($row['PROGRAM_COUNT'] ?? 0);
		}
	}
}
oci_free_statement($stmt_stat);

// Hitung rata-rata donasi
$rata_rata_donasi = $total_donasi > 0 ? $total_nominal / $total_donasi : 0;

// ========================================
// QUERY RIWAYAT DONASI (untuk table)
// ========================================
$riwayat_donasi = [];
$stmt_riwayat = oci_parse($conn, "
	SELECT 
		d.id_donasi,
		d.tanggal_donasi,
		d.jumlah_donasi,
		d.is_anonim,
		c.id_campaign,
		c.judul_campaign,
		c.status as campaign_status
	FROM donasi d
	JOIN campaign c ON d.id_campaign = c.id_campaign
	WHERE d.id_donatur = :id_donatur
	ORDER BY d.tanggal_donasi DESC
");

if ($stmt_riwayat) {
	oci_bind_by_name($stmt_riwayat, ':id_donatur', $id_donatur);
	if (oci_execute($stmt_riwayat)) {
		while ($row = oci_fetch_assoc($stmt_riwayat)) {
			$riwayat_donasi[] = $row;
		}
	}
}
oci_free_statement($stmt_riwayat);

// ========================================
// QUERY LAPORAN PENGGUNAAN DANA (TRANSPARANSI)
// ========================================
$laporan_list = [];
$stmt_laporan = oci_parse($conn, "
	SELECT 
		lr.id_laporan,
		lr.judul_laporan,
		lr.isi_laporan,
		lr.tanggal_generate,
		lr.total_dana_terkumpul,
		c.judul_campaign
	FROM laporan lr
	JOIN campaign c ON lr.id_campaign = c.id_campaign
	WHERE EXISTS (
		SELECT 1 FROM donasi d2
		WHERE d2.id_donatur = :id_donatur
		AND d2.id_campaign = lr.id_campaign
	)
	ORDER BY lr.tanggal_generate DESC
");

if ($stmt_laporan) {
	oci_bind_by_name($stmt_laporan, ':id_donatur', $id_donatur);
	if (oci_execute($stmt_laporan)) {
		while ($row = oci_fetch_assoc($stmt_laporan)) {
			$laporan_list[] = $row;
		}
	}
}
oci_free_statement($stmt_laporan);

?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0">
	<title>Dashboard Donatur - Donasi Masjid & Amal</title>
	<link rel="stylesheet" href="../assets/css/dashboard_donatur.css">-->
	<style>
		.status-badge {
			display: inline-block;
			padding: 4px 12px;
			border-radius: 12px;
			font-size: 0.85rem;
			font-weight: 600;
		}
		.status-aktif {
			background: #d4edda;
			color: #155724;
		}
		.status-terkumpul {
			background: #d4edda;
			color: #155724;
		}
		.status-diproses {
			background: #fff3cd;
			color: #856404;
		}
		.status-default {
			background: #e2e3e5;
			color: #383d41;
		}
		.btn-laporan {
			padding: 6px 12px;
			background: #007bff;
			color: white;
			text-decoration: none;
			border-radius: 4px;
			border: none;
			cursor: pointer;
			font-size: 0.9rem;
		}
		.btn-laporan:hover {
			background: #0056b3;
		}
		.report-card {
			border: 1px solid #ddd;
			padding: 15px;
			margin-bottom: 15px;
			border-radius: 8px;
			background: #fafafa;
		}
		.report-card h4 {
			margin: 0 0 5px 0;
			color: #333;
		}
		.report-card p {
			margin: 10px 0 0 0;
			color: #666;
			font-size: 0.95rem;
		}
		.muted {
			color: #999;
			font-size: 0.9rem;
		}
		.proof-img {
			max-width: 100%;
			margin-top: 10px;
			border-radius: 4px;
		}
		.modal-back {
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(0,0,0,0.5);
			z-index: 999;
			justify-content: center;
			align-items: center;
			padding: 20px;
		}
		.modal-back.show {
			display: flex;
		}
		.modal {
			background: white;
			border-radius: 8px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.2);
			max-width: 600px;
			width: 100%;
			max-height: 90vh;
			overflow: auto;
		}
		.modal-head {
			padding: 20px;
			border-bottom: 1px solid #eee;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.modal-head h3 {
			margin: 0;
		}
		.close {
			background: none;
			border: none;
			font-size: 28px;
			cursor: pointer;
			color: #999;
		}
		.modal-body {
			padding: 20px;
		}
		.modal-foot {
			padding: 20px;
			border-top: 1px solid #eee;
			text-align: right;
		}
		.report-details {
			background: #f8f9fa;
			padding: 15px;
			border-radius: 4px;
			margin-top: 15px;
		}
		.report-details div {
			margin-bottom: 10px;
		}
		.report-details div:last-child {
			margin-bottom: 0;
		}
	</style>
</head>
<body>
	<!-- HEADER -->
	<header class="main-header">
		<div class="header-content">
			<h1><a href="../index.php">Donasi Masjid & Amal</a></h1>
			<nav class="main-nav">
				<a href="campaign.php" class="nav-link">Program</a>
				<a href="campaign.php" class="nav-link">Donasi</a>
				<a href="#" class="nav-link">Dashboard</a>
				<a href="../controller/logout_donaturController.php" class="nav-link logout">Logout</a>
			</nav>
		</div>
	</header>

	<!-- MAIN CONTENT -->
	<main class="main-content">
		<div class="page-header">
			<h1>Dashboard Donatur</h1>
			<p>Selamat datang kembali, Donatur! Laporan penggunaan dana akan tampil di bawah jika admin sudah menggunggahnya.</p>
		</div>

		<!-- STATISTIK KARTU -->
		<div class="stats-container">
			<div class="stat-card">
				<div class="stat-label">Total Donasi</div>
				<div class="stat-value" id="totalDonasiCount"><?php echo htmlspecialchars($total_donasi); ?> Donasi</div>
			</div>
			<div class="stat-card">
				<div class="stat-label">Total Nominal</div>
				<div class="stat-value" id="totalDonasiNominal"><?php echo formatRupiah($total_nominal); ?></div>
			</div>
			<div class="stat-card">
				<div class="stat-label">Transaksi Berhasil</div>
				<div class="stat-value" id="transaksiSukses"><?php echo htmlspecialchars($total_donasi); ?></div>
			</div>
			<div class="stat-card">
				<div class="stat-label">Program Aktif</div>
				<div class="stat-value" id="programAktif"><?php echo htmlspecialchars($program_aktif); ?> Program</div>
			</div>
		</div>

		<!-- RIWAYAT DONASI TABLE -->
		<div class="donasi-box">
			<h3 class="table-title">Riwayat Donasi</h3>
			<table id="donationTable">
				<thead>
					<tr>
						<th>Tanggal</th>
						<th>Program</th>
						<th>Jumlah</th>
						<th>Status</th>
						<th>Aksi</th>
					</tr>
				</thead>
				<tbody>
					<?php
					if (empty($riwayat_donasi)) {
						echo "<tr><td colspan='5' style='text-align:center;color:#999;'>Belum ada riwayat donasi</td></tr>";
					} else {
						foreach ($riwayat_donasi as $donasi) {
							$tanggal = !empty($donasi['TANGGAL_DONASI']) && strtotime($donasi['TANGGAL_DONASI']) !== false 
								? date('Y-m-d', strtotime($donasi['TANGGAL_DONASI'])) 
								: '-';
							$status = $donasi['CAMPAIGN_STATUS'] ?? 'Tidak Ada';
							$status_class = match($status) {
								'Aktif' => 'status-aktif',
								'Selesai' => 'status-terkumpul',
								'Ditangguhkan' => 'status-diproses',
								default => 'status-default'
							};
							echo "<tr>";
							echo "<td>" . htmlspecialchars($tanggal) . "</td>";
							echo "<td>" . htmlspecialchars($donasi['JUDUL_CAMPAIGN'] ?? '') . "</td>";
							echo "<td>" . formatRupiah($donasi['JUMLAH_DONASI'] ?? 0) . "</td>";
							echo "<td><span class='status-badge {$status_class}'>" . htmlspecialchars($status) . "</span></td>";
							echo "<td><a href='detail1.php?id_campaign=" . urlencode($donasi['ID_CAMPAIGN'] ?? '') . "' class='btn-laporan'>Lihat</a></td>";
							echo "</tr>";
						}
					}
					?>
				</tbody>
			</table>
		</div>

		<!-- LAPORAN PENGGUNAAN DANA (TRANSPARANSI) -->
		<div class="donasi-box" style="margin-top:18px;">
			<h3 class="table-title">Laporan Penggunaan Dana (Transparansi)</h3>
			<div id="reportsList">
				<?php
				if (empty($laporan_list)) {
					echo "<p style='color:#999;text-align:center;'>Belum ada laporan penggunaan dana</p>";
				} else {
					foreach ($laporan_list as $laporan) {
						$tanggal = !empty($laporan['TANGGAL_GENERATE']) && strtotime($laporan['TANGGAL_GENERATE']) !== false 
							? date('Y-m-d', strtotime($laporan['TANGGAL_GENERATE'])) 
							: '-';
						$deskripsi = htmlspecialchars($laporan['ISI_LAPORAN'] ?? '');
						if (strlen($deskripsi) > 150) {
							$deskripsi = substr($deskripsi, 0, 150) . '…';
						}
						echo "<div class='report-card'>";
						echo "<div style='display:flex;justify-content:space-between;align-items:start;'>";
						echo "<div>";
						echo "<h4>" . htmlspecialchars($laporan['JUDUL_LAPORAN'] ?? '') . "</h4>";
						echo "<p class='muted'>" . htmlspecialchars($tanggal) . " • " . htmlspecialchars($laporan['JUDUL_CAMPAIGN'] ?? '') . "</p>";
						echo "</div>";
						echo "<div style='text-align:right;'>";
						echo "<div style='font-size:1.1rem;font-weight:600;color:#2c5f2d;'>" . formatRupiah($laporan['TOTAL_DANA_TERKUMPUL'] ?? 0) . "</div>";
						echo "<button class='btn-laporan' onclick='openReport(" . intval($laporan['ID_LAPORAN']) . ")' style='margin-top:8px;'>Lihat Detail</button>";
						echo "</div>";
						echo "</div>";
						echo "<p style='margin:10px 0 0 0;color:#666;'>" . $deskripsi . "</p>";
						echo "</div>";
					}
				}
				?>
			</div>
		</div>
	</main>

	<!-- FOOTER -->
	<footer class="main-footer">
		<p>© 2025 Masjid Al-Falah | Donasi Masjid & Amal</p>
	</footer>

	<!-- MODAL LAPORAN -->
	<div id="reportModal" class="modal-back">
		<div class="modal">
			<header class="modal-head">
				<h3 id="modalTitle">Detail Laporan</h3>
				<button class="close" id="closeModal">&times;</button>
			</header>
			<div class="modal-body">
				<p class="muted" id="modalDate">Tanggal: —</p>
				<p id="modalDesc" style="margin-top:10px;">Deskripsi laporan...</p>
				<div class="report-details">
					<div><strong>Total Dana Terkumpul:</strong> <span id="modalAmount">Rp 0</span></div>
					<div><strong>Campaign:</strong> <span id="modalCampaign">-</span></div>
				</div>
			</div>
			<footer class="modal-foot">
				<button class="btn-laporan" id="modalCloseBtn">Tutup</button>
			</footer>
		</div>
	</div>

	<script>
		const modal = document.getElementById('reportModal');
		const modalTitle = document.getElementById('modalTitle');
		const modalDate = document.getElementById('modalDate');
		const modalDesc = document.getElementById('modalDesc');
		const modalAmount = document.getElementById('modalAmount');
		const modalCampaign = document.getElementById('modalCampaign');

		function formatRupiah(n) {
			if (!n) return 'Rp 0';
			return 'Rp ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
		}

		function openReport(id) {
			// Ambil data laporan dari PHP (embedded di HTML atau via fetch)
			const laporanData = [
				<?php
				$laporan_entries = [];
				foreach ($laporan_list as $laporan) {
					$entry = "{";
					$entry .= "id: " . intval($laporan['ID_LAPORAN']) . ",";
					$entry .= "title: '" . addslashes(htmlspecialchars($laporan['JUDUL_LAPORAN'] ?? '')) . "',";
					$entry .= "date: '" . (date('Y-m-d', strtotime($laporan['TANGGAL_GENERATE'] ?? ''))) . "',";
					$entry .= "description: '" . addslashes(htmlspecialchars(substr($laporan['ISI_LAPORAN'] ?? '', 0, 500))) . "',";
					$entry .= "amount: " . floatval($laporan['TOTAL_DANA_TERKUMPUL'] ?? 0) . ",";
					$entry .= "campaign: '" . addslashes(htmlspecialchars($laporan['JUDUL_CAMPAIGN'] ?? '')) . "'";
					$entry .= "}";
					$laporan_entries[] = $entry;
				}
				echo implode(",", $laporan_entries);
				?>
			];

			const r = laporanData.find(x => x.id === id);
			if (!r) return alert('Laporan tidak ditemukan');

			modalTitle.innerText = r.title;
			modalDate.innerText = 'Tanggal: ' + r.date;
			modalDesc.innerText = r.description;
			modalAmount.innerText = formatRupiah(r.amount);
			modalCampaign.innerText = r.campaign;

			modal.classList.add('show');
		}

		function closeReport() {
			modal.classList.remove('show');
		}

		document.getElementById('closeModal').addEventListener('click', closeReport);
		document.getElementById('modalCloseBtn').addEventListener('click', closeReport);
		modal.addEventListener('click', (e) => {
			if (e.target === modal) closeReport();
		});
	</script>
</body>
</html>

<?php
oci_close($conn);
?>
