<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database configuration
$servername = "sql302.infinityfree.com";
$username = "if0_39066965";
$password = "GESukmyBCuQdM";
$dbname = "if0_39066965_surya_movies";

$user_name = $_SESSION['username'];
$watch_history = [];

// Handle delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_watch'])) {
        $conn = new mysqli($servername, $username, $password, $dbname);
        if (!$conn->connect_error) {
            $watch_id = $_POST['watch_id'];
            $user_id = $_SESSION['user_id'];
            
            $delete_sql = "DELETE FROM watch_history WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("ii", $watch_id, $user_id);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            
            // Redirect to refresh the page
            header("Location: watch_history.php");
            exit();
        }
    }
    
    // Clear all watch history
    if (isset($_POST['clear_all_watch'])) {
        $conn = new mysqli($servername, $username, $password, $dbname);
        if (!$conn->connect_error) {
            $user_id = $_SESSION['user_id'];
            
            $delete_sql = "DELETE FROM watch_history WHERE user_id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            
            header("Location: watch_history.php");
            exit();
        }
    }
}

$conn = new mysqli($servername, $username, $password, $dbname);

if (!$conn->connect_error) {
    $user_id = $_SESSION['user_id'];
    
    // Get watch history - increased limit to 200
    $watch_sql = "SELECT id, movie_name, watch_url, watch_time, duration_seconds FROM watch_history 
                  WHERE user_id = ? ORDER BY watch_time DESC LIMIT 200";
    $stmt = $conn->prepare($watch_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $watch_result = $stmt->get_result();
    
    if ($watch_result) {
        $watch_history = $watch_result->fetch_all(MYSQLI_ASSOC);
    }
    
    $stmt->close();
    $conn->close();
}

// Format watch time for display
function formatWatchTime($seconds) {
    $seconds = intval($seconds);
    
    if ($seconds === 0) {
        return '0 sec';
    }
    
    if ($seconds < 60) {
        return $seconds . ' sec';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        return $minutes . ' min';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        if ($minutes > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } else {
            return $hours . 'h';
        }
    }
}

// Get statistics
$total_watched = count($watch_history);
$total_seconds = 0;
$unique_movies = [];

foreach ($watch_history as $watch) {
    $total_seconds += intval($watch['duration_seconds']);
    $unique_movies[$watch['movie_name']] = true;
}

$total_hours = floor($total_seconds / 3600);
$unique_count = count($unique_movies);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch History - Surya Movies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #eef2ff;
            --secondary: #f9fafb;
            --accent: #10b981;
            --text: #1e293b;
            --text-light: #64748b;
            --white: #ffffff;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            --radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --gradient: linear-gradient(135deg, #8b5cf6, #6366f1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            padding-bottom: 4rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .site-header {
            background: var(--gradient);
            color: var(--white);
            padding: 2.5rem 0;
            text-align: center;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            border-radius: 0 0 var(--radius) var(--radius);
            margin-bottom: 2rem;
        }

        .site-header h1 {
            font-size: 3.2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            margin: 0;
            position: relative;
            z-index: 1;
            background: linear-gradient(to right, #ffffff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* User Section */
        .user-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .user-details h3 {
            color: var(--primary);
            margin-bottom: 0.2rem;
        }

        .user-actions {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .user-action-btn {
            padding: 0.7rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .back-btn {
            background: var(--primary);
            color: var(--white);
        }

        .history-btn {
            background: var(--accent);
            color: var(--white);
        }

        .logout-btn {
            background: #ef4444;
            color: var(--white);
        }

        .user-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Watch History Section */
        .history-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .clear-all-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .clear-all-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: var(--radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .history-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .history-item {
            background: rgba(255, 255, 255, 0.8);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .history-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--accent);
        }

        .history-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .movie-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .watch-info {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .duration-badge {
            background: var(--accent);
            color: white;
            padding: 0.3rem 0.7rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .history-actions {
            display: flex;
            gap: 0.8rem;
            justify-content: space-between;
        }

        .action-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
            flex: 1;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .action-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .download-btn {
            background: #10b981;
        }

        .download-btn:hover {
            background: #059669;
        }

        .delete-btn {
            background: #fef2f2;
            color: #ef4444;
            border: 1px solid #fecaca;
            padding: 0.6rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .delete-btn:hover {
            background: #ef4444;
            color: white;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }

        /* Modal Styles */
        .custom-alert {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .custom-alert.hidden {
            display: none;
        }

        .alert-box {
            background: var(--white);
            border-radius: var(--radius);
            padding: 2.5rem;
            max-width: 450px;
            width: 90%;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .alert-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient);
        }

        .alert-box p {
            margin-bottom: 1.8rem;
            font-size: 1.2rem;
            color: var(--text-light);
            line-height: 1.7;
        }

        .alert-box button {
            padding: 0.9rem 2.2rem;
            background: var(--gradient);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1.1rem;
            box-shadow: 0 4px 6px rgba(99, 102, 241, 0.3);
        }

        .alert-box button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(99, 102, 241, 0.4);
        }

        /* Video Player Styles */
        .video-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 800px;
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            z-index: 1001;
            overflow: hidden;
        }

        .video-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: var(--gradient);
            color: white;
        }

        .video-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.2rem;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
        }

        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
        }

        .video-loader {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            text-align: center;
            z-index: 1;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
            padding: 1rem;
        }

        .pagination-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pagination-btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .pagination-btn:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .pagination-info {
            color: var(--text-light);
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .site-header h1 {
                font-size: 2.2rem;
            }
            
            .user-section {
                flex-direction: column;
                text-align: center;
            }
            
            .user-actions {
                justify-content: center;
            }
            
            .history-list {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .video-popup {
                width: 95%;
            }
        }
        
        @media (max-width: 480px) {
            .site-header h1 {
                font-size: 1.8rem;
            }
            
            .history-section {
                padding: 1.5rem;
            }
            
            .history-actions {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1>WATCH HISTORY</h1>
        </div>
    </header>

    <div class="container">
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <h3>Welcome, <?php echo htmlspecialchars($user_name); ?></h3>
                    <p>Your movie watch history</p>
                </div>
            </div>
            <div class="user-actions">
                <a href="index.php" class="user-action-btn back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Home
                </a>
                <a href="user.php" class="user-action-btn history-btn">
                    <i class="fas fa-history"></i>
                    All History
                </a>
                <a href="logout.php" class="user-action-btn logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <div class="history-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-play-circle"></i>
                    Your Watch History
                </h2>
                <?php if (!empty($watch_history)): ?>
                <form method="POST" onsubmit="return confirm('Are you sure you want to clear all watch history?');">
                    <button type="submit" name="clear_all_watch" class="clear-all-btn">
                        <i class="fas fa-trash"></i>
                        Clear All History
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_watched; ?></div>
                    <div class="stat-label">Total Watched</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $unique_count; ?></div>
                    <div class="stat-label">Unique Movies</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_hours; ?></div>
                    <div class="stat-label">Hours Watched</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php
                        if (!empty($watch_history)) {
                            $lastWatch = $watch_history[0]['watch_time'];
                            echo date('M j', strtotime($lastWatch));
                        } else {
                            echo 'Never';
                        }
                        ?>
                    </div>
                    <div class="stat-label">Last Watched</div>
                </div>
            </div>
            
            <div class="history-list">
                <?php if (empty($watch_history)): ?>
                    <div class="empty-state">
                        <i class="fas fa-film"></i>
                        <h3>No watch history yet</h3>
                        <p>Start watching movies to see them here</p>
                        <a href="index.php" class="action-btn" style="margin-top: 1rem; display: inline-block; width: auto;">
                            <i class="fas fa-play"></i>
                            Start Watching Movies
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($watch_history as $watch): ?>
                        <div class="history-item">
                            <h3 class="movie-title"><?php echo htmlspecialchars($watch['movie_name']); ?></h3>
                            
                            <?php if ($watch['duration_seconds'] > 0): ?>
                                <div class="duration-badge">
                                    <i class="fas fa-clock"></i>
                                    <?php echo formatWatchTime($watch['duration_seconds']); ?>
                                </div>
                            <?php else: ?>
                                <div class="duration-badge" style="background: #6b7280;">
                                    <i class="fas fa-play"></i>
                                    Started
                                </div>
                            <?php endif; ?>
                            
                            <div class="watch-info">
                                <i class="fas fa-calendar"></i>
                                Watched on <?php echo date('M j, Y g:i A', strtotime($watch['watch_time'])); ?>
                            </div>
                            
                            <div class="history-actions">
                                <button class="action-btn watch-btn" onclick="playMovie('<?php echo addslashes($watch['movie_name']); ?>', '<?php echo htmlspecialchars($watch['watch_url']); ?>')">
                                    <i class="fas fa-play"></i>
                                    Watch Now
                                </button>
                                <button class="action-btn download-btn" onclick="downloadMovie('<?php echo addslashes($watch['movie_name']); ?>', '<?php echo htmlspecialchars($watch['watch_url']); ?>')">
                                    <i class="fas fa-download"></i>
                                    Download
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this watch history?');">
                                    <input type="hidden" name="watch_id" value="<?php echo $watch['id']; ?>">
                                    <button type="submit" name="delete_watch" class="delete-btn" title="Delete this watch history">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Alert Modal -->
    <div id="customAlert" class="custom-alert hidden">
        <div class="alert-box">
            <p id="alertMessage">Alert goes here</p>
            <button id="closeAlertBtn">OK</button>
        </div>
    </div>

    <!-- Video Player Modal -->
    <div id="videoPlayer" class="custom-alert hidden">
        <div class="video-popup">
            <div class="video-header">
                <h3 id="videoTitle">Movie Title</h3>
                <button class="close-btn" onclick="closeVideoPlayer()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="video-container">
                <div class="video-loader" id="videoLoader">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Loading video... Please wait</p>
                </div>
                <video id="movieVideo" controls controlsList="nodownload" oncontextmenu="return false;">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
    </div>

    <script>
        // Alert functions
        function showAlert(message) {
            document.getElementById('alertMessage').innerText = message;
            document.getElementById('customAlert').classList.remove('hidden');
        }

        document.getElementById('closeAlertBtn').addEventListener('click', function() {
            document.getElementById('customAlert').classList.add('hidden');
        });

        // Video player functions
        function playMovie(movieName, videoUrl) {
            const videoPlayer = document.getElementById('videoPlayer');
            const videoTitle = document.getElementById('videoTitle');
            const movieVideo = document.getElementById('movieVideo');
            const videoLoader = document.getElementById('videoLoader');

            videoTitle.innerText = movieName;
            videoPlayer.classList.remove('hidden');
            videoLoader.style.display = 'block';
            
            // Set video source
            const source = document.createElement('source');
            source.src = videoUrl;
            source.type = 'video/mp4';
            
            // Clear previous sources
            while (movieVideo.firstChild) {
                movieVideo.removeChild(movieVideo.firstChild);
            }
            movieVideo.appendChild(source);
            
            // Load video
            movieVideo.load();
            
            // Hide loader when video can play
            movieVideo.addEventListener('canplay', function() {
                videoLoader.style.display = 'none';
                // Auto-play when ready
                movieVideo.play().catch(e => {
                    console.log('Auto-play prevented:', e);
                    videoLoader.style.display = 'none';
                });
            });
            
            // Show loader again if waiting
            movieVideo.addEventListener('waiting', function() {
                videoLoader.style.display = 'block';
            });
            
            // Handle errors
            movieVideo.addEventListener('error', function() {
                videoLoader.style.display = 'none';
                showAlert('Error loading video. Please check the video URL or try another movie.');
                closeVideoPlayer();
            });

            // Start tracking watch time
            startWatchTimeTracking(movieName, videoUrl);
        }

        function closeVideoPlayer() {
            const videoPlayer = document.getElementById('videoPlayer');
            const movieVideo = document.getElementById('movieVideo');
            
            // Stop tracking watch time
            stopWatchTimeTracking();
            
            movieVideo.pause();
            movieVideo.currentTime = 0;
            videoPlayer.classList.add('hidden');
        }

        // Download function
        function downloadMovie(movieName, downloadUrl) {
            showAlert(`You will be redirected to download "${movieName}". Please wait...`);
            
            // Save download history
            saveDownloadHistory(movieName, downloadUrl);
            
            // Open download URL in new tab after delay
            setTimeout(() => {
                window.open('https://otieu.com/4/9535254', '_blank');
                setTimeout(() => {
                    window.open(downloadUrl, '_blank');
                }, 2000);
            }, 1500);
        }

        // Watch time tracking variables
        let watchStartTime = null;
        let watchDurationInterval = null;
        let currentMovieName = '';
        let currentVideoUrl = '';

        function startWatchTimeTracking(movieName, videoUrl) {
            currentMovieName = movieName;
            currentVideoUrl = videoUrl;
            watchStartTime = Date.now();
            
            // Send initial watch history
            saveWatchHistory(movieName, videoUrl, 0);
            
            // Update duration every 30 seconds
            watchDurationInterval = setInterval(() => {
                const duration = Math.floor((Date.now() - watchStartTime) / 1000);
                if (duration > 0) {
                    saveWatchHistory(movieName, videoUrl, duration);
                }
            }, 30000); // Update every 30 seconds
        }

        function stopWatchTimeTracking() {
            if (watchDurationInterval) {
                clearInterval(watchDurationInterval);
                watchDurationInterval = null;
            }
            
            // Send final duration
            if (watchStartTime && currentMovieName && currentVideoUrl) {
                const finalDuration = Math.floor((Date.now() - watchStartTime) / 1000);
                if (finalDuration > 10) { // Only save if watched for more than 10 seconds
                    saveWatchHistory(currentMovieName, currentVideoUrl, finalDuration);
                }
            }
            
            watchStartTime = null;
            currentMovieName = '';
            currentVideoUrl = '';
        }

        // Save watch history
        function saveWatchHistory(movieName, watchUrl, duration = 0) {
            const formData = new FormData();
            formData.append('movie_name', movieName);
            formData.append('watch_url', watchUrl);
            formData.append('duration', duration);

            fetch('save_watch.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Failed to save watch history:', data.message);
                }
            })
            .catch(err => console.error('Failed to save watch history:', err));
        }

        // Save download history
        function saveDownloadHistory(movieName, downloadUrl) {
            fetch('save_download.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `movie_name=${encodeURIComponent(movieName)}&download_url=${encodeURIComponent(downloadUrl)}`
            }).catch(err => console.error('Failed to save download history:', err));
        }

        // Close video player when clicking outside
        document.getElementById('videoPlayer').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVideoPlayer();
            }
        });

        // Prevent right-click on video
        document.addEventListener('contextmenu', function(e) {
            if (e.target.tagName === 'VIDEO') {
                e.preventDefault();
            }
        });

        // Prevent video download shortcuts
        document.addEventListener('keydown', function(e) {
            const video = document.getElementById('movieVideo');
            if (video && !video.hidden) {
                // Prevent F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U, Ctrl+S
                if (e.keyCode === 123 || 
                    (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) ||
                    (e.ctrlKey && e.keyCode === 85) ||
                    (e.ctrlKey && e.keyCode === 83)) {
                    e.preventDefault();
                }
            }
        });

        // Handle page visibility changes to stop tracking when tab is not active
        document.addEventListener('visibilitychange', function() {
            const video = document.getElementById('movieVideo');
            if (document.hidden && video && !video.paused) {
                // Page is hidden, pause video and stop tracking
                video.pause();
                stopWatchTimeTracking();
            }
        });
    </script>
</body>
</html>