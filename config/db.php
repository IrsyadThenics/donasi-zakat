<?php
$host = "localhost";
$port = "1521";
$sid  = "orcl"; // ubah sesuai database Oracle kamu
$username = "donasi"; // user schema Oracle
$password = "donasi"; // ganti sesuai akun kamu

$tns = "(DESCRIPTION =
    (ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = $port))
    (CONNECT_DATA = (SID = $sid))
)";
$conn = oci_connect($username, $password, $tns);

if (!$conn) {
    $e = oci_error();
    die("Koneksi ke database gagal: " . $e['message']);
} else {
     echo "";
}
?>
