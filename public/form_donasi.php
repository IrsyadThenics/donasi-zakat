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
	<link rel="stylesheet" href="../assets/css/index.css">
	<style>
		.form-card{max-width:720px;margin:20px auto;padding:20px;border:1px solid #e2e2e2;border-radius:8px;background:#fff}
		label{display:block;margin-bottom:6px;font-weight:600}
		input[type=text], input[type=number], textarea, select{width:100%;padding:10px;margin-bottom:12px;border:1px solid #ccc;border-radius:4px}
		.btn{display:inline-block;padding:10px 16px;background:#007bff;color:#fff;border-radius:4px;text-decoration:none;border:none;cursor:pointer}
		.message{padding:10px;margin-bottom:12px;border-radius:4px}
		.message.success{background:#e6ffed;border:1px solid #2ecc71;color:#1e7e34}
		.message.error{background:#fff1f0;border:1px solid #e74c3c;color:#c0392b}
		.campaign-summary{background:#fafafa;padding:10px;border:1px dashed #ddd;border-radius:4px;margin-bottom:12px}
	</style>
</head>
<body>
	<div class="form-card">
		<h1>Donasi</h1>
		<p>Berikan dukungan Anda untuk campaign yang tersedia.</p>

		<?php if ($message): ?>
			<div class="message <?php echo htmlspecialchars($message_class); ?>"><?php echo htmlspecialchars($message); ?></div>
		<?php endif; ?>

		<form method="post" action="">
			<label for="id_campaign">Pilih Campaign</label>
			<select name="id_campaign" id="id_campaign" required onchange="this.form.submit();">
				<option value="">-- Pilih Campaign --</option>
				<?php foreach ($campaigns as $c): ?>
					<option value="<?php echo htmlspecialchars($c['ID_CAMPAIGN']); ?>" <?php if (($selected_campaign && strval($selected_campaign['ID_CAMPAIGN'])===strval($c['ID_CAMPAIGN'])) || (!isset($_POST['id_campaign']) && isset($_GET['id_campaign']) && strval($_GET['id_campaign'])===strval($c['ID_CAMPAIGN'])) ) echo 'selected'; ?>>
						<?php echo htmlspecialchars($c['JUDUL_CAMPAIGN']); ?> — <?php echo formatRupiah($c['TARGET_DANA'] ?? 0); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<?php if ($selected_campaign): ?>
				<div class="campaign-summary">
					<strong><?php echo htmlspecialchars($selected_campaign['JUDUL_CAMPAIGN']); ?></strong>
					<div>Target: <?php echo formatRupiah($selected_campaign['TARGET_DANA'] ?? 0); ?></div>
					<div>Terkumpul: <?php echo formatRupiah($selected_campaign['DANA_TERKUMPUL'] ?? 0); ?></div>
				</div>
			<?php endif; ?>

			<label for="jumlah">Jumlah Donasi (angka, tanpa simbol)</label>
			<input type="text" name="jumlah" id="jumlah" placeholder="Mis. 50000" value="<?php echo isset($_POST['jumlah']) ? htmlspecialchars($_POST['jumlah']) : ''; ?>" required>

			<label for="metode">Metode Pembayaran</label>
			<select name="metode" id="metode">
				<option value="">-- Pilih Metode --</option>
				<option value="Transfer Bank" <?php if(isset($_POST['metode']) && $_POST['metode']==='Transfer Bank') echo 'selected'; ?>>Transfer Bank</option>
				<option value="E-Wallet" <?php if(isset($_POST['metode']) && $_POST['metode']==='E-Wallet') echo 'selected'; ?>>E-Wallet</option>
				<option value="Tunai" <?php if(isset($_POST['metode']) && $_POST['metode']==='Tunai') echo 'selected'; ?>>Tunai</option>
			</select>

			<label for="pesan">Pesan (opsional)</label>
			<textarea name="pesan" id="pesan" rows="4"><?php echo isset($_POST['pesan']) ? htmlspecialchars($_POST['pesan']) : ''; ?></textarea>

			<div style="display:flex;gap:12px;align-items:center">
				<button type="submit" class="btn">Donasi Sekarang</button>
				<a href="campaign.php" class="btn" style="background:#6c757d">Kembali</a>
			</div>
		</form>

		<p style="margin-top:12px;font-size:0.9rem;color:#666">Catatan: Donasi dapat dilakukan dengan atau tanpa login. Jika login, donasi akan tercatat atas nama Anda. Jika tidak login, donasi akan tercatat sebagai anonim.</p>
	</div>
</body>
</html>

<?php
oci_free_statement($stmt_c ?? null);
oci_close($conn);
?>
