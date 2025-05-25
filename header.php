<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DINAS KOMUNIKASI DAN INFORMATIKA KABUPATEN MINAHASA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="stail.css" />
</head>
<body>
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid d-flex justify-content-between align-items-center">

    <a class="navbar-brand d-flex align-items-center" href="utamas.php">
      <img src="kominfo.png" alt="Logo" width="52" height="55" class="me-2">
      <span class="d-none d-md-inline">DINAS KOMUNIKASI DAN INFORMATIKA KABUPATEN MINAHASA</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="data_tower.php">Data Tower</a></li>
        <li class="nav-item"><a class="nav-link" href="submit_datatower.php">Submit Data</a></li>
        <li class="nav-item"><a class="nav-link" href="kontak.php">Kontak</a></li>
        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'superadmin'])): ?>
          <li class="nav-item"><a class="btn btn-primary ms-2" href="admin/dashboard_admin.php">Olah Data</a></li>
        <?php elseif (basename($_SERVER['SCRIPT_NAME']) !== 'logintest.php'): ?>
          <li class="nav-item"><a class="btn btn-primary ms-2" href="logintest.php">Login</a></li>
        <?php endif; ?>
        <li class="nav-item ms-3 d-flex align-items-center">
          <div class="form-check form-switch m-0">
            <input class="form-check-input" type="checkbox" id="darkModeToggle" />
            <label class="form-check-label text-light mb-0" for="darkModeToggle">Dark Mode</label>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    console.log('Dark mode script loaded');
    // Dark mode toggle script
    const toggleButton = document.getElementById('darkModeToggle');
    const body = document.body;

    if (!toggleButton) {
      console.error('Dark mode toggle button not found');
      return;
    }

    // Load saved preference
    if (localStorage.getItem('darkMode') === 'enabled') {
      body.classList.add('dark-mode');
      toggleButton.checked = true;
      console.log('Dark mode enabled from localStorage');
    }

    toggleButton.addEventListener('change', () => {
      if (toggleButton.checked) {
        body.classList.add('dark-mode');
        localStorage.setItem('darkMode', 'enabled');
        console.log('Dark mode enabled');
      } else {
        body.classList.remove('dark-mode');
        localStorage.setItem('darkMode', 'disabled');
        console.log('Dark mode disabled');
      }
    });
  });
</script>
