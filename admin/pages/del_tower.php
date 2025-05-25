<?php
include "../koneksi.php";

if(isset($_GET['kode'])){
    
    $sql_hapus = "DELETE FROM tower WHERE id_tower='".$_GET['kode']."'";
    $query_hapus = mysqli_query($conn, $sql_hapus);

    
    if ($query_hapus) {
        echo "<script>
        Swal.fire({
            title: 'Hapus Data Berhasil',
            text: 'Data telah berhasil dihapus.',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'dashboard_admin.php?page=olahdata';  // Redirect after confirmation
            }
        })</script>";
    } else {
        echo "<script>
        Swal.fire({
            title: 'Hapus Data Gagal',
            text: 'Terjadi kesalahan saat menghapus data.',
            icon: 'error',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'dashboard_admin.php?page=olahdata';  // Redirect after failure confirmation
            }
        })</script>";
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.30/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.30/dist/sweetalert2.min.js"></script>