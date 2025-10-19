<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = new mysqli('localhost', 'root', '', 'bio_links');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['username']);
        $new_bio = trim($_POST['bio']);
        
        if (!empty($new_username)) {
            // Check if username is already taken by another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $new_username, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['error'] = 'Username is already taken. Please choose another.';
            } else {
                // Update username and bio
                $stmt = $conn->prepare("UPDATE users SET username = ?, bio = ? WHERE id = ?");
                $stmt->bind_param("ssi", $new_username, $new_bio, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Profile updated successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to update profile. Please try again.';
                }
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = 'Username cannot be empty.';
        }
    }

    // Handle new link submission
    if (isset($_POST['add_link'])) {
        $title = trim($_POST['link_title']);
        $url = trim($_POST['link_url']);
        
        if (!empty($title) && !empty($url)) {
            $stmt = $conn->prepare("INSERT INTO links (user_id, title, url) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $title, $url);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Link added successfully!';
            } else {
                $_SESSION['error'] = 'Failed to add link. Please try again.';
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = 'Both title and URL are required.';
        }
    }

    // Handle background upload
    if (isset($_FILES['background']) && $_FILES['background']['error'] == 0) {
        $background = $_FILES['background']['name'];
        $target_background = "uploads/" . basename($background);
        move_uploaded_file($_FILES['background']['tmp_name'], $target_background);

        $stmt = $conn->prepare("INSERT INTO assets (user_id, type, path) VALUES (?, 'background', ?) ON DUPLICATE KEY UPDATE path = VALUES(path)");
        $stmt->bind_param("is", $user_id, $background);
        $stmt->execute();
        $stmt->close();
    }

    // Handle profile avatar upload
    if (isset($_FILES['profile_avatar']) && $_FILES['profile_avatar']['error'] == 0) {
        $profile_avatar = $_FILES['profile_avatar']['name'];
        $target_avatar = "uploads/" . basename($profile_avatar);
        move_uploaded_file($_FILES['profile_avatar']['tmp_name'], $target_avatar);

        // Update the users table instead of assets table
        $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
        $stmt->bind_param("si", $profile_avatar, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Handle custom cursor upload
    if (isset($_FILES['custom_cursor']) && $_FILES['custom_cursor']['error'] == 0) {
        $custom_cursor = $_FILES['custom_cursor']['name'];
        $target_cursor = "uploads/" . basename($custom_cursor);
        move_uploaded_file($_FILES['custom_cursor']['tmp_name'], $target_cursor);

        $stmt = $conn->prepare("INSERT INTO assets (user_id, type, path) VALUES (?, 'custom_cursor', ?) ON DUPLICATE KEY UPDATE path = VALUES(path)");
        $stmt->bind_param("is", $user_id, $custom_cursor);
        $stmt->execute();
        $stmt->close();
    }

    // Handle audio URL
    if (isset($_POST['audio_url']) && !empty($_POST['audio_url'])) {
        $audio_url = $_POST['audio_url'];

        $stmt = $conn->prepare("INSERT INTO assets (user_id, type, path) VALUES (?, 'audio', ?) ON DUPLICATE KEY UPDATE path = VALUES(path)");
        $stmt->bind_param("is", $user_id, $audio_url);
        $stmt->execute();
        $stmt->close();
    }

    $conn->close();
    
    // Redirect to prevent form resubmission on refresh
    header("Location: dashboard.php");
    exit();
}

// Get success/error messages from session
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);

$conn = new mysqli('localhost', 'root', '', 'bio_links');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user info
$stmt = $conn->prepare("SELECT username, bio FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'];
$bio = $user['bio'];
$stmt->close();

// Get assets
$stmt = $conn->prepare("SELECT * FROM assets WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$assets_array = [];
while ($row = $result->fetch_assoc()) {
    $assets_array[$row['type']] = $row;
}
$stmt->close();

// Get links
$stmt = $conn->prepare("SELECT * FROM links WHERE user_id = ? ORDER BY id ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$links = [];
while ($row = $result->fetch_assoc()) {
    $links[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Regret Bio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #000000 100%);
            color: #fff;
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff 0%, #666666 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav {
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .nav a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav a:hover {
            color: #fff;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #666666 100%);
            color: #000;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.3);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 32px;
        }

        .dashboard-header {
            margin-bottom: 40px;
        }

        .dashboard-header h1 {
            font-size: 36px;
            margin-bottom: 8px;
        }

        .dashboard-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 16px;
        }

        .view-profile-btn {
            display: inline-block;
            margin-top: 16px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .view-profile-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: rgba(255, 68, 68, 0.2);
            color: #ff4444;
            border: 1px solid rgba(255, 68, 68, 0.3);
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }

        .card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 28px;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            min-height: 320px;
        }

        .card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #ffffff 0%, #666666 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #000;
        }

        .card h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .profile-update-card {
            grid-column: 1 / -1;
        }

        .upload-area {
            border: 2px dashed rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 48px 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            background: rgba(255, 255, 255, 0.02);
        }

        .upload-area:hover {
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.05);
        }

        .upload-area input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            cursor: pointer;
        }

        .upload-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 16px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: rgba(255, 255, 255, 0.4);
        }

        .upload-text {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            font-weight: 500;
        }

        .preview-container {
            position: relative;
            margin-top: 16px;
        }

        .preview-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
        }

        .preview-audio {
            width: 100%;
            margin-top: 16px;
        }

        .delete-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(255, 0, 0, 0.8);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .delete-btn:hover {
            background: rgba(255, 0, 0, 1);
            transform: scale(1.1);
        }

        .input-group {
            margin-top: 16px;
        }

        .input-group input[type="url"],
        .input-group input[type="text"],
        .input-group textarea {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            margin-bottom: 12px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .input-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .input-group input[type="url"]:focus,
        .input-group input[type="text"]:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.08);
        }

        .input-group input::placeholder,
        .input-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .submit-btn {
            margin-top: 4px;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #ffffff 0%, #666666 100%);
            border: none;
            border-radius: 8px;
            color: #000;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.3);
        }

        .section-title {
            font-size: 24px;
            margin-bottom: 24px;
            font-weight: 700;
        }

        .links-section {
            margin-top: 40px;
        }

        .add-link-card {
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .add-link-card h3 {
            font-size: 18px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .add-link-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #ffffff 0%, #666666 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #000;
        }

        .link-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }

        .link-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .link-info {
            flex: 1;
        }

        .link-title {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .link-url {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }

        .link-actions {
            display: flex;
            gap: 8px;
        }

        .icon-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 6px;
            width: 36px;
            height: 36px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            color: #fff;
        }

        .icon-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .no-links {
            text-align: center;
            padding: 40px;
            color: rgba(255, 255, 255, 0.4);
        }

        .profile-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        @media (max-width: 768px) {
            .header-content {
                padding: 0 16px;
            }

            .container {
                padding: 24px 16px;
            }

            .dashboard-header h1 {
                font-size: 28px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .card {
                min-height: auto;
            }

            .profile-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">Regret Bio</div>
            <nav class="nav">
                <a href="<?php echo urlencode($username); ?>">Your Page</a>
                <a href="logout.php" class="btn-primary">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-header">
            <h1>Welcome back, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Customize your bio link page and manage your content</p>
        </div>

        <?php if ($error): ?>   
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <h2 class="section-title">Profile Settings</h2>
        
        <div class="grid">
            <!-- Profile Update Card -->
            <div class="card profile-update-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <h3>Update Profile Information</h3>
                </div>
                <div class="card-body">
                    <form method="post" id="profile-update-form">
                        <div class="profile-form-grid">
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: rgba(255, 255, 255, 0.7); font-size: 14px; font-weight: 500;">Username</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter your username" required style="width: 100%; padding: 14px 16px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 8px; color: #fff; font-size: 14px; margin-bottom: 0;">
                            </div>
                            <div class="full-width">
                                <label style="display: block; margin-bottom: 8px; color: rgba(255, 255, 255, 0.7); font-size: 14px; font-weight: 500;">Bio</label>
                                <textarea name="bio" placeholder="Tell people about yourself..." style="width: 100%; min-height: 100px; padding: 14px 16px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 8px; color: #fff; font-size: 14px; resize: vertical; font-family: inherit; margin-bottom: 0;"><?php echo htmlspecialchars($bio); ?></textarea>
                            </div>
                            <div class="full-width">
                                <button type="submit" name="update_profile" class="submit-btn" style="margin-top: 16px;">Update Profile</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <h2 class="section-title">Assets & Customization</h2>
        
        <div class="grid">
            <!-- Background Upload -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-image"></i>
                    </div>
                    <h3>Background Image</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($assets_array['background'])): ?>
                        <div class="preview-container">
                            <img src="<?php echo htmlspecialchars($assets_array['background']['path']); ?>" alt="Background" class="preview-image">
                            <button class="delete-btn" onclick="deleteAsset('background')">×</button>
                        </div>
                    <?php else: ?>
                        <form method="post" enctype="multipart/form-data" id="bg-form">
                            <label for="background" class="upload-area">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="upload-text">Click to upload background</div>
                                <input type="file" name="background" id="background" accept="image/*" onchange="document.getElementById('bg-form').submit()">
                            </label>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Avatar Upload -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3>Profile Avatar</h3>
                </div>
                <div class="card-body">
                    <?php 
                    // Get profile picture from users table
                    $conn = new mysqli('localhost', 'root', '', 'bio_links');
                    $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user_data = $result->fetch_assoc();
                    $profile_pic = $user_data['profile_pic'];
                    $stmt->close();
                    $conn->close();
                    
                    if (!empty($profile_pic)): ?>
                        <div class="preview-container">
                            <img src="uploads/<?php echo htmlspecialchars($profile_pic); ?>" alt="Avatar" class="preview-image">
                            <button class="delete-btn" onclick="deleteAsset('profile_avatar')">×</button>
                        </div>
                    <?php else: ?>
                        <form method="post" enctype="multipart/form-data" id="avatar-form">
                            <label for="profile_avatar" class="upload-area">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="upload-text">Click to upload profile picture</div>
                                <input type="file" name="profile_avatar" id="profile_avatar" accept="image/*" onchange="document.getElementById('avatar-form').submit()">
                            </label>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Custom Cursor Upload -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                    <h3>Custom Cursor</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($assets_array['custom_cursor'])): ?>
                        <div class="preview-container">
                            <img src="uploads/<?php echo htmlspecialchars($assets_array['custom_cursor']['path']); ?>" alt="Cursor" class="preview-image">
                            <button class="delete-btn" onclick="deleteAsset('custom_cursor')">×</button>
                        </div>
                    <?php else: ?>
                        <form method="post" enctype="multipart/form-data" id="cursor-form">
                            <label for="custom_cursor" class="upload-area">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="upload-text">Click to upload cursor image</div>
                                <input type="file" name="custom_cursor" id="custom_cursor" accept="image/*" onchange="document.getElementById('cursor-form').submit()">
                            </label>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Audio URL -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-music"></i>
                    </div>
                    <h3>Background Music</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($assets_array['audio'])): ?>
                        <div class="preview-container">
                            <audio controls class="preview-audio">
                                <source src="<?php echo htmlspecialchars($assets_array['audio']['path']); ?>" type="audio/mpeg">
                            </audio>
                            <button class="delete-btn" onclick="deleteAsset('audio')">×</button>
                        </div>
                    <?php else: ?>
                        <form method="post" id="audio-form">
                            <div class="upload-area" style="padding: 32px 24px;">
                                <div class="upload-icon" style="width: 48px; height: 48px; font-size: 22px; margin-bottom: 12px;">
                                    <i class="fas fa-headphones"></i>
                                </div>
                                <div class="upload-text">Add background music</div>
                            </div>
                            <div class="input-group">
                                <input type="url" name="audio_url" placeholder="Enter audio URL (MP3, SoundCloud, etc.)" required>
                                <button type="submit" class="submit-btn">Add Audio</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Links Section -->
        <div class="links-section">
            <h2 class="section-title">Your Links</h2>
            
            <!-- Add New Link Form -->
            <div class="add-link-card">
                <h3>
                    <div class="add-link-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    Add New Link
                </h3>
                <form method="post" id="add-link-form">
                    <div class="input-group">
                        <input type="text" name="link_title" placeholder="Link Title (e.g., My Instagram)" required>
                        <input type="url" name="link_url" placeholder="https://example.com" required>
                        <button type="submit" name="add_link" class="submit-btn">Add Link</button>
                    </div>
                </form>
            </div>

            <!-- Existing Links -->
            <?php if (empty($links)): ?>
                <div class="no-links">
                    <p>No links added yet. Add your first link above to get started!</p>
                </div>
            <?php else: ?>
                <?php foreach ($links as $link): ?>
                    <div class="link-card">
                        <div class="link-info">
                            <div class="link-title"><?php echo htmlspecialchars($link['title']); ?></div>
                            <div class="link-url"><?php echo htmlspecialchars($link['url']); ?></div>
                        </div>
                        <div class="link-actions">
                            <button class="icon-btn" title="Delete" onclick="deleteLink(<?php echo $link['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function deleteAsset(type) {
            if (confirm('Are you sure you want to delete this asset?')) {
                fetch('delete_asset.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'type': type
                    })
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        location.reload();
                    } else {
                        alert('Error deleting asset: ' + data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the asset');
                });
            }
        }

        function deleteLink(linkId) {
            if (confirm('Are you sure you want to delete this link?')) {
                fetch('delete_link.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'link_id': linkId
                    })
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        location.reload();
                    } else {
                        alert('Error deleting link: ' + data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the link');
                });
            }
        }
    </script>
</body>
</html>