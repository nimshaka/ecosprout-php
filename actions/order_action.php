<?php
/**
 * EcoSprout – Order Action Handler
 * Actions: checkout (customer), update_status (admin/staff)
 *
 * ASSUMPTION (Payment Simulation):
 * This application does NOT integrate a real payment gateway.
 * On checkout, the payment is immediately marked as 'paid' to simulate
 * a successful payment. A real implementation would redirect to a
 * payment provider (e.g., PayHere, Stripe) and update status via webhook.
 * This assumption is documented here and in the project report.
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

// ── Customer: Place order (checkout) ─────────────────────────
if ($action === 'checkout') {
    requireRole(['customer']);
    $userId = getCurrentUserId();

    if (empty($_SESSION['cart'])) {
        setFlash('error', 'Your cart is empty.');
        redirect(getBaseUrl() . '/customer/cart.php');
    }

    try {
        $pdo->beginTransaction();

        $totalAmount = 0;
        $plantIds    = array_keys($_SESSION['cart']);

        // Fetch all plant prices and stock in one query
        $placeholders = implode(',', array_fill(0, count($plantIds), '?'));
        $stmt = $pdo->prepare("SELECT id, plant_name, price, stock_quantity FROM plants WHERE id IN ($placeholders)");
        $stmt->execute($plantIds);
        $plantsMap = [];
        while ($row = $stmt->fetch()) {
            $plantsMap[$row['id']] = $row;
        }

        // Validate stock and compute total
        foreach ($_SESSION['cart'] as $plantId => $qty) {
            $plant = $plantsMap[$plantId] ?? null;
            if (!$plant) {
                $pdo->rollBack();
                setFlash('error', 'A plant in your cart is no longer available. Please update your cart.');
                redirect(getBaseUrl() . '/customer/cart.php');
            }
            if ($qty > $plant['stock_quantity']) {
                $pdo->rollBack();
                setFlash('error', '"' . htmlspecialchars($plant['plant_name']) . '" has only ' . $plant['stock_quantity'] . ' in stock.');
                redirect(getBaseUrl() . '/customer/cart.php');
            }
            $totalAmount += $plant['price'] * $qty;
        }

        // Create the order
        // ASSUMPTION: Payment is simulated as immediately 'paid'
        $orderStmt = $pdo->prepare(
            "INSERT INTO orders (customer_id, total_amount, payment_status, order_status)
             VALUES (?, ?, 'paid', 'pending')"
        );
        $orderStmt->execute([$userId, $totalAmount]);
        $orderId = (int) $pdo->lastInsertId();

        // Insert order items and update stock
        $itemStmt = $pdo->prepare(
            "INSERT INTO order_items (order_id, plant_id, quantity, unit_price) VALUES (?,?,?,?)"
        );
        $stockStmt = $pdo->prepare(
            "UPDATE plants SET stock_quantity = stock_quantity - ? WHERE id = ?"
        );

        foreach ($_SESSION['cart'] as $plantId => $qty) {
            $price = $plantsMap[$plantId]['price'];
            $itemStmt->execute([$orderId, $plantId, $qty, $price]);
            $stockStmt->execute([$qty, $plantId]);
        }

        $pdo->commit();

        // Clear the cart after successful order
        $_SESSION['cart'] = [];

        setFlash('success', 'Order placed successfully! Your order ID is #' . $orderId . '. Payment simulated as successful.');
        redirect(getBaseUrl() . '/customer/my-orders.php?new_order=' . $orderId);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('[EcoSprout] Checkout error: ' . $e->getMessage());
        setFlash('error', 'Order could not be placed. Please try again.');
        redirect(getBaseUrl() . '/customer/cart.php');
    }
}

// ── Admin/Staff: Update order status ─────────────────────────
if ($action === 'update_status') {
    requireRole(['admin', 'staff']);
    $orderId  = (int) ($_POST['order_id'] ?? 0);
    $newStatus = $_POST['order_status'] ?? '';
    $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];

    if ($orderId <= 0 || !in_array($newStatus, $validStatuses)) {
        setFlash('error', 'Invalid input.');
        redirect(getBaseUrl() . '/' . getCurrentRole() . '/orders.php');
    }

    try {
        $stmt = $pdo->prepare("UPDATE orders SET order_status=? WHERE id=?");
        $stmt->execute([$newStatus, $orderId]);
        setFlash('success', 'Order #' . $orderId . ' status updated to "' . ucfirst($newStatus) . '".');
        redirect(getBaseUrl() . '/' . getCurrentRole() . '/orders.php');
    } catch (PDOException $e) {
        error_log('[EcoSprout] Order update error: ' . $e->getMessage());
        setFlash('error', 'Could not update order status.');
        redirect(getBaseUrl() . '/' . getCurrentRole() . '/orders.php');
    }
}

setFlash('error', 'Unknown action.');
redirect(getBaseUrl() . '/index.php');
