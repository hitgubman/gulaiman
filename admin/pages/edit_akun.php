<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION["username"]) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("location: ../../utamas.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION["username"];
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email address.";
        header("Location: akun.php");
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

    $stmt = $conn->prepare("UPDATE akun SET email = ? WHERE username = ?");
    if (!$stmt) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: akun.php");
        exit;
    }
    $stmt->bind_param("ss", $email, $username);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update profile.";
    }
    $stmt->close();
    $conn->close();

    header("Location: akun.php");
    exit;
} else {
    header("Location: akun.php");
    exit;
}
