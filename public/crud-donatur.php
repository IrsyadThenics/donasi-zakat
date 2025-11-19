<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Donatur</title>
    <link rel="stylesheet" href="../assets/css/crud-donatur.css">
</head>
<body>

<!-- HEADER -->
<header>
  <div class="nav-container">
    <h2 style="color:#4CAF50;">Takmir</h2>
    <ul class="nav-links">
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="kelola-campaign.php">Campaign</a></li>
      <li><a href="crud-donasi.php">Donasi</a></li>
      <li><a class="active" href="crud-donatur.php">Donatur</a></li>
      <li><a href="../controller/logout_penerimaController.php">Logout</a></li>
    </ul>
  </div>
</header>

<!-- MAIN -->
<div class="wrap">

    <!-- SIDEBAR -->
   <div class="sidebar">
    <h3>Menu Admin</h3>
    <div class="side-list">
        <a href="dashboard_penerima.php">Dashboard</a>
        <a href="crud-campaign.php">Kelola Campaign</a>
        <a href="crud-donasi.php">Kelola Donasi</a>
        <a href="crud-donatur.php" class="active">Kelola Donatur</a>
        <a href="crud-laporan.php">Kelola Laporan</a>
    </div>
</div>  <!-- ← TAMBAHKAN INI -->

    <!-- CONTENT -->
    <div class="content">
        <div class="page-head">
            <h1>Kelola Donatur</h1>
            <p>Data para donatur yang terdaftar</p>
        </div>

        <button class="btn" onclick="location.href='donatur-tambah.php'">+ Tambah Donatur</button>

        <div class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Donatur</th>
                            <th>Email</th>
                            <th>No. WA</th>
                            <th>Tanggal Daftar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <!-- CONTOH DATA -->
                        <tr>
                            <td>1</td>
                            <td>Ahmad Fauzi</td>
                            <td>ahmad@example.com</td>
                            <td>081234567890</td>
                            <td>2025-10-21</td>
                            <td><span class="tag active">Aktif</span></td>
                            <td>
                                <button class="btn small secondary" onclick="location.href='donatur-edit.php?id=1'">Edit</button>
                                <button class="btn small danger">Hapus</button>
                            </td>
                        </tr>

                        <tr>
                            <td>2</td>
                            <td>Siti Lestari</td>
                            <td>siti@example.com</td>
                            <td>08567812345</td>
                            <td>2025-10-25</td>
                            <td><span class="tag closed">Nonaktif</span></td>
                            <td>
                                <button class="btn small secondary">Edit</button>
                                <button class="btn small danger">Hapus</button>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- FOOTER -->
<footer>
  <p>© 2025 Takmir Masjid — Sistem Donasi</p>
</footer>

</body>
</html>
