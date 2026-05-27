<?php
// ============================================================
// G&G Support Portal — user_home.php
// ============================================================

require_once __DIR__ . '/includes/auth_guard.php';
guard_require_login();

$page_title = 'Search Solutions';

// Fetch top 3 active announcements
$announcements = $pdo->query(
    'SELECT title, content FROM announcements WHERE is_active = 1 ORDER BY priority ASC LIMIT 3'
)->fetchAll();

// Fetch top-level categories for submit answer form
$categories = $pdo->query(
    'SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name ASC'
)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Moving Announcement ticker ───────────────────────── -->
<?php if ($announcements): ?>
<div class="ticker-wrap">
    <div class="ticker-label">📢 Notices</div>
    <div class="ticker-track">
        <div class="ticker-content" id="tickerContent">
            <?php foreach ($announcements as $ann): ?>
            <span class="ticker-item">
                <strong><?= htmlspecialchars($ann['title']) ?>:</strong>
                <?= htmlspecialchars(strip_tags($ann['content'])) ?>
            </span>
            <?php endforeach; ?>
            <!-- Duplicate for seamless loop -->
            <?php foreach ($announcements as $ann): ?>
            <span class="ticker-item">
                <strong><?= htmlspecialchars($ann['title']) ?>:</strong>
                <?= htmlspecialchars(strip_tags($ann['content'])) ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Greeting + Search box ────────────────────────────── -->
<div class="search-hero">
    <h2 class="search-hero-title">What can we help you with?</h2>
    <p class="search-hero-sub">Search our knowledge base for answers to common questions.</p>
    <div class="search-box-wrap">
        <span class="search-icon">🔍</span>
        <input
            type="text"
            id="searchInput"
            class="search-input"
            placeholder="Type your question here..."
            autocomplete="off"
        >
    </div>
</div>

<div id="searchResults" class="search-results hidden"></div>
<div id="noResults" class="no-results hidden">
    <div class="no-results-icon">🤔</div>
    <p><strong>No results found.</strong><br>Would you like to raise a flag or submit an answer?</p>
    <div class="action-buttons">
        <button class="btn btn-warning" onclick="openModal('flagModal')">🚩 Raise a Flag</button>
        <button class="btn btn-success" onclick="openModal('answerModal')">💡 Submit an Answer</button>
    </div>
</div>

<!-- ── Flag Modal ────────────────────────────────────────── -->
<div id="flagModal" class="modal-overlay hidden">
    <div class="modal-box">
        <div class="modal-header">
            <h2>🚩 Raise a Flag</h2>
            <button class="modal-close" onclick="closeModal('flagModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom:1rem;color:var(--text-muted)">No answer found? Let us know your question and an admin will look into it.</p>
            <div class="form-group">
                <label>Your question</label>
                <textarea id="flagQuestion" class="form-control" rows="4" placeholder="Describe what you were looking for..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('flagModal')">Cancel</button>
            <button class="btn btn-warning" onclick="submitFlag()">Submit Flag</button>
        </div>
        <div id="flagMsg" class="form-msg hidden"></div>
    </div>
</div>

<!-- ── Answer Modal ──────────────────────────────────────── -->
<div id="answerModal" class="modal-overlay hidden">
    <div class="modal-box">
        <div class="modal-header">
            <h2>💡 Submit an Answer</h2>
            <button class="modal-close" onclick="closeModal('answerModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom:1rem;color:var(--text-muted)">Know the answer? Submit it for admin review.</p>
            <div class="form-group">
                <label>Question</label>
                <input type="text" id="answerQuestion" class="form-control" placeholder="The question...">
            </div>
            <div class="form-group">
                <label>Category <span style="color:var(--text-muted);font-weight:400">(optional)</span></label>
                <select id="answerCategory" class="form-control">
                    <option value="">— Select category —</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Your answer</label>
                <textarea id="answerText" class="form-control" rows="5" placeholder="Provide the answer..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('answerModal')">Cancel</button>
            <button class="btn btn-success" onclick="submitAnswer()">Submit Answer</button>
        </div>
        <div id="answerMsg" class="form-msg hidden"></div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
