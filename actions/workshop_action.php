<?php
/**
 * EcoSprout – Workshop Action Handler
 * POST param 'action': 'create' | 'update' | 'delete' | 'register' | 'unregister'
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBaseUrl() . '/index.php');
}

verifyCsrf();

$action  = $_POST['action'] ?? '';
$role    = getCurrentRole();
$pdo     = getPDO();
$userId  = getCurrentUserId();

// Determine return URL based on action and role
$mgmtUrl  = $role === 'admin' ? getBaseUrl() . '/admin/workshops.php' : getBaseUrl() . '/staff/workshops.php';
$custUrl  = getBaseUrl() . '/customer/my-workshops.php';
$returnUrl = in_array($role, ['admin','staff']) ? $mgmtUrl : $custUrl;

try {

    // ── Staff/Admin: Create workshop ──────────────────────────
    if ($action === 'create') {
        requireRole(['admin','staff']);
        $title    = trim($_POST['title']         ?? '');
        $desc     = trim($_POST['description']   ?? '');
        $schedule = trim($_POST['schedule_date'] ?? '');
        $capacity = (int)   ($_POST['capacity']  ?? 20);
        $fee      = (float) ($_POST['fee']       ?? 0);

        if (empty($title) || empty($schedule)) {
            setFlash('error', 'Title and schedule date are required.');
            redirect($mgmtUrl);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO workshops (title, description, schedule_date, capacity, fee) VALUES (?,?,?,?,?)"
        );
        $stmt->execute([$title, $desc, $schedule, $capacity, $fee]);
        setFlash('success', 'Workshop "' . htmlspecialchars($title) . '" created!');
        redirect($mgmtUrl);
    }

    // ── Staff/Admin: Update workshop ──────────────────────────
    if ($action === 'update') {
        requireRole(['admin','staff']);
        $id       = (int) ($_POST['workshop_id']   ?? 0);
        $title    = trim($_POST['title']           ?? '');
        $desc     = trim($_POST['description']     ?? '');
        $schedule = trim($_POST['schedule_date']   ?? '');
        $capacity = (int)   ($_POST['capacity']    ?? 20);
        $fee      = (float) ($_POST['fee']         ?? 0);

        if ($id <= 0 || empty($title) || empty($schedule)) {
            setFlash('error', 'Invalid input.');
            redirect($mgmtUrl);
        }

        $stmt = $pdo->prepare(
            "UPDATE workshops SET title=?, description=?, schedule_date=?, capacity=?, fee=? WHERE id=?"
        );
        $stmt->execute([$title, $desc, $schedule, $capacity, $fee, $id]);
        setFlash('success', 'Workshop updated!');
        redirect($mgmtUrl);
    }

    // ── Staff/Admin: Delete workshop ──────────────────────────
    if ($action === 'delete') {
        requireRole(['admin','staff']);
        $id = (int) ($_POST['workshop_id'] ?? 0);
        if ($id <= 0) { setFlash('error', 'Invalid workshop ID.'); redirect($mgmtUrl); }

        $stmt = $pdo->prepare("DELETE FROM workshops WHERE id=?");
        $stmt->execute([$id]);
        setFlash('success', 'Workshop deleted.');
        redirect($mgmtUrl);
    }

    // ── Customer: Register for workshop ──────────────────────
    if ($action === 'register') {
        requireRole(['customer']);
        $workshopId = (int) ($_POST['workshop_id'] ?? 0);
        if ($workshopId <= 0) { setFlash('error', 'Invalid workshop.'); redirect($custUrl); }

        // Check workshop exists and capacity
        $ws = $pdo->prepare("SELECT capacity FROM workshops WHERE id=?");
        $ws->execute([$workshopId]);
        $workshop = $ws->fetch();
        if (!$workshop) { setFlash('error', 'Workshop not found.'); redirect($custUrl); }

        // Count current registrations
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM workshop_registrations WHERE workshop_id=?");
        $cnt->execute([$workshopId]);
        $registered = (int) $cnt->fetchColumn();

        if ($registered >= $workshop['capacity']) {
            setFlash('error', 'Sorry, this workshop is fully booked.');
            redirect($custUrl);
        }

        // Check if already registered
        $check = $pdo->prepare("SELECT id FROM workshop_registrations WHERE workshop_id=? AND user_id=?");
        $check->execute([$workshopId, $userId]);
        if ($check->fetch()) {
            setFlash('info', 'You are already registered for this workshop.');
            redirect($custUrl);
        }

        $ins = $pdo->prepare("INSERT INTO workshop_registrations (workshop_id, user_id) VALUES (?,?)");
        $ins->execute([$workshopId, $userId]);
        setFlash('success', 'You have been registered for the workshop!');
        redirect($custUrl);
    }

    // ── Customer: Unregister from workshop ────────────────────
    if ($action === 'unregister') {
        requireRole(['customer']);
        $workshopId = (int) ($_POST['workshop_id'] ?? 0);
        if ($workshopId <= 0) { setFlash('error', 'Invalid workshop.'); redirect($custUrl); }

        $del = $pdo->prepare("DELETE FROM workshop_registrations WHERE workshop_id=? AND user_id=?");
        $del->execute([$workshopId, $userId]);
        setFlash('success', 'You have been unregistered from the workshop.');
        redirect($custUrl);
    }

    setFlash('error', 'Unknown action.');
    redirect($returnUrl);

} catch (PDOException $e) {
    error_log('[EcoSprout] Workshop action error: ' . $e->getMessage());
    setFlash('error', 'Database error. Please try again.');
    redirect($returnUrl);
}
