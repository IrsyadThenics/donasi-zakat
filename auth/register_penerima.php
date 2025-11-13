<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Daftar Akun - Donasi Masjid & Amal</title>
  <link rel="stylesheet" href="../assets/css/register.css" />
</head>
<body>
  <header>
    <nav class="nav-container">
      <div class="logo">
        <span class="icon">🕌</span>
        <span class="logo-text">DonasiMasjid</span>
      </div>
      <ul class="nav-links">
        <li><a href="../index.php" class="active">Beranda</a></li>
        <li><a href="../public/campaign.php">Program</a></li>
        <li><a href="../public/tentang-kami.php">Tentang Kami</a></li>
        <li><a href="login_penerima.php">Login</a></li>
      </ul>
    </nav>
  </header>

  <main>
    <section class="register-container">
      <h2>Buat Akun Baru Penerima</h2>
      <!--<p class="subtitle">Pilih peran dan isi data Anda</p>

      <div class="role-selection">
        <button id="btnDonatur" class="role-btn">Donatur</button>
        <button id="btnPenerima" class="role-btn">Penerima</button>
      </div>-->

      <form id="registerForm" method="POST" action="../controller/register_penerimaController.php">
        <input type="text" name="username" placeholder="Username" required />
        <input type="hidden" name="role" id="roleInput" value="">
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Kata Sandi" required />
        <input type="password" name="password_confirm" placeholder="Konfirmasi Kata Sandi" required />
        <button type="submit" class="btn-register">Daftar</button>
        <!--<p class="login-link">
          Sudah punya akun? <a href="login_penerima.php">Masuk di sini</a>-->
      </form>

    </section>
  </main>

  <footer>
    <p>© 2025 Donasi Masjid & Amal | “Berbagi untuk Keberkahan”</p>
  </footer>

<!--<script src="../assets/js/register.js"></script>-->
</body>
</html>
