<?php
/**
 * Form Donasi
 * - Menampilkan form donasi untuk campaign yang dipilih
 * - Memproses POST untuk menyimpan donasi dan mengupdate dana_terkumpul pada campaign
 * Database: Oracle (oci8)
 */
session_start();
require_once '../config/db.php';

function formatRupiah($num) {
	return 'Rp ' . number_format(floatval($num), 0, ',', '.');
}

$message = '';
$message_class = '';

// Dapatkan id_donatur dari session email (jika ada)
$email_donatur = $_SESSION['email'] ?? null;
$id_donatur = null;

if ($email_donatur) {
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
}

// Load campaigns for dropdown
$campaigns = [];
$stmt_c = oci_parse($conn, "SELECT id_campaign, judul_campaign, target_dana, dana_terkumpul, status FROM campaign WHERE status = 'Aktif' ORDER BY tanggal_mulai DESC");
if ($stmt_c && oci_execute($stmt_c)) {
	while ($row = oci_fetch_assoc($stmt_c)) {
		$campaigns[] = $row;
	}
}

$selected_campaign = null;
if (!empty($_GET['id_campaign'])) {
	$idc = $_GET['id_campaign'];
	foreach ($campaigns as $c) {
		if (strval($c['ID_CAMPAIGN']) === strval($idc)) {
			$selected_campaign = $c;
			break;
		}
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id_campaign = $_POST['id_campaign'] ?? '';
	$jumlah = isset($_POST['jumlah']) ? str_replace([',','.' ], ['',''], $_POST['jumlah']) : '';
	$jumlah = floatval($jumlah);
	$metode = trim($_POST['metode'] ?? '');
	$pesan = trim($_POST['pesan'] ?? '');
	$is_anonim = ($id_donatur === null) ? 1 : 0;
	
	if (empty($id_campaign)) {
		$message = 'Pilih campaign tujuan donasi.';
		$message_class = 'error';
	} elseif ($jumlah <= 0) {
		$message = 'Masukkan jumlah donasi yang valid (lebih besar dari 0).';
		$message_class = 'error';
	} else {
		$ok = true;
		
		// Insert donasi (id_donatur boleh NULL jika anonim, gunakan is_anonim flag untuk menandai)
		$sql_ins = "INSERT INTO donasi (id_donasi, id_campaign, id_donatur, jumlah_donasi, tanggal_donasi, is_anonim, metode, pesan) VALUES (seq_donasi.nextval, :idc, :idd, :jml, SYSDATE, :is_anon, :met, :pes)";
		$stmt_ins = oci_parse($conn, $sql_ins);
		if ($stmt_ins) {
			oci_bind_by_name($stmt_ins, ':idc', $id_campaign);
			oci_bind_by_name($stmt_ins, ':idd', $id_donatur);
			oci_bind_by_name($stmt_ins, ':jml', $jumlah);
			oci_bind_by_name($stmt_ins, ':is_anon', $is_anonim);
			oci_bind_by_name($stmt_ins, ':met', $metode);
			oci_bind_by_name($stmt_ins, ':pes', $pesan);
			if (!@oci_execute($stmt_ins, OCI_NO_AUTO_COMMIT)) {
				$ok = false;
				$err = oci_error($stmt_ins);
				$message = 'Gagal menyimpan donasi: ' . ($err['message'] ?? '');
				$message_class = 'error';
			}
		} else {
			$ok = false;
			$message = 'Gagal mempersiapkan query.';
			$message_class = 'error';
		}
		
		if ($ok) {
			$sql_upd = "UPDATE campaign SET dana_terkumpul = NVL(dana_terkumpul,0) + :jml WHERE id_campaign = :idc";
			$stmt_upd = oci_parse($conn, $sql_upd);
			if ($stmt_upd) {
				oci_bind_by_name($stmt_upd, ':jml', $jumlah);
				oci_bind_by_name($stmt_upd, ':idc', $id_campaign);
				if (!@oci_execute($stmt_upd, OCI_NO_AUTO_COMMIT)) {
					$ok = false;
					$err = oci_error($stmt_upd);
					$message = 'Gagal mengupdate campaign: ' . ($err['message'] ?? '');
					$message_class = 'error';
				}
			} else {
				$ok = false;
				$message = 'Gagal mempersiapkan update.';
				$message_class = 'error';
			}
		}
		
		if ($ok) {
			if (oci_commit($conn)) {
				$message = 'Terima kasih! Donasi berhasil disimpan.';
				$message_class = 'success';
				foreach ($campaigns as &$c) {
					if (strval($c['ID_CAMPAIGN']) === strval($id_campaign)) {
						$c['DANA_TERKUMPUL'] = ($c['DANA_TERKUMPUL'] ?? 0) + $jumlah;
						$selected_campaign = $c;
						break;
					}
				}
			} else {
				$message = 'Gagal commit transaksi.';
				$message_class = 'error';
			}
		} else {
			@oci_rollback($conn);
		}
	}
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Form Donasi - Donasi Masjid & Amal</title>
	<link rel="stylesheet" href="../assets/css/form_donasi.css">
</head>
<body>
	<!-- HEADER -->
	<header class="main-header">
		<div class="header-wrapper">
			<h1 class="logo"><a href="../index.php">🌿 Donasi Masjid & Amal</a></h1>
			<nav class="main-nav">
				<a href="campaign.php" class="nav-link">Program</a>
				<a href="form_donasi.php" class="nav-link active">Donasi</a>
				<?php if ($id_donatur): ?>
					<a href="dashboard_donatur.php" class="nav-link">Dashboard</a>
					<a href="../controller/logout_donaturController.php" class="nav-link logout">Logout</a>
				<?php else: ?>
					<a href="../auth/login_donatur.php" class="nav-link">Login</a>
				<?php endif; ?>
			</nav>
		</div>
	</header>

	<!-- MAIN CONTENT -->
	<main class="main-content">
		<div class="form-container">
			<!-- PAGE HEADER -->
			<div class="page-header">
				<h1>Formulir Donasi</h1>
				<p>Berikan dukungan Anda untuk campaign yang tersedia dan membantu masyarakat.</p>
			</div>

			<!-- MESSAGE ALERT -->
			<?php if ($message): ?>
				<div class="alert alert-<?php echo htmlspecialchars($message_class); ?>">
					<span class="alert-icon"><?php echo $message_class === 'success' ? '✓' : '✕'; ?></span>
					<span class="alert-text"><?php echo htmlspecialchars($message); ?></span>
				</div>
			<?php endif; ?>

			<!-- FORM CARD -->
			<div class="form-card">
				<form method="post" action="" class="donation-form">
					<!-- CAMPAIGN SELECTION -->
					<div class="form-group">
						<label for="id_campaign" class="form-label">Pilih Campaign <span class="required">*</span></label>
						<select name="id_campaign" id="id_campaign" class="form-control" required onchange="this.form.submit();">
							<option value="">-- Pilih Campaign --</option>
							<?php foreach ($campaigns as $c): ?>
								<option value="<?php echo htmlspecialchars($c['ID_CAMPAIGN']); ?>" <?php if (($selected_campaign && strval($selected_campaign['ID_CAMPAIGN'])===strval($c['ID_CAMPAIGN'])) || (!isset($_POST['id_campaign']) && isset($_GET['id_campaign']) && strval($_GET['id_campaign'])===strval($c['ID_CAMPAIGN'])) ) echo 'selected'; ?>>
									<?php echo htmlspecialchars($c['JUDUL_CAMPAIGN']); ?> — <?php echo formatRupiah($c['TARGET_DANA'] ?? 0); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<!-- CAMPAIGN SUMMARY -->
					<?php if ($selected_campaign): ?>
						<div class="campaign-summary">
							<h3><?php echo htmlspecialchars($selected_campaign['JUDUL_CAMPAIGN']); ?></h3>
							<div class="summary-row">
								<div class="summary-item">
									<span class="summary-label">Target Dana</span>
									<span class="summary-value"><?php echo formatRupiah($selected_campaign['TARGET_DANA'] ?? 0); ?></span>
								</div>
								<div class="summary-item">
									<span class="summary-label">Terkumpul</span>
									<span class="summary-value highlight"><?php echo formatRupiah($selected_campaign['DANA_TERKUMPUL'] ?? 0); ?></span>
								</div>
							</div>
							<div class="progress-bar">
								<div class="progress-fill" style="width: <?php 
									$target = floatval($selected_campaign['TARGET_DANA'] ?? 1);
									$terkumpul = floatval($selected_campaign['DANA_TERKUMPUL'] ?? 0);
									echo min(($terkumpul / $target) * 100, 100); 
								?>%"></div>
							</div>
							<p class="progress-text"><?php echo number_format(min(($terkumpul / $target) * 100, 100), 1); ?>% terpenuhi</p>
						</div>
					<?php endif; ?>

					<!-- DONATION AMOUNT -->
					<div class="form-group">
						<label for="jumlah" class="form-label">Jumlah Donasi <span class="required">*</span></label>
						<div class="input-wrapper">
							<span class="input-prefix">Rp</span>
							<input type="text" name="jumlah" id="jumlah" class="form-control" placeholder="50000" value="<?php echo isset($_POST['jumlah']) ? htmlspecialchars($_POST['jumlah']) : ''; ?>" required>
						</div>
						<small class="form-hint">Masukkan angka tanpa simbol (contoh: 50000)</small>
					</div>

					<!-- PAYMENT METHOD -->
					<div class="form-group">
						<label for="metode" class="form-label">Metode Pembayaran</label>
						<select name="metode" id="metode" class="form-control">
							<option value="">-- Pilih Metode --</option>
							<option value="Transfer Bank" <?php if(isset($_POST['metode']) && $_POST['metode']==='Transfer Bank') echo 'selected'; ?>>🏦 Transfer Bank</option>
							<option value="E-Wallet" <?php if(isset($_POST['metode']) && $_POST['metode']==='E-Wallet') echo 'selected'; ?>>📱 E-Wallet</option>
							<option value="Tunai" <?php if(isset($_POST['metode']) && $_POST['metode']==='Tunai') echo 'selected'; ?>>💵 Tunai</option>
						</select>
					</div>

					<!-- MESSAGE -->
					<div class="form-group">
						<label for="pesan" class="form-label">Pesan (Opsional)</label>
						<textarea name="pesan" id="pesan" class="form-control" rows="4" placeholder="Sampaikan pesan atau doa Anda..."><?php echo isset($_POST['pesan']) ? htmlspecialchars($_POST['pesan']) : ''; ?></textarea>
						<small class="form-hint">Pesan Anda akan ditampilkan di halaman campaign</small>
					</div>

					<!-- ACTION BUTTONS -->
					<div class="form-actions">
						<!--<button type="submit" class="btn btn-primary">💚 Donasi Sekarang</button>-->
						<a href="dashboard_donatur.php" class="btn btn-secondary">← Kembali</a>
					</div>
				</form>
			</div>

			<!-- INFO BOX -->
			<!--<div class="info-box">
				<p><strong>ℹ️ Catatan Penting:</strong></p>
				<ul>
					<li>Donasi dapat dilakukan dengan atau tanpa login</li>
					<li>Jika login, donasi akan tercatat atas nama Anda</li>
					<li>Jika tidak login, donasi akan tercatat sebagai anonim</li>
					<li>Setiap donasi sangat berarti bagi kami</li>
				</ul>
			</div>-->
		</div>
	</main>

	<!-- FOOTER -->
	<footer class="main-footer">
		<p>© 2025 Masjid Al-Falah | Donasi Masjid & Amal</p>
	</footer>
</body>
</html>

<?php
oci_free_statement($stmt_c ?? null);
oci_close($conn);
?>