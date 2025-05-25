<?
$no = 1;

$search = ""; // Default search value
if (isset($_GET['search'])) {
    $search = $_GET['search']; // Get the search input
}

// Search SQL query
$sql = "SELECT * FROM tower WHERE 
       LOWER(id_tower) LIKE LOWER('%$search%') OR 
       LOWER(status) LIKE LOWER('%$search%') OR 
       LOWER(provider) LIKE LOWER('%$search%') OR 
       LOWER(lokasi) LIKE LOWER('%$search%')";

$result = $conn->query($sql);
?>