<?php
/**
 * EcoSprout – Admin Dashboard
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['admin']);

if (!defined('BASE_URL')) {
    define('BASE_URL', getBaseUrl());
}
$base = getBaseUrl();

$pdo = getPDO();

// ── Dashboard Statistics ──────────────────────────────────────
$stats = [];

// Total customers
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'");
$stats['total_customers'] = (int) $stmt->fetchColumn();

// Active users (all roles, enabled)
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status='enabled'");
$stats['active_users'] = (int) $stmt->fetchColumn();

// Total plants
$stmt = $pdo->query("SELECT COUNT(*) FROM plants");
$stats['total_plants'] = (int) $stmt->fetchColumn();

// Low stock (≤ 5)
$stmt = $pdo->query("SELECT COUNT(*) FROM plants WHERE stock_quantity <= 5");
$stats['low_stock'] = (int) $stmt->fetchColumn();

// Pending orders
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'");
$stats['pending_orders'] = (int) $stmt->fetchColumn();

// Upcoming workshops (schedule_date > NOW())
$stmt = $pdo->query("SELECT COUNT(*) FROM workshops WHERE schedule_date > NOW()");
$stats['upcoming_workshops'] = (int) $stmt->fetchColumn();

// Open queries
$stmt = $pdo->query("SELECT COUNT(*) FROM queries WHERE status='open'");
$stats['open_queries'] = (int) $stmt->fetchColumn();

// Recent orders (last 5)
$recentOrders = $pdo->query(
    "SELECT o.id, o.total_amount, o.order_status, o.payment_status, o.order_date, u.full_name
     FROM orders o JOIN users u ON o.customer_id = u.id
     ORDER BY o.order_date DESC LIMIT 5"
)->fetchAll();

// Recent registrations (last 5 customers)
$recentCustomers = $pdo->query(
    "SELECT id, full_name, email, created_at FROM users WHERE role='customer' ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

// Low stock plants
$lowStockPlants = $pdo->query(
    "SELECT id, plant_name, category, stock_quantity FROM plants WHERE stock_quantity <= 5 ORDER BY stock_quantity ASC LIMIT 5"
)->fetchAll();

$pageHeading = 'Admin Dashboard';
$activePage  = 'dashboard';
$base = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/assets/favicon.svg">
    <title>Admin Dashboard | EcoSprout</title>
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

        <div class="page-content">
            <?php renderFlashes(); ?>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <!-- Total Customers -->
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:var(--eco-mint);color:var(--eco-primary);">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stat-value"><?= $stats['total_customers'] ?></div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                </div>
                <!-- Active Users -->
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#d4edda;color:#155724;">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                        <div class="stat-value"><?= $stats['active_users'] ?></div>
                        <div class="stat-label">Active Users</div>
                    </div>
                </div>
                <!-- Total Plants -->
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#e8f5e9;color:#388e3c;">
                            <i class="bi bi-flower1"></i>
                        </div>
                        <div class="stat-value"><?= $stats['total_plants'] ?></div>
                        <div class="stat-label">Plants in Catalogue</div>
                    </div>
                </div>
                <!-- Low Stock -->
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fff3cd;color:#856404;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div class="stat-value"><?= $stats['low_stock'] ?></div>
                        <div class="stat-label">Low Stock Plants</div>
                    </div>
                </div>
                <!-- Pending Orders -->
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#cff4fc;color:#055160;">
                            <i class="bi bi-bag-check-fill"></i>
                        </div>
                        <div class="stat-value"><?= $stats['pending_orders'] ?></div>
                        <div class="stat-label">Pending Orders</div>
                    </div>
                </div>
                <!-- Upcoming Workshops -->
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#e0d7f8;color:#5b21b6;">
                            <i class="bi bi-calendar-event-fill"></i>
                        </div>
                        <div class="stat-value"><?= $stats['upcoming_workshops'] ?></div>
                        <div class="stat-label">Upcoming Workshops</div>
                    </div>
                </div>
                <!-- Open Queries -->
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fce4e4;color:#c00;">
                            <i class="bi bi-chat-dots-fill"></i>
                        </div>
                        <div class="stat-value"><?= $stats['open_queries'] ?></div>
                        <div class="stat-label">Open Queries</div>
                    </div>
                </div>
            </div>

            <!-- Row: Recent Orders + Low Stock -->
            <div class="row g-3 mb-3">
                <!-- Recent Orders -->
                <div class="col-lg-7">
                    <div class="eco-card">
                        <div class="eco-card-header">
                            <h6><i class="bi bi-bag me-2"></i>Recent Orders</h6>
                            <a href="<?= $base ?>/admin/orders.php" class="btn btn-sm btn-eco-primary">View All</a>
                        </div>
                        <div class="eco-table-wrap">
                            <table class="eco-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentOrders)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">No orders yet.</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                                        <td><?= formatLKR($order['total_amount']) ?></td>
                                        <td><?= orderStatusBadge($order['order_status']) ?></td>
                                        <td><?= date('M d', strtotime($order['order_date'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="col-lg-5">
                    <div class="eco-card">
                        <div class="eco-card-header">
                            <h6><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Low Stock Alert</h6>
                            <a href="<?= $base ?>/admin/plants.php" class="btn btn-sm btn-warning">Manage</a>
                        </div>
                        <div class="eco-table-wrap">
                            <table class="eco-table">
                                <thead><tr><th>Plant</th><th>Category</th><th>Stock</th></tr></thead>
                                <tbody>
                                    <?php if (empty($lowStockPlants)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">All plants well-stocked!</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($lowStockPlants as $p): ?>
                                    <tr class="<?= $p['stock_quantity'] == 0 ? 'table-danger' : 'low-stock' ?>">
                                        <td><?= htmlspecialchars($p['plant_name']) ?></td>
                                        <td><?= categoryBadge($p['category']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $p['stock_quantity'] == 0 ? 'danger' : 'warning text-dark' ?> badge-pulse">
                                                <?= $p['stock_quantity'] ?> left
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Customers -->
            <div class="eco-card">
                <div class="eco-card-header">
                    <h6><i class="bi bi-person-plus me-2"></i>Recent Customer Registrations</h6>
                    <a href="<?= $base ?>/admin/staff.php" class="btn btn-sm btn-eco-primary">View All Users</a>
                </div>
                <div class="eco-table-wrap">
                    <table class="eco-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentCustomers)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No customers yet.</td></tr>
                            <?php else: ?>
                            <?php foreach ($recentCustomers as $c): ?>
                            <tr>
                                <td><?= $c['id'] ?></td>
                                <td><?= htmlspecialchars($c['full_name']) ?></td>
                                <td><?= htmlspecialchars($c['email']) ?></td>
                                <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><!-- /.page-content -->
    </div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
