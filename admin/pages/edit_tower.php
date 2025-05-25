<?php
include "../koneksi.php";

    if(isset($_GET['kode'])){
        $sql_cek = "SELECT * FROM tower WHERE id_tower ='".$_GET['kode']."'";
        $query_cek = mysqli_query($conn, $sql_cek);
        $data_cek = mysqli_fetch_array($query_cek,MYSQLI_BOTH);
    }

?>

<main>
    <div class="container-fluid">
    <h4 class="mb-4 font-weight-bold">Edit Data Tower</h4>
        <div class="card p-4 shadow-sm">
            <form action="" method="post" enctype="multipart/form-data">
                
                <div class="mb-3">
                    <label>Nama Provider</label>
                    <input type="text" class="form-control form-control-sm" name="provider" value="<?php echo $data_cek['provider']; ?>">
                </div>

                <div class="mb-3">
                    <label>Status</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" value="Aktif" <?php if ($data_cek['status'] == 'Aktif') echo 'checked'; ?>>
                        <label class="form-check-label">Aktif</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" value="Non-Aktif" <?php if ($data_cek['status'] == 'Non-Aktif') echo 'checked'; ?>>
                        <label class="form-check-label">Non-Aktif</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Lokasi</label>
                    <input type="text" class="form-control form-control-sm" name="lokasi" value="<?php echo $data_cek['lokasi']; ?>">
                </div>

                <div class="mb-3">
                    <label>Jumlah Kaki</label>
                    <select class="form-control form-control-sm" name="jml_kaki">
                        <option value="1" <?php if ($data_cek['jml_kaki'] === '1') echo 'selected'; ?>>1</option>
                        <option value="3" <?php if ($data_cek['jml_kaki'] === '3') echo 'selected'; ?>>3</option>
                        <option value="4" <?php if ($data_cek['jml_kaki'] === '4') echo 'selected'; ?>>4</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>ID Tower</label>
                    <input type="text" class="form-control form-control-sm" name="id_tower" value="<?php echo "TWR-0" . $data_cek['id_tower']; ?>" readonly>
                </div>

				<div class="mb-3">
        <label for="file">Gambar</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text p-0">
                <img src="https://img.icons8.com/ios-filled/50/image.png" alt="icon" width="35" height="35" style="padding: 5px;">
            </span>
            <input type="text" class="form-control" id="filename" placeholder="Pilih gambar..." readonly>
            <label class="btn btn-secondary input-group-text mb-0">
                Upload 
                <input type="file" name="file" id="file" hidden onchange="updateFilename(this)">
            </label>
        </div>
        <small class="text-muted">Format yang diterima: jpg, jpeg, atau png</small>
    </div>

		<!-- Image Preview -->
		<div class="mt-3">
			<label for="preview-image">Preview</label>
			<div style="width: 200px; height: auto;">
            <img id="preview-image" src="<?php echo !empty($data_cek['gambar']) ? '../uploads/' . $data_cek['gambar'] : '#'; ?>" alt="Preview" class="img-fluid rounded" style="<?php echo !empty($data_cek['gambar']) ? '' : 'display:none;'; ?> max-height: 150px;">

			</div>
		</div>

		<!-- Titik Koordinat -->
		<div class="mt-4">
			<label>Titik Koordinat</label>
			<div class="row">
				<div class="col-md-6 mb-2">
					<input type="text" class="form-control form-control-sm" id="lintang" placeholder="Lintang" name="lintang" value="<?php echo htmlspecialchars($data_cek['lintang']); ?>">
				</div>
				<div class="col-md-6 mb-2">
					<input type="text" class="form-control form-control-sm" id="bujur" placeholder="Bujur" name="bujur" value="<?php echo htmlspecialchars($data_cek['bujur']); ?>">
				</div>
			</div>
		</div>

		<!-- Leaflet Map Preview -->
		<div class="mt-3">
			<label for="map">Preview</label>
			<div id="map" style="height: 250px; border-radius: 8px;"></div>
		</div>
        <br>
                <div class="d-flex gap-2 flex-row-reverse">
                    <input type="submit" name="Ubah" value="Ubah" class="btn btn-primary btn-sm">
                    <a href="?page=olahdata" class="btn btn-danger btn-sm">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>


<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    // Image preview
    document.getElementById("file").addEventListener("change", function (event) {
        const file = event.target.files[0];
        const preview = document.getElementById("preview-image");
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = "block";
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = "none";
        }
    });

    // Leaflet Map
    var latInput = document.getElementById("lintang");
    var lngInput = document.getElementById("bujur");

    var initialLat = parseFloat(latInput.value);
    var initialLng = parseFloat(lngInput.value);

    if (isNaN(initialLat) || isNaN(initialLng)) {
        initialLat = -6.200000;
        initialLng = 106.816666;
    }

    var map = L.map('map').setView([initialLat, initialLng], 13);
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

    var marker = L.marker([initialLat, initialLng], {
        draggable: true
    }).addTo(map);

    // Sync marker to input fields when dragged
    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        latInput.value = position.lat.toFixed(6);
        lngInput.value = position.lng.toFixed(6);
    });

    // Sync input fields to marker and map
    function updateMap() {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);
        if (!isNaN(lat) && !isNaN(lng)) {
            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 15);
        }
    }

    latInput.addEventListener("input", updateMap);
    lngInput.addEventListener("input", updateMap);
</script>




<?php

if (isset($_POST['Ubah'])) {
    
    $id_tower = str_replace('TWR-0', '', $_POST['id_tower']);

    
    $provider = $_POST['provider']; 
    $status = $_POST['status'];
    $lokasi = $_POST['lokasi']; 
    $jml_kaki = $_POST['jml_kaki'];
    $lintang = $_POST['lintang'] ?? null;
    $bujur = $_POST['bujur'] ?? null;

    
    $filename = $data_cek['gambar'] ?? ''; 
    if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "../uploads/";
        $fileNameBefore = basename($_FILES['file']['name']);
        $targetFilePath = $targetDir . $fileNameBefore;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        $allowedTypes = ['jpg', 'jpeg', 'png'];
        if (in_array(strtolower($fileType), $allowedTypes)) {
            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFilePath)) {
                $filename = $fileNameBefore;
            } else {
                echo "<script>
                    Swal.fire({
                        title: 'Upload Gagal!',
                        text: 'Terjadi kesalahan saat mengupload gambar.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                </script>";
            }
        } else {
            echo "<script>
                Swal.fire({
                    title: 'Upload Gagal!',
                    text: 'Format gambar harus jpg, jpeg, atau png.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
    }

    
    $sql_ubah = "UPDATE tower SET
        provider = '".mysqli_real_escape_string($conn, $provider)."',
        status = '".mysqli_real_escape_string($conn, $status)."',
        lokasi = '".mysqli_real_escape_string($conn, $lokasi)."',
        jml_kaki = '".mysqli_real_escape_string($conn, $jml_kaki)."',
        gambar = '".mysqli_real_escape_string($conn, $filename)."',
        lintang = '".mysqli_real_escape_string($conn, $lintang)."',
        bujur = '".mysqli_real_escape_string($conn, $bujur)."'
        WHERE id_tower = '".mysqli_real_escape_string($conn, $id_tower)."'";

    $query_ubah = mysqli_query($conn, $sql_ubah);

    if ($query_ubah) {
        echo "<script>
        Swal.fire({
            title: 'Ubah Data Berhasil',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'dashboard_admin.php?page=olahdata';
            }
        });
        </script>";
    } else {
        echo "<script>
        Swal.fire({
            title: 'Ubah Data Gagal',
            icon: 'error',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'dashboard_admin.php?page=olahdata';
            }
        });
        </script>";
    }
}

