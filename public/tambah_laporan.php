    <?php
    /**
     * tambah_laporan.php
     * Form input laporan penggunaan dana campaign
     */
    session_start();
    require_once '../config/db.php';

    

    // Pastikan penerima login
    $username_penerima = $_SESSION['username'] ?? null;
    if (!$username_penerima) {
        header('Location: ../auth/login_penerima.php');
        exit;
    }

    // Ambil id penerima
    $stmt = oci_parse($conn, "SELECT id_penerima FROM penerima WHERE username = :u");
    oci_bind_by_name($stmt, ":u", $username_penerima);
    oci_execute($stmt);

    $id_penerima = null;
    if ($row = oci_fetch_assoc($stmt)) {
        $id_penerima = intval($row['ID_PENERIMA']);
    }
    oci_free_statement($stmt);

    if (!$id_penerima) {
        header('Location: ../auth/login_penerima.php');
        exit;
    }

    // Ambil daftar campaign milik penerima
    $campaigns = [];
    $cq = oci_parse(
        $conn,
        "SELECT id_campaign, judul_campaign 
        FROM campaign 
        WHERE id_penerima = :p
        ORDER BY id_campaign DESC"
    );
    oci_bind_by_name($cq, ":p", $id_penerima);
    oci_execute($cq);

    while ($r = oci_fetch_assoc($cq)) {
        $campaigns[] = $r;
    }
    oci_free_statement($cq);

    $message = "";

    // Jika submit form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $judul              = trim($_POST['judul'] ?? '');
        $id_campaign        = intval($_POST['id_campaign'] ?? 0);
        $total              = floatval($_POST['total_dana'] ?? 0);
        $isi_laporan        = trim($_POST['isi_laporan'] ?? '');

        if ($judul == '' || $id_campaign == 0 || $total <= 0 || $isi_laporan == '') {
            $message = "Semua field wajib diisi.";
        } else {
            $stmt_ins = oci_parse(
                $conn,
                "INSERT INTO laporan (
                    id_laporan,
                    id_penerima,
                    id_campaign,
                    judul_laporan,
                    total_dana_terkumpul,
                    tanggal_generate,
                    isi_laporan
                ) VALUES (
                    seq_laporan.nextval,
                    :p,
                    :c,
                    :j,
                    :t,
                    SYSDATE,
                    :isi
                )"
            );

            oci_bind_by_name($stmt_ins, ":p",   $id_penerima);
            oci_bind_by_name($stmt_ins, ":c",   $id_campaign);
            oci_bind_by_name($stmt_ins, ":j",   $judul);
            oci_bind_by_name($stmt_ins, ":t",   $total);
            oci_bind_by_name($stmt_ins, ":isi", $isi_laporan);

            if (@oci_execute($stmt_ins, OCI_NO_AUTO_COMMIT)) {
                oci_commit($conn);
                header("Location: crud-laporan.php?msg=saved");
                exit;
            } else {
                oci_rollback($conn);
                $err = oci_error($stmt_ins);
                $message = "Gagal menyimpan laporan: " . ($err['message'] ?? '');
            }
        }
    }

    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Tambah Laporan</title>
        <link rel="stylesheet" href="../assets/css/crud-laporan.css">
    </head>
    <body>

    <div class="wrap" style="max-width:600px;margin:auto;padding:20px;">

        <h2>Tambah Laporan</h2>
        <a href="crud-laporan.php" style="display:inline-block;margin-bottom:10px;">← Kembali</a>

        <?php if ($message): ?>
            <div style="padding:10px;background:#ffe5e5;border:1px solid #cc0000;color:#900;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Judul Laporan</label>
            <input type="text" name="judul" required style="width:100%;padding:8px;margin-bottom:10px;">

            <label>Pilih Campaign</label>
            <select name="id_campaign" required style="width:100%;padding:8px;margin-bottom:10px;">
                <option value="">-- Pilih Campaign --</option>
                <?php foreach ($campaigns as $c): ?>
                    <option value="<?= $c['ID_CAMPAIGN'] ?>">
                        <?= htmlspecialchars($c['JUDUL_CAMPAIGN']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Total Dana Digunakan</label>
            <input type="number" min="1" name="total_dana" required style="width:100%;padding:8px;margin-bottom:10px;">

            <label>Isi Laporan</label>
            <textarea name="isi_laporan" required style="width:100%;height:120px;margin-bottom:10px;"></textarea>

            <button type="submit" 
                    style="width:100%;padding:10px;background:#2d7f2d;border:0;color:#fff;font-weight:600;">
                Simpan
            </button>

        </form>

    </div>

    </body>
    </html>

    <?php
    oci_close($conn);
    ?>
