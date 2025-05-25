<?php
header('Content-Type: application/json');
include 'koneksi.php';

$sql = "SELECT provider, COUNT(*) as count FROM tower GROUP BY provider";
$result = $conn->query($sql);

$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'provider' => $row['provider'],
            'count' => (int)$row['count']
        ];
    }
}

echo json_encode($data);
$conn->close();
?>
