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
    <div class="nav-container">
        <div class="logo">
            <span class="icon">🕌</span>  
            Manajemen Masjid
        </div>

        <ul class="nav-links">
            <li><a href="index.php">Beranda</a></li>
            <li><a href="tentang.php">Tentang</a></li>
            <li><a href="kontak.php">Kontak</a></li>
            <li><a href="#" class="active">Dashboard</a></li>
        </ul>
    </div>
</header>

<!-- WRAPPER -->
<div class="wrap">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <h3>Menu Admin</h3>

        <div class="side-list">
            <a href="dashboard-penerima.php" class="active">Dashboard</a>
            <a href="crud-campaign.php">Kelola Campaign</a>
            <a href="crud-donasi.php">Kelola Donasi</a>
            <a href="crud-donatur.php">Kelola Donatur</a>
            <a href="crud-laporan.php">Kelola Laporan</a>
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
                <div class="val">Rp 300.000</div>
            </div>

            <div class="card">
                <h3>Total Penyaluran</h3>
                <div class="val">Rp 700.000</div>
            </div>

            <div class="card">
                <h3>Campaign Aktif</h3>
                <div class="val">5 Program</div>
            </div>

            <div class="card">
                <h3>Total Laporan Donatur</h3>
                <div class="val">2 Laporan</div>
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
                        <th>Aksi</th>
                    </tr>

                    <tr>
                        <td>2025-11-07</td>
                        <td>Laporan</td>
                        <td>Detail penggunaan dana</td>
                        <td>-</td>
                        <td><span class="tag active">Disetujui</span></td>
                        <td>
                            <button class="btn secondary small">Edit</button>
                            <button class="btn danger small">Hapus</button>
                        </td>
                    </tr>

                    <tr>
                        <td>2025-11-08</td>
                        <td>Laporan</td>
                        <td>Bukti pembelian semen</td>
                        <td>Rp 1.200.000</td>
                        <td><span class="tag closed">Menunggu</span></td>
                        <td>
                            <button class="btn secondary small">Edit</button>
                            <button class="btn danger small">Hapus</button>
                            <button class="btn small">Verifikasi</button>
                        </td>
                    </tr>

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

</body>
</html>
