<?php
// ============================================================
// includes/ticket_helpers.php
// Helper functions for ticket assignment, SLA, escalation
// ============================================================

function assignTicketToLevel(PDO $pdo, int $ticketId, int $levelId): int|false {
    $stmt = $pdo->prepare("
        SELECT tla.user_id
        FROM ticket_level_admins tla
        INNER JOIN users u ON u.id = tla.user_id AND u.is_active = 1
        WHERE tla.level_id = ?
        ORDER BY tla.id ASC
    ");
    $stmt->execute([$levelId]);
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($admins)) return false;

    $ptr = $pdo->prepare("SELECT last_admin_index FROM round_robin_pointer WHERE level_id = ?");
    $ptr->execute([$levelId]);
    $lastIndex = (int)($ptr->fetchColumn() ?? 0);
    $nextIndex = ($lastIndex + 1) % count($admins);
    $assignedAdmin = $admins[$nextIndex];

    $pdo->prepare("UPDATE round_robin_pointer SET last_admin_index = ? WHERE level_id = ?")
        ->execute([$nextIndex, $levelId]);

    $sla = $pdo->prepare("SELECT attend_sla, resolve_sla FROM ticket_levels WHERE id = ?");
    $sla->execute([$levelId]);
    $slaRow = $sla->fetch(PDO::FETCH_ASSOC);

    $now = new DateTime();
    $attendDeadline  = (clone $now)->modify("+{$slaRow['attend_sla']} minutes");
    $resolveDeadline = (clone $now)->modify("+{$slaRow['resolve_sla']} minutes");

    $pdo->prepare("
        UPDATE tickets
        SET current_level = ?, current_admin = ?,
            attend_deadline = ?, resolve_deadline = ?, status = 'open'
        WHERE id = ?
    ")->execute([
        $levelId, $assignedAdmin,
        $attendDeadline->format('Y-m-d H:i:s'),
        $resolveDeadline->format('Y-m-d H:i:s'),
        $ticketId
    ]);

    logTicketActivity($pdo, $ticketId, null, 'assigned', $levelId, $assignedAdmin,
        $attendDeadline->format('Y-m-d H:i:s'),
        $resolveDeadline->format('Y-m-d H:i:s'));

    return $assignedAdmin;
}

function escalateTicket(PDO $pdo, int $ticketId): bool {
    $ticket = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
    $ticket->execute([$ticketId]);
    $t = $ticket->fetch(PDO::FETCH_ASSOC);
    if (!$t) return false;

    $admins = $pdo->prepare("
        SELECT tla.user_id FROM ticket_level_admins tla
        INNER JOIN users u ON u.id = tla.user_id AND u.is_active = 1
        WHERE tla.level_id = ? ORDER BY tla.id ASC
    ");
    $admins->execute([$t['current_level']]);
    $levelAdmins = $admins->fetchAll(PDO::FETCH_COLUMN);

    if (count($levelAdmins) > 1) {
        $currentPos = array_search($t['current_admin'], $levelAdmins);
        $nextPos = ($currentPos !== false) ? ($currentPos + 1) % count($levelAdmins) : 0;
        if ($nextPos !== $currentPos) {
            assignAdminToTicket($pdo, $t, $t['current_level'], $levelAdmins[$nextPos], 'escalated');
            return true;
        }
    }

    $nextLevel = $pdo->prepare("
        SELECT id FROM ticket_levels WHERE level_order > (
            SELECT level_order FROM ticket_levels WHERE id = ?
        ) ORDER BY level_order ASC LIMIT 1
    ");
    $nextLevel->execute([$t['current_level']]);
    $nextLevelId = $nextLevel->fetchColumn();

    if ($nextLevelId) {
        assignTicketToLevel($pdo, $ticketId, (int)$nextLevelId);
        return true;
    }

    $pdo->prepare("UPDATE tickets SET status = 'unattended' WHERE id = ?")->execute([$ticketId]);
    logTicketActivity($pdo, $ticketId, null, 'unattended', null, null, null, null,
        'No further levels. Ticket unattended.');
    return false;
}

function assignAdminToTicket(PDO $pdo, array $ticket, int $levelId, int $adminId, string $action): void {
    $sla = $pdo->prepare("SELECT attend_sla, resolve_sla FROM ticket_levels WHERE id = ?");
    $sla->execute([$levelId]);
    $slaRow = $sla->fetch(PDO::FETCH_ASSOC);
    $now = new DateTime();
    $attendDeadline  = (clone $now)->modify("+{$slaRow['attend_sla']} minutes");
    $resolveDeadline = (clone $now)->modify("+{$slaRow['resolve_sla']} minutes");
    $pdo->prepare("UPDATE tickets SET current_admin = ?, attend_deadline = ?, resolve_deadline = ? WHERE id = ?")
        ->execute([$adminId, $attendDeadline->format('Y-m-d H:i:s'), $resolveDeadline->format('Y-m-d H:i:s'), $ticket['id']]);
    logTicketActivity($pdo, $ticket['id'], null, $action, $levelId, $adminId,
        $attendDeadline->format('Y-m-d H:i:s'), $resolveDeadline->format('Y-m-d H:i:s'));
}

function logTicketActivity(PDO $pdo, int $ticketId, ?int $actorId, string $action,
    ?int $levelId=null, ?int $adminId=null,
    ?string $attendDeadline=null, ?string $resolveDeadline=null, ?string $notes=null): void {
    $pdo->prepare("
        INSERT INTO ticket_activity
            (ticket_id, actor_id, action, level_id, admin_id, attend_deadline, resolve_deadline, actual_time, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ")->execute([$ticketId, $actorId, $action, $levelId, $adminId, $attendDeadline, $resolveDeadline, $notes]);
}

function runSlaCheck(PDO $pdo): void {
    $stmt = $pdo->query("SELECT id FROM tickets WHERE status='open' AND attend_deadline < NOW() AND attended_at IS NULL");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) escalateTicket($pdo, (int)$id);

    $stmt = $pdo->query("SELECT id FROM tickets WHERE status='in_progress' AND resolve_deadline < NOW() AND resolved_at IS NULL");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) escalateTicket($pdo, (int)$id);
}

function getStatusBadge(string $status): string {
    $map = [
        'open'        => ['Open',        'badge-open'],
        'in_progress' => ['In Progress', 'badge-progress'],
        'resolved'    => ['Resolved',    'badge-resolved'],
        'unresolved'  => ['Unresolved',  'badge-unresolved'],
        'unattended'  => ['Unattended',  'badge-unattended'],
    ];
    $s = $map[$status] ?? [$status, 'badge-open'];
    return "<span class=\"badge {$s[1]}\">{$s[0]}</span>";
}
