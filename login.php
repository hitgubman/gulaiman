<?php

require_once('header.php');

?>

<style>
    /* Responsive adjustments */
    @media (max-width: 576px) {
        .login-container {
            padding: 1.5rem;
        }
        
        .login-header-content h2 {
            font-size: 1.3rem;
        }
        
        .login-header-content h3 {
            font-size: 1rem;
        }
        
        .login-header-content h4 {
            font-size: 1.3rem;
        }
        
        .login-image-box {
            width: 70px;
            height: 70px;
        }
    }
</style>

<div class="login-page-body">
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
