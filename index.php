<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
        
        // Retrieve assets
        $assets = [];
        $asset_types = ['background', 'audio', 'profile_avatar', 'custom_cursor'];
        foreach ($asset_types as $type) {
            $stmt = $conn->prepare("SELECT path FROM assets WHERE user_id = ? AND type = ?");
            $stmt->bind_param("is", $user_id, $type);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $assets[$type] = $result->fetch_assoc()['path'];
            }
        }
        
        // Get links
        $stmt = $conn->prepare("SELECT * FROM links WHERE user_id = ? ORDER BY id ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $links_result = $stmt->get_result();
        $links = [];
        while ($row = $links_result->fetch_assoc()) {
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
    <title><?php echo htmlspecialchars($username); ?> - Regret Bio</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #000000 100%);
            padding: 20px;
            <?php if (isset($assets['background'])): ?>
            background-image: url('uploads/<?php echo htmlspecialchars($assets['background']); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            <?php endif; ?>
            <?php if (isset($assets['custom_cursor'])): ?>
            cursor: url('uploads/<?php echo htmlspecialchars($assets['custom_cursor']); ?>') 16 16, auto;
            <?php endif; ?>
        }
        
        a, button, input, textarea, select, .link-item, .play-btn, .volume-btn, .progress-bar {
            <?php if (isset($assets['custom_cursor'])): ?>
            cursor: url('uploads/<?php echo htmlspecialchars($assets['custom_cursor']); ?>') 16 16, pointer;
            <?php endif; ?>
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(8px);
            z-index: -1;
        }
        
        .container {
            max-width: 680px;
            width: 100%;
            margin: 0 auto;
        }
        
        .bio-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 48px 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            text-align: center;
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 24px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            display: block;
        }
        
        .profile-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffffff 0%, #666666 100%);
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #000;
            font-weight: bold;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }
        
        .username {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff 0%, #999999 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
        }

        .staff-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 6px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .staff-badge img {
            width: 20px;
            height: 20px;
            object-fit: contain;
            display: block;
        }
        
        .staff-badge .badge-text {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
            margin-bottom: 8px;
        }
        
        .staff-badge .badge-text::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.9);
        }
        
        .staff-badge:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        
        .staff-badge:hover .badge-text {
            opacity: 1;
            margin-bottom: 12px;
        }

        .bio {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            margin-bottom: 32px;
        }
        
        /* Custom Music Player Styles */
        .music-player {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            backdrop-filter: blur(10px);
        }
        
        .player-controls {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .play-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffffff 0%, #999999 100%);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            flex-shrink: 0;
        }
        
        .play-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.3);
        }
        
        .play-btn:active {
            transform: scale(0.95);
        }
        
        .play-btn svg {
            width: 20px;
            height: 20px;
            fill: #000;
        }
        
        .player-info {
            flex: 1;
            text-align: left;
            min-width: 0;
        }
        
        .song-title {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .song-time {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .volume-control {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        
        .volume-btn {
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .volume-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .volume-btn svg {
            width: 16px;
            height: 16px;
            fill: #fff;
        }
        
        .volume-slider {
            width: 80px;
            height: 4px;
            -webkit-appearance: none;
            appearance: none;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            outline: none;
        }
        
        .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 12px;
            height: 12px;
            background: #fff;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .volume-slider::-moz-range-thumb {
            width: 12px;
            height: 12px;
            background: #fff;
            border-radius: 50%;
            cursor: pointer;
            border: none;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #ffffff 0%, #999999 100%);
            border-radius: 3px;
            width: 0%;
            transition: width 0.1s linear;
            position: relative;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .visualizer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
            height: 40px;
            margin-top: 16px;
        }
        
        .bar {
            width: 3px;
            height: 8px;
            background: linear-gradient(135deg, #ffffff 0%, #999999 100%);
            border-radius: 2px;
            animation: wave 1s ease-in-out infinite;
        }
        
        .bar:nth-child(1) { animation-delay: 0s; }
        .bar:nth-child(2) { animation-delay: 0.1s; }
        .bar:nth-child(3) { animation-delay: 0.2s; }
        .bar:nth-child(4) { animation-delay: 0.3s; }
        .bar:nth-child(5) { animation-delay: 0.4s; }
        .bar:nth-child(6) { animation-delay: 0.3s; }
        .bar:nth-child(7) { animation-delay: 0.2s; }
        .bar:nth-child(8) { animation-delay: 0.1s; }
        
        @keyframes wave {
            0%, 100% { height: 8px; }
            50% { height: 28px; }
        }
        
        .visualizer.paused .bar {
            animation: none;
            height: 8px;
        }
        
        .links-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .link-item {
            display: block;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 18px 24px;
            color: #fff;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .link-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #ffffff 0%, #666666 100%);
            transition: left 0.3s ease;
            z-index: -1;
        }
        
        .link-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.4);
            color: #000;
        }
        
        .link-item:hover::before {
            left: 0;
        }
        
        .link-item:active {
            transform: translateY(0);
        }
        
        @media (max-width: 640px) {
            .bio-card {
                padding: 36px 24px;
            }
            
            .username {
                font-size: 24px;
            }
            
            .profile-pic,
            .profile-placeholder {
                width: 100px;
                height: 100px;
            }
            
            .profile-placeholder {
                font-size: 40px;
            }
            
            .volume-slider {
                width: 60px;
            }
            
            .player-controls {
                flex-wrap: wrap;
            }
            
            .volume-control {
                width: 100%;
                justify-content: center;
                margin-top: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="bio-card">
            <?php if (!empty($profile_pic)): ?>
                <img src="uploads/<?php echo htmlspecialchars($profile_pic); ?>" alt="<?php echo htmlspecialchars($username); ?>" class="profile-pic">
            <?php else: ?>
                <div class="profile-placeholder">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>
            <?php endif; ?>
            
            <h1 class="username"><?php echo htmlspecialchars($username); ?></h1>
            
            <span class="staff-badge">
                <img src="https://media.regret.bio/stafff.png" alt="Staff">
                <span class="badge-text">Staff</span>
            </span>
            
            <?php if (!empty($bio)): ?>
                <p class="bio"><?php echo nl2br(htmlspecialchars($bio)); ?></p>
            <?php endif; ?>
            
            <?php if (isset($assets['audio'])): 
                // Check if it's a URL or a local file
                $audioPath = $assets['audio'];
                $isExternalUrl = (strpos($audioPath, 'http://') === 0 || strpos($audioPath, 'https://') === 0);
                $audioSrc = $isExternalUrl ? $audioPath : 'uploads/' . $audioPath;
            ?>
                <div class="music-player">
                    <div class="player-controls">
                        <button class="play-btn" id="playBtn">
                            <svg id="playIcon" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <svg id="pauseIcon" viewBox="0 0 24 24" style="display: none;">
                                <path d="M6 4h4v16H6zM14 4h4v16h-4z"/>
                            </svg>
                        </button>
                        
                        <div class="player-info">
                            <div class="song-title">Now Playing</div>
                            <div class="song-time">
                                <span id="currentTime">0:00</span> / <span id="duration">0:00</span>
                            </div>
                        </div>
                        
                        <div class="volume-control">
                            <button class="volume-btn" id="volumeBtn">
                                <svg id="volumeIcon" viewBox="0 0 24 24">
                                    <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/>
                                </svg>
                                <svg id="muteIcon" viewBox="0 0 24 24" style="display: none;">
                                    <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                                </svg>
                            </button>
                            <input type="range" class="volume-slider" id="volumeSlider" min="0" max="100" value="70">
                        </div>
                    </div>
                    
                    <div class="progress-bar" id="progressBar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    
                    <div class="visualizer" id="visualizer">
                        <div class="bar"></div>
                        <div class="bar"></div>
                        <div class="bar"></div>
                        <div class="bar"></div>
                        <div class="bar"></div>
                        <div class="bar"></div>
                        <div class="bar"></div>
                        <div class="bar"></div>
                    </div>
                    
                    <audio id="audioPlayer" preload="auto">
                        <source src="<?php echo htmlspecialchars($audioSrc); ?>" type="audio/mpeg">
                        <source src="<?php echo htmlspecialchars($audioSrc); ?>" type="audio/mp4">
                        <source src="<?php echo htmlspecialchars($audioSrc); ?>" type="audio/ogg">
                        <source src="<?php echo htmlspecialchars($audioSrc); ?>" type="audio/wav">
                    </audio>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($links)): ?>
                <div class="links-container">
                    <?php foreach ($links as $link): ?>
                        <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" class="link-item">
                            <?php echo htmlspecialchars($link['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        const audio = document.getElementById('audioPlayer');
        const playBtn = document.getElementById('playBtn');
        const playIcon = document.getElementById('playIcon');
        const pauseIcon = document.getElementById('pauseIcon');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');
        const currentTimeEl = document.getElementById('currentTime');
        const durationEl = document.getElementById('duration');
        const volumeSlider = document.getElementById('volumeSlider');
        const volumeBtn = document.getElementById('volumeBtn');
        const volumeIcon = document.getElementById('volumeIcon');
        const muteIcon = document.getElementById('muteIcon');
        const visualizer = document.getElementById('visualizer');
        
        // Set initial volume
        audio.volume = 0.7;
        
        // Auto-play with user interaction workaround
        let hasInteracted = false;
        
        function tryAutoplay() {
            audio.play().then(() => {
                playIcon.style.display = 'none';
                pauseIcon.style.display = 'block';
                visualizer.classList.remove('paused');
            }).catch(err => {
                console.log('Autoplay prevented, waiting for user interaction');
            });
        }
        
        // Try to autoplay
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(tryAutoplay, 500);
        });
        
        // Enable autoplay after first user interaction
        document.addEventListener('click', function enableAutoplay() {
            if (!hasInteracted) {
                hasInteracted = true;
                if (audio.paused) {
                    audio.play();
                }
            }
        }, { once: true });
        
        // Play/Pause toggle
        playBtn.addEventListener('click', () => {
            if (audio.paused) {
                audio.play();
                playIcon.style.display = 'none';
                pauseIcon.style.display = 'block';
                visualizer.classList.remove('paused');
            } else {
                audio.pause();
                playIcon.style.display = 'block';
                pauseIcon.style.display = 'none';
                visualizer.classList.add('paused');
            }
        });
        
        // Update progress bar
        audio.addEventListener('timeupdate', () => {
            const progress = (audio.currentTime / audio.duration) * 100;
            progressFill.style.width = progress + '%';
            currentTimeEl.textContent = formatTime(audio.currentTime);
        });
        
        // Update duration
        audio.addEventListener('loadedmetadata', () => {
            durationEl.textContent = formatTime(audio.duration);
        });
        
        // Seek functionality
        progressBar.addEventListener('click', (e) => {
            const rect = progressBar.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            audio.currentTime = percent * audio.duration;
        });
        
        // Volume control
        volumeSlider.addEventListener('input', (e) => {
            audio.volume = e.target.value / 100;
            updateVolumeIcon();
        });
        
        // Mute toggle
        volumeBtn.addEventListener('click', () => {
            audio.muted = !audio.muted;
            updateVolumeIcon();
        });
        
        function updateVolumeIcon() {
            if (audio.muted || audio.volume === 0) {
                volumeIcon.style.display = 'none';
                muteIcon.style.display = 'block';
            } else {
                volumeIcon.style.display = 'block';
                muteIcon.style.display = 'none';
            }
        }
        
        // Format time helper
        function formatTime(seconds) {
            if (isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
        
        // Loop audio
        audio.addEventListener('ended', () => {
            audio.currentTime = 0;
            audio.play();
        });
        
        // Error handling
        audio.addEventListener('error', (e) => {
            console.error('Audio error:', e);
            console.error('Source:', audio.currentSrc);
        });
    </script>
</body>
</html>
<?php
    } else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Not Found - Regret Bio</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #000000 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 48px;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            text-align: center;
        }

        .error-icon {
            margin-bottom: 24px;
            display: flex;
            justify-content: center;
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff 0%, #999999 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
        }

        p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 15px;
            margin-bottom: 32px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }

        .btn {
            padding: 14px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #666666 100%);
            color: #000;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <svg viewBox="0 0 24 24" width="64" height="64" stroke="#ffffff" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
        </div>
        <h1>User Not Found</h1>
        <p>The username you're looking for doesn't exist.</p>
        <div class="button-group">
            <a href="index.php" class="btn btn-primary">Go Home</a>
            <a href="register.php" class="btn btn-secondary">Claim User</a>
        </div>
    </div>
</body>
</html>
<?php
    }
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regret Bio - Your Link in Bio Solution</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #000000 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .hero-container {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }

        .logo {
            margin-bottom: 48px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo h1 {
            font-size: 48px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff 0%, #999999 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 16px;
        }

        .logo p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 20px;
        }

        .cta-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-bottom: 80px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 16px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #666666 100%);
            color: #000;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 48px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            transition: all 0.3s;
            animation: fadeIn 0.6s ease-out;
            animation-fill-mode: both;
        }

        .feature-card:nth-child(1) { animation-delay: 0.1s; }
        .feature-card:nth-child(2) { animation-delay: 0.2s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feature-icon svg {
            width: 48px;
            height: 48px;
            stroke: #ffffff;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .feature-card h3 {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 12px;
        }

        .feature-card p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 15px;
            line-height: 1.6;
        }

        @media (max-width: 640px) {
            .logo h1 {
                font-size: 36px;
            }

            .logo p {
                font-size: 18px;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="hero-container">
        <div class="logo">
            <h1>Regret Bio</h1>
            <p>One link to rule them all</p>
        </div>

        <div class="cta-buttons">
            <a href="register.php" class="btn btn-primary">Get Started</a>
            <a href="login.php" class="btn btn-secondary">Sign In</a>
        </div>

        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                    </svg>
                </div>
                <h3>Multiple Links</h3>
                <p>Share all your important links in one place. Perfect for social media bios.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                    </svg>
                </div>
                <h3>Customizable</h3>
                <p>Personalize your page with custom backgrounds, profile pictures, and more.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                </div>
                <h3>Easy to Use</h3>
                <p>Create and manage your bio link page in minutes. No technical skills required.</p>
            </div>
        </div>
    </div>
</body>
</html>
<?php
}
?>