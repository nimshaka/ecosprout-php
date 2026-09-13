<?php
/**
 * EcoSprout – Nursery Staff Dashboard
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['staff', 'admin']);

$pdo  = getPDO();
$base = getBaseUrl();
$user = getCurrentUser();

// ── Metrics for Nursery Staff ─────────────────────────────────
$totalPlants = (int) $pdo->query("SELECT COUNT(*) FROM plants")->fetchColumn();
$lowStockCount = (int) $pdo->query("SELECT COUNT(*) FROM plants WHERE stock_quantity <= 10")->fetchColumn();
$openQueries = (int) $pdo->query("SELECT COUNT(*) FROM queries WHERE status='open'")->fetchColumn();
$upcomingWorkshops = (int) $pdo->query("SELECT COUNT(*) FROM workshops WHERE schedule_date >= NOW()")->fetchColumn();

// ── Low Stock Plants ──────────────────────────────────────────
$lowStockPlants = $pdo->query("
    SELECT id, plant_name, botanical_name, category, stock_quantity, price 
    FROM plants 
    WHERE stock_quantity <= 10 
    ORDER BY stock_quantity ASC 
    LIMIT 6
")->fetchAll();

// ── Unanswered Customer Plant Queries ─────────────────────────
$recentQueries = $pdo->query("
    SELECT q.*, u.full_name AS customer_name, u.email AS customer_email
    FROM queries q
    JOIN users u ON q.customer_id = u.id
    WHERE q.status = 'open'
    ORDER BY q.created_at DESC
    LIMIT 5
")->fetchAll();

$pageHeading = 'Staff Operations Dashboard';
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

            <!-- Welcome Greeting -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h2 class="h3 mb-1 text-dark fw-bold">Welcome, <?= htmlspecialchars($user['full_name'] ?? 'Staff') ?>! 🌿</h2>
                    <p class="text-muted mb-0">Nursery plant inventory, botanical inquiries, and gardening operations.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= $base ?>/staff/inventory.php" class="btn btn-primary shadow-sm">
                        <i class="bi bi-flower1 me-1"></i> Manage Inventory
                    </a>
                    <a href="<?= $base ?>/staff/queries.php" class="btn btn-outline-warning shadow-sm">
                        <i class="bi bi-chat-dots me-1"></i> Answer Queries (<?= $openQueries ?>)
                    </a>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success-subtle text-success">
                                <i class="bi bi-flower1"></i>
                            </div>
                            <div>
                                <div class="stat-label">Plant Varieties</div>
                                <div class="stat-value"><?= number_format($totalPlants) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-danger-subtle text-danger">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Low Stock Varieties</div>
                                <div class="stat-value text-danger fw-bold"><?= number_format($lowStockCount) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning-subtle text-warning">
                                <i class="bi bi-chat-left-dots-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Pending Inquiries</div>
                                <div class="stat-value text-warning fw-bold"><?= number_format($openQueries) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-info-subtle text-info">
                                <i class="bi bi-calendar-check-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Upcoming Workshops</div>
                                <div class="stat-value"><?= number_format($upcomingWorkshops) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Low Stock Alert Column -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold text-danger">
                                <i class="bi bi-exclamation-circle-fill me-2"></i>Inventory Attention Needed
                            </h5>
                            <a href="<?= $base ?>/staff/inventory.php" class="small text-decoration-none">View All Inventory &rarr;</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($lowStockPlants)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-check-circle-fill text-success display-6 d-block mb-2"></i>
                                    All inventory levels are healthy!
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Plant</th>
                                                <th>Category</th>
                                                <th class="text-center">Remaining</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lowStockPlants as $lp): ?>
                                                <tr>
                                                    <td>
                                                        <strong class="text-dark d-block"><?= htmlspecialchars($lp['plant_name']) ?></strong>
                                                        <small class="text-muted fst-italic"><?= htmlspecialchars($lp['botanical_name'] ?? '') ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border text-capitalize"><?= htmlspecialchars($lp['category']) ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-danger rounded-pill px-2 py-1"><?= $lp['stock_quantity'] ?> left</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="<?= $base ?>/staff/inventory.php?search=<?= urlencode($lp['plant_name']) ?>" class="btn btn-sm btn-outline-primary">
                                                            Restock
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

                <!-- Unanswered Customer Queries -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold">
                                <i class="bi bi-chat-dots-fill text-warning me-2"></i>Pending Botanical Queries
                            </h5>
                            <a href="<?= $base ?>/staff/queries.php" class="small text-decoration-none">All Queries &rarr;</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recentQueries)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-chat-check-fill text-success display-6 d-block mb-2"></i>
                                    No pending customer queries! Great job!
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentQueries as $rq): ?>
                                        <div class="list-group-item p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($rq['subject']) ?></h6>
                                                <small class="text-muted"><?= date('M d, h:i A', strtotime($rq['created_at'])) ?></small>
                                            </div>
                                            <p class="text-muted small mb-2 text-truncate"><?= htmlspecialchars($rq['message']) ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-secondary"><i class="bi bi-person me-1"></i><?= htmlspecialchars($rq['customer_name']) ?></small>
                                                <a href="<?= $base ?>/staff/queries.php?search=<?= urlencode($rq['subject']) ?>" class="btn btn-sm btn-warning text-dark">
                                                    <i class="bi bi-reply-fill me-1"></i> Answer Query
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
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
