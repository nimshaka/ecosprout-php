<?php
/**
 * EcoSprout – Orders Management (Admin / Staff)
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['admin', 'staff']);

$pdo  = getPDO();
$base = getBaseUrl();
$role = getCurrentRole();

// ── Search & Filter ───────────────────────────────────────────
$search       = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$validStatuses = ['pending', 'processing', 'completed', 'cancelled'];

$where  = [];
$params = [];

if (!empty($search)) {
    $where[]  = "(o.id = ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = is_numeric($search) ? (int)$search : 0;
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($statusFilter) && in_array($statusFilter, $validStatuses)) {
    $where[]  = "o.order_status = ?";
    $params[] = $statusFilter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Fetch orders with customer info
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    $whereClause
    ORDER BY o.order_date DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Fetch order items lookup
$itemsStmt = $pdo->query("
    SELECT oi.*, p.plant_name, p.botanical_name
    FROM order_items oi
    JOIN plants p ON oi.plant_id = p.id
    ORDER BY oi.id ASC
");
$orderItemsMap = [];
while ($item = $itemsStmt->fetch()) {
    $orderItemsMap[$item['order_id']][] = $item;
}

// Stats
$totalOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn();
$completedOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='completed'")->fetchColumn();
$totalRevenue = (float) $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status='paid' AND order_status != 'cancelled'")->fetchColumn();

$pageHeading = 'Orders Management';
$activePage  = 'orders';
$actionUrl   = $base . '/actions/order_action.php';
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

            <!-- Page Title -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h2 class="h3 mb-1 text-dark fw-bold"><?= htmlspecialchars($pageHeading) ?></h2>
                    <p class="text-muted mb-0">Track customer purchases, fulfillment stages, and payment receipts.</p>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary-subtle text-primary">
                                <i class="bi bi-bag-check"></i>
                            </div>
                            <div>
                                <div class="stat-label">Total Orders</div>
                                <div class="stat-value"><?= number_format($totalOrders) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning-subtle text-warning">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <div>
                                <div class="stat-label">Pending Fulfillment</div>
                                <div class="stat-value"><?= number_format($pendingOrders) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success-subtle text-success">
                                <i class="bi bi-check2-all"></i>
                            </div>
                            <div>
                                <div class="stat-label">Completed Orders</div>
                                <div class="stat-value"><?= number_format($completedOrders) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-info-subtle text-info">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div>
                                <div class="stat-label">Total Paid Revenue</div>
                                <div class="stat-value fs-5"><?= formatLKR($totalRevenue) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="" class="row g-2 align-items-center">
                        <div class="col-12 col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control bg-light border-start-0" 
                                       placeholder="Search by Order ID (#), customer name, or email..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <select name="status" class="form-select bg-light">
                                <option value="">All Order Statuses</option>
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-secondary flex-grow-1">Filter</button>
                            <?php if ($search || $statusFilter): ?>
                                <a href="<?= $base ?>/<?= $role ?>/orders.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="card-title mb-0 fw-bold">Customer Orders (<?= count($orders) ?>)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Placed Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                        No orders found matching your search.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $ord): 
                                    $items = $orderItemsMap[$ord['id']] ?? [];
                                    $itemCount = array_sum(array_column($items, 'quantity'));
                                ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-primary">#<?= str_pad($ord['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($ord['customer_name']) ?></span>
                                            <span class="text-muted small"><?= htmlspecialchars($ord['customer_email']) ?></span>
                                        </td>
                                        <td class="text-muted small">
                                            <?= date('M d, Y', strtotime($ord['order_date'])) ?><br>
                                            <?= date('h:i A', strtotime($ord['order_date'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= $itemCount ?> <?= $itemCount === 1 ? 'item' : 'items' ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?= formatLKR($ord['total_amount']) ?>
                                        </td>
                                        <td>
                                            <?php if ($ord['payment_status'] === 'paid'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $st = $ord['order_status'];
                                            $badgeClass = match($st) {
                                                'completed'  => 'bg-success text-white',
                                                'processing' => 'bg-info text-dark',
                                                'cancelled'  => 'bg-danger text-white',
                                                default      => 'bg-warning text-dark'
                                            };
                                            ?>
                                            <span class="badge <?= $badgeClass ?> rounded-pill text-capitalize">
                                                <?= htmlspecialchars($st) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick='viewOrderDetails(<?= json_encode($ord) ?>, <?= json_encode($items) ?>)'
                                                        title="View Order Details">
                                                    <i class="bi bi-eye"></i> Details
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                        onclick='openStatusModal(<?= (int)$ord['id'] ?>, "<?= htmlspecialchars($ord['order_status']) ?>")'
                                                        title="Update Status">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                            </div>
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
</div>

<!-- View Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="orderModalTitle">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Customer & Order Meta -->
                <div class="row g-3 mb-3 pb-3 border-bottom">
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase fw-bold mb-2">Customer Details</h6>
                        <p class="mb-1"><strong id="modalCustName"></strong></p>
                        <p class="mb-1 small text-muted"><i class="bi bi-envelope me-1"></i> <span id="modalCustEmail"></span></p>
                        <p class="mb-0 small text-muted"><i class="bi bi-telephone me-1"></i> <span id="modalCustPhone"></span></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6 class="text-muted small text-uppercase fw-bold mb-2">Order Summary</h6>
                        <p class="mb-1">Placed: <strong id="modalOrderDate"></strong></p>
                        <p class="mb-1">Payment: <span id="modalPaymentStatus"></span></p>
                        <p class="mb-0">Status: <span id="modalOrderStatus"></span></p>
                    </div>
                </div>

                <!-- Items Table -->
                <h6 class="fw-bold mb-2">Items in this Order</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Plant</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="modalItemsBody">
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end pt-3">Total Amount:</th>
                                <th class="text-end pt-3 fs-5 text-success" id="modalOrderTotal"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $actionUrl ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="updateOrderId">
                <div class="modal-body">
                    <p class="small text-muted mb-2">Update status for Order <strong id="updateOrderLabel" class="text-primary"></strong>:</p>
                    <div class="mb-3">
                        <select name="order_status" id="updateOrderStatusSelect" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
<script>
function viewOrderDetails(order, items) {
    document.getElementById('orderModalTitle').textContent = `Order #${String(order.id).padStart(5, '0')} Details`;
    document.getElementById('modalCustName').textContent = order.customer_name;
    document.getElementById('modalCustEmail').textContent = order.customer_email;
    document.getElementById('modalCustPhone').textContent = order.customer_phone || 'None provided';
    document.getElementById('modalOrderDate').textContent = order.order_date;
    document.getElementById('modalPaymentStatus').textContent = order.payment_status.toUpperCase();
    document.getElementById('modalOrderStatus').textContent = order.order_status.toUpperCase();
    document.getElementById('modalOrderTotal').textContent = 'LKR ' + parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2});

    const tbody = document.getElementById('modalItemsBody');
    tbody.innerHTML = '';
    items.forEach(item => {
        const subtotal = parseFloat(item.unit_price) * parseInt(item.quantity);
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <strong>${escapeHtml(item.plant_name)}</strong><br>
                <small class="text-muted fst-italic">${escapeHtml(item.botanical_name || '')}</small>
            </td>
            <td class="text-center">${item.quantity}</td>
            <td class="text-end">LKR ${parseFloat(item.unit_price).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td class="text-end fw-bold">LKR ${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
        `;
        tbody.appendChild(tr);
    });

    new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
}

function openStatusModal(orderId, currentStatus) {
    document.getElementById('updateOrderId').value = orderId;
    document.getElementById('updateOrderLabel').textContent = '#' + String(orderId).padStart(5, '0');
    document.getElementById('updateOrderStatusSelect').value = currentStatus;
    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}
</script>
</body>
</html>
