<?php
/**
 * EcoSprout – Customer Cart
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['customer']);

$pdo  = getPDO();
$base = getBaseUrl();

$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$totalAmount = 0.0;

if (!empty($cart)) {
    $plantIds = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($plantIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM plants WHERE id IN ($placeholders)");
    $stmt->execute($plantIds);
    $plantsMap = [];
    while ($row = $stmt->fetch()) {
        $plantsMap[$row['id']] = $row;
    }

    foreach ($cart as $pid => $qty) {
        if (isset($plantsMap[$pid])) {
            $plant = $plantsMap[$pid];
            $subtotal = $plant['price'] * $qty;
            $totalAmount += $subtotal;
            $cartItems[] = [
                'plant'    => $plant,
                'quantity' => $qty,
                'subtotal' => $subtotal
            ];
        }
    }
}

$pageHeading = 'My Shopping Cart';
$activePage  = 'cart';
$cartAction  = $base . '/actions/cart_action.php';
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
                    <p class="text-muted mb-0">Review selected potted saplings, update pot quantities, and verify order totals.</p>
                </div>
                <?php if (!empty($cartItems)): ?>
                    <form method="POST" action="<?= $cartAction ?>" onsubmit="return confirm('Clear all items from your cart?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash3 me-1"></i> Empty Cart
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (empty($cartItems)): ?>
                <!-- Empty Cart State -->
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <div class="text-muted mb-3">
                            <i class="bi bi-cart-x display-3 text-secondary"></i>
                        </div>
                        <h4 class="fw-bold text-dark">Your cart is currently empty</h4>
                        <p class="text-muted mb-4">Add healthy indoor plants, exotic ornamentals, or fruit trees to your basket.</p>
                        <a href="<?= $base ?>/customer/catalogue.php" class="btn btn-primary px-4 py-2">
                            <i class="bi bi-flower1 me-1"></i> Explore Plant Catalogue
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <!-- Cart Items Table -->
                    <div class="col-12 col-xl-8">
                        <div class="card border-0 shadow-sm">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width: 180px;">Plant</th>
                                            <th style="min-width: 100px;">Price</th>
                                            <th style="min-width: 130px;">Quantity</th>
                                            <th class="text-end" style="min-width: 100px;">Subtotal</th>
                                            <th class="text-end" style="min-width: 60px;">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cartItems as $item): 
                                            $p = $item['plant'];
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="rounded overflow-hidden bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; flex-shrink: 0;">
                                                            <?php if (!empty($p['image'])): ?>
                                                                <img src="<?= $base ?>/assets/images/plants/<?= htmlspecialchars($p['image']) ?>" alt="" class="w-100 h-100 object-fit-cover">
                                                            <?php else: ?>
                                                                <i class="bi bi-flower1 text-success fs-4"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <strong class="text-dark d-block"><?= htmlspecialchars($p['plant_name']) ?></strong>
                                                            <small class="text-muted fst-italic"><?= htmlspecialchars($p['botanical_name'] ?? '') ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-muted">
                                                    <?= formatLKR($p['price']) ?>
                                                </td>
                                                <td>
                                                    <form method="POST" action="<?= $cartAction ?>" class="d-flex align-items-center gap-1">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="update">
                                                        <input type="hidden" name="plant_id" value="<?= (int)$p['id'] ?>">
                                                        <input type="number" name="quantity" min="1" max="<?= (int)$p['stock_quantity'] ?>" 
                                                               value="<?= (int)$item['quantity'] ?>" 
                                                               class="form-control form-control-sm text-center" style="width: 65px;">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Update quantity">
                                                            <i class="bi bi-arrow-repeat"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                                <td class="text-end fw-bold text-dark">
                                                    <?= formatLKR($item['subtotal']) ?>
                                                </td>
                                                <td class="text-end">
                                                    <form method="POST" action="<?= $cartAction ?>" class="d-inline" onsubmit="return confirm('Remove this plant from cart?');">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="remove">
                                                        <input type="hidden" name="plant_id" value="<?= (int)$p['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                            <i class="bi bi-x-circle fs-5"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-white p-3 d-flex justify-content-between">
                                <a href="<?= $base ?>/customer/catalogue.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-left me-1"></i> Continue Shopping
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary Card -->
                    <div class="col-12 col-xl-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="card-title mb-0 fw-bold">Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subtotal (<?= count($cartItems) ?> items)</span>
                                    <span class="fw-semibold text-dark"><?= formatLKR($totalAmount) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Nursery Packaging</span>
                                    <span class="text-success fw-semibold">FREE Eco-Wrap</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Local Delivery (Kegalle)</span>
                                    <span class="text-success fw-semibold">Complimentary</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <span class="fw-bold text-dark fs-5">Grand Total</span>
                                    <span class="fw-bold text-success fs-4"><?= formatLKR($totalAmount) ?></span>
                                </div>

                                <a href="<?= $base ?>/customer/checkout.php" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                                    Proceed to Checkout <i class="bi bi-arrow-right ms-1"></i>
                                </a>

                                <div class="mt-3 p-2 bg-light rounded text-center small text-muted">
                                    <i class="bi bi-shield-check text-success me-1"></i>
                                    Safe simulated checkout & guaranteed healthy delivery.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
