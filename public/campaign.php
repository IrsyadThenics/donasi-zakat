<?php
/**
 * campaign.php (Laporan Teks)
 * Halaman menampilkan semua campaign aktif dalam format tabel laporan.
 */
session_start();

// Pastikan path ke db.php sudah benar.
require_once '../config/db.php'; 

// Fungsi helper untuk format mata uang
function formatRupiah($num) {
    // floatval() memastikan nilai dari database diubah menjadi angka yang dapat diformat
    return 'Rp ' . number_format(floatval($num), 0, ',', '.');
}

// ======================================
// LOAD DATA CAMPAIGN
// ======================================
$campaigns = [];
$stmt = null; // Inisialisasi statement resource

$sql = "
    SELECT 
        id_campaign,
        judul_campaign,
        deskripsi, -- Tambahkan deskripsi untuk detail laporan
        target_dana,
        NVL(dana_terkumpul, 0) AS dana_terkumpul, 
        tanggal_mulai,
        tanggal_deadline,
        status
    FROM campaign
    WHERE status = 'Aktif'
    ORDER BY tanggal_mulai DESC
";
$stmt = oci_parse($conn, $sql);

if ($stmt) {
    if (oci_execute($stmt)) {
        while ($row = oci_fetch_assoc($stmt)) {
            $campaigns[] = $row;
        }
    }
    // Wajib: Bebaskan statement resource setelah digunakan
    oci_free_statement($stmt); 
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Laporan Campaign Aktif</title>

  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
    .report-container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .progress-bar { background-color: #f3f3f3; border-radius: 4px; overflow: hidden; height: 20px; }
    .progress-bar-fill { background-color: #2196F3; height: 100%; text-align: center; color: white; font-size: 12px; line-height: 20px; }
    .text-center { text-align: center; }
  </style>
</head>

<body>

<div class="report-container">

    <header>
        <h1>📋 Laporan Campaign Aktif</h1>
        <p>Ringkasan program donasi yang sedang berjalan.</p>
    </header>

    <?php if (empty($campaigns)): ?>
        <p style="padding:20px; background: #fff3cd; border: 1px solid #ffeeba; color: #856404; border-radius: 4px;">
            Belum ada campaign aktif saat ini.
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Judul Campaign</th>
                    <th>Target Dana</th>
                    <th>Terkumpul</th>
                    <th>Progres</th>
                    <th>Deadline</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $c): ?>
                <?php
                    // Ekstraksi data
                    $target_dana = $c['TARGET_DANA'];
                    $dana_terkumpul = $c['DANA_TERKUMPUL']; 
                    $id_campaign = $c['ID_CAMPAIGN'];
                    
                    // Hitung progress
                    $progress = $target_dana > 0
                        ? round(($dana_terkumpul / $target_dana) * 100)
                        : 0;

                    $progress = min($progress, 100);
                    $id = urlencode($id_campaign);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($id_campaign); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($c['JUDUL_CAMPAIGN']); ?></strong>
                        <br><small><?php echo substr(htmlspecialchars($c['DESKRIPSI']), 0, 80) . '...'; ?></small>
                    </td>
                    <td><?php echo formatRupiah($target_dana); ?></td>
                    <td><?php echo formatRupiah($dana_terkumpul); ?></td>
                    <td style="min-width: 150px;">
                        <div class="progress-bar">
                            <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%">
                                <?php echo $progress; ?>%
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($c['TANGGAL_DEADLINE']); ?></td>
                    <td class="text-center">
                        <a href="detail1.php?id=<?php echo $id; ?>" style="color:#2196F3; text-decoration:none;">Lihat Detail</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>