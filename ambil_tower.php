<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include 'koneksi.php';

$alamat = isset($_GET['alamat']) ? $_GET['alamat'] : '';
$provider = isset($_GET['provider']) ? $_GET['provider'] : '';
$jumlahKaki = isset($_GET['jumlah_kaki']) ? $_GET['jumlah_kaki'] : '';

$sql = "SELECT * FROM tower WHERE 1=1";

if (!empty($alamat)) {
    $sql .= " AND lokasi LIKE '%$alamat%'"; 
}
if (!empty($provider)) {
    $sql .= " AND provider LIKE '%$provider%'"; 
}
if (!empty($jumlahKaki)) {
    $sql .= " AND jml_kaki = $jumlahKaki";
}

$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$conn->close();
echo json_encode($data);
?>