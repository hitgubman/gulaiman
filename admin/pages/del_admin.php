<?php
include "../koneksi.php";

if (isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("DELETE FROM akun WHERE id = ? AND role = 'admin'");
    $stmt->bind_param("i", $delete_id);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
        Swal.fire({
            title: 'Delete Successful',
            text: 'Admin user has been deleted.',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'dashboard_admin.php?page=manage_admins';
            }
        });
        </script>";
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
        Swal.fire({
            title: 'Delete Failed',
            text: 'An error occurred while deleting the admin user.',
            icon: 'error',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'manage_admins.php';
            }
        });
        </script>";
    }
} else {
    header("Location: manage_admins.php");
    exit();
}
