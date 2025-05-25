    <?php
include 'koneksi.php';

$tower_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tower - <?= htmlspecialchars($tower['id_tower']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="stail.css"/>
    <style>
        body {
        background-color: var(--bg-color);
        }

        .map-image-row {
            --gap: 12px;
            margin-bottom: 20px;
        }
        .fixed-container {
            width: 100%;
            height: 236px;
            position: relative;
        }
        #map {
            width: 100%;
            height: 100%;
            border-radius: 5px;
        }
        .tower-info {
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .image-container {
            width: 100%;
            height: 100%;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            position: relative; 
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover; 
            position: absolute;
            top: 0;
            left: 0;
            transition: transform 0.3s ease;
        }
        .image-container:hover img {
            transform: scale(1.05);
        }
        .image-container img[title] {
            position: relative;
        }
        .image-container img[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 5px;
            left: 5px;
            background: rgba(0,0,0,0.7);
            color: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        .placeholder-icon {
            font-size: 48px;
            color: #6c757d;
        }

        table.table-bordered tbody tr:nth-child(odd) {
            background-color: var(--detail-table-row-odd-bg);
        }
        table.table-bordered tbody tr:hover {
            background-color: var(--detail-table-row-hover-bg);
        }
        .badge-status {
            padding: 0.35em 0.65em;
            font-size: 0.85em;
            border-radius: 0.25rem;
            color: #fff;
            display: inline-block;
        }
        .badge-active {
            background-color: #28a745;
        }
        .badge-inactive {
            background-color: #dc3545;
        }
        .badge-maintenance {
            background-color: #ffc107;
            color: #212529;
        }

        @media (min-width: 992px) {
            .map-col {
                padding-right: calc(var(--gap) / 2);
            }
            .image-col {
                padding-left: calc(var(--gap) / 2);
            }
        }

        .footer {
    margin-top: auto;
}
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
        <div class="container mt-4">
            <div>
                <h1 class="mb-4">Detail Tower</h1>
                <a href="export_tower_pdf.php?id=<?= $tower['id_tower'] ?>" class="btn btn-primary mb-3">Export to PDF</a>
            </div>

        <div class="row map-image-row g-2">
            <div class="col-lg-8 map-col">
                <div class="fixed-container shadow-sm">
                    <div id="map"></div>
                </div>
            </div>
            <div class="col-lg-4 image-col">
                <div class="fixed-container shadow-sm">
                    <div class="image-container h-100" id="image-container">
                        <?php if (!empty($tower['gambar'])): ?>
                            <img id="tower-photo" src="uploads/<?= htmlspecialchars($tower['gambar']) ?>" alt="Tower Photo" class="preview-image" style="cursor:pointer;" data-towerid="<?= 'TWR-0' . htmlspecialchars($tower['id_tower']) ?>">
                        <?php else: ?>
                            <div id="no-image" class="text-center p-3">
                                <div class="placeholder-icon mb-2">
                                    <i class="bi bi-image"></i>
                                </div>
                                <div class="text-muted">No image available</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <h2 class="mb-0">Informasi Tower</h>
        </div>  
        <div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
        <tbody>
            <tr>
                <td class="fw-bold">Nama Provider</td>
                <td><?= htmlspecialchars($tower['provider']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Alamat</td>
                <td><?= htmlspecialchars($tower['lokasi'])  ?></td>
            </tr>
            <tr>
                <td class="fw-bold">ID Tower</td>
                <td><?= "TWR-0" . htmlspecialchars($tower['id_tower']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Status Tower</td>
                <td>
                    <?php
                    $status = strtolower($tower['status']);
                    $badgeClass = 'badge-status ';
                    if ($status === 'aktif') {
                        $badgeClass .= 'badge-active';
                    } elseif ($status === 'non-aktif') {
                        $badgeClass .= 'badge-inactive';
                    } 
                    echo '<span class="' . $badgeClass . '">' . htmlspecialchars($tower['status']) . '</span>';
                    ?>
                </td>
            </tr>
            <tr>
                <td class="fw-bold">Jumlah Kaki</td>
                <td><?= htmlspecialchars($tower['jml_kaki']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Koordinat</td>
                <td><em><?= htmlspecialchars($tower['lintang']) ?>, <?= htmlspecialchars($tower['bujur']) ?></em></td>
            </tr>
        </tbody>
    </table>
</div>
    </div>
<?php include 'footer.php'; ?>



    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Use actual coordinates from database
            const coords = [<?= $tower['lintang'] ?>, <?= $tower['bujur'] ?>];
            const map = L.map('map').setView(coords, 15);
            
            var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
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


            // Add scale bar
            L.control.scale().addTo(map);
            
            // Add marker with actual data and enhanced popup
            L.marker(coords).addTo(map)
                .bindPopup(`
                    <b>TWR-0<?= $tower['id_tower'] ?></b><br>
                `)
                .openPopup();
        });
    </script>
</body>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark">
      <div class="modal-header border-0">
        <h5 class="modal-title text-white" id="imagePreviewModalLabel">Preview Gambar Tower</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="modalImage" class="img-fluid rounded" alt="Preview Image" />
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.preview-image').addEventListener('click', function() {
        var src = this.getAttribute('src');
        var towerId = this.getAttribute('data-towerid');
        document.getElementById('modalImage').setAttribute('src', src);
        document.getElementById('imagePreviewModalLabel').textContent = towerId;
        var myModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
        myModal.show();
    });
});
</script>

</html>
