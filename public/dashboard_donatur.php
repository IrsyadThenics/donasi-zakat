<?php
/**
 * Dashboard Donatur - Menampilkan Top 5 Campaign
 * Database: Oracle
 * Features: View untuk menampilkan top 5 campaign terbanyak donasi
 */

session_start();
require_once '../config/db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
}

// Cek apakah donatur sudah login
if (!isset($_SESSION['email'])) {
    // Jika belum login, redirect ke halaman login
    header('Location: ../auth/login_donatur.php');
    exit;
}



// Fungsi untuk format Rupiah
function formatRupiah($num) {
    return 'Rp ' . number_format($num, 0, ',', '.');
}

// Query untuk mendapatkan Top 5 Campaign
$query_top_5 = "
    SELECT 
        c.id_campaign,
        c.judul_campaign,
        c.target_dana,
        c.dana_terkumpul,
        ROUND((c.dana_terkumpul / c.target_dana) * 100, 2) as progress_percentage,
        c.tanggal_mulai,
        c.tanggal_deadline,
        c.status,
        COUNT(d.id_donasi) as donation_count
    FROM 
        campaign c
        LEFT JOIN donasi d ON c.id_campaign = d.id_campaign
    WHERE
        c.status = 'Aktif'
    GROUP BY
        c.id_campaign, c.judul_campaign, c.target_dana, c.dana_terkumpul,
        c.tanggal_mulai, c.tanggal_deadline, c.status
    ORDER BY 
        c.dana_terkumpul DESC
";

$stmt_top_5 = oci_parse($conn, $query_top_5);
if (!oci_execute($stmt_top_5)) {
    $error = oci_error($stmt_top_5);
    die("Error query top 5 campaign: " . $error['message']);
}

// Query untuk statistik total donasi donatur
$query_donatur_stats = "
    SELECT 
        COUNT(d.id_donasi) as total_donations,
        SUM(d.jumlah_donasi) as total_amount,
        AVG(d.jumlah_donasi) as avg_amount
    FROM 
        donasi d
    WHERE 
        d.id_donatur = :donatur_id
";

$stmt_stats = oci_parse($conn, $query_donatur_stats);
if (!$stmt_stats) {
    $error = oci_error($conn);
    die("Error parse query stats: " . $error['message']);
}

oci_bind_by_name($stmt_stats, ':donatur_id', $_SESSION['id_donatur']);
if (!oci_execute($stmt_stats)) {
    $error = oci_error($stmt_stats);
    die("Error execute query stats: " . $error['message']);
}

$stats = oci_fetch_assoc($stmt_stats);
// Set default values jika tidak ada data
if (!$stats) {
    $stats = array(
        'TOTAL_DONATIONS' => 0,
        'TOTAL_AMOUNT' => 0,
        'AVG_AMOUNT' => 0
    );
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Donatur - Donasi Masjid & Amal</title>
    <link rel="stylesheet" href="../assets/css/dashboard_donatur.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <div class="header-title">
                <h1>🎯 Dashboard Donatur</h1>
                <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['nama_donatur'] ?? 'Donatur'); ?>!</p>
            </div>
            <div class="header-actions">
                <a href="form_donasi.php" class="btn btn-primary">+ Donasi Sekarang</a>
                <a href="../controller/logout_donaturController.php" class="btn btn-danger">Logout</a>
            </div>
        </header>

        <!-- Statistics Section -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Donasi</h3>
                <div class="value"><?php echo formatRupiah($stats['TOTAL_AMOUNT'] ?? 0); ?></div>
            </div>
            <div class="stat-card secondary">
                <h3>Jumlah Transaksi</h3>
                <div class="value"><?php echo $stats['TOTAL_DONATIONS'] ?? 0; ?>x</div>
            </div>
            <div class="stat-card success">
                <h3>Rata-rata Donasi</h3>
                <div class="value"><?php echo formatRupiah($stats['AVG_AMOUNT'] ?? 0); ?></div>
            </div>
        </div>

        <!-- Top 5 Campaign Section -->
        <div class="section-title">
            <h2>🏆 Top 5 Campaign Terbanyak Donasi</h2>
        </div>

        <div class="campaign-container">
            <?php
            $rank = 1;
            $has_campaigns = false;

            // Fetch rows safely: ensure assignment from oci_fetch_assoc happens before checking $rank
            while ($rank <= 5 && ($campaign = oci_fetch_assoc($stmt_top_5))) {
                $has_campaigns = true;
                // Ensure progress is numeric and bounded to 0-100
                $progress = min(max(floatval($campaign['PROGRESS_PERCENTAGE'] ?? 0), 0), 100);
                ?>
                <div class="campaign-card">
                    <div class="campaign-header">
                        <div class="campaign-rank">#<?php echo $rank; ?> - Top Campaign</div>
                        <div class="campaign-name"><?php echo htmlspecialchars($campaign['JUDUL_CAMPAIGN']); ?></div>
                        <div class="campaign-status">Status: <?php echo htmlspecialchars($campaign['STATUS']); ?></div>
                    </div>

                    <div class="campaign-body">
                        <div class="campaign-info">
                            <label>Target Dana:</label>
                            <div><?php echo formatRupiah($campaign['TARGET_DANA'] ?? 0); ?></div>
                        </div>

                        <div class="campaign-info">
                            <label>Dana Terkumpul:</label>
                            <div><?php echo formatRupiah($campaign['DANA_TERKUMPUL'] ?? 0); ?></div>
                        </div>

                        <div class="progress-container">
                            <div class="progress-label">
                                <span>Progress</span>
                                <span><?php echo $progress; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>

                        <div class="campaign-info">
                            <label>Periode:</label>
                            <div>
                                <?php
                                // Safely format dates; if missing or invalid, show '-'
                                $start = (!empty($campaign['TANGGAL_MULAI']) && strtotime($campaign['TANGGAL_MULAI']) !== false)
                                    ? date('d/m/Y', strtotime($campaign['TANGGAL_MULAI']))
                                    : '-';
                                $end = (!empty($campaign['TANGGAL_DEADLINE']) && strtotime($campaign['TANGGAL_DEADLINE']) !== false)
                                    ? date('d/m/Y', strtotime($campaign['TANGGAL_DEADLINE']))
                                    : '-';
                                echo "$start - $end";
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="campaign-footer">
                        <span class="donation-count">👥 <?php echo intval($campaign['DONATION_COUNT'] ?? 0); ?> Donatur</span>
                        <a href="form_donasi.php?id_campaign=<?php echo urlencode($campaign['ID_CAMPAIGN'] ?? ''); ?>" class="btn-donate">Donasi</a>
                    </div>
                </div>
                <?php
                $rank++;
            }

            if (!$has_campaigns) {
                echo '<div class="empty-state"><p>Belum ada campaign tersedia saat ini.</p></div>';
            }
            ?>
        </div>

        <!-- Footer -->
        <footer>
            <p>&copy; 2025 Donasi Masjid & Amal — Transparansi Keuangan untuk Semua</p>
        </footer>
    </div>
</body>
</html>

<?php
oci_free_statement($stmt_top_5);
oci_free_statement($stmt_stats);
oci_close($conn);
?>
