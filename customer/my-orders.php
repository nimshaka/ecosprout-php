<?php
/**
 * EcoSprout – Customer My Orders History
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['customer']);

$pdo    = getPDO();
$base   = getBaseUrl();
$userId = getCurrentUserId();

// Fetch customer's orders
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE customer_id = ? 
    ORDER BY order_date DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// Fetch items for customer's orders
$itemsStmt = $pdo->prepare("
    SELECT oi.*, p.plant_name, p.botanical_name, p.image
    FROM order_items oi
    JOIN plants p ON oi.plant_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.customer_id = ?
    ORDER BY oi.id ASC
");
$itemsStmt->execute([$userId]);
$orderItemsMap = [];
while ($item = $itemsStmt->fetch()) {
    $orderItemsMap[$item['order_id']][] = $item;
}

$pageHeading = 'My Purchase History';
$activePage  = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageHeading) ?> | EcoSprout</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

    <div class="main-content">
        <?php require_once __DIR__ . '/../components/navbar.php'; ?>

        <div class="content-area">
            <?= renderFlash() ?>

            <!-- Page Title -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h2 class="h3 mb-1 text-dark fw-bold"><?= htmlspecialchars($pageHeading) ?></h2>
                    <p class="text-muted mb-0">Review past nursery plant orders, track shipping/delivery progress, and print receipts.</p>
                </div>
                <div>
                    <a href="<?= $base ?>/customer/catalogue.php" class="btn btn-primary shadow-sm">
                        <i class="bi bi-flower1 me-1"></i> Order More Plants
                    </a>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-bag-x display-4 text-muted mb-3 d-block"></i>
                        <h5>No Plant Orders Found</h5>
                        <p class="text-muted">You haven't purchased any plants from EcoSprout yet.</p>
                        <a href="<?= $base ?>/customer/catalogue.php" class="btn btn-primary">Start Shopping</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($orders as $ord): 
                        $items = $orderItemsMap[$ord['id']] ?? [];
                        $st = $ord['order_status'];
                        $badgeClass = match($st) {
                            'completed'  => 'bg-success text-white',
                            'processing' => 'bg-info text-dark',
                            'cancelled'  => 'bg-danger text-white',
                            default      => 'bg-warning text-dark'
                        };
                    ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-3 border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                                    <div>
                                        <span class="fw-bold text-primary fs-5">Order #<?= str_pad($ord['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                        <span class="text-muted small ms-2">Placed on <?= date('M d, Y h:i A', strtotime($ord['order_date'])) ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                            <?= strtoupper($ord['payment_status']) ?>
                                        </span>
                                        <span class="badge <?= $badgeClass ?> rounded-pill text-capitalize">
                                            Status: <?= htmlspecialchars($st) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Plant Variety</th>
                                                    <th class="text-center">Quantity</th>
                                                    <th class="text-end">Unit Price</th>
                                                    <th class="text-end">Item Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $it): 
                                                    $subtotal = $it['quantity'] * $it['unit_price'];
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <strong class="text-dark"><?= htmlspecialchars($it['plant_name']) ?></strong>
                                                            <?php if (!empty($it['botanical_name'])): ?>
                                                                <small class="text-muted fst-italic d-block"><?= htmlspecialchars($it['botanical_name']) ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center"><?= $it['quantity'] ?></td>
                                                        <td class="text-end text-muted"><?= formatLKR($it['unit_price']) ?></td>
                                                        <td class="text-end fw-semibold text-dark"><?= formatLKR($subtotal) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">
                                        <i class="bi bi-truck me-1 text-success"></i> Dispatched from Kegalle Nursery Hub
                                    </span>
                                    <div>
                                        <span class="text-muted me-2">Grand Total:</span>
                                        <strong class="text-success fs-5"><?= formatLKR($ord['total_amount']) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
