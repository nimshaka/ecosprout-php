<?php
/**
 * EcoSprout – Cart Action Handler
 * Actions: add | update | remove | clear
 *
 * ASSUMPTION: Cart is stored in the PHP session as an associative array:
 *   $_SESSION['cart'] = [ plant_id => quantity, … ]
 * This is a session-based cart; no database table is required.
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['customer']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBaseUrl() . '/customer/cart.php');
}

verifyCsrf();

$action    = $_POST['action']   ?? '';
$plantId   = (int) ($_POST['plant_id'] ?? 0);
$quantity  = max(1, (int) ($_POST['quantity'] ?? 1));
$returnUrl = $_POST['return_url'] ?? getBaseUrl() . '/customer/cart.php';
$pdo       = getPDO();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

try {

    if ($action === 'add') {
        if ($plantId <= 0) { setFlash('error', 'Invalid plant.'); redirect($returnUrl); }

        // Verify plant exists and has stock
        $stmt = $pdo->prepare("SELECT id, plant_name, stock_quantity FROM plants WHERE id=?");
        $stmt->execute([$plantId]);
        $plant = $stmt->fetch();

        if (!$plant) { setFlash('error', 'Plant not found.'); redirect($returnUrl); }

        $current   = $_SESSION['cart'][$plantId] ?? 0;
        $newQty    = $current + $quantity;

        if ($newQty > $plant['stock_quantity']) {
            setFlash('warning', 'Not enough stock available. Available: ' . $plant['stock_quantity']);
            redirect($returnUrl);
        }

        $_SESSION['cart'][$plantId] = $newQty;
        setFlash('success', '"' . htmlspecialchars($plant['plant_name']) . '" added to cart!');
        redirect($returnUrl);
    }

    if ($action === 'update') {
        if ($plantId <= 0) { redirect(getBaseUrl() . '/customer/cart.php'); }

        if ($quantity < 1) {
            unset($_SESSION['cart'][$plantId]);
        } else {
            // Validate against stock
            $stmt = $pdo->prepare("SELECT stock_quantity FROM plants WHERE id=?");
            $stmt->execute([$plantId]);
            $plant = $stmt->fetch();

            if ($plant && $quantity <= $plant['stock_quantity']) {
                $_SESSION['cart'][$plantId] = $quantity;
            } else {
                setFlash('warning', 'Quantity exceeds available stock.');
            }
        }
        redirect(getBaseUrl() . '/customer/cart.php');
    }

    if ($action === 'remove') {
        unset($_SESSION['cart'][$plantId]);
        setFlash('info', 'Item removed from cart.');
        redirect(getBaseUrl() . '/customer/cart.php');
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        setFlash('info', 'Cart cleared.');
        redirect(getBaseUrl() . '/customer/cart.php');
    }

    redirect(getBaseUrl() . '/customer/cart.php');

} catch (PDOException $e) {
    error_log('[EcoSprout] Cart error: ' . $e->getMessage());
    setFlash('error', 'A system error occurred.');
    redirect(getBaseUrl() . '/customer/cart.php');
}
