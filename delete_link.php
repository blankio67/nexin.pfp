<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['link_id'])) {
    $link_id = $_POST['link_id'];
    $user_id = $_SESSION['user_id'];

    $conn = new mysqli('localhost', 'root', '', 'bio_links');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("DELETE FROM links WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $link_id, $user_id);
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'Error deleting link: ' . $stmt->error;
    }
    $stmt->close();
    $conn->close();
} else {
    echo 'Invalid request';
}
?>