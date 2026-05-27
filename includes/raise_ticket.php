<?php
// includes/raise_ticket.php — AJAX: user raises a ticket
// FIXED: robust error handling, no silent crashes

// Catch ALL errors and return JSON instead of crashing
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => "PHP Error: $errstr in $errfile line $errline"]);
    exit;
});
set_exception_handler(function($e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => "Exception: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine()]);
    exit;
});

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/ticket_helpers.php';

guard_require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']); exit;
}

// CSRF check — csrf_verify() dies with http 403 which breaks JSON.
// Do it manually instead so we always return JSON.
$token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh the page and try again.']); exit;
}

$title       = trim($_POST['title']       ?? '');
$description = trim($_POST['description'] ?? '');
$categoryId  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

if (!$title || !$description) {
    echo json_encode(['success' => false, 'message' => 'Title and description are required.']); exit;
}

// Get lowest-order level
$firstLevel = $pdo->query("SELECT id FROM ticket_levels ORDER BY level_order ASC LIMIT 1")->fetchColumn();
if (!$firstLevel) {
    echo json_encode(['success' => false, 'message' => 'No ticket levels configured yet. Ask the system admin to set up ticket levels first.']); exit;
}

$pdo->prepare("INSERT INTO tickets (title, description, category_id, raised_by) VALUES (?,?,?,?)")
    ->execute([$title, $description, $categoryId, $_SESSION['user_id']]);
$ticketId = (int)$pdo->lastInsertId();

logTicketActivity($pdo, $ticketId, $_SESSION['user_id'], 'raised', null, null, null, null, 'Ticket raised by user.');

$assigned = assignTicketToLevel($pdo, $ticketId, (int)$firstLevel);

if ($assigned === false) {
    echo json_encode(['success' => true, 'message' => 'Ticket raised! No admins assigned to Level 1 yet — an admin will pick it up soon.', 'ticket_id' => $ticketId]);
} else {
    echo json_encode(['success' => true, 'message' => 'Ticket raised successfully!', 'ticket_id' => $ticketId]);
}