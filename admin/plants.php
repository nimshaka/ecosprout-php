<?php
/**
 * EcoSprout – Admin/Staff Plant Management Page
 * Reusable for both admin/plants.php and staff/inventory.php
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

// ── Search / Filter ───────────────────────────────────────────
$search   = trim($_GET['search']   ?? '');
$category = trim($_GET['category'] ?? '');
$validCats = ['indoor','outdoor','ornamental','edible'];

// Build query
$where  = [];
$params = [];

if (!empty($search)) {
    $where[]  = "(plant_name LIKE ? OR botanical_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($category) && in_array($category, $validCats)) {
    $where[]  = "category = ?";
    $params[] = $category;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT * FROM plants $whereClause ORDER BY created_at DESC");
$stmt->execute($params);
$plants = $stmt->fetchAll();

$pageHeading = $role === 'admin' ? 'Plant Catalogue Management' : 'Plant Inventory';
$activePage  = $role === 'admin' ? 'plants' : 'inventory';
$actionUrl   = $base . '/actions/plant_action.php';
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
        <div class="page-content">
            <?php renderFlashes(); ?>

            <div class="eco-card">
                <div class="eco-card-header">
                    <h5><i class="bi bi-flower1 me-2"></i><?= htmlspecialchars($pageHeading) ?></h5>
                    <button class="btn btn-eco-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPlantModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Plant
                    </button>
                </div>
                <div class="eco-card-body">

                    <!-- Search / Filter Bar -->
                    <form method="GET" action="<?= $base ?>/<?= $role === 'admin' ? 'admin/plants.php' : 'staff/inventory.php' ?>" class="row g-2 mb-3">
                        <div class="col-md-5">
                            <input type="text" name="search" id="tableSearch" class="form-control form-control-sm"
                                   placeholder="Search by plant or botanical name…"
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($validCats as $cat): ?>
                                <option value="<?= $cat ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                    <?= ucfirst($cat) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-eco-primary btn-sm w-100">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="<?= $base ?>/<?= $role === 'admin' ? 'admin/plants.php' : 'staff/inventory.php' ?>" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
                        </div>
                    </form>

                    <!-- Plant Table -->
                    <div class="eco-table-wrap">
                        <table class="eco-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Plant Name</th>
                                    <th>Botanical Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($plants)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-4">
                                    No plants found. <?= !empty($search) || !empty($category) ? '<a href="?">Clear filters</a>' : '' ?>
                                </td></tr>
                                <?php else: ?>
                                <?php foreach ($plants as $p): ?>
                                <tr class="searchable-row <?= $p['stock_quantity'] <= 5 ? 'low-stock' : '' ?>">
                                    <td><?= $p['id'] ?></td>
                                    <td>
                                        <?php if (!empty($p['image']) && file_exists(__DIR__ . '/../assets/images/plants/' . $p['image'])): ?>
                                            <img src="<?= $base ?>/assets/images/plants/<?= htmlspecialchars($p['image']) ?>"
                                                 style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
                                        <?php else: ?>
                                            <div style="width:48px;height:48px;border-radius:8px;background:var(--eco-mint);display:flex;align-items:center;justify-content:center;font-size:1.5rem;">🌿</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold"><?= htmlspecialchars($p['plant_name']) ?></td>
                                    <td><em class="text-muted"><?= htmlspecialchars($p['botanical_name'] ?? 'N/A') ?></em></td>
                                    <td><?= categoryBadge($p['category']) ?></td>
                                    <td><?= formatLKR($p['price']) ?></td>
                                    <td>
                                        <?php if ($p['stock_quantity'] == 0): ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php elseif ($p['stock_quantity'] <= 5): ?>
                                            <span class="badge bg-warning text-dark badge-pulse"><?= $p['stock_quantity'] ?> ⚠</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?= $p['stock_quantity'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <!-- Edit button triggers modal -->
                                            <button class="btn btn-sm btn-outline-primary"
                                                    title="Edit"
                                                    onclick="openEditPlant(<?= htmlspecialchars(json_encode($p)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <!-- Delete form -->
                                            <form method="POST" action="<?= $actionUrl ?>" id="del-<?= $p['id'] ?>">
                                                <?php csrfField(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="plant_id" value="<?= $p['id'] ?>">
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                                        onclick="confirmDelete('del-<?= $p['id'] ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted small mt-2">Showing <?= count($plants) ?> plant(s).</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Add Plant Modal ────────────────────────────────────────── -->
<div class="modal fade" id="addPlantModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Plant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $actionUrl ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="create">
                <?php $plantFormId = 'addPlant'; ?>
                <?php include __DIR__ . '/../includes/plant_form_fields.php'; ?>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-eco-primary"><i class="bi bi-save me-1"></i>Save Plant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Edit Plant Modal ───────────────────────────────────────── -->
<div class="modal fade" id="editPlantModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Plant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $actionUrl ?>" enctype="multipart/form-data" class="needs-validation" novalidate id="editPlantForm">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="plant_id" id="editPlantId">
                <?php $plantFormId = 'editPlant'; ?>
                <?php include __DIR__ . '/../includes/plant_form_fields.php'; ?>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-eco-primary"><i class="bi bi-save me-1"></i>Update Plant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
<script>
function openEditPlant(plant) {
    const modal = document.getElementById('editPlantModal');
    const imageInput = modal.querySelector('#editPlantImageInput');
    const preview = modal.querySelector('#editPlantImagePreview');
    const previewWrap = modal.querySelector('#editPlantImagePreviewWrap');
    const fileName = modal.querySelector('#editPlantImageFileName');
    const imageUrl = modal.querySelector('#editPlantImageUrl');
    const removeBtn = modal.querySelector('#editPlantRemoveImageBtn');
    const removeFlag = modal.querySelector('#editPlantRemoveImageFlag');
    const currentBadge = modal.querySelector('#editPlantCurrentImageBadge');

    document.getElementById('editPlantId').value = plant.id;
    modal.querySelector('[name="plant_name"]').value        = plant.plant_name || '';
    modal.querySelector('[name="botanical_name"]').value    = plant.botanical_name || '';
    modal.querySelector('[name="category"]').value          = plant.category || 'indoor';
    modal.querySelector('[name="description"]').value       = plant.description || '';
    modal.querySelector('[name="care_instructions"]').value = plant.care_instructions || '';
    modal.querySelector('[name="price"]').value             = plant.price || '0.00';
    modal.querySelector('[name="stock_quantity"]').value    = plant.stock_quantity || '0';

    if (imageInput) imageInput.value = '';
    if (removeFlag) removeFlag.value = '0';
    if (imageUrl) { imageUrl.href = '#'; imageUrl.classList.add('d-none'); }
    if (preview && plant.image) {
        const imagePath = '<?= $base ?>/assets/images/plants/' + encodeURIComponent(plant.image);
        preview.src = imagePath;
        if (imageUrl) { imageUrl.href = imagePath; imageUrl.classList.remove('d-none'); }
        if (previewWrap) previewWrap.classList.remove('d-none');
        if (fileName) fileName.textContent = 'Current image';
        if (removeBtn) removeBtn.classList.remove('d-none');
        if (currentBadge) currentBadge.classList.remove('d-none');
    } else {
        if (preview) preview.src = '';
        if (previewWrap) previewWrap.classList.add('d-none');
        if (fileName) fileName.textContent = '';
        if (removeBtn) removeBtn.classList.add('d-none');
        if (currentBadge) currentBadge.classList.add('d-none');
    }

    new bootstrap.Modal(modal).show();
}
</script>
</body>
</html>
