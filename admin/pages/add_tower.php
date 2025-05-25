<?php
include "../koneksi.php";

$sql_cek = "SELECT id_tower FROM tower ORDER BY id_tower DESC LIMIT 1";
$query_cek = mysqli_query($conn, $sql_cek);
$data_cek = mysqli_fetch_array($query_cek,MYSQLI_BOTH);
$data_cek["id_tower"] = $data_cek["id_tower"] + 1;

?>

<main>
    <div class="container-fluid">
    <h4 class="mb-4 font-weight-bold">Tambah Data Tower</h4>
        <div class="card p-4 shadow-sm">
            <form action="" method="post" enctype="multipart/form-data">
                
                <div class="mb-3">
                    <label>Nama Provider</label>
                    <input type="text" class="form-control form-control-sm" name="provider" value="" required>
                </div>

                <div class="mb-3">
                    <label>Status</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" value="Aktif" required>
                        <label class="form-check-label">Aktif</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" value="Non-aktif" required>
                        <label class="form-check-label">Non-aktif</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Lokasi</label>
                    <input type="text" class="form-control form-control-sm" name="lokasi" placeholder="Kelurahan, Kecamatan" required>
                </div>

                <div class="mb-3">
                    <label>Jumlah Kaki</label>
                    <select class="form-control form-control-sm" name="jml_kaki">
                        <option value="1">1</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>ID Tower</label>
                    <input type="text" class="form-control form-control-sm" name="id_tower" value="<?php echo "TWR-0" . $data_cek["id_tower"]?>"  readonly>
                </div>

				<div class="mb-3">
        <label for="file">Gambar</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text p-0">
                <img src="https://img.icons8.com/ios-filled/50/image.png" alt="icon" width="35" height="35" style="padding: 5px;">
            </span>
            <input type="text" class="form-control" id="filename" placeholder="Pilih gambar..." readonly>
            <label class="btn btn-secondary input-group-text">
                Upload
                <input type="file" name="file" id="file" hidden onchange="updateFilename(this)" required>
            </label>
        </div>
        <small class="text-muted">Format yang diterima: jpg, jpeg, atau png</small>
    </div>

		<!-- Image Preview -->
		<div class="mt-3">
			<label for="preview-image">Preview</label>
			<div style="width: 200px; height: auto;">
				<img id="preview-image" src="#" alt="Preview" class="img-fluid rounded" style="display: none; max-height: 150px;">
			</div>
		</div>

		<!-- Titik Koordinat -->
		<div class="mt-4">
			<label>Titik Koordinat</label>
			<div class="row">
				<div class="col-md-6 mb-2">
					<input type="text" type="number" step="any" class="form-control form-control-sm" id="latitude" placeholder="Lintang" name="lintang">
				</div>
				<div class="col-md-6 mb-2">
					<input type="text" type="number" step="any" class="form-control form-control-sm" id="longitude" placeholder="Bujur" name="bujur">
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
                    <input type="submit" name="Simpan" value="Tambah" class="btn btn-primary btn-sm">
                    <a href="?page=olahdata" class="btn btn-danger btn-sm">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>


<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



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
    var map = L.map('map').setView([-6.200000, 106.816666], 13);
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

    var marker = L.marker([-6.200000, 106.816666], {
        draggable: true
    }).addTo(map);

    // Sync marker to input fields when dragged
    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        document.getElementById("latitude").value = position.lat.toFixed(6);
        document.getElementById("longitude").value = position.lng.toFixed(6);
    });

    // Sync input fields to marker and map
    function updateMap() {
        const lat = parseFloat(document.getElementById("latitude").value);
        const lng = parseFloat(document.getElementById("longitude").value);
        if (!isNaN(lat) && !isNaN(lng)) {
            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 15);
        }
    }

    document.getElementById("latitude").addEventListener("input", updateMap);
    document.getElementById("longitude").addEventListener("input", updateMap);

</script>




<?php
if (isset($_POST['Simpan'])) {
    $id_tower   = $_POST['id_tower'];
    $status     = $_POST['status'];
    $provider   = $_POST['provider'];
    $lokasi     = $_POST['lokasi'];
    $jml_kaki   = $_POST['jml_kaki'];
    $lintang    = $_POST['lintang'];
    $bujur      = $_POST['bujur'];

    $nama_file  = $_FILES['file']['name'];
    $tmp_file   = $_FILES['file']['tmp_name'];
    $upload_dir = "../uploads/"; 

    if (move_uploaded_file($tmp_file, $upload_dir . $nama_file)) {
        $sql_simpan = "INSERT INTO tower (id_tower, gambar, status, provider, lokasi, jml_kaki, lintang, bujur)
                       VALUES ('$id_tower', '$nama_file', '$status', '$provider', '$lokasi', '$jml_kaki', '$lintang', '$bujur')";

        $query_simpan = mysqli_query($conn, $sql_simpan);

        if ($query_simpan) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Data tower berhasil ditambahkan.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href='?page=olahdata';
                });
            </script>";
        } else {
            echo "Gagal menyimpan data: " . mysqli_error($conn);
        }
    } else {
        echo "Gagal upload gambar.";
    }
}
?>

