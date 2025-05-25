<?php
ob_start();
require_once '../koneksi.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../utamas.php");
    exit();
}

$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'] === 'superadmin' ? 'superadmin' : 'admin';

    
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileSize = $_FILES['profile_image']['size'];
        $fileType = $_FILES['profile_image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = array('jpg', 'jpeg', 'png');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/profile_images/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $profile_image = $newFileName;
            } else {
                $error = 'There was an error moving the uploaded file.';
            }
        } else {
            $error = 'Upload failed. Allowed file types: jpg, jpeg, png.';
        }
    }

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } else if (!$error) {
        
        $stmt = $conn->prepare("SELECT id FROM akun WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Username already exists.';
        }
        $stmt->close();

            if (!$error) {
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                
                if ($profile_image) {
                    $stmt = $conn->prepare("INSERT INTO akun (username, password, role, profile_image) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $username, $hashed_password, $role, $profile_image);
                } else {
                    $stmt = $conn->prepare("INSERT INTO akun (username, password, role) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $hashed_password, $role);
                }
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $error = 'Failed to add new admin.';
                }
                $stmt->close();
            }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Add New Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<main>
    <div class="container-fluid">
        <h4 class="mb-4 font-weight-bold">Tambah Admin Baru</h4>
        <div class="card p-4 shadow-sm">
            <?php if ($error): ?>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: '<?= htmlspecialchars($error) ?>'
                    });
                </script>
            <?php endif; ?>
            <?php if ($success): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Admin added successfully!',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = '?page=manage_admins';
                    });
                </script>
            <?php endif; ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="username" class="form-label">Username *</label>
                    <input type="text" id="username" name="username" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password *</label>
                    <input type="password" id="password" name="password" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role *</label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="admin" selected>Admin</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="profile_image" class="form-label">Profile Image (jpg, png)</label>
                    <input type="file" id="profile_image" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png" />
                    <img id="preview" src="#" alt="Image Preview" style="display:none; max-width: 150px; margin-top: 10px; border-radius: 5px;" />
                </div>
                <button type="submit" class="btn btn-primary">Tambah Admin</button>
                <a href="?page=manage_admins" class="btn btn-danger ms-2">Batal</a>
            </form>
        </div>
    </div>
</main>
<script>
document.getElementById('profile_image').addEventListener('change', function(event) {
    const [file] = event.target.files;
    if (file) {
        const preview = document.getElementById('preview');
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
