<?php
/**
 * top_campaigns.php
 * Menampilkan Top 5 Campaign berdasarkan total jumlah donasi.
 * Database: Oracle (oci8)
 */
session_start();
require_once '../config/db.php'; 

// Cek apakah admin/penerima sudah login (sesuaikan key SESSION Anda)
if (!isset($_SESSION['email'])) { 
    header('Location: ../auth/login_donatur.php');
    exit;
}

$top_campaigns = [];
$message = '';
$message_class = '';

// Query untuk mengambil Top 5 Campaign berdasarkan total donasi.
// Menggunakan ROWNUM dengan subquery untuk kompatibilitas dengan versi Oracle (11g ke bawah).
// Menggunakan JUDUL_CAMPAIGN sesuai skema tabel CAMPAIGN.
$sql = "
    SELECT * FROM (
        SELECT 
            c.JUDUL_CAMPAIGN, 
            SUM(d.JUMLAH_DONASI) AS TOTAL_DONASI
        FROM 
            DONASI d
        JOIN 
            CAMPAIGN c ON d.ID_CAMPAIGN = c.ID_CAMPAIGN
        GROUP BY 
            c.JUDUL_CAMPAIGN
        ORDER BY 
            TOTAL_DONASI DESC
    )
    WHERE ROWNUM <= 5
";

$stmt = oci_parse($conn, $sql);

if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    $message = 'Gagal memuat data campaign: ' . ($e['message'] ?? 'Error Database');
    $message_class = 'error';
    // PENTING: Untuk error ORA-01438 pada create_campaign, cek presisi kolom TARGET_DANA (saat ini NUMBER(12,2)).
} else {
    while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
        // Menggunakan key JUDUL_CAMPAIGN (uppercase) sesuai nama kolom di SQL
        $top_campaigns[] = [
            'JUDUL_CAMPAIGN' => htmlspecialchars($row['JUDUL_CAMPAIGN']),
            'TOTAL_DONASI' => $row['TOTAL_DONASI']
        ];
    }
    oci_free_statement($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top 5 Campaign</title>
    <link rel="stylesheet" href="../assets/css/crud-donatur.css"> 
    
    <style>
        /* CSS tambahan agar angka terlihat rapi */
/* ================================
   ✳ Styling Halaman Top Campaign
   ================================ */

/* Layout utama */
.content {
  max-width: 1200px;
  margin: 40px auto;
  background: #ffffff;
  padding: 28px 32px;
  border-radius: 14px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.06);
  border: 1px solid #dfede4;
}

/* Judul Halaman */
.page-head h1 {
  font-size: 2rem;
  color: #214a30;
  margin-bottom: 10px;
  font-weight: 800;
}

.page-head p {
  color: #3d573d;
  font-size: 1rem;
  margin-bottom: 20px;
}

/* Panel tabel */
.panel {
  margin-top: 15px;
}

/* Table Styling */
.table-wrap {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  border-radius: 10px;
  overflow: hidden;
}

.data-table th {
  background: #90c943;
  color: #1f3d2f;
  padding: 14px 16px;
  font-size: 0.95rem;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.data-table td {
  padding: 14px 16px;
  background: #ffffff;
  border-bottom: 1px solid #dfede4;
  color: #214a30;
  font-weight: 500;
}

/* Hover row */
.data-table tbody tr:hover {
  background: #eef6e8;
}

/* Kolom rank & jumlah */
.data-table .col-rank {
  text-align: center;
  width: 60px;
  font-weight: bold;
  color: #214a30;
}

.data-table .col-jumlah {
  text-align: right;
  font-weight: bold;
  color: #214a30;
}

/* Pesan success/error/warning */
.message {
  padding: 14px;
  border-radius: 8px;
  margin-bottom: 18px;
  font-weight: 600;
}

.message.error {
  background: #ffeded;
  border-left: 5px solid #c70000;
  color: #8b0000;
}

.message.success {
  background: #e8ffe8;
  border-left: 5px solid #009e2b;
  color: #076e1c;
}

.message.warning {
  background: #fffbe1;
  border-left: 5px solid #e5c000;
  color: #665300;
}

/* Form perbaikan input agar rapi */
.form-group label {
  font-weight: 600;
  color: #214a30;
  margin-bottom: 6px;
  display: block;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"] {
  width: 100%;
  padding: 12px;
  border: 1px solid #c8d8ca;
  border-radius: 8px;
  background: #ffffff;
  font-size: 0.95rem;
  box-sizing: border-box;
}

/* Responsive */
@media (max-width: 768px) {
  .content {
    margin: 20px;
    padding: 18px;
  }

  .data-table th,
  .data-table td {
    font-size: 0.85rem;
  }
}

/* ================================
   🟢 Tombol Kembali
   ================================ */
.btn-back {
  
  display: inline-block;
  background: #c0ff28;
  color: #214a30;
  font-weight: 700;
  font-size: 0.95rem;
  padding: 12px 26px;
  border-radius: 10px;
  text-decoration: none;
  transition: all 0.3s ease;
  box-shadow: 0 3px 10px rgba(192, 255, 40, 0.4);
  margin-top: 22px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.btn-back:hover {
  background: #a9e51f;
  color: #f6ffea;
  transform: translateY(-2px);
}


    </style>
</head>
<body>
    <div class="wrap">
        
        <?php 
        // include 'header.php'; // Jika Anda menggunakan file header terpisah
        
        ?>

        <div class="content">
            <div class="page-head">
                <h1>Top 5 Campaign Donasi Terbanyak</h1>
                <p>Daftar 5 Campaign dengan akumulasi total donasi terbesar.</p>
            </div>
            

            <?php if ($message): ?>
                <div class="message <?php echo htmlspecialchars($message_class); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="panel">
                <?php if (!empty($top_campaigns)): ?>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="col-rank">Peringkat</th>
                                    <th>Nama Campaign</th>
                                    <th class="col-jumlah">Total Donasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ($top_campaigns as $campaign): 
                                    // Format angka ke format Rupiah (IDR)
                                    $formatted_amount = 'Rp ' . number_format($campaign['TOTAL_DONASI'], 0, ',', '.');
                                ?>
                                <tr>
                                    <td class="col-rank"><?php echo $rank++; ?></td>
                                    <td><?php echo $campaign['JUDUL_CAMPAIGN']; ?></td> 
                                    <td class="col-jumlah"><?php echo $formatted_amount; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="message warning">
                        Belum ada data donasi yang tercatat atau terjadi kesalahan pemuatan data.
                    </div>
                <?php endif; ?>
            </div>
            <a href="dashboard_donatur.php" class="btn-back" >← Kembali</a>
        </div>
    </div>
    
</body>
</html>

<?php oci_close($conn); ?>