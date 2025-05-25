<?php
ob_start();
require_once '../koneksi.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../utamas.php");
    exit();
}

$error = '';
$success = false;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: manage_admins.php");
    exit();
}


$stmt = $conn->prepare("SELECT username, role, profile_image FROM akun WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($username, $role, $profile_image);
if (!$stmt->fetch()) {
    $stmt->close();
    header("Location: manage_admins.php");
    exit();
}
$stmt->close();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $role_new = $_POST['role'] === 'superadmin' ? 'superadmin' : 'admin';
        $username_new = trim($_POST['username']);
        $password_new = $_POST['password'];

        
        $new_profile_image = $profile_image; 
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_image']['tmp_name'];
            $fileName = $_FILES['profile_image']['name'];
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
            $new_profile_image = $newFileName;
        } else {
            $error = 'There was an error moving the uploaded file.';
        }
    } else {
        $error = 'Upload failed. Allowed file types: jpg, jpeg, png.';
    }
}

if (!$error) {
    
    if ($new_profile_image !== $profile_image && !empty($profile_image)) {
        $oldImagePath = '../uploads/profile_images/' . $profile_image;
        if (file_exists($oldImagePath)) {
            unlink($oldImagePath);
        }
    }

    if (!empty($password_new)) {
        $password_hash = password_hash($password_new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE akun SET username = ?, password = ?, role = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $username_new, $password_hash, $role_new, $new_profile_image, $id);
    } else {
        $stmt = $conn->prepare("UPDATE akun SET username = ?, role = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username_new, $role_new, $new_profile_image, $id);
    }
    if ($stmt->execute()) {
        $success = true;
        $profile_image = $new_profile_image; 
        $username = $username_new; 
    } else {
        $error = 'Failed to update admin.';
    }
    $stmt->close();
}
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<main>
    <div class="container-fluid">
        <h4 class="mb-4 font-weight-bold">Edit Admin: <?= htmlspecialchars($username) ?></h4>
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
                        text: 'Admin updated successfully!',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = '?page=manage_admins';
                    });
                </script>
            <?php endif; ?>
<form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="username" class="form-label">Username *</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required />
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password (Kosongkan jika tidak ingin mengubah)</label>
                    <input type="password" id="password" name="password" class="form-control" />
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role *</label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="superadmin" <?= $role === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="profile_image" class="form-label">Profile Image (jpg, png)</label>
                    <input type="file" id="profile_image" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png" />
            <?php if (!empty($profile_image)): ?>
                <img id="preview" src="../uploads/profile_images/<?= htmlspecialchars($profile_image) ?>" alt="Image Preview" style="max-width: 150px; margin-top: 10px; border-radius: 5px;" />
            <?php else: ?>
                <img id="preview" src="#" alt="Image Preview" style="display:none; max-width: 150px; margin-top: 10px; border-radius: 5px;" />
            <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="?page=manage_admins" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

</body>
</html>
