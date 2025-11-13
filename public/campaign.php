<?php
/**
 * Dynamic campaign listing — load campaigns from Oracle DB
 */
session_start();
require_once '../config/db.php';

function formatRupiah($num) {
    return 'Rp ' . number_format(floatval($num), 0, ',', '.');
}

// Fetch active campaigns
$campaigns = [];
$sql = "SELECT id_campaign, judul_campaign, deskripsi, target_dana, NVL(dana_terkumpul,0) AS dana_terkumpul, tanggal_mulai, tanggal_deadline FROM campaign WHERE status = 'Aktif' ORDER BY tanggal_mulai DESC";
$stmt = oci_parse($conn, $sql);
if ($stmt && oci_execute($stmt)) {
    while ($row = oci_fetch_assoc($stmt)) {
        $campaigns[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Campaign Aktif - Donasi Masjid & Amal</title>
  <link rel="stylesheet" href="../assets/css/campaign.css" />
</head>
<body>
  <header>
    <nav class="nav-container">
      <div class="logo">
        <span class="icon">👤</span>
        <span class="logo-text">Campaign Aktif</span>
      </div>
      <ul class="nav-links">
        <li><a href="../index.php" class="active">Beranda</a></li>
        <li><a href="campaign.php">Program</a></li>
        <li><a href="tentang-kami.php">Tentang Kami</a></li>
        <li><a href="../auth/login_penerima.php">Login penerima</a></li>
        <li><a href="../auth/login_donatur.php">Login donatur</a></li>
      </ul>
    </nav>
  </header>

  <main>
    <section class="campaign-list">
      <?php if (empty($campaigns)): ?>
        <p style="padding:20px">Belum ada campaign aktif saat ini.</p>
      <?php endif; ?>

      <?php foreach ($campaigns as $c):
        $progress = $c['TARGET_DANA'] > 0 ? round(($c['DANA_TERKUMPUL'] / $c['TARGET_DANA']) * 100) : 0;
        $id = urlencode($c['ID_CAMPAIGN']);
      ?>
      <article class="campaign-card">
        <img src="../assets/img/g1.png" alt="<?php echo htmlspecialchars($c['JUDUL_CAMPAIGN']); ?>" />
        <h3><?php echo htmlspecialchars($c['JUDUL_CAMPAIGN']); ?></h3>
        <div class="progress-bar-bg">
          <div class="progress-bar-fg" style="width: <?php echo $progress; ?>%"></div>
        </div>
        <p class="donation-amount">
          <span><?php echo formatRupiah($c['DANA_TERKUMPUL']); ?></span>
          <small>dari <?php echo formatRupiah($c['TARGET_DANA']); ?></small>
        </p>
        <div class="btn-group">
          <a href="detail1.php?id=<?php echo $id; ?>" class="btn btn-detail">Detail</a>
          <a href="form_donasi.php?id_campaign=<?php echo $id; ?>" class="btn btn-donate">Donasi</a>
        </div>
      </article>
      <?php endforeach; ?>

    </section>
  </main>

  <footer>
    <p>© 2025 Donasi Masjid & Amal | “Berbagi untuk Keberkahan”</p>
  </footer>
</body>
</html>
