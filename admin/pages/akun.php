<?php

require_once '../koneksi.php';

$username = $_SESSION["username"];
$profile_image = "../profile.png"; 


$user_role = "";
$profile_image_db = "";
$user_id = null;

$stmt = $conn->prepare("SELECT id, role, profile_image FROM akun WHERE username = ?");
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id_db, $role_db, $profile_image_db);
    if ($stmt->fetch()) {
        $user_id = $id_db;
        $user_role = $role_db;
        if ($profile_image_db) {
            $profile_image = "../uploads/profile_images/" . htmlspecialchars($profile_image_db);
        }
    }
    $stmt->close();
}

?>

<div class="container mt-4">
    <h2>Informasi Akun Admin</h2>
    <div class="card p-4 mt-3">
        <div class="text-center mb-4">
            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Picture" class="rounded-circle preview-image" width="120" height="120" style="object-fit: cover;" data-username="<?php echo htmlspecialchars($username); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">ID:</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_id ?? ''); ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Username:</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Peran:</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_role ?? 'Administrator'); ?>" readonly>
        </div>
        <div class="mt-4">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit Profil</button>
            <button type="button" class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Ubah Kata Sandi</button>
        </div>
    </div>
</div>

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

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog">
<form id="editProfileForm" class="modal-content" method="POST" action="dashboard_admin.php?page=edit_akun" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title" id="editProfileModalLabel">Edit Profil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_id ?? ''); ?>">
          <div class="mb-3">
              <label for="username" class="form-label">Username</label>
              <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
          </div>
          <!-- Removed password field from edit profile modal -->
          <div class="mb-3">
              <label for="role" class="form-label">Role</label>
              <input type="text" class="form-control" id="role" name="role" value="<?php echo htmlspecialchars($user_role ?? 'admin'); ?>" readonly>
          </div>
          <div class="mb-3">
              <label for="profile_image" class="form-label">Profile Image</label>
              <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
<form id="changePasswordForm" class="modal-content" method="POST" action="dashboard_admin.php?page=change_password">
      <div class="modal-header">
        <h5 class="modal-title" id="changePasswordModalLabel">Ubah Kata Sandi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
              <label for="currentPassword" class="form-label">Kata Sandi Saat Ini</label>
              <input type="password" class="form-control" id="currentPassword" name="current_password" required>
          </div>
          <div class="mb-3">
              <label for="newPassword" class="form-label">Kata Sandi Baru</label>
              <input type="password" class="form-control" id="newPassword" name="new_password" required>
          </div>
          <div class="mb-3">
              <label for="confirmNewPassword" class="form-label">Konfirmasi Kata Sandi Baru</label>
              <input type="password" class="form-control" id="confirmNewPassword" name="confirm_new_password" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Ubah Kata Sandi</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Optional: Add client-side validation for change password form
document.getElementById('changePasswordForm').addEventListener('submit', function(event) {
    var newPassword = document.getElementById('newPassword').value;
    var confirmNewPassword = document.getElementById('confirmNewPassword').value;
    if (newPassword !== confirmNewPassword) {
        event.preventDefault();
        alert('New password and confirmation do not match.');
    }
});

// Show SweetAlert based on session swal data
<?php if (isset($_SESSION['swal'])): ?>
Swal.fire({
    icon: '<?php echo $_SESSION['swal']['icon']; ?>',
    title: '<?php echo $_SESSION['swal']['title']; ?>',
    text: '<?php echo $_SESSION['swal']['text']; ?>'
});
<?php unset($_SESSION['swal']); ?>
<?php endif; ?>

// Profile image preview modal script
$(document).ready(function() {
    var myModalEl = document.getElementById('imagePreviewModal');
    var myModal = new bootstrap.Modal(myModalEl);

    $('.preview-image').on('click', function() {
        var src = $(this).attr('src');
        var username = $(this).data('username');
        $('#modalImage').attr('src', src);
        $('#imagePreviewModalLabel').text('Preview Gambar Profil: ' + username);
        myModal.show();
    });

    myModalEl.addEventListener('hidden.bs.modal', function () {
        // Dispose modal to remove backdrop and focus trap
        myModal.dispose();
    });
});
</script>
