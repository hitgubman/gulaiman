<?php

$host = '127.0.0.1';
$dbname = 'tower_kominfo';
$username = 'root'; 
$password = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


$stmt = $pdo->query("SELECT * FROM tower");
$towers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PEMETAAN TOWER SELULER DISKOMINFO MINAHASA</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        #map {
            height: 500px;
            border-radius: 5px;
        }
        .tower-card {
            transition: transform 0.2s;
        }
        .tower-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .img-thumbnail {
            height: 200px;
            object-fit: cover;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid bg-primary text-white py-3">
        <div class="container">
            <h1 class="text-center">PEMETAAN TOWER SELULER</h1>
            <p class="text-center mb-0">DISKOMINFO KABUPATEN MINAHASA</p>
        </div>
    </div>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-8">
                <div id="map" class="shadow mb-4"></div>
            </div>
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Daftar Tower Seluler</h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($towers as $tower): ?>
                            <div class="card mb-3 tower-card" 
                                 onclick="flyTo(<?= $tower['lintang'] ?>, <?= $tower['bujur'] ?>)"
                                 style="cursor: pointer;">
                                <div class="position-relative">
                                    <img src="uploads/<?= $tower['gambar'] ?>" class="card-img-top img-thumbnail" alt="Tower Image">
                                    <span class="badge <?= $tower['status'] == 'Aktif' ? 'bg-success' : 'bg-danger' ?> status-badge">
                                        <?= $tower['status'] ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">Tower <?= $tower['id_tower'] ?></h5>
                                    <p class="card-text">
                                        <strong>Provider:</strong> <?= $tower['provider'] ?><br>
                                        <strong>Lokasi:</strong> <?= $tower['lokasi'] ?><br>
                                        <strong>Kaki Tower:</strong> <?= $tower['jml_kaki'] ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-3 bg-light">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 text-center">
                    <span class="text-dark opacity-75">Copyright © Dinas Komunikasi dan Informatika Kabupaten Minahasa</span>
                </div>
            </div>
            <div class="row mt-2 justify-content-between align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">PEMETAAN TOWER SELULER<br>DISKOMINFO MINAHASA</p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                    <div class="d-inline-block me-3">
                        <a href="#" class="text-dark me-2"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-dark"><i class="fab fa-instagram"></i></a>
                    </div>
                    <div class="d-inline-block">
                        <img src="minahasa.png" alt="Logo Minahasa" style="height: 40px;">
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    
    <script>
        // Initialize map
        const map = L.map('map').setView([1.3, 125], 10);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Add markers for each tower
        <?php foreach ($towers as $tower): ?>
            const marker<?= $tower['id_tower'] ?> = L.marker([<?= $tower['lintang'] ?>, <?= $tower['bujur'] ?>]).addTo(map)
                .bindPopup(`
                    <b>Tower <?= $tower['id_tower'] ?></b><br>
                    Provider: <?= $tower['provider'] ?><br>
                    Status: <span class="<?= $tower['status'] == 'Aktif' ? 'text-success' : 'text-danger' ?>"><?= $tower['status'] ?></span><br>
                    Kaki: <?= $tower['jml_kaki'] ?><br>
                    <img src="uploads/<?= $tower['gambar'] ?>" style="max-width: 100%; max-height: 150px;" class="mt-2">
                `);
        <?php endforeach; ?>

        // Function to fly to specific coordinates
        function flyTo(lat, lng) {
            map.flyTo([lat, lng], 15);
        }
    </script>
</body>
</html>