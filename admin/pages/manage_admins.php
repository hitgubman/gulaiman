<?php
require_once '../koneksi.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../utamas.php");
    exit();
}


if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM akun WHERE id = ? AND role = 'admin'");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_admins.php");
    exit();
}


$result = $conn->query("SELECT id, username, role, profile_image FROM akun WHERE role IN ('admin', 'superadmin')");

?>

<?php
require_once '../koneksi.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../utamas.php");
    exit();
}


if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM akun WHERE id = ? AND role = 'admin'");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard_admin.php?page=manage_admins");
    exit();
}

$result = $conn->query("SELECT id, username, role, profile_image FROM akun WHERE role IN ('admin', 'superadmin')");
?>

<head>
    
<style>
    a, button, .btn {
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
    }
    a.btn:hover, button.btn:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    a.btn-primary:hover, button.btn-primary:hover {
        background-color: #0056b3 !important;
        border-color: #004085 !important;
    }
    a.btn-warning:hover, button.btn-warning:hover {
        background-color: #e0a800 !important;
        border-color: #d39e00 !important;
    }
    a.btn-danger:hover, button.btn-danger:hover {
        background-color: #bd2130 !important;
        border-color: #b21f2d !important;
    }
</style>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
</head>
<main>
    <div class="container">
<section>
    <h2 class="text-start mb-3">Kelola Admin</h2>
</section>
        <div class="card p-4 shadow-sm">
<div class="d-flex justify-content-end mb-3">
    <a href="?page=add_admin" class="btn btn-primary d-flex align-items-center gap-1" style="white-space: nowrap;">
        <i class="bi bi-plus-circle"></i> Tambah Admin Baru
    </a>
</div>
            <div class="table-responsive">
<table id="manageAdminsTable" class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Profil</th>
                            <th>Peran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td>
                                <?php if (!empty($row['profile_image'])): ?>
                                    <img src="../uploads/profile_images/<?= htmlspecialchars($row['profile_image']) ?>" alt="Profile Image" width="40" height="40" class="rounded-circle preview-image" data-username="<?= htmlspecialchars($row['username']) ?>" style="cursor: pointer;">
                                <?php else: ?>
                                    <img src="../profile.png" alt="Default Profile" width="40" height="40" class="rounded-circle preview-image" data-username="<?= htmlspecialchars($row['username']) ?>" style="cursor: pointer;">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['role']) ?></td>
                            <td>
<a href="?page=edit_admin&id=<?= $row['id'] ?>" class="btn btn-warning btn-sm custom-btn-padding">
    <i class="bi bi-pencil"></i>
</a>
<?php if ($row['role'] !== 'superadmin'): ?>
<a href="#" class="btn btn-danger btn-sm delete-admin-btn custom-btn-padding" data-id="<?= $row['id'] ?>">
    <i class="bi bi-trash"></i>
</a>
<?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- jQuery and DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteButtons = document.querySelectorAll('.delete-admin-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const adminId = this.getAttribute('data-id');
            Swal.fire({
                title: 'Apakah Anda yakin ingin menghapus admin ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Tidak, batalkan',
            }).then((result) => {
                if (result.isConfirmed) {
window.location.href = 'dashboard_admin.php?page=del_admin&id=' + adminId;
                }
            });
        });
    });

    // Initialize DataTables
    $('#manageAdminsTable').DataTable({
        "pageLength": 5,
        "lengthChange": false,
        "searching": true,
        "language": {
            "paginate": {
                "previous": "&laquo;",
                "next": "&raquo;"
            },
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
            "infoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
            "infoFiltered": "(disaring dari _MAX_ total entri)",
            "zeroRecords": "Tidak ada data yang cocok",
            "search": "Cari:",
            "emptyTable": "Tidak ada data tersedia"
        },
        "dom": 'lrtip',
        "ordering": false
    });
});
</script>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark">
      <div class="modal-header border-0">
        <h5 class="modal-title text-white" id="imagePreviewModalLabel">Preview Gambar Profil</h5>
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

    // Use event delegation to handle dynamically created elements on DataTables pagination
    $('#manageAdminsTable tbody').on('click', '.preview-image', function() {
      var src = $(this).attr('src');
      var username = $(this).data('username');
      $('#modalImage').attr('src', src);
      $('#imagePreviewModalLabel').text('Preview Gambar Profil: ' + username);
      myModal.show();
    });

    myModalEl.addEventListener('hidden.bs.modal', function () {
      // Remove modal backdrop manually to fix dimmed screen issue
      var backdrops = document.getElementsByClassName('modal-backdrop');
      while(backdrops.length > 0){
          backdrops[0].parentNode.removeChild(backdrops[0]);
      }
      // Remove modal-open class from body
      document.body.classList.remove('modal-open');
      // Reset modal display style
      myModalEl.style.display = 'none';
    });
  });
</script>
