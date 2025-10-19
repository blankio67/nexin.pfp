<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    echo 'unauthorized';
    exit();
}

if (!isset($_POST['type'])) {
    echo 'error: type not specified';
    exit();
}

$user_id = $_SESSION['user_id'];
$type = $_POST['type'];

$conn = new mysqli('localhost', 'root', '', 'bio_links');
if ($conn->connect_error) {
    echo "error: connection failed";
    exit();
}

// Special case for profile picture - stored in users table
if ($type == 'profile_avatar') {
    $stmt = $conn->prepare("UPDATE users SET profile_pic = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error: database update failed';
    }
    $stmt->close();
    $conn->close();
    exit();
}

// For other assets stored in assets table
$stmt = $conn->prepare("DELETE FROM assets WHERE user_id = ? AND type = ?");
$stmt->bind_param("is", $user_id, $type);

if ($stmt->execute()) {
    echo 'success';
} else {
    echo 'error: database delete failed';
}

$stmt->close();
$conn->close();
?>