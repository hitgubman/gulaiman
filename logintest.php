<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <?php include 'header.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="logintest.css" rel="stylesheet">
</head>


<style>
@media (max-width: 575.98px) {
  .mobile-header-spacer {
    display: block !important;
    height: 3rem !important;
    width: 100% !important;
  }
}
@media (min-width: 576px) {
  .mobile-header-spacer {
    display: none !important;
  }
}
</style>

<div class="mobile-header-spacer"></div>

<body class="vh-100">
    <div class="container-fluid h-100">
        <div class="row h-100 d-flex flex-wrap">

            <div class="col-md-7 col-12 d-flex align-items-center justify-content-center">
                <div class="col-md-3 col-12 d-flex align-items-center justify-content-center mx-auto login-container">
                <div class="container">
            <div class="login-container">
                <div class="login-header-content">
                    <div class="login-logo">
                        <h2 class="text-white">DINAS KOMUNIKASI DAN INFORMATIKA</h2>
                        <h3 class="text-white">KABUPATEN MINAHASA</h3>
                    </div>
                    

                    <div class="login-image-container">
                        <div class="login-image-box">
                            <img src="kominfo.png" alt="Logo Kominfo">
                        </div>
                        <div class="login-image-box">
                            <img src="minahasa.png" alt="Logo Minahasa">
                        </div>
                    </div>
                </div>
                
                <form action="authenticate.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label text-white">Username</label>
                        <input type="text" class="form-control login-form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label text-white">Password</label>
                        <input type="password" class="form-control login-form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-login-custom">Login</button>
                </form>
            </div>
        </div>
                </div>
            </div>

            <div class="col-md-5 col-12 d-flex align-items-center justify-content-center">
                <div class="col-md-3 col-12 d-flex align-items-center justify-content-center mx-auto kominfo-container">
                    <img src="kominfocrop.png" alt="Image">
                </div>
            </div>

        </div>
    </div>
</body>
</html>

<?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: 'Username atau password yang dimasukkan itu salah.',
            confirmButtonColor: '#3085d6    ',
            confirmButtonText: 'OK'
        });
    });
</script>
<?php endif; ?>
