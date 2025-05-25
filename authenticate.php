<?php
session_start();

include "koneksi.php";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

error_log("Login attempt for username: " . $username);

$stmt = $conn->prepare("SELECT * FROM akun WHERE username = ?");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    header("Location: logintest.php?error=1");
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    error_log("User found: " . $user['username']);
    if (password_verify($password, $user['password'])) {
        error_log("Password verified for user: " . $username);
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'] ?? 'user';
        error_log("Session variables set for user: " . $username);
        header("Location: admin/dashboard_admin.php");
        exit();
    } else {
        error_log("Password verification failed for user: " . $username);
        header("Location: logintest.php?error=1");
        exit();
    }
} else {
    error_log("User not found or multiple users found for username: " . $username);
    header("Location: logintest.php?error=1");
    exit();
}

$conn->close();
?>
