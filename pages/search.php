<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all'; 
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$results = [];
$total_results = 0;
$total_pages = 0;

if (!empty($query)) {
    $search_query = '%' . $db->escape($query) . '%';
    
    if ($type === 'users' || $type === 'all') {
       
        $users_stmt = $db->prepare("
            SELECT u.*,
                   (SELECT COUNT(*) FROM audio WHERE user_id = u.user_id) as upload_count,
                   (SELECT COUNT(*) FROM subscriptions WHERE creator_id = u.user_id) as follower_count
            FROM users u
            WHERE u.is_active = 1 
            AND (u.username LIKE ? OR u.email LIKE ?)
            ORDER BY u.username
            LIMIT ? OFFSET ?
        ");
        $users_stmt->bind_param("ssii", $search_query, $search_query, $limit, $offset);
        $users_stmt->execute();
        $users_result = $users_stmt->get_result();
        
        $results['users'] = [];
        while ($user = $users_result->fetch_assoc()) {
            $results['users'][] = $user;
        }
        
        
        $count_stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE is_active = 1 
            AND (username LIKE ? OR email LIKE ?)
        ");
        $count_stmt->bind_param("ss", $search_query, $search_query);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $user_count = $count_result->fetch_assoc()['count'];
        $total_results += $user_count;
    }
    
    if ($type === 'audio' || $type === 'all') {
      
        $audio_stmt = $db->prepare("
            SELECT a.*, u.username, u.profile_pic,
                   COUNT(DISTINCT l.like_id) as like_count,
                   COUNT(DISTINCT c.comment_id) as comment_count
            FROM audio a
            JOIN users u ON a.user_id = u.user_id
            LEFT JOIN likes l ON a.audio_id = l.audio_id
            LEFT JOIN comments c ON a.audio_id = c.audio_id
            WHERE a.title LIKE ? OR a.description LIKE ? OR a.genre LIKE ?
            GROUP BY a.audio_id
            ORDER BY a.upload_date DESC
            LIMIT ? OFFSET ?
        ");
        $audio_stmt->bind_param("ssiii", $search_query, $search_query, $search_query, $limit, $offset);
        $audio_stmt->execute();
        $audio_result = $audio_stmt->get_result();
        
        $results['audio'] = [];
        while ($audio = $audio_result->fetch_assoc()) {
            $results['audio'][] = $audio;
        }
        
       
        $count_stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM audio 
            WHERE title LIKE ? OR description LIKE ? OR genre LIKE ?
        ");
        $count_stmt->bind_param("sss", $search_query, $search_query, $search_query);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $audio_count = $count_result->fetch_assoc()['count'];
        $total_results += $audio_count;
    }
    
    $total_pages = ceil($total_results / $limit);
}

$page_title = "Search Results";
require_once '../includes/header.php';
?>

<div class="search-container">
    <h1>Search</h1>
    
   
    <div class="search-box-large">
        <form action="search.php" method="GET" class="search-form">
            <div class="search-input-group">
                <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" 
                       placeholder="Search for users, audio, genres..." 
                       class="search-input">
                <button type="submit" class="search-button">Search</button>
            </div>
            
            <div class="search-filters">
                <label class="filter-option">
                    <input type="radio" name="type" value="all" <?php echo ($type === 'all') ? 'checked' : ''; ?>>
                    <span>All</span>
                </label>
                <label class="filter-option">
                    <input type="radio" name="type" value="users" <?php echo ($type === 'users') ? 'checked' : ''; ?>>
                    <span>Users</span>
                </label>
                <label class="filter-option">
                    <input type="radio" name="type" value="audio" <?php echo ($type === 'audio') ? 'checked' : ''; ?>>
                    <span>Audio</span>
                </label>
            </div>
        </form>
    </div>
    
  
    <?php if (!empty($query)): ?>
        <div class="search-results">
            <h2>Results for "<?php echo htmlspecialchars($query); ?>"</h2>
            <p class="results-count"><?php echo number_format($total_results); ?> results found</p>
            
            <?php if ($total_results == 0): ?>
                <div class="empty-state">
                    <p>No results found. Try different keywords.</p>
                </div>
            <?php else: ?>
                
                <?php if (isset($results['users']) && count($results['users']) > 0): ?>
                    <div class="results-section">
                        <h3>Users (<?php echo isset($user_count) ? $user_count : count($results['users']); ?>)</h3>
                        <div class="users-grid">
                            <?php foreach ($results['users'] as $user_result): ?>
                                <div class="user-card">
                                    <div class="user-avatar">
                                        <a href="profile.php?id=<?php echo $user_result['user_id']; ?>">
                                            <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($user_result['profile_pic']); ?>" 
                                                 alt="<?php echo htmlspecialchars($user_result['username']); ?>">
                                        </a>
                                    </div>
                                    
                                    <div class="user-info">
                                        <h4>
                                            <a href="profile.php?id=<?php echo $user_result['user_id']; ?>">
                                                <?php echo htmlspecialchars($user_result['username']); ?>
                                            </a>
                                        </h4>
                                        
                                        <div class="user-stats">
                                            <span class="stat">🎵 <?php echo $user_result['upload_count']; ?> uploads</span>
                                            <span class="stat">👥 <?php echo $user_result['follower_count']; ?> followers</span>
                                        </div>
                                        
                                        <div class="user-actions">
                                            <a href="profile.php?id=<?php echo $user_result['user_id']; ?>" class="btn btn-small">View Profile</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
           
                <?php if (isset($results['audio']) && count($results['audio']) > 0): ?>
                    <div class="results-section">
                        <h3>Audio (<?php echo isset($audio_count) ? $audio_count : count($results['audio']); ?>)</h3>
                        <div class="audio-grid">
                            <?php foreach ($results['audio'] as $audio): ?>
                                <div class="audio-card">
                                    <div class="audio-cover">
                                        <img src="<?php echo SITE_URL; ?>/uploads/covers/<?php echo htmlspecialchars($audio['cover_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($audio['title']); ?>">
                                        <div class="play-overlay">
                                            <span>▶</span>
                                        </div>
                                    </div>
                                    <div class="audio-info">
                                        <h4>
                                            <a href="audio-player.php?id=<?php echo $audio['audio_id']; ?>">
                                                <?php echo htmlspecialchars($audio['title']); ?>
                                            </a>
                                        </h4>
                                        <p class="audio-creator">
                                            <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($audio['profile_pic']); ?>" 
                                                 alt="<?php echo htmlspecialchars($audio['username']); ?>" class="creator-avatar">
                                            <?php echo htmlspecialchars($audio['username']); ?>
                                        </p>
                                        <div class="audio-stats">
                                            <span>▶ <?php echo number_format($audio['play_count']); ?></span>
                                            <span>❤ <?php echo number_format($audio['like_count']); ?></span>
                                            <span>💬 <?php echo number_format($audio['comment_count']); ?></span>
                                        </div>
                                        <span class="audio-genre"><?php echo htmlspecialchars($audio['genre']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
              
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&type=<?php echo $type; ?>&page=<?php echo $page - 1; ?>" 
                               class="page-link">← Previous</a>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="page-link active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?q=<?php echo urlencode($query); ?>&type=<?php echo $type; ?>&page=<?php echo $i; ?>" 
                                   class="page-link"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&type=<?php echo $type; ?>&page=<?php echo $page + 1; ?>" 
                               class="page-link">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
     
        <div class="search-empty">
            <p>Enter a search term to find users or audio.</p>
            <p class="search-tips">
                <strong>Search tips:</strong><br>
                • Search by username<br>
                • Search by audio title<br>
                • Search by genre (e.g., "rock", "podcast")<br>
                • Search by description keywords
            </p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>