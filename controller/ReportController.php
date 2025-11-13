<?php
/**
 * Report Generation Controller
 * Handling laporan donasi dan campaign statistics
 */

class ReportController {

    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Get monthly donatur report
     * @param int $year
     * @param int $month
     * @return array Report data
     */
    public function getMonthlyReport($year, $month) {
        $cursor = null;
        
        $sql = "BEGIN sp_monthly_donatur_report(:year, :month, :cursor); END;";
        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':year', $year);
        oci_bind_by_name($stmt, ':month', $month);
        oci_bind_by_name($stmt, ':cursor', $cursor, -1, OCI_B_CURSOR);

        if (!oci_execute($stmt)) {
            throw new Exception("Error executing procedure");
        }

        oci_execute($cursor);

        $data = [];
        while ($row = oci_fetch_assoc($cursor)) {
            $data[] = $row;
        }

        oci_free_statement($cursor);
        oci_free_statement($stmt);

        return $data;
    }

    /**
     * Get top 5 campaigns
     * @return array Campaign data
     */
    public function getTop5Campaigns() {
        $query = "SELECT * FROM v_top_5_campaigns";
        $stmt = oci_parse($this->conn, $query);

        if (!oci_execute($stmt)) {
            throw new Exception("Error fetching top 5 campaigns");
        }

        $data = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = $row;
        }

        oci_free_statement($stmt);

        return $data;
    }

    /**
     * Get campaign statistics
     * @return array Statistics data
     */
    public function getCampaignStatistics() {
        $query = "SELECT * FROM v_campaign_statistics ORDER BY total_amount DESC";
        $stmt = oci_parse($this->conn, $query);

        if (!oci_execute($stmt)) {
            throw new Exception("Error fetching campaign statistics");
        }

        $data = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = $row;
        }

        oci_free_statement($stmt);

        return $data;
    }

    /**
     * Generate monthly report with statistics
     * @param int $year
     * @param int $month
     * @return array Report statistics
     */
    public function generateMonthlyReportStats($year, $month) {
        $query = "
            SELECT 
                COUNT(DISTINCT d.donatur_id) as total_donors,
                COUNT(d.donation_id) as total_donations,
                SUM(d.amount) as total_amount,
                AVG(d.amount) as avg_donation,
                MIN(d.amount) as min_donation,
                MAX(d.amount) as max_donation
            FROM 
                donations d
            WHERE 
                EXTRACT(YEAR FROM d.donation_date) = :year
                AND EXTRACT(MONTH FROM d.donation_date) = :month
                AND d.status = 'completed'
        ";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':year', $year);
        oci_bind_by_name($stmt, ':month', $month);

        if (!oci_execute($stmt)) {
            throw new Exception("Error generating report statistics");
        }

        $result = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    /**
     * Get donatur statistics
     * @param int $donatur_id
     * @return array Donatur data
     */
    public function getDonaturStats($donatur_id) {
        $query = "
            SELECT 
                d.donatur_id,
                d.name,
                d.email,
                d.phone,
                COUNT(don.donation_id) as total_donations,
                SUM(don.amount) as total_amount,
                AVG(don.amount) as avg_donation,
                MIN(don.donation_date) as first_donation,
                MAX(don.donation_date) as last_donation
            FROM 
                donaturs d
                LEFT JOIN donations don ON d.donatur_id = don.donatur_id
            WHERE 
                d.donatur_id = :donatur_id
                AND don.status = 'completed'
            GROUP BY 
                d.donatur_id,
                d.name,
                d.email,
                d.phone
        ";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':donatur_id', $donatur_id);

        if (!oci_execute($stmt)) {
            throw new Exception("Error fetching donatur statistics");
        }

        $result = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    /**
     * Update campaign totals (manual trigger)
     * @return bool Success
     */
    public function updateCampaignTotals() {
        $sql = "BEGIN sp_update_campaign_totals; END;";
        $stmt = oci_parse($this->conn, $sql);

        if (!oci_execute($stmt)) {
            throw new Exception("Error updating campaign totals");
        }

        oci_free_statement($stmt);
        return true;
    }

    /**
     * Get campaign by ID with stats
     * @param int $campaign_id
     * @return array Campaign data
     */
    public function getCampaignWithStats($campaign_id) {
        $query = "
            SELECT 
                c.campaign_id,
                c.campaign_name,
                c.campaign_description,
                c.target_amount,
                c.start_date,
                c.end_date,
                c.status,
                ct.total_donations,
                ct.donation_count,
                ROUND((ct.total_donations / c.target_amount) * 100, 2) as progress_percentage
            FROM 
                campaigns c
                LEFT JOIN campaign_totals ct ON c.campaign_id = ct.campaign_id
            WHERE 
                c.campaign_id = :campaign_id
        ";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':campaign_id', $campaign_id);

        if (!oci_execute($stmt)) {
            throw new Exception("Error fetching campaign");
        }

        $result = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    /**
     * Export report to CSV
     * @param array $data
     * @param string $filename
     */
    public function exportToCSV($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $output = fopen('php://output', 'w');

        if (!empty($data)) {
            // Header
            fputcsv($output, array_keys($data[0]));

            // Data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Get execution statistics untuk analysis
     * @return array Statistics
     */
    public function getExecutionStats() {
        $query = "
            SELECT 
                sql_id,
                child_number,
                executions,
                ROUND(elapsed_time / NULLIF(executions, 0) / 1000000, 4) as avg_time_sec,
                SUBSTR(sql_text, 1, 100) as sql_preview
            FROM 
                v\$sql
            WHERE 
                sql_text LIKE '%donations%'
                OR sql_text LIKE '%campaigns%'
            ORDER BY 
                elapsed_time DESC
            FETCH FIRST 10 ROWS ONLY
        ";

        $stmt = oci_parse($this->conn, $query);

        if (!oci_execute($stmt)) {
            return [];
        }

        $data = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = $row;
        }

        oci_free_statement($stmt);
        return $data;
    }
}

?>
