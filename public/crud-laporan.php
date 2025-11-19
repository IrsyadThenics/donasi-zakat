<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan — Dashboard Takmir</title>

    <!-- GLOBAL CSS CRUD LAPORAN -->
    <link rel="stylesheet" href="../assets/css/crud-donatur.css">
    <link rel="stylesheet" href="../assets/css/crud-laporan.css">
</head>

<body>

    <!-- ================= HEADER ================= -->
    <header>
        <div class="nav-container">
            <div class="logo" style="font-weight:bold; color:#2f4a2f;">
                🕌 Manajemen Masjid
            </div>

            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="tentang.php">Tentang</a></li>
                <li><a href="kontak.php">Kontak</a></li>
                <li><a href="dashboard-penerima.php" class="active">Dashboard</a></li>
            </ul>
        </div>
    </header>

    <!-- ================= WRAPPER ================= -->
    <div class="wrap">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <h3 style="margin-bottom:15px; color:#2f4a2f;">Menu Admin</h3>

            <div class="side-list">
                <a href="dashboard_penerima.php">Dashboard</a>
                <a href="crud-campaign.php">Kelola Campaign</a>
                <a href="crud-donasi.php">Kelola Donasi</a>
                <a href="crud-donatur.php">Kelola Donatur</a>
                <a href="crud-laporan.php" class="active">Kelola Laporan</a>
            </div>
        </aside>

        <!-- CONTENT -->
        <main class="content">

            <h1 style="color:#2f4a2f; font-size:26px; font-weight:700;">
                Kelola Laporan
            </h1>
            <p style="color:#777;">Transparansi penggunaan dana setiap campaign</p>

            <!-- CARDS -->
            <div class="cards">

                <div class="card">
                    <h4>Total Laporan</h4>
                    <div class="val">12</div>
                </div>

                <div class="card">
                    <h4>Bulan Ini</h4>
                    <div class="val">4</div>
                </div>

                <div class="card">
                    <h4>Total Pengeluaran</h4>
                    <div class="val">Rp 1.280.000</div>
                </div>

                <div class="card">
                    <h4>Campaign Terlapor</h4>
                    <div class="val">2</div>
                </div>

            </div>

            <!-- TOOLBAR -->
            <div class="toolbar">
                <button class="btn" id="btnAdd">+ Tambah Laporan</button>

                <input type="text" class="input-search" placeholder="Cari laporan...">
                <select class="filter-select">
                    <option>Semua Campaign</option>
                    <option>Jum'at Berkah</option>
                    <option>Bantuan Sosial</option>
                </select>
            </div>

            <!-- TABLE PANEL -->
            <div class="panel">
                <h3 style="margin-bottom:15px; color:#2f4a2f;">Daftar Laporan Penggunaan Dana</h3>

                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Judul</th>
                            <th>Campaign</th>
                            <th>Jumlah</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr>
                            <td>2025-11-12</td>
                            <td>Penggunaan Donasi Jum'at Berkah - Pembelian Beras</td>
                            <td>Jum'at Berkah</td>
                            <td>Rp 80.000</td>
                            <td>
                                <button class="btn small secondary">Detail</button>
                                <button class="btn small danger">Hapus</button>
                            </td>
                        </tr>

                        <tr>
                            <td>2025-11-07</td>
                            <td>Penggunaan Donasi Bantuan Sosial - Semen Renovasi</td>
                            <td>Bantuan Sosial</td>
                            <td>Rp 1.200.000</td>
                            <td>
                                <button class="btn small secondary">Detail</button>
                                <button class="btn small danger">Hapus</button>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

        </main>

    </div>

    <!-- ================= FOOTER ================= -->
    <footer>
        © 2025 Masjid Al-Falah — Dashboard Takmir
    </footer>
    <script src="../assets/js/crud-laporan.js"></script>

</body>
</html>
