<?php
if (isset($_GET['username'])) {
    $username = $_GET['username'];

    $conn = new mysqli('localhost', 'root', '', 'bio_links');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $bio = $user['bio'];
        $profile_pic = $user['profile_pic'];

        echo "<h2>{$username}</h2>";
        echo "<img src='uploads/{$profile_pic}' alt='Profile Pic'><br>";
        echo "<p>{$bio}</p>";

        $stmt = $conn->prepare("SELECT * FROM links WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            echo "<p><a href='{$row['url']}' target='_blank'>{$row['title']}</a></p>";
        }
    } else {
        echo "User not found.";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Username not provided.";
}
?>