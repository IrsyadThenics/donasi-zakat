<?php
/**
 * Laporan Donatur Bulanan
 * Menampilkan laporan donasi donatur per bulan
 * Menggunakan Procedure: sp_monthly_donatur_report
 */

session_start();
require_once '../config/db.php';

// Cek login
//if (!isset($_SESSION['donatur_id'])) {
//    header('Location: ../auth/login_donatur.php');
//    exit;
//}

// Get year dan month dari request atau gunakan current
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

// Validasi
$year = max(2020, min($year, date('Y')));
$month = max(1, min($month, 12));

// Format untuk display
$month_name = date('F', mktime(0, 0, 0, $month, 1));
$report_period = "$month_name $year";

// Fungsi untuk format Rupiah
function formatRupiah($num) {
    return 'Rp ' . number_format($num, 0, ',', '.');
}

// Query menggunakan Procedure
$cursor = null;
$sql = "BEGIN sp_monthly_donatur_report(:year, :month, :cursor); END;";
$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ':year', $year);
oci_bind_by_name($stmt, ':month', $month);
oci_bind_by_name($stmt, ':cursor', $cursor, -1, OCI_B_CURSOR);

oci_execute($stmt);
oci_execute($cursor);

// Fetch data dari cursor
$report_data = [];
while ($row = oci_fetch_assoc($cursor)) {
    $report_data[] = $row;
}

oci_free_statement($cursor);
oci_free_statement($stmt);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Donatur Bulanan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        header p {
            color: #666;
            font-size: 14px;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 150px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }

        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
        }

        .btn-print {
            background: #27ae60;
            color: white;
        }

        .btn-print:hover {
            background: #229954;
        }

        .btn-back {
            background: #95a5a6;
            color: white;
        }

        .btn-back:hover {
            background: #7f8c8d;
        }

        /* Report Summary */
        .summary-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .summary-item {
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }

        .summary-item h3 {
            color: #666;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .summary-item .value {
            color: #333;
            font-size: 24px;
            font-weight: bold;
        }

        /* Table */
        .table-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }

        .table-header h3 {
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        thead th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
            font-size: 13px;
            text-transform: uppercase;
        }

        tbody td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #555;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .table-empty {
            padding: 40px;
            text-align: center;
            color: #999;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-primary {
            background: #e3f2fd;
            color: #1976d2;
        }

        /* Footer */
        footer {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            color: #666;
            margin-top: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
            }

            .filter-section,
            .btn {
                display: none;
            }

            header, .summary-section, .table-section {
                box-shadow: none;
                page-break-inside: avoid;
            }
        }

        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }

            .form-group {
                min-width: 100%;
            }

            table {
                font-size: 12px;
            }

            thead th, tbody td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <h1>📊 Laporan Donasi Bulanan</h1>
            <p>Laporan riwayat donasi Anda untuk periode <?php echo $report_period; ?></p>
        </header>

        <!-- Filter Section -->
        <div class="filter-section">
            <form class="filter-form">
                <div class="form-group">
                    <label for="month">Bulan:</label>
                    <select name="month" id="month" onchange="this.form.submit()">
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $name = date('F', mktime(0, 0, 0, $m, 1));
                            $selected = $m == $month ? 'selected' : '';
                            echo "<option value=\"$m\" $selected>$name</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="year">Tahun:</label>
                    <select name="year" id="year" onchange="this.form.submit()">
                        <?php
                        $current_year = date('Y');
                        for ($y = 2020; $y <= $current_year; $y++) {
                            $selected = $y == $year ? 'selected' : '';
                            echo "<option value=\"$y\" $selected>$y</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="button" onclick="window.print()" class="btn btn-print">🖨️ Cetak</button>
                <a href="dashboard_donatur.php" class="btn btn-back">← Kembali</a>
            </form>
        </div>

        <?php
        // Calculate summary
        $total_donations = 0;
        $total_amount = 0;
        $unique_campaigns = 0;

        if (!empty($report_data)) {
            $campaigns = [];
            foreach ($report_data as $row) {
                $total_donations += (int)$row['DONATION_COUNT'];
                $total_amount += (int)$row['TOTAL_DONATED'];
                $campaigns[$row['DONATUR_ID']] = true;
            }
            $unique_campaigns = count($campaigns);
        }
        ?>

        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-grid">
                <div class="summary-item">
                    <h3>Total Transaksi</h3>
                    <div class="value"><?php echo $total_donations; ?>x</div>
                </div>
                <div class="summary-item">
                    <h3>Total Donasi</h3>
                    <div class="value"><?php echo formatRupiah($total_amount); ?></div>
                </div>
                <div class="summary-item">
                    <h3>Rata-rata</h3>
                    <div class="value"><?php echo formatRupiah($total_donations > 0 ? $total_amount / $total_donations : 0); ?></div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-section">
            <div class="table-header">
                <h3>📋 Detail Donasi - <?php echo $report_period; ?></h3>
            </div>

            <?php if (empty($report_data)): ?>
                <div class="table-empty">
                    <p>Tidak ada data donasi untuk periode <?php echo $report_period; ?></p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%">No</th>
                            <th style="width: 25%">Nama Donatur</th>
                            <th style="width: 20%">Email</th>
                            <th class="text-right" style="width: 15%">Jumlah Donasi</th>
                            <th class="text-right" style="width: 15%">Total Donasi</th>
                            <th class="text-right" style="width: 20%">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($report_data as $row):
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['NAME']); ?></strong>
                                    <br>
                                    <small><?php echo htmlspecialchars($row['PHONE']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['EMAIL']); ?></td>
                                <td class="text-right">
                                    <span class="badge badge-primary"><?php echo $row['DONATION_COUNT']; ?> Transaksi</span>
                                </td>
                                <td class="text-right">
                                    <strong><?php echo formatRupiah($row['TOTAL_DONATED']); ?></strong>
                                </td>
                                <td class="text-right">
                                    <?php echo formatRupiah($row['TOTAL_DONATED'] / $row['DONATION_COUNT']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <footer>
            <p>&copy; 2025 Donasi Masjid & Amal — Laporan Transparan untuk Donatur Setia</p>
            <p style="font-size: 12px; margin-top: 10px; color: #999;">
                Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?>
            </p>
        </footer>
    </div>
</body>
</html>

<?php
oci_close($conn);
?>
