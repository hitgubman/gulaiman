<?php
include "../koneksi.php";


$total_towers = 0;
$active_towers = 0;
$inactive_towers = 0;
$recent_towers = [];

$sql_total = "SELECT COUNT(*) as total FROM tower";
$sql_active = "SELECT COUNT(*) as active FROM tower WHERE status = 'Aktif'";
$sql_inactive = "SELECT COUNT(*) as inactive FROM tower WHERE status = 'Non-aktif'";

$result_total = $conn->query($sql_total);
if ($result_total) {
    $row = $result_total->fetch_assoc();
    $total_towers = $row['total'];
}

$result_active = $conn->query($sql_active);
if ($result_active) {
    $row = $result_active->fetch_assoc();
    $active_towers = $row['active'];
}

$result_inactive = $conn->query($sql_inactive);
if ($result_inactive) {
    $row = $result_inactive->fetch_assoc();
    $inactive_towers = $row['inactive'];
}


$sql_recent = "SELECT id_tower, provider, status, lokasi FROM tower ORDER BY id_tower DESC LIMIT 5";
$result_recent = $conn->query($sql_recent);
if ($result_recent) {
    while ($row = $result_recent->fetch_assoc()) {
        $recent_towers[] = $row;
    }
}
?>

<h2>Beranda</h2>
<p>Selamat datang di sistem pemetaan tower seluler.</p>

<div class="row">
    <!-- Summary Cards -->
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Tower</h5>
                <p class="card-text fs-3"><?= $total_towers ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Tower Aktif</h5>
                <p class="card-text fs-3"><?= $active_towers ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title">Tower Non-aktif</h5>
                <p class="card-text fs-3"><?= $inactive_towers ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Buttons -->
<div class="mb-4 d-flex flex-wrap gap-2">
    <a href="?page=add_tower" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tambah Tower Baru
    </a>
    <a href="?page=olahdata" class="btn btn-secondary">
        <i class="bi bi-list"></i> Lihat Semua Tower
    </a>
</div>

<!-- Recent Towers Table -->
<div class="card mb-4">
<div class="card-header d-flex align-items-center">
    <h5 class="mb-0">Daftar Tower yang Baru Ditambahkan</h5>
</div>
    <div class="card-body">
        <?php if (count($recent_towers) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID Tower</th>
                        <th>Provider</th>
                        <th>Status</th>
                        <th>Lokasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_towers as $tower): ?>
                    <tr>
                        <td><?= "TWR-0" . $tower['id_tower'] ?></td>
                        <td><?= htmlspecialchars($tower['provider']) ?></td>
                        <td><?= htmlspecialchars($tower['status']) ?></td>
                        <td><?= htmlspecialchars($tower['lokasi']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p>Tidak ada data tower terbaru.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Map Visualization -->
<div class="card">
<div class="card-header d-flex align-items-center">
    <h5 class="mb-0">Peta Sebaran Tower</h5>
</div>
    <div class="card-body">
        <div id="map" style="height: 400px;"></div>
    </div>
</div>

<!-- Leaflet CSS & JS -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
<style>
    body, html {
        font-family: 'Inter', sans-serif !important;
    }
</style>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    var map = L.map('map').setView([1.235866, 124.906327], 11);


    var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    });

    var satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles © Esri — Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
    });

    osmLayer.addTo(map);

    var baseMaps = {
        "OpenStreetMap": osmLayer,
        "Satellite": satelliteLayer
    };

    L.control.layers(baseMaps).addTo(map);

    <?php
    
    $sql_locations = "SELECT id_tower, provider, status, lokasi, lintang, bujur FROM tower WHERE lintang IS NOT NULL AND bujur IS NOT NULL";
    $result_locations = $conn->query($sql_locations);
    if ($result_locations) {
        while ($row = $result_locations->fetch_assoc()) {
            $lat = $row['lintang'];
            $lng = $row['bujur'];
            $id = $row['id_tower'];
            $provider = htmlspecialchars($row['provider'], ENT_QUOTES);
            $status = htmlspecialchars($row['status'], ENT_QUOTES);
            $lokasi = htmlspecialchars($row['lokasi'], ENT_QUOTES);
            echo "L.marker([$lat, $lng]).addTo(map).bindPopup('<b>ID Tower:</b> TWR-0$id<br><b>Provider:</b> $provider<br><b>Status:</b> $status<br><b>Lokasi:</b> $lokasi');\n";
        }
    }
    ?>
</script>
