<?php
/**
 * EcoSprout – Customer Queries Management (Admin / Staff)
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
$validStatuses = ['open', 'answered', 'closed'];

$where  = [];
$params = [];

if (!empty($search)) {
    $where[]  = "(q.subject LIKE ? OR q.message LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($statusFilter) && in_array($statusFilter, $validStatuses)) {
    $where[]  = "q.status = ?";
    $params[] = $statusFilter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT q.*, 
           u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
           s.full_name AS responder_name
    FROM queries q
    JOIN users u ON q.customer_id = u.id
    LEFT JOIN users s ON q.responded_by = s.id
    $whereClause
    ORDER BY (q.status = 'open') DESC, q.created_at DESC
");
$stmt->execute($params);
$queries = $stmt->fetchAll();

// Stats
$totalQueries   = (int) $pdo->query("SELECT COUNT(*) FROM queries")->fetchColumn();
$openQueries    = (int) $pdo->query("SELECT COUNT(*) FROM queries WHERE status='open'")->fetchColumn();
$answeredQueries = (int) $pdo->query("SELECT COUNT(*) FROM queries WHERE status='answered'")->fetchColumn();

$pageHeading = 'Customer Plant Queries';
$activePage  = 'queries';
$actionUrl   = $base . '/actions/query_action.php';
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
                    <p class="text-muted mb-0">Review questions submitted by plant lovers, diagnose symptoms, and send expert botanical advice.</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary-subtle text-primary">
                                <i class="bi bi-chat-dots-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Total Queries</div>
                                <div class="stat-value"><?= number_format($totalQueries) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning-subtle text-warning">
                                <i class="bi bi-question-circle-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Awaiting Staff Response</div>
                                <div class="stat-value text-warning fw-bold"><?= number_format($openQueries) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success-subtle text-success">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Resolved / Answered</div>
                                <div class="stat-value"><?= number_format($answeredQueries) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="" class="row g-2 align-items-center">
                        <div class="col-12 col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control bg-light border-start-0" 
                                       placeholder="Search questions or customer name..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <select name="status" class="form-select bg-light">
                                <option value="">All Statuses</option>
                                <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open / Unanswered</option>
                                <option value="answered" <?= $statusFilter === 'answered' ? 'selected' : '' ?>>Answered</option>
                                <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-secondary flex-grow-1">Filter</button>
                            <?php if ($search || $statusFilter): ?>
                                <a href="<?= $base ?>/<?= $role ?>/queries.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Queries Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="card-title mb-0 fw-bold">Inquiries (<?= count($queries) ?>)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Subject</th>
                                <th>Date Submitted</th>
                                <th>Status</th>
                                <th>Responded By</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($queries)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-chat-left-heart display-6 d-block mb-2"></i>
                                        No customer inquiries found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($queries as $q): ?>
                                    <tr class="<?= $q['status'] === 'open' ? 'table-warning-subtle' : '' ?>">
                                        <td class="text-muted small">#<?= (int)$q['id'] ?></td>
                                        <td>
                                            <strong class="text-dark d-block"><?= htmlspecialchars($q['customer_name']) ?></strong>
                                            <span class="text-muted small"><?= htmlspecialchars($q['customer_email']) ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($q['subject']) ?></span>
                                            <small class="text-muted d-block text-truncate" style="max-width: 320px;">
                                                <?= htmlspecialchars($q['message']) ?>
                                            </small>
                                        </td>
                                        <td class="text-muted small">
                                            <?= date('M d, Y', strtotime($q['created_at'])) ?><br>
                                            <?= date('h:i A', strtotime($q['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?php if ($q['status'] === 'open'): ?>
                                                <span class="badge bg-warning text-dark rounded-pill">Open</span>
                                            <?php elseif ($q['status'] === 'answered'): ?>
                                                <span class="badge bg-success rounded-pill">Answered</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary rounded-pill">Closed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $q['responder_name'] ? htmlspecialchars($q['responder_name']) : '<span class="text-muted">—</span>' ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm <?= $q['status'] === 'open' ? 'btn-primary' : 'btn-outline-primary' ?>" 
                                                    onclick='openRespondModal(<?= json_encode($q) ?>)'>
                                                <i class="bi bi-reply-fill me-1"></i> <?= $q['status'] === 'open' ? 'Reply' : 'View / Edit' ?>
                                            </button>
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

<!-- Respond Modal -->
<div class="modal fade" id="respondModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-chat-quote-fill me-2 text-primary"></i>Customer Plant Query</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $actionUrl ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="respond">
                <input type="hidden" name="query_id" id="respondQueryId">

                <div class="modal-body">
                    <!-- Inquiry Summary -->
                    <div class="bg-light p-3 rounded-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                Customer: <strong id="queryCustName" class="text-dark"></strong>
                                (<span id="queryCustEmail" class="text-muted small"></span>)
                            </div>
                            <span id="queryDate" class="text-muted small"></span>
                        </div>
                        <h6 class="fw-bold text-dark mb-1" id="querySubject"></h6>
                        <p class="mb-0 text-secondary" id="queryMessage" style="white-space: pre-wrap;"></p>
                    </div>

                    <!-- Staff Botanical Response -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Staff Botanical Advice / Response <span class="text-danger">*</span></label>
                        <textarea name="response" id="queryResponseInput" rows="6" class="form-control" 
                                  placeholder="Provide clear care diagnosis, watering adjustments, pest management, or nutrient recommendations..." required></textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Update Status</label>
                            <select name="query_status" id="queryStatusSelect" class="form-select">
                                <option value="answered">Mark as Answered</option>
                                <option value="open">Keep as Open</option>
                                <option value="closed">Close Ticket</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill me-1"></i>Send Botanical Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
<script>
function openRespondModal(q) {
    document.getElementById('respondQueryId').value = q.id;
    document.getElementById('queryCustName').textContent = q.customer_name;
    document.getElementById('queryCustEmail').textContent = q.customer_email;
    document.getElementById('queryDate').textContent = q.created_at;
    document.getElementById('querySubject').textContent = q.subject;
    document.getElementById('queryMessage').textContent = q.message;
    document.getElementById('queryResponseInput').value = q.response || '';
    document.getElementById('queryStatusSelect').value = q.status === 'open' ? 'answered' : q.status;

    new bootstrap.Modal(document.getElementById('respondModal')).show();
}
</script>
</body>
</html>
