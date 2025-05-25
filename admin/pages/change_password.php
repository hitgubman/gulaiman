<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION["username"]) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("location: ../../utamas.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION["username"];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    if ($new_password !== $confirm_new_password) {
        $_SESSION['error'] = "New password and confirmation do not match.";
        header("Location: ?=akun.php");
        exit;
    }

    
    global $conn;
    if (!isset($conn)) {
        $conn = new mysqli('localhost', 'root', '', 'your_database_name'); 
        if ($conn->connect_error) {
            $_SESSION['error'] = "Database connection failed: " . $conn->connect_error;
            header("Location: akun.php");
            exit;
        }
    }

    
    $stmt = $conn->prepare("SELECT password FROM akun WHERE username = ?");
    if (!$stmt) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: akun.php");
        exit;
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($stored_password);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "User not found.";
        $stmt->close();
        $conn->close();
        header("Location: akun.php");
        exit;
    }
    $stmt->close();

    if ($current_password !== $stored_password) {
        $_SESSION['error'] = "Current password is incorrect.";
        $conn->close();
        header("Location: akun.php");
        exit;
    }

    
    $stmt = $conn->prepare("UPDATE akun SET password = ? WHERE username = ?");
    if (!$stmt) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        $conn->close();
        header("Location: akun.php");
        exit;
    }
    $stmt->bind_param("ss", $new_password, $username);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Password changed successfully.";
    } else {
        $_SESSION['error'] = "Failed to change password.";
    }
    $stmt->close();
    $conn->close();

    header("Location: akun.php");
    exit;
} else {
    header("Location: akun.php");
    exit;
}
