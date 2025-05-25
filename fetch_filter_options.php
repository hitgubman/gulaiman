    <?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "tower_kominfo";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sqlProviders = "SELECT DISTINCT provider FROM tower";
    $resultProviders = $conn->query($sqlProviders);
    $providers = [];
    if ($resultProviders->num_rows > 0) {
        while ($row = $resultProviders->fetch_assoc()) {
            $providers[] = $row['provider'];
        }
    }

    $sqlAlamat = "SELECT DISTINCT lokasi FROM tower";
    $resultAlamat = $conn->query($sqlAlamat);
    $alamat = [];
    if ($resultAlamat->num_rows > 0) {
        while ($row = $resultAlamat->fetch_assoc()) {
            $alamat[] = $row['lokasi'];
        }
    }

    $sqlJumlahKaki = "SELECT DISTINCT jml_kaki FROM tower";
    $resultJumlahKaki = $conn->query($sqlJumlahKaki);
    $jumlahKaki = [];
    if ($resultJumlahKaki->num_rows > 0) {
        while ($row = $resultJumlahKaki->fetch_assoc()) {
            $jumlahKaki[] = $row['jml_kaki'];
        }
    }

    $conn->close();

    echo json_encode([
        'providers' => $providers,
        'alamat' => $alamat,
        'jumlahKaki' => $jumlahKaki
    ]);
    ?>