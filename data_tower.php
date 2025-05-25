<?php 
include 'koneksi.php'; 
$no = 1;

$search = ""; 
if (isset($_GET['search'])) {
    $search = $_GET['search']; 
}

$sql = "SELECT * FROM tower WHERE 
       LOWER(id_tower) LIKE LOWER('%$search%') OR 
       LOWER(status) LIKE LOWER('%$search%') OR 
       LOWER(provider) LIKE LOWER('%$search%') OR 
       LOWER(lokasi) LIKE LOWER('%$search%')";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Tower BTS</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stail.css">
</head>
<body>
<div class="container-wrapper">
  <?php include 'header.php'; ?>
  <br>
  <div class="container">
      <h2>DATA TOWER BTS</h2>
      <form method="GET" class="mb-3 d-flex align-items-center gap-2">
    <input type="text" name="search" placeholder="Cari..." class="form-control" style="width: 200px;" 
    value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary">Cari</button>
    <a href="data_tower.php" class="btn btn-secondary">Reset</a>
</form>


      <div class="table-container">
          <table id="TabelTower">
              <thead>
                  <tr>
                      <th>No</th>
                      <th>Gambar</th>
                      <th>ID Tower</th>
                      <th>Status</th>
                      <th>Provider</th>
                      <th>Lokasi</th>
                      <th>Aksi</th> 
                  </tr>
              </thead>
              <tbody>
              <?php
              if ($result -> num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                      echo "<tr>";
                      echo "<td></td>";
                      echo "<td><img src='uploads/" . $row['gambar'] . "' width='100' class='preview-image' style='cursor:pointer;' data-towerid='TWR-0" . $row['id_tower'] . "'></td>";
                      echo "<td> TWR-0" . $row['id_tower'] . "</td>";
                      echo "<td>" . $row['status'] . "</td>";
                      echo "<td>" . $row['provider'] . "</td>";
                      echo "<td>" . $row['lokasi'] . "</td>";
                      echo "<td data-label='Aksi'><a href='detail_tower.php?id=" . $row['id_tower'] . "' class='btn btn-outline-success'>Detail</a></td>";
                      echo "</tr>";
                  }
              } else {
                  echo "<tr><td colspan='7'>No data available</td></tr>";
              }

              $conn->close();
              ?>
              </tbody>
          </table>
      </div>
      <br>
  </div>
</div>

<footer class="text-center py-3 text-dark opacity-25">
  <span>Copyright © Dinas Komunikasi dan Informatika Kabupaten Minahasa</span>
</footer>

<div class="footer">
    <div class="footer-content">
        <div class="footer-text">
            <p>PEMETAAN TOWER SELULER<br>DISKOMINFO MINAHASA</p>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
        <div class="footer-logo">
            <img src="minahasa.png" alt="Logo Minahasa"> 
        </div>
    </div>
</div>


<script>
    $(document).ready( function () {
        var table = $('#TabelTower').DataTable({
            "pageLength": 5,
            "searching": false,
            "error": false,
            "columnDefs": [
                {
                    "searchable": false,
                    "orderable": false,
                    "targets": 0
                },
                {
                    "orderable": false,
                    "targets": [1, 6]
                }
            ],
            "order": [[ 2, 'asc' ]]
        });

        table.on( 'order.dt search.dt', function () {
            table.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
                cell.innerHTML = i+1;
            });
        }).draw();
    });
    $.fn.dataTable.ext.errMode = 'none';

</script>

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
$(document).ready(function() {
    $('#TabelTower').on('click', '.preview-image', function() {
        var src = $(this).attr('src');
        var towerId = $(this).data('towerid');
        $('#modalImage').attr('src', src);
        $('#imagePreviewModalLabel').text(towerId);
        var myModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
        myModal.show();
    });
});
</script>

</body>
</html>
