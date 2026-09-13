<?php
/**
 * EcoSprout – Customer Checkout (Payment Simulation)
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['customer']);

$pdo  = getPDO();
$base = getBaseUrl();
$user = getCurrentUser();

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    setFlash('error', 'Your cart is empty. Please add plants before checking out.');
    redirect($base . '/customer/cart.php');
}

// Fetch plants in cart
$plantIds = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($plantIds), '?'));
$stmt = $pdo->prepare("SELECT * FROM plants WHERE id IN ($placeholders)");
$stmt->execute($plantIds);
$plantsMap = [];
while ($row = $stmt->fetch()) {
    $plantsMap[$row['id']] = $row;
}

$cartItems = [];
$totalAmount = 0.0;
foreach ($cart as $pid => $qty) {
    if (isset($plantsMap[$pid])) {
        $p = $plantsMap[$pid];
        $subtotal = $p['price'] * $qty;
        $totalAmount += $subtotal;
        $cartItems[] = [
            'plant'    => $p,
            'quantity' => $qty,
            'subtotal' => $subtotal
        ];
    }
}

$pageHeading = 'Order Checkout';
$activePage  = 'cart';
$orderAction = $base . '/actions/order_action.php';
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
            <div class="mb-4">
                <a href="<?= $base ?>/customer/cart.php" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i> Back to Cart
                </a>
                <h2 class="h3 mb-1 text-dark fw-bold mt-1"><?= htmlspecialchars($pageHeading) ?></h2>
                <p class="text-muted mb-0">Confirm your recipient contact details and complete the simulated order payment.</p>
            </div>

            <form method="POST" action="<?= $orderAction ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="checkout">

                <div class="row g-4">
                    <!-- Delivery & Contact Info -->
                    <div class="col-12 col-xl-7">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="card-title mb-0 fw-bold">
                                    <i class="bi bi-geo-alt-fill text-primary me-2"></i>Delivery & Contact Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Customer Full Name</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Email Address</label>
                                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Contact Phone Number</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '077 123 4567') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Delivery Address / Nursery Pickup Instructions</label>
                                        <textarea rows="3" class="form-control" placeholder="House / street address, Matara landmark, or indicate 'Nursery Counter Pickup'...">Matara Town Delivery or EcoSprout Nursery Counter Pickup</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Simulation Selection -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="card-title mb-0 fw-bold">
                                    <i class="bi bi-credit-card-2-front-fill text-success me-2"></i>Payment Method (Simulation)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info border-0 d-flex gap-2">
                                    <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
                                    <div class="small">
                                        <strong>Prototype Academic Demonstration:</strong> A live payment gateway (e.g. PayHere/Stripe) is not connected. Submitting this form simulates an immediate successful transaction and records your order as <strong>Paid</strong> in the database.
                                    </div>
                                </div>

                                <div class="form-check p-3 border rounded-3 mb-2 bg-light">
                                    <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="pay1" value="simulated_card" checked>
                                    <label class="form-check-label fw-semibold" for="pay1">
                                        <i class="bi bi-credit-card me-1 text-primary"></i> Simulated Visa / MasterCard / Debit Card
                                        <div class="text-muted fw-normal small">Simulates immediate instant clearance and order receipt generation.</div>
                                    </label>
                                </div>

                                <div class="form-check p-3 border rounded-3 bg-light">
                                    <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="pay2" value="cod">
                                    <label class="form-check-label fw-semibold" for="pay2">
                                        <i class="bi bi-cash-coin me-1 text-success"></i> Cash on Delivery / Counter Settlement
                                        <div class="text-muted fw-normal small">Pay cash upon receiving plants at your doorstep in Matara.</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="col-12 col-xl-5">
                        <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="card-title mb-0 fw-bold">Items in Order (<?= count($cartItems) ?>)</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($cartItems as $ci): 
                                        $p = $ci['plant'];
                                    ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-3">
                                            <div>
                                                <strong class="text-dark d-block"><?= htmlspecialchars($p['plant_name']) ?></strong>
                                                <small class="text-muted"><?= $ci['quantity'] ?> &times; <?= formatLKR($p['price']) ?></small>
                                            </div>
                                            <span class="fw-bold text-dark"><?= formatLKR($ci['subtotal']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="card-body border-top">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subtotal</span>
                                    <span class="fw-semibold text-dark"><?= formatLKR($totalAmount) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Delivery</span>
                                    <span class="text-success fw-semibold">FREE (Matara)</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <span class="fw-bold text-dark fs-5">Total to Pay</span>
                                    <span class="fw-bold text-success fs-4"><?= formatLKR($totalAmount) ?></span>
                                </div>

                                <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-6 shadow-sm">
                                    <i class="bi bi-lock-fill me-1"></i> Place Order & Pay <?= formatLKR($totalAmount) ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
