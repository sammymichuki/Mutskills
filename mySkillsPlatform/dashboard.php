<?php
session_start();
require_once 'config.php';

// Handle remember me auto-login
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    try {
        $pdo = getDBConnection();
        $providedToken = $_COOKIE['remember_token'];
        $hashedToken = hash('sha256', $providedToken);
        $stmt = $pdo->prepare("SELECT user_id FROM remember_tokens WHERE token = :token AND expiry > NOW() LIMIT 1");
        $stmt->execute([':token' => $hashedToken]);
        $row = $stmt->fetch();
        if ($row && isset($row['user_id'])) {
            $_SESSION['user_id'] = (int)$row['user_id'];
        }
    } catch (PDOException $e) {
        error_log('Remember token check failed: ' . $e->getMessage());
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Fetch user data from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // User not found, logout
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Fetch user stats
    $stmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM connections WHERE user_id = :user_id OR connected_user_id = :user_id2) as connections,
        (SELECT COUNT(*) FROM followers WHERE follower_id = :user_id3) as following,
        (SELECT COUNT(*) FROM followers WHERE user_id = :user_id4) as followers
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':user_id2' => $_SESSION['user_id'],
        ':user_id3' => $_SESSION['user_id'],
        ':user_id4' => $_SESSION['user_id']
    ]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch posts (with user info)
    $stmt = $pdo->prepare("
        SELECT p.*, u.first_name, u.last_name, u.email, u.title, 
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes,
               (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comments,
               (SELECT COUNT(*) FROM post_shares WHERE post_id = p.id) as shares,
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = :current_user_id) as user_liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([':current_user_id' => $_SESSION['user_id']]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

$firstName = $user['first_name'] ?? '';
$lastName = $user['last_name'] ?? '';
$fullName = trim($firstName . ' ' . $lastName);
if (empty($fullName)) $fullName = $user['email'];
$title = $user['title'] ?? 'Add your title';

// Get initials
$initials = '';
if (!empty($firstName)) $initials .= strtoupper($firstName[0]);
if (!empty($lastName)) $initials .= strtoupper($lastName[0]);
if (empty($initials) && !empty($user['email'])) {
    $initials = strtoupper(substr($user['email'], 0, 2));
}
if (empty($initials)) $initials = 'U';

// Function to get user initials
function getUserInitials($firstName, $lastName, $email) {
    $init = '';
    if (!empty($firstName)) $init .= strtoupper($firstName[0]);
    if (!empty($lastName)) $init .= strtoupper($lastName[0]);
    if (empty($init) && !empty($email)) {
        $init = strtoupper(substr($email, 0, 2));
    }
    return empty($init) ? 'U' : $init;
}

// Function to format time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 7200) return '1 hour ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 172800) return '1 day ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    if ($diff < 1209600) return '1 week ago';
    return floor($diff / 604800) . ' weeks ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mutSkills - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #e8e9ea;
            color: #2d2d2d;
        }

        /* Alert Messages */
        .alert {
            padding: 12px 20px;
            margin: 20px auto;
            max-width: 1280px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        /* Header */
        .header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee0e3;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: #22c55e;
            text-decoration: none;
        }

        .search-bar {
            flex: 1;
            max-width: 400px;
            margin: 0 32px;
        }

        .search-bar input {
            width: 100%;
            padding: 10px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: #f5f5f5;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #22c55e;
            background: white;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .icon-btn {
            background: none;
            border: none;
            font-size: 14px;
            cursor: pointer;
            padding: 8px;
            color: #666;
            transition: color 0.3s;
        }

        .icon-btn:hover {
            color: #22c55e;
        }

        .user-avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #22c55e, #059669); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; cursor: pointer; }
        .header-profile { display:flex; align-items:center; gap:10px; padding:6px 10px; border:1px solid #e5e7eb; border-radius:999px; background:#fff; }
        .header-profile .name { font-weight:600; font-size:14px; }
        .header-profile .title { font-size:12px; color:#666; }

        /* Main Layout */
        .main-container {
            max-width: 1280px;
            margin: 24px auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 280px 1fr 280px;
            gap: 24px;
        }

        /* Sidebar */
        .sidebar {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            height: fit-content;
            position: sticky;
            top: 88px;
        }

        .profile-section {
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 20px;
        }

        .profile-pic {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #22c55e, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 600;
            margin: 0 auto 12px;
        }

        .profile-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .profile-title {
            font-size: 13px;
            color: #666;
        }

        .profile-stats {
            margin-top: 20px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .stat-label {
            color: #666;
        }

        .stat-value {
            font-weight: 600;
            color: #22c55e;
        }

        /* Feed */
        .feed {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
        }

        /* Create Post */
        .create-post-header {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .create-post-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #22c55e, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .create-post-input {
            flex: 1;
        }

        .create-post-input textarea {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            resize: vertical;
            min-height: 80px;
        }

        .create-post-input textarea:focus {
            outline: none;
            border-color: #22c55e;
        }

        .create-post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
        }

        .post-options {
            display: flex;
            gap: 16px;
        }

        .post-option-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            color: #666;
            transition: color 0.3s;
        }

        .post-option-btn:hover {
            color: #22c55e;
        }

        .post-btn {
            background: #22c55e;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .post-btn:hover {
            background: #16a34a;
        }

        /* Post Card */
        .post-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
        }

        .post-header {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }

        .post-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #34d399, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .post-info {
            flex: 1;
        }

        .post-author {
            font-weight: 600;
            font-size: 15px;
        }

        .post-meta {
            font-size: 12px;
            color: #666;
        }

        .post-menu {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }

        .post-content {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 16px;
            white-space: pre-wrap;
        }

        .post-image {
            width: 100%;
            aspect-ratio: 16 / 9;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 16px;
            display: block;
            background: #f3f4f6;
        }

        .post-stats {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
            color: #666;
        }

        .post-actions {
            display: flex;
            justify-content: space-around;
            padding-top: 8px;
        }

        .action-btn {
            background: none;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            cursor: pointer;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .action-btn:hover {
            background: #f5f5f5;
        }

        .action-btn.active {
            color: #22c55e;
        }

        /* Right Sidebar */
        .suggestions {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            height: fit-content;
            position: sticky;
            top: 88px;
        }

        .suggestions-title {
            font-weight: 600;
            margin-bottom: 16px;
        }

        .suggestion-item {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            align-items: center;
        }

        .suggestion-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .suggestion-info {
            flex: 1;
        }

        .suggestion-name {
            font-weight: 600;
            font-size: 14px;
        }

        .suggestion-title {
            font-size: 12px;
            color: #666;
        }

        .follow-btn {
            background: none;
            border: 1px solid #22c55e;
            color: #22c55e;
            padding: 6px 16px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .follow-btn:hover {
            background: #22c55e;
            color: white;
        }

        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
            }

            .sidebar,
            .suggestions {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .search-bar {
                display: none;
            }
            .skills-form-row { flex-direction: column; }
            .post-btn { width: 100%; }
            .small-btn { width: 100%; }
            .card { padding: 16px; }
            .post-actions { flex-direction: column; gap: 8px; }
        }

        /* Skills */
        .skills-form-row { display:flex; gap:12px; margin-top:12px; }
        .skills-form-row input, .skills-form-row textarea { flex:1; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:14px; }
        .skills-list { display:flex; flex-direction:column; gap:10px; }
        .skill-item { border:1px solid #eee; border-radius:8px; padding:12px; }
        .skill-name { font-weight:600; }
        .skill-meta { color:#666; font-size:12px; margin-top:4px; }
        .small-btn { border:1px solid #22c55e; color:#22c55e; background:none; border-radius:14px; padding:6px 10px; font-size:12px; cursor:pointer; }

        /* Messages */
        .messages-wrapper { display:flex; gap:12px; }
        .messages-users { width: 260px; border:1px solid #eee; border-radius:8px; height:320px; overflow:auto; }
        .messages-chat { flex:1; border:1px solid #eee; border-radius:8px; height:320px; display:flex; flex-direction:column; }
        .messages-list { padding:10px; flex:1; overflow:auto; }
        .message-row { margin:6px 0; }
        .message-me { text-align:right; }
        .message-bubble { display:inline-block; padding:8px 10px; border-radius:12px; background:#f5f5f5; max-width:80%; }
        .message-me .message-bubble { background:#dcfce7; }
        .messages-input { display:flex; gap:8px; padding:10px; border-top:1px solid #eee; }
        .messages-input input { flex:1; padding:10px; border:1px solid #ddd; border-radius:8px; }
        .user-item { padding:10px; border-bottom:1px solid #f0f0f0; cursor:pointer; }
        .user-item:hover { background:#f9f9f9; }
        .user-search { width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; margin-bottom:8px; }
        @media (max-width: 768px) {
            .messages-wrapper { flex-direction: column; }
            .messages-users { width: 100%; height: 200px; }
            .messages-chat { height: 320px; }
        }

        /* Modal */
        .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.4); display:none; align-items:center; justify-content:center; z-index:200; }
        .modal { background:#f8f9fa; border-radius:12px; padding:20px; width:100%; max-width:420px; }
        .modal header { font-weight:600; margin-bottom:10px; }
        .modal .row { display:flex; gap:8px; margin:10px 0; }
        .modal input, .modal textarea { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; font-size:14px; }
        .modal .actions { display:flex; justify-content:flex-end; gap:8px; margin-top:10px; }
    </style>
</head>
<body>
    <?php
    // Display success/error messages
    if (isset($_SESSION['post_success'])) {
        echo '<div class="alert success">' . htmlspecialchars($_SESSION['post_success']) . '</div>';
        unset($_SESSION['post_success']);
    }
    if (isset($_SESSION['post_error'])) {
        echo '<div class="alert error">' . htmlspecialchars($_SESSION['post_error']) . '</div>';
        unset($_SESSION['post_error']);
    }
    ?>

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="#" class="logo">mutSkills</a>
            <div class="search-bar">
                <input id="globalSearch" type="text" placeholder="Search skills and press Enter...">
            </div>
            <div class="header-actions">
                <button class="icon-btn" title="Add Skill" onclick="openAddSkillModal()">➕ Add Skill</button>
                <button class="icon-btn" title="View Skills" onclick="focusSkillsFeed()">🧩 View Skills</button>
                <button class="icon-btn" title="Messages" onclick="openMessagesPanel()">💬 Messages</button>
                <div class="header-profile" title="Profile">
                    <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Left Sidebar -->
        <aside class="sidebar">
            <div class="profile-section">
                <div class="profile-pic"><?php echo htmlspecialchars($initials); ?></div>
                <div class="profile-name"><?php echo htmlspecialchars($fullName); ?></div>
                <div class="profile-title"><?php echo htmlspecialchars($title); ?></div>
            </div>
            <div class="profile-stats">
                <div class="stat-item">
                        <span class="stat-label">Connections</span>
                        <span class="stat-value">342</span>
                 </div>
                 <div class="stat-item">
                        <span class="stat-label">Following</span>
                        <span class="stat-value">128</span>
                 </div>
                 <div class="stat-item">
                        <span class="stat-label">Followers</span>
                        <span class="stat-value">1,247</span>
                 </div>
                </div>

            <div style="margin-top:18px; text-align:center;">
                <form method="POST" action="logout.php" onsubmit="return confirm('Are you sure you want to log out?');">
                    <button type="submit" class="follow-btn" style="border-color:#c0392b;color:#c0392b;">Logout</button>
                </form>
            </div>
        </aside>

        <!-- Feed -->
        <main class="feed">
            <!-- Create Post (moved to top) -->
            <div class="card">
                <form method="POST" action="create_post.php" enctype="multipart/form-data" onsubmit="return validatePost(event)">
                    <div class="create-post-header">
                        <div class="create-post-avatar"><?php echo htmlspecialchars($initials); ?></div>
                        <div class="create-post-input">
                            <textarea id="postContent" name="content" placeholder="Share your thoughts, skills, or achievements..."></textarea>
                        </div>
                    </div>
                    <div class="create-post-actions">
                        <div class="post-options">
                            <label class="post-option-btn" title="Add image" style="cursor:pointer;">
                                📷
                                <input type="file" name="image" accept="image/*" style="display:none;">
                            </label>
                            <button type="button" class="post-option-btn" title="Add video">🎥</button>
                        </div>
                        <button type="submit" class="post-btn">Post</button>
                    </div>
                </form>
            </div>

            <!-- Skills Feed -->
            <div class="card" id="skillsFeedCard">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                    <div style="font-weight:600;">Skills</div>
                    <div style="display:flex; gap:8px;">
                        <input type="text" id="feedSkillsSearch" placeholder="Search skills..." style="padding:8px 10px; border:1px solid #ddd; border-radius:8px; font-size:14px;">
                        <button class="small-btn" onclick="loadFeedSkills()">Search</button>
                    </div>
                </div>
                <div id="feedSkills" class="skills-list" style="margin-top:10px;"></div>
            </div>

            

            <!-- Posts -->
            <div id="postsContainer">
                <?php if (empty($posts)): ?>
                    <div class="card" style="text-align: center; color: #666;">
                        <p>No posts yet. Be the first to share something!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): 
                        $postUserInitials = getUserInitials($post['first_name'], $post['last_name'], $post['email']);
                        $postUserName = trim($post['first_name'] . ' ' . $post['last_name']);
                        if (empty($postUserName)) $postUserName = $post['email'];
                        $postUserTitle = $post['title'] ?? 'Member';
                        $timeAgo = timeAgo($post['created_at']);
                        $isLiked = $post['user_liked'] > 0;
                    ?>
                    <div class="post-card">
                        <div class="post-header">
                            <div class="post-avatar"><?php echo htmlspecialchars($postUserInitials); ?></div>
                            <div class="post-info">
                                <div class="post-author"><?php echo htmlspecialchars($postUserName); ?></div>
                                <div class="post-meta"><?php echo htmlspecialchars($postUserTitle); ?> • <?php echo htmlspecialchars($timeAgo); ?></div>
                            </div>
                            <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                <button class="post-menu" onclick="deletePost(<?php echo $post['id']; ?>)">⋯</button>
                            <?php endif; ?>
                        </div>
                        <div class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                        <?php if (!empty($post['image_path'])): ?>
                            <img class="post-image" src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="post image">
                        <?php endif; ?>
                        <div class="post-stats">
                            <span><?php echo $post['likes']; ?> likes</span>
                            <span><?php echo $post['comments']; ?> comments • <?php echo $post['shares']; ?> shares</span>
                        </div>
                        <div class="post-actions">
                            <button class="action-btn <?php echo $isLiked ? 'active' : ''; ?>" onclick="toggleLike(this, <?php echo $post['id']; ?>)" style="<?php echo $isLiked ? 'color: #22c55e;' : ''; ?>">
                                <span>👍</span> Like
                            </button>
                            <button class="action-btn" onclick="toggleComments(this)">
                                <span>💬</span> Comment
                            </button>
                            <button class="action-btn">
                                <span>↗️</span> Share
                            </button>
                        </div>
                        <div class="comments" style="margin-top:12px; display:none;">
                            <div class="comments-list" style="margin-bottom:8px;"></div>
                            <div style="display:flex; gap:8px;">
                                <input type="text" class="comment-input" placeholder="Write a comment..." style="flex:1; padding:8px; border:1px solid #ddd; border-radius:6px;" />
                                <button class="post-btn" onclick="submitComment(this, <?php echo $post['id']; ?>)">Post</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- Right Sidebar (minimal content) -->
        <aside class="suggestions">
            <div class="suggestions-title">Latest Skills</div>
            <div id="allSkillsRight" class="skills-list"></div>
        </aside>
    </div>

    <script>
        const CURRENT_USER_ID = <?php echo (int)$_SESSION['user_id']; ?>;
        function validatePost(event) {
            const content = document.getElementById('postContent').value.trim();
            const fileInput = document.querySelector('input[name="image"]');
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
            if (!content && !hasFile) {
                alert('Please add text or attach an image');
                event.preventDefault();
                return false;
            }
            return true;
        }

        function toggleLike(btn, postId) {
            // Send AJAX request to like/unlike post
            fetch('toggle_like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'post_id=' + postId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.classList.toggle('active');
                    if (btn.classList.contains('active')) {
                        btn.style.color = '#22c55e';
                    } else {
                        btn.style.color = '#666';
                    }
                    
                    // Update like count
                    const postCard = btn.closest('.post-card');
                    const statsSpan = postCard.querySelector('.post-stats span:first-child');
                    statsSpan.textContent = data.likes + ' likes';
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function deletePost(postId) {
            if (!confirm('Are you sure you want to delete this post?')) return;
            
            fetch('delete_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'post_id=' + postId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to delete post');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function toggleComments(btn) {
            const card = btn.closest('.post-card');
            const box = card.querySelector('.comments');
            const list = card.querySelector('.comments-list');
            box.style.display = box.style.display === 'none' ? 'block' : 'none';
            if (box.style.display === 'block' && list.childElementCount === 0) {
                const postId = card.querySelector('[onclick^="toggleLike"]').getAttribute('onclick').match(/,(\s*)(\d+)/)[2];
                fetch('get_comments.php?post_id=' + postId)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            list.innerHTML = '';
                            data.comments.forEach(c => {
                                const name = (c.first_name || '') + ' ' + (c.last_name || '');
                                const safeName = name.trim() || (c.email || '');
                                const div = document.createElement('div');
                                div.style.padding = '6px 0';
                                div.style.borderBottom = '1px solid #f0f0f0';
                                div.textContent = (safeName + ': ' + c.content);
                                list.appendChild(div);
                            });
                        }
                    });
            }
        }

        function submitComment(btn, postId) {
            const card = btn.closest('.post-card');
            const input = card.querySelector('.comment-input');
            const content = (input.value || '').trim();
            if (!content) return;
            btn.disabled = true;
            fetch('add_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'post_id=' + encodeURIComponent(postId) + '&content=' + encodeURIComponent(content)
            }).then(r => r.json()).then(data => {
                btn.disabled = false;
                if (data.success) {
                    // append new comment
                    const list = card.querySelector('.comments-list');
                    const me = document.createElement('div');
                    me.style.padding = '6px 0';
                    me.style.borderBottom = '1px solid #f0f0f0';
                    me.textContent = ('You: ' + content);
                    list.appendChild(me);
                    input.value = '';
                    // update count in stats
                    const statsSpan = card.querySelector('.post-stats span:nth-child(2)');
                    if (statsSpan) {
                        const likesText = statsSpan.textContent; // "X comments • Y shares"
                        const parts = likesText.split(' • ');
                        parts[0] = data.comments + ' comments';
                        statsSpan.textContent = parts.join(' • ');
                    }
                } else {
                    alert(data.message || 'Failed to add comment');
                }
            }).catch(() => { btn.disabled = false; });
        }
        function toggleFollow(btn) {
            if (btn.textContent === 'Follow') {
                btn.textContent = 'Following';
                btn.style.background = '#22c55e';
                btn.style.color = 'white';
            } else {
                btn.textContent = 'Follow';
                btn.style.background = 'none';
                btn.style.color = '#22c55e';
            }
        }

        // Skills
        function submitSkill() {
            const formData = new FormData();
            formData.append('name', (document.getElementById('skillName').value || '').trim());
            formData.append('level', (document.getElementById('skillLevel').value || '').trim());
            formData.append('description', (document.getElementById('skillDescription').value || '').trim());
            const imgFile = document.getElementById('skillImage').files[0];
            if (imgFile) formData.append('image', imgFile);
            if (!formData.get('name')) { alert('Please enter a skill name'); return; }
            fetch('add_skill.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    document.getElementById('skillName').value = '';
                    document.getElementById('skillLevel').value = '';
                    document.getElementById('skillDescription').value = '';
                    document.getElementById('skillImage').value = '';
                    document.getElementById('skillImageLabel').textContent = '';
                    if (document.getElementById('skillsViewBackdrop').style.display === 'flex') {
                        loadMySkills();
                        loadAllSkills();
                    }
                    closeAddSkillModal();
                } else {
                    alert(data.message || 'Failed to add skill');
                }
            });
        }

        function skillOwnerName(s) {
            const fn = s.first_name || ''; const ln = s.last_name || ''; const name = (fn + ' ' + ln).trim();
            return name || (s.email || 'Member');
        }

        function renderSkillItem(s, withMessageBtn) {
            const wrap = document.createElement('div');
            wrap.className = 'skill-item';
            const owner = skillOwnerName(s);
            wrap.innerHTML =
                '<div class="skill-name">' + (s.name || '') + (s.level ? ' • ' + s.level : '') + '</div>' +
                '<div class="skill-meta">' + (owner) + '</div>' +
                (s.description ? '<div style="margin-top:6px; font-size:13px;">' + escapeHtml(s.description) + '</div>' : '') +
                (s.image_path ? '<div style="margin-top:8px;"><img src="' + escapeHtml(s.image_path) + '" style="width:100%; border-radius:8px; aspect-ratio:16/9; object-fit:cover;" alt="Skill image"></div>' : '') +
                (withMessageBtn ? '<div style="margin-top:8px;"><button class="small-btn" onclick="openMessageModal(' + (s.user_id || s.userId || 0) + ', \'' + jsString(owner) + '\')">Message</button></div>' : '');
            return wrap;
        }

        function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
        function jsString(t) { return String(t).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }

        function loadMySkills() {
            const q = encodeURIComponent((document.getElementById('mySkillsSearch') ? document.getElementById('mySkillsSearch').value : (document.getElementById('mySkillsSearchModal') ? document.getElementById('mySkillsSearchModal').value : '')).trim());
            fetch('get_skills.php?scope=mine&q=' + q).then(r=>r.json()).then(data=>{
                const c = document.getElementById('mySkills') || document.getElementById('mySkillsModalList');
                c.innerHTML='';
                if (!data.success || !data.skills || data.skills.length === 0) {
                    const empty = document.createElement('div');
                    empty.style.color = '#666';
                    empty.textContent = 'No skills yet.';
                    c.appendChild(empty);
                    return;
                }
                data.skills.forEach(s => c.appendChild(renderSkillItem(s, false)));
            });
        }

        function loadAllSkills() {
            const q = encodeURIComponent((document.getElementById('allSkillsSearch') ? document.getElementById('allSkillsSearch').value : (document.getElementById('allSkillsSearchModal') ? document.getElementById('allSkillsSearchModal').value : '')).trim());
            fetch('get_skills.php?scope=all&q=' + q).then(r=>r.json()).then(data=>{
                const c = document.getElementById('allSkills') || document.getElementById('allSkillsModalList') || document.getElementById('feedSkills') || document.getElementById('allSkillsRight');
                if (!c) return;
                c.innerHTML='';
                if (!data.success || !data.skills || data.skills.length === 0) {
                    const empty = document.createElement('div');
                    empty.style.color = '#666';
                    empty.textContent = 'No skills found.';
                    c.appendChild(empty);
                    return;
                }
                let list = data.skills;
                // If rendering into right sidebar, keep it minimal (top 5)
                if (c.id === 'allSkillsRight') {
                    list = list.slice(0, 5);
                }
                list.forEach(s => c.appendChild(renderSkillItem(s, true)));
            });
        }

        // Feed skills loader
        function loadFeedSkills() {
            const q = (document.getElementById('feedSkillsSearch') ? document.getElementById('feedSkillsSearch').value : '').trim();
            const target = document.getElementById('feedSkills');
            if (target) target.innerHTML = '';
            fetch('get_skills.php?scope=all&q=' + encodeURIComponent(q))
                .then(r=>r.json()).then(data => {
                    const c = document.getElementById('feedSkills');
                    if (!c) return;
                    c.innerHTML = '';
                    if (!data.success || !data.skills || data.skills.length === 0) {
                        const empty = document.createElement('div');
                        empty.style.color = '#666';
                        empty.textContent = 'No skills found.';
                        c.appendChild(empty);
                        return;
                    }
                    data.skills.forEach(s => c.appendChild(renderSkillItem(s, true)));
                });
        }

        // Header search handler -> filter skills feed
        const globalSearchInput = document.getElementById('globalSearch');
        if (globalSearchInput) {
            globalSearchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const v = (globalSearchInput.value || '').trim();
                    const input = document.getElementById('feedSkillsSearch');
                    if (input) input.value = v;
                    loadFeedSkills();
                    const card = document.getElementById('skillsFeedCard');
                    if (card) card.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }

        function focusSkillsFeed() {
            const card = document.getElementById('skillsFeedCard');
            if (card) card.scrollIntoView({ behavior: 'smooth' });
        }

        // Initial loads
        loadFeedSkills();
        // Load minimal latest skills into right sidebar
        loadAllSkills();

        // Messaging
        function openMessageModal(userId, userName) {
            const b = document.getElementById('messageBackdrop');
            document.getElementById('messageToId').value = userId || '';
            document.getElementById('messageToName').value = userName || '';
            document.getElementById('messageContent').value = '';
            b.style.display = 'flex';
        }
        function closeMessageModal() { document.getElementById('messageBackdrop').style.display = 'none'; }
        function sendMessage() {
            const rid = parseInt(document.getElementById('messageToId').value || '0', 10);
            const content = (document.getElementById('messageContent').value || '').trim();
            if (!rid || !content) { alert('Recipient and message are required'); return; }
            fetch('send_message.php', {
                method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body: 'receiver_id=' + encodeURIComponent(rid) + '&content=' + encodeURIComponent(content)
            }).then(r=>r.json()).then(data=>{
                if (data.success) { closeMessageModal(); alert('Message sent'); }
                else { alert(data.message || 'Failed to send message'); }
            });
        }

        // Messages card logic
        let currentChatUserId = 0;
        let currentChatUserName = '';
        let chatPollTimer = null;

        function searchUsers(q) {
            fetch('list_users.php?q=' + encodeURIComponent(q || ''))
                .then(r=>r.json()).then(data => {
                    const list = document.getElementById('usersList');
                    list.innerHTML = '';
                    if (!data.success || !data.users || data.users.length === 0) {
                        const empty = document.createElement('div');
                        empty.style.color = '#666';
                        empty.style.padding = '10px';
                        empty.textContent = 'No users found';
                        list.appendChild(empty);
                        return;
                    }
                    data.users.forEach(u => {
                        const name = ((u.first_name || '') + ' ' + (u.last_name || '')).trim() || (u.email || 'User');
                        const div = document.createElement('div');
                        div.className = 'user-item';
                        div.textContent = name;
                        div.onclick = () => openChat(u.id, name);
                        list.appendChild(div);
                    });
                });
        }

        function openChat(userId, name) {
            currentChatUserId = userId;
            currentChatUserName = name;
            document.getElementById('chatHeader').textContent = 'Chat with ' + name;
            document.getElementById('chatInput').disabled = false;
            document.getElementById('sendBtn').disabled = false;
            loadMessages();
            if (chatPollTimer) clearInterval(chatPollTimer);
            chatPollTimer = setInterval(loadMessages, 5000);
        }

        function loadMessages() {
            if (!currentChatUserId) return;
            fetch('get_messages.php?with_user_id=' + encodeURIComponent(currentChatUserId))
                .then(r=>r.json()).then(data => {
                    const list = document.getElementById('messagesList');
                    list.innerHTML = '';
                    if (!data.success || !data.messages) return;
                    data.messages.forEach(m => {
                        const row = document.createElement('div');
                        const isMe = (m.sender_id == CURRENT_USER_ID);
                        row.className = 'message-row' + (isMe ? ' message-me' : '');
                        const bubble = document.createElement('div');
                        bubble.className = 'message-bubble';
                        bubble.textContent = m.content;
                        row.appendChild(bubble);
                        list.appendChild(row);
                    });
                    list.scrollTop = list.scrollHeight;
                });
        }

        function sendChatMessage() {
            if (!currentChatUserId) return;
            const input = document.getElementById('chatInput');
            const content = (input.value || '').trim();
            if (!content) return;
            document.getElementById('sendBtn').disabled = true;
            fetch('send_message.php', {
                method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body: 'receiver_id=' + encodeURIComponent(currentChatUserId) + '&content=' + encodeURIComponent(content)
            }).then(r=>r.json()).then(data => {
                document.getElementById('sendBtn').disabled = false;
                if (data.success) {
                    input.value = '';
                    loadMessages();
                }
            }).catch(()=>{ document.getElementById('sendBtn').disabled = false; });
        }

        // Header-driven modals/panels
        function openSkillsModal() {
            const b = document.getElementById('skillsViewBackdrop');
            b.style.display = 'flex';
            loadMySkills();
            loadAllSkills();
        }
        function closeSkillsModal() { document.getElementById('skillsViewBackdrop').style.display = 'none'; }

        function openAddSkillModal() { document.getElementById('addSkillBackdrop').style.display = 'flex'; }
        function closeAddSkillModal() { document.getElementById('addSkillBackdrop').style.display = 'none'; }

        function openMessagesPanel() {
            const b = document.getElementById('messagesBackdrop');
            b.style.display = 'flex';
            searchUsers('');
        }
        function closeMessagesPanel() {
            const b = document.getElementById('messagesBackdrop');
            b.style.display = 'none';
            if (chatPollTimer) { clearInterval(chatPollTimer); chatPollTimer = null; }
            currentChatUserId = 0;
            document.getElementById('messagesList').innerHTML='';
            document.getElementById('chatHeader').textContent = 'Select a user';
            document.getElementById('chatInput').disabled = true;
            document.getElementById('sendBtn').disabled = true;
        }

        // Initialize form handlers
        document.addEventListener('DOMContentLoaded', function() {
            const addSkillForm = document.getElementById('addSkillForm');
            if (addSkillForm) {
                addSkillForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitSkill();
                });
            }
        });

        loadMySkills();
        loadAllSkills();
    </script>

    <!-- Message Modal -->
    <div id="messageBackdrop" class="modal-backdrop" onclick="if(event.target===this) closeMessageModal();">
        <div class="modal">
            <header>Send Message</header>
            <div class="row">
                <input type="text" id="messageToName" placeholder="Recipient" readonly>
                <input type="hidden" id="messageToId">
            </div>
            <div class="row">
                <textarea id="messageContent" placeholder="Write your message..." style="min-height:120px;"></textarea>
            </div>
            <div class="actions">
                <button class="small-btn" onclick="closeMessageModal()">Cancel</button>
                <button class="post-btn" onclick="sendMessage()">Send</button>
            </div>
        </div>
    </div>

    <!-- Skills View Modal -->
    <div id="skillsViewBackdrop" class="modal-backdrop" onclick="if(event.target===this) closeSkillsModal();" style="display:none;">
        <div class="modal" style="max-width:720px; width:95%;">
            <header>Skills</header>
            <div class="row" style="gap:12px; flex-wrap:wrap;">
                <div style="flex:1 1 260px;">
                    <div style="font-weight:600; margin-bottom:6px;">My Skills</div>
                    <div style="display:flex; gap:8px; margin-bottom:8px;">
                        <input type="text" id="mySkillsSearchModal" placeholder="Search my skills...">
                        <button class="small-btn" onclick="loadMySkills()">Search</button>
                    </div>
                    <div id="mySkillsModalList" class="skills-list" style="max-height:280px; overflow:auto;"></div>
                </div>
                <div style="flex:1 1 260px;">
                    <div style="font-weight:600; margin-bottom:6px;">All Skills</div>
                    <div style="display:flex; gap:8px; margin-bottom:8px;">
                        <input type="text" id="allSkillsSearchModal" placeholder="Search all skills...">
                        <button class="small-btn" onclick="loadAllSkills()">Search</button>
                    </div>
                    <div id="allSkillsModalList" class="skills-list" style="max-height:280px; overflow:auto;"></div>
                </div>
            </div>
            <div class="actions">
                <button class="small-btn" onclick="closeSkillsModal()">Close</button>
            </div>
        </div>
    </div>

<!-- Add Skill Modal -->
<div id="addSkillBackdrop" class="modal-backdrop" onclick="if(event.target===this) closeAddSkillModal();" style="display:none;">
    <div class="modal" style="max-width:520px; width:95%;">
        <header>Add a Skill</header>
        <form id="addSkillForm">
            <div class="row"><input type="text" id="skillName" placeholder="Skill name (e.g., JavaScript, Baking)" required></div>
            <div class="row"><input type="text" id="skillLevel" placeholder="Level (e.g., Beginner, Intermediate, Expert)"></div>
            <div class="row"><textarea id="skillDescription" placeholder="Short description (optional)" style="min-height:100px;"></textarea></div>
            <div class="row" style="display:flex; align-items:center; gap:8px;">
                <label class="post-option-btn" for="skillImage" title="Add image" style="cursor:pointer; display:flex; align-items:center;">
                    <span id="skillImageLabel">📷 Attach Image</span>
                    <input type="file" id="skillImage" name="image" accept="image/*" style="display:none;">
                </label>
            </div>
            <div class="actions">
                <button type="button" class="small-btn" onclick="closeAddSkillModal()">Cancel</button>
                <button type="submit" class="post-btn">Save Skill</button>
            </div>
        </form>
    </div>
</div>

<script>
// Handle file input change
document.getElementById('skillImage').addEventListener('change', function(e) {
    const label = document.getElementById('skillImageLabel');
    if (this.files && this.files.length > 0) {
        label.textContent = '✓ ' + this.files[0].name;
    } else {
        label.textContent = '📷 Attach Image';
    }
});

// Handle form submission
document.getElementById('addSkillForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('name', document.getElementById('skillName').value);
    formData.append('level', document.getElementById('skillLevel').value);
    formData.append('description', document.getElementById('skillDescription').value);
    
    const imageFile = document.getElementById('skillImage').files[0];
    if (imageFile) {
        formData.append('image', imageFile);
    }
    
    // Send to server
    fetch('save_skill.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Success:', data);
        closeAddSkillModal();
        // Refresh skills list or add to page
    })
    .catch(error => console.error('Error:', error));
});
</script>

    <!-- Messages Panel Modal -->
    <div id="messagesBackdrop" class="modal-backdrop" onclick="if(event.target===this) closeMessagesPanel();" style="display:none;">
        <div class="modal" style="max-width:900px; width:95%;">
            <header>Messages</header>
            <div class="messages-wrapper" style="margin-top:6px;">
                <div style="flex:0 0 auto; width:280px;">
                    <input id="userSearch" class="user-search" type="text" placeholder="Search users..." oninput="searchUsers(this.value)">
                    <div id="usersList" class="messages-users" style="height:360px;"></div>
                </div>
                <div class="messages-chat" style="height:360px;">
                    <div id="chatHeader" style="padding:10px; border-bottom:1px solid #eee; font-weight:600;">Select a user</div>
                    <div id="messagesList" class="messages-list"></div>
                    <div class="messages-input">
                        <input id="chatInput" type="text" placeholder="Type a message..." disabled onkeydown="if(event.key==='Enter'){sendChatMessage();}">
                        <button class="small-btn" onclick="sendChatMessage()" id="sendBtn" disabled>Send</button>
                    </div>
                </div>
            </div>
            <div class="actions">
                <button class="small-btn" onclick="closeMessagesPanel()">Close</button>
            </div>
        </div>
    </div>
</body>
</html>