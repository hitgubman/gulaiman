<?php

session_start();

/**
 * Redirect URLs with extra path info to clean URL
 */
$requestUri = $_SERVER['REQUEST_URI'];
$pattern = '/^(.*\.php)(\/.*)$/';
if (preg_match($pattern, $requestUri, $matches)) {
    $newUri = $matches[1];
    header("Location: $newUri", true, 301);
    exit();
}

/**
 * Check user authentication and authorization
 */
if (!isset($_SESSION["username"]) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'])) {
    header("location: ../utamas.php");
    exit;
}

$data_user = $_SESSION["username"];

/**
 * Database connection function
 */
function getDbConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli('localhost', 'root', '', 'your_database_name');
        if ($conn->connect_error) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Database connection failed',
                'text' => $conn->connect_error
            ];
            header("Location: dashboard_admin.php?page=akun");
            exit;
        }
    }
    return $conn;
}

/**
 * Handle profile image upload
 */
function handleProfileImageUpload() {
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/profile_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $tmpName = $_FILES['profile_image']['tmp_name'];
        $fileName = basename($_FILES['profile_image']['name']);
        $targetFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($tmpName, $targetFilePath)) {
            $profile_image = $fileName;
        } else {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Failed to upload profile image',
                'text' => ''
            ];
            header("Location: dashboard_admin.php?page=akun");
            exit;
        }
    } else {
        $profile_image = $_POST['existing_profile_image'] ?? null;
    }
    return $profile_image;
}

/**
 * Update user profile in database
 */
function updateUserProfile($id, $username, $password, $role, $profile_image) {
    $conn = getDbConnection();

    if ($password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE akun SET username = ?, password = ?, role = ?, profile_image = ? WHERE id = ?");
        if (!$stmt) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Database error',
                'text' => $conn->error
            ];
            header("Location: dashboard_admin.php?page=akun");
            exit;
        }
        $stmt->bind_param("ssssi", $username, $hashed_password, $role, $profile_image, $id);
    } else {
        $stmt = $conn->prepare("UPDATE akun SET username = ?, role = ?, profile_image = ? WHERE id = ?");
        if (!$stmt) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Database error',
                'text' => $conn->error
            ];
            header("Location: dashboard_admin.php?page=akun");
            exit;
        }
        $stmt->bind_param("sssi", $username, $role, $profile_image, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => 'Profile updated successfully',
            'text' => ''
        ];
    } else {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'Failed to update profile',
            'text' => ''
        ];
    }
    $stmt->close();
}

/**
 * Handle profile update POST request
 */
function handleEditAkunPost() {
    $id = $_POST['id'] ?? null;
    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;
    $role = $_POST['role'] ?? 'admin';

    if (!$id || !$username) {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'ID and Username are required.',
            'text' => ''
        ];
        header("Location: dashboard_admin.php?page=akun");
        exit;
    }

    $profile_image = handleProfileImageUpload();

    updateUserProfile($id, $username, $password, $role, $profile_image);

    header("Location: dashboard_admin.php?page=akun");
    exit;
}

/**
 * Change user password in database
 */
function changeUserPassword($username, $current_password, $new_password, $confirm_new_password) {
    if ($new_password !== $confirm_new_password) {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'New password and confirmation do not match.',
            'text' => ''
        ];
        header("Location: dashboard_admin.php?page=akun");
        exit;
    }

    $conn = getDbConnection();

    $stmt = $conn->prepare("SELECT password FROM akun WHERE username = ?");
    if (!$stmt) {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'Database error',
            'text' => $conn->error
        ];
        header("Location: dashboard_admin.php?page=akun");
        exit;
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($stored_password);
    if (!$stmt->fetch()) {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'User not found.',
            'text' => ''
        ];
        $stmt->close();
        header("Location: dashboard_admin.php?page=akun");
        exit;
    }
    $stmt->close();

    if (!password_verify($current_password, $stored_password)) {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'Current password is incorrect.',
            'text' => ''
        ];
        header("Location: dashboard_admin.php?page=akun");
        exit;
    }

    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE akun SET password = ? WHERE username = ?");
    if (!$stmt) {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'Database error',
            'text' => $conn->error
        ];
        header("Location: dashboard_admin.php?page=akun");
        exit;
    }
    $stmt->bind_param("ss", $hashed_new_password, $username);
    if ($stmt->execute()) {
        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => 'Password changed successfully.',
            'text' => ''
        ];
    } else {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'Failed to change password.',
            'text' => ''
        ];
    }
    $stmt->close();
}

/**
 * Handle change password POST request
 */
function handleChangePasswordPost() {
    $username = $_SESSION["username"];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    changeUserPassword($username, $current_password, $new_password, $confirm_new_password);

    header("Location: dashboard_admin.php?page=akun");
    exit;
}

/**
 * Main page routing and request handling
 */
$page = $_GET['page'] ?? 'home';

if ($page === 'manage_admins' && ($_SESSION['role'] ?? '') !== 'superadmin') {
    header("Location: dashboard_admin.php?page=akun");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($page === 'edit_akun') {
        handleEditAkunPost();
    } elseif ($page === 'change_password') {
        handleChangePasswordPost();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard | Pemetaan Tower</title>
  <link rel="icon" href="../kominfocrop.png  ">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="admin-dark-mode.css" />
  <link rel="stylesheet" href="dashboard_admin.css" />
</head>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.19.1"></script>


<body>

  <!-- Topbar -->
  <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-outline-light d-md-none mobile-toggle" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
        <i class="bi bi-list"></i>
      </button>
      <img src="../kominfo.png" alt="Logo" width="50">
      <span><strong>PEMETAAN TOWER SELULER KABUPATEN MINAHASA</strong></span>
    </div>
    <form action="logout.php" method="POST" class="topbar-logout-form d-flex align-items-center gap-2">
      <div class="form-check form-switch d-none d-md-inline-block me-2">
        <input class="form-check-input" type="checkbox" id="darkModeToggle" />
        <label class="form-check-label text-light" for="darkModeToggle">Dark Mode</label>
      </div>
      <button class="btn btn-primary btn-sm d-none d-md-inline-block" id="tombolLogOut">Log Out</button>
    </form>
  </div>

<!-- Desktop Sidebar -->
<div class="sidebar-desktop d-none d-md-block">
  <?php
    $username = $_SESSION['username'] ?? '';
    $profile_image = '../profile.png';
    if ($username) {
        require_once '../koneksi.php';
        $stmt = $conn->prepare("SELECT profile_image FROM akun WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($db_profile_image);
        if ($stmt->fetch() && $db_profile_image) {
            $profile_image = '../uploads/profile_images/' . htmlspecialchars($db_profile_image);
        }
        $stmt->close();
    }
  ?>
  <div class="text-center mb-3">
    <img src="<?= $profile_image ?>" width="50" class="rounded-circle mb-2 preview-image" alt="Profile" data-username="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
    <div><strong><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'User')) ?></strong></div>
  </div>
  <a href="?page=home"><i class="bi bi-house-door me-2"></i> Home</a>
  <a href="?page=olahdata"><i class="bi bi-bar-chart-line me-2"></i> Olah Data</a>
  <?php if (($_SESSION['role'] ?? '') === 'superadmin'): ?>
    <a href="?page=manage_admins"><i class="bi bi-people me-2"></i> Kelola Akun Admin</a>
  <?php else: ?>
    <a href="?page=akun"><i class="bi bi-person-circle me-2"></i> Akun Admin</a>
  <?php endif; ?>
  <a href="../utamas.php"><i class="bi bi-laptop-fill me-2"></i> Halaman Utama</a>
</div>

  <!-- Mobile Sidebar -->
  <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header bg-dark text-white">
      <h5 class="offcanvas-title"><?php echo htmlspecialchars(ucfirst($_SESSION['role'] ?? 'User')); ?></h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
      <div class="offcanvas-body bg-dark text-white">
        <div class="text-center mb-3">
          <img src="../profile.png" width="60" class="rounded-circle mb-2 preview-image" alt="Profile" data-username="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
        </div>
        <div class="form-check form-switch mb-3 w-100">
          <input class="form-check-input" type="checkbox" id="darkModeToggle" />
          <label class="form-check-label text-light" for="darkModeToggle">Dark Mode</label>
        </div>
        <a href="?page=home" class="d-block mb-2 text-white text-decoration-none"><i class="bi bi-house-door me-2"></i> Home</a>
        <a href="?page=olahdata" class="d-block mb-2 text-white text-decoration-none"><i class="bi bi-bar-chart-line me-2"></i> Olah Data</a>
        <?php if (($_SESSION['role'] ?? '') === 'superadmin'): ?>
          <a href="?page=manage_admins" class="d-block mb-2 text-white text-decoration-none"><i class="bi bi-people me-2"></i> Manage Admins</a>
        <?php else: ?>
          <a href="?page=akun" class="d-block mb-2 text-white text-decoration-none"><i class="bi bi-person-circle me-2"></i> Akun Admin</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-danger btn-sm mt-3 d-md-none"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
      </div>
  </div>

<div class="content">
  <?php
    $pagePath = "pages/$page.php";
    if (file_exists($pagePath)) {
      include $pagePath;
    } else {
      echo "<h4 class='text-danger'>Halaman tidak ditemukan.</h4>";
    }
  ?>
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


<!-- JS & Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  $(document).ready(function() {
    $('.preview-image').on('click', function() {
      var src = $(this).attr('src');
      var username = $(this).data('username');
      $('#modalImage').attr('src', src);
      $('#imagePreviewModalLabel').text('Preview Gambar Profil: ' + username);
      var myModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
      myModal.show();
    });
  });
</script>


<script>
// Target the form, not the button
document.querySelector('form').addEventListener('submit', function (e) {
  e.preventDefault(); // Stop the form from submitting immediately

  Swal.fire({
    title: 'Apakah anda ingin logout?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Logout',
    cancelButtonText: 'Tidak',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6'
  }).then((result) => {
    if (result.isConfirmed) {
      this.submit(); // Now submit the form if confirmed
    }
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
  document.addEventListener('DOMContentLoaded', function () {
    var myModalEl = document.getElementById('imagePreviewModal');
    var myModal = new bootstrap.Modal(myModalEl);

    document.querySelectorAll('.preview-image').forEach(function(element) {
      element.addEventListener('click', function() {
        var src = this.getAttribute('src');
        var username = this.getAttribute('data-username');
        document.getElementById('modalImage').setAttribute('src', src);
        document.getElementById('imagePreviewModalLabel').textContent = 'Preview Gambar Profil: ' + username;
        myModal.show();
      });
    });
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const toggleButton = document.getElementById('darkModeToggle');
    const body = document.body;

    if (!toggleButton) {
      console.error('Dark mode toggle button not found');
      return;
    }

    if (localStorage.getItem('darkMode') === 'enabled') {
      body.classList.add('dark-mode');
      toggleButton.checked = true;
    }

    toggleButton.addEventListener('change', () => {
      if (toggleButton.checked) {
        body.classList.add('dark-mode');
        localStorage.setItem('darkMode', 'enabled');
      } else {
        body.classList.remove('dark-mode');
        localStorage.setItem('darkMode', 'disabled');
      }
    });
  });
</script>
</body>
</html>
