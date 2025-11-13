<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Donasi Masjid & Amal</title>
  <link rel="stylesheet" href="../assets/css/login.css" />
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
        <li><a href="register_penerima.php">Register</a></li>
      </ul>
    </nav>
  </header>

  <main>
    <section class="login-container">
      <h2>Masuk ke Akun Penerima</h2>
      <!--<p class="subtitle">Pilih peran Anda untuk melanjutkan</p>
      <div class="role-selection">
        <button id="btnDonatur" class="role-btn">Sebagai Donatur</button>
        <button id="btnPenerima" class="role-btn">Sebagai Penerima</button>
      </div>-->

      <form id="loginForm" class="hidden" method="POST" action="../controller/login_penerimaController.php">
        <p class="role-indicator"></p>
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Kata Sandi" required />
        <button type="submit" class="btn-login">Masuk</button>
        <p class="register-text">Belum punya akun? <a href="register_penerima.php">Daftar</a></p>
      </form>
    </section>
  </main>

  <footer>
    <p>© 2025 Donasi Masjid & Amal | “Berbagi untuk Keberkahan”</p>
  </footer>

  <script src="../assets/js/login.js"></script>
</body>
</html>
