<?php
/**
 * Dashboard Penerima - Menampilkan Top 5 Campaign untuk penerima
 * Database: Oracle
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
    header('Location: ../auth/login_penerima.php');
    exit;
}

if (!isset($_SESSION['id_penerima'])) {
    $_SESSION['id_penerima'] = 1;
    $_SESSION['nama_penerima'] = 'Pengelola Penerima';
    $_SESSION['email_penerima'] = 'penerima@example.com';
}

function formatRupiah($num) {
    return 'Rp ' . number_format(floatval($num), 0, ',', '.');
}

$penerima_id = $_SESSION['id_penerima'];

// Query Top 5 Campaign milik penerima
$query_top_5 = "
    SELECT 
        c.id_campaign,
        c.judul_campaign,
        c.target_dana,
        c.dana_terkumpul,
        ROUND((NVL(c.dana_terkumpul,0) / NULLIF(c.target_dana,0)) * 100, 2) as progress_percentage,
        c.tanggal_mulai,
        c.tanggal_deadline,
        c.status,
        COUNT(d.id_donasi) as donation_count
    FROM 
        campaign c
        LEFT JOIN donasi d ON c.id_campaign = d.id_campaign
    WHERE
        c.status = 'Aktif' AND c.id_penerima = :penerima_id
    GROUP BY
        c.id_campaign, c.judul_campaign, c.target_dana, c.dana_terkumpul,
        c.tanggal_mulai, c.tanggal_deadline, c.status
    ORDER BY 
        c.dana_terkumpul DESC
";

$stmt_top_5 = oci_parse($conn, $query_top_5);
if (!$stmt_top_5) {
    $err = oci_error($conn);
    die('Error prepare top 5: ' . ($err['message'] ?? ''));
}
oci_bind_by_name($stmt_top_5, ':penerima_id', $penerima_id);
if (!oci_execute($stmt_top_5)) {
    $err = oci_error($stmt_top_5);
    die('Error execute top 5: ' . ($err['message'] ?? ''));
}

// Statistik untuk penerima: total donasi diterima, jumlah transaksi, rata-rata
$query_stats = "
    SELECT 
        COUNT(d.id_donasi) as total_donations,
        SUM(d.jumlah_donasi) as total_amount,
        AVG(d.jumlah_donasi) as avg_amount
    FROM donasi d
    JOIN campaign c ON d.id_campaign = c.id_campaign
    WHERE c.id_penerima = :penerima_id
";

$stmt_stats = oci_parse($conn, $query_stats);
oci_bind_by_name($stmt_stats, ':penerima_id', $penerima_id);
if (!oci_execute($stmt_stats)) {
    $err = oci_error($stmt_stats);
    die('Error execute stats: ' . ($err['message'] ?? ''));
}
$stats = oci_fetch_assoc($stmt_stats);
if (!$stats) {
    $stats = array('TOTAL_DONATIONS' => 0, 'TOTAL_AMOUNT' => 0, 'AVG_AMOUNT' => 0);
}

// Count campaigns
$query_campaign_count = "SELECT COUNT(*) AS TOTAL_CAMPAIGNS FROM campaign WHERE id_penerima = :penerima_id";
$stmt_cnt = oci_parse($conn, $query_campaign_count);
oci_bind_by_name($stmt_cnt, ':penerima_id', $penerima_id);
if (oci_execute($stmt_cnt)) {
    $cnt_row = oci_fetch_assoc($stmt_cnt);
    $total_campaigns = $cnt_row['TOTAL_CAMPAIGNS'] ?? 0;
} else {
    $total_campaigns = 0;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penerima - Donasi Masjid & Amal</title>
    
    <link rel="stylesheet" href="../assets/css/dashboard_donatur.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-title">
                <h1>📊 Dashboard Penerima</h1>
                <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['nama_penerima'] ?? 'Penerima'); ?>!</p>
            </div>
            <div class="header-actions">
                <a href="create_campaign.php" class="btn btn-primary">Kelola Campaign</a>
                <a href="../controller/logout_penerimaController.php" class="btn btn-danger">Logout</a>
            </div>
        </header>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Penerimaan</h3>
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
            <div class="stat-card info">
                <h3>Jumlah Campaign</h3>
                <div class="value"><?php echo intval($total_campaigns); ?></div>
            </div>
        </div>

        <div class="section-title">
            <h2>🏆 Top 5 Campaign Anda</h2>
        </div>

        <div class="campaign-container">
            <?php
            $rank = 1;
            $has_campaigns = false;
            while ($rank <= 5 && ($campaign = oci_fetch_assoc($stmt_top_5))) {
                $has_campaigns = true;
                $progress = min(max(floatval($campaign['PROGRESS_PERCENTAGE'] ?? 0), 0), 100);
                ?>
                <div class="campaign-card">
                    <div class="campaign-header">
                        <div class="campaign-rank">#<?php echo $rank; ?></div>
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
                        <a href="detail1.php?id_campaign=<?php echo urlencode($campaign['ID_CAMPAIGN'] ?? ''); ?>" class="btn-donate">Lihat</a>
                    </div>
                </div>
                <?php
                $rank++;
            }

            if (!$has_campaigns) {
                echo '<div class="empty-state"><p>Belum ada campaign Anda yang aktif saat ini.</p></div>';
            }
            ?>
        </div>

        <footer>
            <p>&copy; 2025 Donasi Masjid & Amal — Transparansi Keuangan untuk Semua</p>
        </footer>
    </div>
</body>
</html>

<?php
oci_free_statement($stmt_top_5);
oci_free_statement($stmt_stats);
oci_free_statement($stmt_cnt);
oci_close($conn);
?>
