<?php
// ============================================================
// G&G Support Portal — admin/dashboard.php
// Admin home: stats, pending solutions, open flags
// ============================================================

require_once __DIR__ . '/../includes/auth_guard.php';
guard_require_login();
guard_require_role(['admin', 'system_admin']);

$page_title = 'Admin Dashboard';

// Stats
$total_solutions   = (int)$pdo->query('SELECT COUNT(*) FROM solutions WHERE status = "approved"')->fetchColumn();
$pending_solutions = (int)$pdo->query('SELECT COUNT(*) FROM solutions WHERE status = "pending"')->fetchColumn();
$open_flags        = (int)$pdo->query('SELECT COUNT(*) FROM flags WHERE status = "open"')->fetchColumn();
$total_categories  = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();

// Recent pending solutions
$pending = $pdo->query(
    'SELECT s.id, s.question, s.status, u.full_name AS submitted_by, s.created_at
     FROM solutions s
     LEFT JOIN users u ON s.submitted_by = u.id
     WHERE s.status = "pending"
     ORDER BY s.created_at DESC
     LIMIT 5'
)->fetchAll();

// Recent open flags
$flags = $pdo->query(
    'SELECT f.id, f.question, u.full_name AS raised_by, f.created_at
     FROM flags f
     LEFT JOIN users u ON f.raised_by = u.id
     WHERE f.status = "open"
     ORDER BY f.created_at DESC
     LIMIT 5'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Stats cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">💡</div>
        <div class="stat-number"><?= $total_solutions ?></div>
        <div class="stat-label">Approved Solutions</div>
    </div>
    <div class="stat-card stat-warning">
        <div class="stat-icon">⏳</div>
        <div class="stat-number"><?= $pending_solutions ?></div>
        <div class="stat-label">Pending Review</div>
    </div>
    <div class="stat-card stat-danger">
        <div class="stat-icon">🚩</div>
        <div class="stat-number"><?= $open_flags ?></div>
        <div class="stat-label">Open Flags</div>
    </div>
    <div class="stat-card stat-info">
        <div class="stat-icon">📁</div>
        <div class="stat-number"><?= $total_categories ?></div>
        <div class="stat-label">Categories</div>
    </div>
</div>

<!-- Quick actions -->
<div class="quick-actions">
    <a href="<?= BASE_URL ?>/admin/solutions.php" class="btn btn-primary">+ Add Solution</a>
    <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-secondary">+ Add Category</a>
    <a href="<?= BASE_URL ?>/admin/announcements.php" class="btn btn-secondary">+ Announcement</a>
    <a href="<?= BASE_URL ?>/admin/pending_flags.php" class="btn btn-warning">View Flags</a>
</div>

<div class="two-col-grid">

    <!-- Pending solutions -->
    <div class="card">
        <div class="card-header">
            <h2>⏳ Pending Solutions</h2>
            <a href="<?= BASE_URL ?>/admin/solutions.php?filter=pending" class="btn btn-sm">View all</a>
        </div>
        <?php if ($pending): ?>
        <table class="data-table">
            <thead><tr><th>Question</th><th>Submitted By</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($pending as $s): ?>
            <tr>
                <td><?= htmlspecialchars(substr($s['question'], 0, 60)) ?>…</td>
                <td><?= htmlspecialchars($s['submitted_by'] ?? 'Unknown') ?></td>
                <td><a href="<?= BASE_URL ?>/admin/solutions.php?edit=<?= $s['id'] ?>" class="btn btn-sm btn-primary">Review</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="empty-state">✅ No pending solutions.</p>
        <?php endif; ?>
    </div>

    <!-- Open flags -->
    <div class="card">
        <div class="card-header">
            <h2>🚩 Open Flags</h2>
            <a href="<?= BASE_URL ?>/admin/pending_flags.php" class="btn btn-sm">View all</a>
        </div>
        <?php if ($flags): ?>
        <table class="data-table">
            <thead><tr><th>Question</th><th>Raised By</th></tr></thead>
            <tbody>
            <?php foreach ($flags as $f): ?>
            <tr>
                <td><?= htmlspecialchars(substr($f['question'], 0, 60)) ?>…</td>
                <td><?= htmlspecialchars($f['raised_by'] ?? 'Unknown') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="empty-state">✅ No open flags.</p>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
