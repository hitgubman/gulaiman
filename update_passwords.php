<?php
include "koneksi.php";

$sql = "SELECT id, password FROM akun";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $plain_password = $row['password'];

        // Check if password is already hashed (assuming hashed passwords start with $2y$)
        if (substr($plain_password, 0, 4) !== '$2y$') {
            $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE akun SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $id);
            if ($stmt->execute()) {
                echo "Password updated for user ID: $id\n";
            } else {
                echo "Failed to update password for user ID: $id\n";
            }
            $stmt->close();
        } else {
            echo "Password already hashed for user ID: $id\n";
        }
    }
} else {
    echo "No users found in akun table.\n";
}

$conn->close();
?>
