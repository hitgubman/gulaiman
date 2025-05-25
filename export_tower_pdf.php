<?php
require_once 'koneksi.php';
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

$tower_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($tower_id <= 0) {
    die("Invalid tower ID");
}

$sql = "SELECT * FROM tower WHERE id_tower = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tower_id);
$stmt->execute();
$result = $stmt->get_result();
$tower = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$tower) {
    die("Tower not found");
}


$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Detail Tower - TWR-0' . htmlspecialchars($tower['id_tower']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #fff; color: #000; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; }
        th { background-color: #f2f2f2; }
        .badge-status { padding: 0.35em 0.65em; font-size: 0.85em; border-radius: 0.25rem; color: #fff; display: inline-block; }
        .badge-active { background-color: #28a745; }
        .badge-inactive { background-color: #dc3545; }
        .badge-maintenance { background-color: #ffc107; color: #212529; }
        .image-container { text-align: center; margin-top: 20px; }
        .image-container img { max-width: 300px; max-height: 200px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Detail Tower - TWR-0' . htmlspecialchars($tower['id_tower']) . '</h1>
    <div class="image-container">';
if (!empty($tower['gambar']) && file_exists('uploads/' . $tower['gambar'])) {
    $imagePath = realpath('uploads/' . $tower['gambar']);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
    $html .= '<img src="data:image/' . $imageType . ';base64,' . $imageData . '" alt="Tower Image" />';
} else {
    $html .= '<p>No image available</p>';
}
$html .= '</div>
    <table>
        <tbody>
            <tr>
                <th>Nama Provider</th>
                <td>' . htmlspecialchars($tower['provider']) . '</td>
            </tr>
            <tr>
                <th>Alamat</th>
                <td>' . htmlspecialchars($tower['lokasi']) . '</td>
            </tr>
            <tr>
                <th>ID Tower</th>
                <td>TWR-0' . htmlspecialchars($tower['id_tower']) . '</td>
            </tr>
            <tr>
                <th>Status Tower</th>
                <td>';
$status = strtolower($tower['status']);
$badgeClass = '';
if ($status === 'aktif') {
    $badgeClass = 'badge-active';
} elseif ($status === 'non-aktif') {
    $badgeClass = 'badge-inactive';
} elseif ($status === 'maintenance') {
    $badgeClass = 'badge-maintenance';
}
$html .= '<span class="badge-status ' . $badgeClass . '">' . htmlspecialchars($tower['status']) . '</span>';
$html .= '</td>
            </tr>
            <tr>
                <th>Jumlah Kaki</th>
                <td>' . htmlspecialchars($tower['jml_kaki']) . '</td>
            </tr>
            <tr>
                <th>Koordinat</th>
                <td><em>' . htmlspecialchars($tower['lintang']) . ', ' . htmlspecialchars($tower['bujur']) . '</em></td>
            </tr>
        </tbody>
    </table>
</body>
</html>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();


$filename = 'Detail_Tower_TWR-0' . $tower['id_tower'] . '.pdf';
$dompdf->stream($filename, array("Attachment" => 1));
exit;
