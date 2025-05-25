<?php

$requestUri = $_SERVER['REQUEST_URI'];
$pattern = '/^(.*\.php)(\/.*)$/';
if (preg_match($pattern, $requestUri, $matches)) {
    $newUri = $matches[1]; 
    header("Location: $newUri", true, 301);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemetaan Tower</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stail.css">
    
    <style>
        #map {
            height: 500px;
            width: 100%;
            border-radius: 10px;
        }
    </style>
</head>

<body>
<?php include 'header.php'; ?>

    <div class="container mt-4">
        <div class="text-center">
            <h1 class="fw-bold">PEMETAAN TOWER SELULER DISKOMINFO MINAHASA</h1>
            <p>Selamat datang di website pemetaan tower seluler di Minahasa, silahkan gunakan peta interaktif di bawah untuk mencari informasi mengenai tiap tower.</p>
            <br>
        </div>
        
        <!-- Filter Button -->
        <div class="d-flex justify-content-end my-3 align-items-center gap-2">
    <!-- Button trigger modal -->
    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filterModal">
        Filter
    </button>
    <button type="button" class="btn btn-outline-danger" id="resetFilterOutside">
        Reset Filter
    </button>
</div>

        <!-- Modal -->
        <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filterModalLabel">Filter Tower</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="filterAlamat" class="form-label">Alamat:</label>
                            <select class="form-select" id="filterAlamat">
                                <option value="">Pilih Alamat</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="filterProvider" class="form-label">Provider:</label>
                            <select class="form-select" id="filterProvider">
                                <option value="">Pilih Provider</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="filterJumlahKaki" class="form-label">Jumlah Kaki:</label>
                            <select class="form-select" id="filterJumlahKaki">
                                <option value="">Pilih Jumlah Kaki</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="applyFilter">Apply Filter</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Section -->
        <div class="text-center">
            <div id="map"></div>
        </div>

        <!-- Statistics Section -->
        <div class="row mt-5">
            <div class="col-12 col-md-6 mb-4">
                <div class="border rounded p-3">
                    <h5 class="text-center">Statistik Provider</h5>
                    <canvas id="providerChart"></canvas>
                </div>
            </div>
            <div class="col-12 col-md-6 mb-4">
                <div class="border rounded p-3">
                    <h5 class="text-center">Statistik Status Tower</h5>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>
</body>

</html>

<script>
    var map = L.map('map').setView([1.303112, 124.913664], 10);

    // Base layer
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

    // Fetch data from PHP file
    // Removed initial fetch call to use fetchTowers on DOMContentLoaded instead
</script>


<script>
    // Ensure the DOM is fully loaded before adding event listeners
    document.addEventListener('DOMContentLoaded', function () {
        // Fetch filter options and populate dropdowns
        fetch('fetch_filter_options.php')
            .then(response => response.json())
            .then(data => {
                const alamatSelect = document.getElementById('filterAlamat');
                data.alamat.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item;
                    option.textContent = item;
                    alamatSelect.appendChild(option);
                });

                const providerSelect = document.getElementById('filterProvider');
                data.providers.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item;
                    option.textContent = item;
                    providerSelect.appendChild(option);
                });

                const jumlahKakiSelect = document.getElementById('filterJumlahKaki');
                data.jumlahKaki.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item;
                    option.textContent = item;
                    jumlahKakiSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching filter options:', error));

        // Add event listener to the Apply Filter button
        document.getElementById('applyFilter').addEventListener('click', function () {
            // Get filter criteria
            const alamat = document.getElementById('filterAlamat').value;
            const provider = document.getElementById('filterProvider').value;
            const jumlahKaki = document.getElementById('filterJumlahKaki').value;

            console.log('Filter Criteria:', { alamat, provider, jumlahKaki }); // Debugging: Log filter criteria

            // Fetch data and apply the filter
            fetchTowers(alamat, provider, jumlahKaki);

            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
            modal.hide();
        });

        // Add event listener to the Reset Filter button outside modal
        document.getElementById('resetFilterOutside').addEventListener('click', function () {
            // Clear filter selections
            document.getElementById('filterAlamat').value = '';
            document.getElementById('filterProvider').value = '';
            document.getElementById('filterJumlahKaki').value = '';

            // Fetch all towers without filters
            fetchTowers('', '', '');
        });

        // Initial fetch of all towers on page load
        fetchTowers('', '', '');
    });

    async function fetchTowers(alamat, provider, jumlahKaki) {
    try {
        const queryParams = new URLSearchParams();
        if (alamat) queryParams.set('alamat', alamat);
        if (provider) queryParams.set('provider', provider);
        if (jumlahKaki) queryParams.set('jumlah_kaki', jumlahKaki);

        const url = `ambil_tower.php?${queryParams.toString()}`;

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();

        map.eachLayer(layer => {
            if (layer instanceof L.Marker) {
                map.removeLayer(layer);
            }
        });

        data.forEach(tower => {
            L.marker([tower.lintang, tower.bujur]).addTo(map)
                .bindPopup(`<b>TWR-0${tower.id_tower}</b><br>Lokasi: ${tower.lokasi}<br>Provider: ${tower.provider}<br>Status: ${tower.status}`);
        });
    } catch (error) {
        console.error('Error fetching filtered tower data:', error);
    }
}
</script>


<script>
    async function fetchProviderData() {
        try {
            const response = await fetch('fetch_data.php');
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const data = await response.json();

            const labels = data.map(item => item.provider);
            const counts = data.map(item => parseInt(item.count)); 

            const providerData = {
                labels: labels,
                datasets: [{
                    label: 'Number of Towers',
                    data: counts,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            };

            const ctx = document.getElementById('providerChart').getContext('2d');

            const providerChart = new Chart(ctx, {
                type: 'bar',
                data: providerData,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function (value) {
                                    if (Number.isInteger(value)) {
                                        return value;
                                    }
                                }
                            },
                            max: Math.max(...counts) + 1
                        }
                    }
                }
            });

        } catch (error) {
            console.error('Error fetching provider data:', error);
        }
    }

    async function fetchStatusData() {
        try {
            const response = await fetch('ambil_dataaktif.php');
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const data = await response.json();

            const labels = data.map(item => item.status);
            const counts = data.map(item => parseInt(item.count)); 

            const statusData = {
                labels: labels, 
                datasets: [{
                    label: 'Number of Towers',
                    data: counts, 
                    backgroundColor: [
                        'rgba(64, 126, 180, 0.2)', 
                        'rgba(251, 59, 59, 0.2)'  
                    ],
                    borderColor: [
                        'rgb(79, 153, 196)', 
                        'rgb(250, 73, 90)'  
                    ],
                    borderWidth: 1
                }]
            };

            const ctx = document.getElementById('statusChart').getContext('2d');

            const statusChart = new Chart(ctx, {
                type: 'bar',
                data: statusData,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function (value) {
                                    if (Number.isInteger(value)) {
                                        return value;
                                    }
                                }
                            },
                            max: Math.max(...counts) + 1
                        }
                    }
                }
            });

        } catch (error) {
            console.error('Error fetching status data:', error);
        }
    }

    // Call the fetch functions
    fetchProviderData();
    fetchStatusData();
</script>
</body>

</html>
