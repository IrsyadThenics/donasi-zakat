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
        <li><a href="../index.php">Beranda</a></li>
        <li><a href="../public/campaign.php">Campaign</a></li>
        <li><a href="../public/tentang-kami.php">Tentang Kami</a></li>
        <li><a href="register_donatur.php">Register</a></li>
        <li><a href="login_donatur" class="active">Login</a></li>
      </ul>
    </nav>
  </header>

  <main>
    <section class="login-container">
      <h2>Masuk ke Akun Donatur</h2>
      <!--<p class="subtitle">Pilih peran Anda untuk melanjutkan</p>

      <div class="role-selection">
        <button id="btnDonatur" class="role-btn">Sebagai Donatur</button>
        <button id="btnPenerima" class="role-btn">Sebagai Penerima</button>
      </div>
      -->

  <form id="loginForm" method="POST" action="../controller/login_donaturController.php">
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Kata Sandi" required />
        <button type="submit" class="btn-login">Masuk</button>
        <p class="register-text">Belum punya akun? <a href="register_donatur.php">Daftar</a></p>
      </form>
    </section>
  </main>

  <footer>
    <p>© 2025 Donasi Masjid & Amal | “Berbagi untuk Keberkahan”</p>
  </footer>

  <!--<script src="../assets/js/login.js"></script>-->
</body>
</html>
