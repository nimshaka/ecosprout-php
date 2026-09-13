<?php
/**
 * EcoSprout – Gardening Services Action Handler
 * POST param 'action': 'create' | 'update' | 'delete'
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBaseUrl() . '/' . getCurrentRole() . '/services.php');
}

verifyCsrf();

$action    = $_POST['action'] ?? '';
$pdo       = getPDO();
$returnUrl = getCurrentRole() === 'admin'
    ? getBaseUrl() . '/admin/services.php'
    : getBaseUrl() . '/staff/services.php';

try {

    if ($action === 'create') {
        $name  = trim($_POST['service_name'] ?? '');
        $desc  = trim($_POST['description']  ?? '');
        $price = (float) ($_POST['price']    ?? 0);

        if (empty($name)) { setFlash('error', 'Service name is required.'); redirect($returnUrl); }
        if ($price < 0)   { setFlash('error', 'Price cannot be negative.');  redirect($returnUrl); }

        $stmt = $pdo->prepare("INSERT INTO gardening_services (service_name, description, price) VALUES (?,?,?)");
        $stmt->execute([$name, $desc, $price]);
        setFlash('success', 'Service "' . htmlspecialchars($name) . '" added!');
        redirect($returnUrl);
    }

    if ($action === 'update') {
        $id    = (int) ($_POST['service_id']    ?? 0);
        $name  = trim($_POST['service_name']    ?? '');
        $desc  = trim($_POST['description']     ?? '');
        $price = (float) ($_POST['price']       ?? 0);

        if ($id <= 0 || empty($name)) { setFlash('error', 'Invalid input.'); redirect($returnUrl); }

        $stmt = $pdo->prepare("UPDATE gardening_services SET service_name=?, description=?, price=? WHERE id=?");
        $stmt->execute([$name, $desc, $price, $id]);
        setFlash('success', 'Service updated successfully!');
        redirect($returnUrl);
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['service_id'] ?? 0);
        if ($id <= 0) { setFlash('error', 'Invalid service ID.'); redirect($returnUrl); }

        $stmt = $pdo->prepare("DELETE FROM gardening_services WHERE id=?");
        $stmt->execute([$id]);
        setFlash('success', 'Service deleted.');
        redirect($returnUrl);
    }

    setFlash('error', 'Unknown action.');
    redirect($returnUrl);

} catch (PDOException $e) {
    error_log('[EcoSprout] Service action error: ' . $e->getMessage());
    setFlash('error', 'Database error. Please try again.');
    redirect($returnUrl);
}
