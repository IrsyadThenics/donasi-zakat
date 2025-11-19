<?php
session_start();
require_once '../config/db.php';

echo "<h2>DEBUG Session & User Data</h2>";
echo "<pre>";
echo "Session data:\n";
var_dump($_SESSION);
echo "\n";

// Cek email dari session
$email_donatur = $_SESSION['email'] ?? null;
echo "Email dari session: " . htmlspecialchars($email_donatur) . "\n\n";

if ($email_donatur) {
	// Query untuk mendapatkan id_donatur dari email
	$stmt_cek = oci_parse($conn, "SELECT id_donatur FROM donatur WHERE email = :email");
	oci_bind_by_name($stmt_cek, ':email', $email_donatur);
	if (oci_execute($stmt_cek)) {
		if ($row = oci_fetch_assoc($stmt_cek)) {
			$id_donatur = intval($row['ID_DONATUR']);
			echo "ID Donatur dari email: " . $id_donatur . "\n\n";
			
			// Cek donasi milik user ini
			$stmt_donasi = oci_parse($conn, "
				SELECT COUNT(*) as cnt FROM donasi WHERE id_donatur = :idd
			");
			oci_bind_by_name($stmt_donasi, ':idd', $id_donatur);
			if (oci_execute($stmt_donasi)) {
				if ($row_cnt = oci_fetch_assoc($stmt_donasi)) {
					echo "Donasi milik user " . $id_donatur . ": " . $row_cnt['CNT'] . " donasi\n\n";
				}
			}
			oci_free_statement($stmt_donasi);
		}
	}
	oci_free_statement($stmt_cek);
}

// Tampilkan semua donasi untuk referensi
echo "Semua donasi di database:\n";
$stmt_all = oci_parse($conn, "SELECT id_donasi, id_donatur, jumlah_donasi, tanggal_donasi, is_anonim FROM donasi ORDER BY tanggal_donasi DESC");
if (oci_execute($stmt_all)) {
	while ($row_all = oci_fetch_assoc($stmt_all)) {
		echo "- ID: " . $row_all['ID_DONASI'] . ", ID_Donatur: " . ($row_all['ID_DONATUR'] ?? 'NULL') . ", Jumlah: " . $row_all['JUMLAH_DONASI'] . ", is_anonim: " . $row_all['IS_ANONIM'] . "\n";
	}
}
oci_free_statement($stmt_all);

echo "</pre>";
oci_close($conn);
?>
