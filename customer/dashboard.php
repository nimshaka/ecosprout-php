<?php
/**
 * EcoSprout – Customer Dashboard
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
$user   = getCurrentUser();

// ── Customer Stats ─────────────────────────────────────────────
$orderCountStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
$orderCountStmt->execute([$userId]);
$totalOrders = (int) $orderCountStmt->fetchColumn();

$wsCountStmt = $pdo->prepare("
    SELECT COUNT(*) FROM workshop_registrations r
    JOIN workshops w ON r.workshop_id = w.id
    WHERE r.user_id = ? AND w.schedule_date >= NOW()
");
$wsCountStmt->execute([$userId]);
$upcomingWsCount = (int) $wsCountStmt->fetchColumn();

$queryCountStmt = $pdo->prepare("SELECT COUNT(*) FROM queries WHERE customer_id = ? AND status = 'open'");
$queryCountStmt->execute([$userId]);
$openQueries = (int) $queryCountStmt->fetchColumn();

// ── Recent Orders ──────────────────────────────────────────────
$recentOrdersStmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE customer_id = ? 
    ORDER BY order_date DESC 
    LIMIT 4
");
$recentOrdersStmt->execute([$userId]);
$recentOrders = $recentOrdersStmt->fetchAll();

// ── Enrolled Workshops ─────────────────────────────────────────
$enrolledWsStmt = $pdo->prepare("
    SELECT w.*, r.registered_at
    FROM workshop_registrations r
    JOIN workshops w ON r.workshop_id = w.id
    WHERE r.user_id = ? AND w.schedule_date >= NOW()
    ORDER BY w.schedule_date ASC
    LIMIT 3
");
$enrolledWsStmt->execute([$userId]);
$upcomingWorkshops = $enrolledWsStmt->fetchAll();

$pageHeading = 'Customer Dashboard';
$activePage  = 'dashboard';
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

            <!-- Welcome Hero -->
            <div class="p-4 rounded-4 mb-4 text-white shadow-sm" style="background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2 mb-2">
                            <i class="bi bi-sprout me-1"></i> Plant Lover Portal
                        </span>
                        <h2 class="customer-hero-title fw-bold mb-2">Ayubowan, <?= htmlspecialchars($user['full_name'] ?? 'Friend') ?>! 🌱</h2>
                        <p class="customer-hero-description mb-3">
                            Welcome to your personal gardening sanctuary. Discover lush botanical additions for your home, review your delivery orders, or consult our nursery horticulturalists.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?= $base ?>/customer/catalogue.php" class="btn btn-warning text-dark fw-semibold">
                                <i class="bi bi-flower1 me-1"></i> Browse Nursery Catalogue
                            </a>
                            <a href="<?= $base ?>/customer/my-queries.php" class="btn btn-outline-light">
                                <i class="bi bi-chat-dots me-1"></i> Ask Plant Doctor
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary-subtle text-primary">
                                <i class="bi bi-bag-check-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Total Orders</div>
                                <div class="stat-value"><?= $totalOrders ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success-subtle text-success">
                                <i class="bi bi-calendar2-check-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Upcoming Workshops</div>
                                <div class="stat-value"><?= $upcomingWsCount ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning-subtle text-warning">
                                <i class="bi bi-chat-dots-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Pending Inquiries</div>
                                <div class="stat-value"><?= $openQueries ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-info-subtle text-info">
                                <i class="bi bi-cart3"></i>
                            </div>
                            <div>
                                <div class="stat-label">Cart Items</div>
                                <div class="stat-value"><?= cartCount() ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Orders -->
                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold"><i class="bi bi-bag-fill text-primary me-2"></i>Recent Orders</h5>
                            <a href="<?= $base ?>/customer/my-orders.php" class="small text-decoration-none">View All &rarr;</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recentOrders)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-bag-x display-6 d-block mb-2"></i>
                                    <p class="mb-2">You haven't placed any orders yet.</p>
                                    <a href="<?= $base ?>/customer/catalogue.php" class="btn btn-sm btn-primary">Start Shopping</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Date</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $ro): 
                                                $st = $ro['order_status'];
                                                $badgeClass = match($st) {
                                                    'completed'  => 'bg-success text-white',
                                                    'processing' => 'bg-info text-dark',
                                                    'cancelled'  => 'bg-danger text-white',
                                                    default      => 'bg-warning text-dark'
                                                };
                                            ?>
                                                <tr>
                                                    <td>
                                                        <span class="fw-bold text-primary">#<?= str_pad($ro['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                                    </td>
                                                    <td class="text-muted small"><?= date('M d, Y', strtotime($ro['order_date'])) ?></td>
                                                    <td class="fw-bold text-dark"><?= formatLKR($ro['total_amount']) ?></td>
                                                    <td>
                                                        <span class="badge <?= $badgeClass ?> rounded-pill text-capitalize"><?= htmlspecialchars($st) ?></span>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="<?= $base ?>/customer/my-orders.php?order_id=<?= (int)$ro['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            View Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Enrolled Workshops & Actions -->
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold"><i class="bi bi-calendar-event-fill text-success me-2"></i>My Registered Workshops</h5>
                            <a href="<?= $base ?>/customer/my-workshops.php" class="small text-decoration-none">All Workshops &rarr;</a>
                        </div>
                        <div class="card-body p-3">
                            <?php if (empty($upcomingWorkshops)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-calendar-check display-6 d-block mb-2"></i>
                                    <p class="small mb-2">You have no upcoming workshop bookings.</p>
                                    <a href="<?= $base ?>/customer/my-workshops.php" class="btn btn-sm btn-outline-success">Browse Sessions</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($upcomingWorkshops as $uws): ?>
                                    <div class="p-3 mb-2 bg-light rounded-3 border-start border-4 border-success">
                                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($uws['title']) ?></h6>
                                        <div class="d-flex justify-content-between align-items-center small text-muted">
                                            <span><i class="bi bi-calendar3 me-1"></i><?= date('M d, Y', strtotime($uws['schedule_date'])) ?></span>
                                            <span><i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($uws['schedule_date'])) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
