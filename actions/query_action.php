<?php
/**
 * EcoSprout – Customer Query Action Handler
 * Actions: submit (customer), respond (admin/staff)
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

$action = $_POST['action'] ?? '';
$pdo    = getPDO();
$role   = getCurrentRole();
$userId = getCurrentUserId();

// ── Customer: Submit new query ────────────────────────────────
if ($action === 'submit') {
    requireRole(['customer']);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        setFlash('error', 'Subject and message are required.');
        redirect(getBaseUrl() . '/customer/my-queries.php');
    }
    if (mb_strlen($subject) > 250) {
        setFlash('error', 'Subject is too long (max 250 characters).');
        redirect(getBaseUrl() . '/customer/my-queries.php');
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO queries (customer_id, subject, message) VALUES (?,?,?)"
        );
        $stmt->execute([$userId, $subject, $message]);
        setFlash('success', 'Your query has been submitted! Our team will respond soon.');
        redirect(getBaseUrl() . '/customer/my-queries.php');
    } catch (PDOException $e) {
        error_log('[EcoSprout] Query submit error: ' . $e->getMessage());
        setFlash('error', 'Could not submit query. Please try again.');
        redirect(getBaseUrl() . '/customer/my-queries.php');
    }
}

// ── Staff/Admin: Respond to query ─────────────────────────────
if ($action === 'respond') {
    requireRole(['admin', 'staff']);
    $queryId  = (int) ($_POST['query_id']  ?? 0);
    $response = trim($_POST['response']    ?? '');
    $status   = $_POST['query_status']     ?? 'answered';
    $validStatuses = ['open', 'answered', 'closed'];

    if ($queryId <= 0 || empty($response)) {
        setFlash('error', 'Query ID and response text are required.');
        redirect(getBaseUrl() . '/' . $role . '/queries.php');
    }
    if (!in_array($status, $validStatuses)) {
        $status = 'answered';
    }

    try {
        $stmt = $pdo->prepare(
            "UPDATE queries SET response=?, status=?, responded_by=? WHERE id=?"
        );
        $stmt->execute([$response, $status, $userId, $queryId]);
        setFlash('success', 'Response saved and query marked as "' . ucfirst($status) . '".');
        redirect(getBaseUrl() . '/' . $role . '/queries.php');
    } catch (PDOException $e) {
        error_log('[EcoSprout] Query respond error: ' . $e->getMessage());
        setFlash('error', 'Could not save response. Please try again.');
        redirect(getBaseUrl() . '/' . $role . '/queries.php');
    }
}

// ── Admin/Staff: Update query status only ────────────────────
if ($action === 'update_status') {
    requireRole(['admin', 'staff']);
    $queryId = (int) ($_POST['query_id'] ?? 0);
    $status  = $_POST['query_status'] ?? '';
    $validStatuses = ['open', 'answered', 'closed'];

    if ($queryId <= 0 || !in_array($status, $validStatuses)) {
        setFlash('error', 'Invalid input.');
        redirect(getBaseUrl() . '/' . $role . '/queries.php');
    }

    try {
        $stmt = $pdo->prepare("UPDATE queries SET status=? WHERE id=?");
        $stmt->execute([$status, $queryId]);
        setFlash('success', 'Query status updated.');
        redirect(getBaseUrl() . '/' . $role . '/queries.php');
    } catch (PDOException $e) {
        error_log('[EcoSprout] Query status update error: ' . $e->getMessage());
        setFlash('error', 'Could not update status.');
        redirect(getBaseUrl() . '/' . $role . '/queries.php');
    }
}

setFlash('error', 'Unknown action.');
redirect(getBaseUrl() . '/index.php');
