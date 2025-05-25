<?php
include "../koneksi.php";

?>
<!-- Main content -->
<h2 class="text-start">
		 Olah Data Tower
	</h2>
<main class="py-1">
<div class="container-fluid mt-0 ">
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Data Tower</h5>
      <div class="d-flex align-items-center gap-2">
        <input type="search" id="tableSearch" class="form-control form-control-sm" placeholder="Cari data tower..." style="min-width: 200px;">
        <a href="?page=add_tower" class="btn btn-primary btn-sm d-flex align-items-center gap-1" style="white-space: nowrap;">
          <i class="bi bi-plus-circle"></i> Tambah Data
        </a>
      </div>
    </div> 
    <div class="card-body">
    <div class="table-responsive">
        <table id="towerTable" class="table table-bordered table-striped table-hover">
          <thead class="table-dark">
            <tr>
              <th>No</th>
              <th>Gambar</th>
              <th>ID Tower</th>
              <th>Status</th>
              <th>Jumlah Kaki</th>
              <th>Provider</th>
              <th>Lokasi</th>
              <th>Kelola</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $no = 1;
              $sql = $conn->query("SELECT * FROM tower");
              while ($data = $sql->fetch_assoc()) {
            ?>
              <tr>
                <td><?= $no++; ?></td>
                <td><img src="../uploads/<?= $data['gambar']; ?>" alt="Gambar Tower" class="tower-image preview-image" style="cursor:pointer;" data-towerid="<?= 'TWR-0' . $data['id_tower']; ?>"></td>
                <td><?= "TWR-0" . $data['id_tower']; ?></td>
                <td><?= $data['status']; ?></td>
                <td><?= $data['jml_kaki']; ?></td>
                <td><?= $data['provider']; ?></td>
                <td><?= $data['lokasi']; ?></td>
                <td>
                  <a href="?page=edit_tower&kode=<?= $data['id_tower']; ?>" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a href="javascript:void(0);" 
                    onclick="confirmDelete('<?= $data['id_tower']; ?>')" 
                    class="btn btn-danger btn-sm">
                    <i class="bi bi-trash"></i>
                  </a>
                  </a>
                </td>
              </tr>
            <?php } ?>
          </tbody>
</table>
      </div>
    </div>
  </div>
</div>
</section>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="../stail.css" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Yakin Hapus Data Ini?',
        text: "Data yang dihapus tidak bisa dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?page=del_tower&kode=' + id;
        }
    })
}

    $(document).ready(function() {
        var table = $('#towerTable').DataTable({
            "pageLength": 5,
            "lengthChange": false,
            "searching": true,
            "error": false,
            "language": {
                "paginate": {
                    "previous": "&laquo;",
                    "next": "&raquo;"
                },
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
                "infoFiltered": "(disaring dari _MAX_ total data)",
                "zeroRecords": "Tidak ada data yang cocok",
                "search": "Cari:",
                "emptyTable": "Tidak ada data tersedia"
            },
            "dom": 'lrtip'
        });

        // Custom search input
        $('#tableSearch').on('keyup', function() {
            table.search(this.value).draw();
        });
    });
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
    var myModalEl = document.getElementById('imagePreviewModal');
    var myModal = new bootstrap.Modal(myModalEl);

    $('#towerTable').on('click', '.preview-image', function() {
        var src = $(this).attr('src');
        var towerId = $(this).data('towerid');
        $('#modalImage').attr('src', src);
        $('#imagePreviewModalLabel').text(towerId);
        myModal.show();
    });

    myModalEl.addEventListener('hidden.bs.modal', function () {
        // Dispose modal to remove backdrop and focus trap
        myModal.dispose();
    });
});
</script>
