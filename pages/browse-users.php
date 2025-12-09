<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';


$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;


$count_stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
$total_users = $count_stmt->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);


$users_stmt = $db->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM audio WHERE user_id = u.user_id) as upload_count,
           (SELECT COUNT(*) FROM subscriptions WHERE creator_id = u.user_id) as follower_count
    FROM users u
    WHERE u.is_active = 1
    ORDER BY u.join_date DESC
    LIMIT ? OFFSET ?
");
$users_stmt->bind_param("ii", $limit, $offset);
$users_stmt->execute();
$users_result = $users_stmt->get_result();

$page_title = "Browse Users";
require_once '../includes/header.php';
?>

<div class="browse-container">
    <h1>Browse Creators</h1>
    <p class="subtitle">Discover audio creators on MegaDj</p>
    
    <?php if ($users_result->num_rows > 0): ?>
        <div class="users-grid">
            <?php while ($user = $users_result->fetch_assoc()): ?>
                <div class="user-card">
                    <div class="user-avatar">
                        <a href="profile.php?id=<?php echo $user['user_id']; ?>">
                            <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($user['profile_pic']); ?>" 
                                 alt="<?php echo htmlspecialchars($user['username']); ?>">
                        </a>
                    </div>
                    
                    <div class="user-info">
                        <h3>
                            <a href="profile.php?id=<?php echo $user['user_id']; ?>">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </a>
                        </h3>
                        
                        <?php if (!empty($user['bio'])): ?>
                            <p class="user-bio"><?php echo htmlspecialchars(substr($user['bio'], 0, 100)); ?><?php echo strlen($user['bio']) > 100 ? '...' : ''; ?></p>
                        <?php endif; ?>
                        
                        <div class="user-stats">
                            <span class="stat">🎵 <?php echo $user['upload_count']; ?> uploads</span>
                            <span class="stat">👥 <?php echo $user['follower_count']; ?> followers</span>
                        </div>
                        
                        <div class="user-actions">
                            <a href="profile.php?id=<?php echo $user['user_id']; ?>" class="btn btn-small">View Profile</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="page-link">← Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="page-link active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="page-link">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="empty-state">
            <p>No users found.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>