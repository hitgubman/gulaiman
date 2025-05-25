<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Updated custom CSS for logout button animation */
        .navbar-nav .nav-item .btn.btn-primary.ms-5 {
            transition: transform 0.3s ease !important;
            cursor: pointer !important;
        }
        .navbar-nav .nav-item .btn.btn-primary.ms-5:hover {
            transform: scale(1.1) !important;
            box-shadow: 0 6px 15px rgba(0,0,0,0.3) !important;
            background-color: rgb(255, 0, 119) !important;
            border-color: #003366 !important;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <!-- Logo and Text -->
    <a class="navbar-brand d-flex align-items-center" href="utamas.php">
      <img src="kominfo.png" alt="Logo" width="52" height="55" class="me-2">
      <span class="d-none d-md-inline">DINAS KOMUNIKASI DAN INFORMATIKA KABUPATEN MINAHASA</span>
    </a>

    <!-- Navbar Toggler for Mobile -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navbar Links -->
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link ms-5" href="data_tower.php">Data Tower</a></li>
        <li class="nav-item"><a class="nav-link ms-5" href="submit_datatower.php">Submit Data</a></li>
        <li class="nav-item"><a class="nav-link ms-5" href="#">Kontak</a></li>
        <li class="nav-item"><a class="btn btn-primary ms-5" href="#">Log Out</a></li>
        <li class="nav-item"><a class="btn btn-primary ms-3" href="halaman_admin.php">Olah Data</a></li>
      </ul>
    </div>
  </div>
</nav>

</body>
</html>

