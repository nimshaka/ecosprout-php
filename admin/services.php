<?php
/**
 * EcoSprout – Gardening Services Management (Admin / Staff)
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

// Search
$search = trim($_GET['search'] ?? '');
$params = [];
$whereClause = '';
if (!empty($search)) {
    $whereClause = "WHERE service_name LIKE ? OR description LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT * FROM gardening_services $whereClause ORDER BY id DESC");
$stmt->execute($params);
$services = $stmt->fetchAll();

$totalServices = count($services);
$avgPrice = $totalServices > 0 ? array_sum(array_column($services, 'price')) / $totalServices : 0;

$pageHeading = 'Gardening Services';
$activePage  = 'services';
$actionUrl   = $base . '/actions/service_action.php';
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

            <!-- Page Title & Actions -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h2 class="h3 mb-1 text-dark fw-bold"><?= htmlspecialchars($pageHeading) ?></h2>
                    <p class="text-muted mb-0">Manage landscaping, maintenance, consultation, and gardening packages.</p>
                </div>
                <div>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                        <i class="bi bi-plus-circle me-1"></i> Add New Service
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary-subtle text-primary">
                                <i class="bi bi-tools"></i>
                            </div>
                            <div>
                                <div class="stat-label">Total Services Offered</div>
                                <div class="stat-value"><?= $totalServices ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success-subtle text-success">
                                <i class="bi bi-tag-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Average Starting Fee</div>
                                <div class="stat-value"><?= formatLKR($avgPrice) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-info-subtle text-info">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Service Area</div>
                                <div class="stat-value fs-5">Matara & Southern Province</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="" class="row g-2 align-items-center">
                        <div class="col-12 col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control bg-light border-start-0" 
                                       placeholder="Search service name or description..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-12 col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-secondary flex-grow-1">Search</button>
                            <?php if ($search): ?>
                                <a href="<?= $base ?>/<?= $role ?>/services.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Services Grid / Cards -->
            <?php if (empty($services)): ?>
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-tools display-4 text-muted mb-3 d-block"></i>
                        <h5>No Gardening Services Found</h5>
                        <p class="text-muted">Start by adding your first service offering or package.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                            <i class="bi bi-plus-circle me-1"></i> Add Service Now
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($services as $srv): ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card border-0 shadow-sm h-100 service-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="service-icon-box bg-success-subtle text-success rounded-3 p-3">
                                            <i class="bi bi-flower3 fs-4"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border-0" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <button class="dropdown-item" 
                                                            onclick='openEditService(<?= json_encode($srv) ?>)'>
                                                        <i class="bi bi-pencil me-2 text-primary"></i> Edit Service
                                                    </button>
                                                </li>
                                                <li>
                                                    <form method="POST" action="<?= $actionUrl ?>" id="del-srv-<?= (int)$srv['id'] ?>">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="service_id" value="<?= (int)$srv['id'] ?>">
                                                        <button type="button" class="dropdown-item text-danger"
                                                                onclick="confirmDelete('del-srv-<?= (int)$srv['id'] ?>',
                                                                  'Delete service &lt;strong&gt;<?= htmlspecialchars(addslashes($srv['service_name'] ?? 'this service')) ?>&lt;/strong&gt;?', '🛠️')">
                                                            <i class="bi bi-trash me-2"></i> Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <h5 class="card-title fw-bold text-dark mb-2"><?= htmlspecialchars($srv['service_name']) ?></h5>
                                    <p class="card-text text-muted flex-grow-1 small lh-base">
                                        <?= nl2br(htmlspecialchars($srv['description'] ?? 'No description provided.')) ?>
                                    </p>

                                    <div class="pt-3 mt-auto border-top d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-muted small d-block">Starting from</span>
                                            <span class="fw-bold text-success fs-5"><?= formatLKR($srv['price']) ?></span>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick='openEditService(<?= json_encode($srv) ?>)'>
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </button>
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

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-tools me-2 text-primary"></i>Add Gardening Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $actionUrl ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="service_name" class="form-control" placeholder="e.g. Lawn Mowing & Maintenance" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Starting Price (LKR) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="price" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Detailed Description</label>
                        <textarea name="description" rows="4" class="form-control" placeholder="Explain what is included, visit frequency, equipment, materials..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $actionUrl ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="service_id" id="editServiceId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="service_name" id="editServiceName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Starting Price (LKR) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="price" id="editServicePrice" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Detailed Description</label>
                        <textarea name="description" id="editServiceDescription" rows="4" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
<script>
function openEditService(service) {
    document.getElementById('editServiceId').value = service.id;
    document.getElementById('editServiceName').value = service.service_name;
    document.getElementById('editServicePrice').value = service.price;
    document.getElementById('editServiceDescription').value = service.description || '';
    new bootstrap.Modal(document.getElementById('editServiceModal')).show();
}
</script>
</body>
</html>
