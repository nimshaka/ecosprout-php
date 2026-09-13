<?php
/**
 * EcoSprout – Sales & Inventory Reports (Admin)
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['admin']);

$pdo  = getPDO();
$base = getBaseUrl();

// ── Date Filters ───────────────────────────────────────────────
$startDate = trim($_GET['start_date'] ?? date('Y-m-01')); // 1st of current month
$endDate   = trim($_GET['end_date']   ?? date('Y-m-d'));

$salesStmt = $pdo->prepare("
    SELECT 
        COUNT(id) AS total_orders,
        COALESCE(SUM(total_amount), 0) AS total_revenue,
        COALESCE(AVG(total_amount), 0) AS avg_order_value
    FROM orders
    WHERE DATE(order_date) BETWEEN ? AND ?
      AND order_status != 'cancelled'
");
$salesStmt->execute([$startDate, $endDate]);
$summary = $salesStmt->fetch();

// ── Top Selling Plants ─────────────────────────────────────────
$topPlantsStmt = $pdo->prepare("
    SELECT 
        p.id, p.plant_name, p.category, p.price,
        SUM(oi.quantity) AS total_sold,
        SUM(oi.quantity * oi.unit_price) AS total_revenue
    FROM order_items oi
    JOIN plants p ON oi.plant_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.order_date) BETWEEN ? AND ?
      AND o.order_status != 'cancelled'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 8
");
$topPlantsStmt->execute([$startDate, $endDate]);
$topPlants = $topPlantsStmt->fetchAll();

// ── Sales by Category ──────────────────────────────────────────
$catStmt = $pdo->prepare("
    SELECT 
        p.category,
        COUNT(DISTINCT o.id) AS order_count,
        SUM(oi.quantity) AS items_sold,
        SUM(oi.quantity * oi.unit_price) AS category_revenue
    FROM order_items oi
    JOIN plants p ON oi.plant_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.order_date) BETWEEN ? AND ?
      AND o.order_status != 'cancelled'
    GROUP BY p.category
    ORDER BY category_revenue DESC
");
$catStmt->execute([$startDate, $endDate]);
$catSales = $catStmt->fetchAll();

// ── Order Status Breakdown ─────────────────────────────────────
$statusStmt = $pdo->query("
    SELECT order_status, COUNT(*) AS cnt, SUM(total_amount) AS total
    FROM orders
    GROUP BY order_status
");
$orderStatusStats = $statusStmt->fetchAll();

// ── Total Inventory Valuation ──────────────────────────────────
$invVal = $pdo->query("
    SELECT 
        COUNT(*) AS total_varieties,
        SUM(stock_quantity) AS total_stock,
        SUM(stock_quantity * price) AS valuation
    FROM plants
")->fetch();

$pageHeading = 'Sales & Business Reports';
$activePage  = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/assets/favicon.svg">
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

            <!-- Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h2 class="h3 mb-1 text-dark fw-bold"><?= htmlspecialchars($pageHeading) ?></h2>
                    <p class="text-muted mb-0">Financial metrics, product velocity, category breakdown, and warehouse inventory valuation.</p>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-outline-secondary shadow-sm">
                        <i class="bi bi-printer me-1"></i> Print / Save PDF
                    </button>
                </div>
            </div>

            <!-- Date Range Selector -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="" class="row g-2 align-items-end">
                        <div class="col-12 col-sm-4 col-md-3">
                            <label class="form-label small fw-semibold text-muted mb-1">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>" required>
                        </div>
                        <div class="col-12 col-sm-4 col-md-3">
                            <label class="form-label small fw-semibold text-muted mb-1">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>" required>
                        </div>
                        <div class="col-12 col-sm-4 col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i>Apply Filter</button>
                            <a href="<?= $base ?>/admin/reports.php" class="btn btn-outline-secondary">This Month</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Key Metrics for Selected Period -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success-subtle text-success">
                                <i class="bi bi-cash-coin"></i>
                            </div>
                            <div>
                                <div class="stat-label">Period Revenue</div>
                                <div class="stat-value fs-5"><?= formatLKR($summary['total_revenue']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary-subtle text-primary">
                                <i class="bi bi-bag-check-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Orders Placed</div>
                                <div class="stat-value"><?= number_format($summary['total_orders']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning-subtle text-warning">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <div>
                                <div class="stat-label">Avg. Order Value</div>
                                <div class="stat-value fs-5"><?= formatLKR($summary['avg_order_value']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-info-subtle text-info">
                                <i class="bi bi-boxes"></i>
                            </div>
                            <div>
                                <div class="stat-label">Inventory Asset Value</div>
                                <div class="stat-value fs-5"><?= formatLKR($invVal['valuation'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Top Selling Plants -->
                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="card-title mb-0 fw-bold"><i class="bi bi-trophy-fill text-warning me-2"></i>Top Selling Plants (Selected Period)</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Plant</th>
                                        <th>Category</th>
                                        <th class="text-center">Units Sold</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topPlants)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No sales recorded in this date range.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($topPlants as $idx => $tp): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-light text-dark me-1">#<?= $idx + 1 ?></span>
                                                    <strong class="text-dark"><?= htmlspecialchars($tp['plant_name']) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill text-capitalize">
                                                        <?= htmlspecialchars($tp['category']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center fw-bold text-primary"><?= number_format($tp['total_sold']) ?></td>
                                                <td class="text-end fw-bold text-success"><?= formatLKR($tp['total_revenue']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="card-title mb-0 fw-bold"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Sales by Category</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($catSales)): ?>
                                <p class="text-muted text-center py-4">No category sales recorded.</p>
                            <?php else: 
                                $totalCatRev = array_sum(array_column($catSales, 'category_revenue'));
                            ?>
                                <?php foreach ($catSales as $cs): 
                                    $pct = $totalCatRev > 0 ? round(($cs['category_revenue'] / $totalCatRev) * 100) : 0;
                                ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-capitalize fw-semibold text-dark"><?= htmlspecialchars($cs['category']) ?> Plants</span>
                                            <span class="fw-bold text-success"><?= formatLKR($cs['category_revenue']) ?> (<?= $pct ?>%)</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= number_format($cs['items_sold']) ?> plants sold across <?= $cs['order_count'] ?> orders</small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warehouse Inventory Valuation Summary -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-building-check text-success me-2"></i>Nursery Inventory Asset Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small text-uppercase fw-semibold mb-1">Catalog Varieties</div>
                                <div class="fs-4 fw-bold text-dark"><?= number_format($invVal['total_varieties'] ?? 0) ?> Types</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small text-uppercase fw-semibold mb-1">Stocked Pots & Saplings</div>
                                <div class="fs-4 fw-bold text-primary"><?= number_format($invVal['total_stock'] ?? 0) ?> Plants</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small text-uppercase fw-semibold mb-1">Estimated Retail Value</div>
                                <div class="fs-4 fw-bold text-success"><?= formatLKR($invVal['valuation'] ?? 0) ?></div>
                            </div>
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
