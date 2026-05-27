<?php
// ============================================================
// G&G Support Portal — generate_hash.php
// ONE-TIME USE: Generate bcrypt password hashes.
// STEP 1: Place this file in C:\xampp\htdocs\ (NOT inside gg-support)
// STEP 2: Visit http://localhost/generate_hash.php
// STEP 3: Copy the hash shown
// STEP 4: In phpMyAdmin run: UPDATE knowledgebase.users SET password = 'PASTE_HASH_HERE';
// STEP 5: DELETE THIS FILE IMMEDIATELY after use.
// ============================================================

// Block access if not on localhost
$allowed = ['127.0.0.1', '::1', 'localhost'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed)) {
    http_response_code(403);
    die('Access denied.');
}

$password = 'Test@1234';
$hash     = password_hash($password, PASSWORD_BCRYPT);
$verify   = password_verify($password, $hash) ? '✅ Hash verified OK' : '❌ Hash verification FAILED';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hash Generator</title>
    <style>
        body { font-family: monospace; padding: 2rem; background: #f8fafc; }
        .box { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; max-width: 700px; }
        .hash { word-break: break-all; background: #f1f5f9; padding: 1rem; border-radius: 6px; font-size: .95rem; margin: 1rem 0; }
        .warning { color: #dc2626; font-weight: bold; margin-top: 1rem; }
        h2 { margin-bottom: .5rem; }
        p  { margin-bottom: .5rem; }
    </style>
</head>
<body>
<div class="box">
    <h2>Password Hash Generator</h2>
    <p>Password: <strong><?= htmlspecialchars($password) ?></strong></p>
    <p>Status: <?= $verify ?></p>
    <p><strong>Hash (copy everything inside the box):</strong></p>
    <div class="hash"><?= htmlspecialchars($hash) ?></div>
    <p>Run this SQL in phpMyAdmin:</p>
    <div class="hash">UPDATE knowledgebase.users SET password = '<?= htmlspecialchars($hash) ?>';</div>
    <p class="warning">⚠️ DELETE THIS FILE IMMEDIATELY after copying the hash!</p>
    <p class="warning">File location to delete: C:\xampp\htdocs\generate_hash.php</p>
</div>
</body>
</html>
