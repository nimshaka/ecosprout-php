<?php
/**
 * EcoSprout – Customer Plant Catalogue
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['customer']);

$pdo  = getPDO();
$base = getBaseUrl();

// ── Search & Filter ───────────────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort     = trim($_GET['sort'] ?? 'newest');
$validCats = ['indoor', 'outdoor', 'ornamental', 'edible'];

$where  = [];
$params = [];

if (!empty($search)) {
    $where[]  = "(plant_name LIKE ? OR botanical_name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($category) && in_array($category, $validCats)) {
    $where[]  = "category = ?";
    $params[] = $category;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderBy = match($sort) {
    'price_asc'  => 'ORDER BY price ASC',
    'price_desc' => 'ORDER BY price DESC',
    'name'       => 'ORDER BY plant_name ASC',
    default      => 'ORDER BY created_at DESC'
};

$stmt = $pdo->prepare("SELECT * FROM plants $whereClause $orderBy");
$stmt->execute($params);
$plants = $stmt->fetchAll();

$pageHeading = 'Browse Plant Catalogue';
$activePage  = 'catalogue';
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
                    <p class="text-muted mb-0">Explore healthy potted plants, exotic ornamentals, and home orchard varieties grown in Kegalle.</p>
                </div>
                <div>
                    <a href="<?= $base ?>/customer/cart.php" class="btn btn-outline-success shadow-sm">
                        <i class="bi bi-cart3 me-1"></i> View Cart
                        <?php if (cartCount() > 0): ?>
                            <span class="badge bg-success text-white rounded-pill ms-1"><?= cartCount() ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Category Quick Pills -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="<?= $base ?>/customer/catalogue.php" class="btn btn-sm <?= empty($category) ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill px-3">
                    All Plants
                </a>
                <a href="<?= $base ?>/customer/catalogue.php?category=indoor" class="btn btn-sm <?= $category === 'indoor' ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill px-3">
                    🌿 Indoor Plants
                </a>
                <a href="<?= $base ?>/customer/catalogue.php?category=outdoor" class="btn btn-sm <?= $category === 'outdoor' ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill px-3">
                    🌳 Outdoor & Shade
                </a>
                <a href="<?= $base ?>/customer/catalogue.php?category=ornamental" class="btn btn-sm <?= $category === 'ornamental' ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill px-3">
                    🌸 Ornamental & Floral
                </a>
                <a href="<?= $base ?>/customer/catalogue.php?category=edible" class="btn btn-sm <?= $category === 'edible' ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill px-3">
                    🍋 Edible & Herbs
                </a>
            </div>

            <!-- Search & Filter Bar -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="" class="row g-2 align-items-center">
                        <div class="col-12 col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control bg-light border-start-0" 
                                       placeholder="Search plant name, botanical name, or care requirements..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <select name="sort" class="form-select bg-light">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Alphabetical</option>
                            </select>
                        </div>
                        <?php if ($category): ?>
                            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                        <?php endif; ?>
                        <div class="col-6 col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                            <?php if ($search || $category || $sort !== 'newest'): ?>
                                <a href="<?= $base ?>/customer/catalogue.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Plant Cards Grid -->
            <?php if (empty($plants)): ?>
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-tree display-4 text-muted mb-3 d-block"></i>
                        <h5>No plants matched your selection</h5>
                        <p class="text-muted">Try clearing your search terms or picking another category.</p>
                        <a href="<?= $base ?>/customer/catalogue.php" class="btn btn-primary">Browse All Plants</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($plants as $p): 
                        $inStock = (int)$p['stock_quantity'] > 0;
                        $lowStock = $inStock && (int)$p['stock_quantity'] <= 5;
                    ?>
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                            <div class="card border-0 shadow-sm h-100 plant-card d-flex flex-column">
                                <!-- Plant Image Container -->
                                <div class="position-relative bg-light rounded-top overflow-hidden" style="height: 200px;">
                                    <?php if (!empty($p['image'])): ?>
                                        <img src="<?= $base ?>/assets/images/plants/<?= htmlspecialchars($p['image']) ?>" 
                                             alt="<?= htmlspecialchars($p['plant_name']) ?>" 
                                             class="w-100 h-100 object-fit-cover">
                                    <?php else: ?>
                                        <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-success-emphasis bg-success-subtle">
                                            <i class="bi bi-flower1 display-4"></i>
                                            <span class="small text-muted mt-1">EcoSprout Nursery</span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Category Pill -->
                                    <span class="badge bg-dark bg-opacity-75 position-absolute top-0 start-0 m-2 rounded-pill text-capitalize">
                                        <?= htmlspecialchars($p['category']) ?>
                                    </span>

                                    <!-- Stock Pill -->
                                    <span class="badge position-absolute top-0 end-0 m-2 rounded-pill <?= !$inStock ? 'bg-danger' : ($lowStock ? 'bg-warning text-dark' : 'bg-success') ?>">
                                        <?= !$inStock ? 'Out of Stock' : ($lowStock ? 'Only ' . $p['stock_quantity'] . ' Left' : 'In Stock') ?>
                                    </span>
                                </div>

                                <!-- Plant Info -->
                                <div class="card-body d-flex flex-column p-3">
                                    <h5 class="fw-bold text-dark mb-1 text-truncate" title="<?= htmlspecialchars($p['plant_name']) ?>">
                                        <?= htmlspecialchars($p['plant_name']) ?>
                                    </h5>
                                    <?php if (!empty($p['botanical_name'])): ?>
                                        <small class="text-muted fst-italic mb-2 d-block text-truncate">
                                            <?= htmlspecialchars($p['botanical_name']) ?>
                                        </small>
                                    <?php endif; ?>

                                    <p class="text-secondary small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?= htmlspecialchars($p['description'] ?? 'Cultivated sustainably in Sri Lankan tropical climate.') ?>
                                    </p>

                                    <!-- Price & Cart Form -->
                                    <div class="pt-2 border-top mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-success fs-5"><?= formatLKR($p['price']) ?></span>
                                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0" 
                                                    onclick='viewCareGuide(<?= json_encode($p) ?>)'>
                                                <i class="bi bi-info-circle me-1"></i> Care Guide
                                            </button>
                                        </div>

                                        <?php if ($inStock): ?>
                                            <form method="POST" action="<?= $cartAction ?>" class="d-flex gap-2">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="plant_id" value="<?= (int)$p['id'] ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-success w-100 btn-sm fw-semibold">
                                                    <i class="bi bi-cart-plus me-1"></i> Add to Cart
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-secondary w-100 btn-sm disabled" disabled>
                                                Currently Unavailable
                                            </button>
                                        <?php endif; ?>
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

<!-- Plant Care Guide Modal -->
<div class="modal fade" id="careModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="carePlantName"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fst-italic text-muted small mb-2" id="careBotanicalName"></p>
                <div class="p-3 bg-light rounded-3 mb-3">
                    <h6 class="fw-bold text-dark mb-1">Description</h6>
                    <p class="small text-secondary mb-0" id="careDescription"></p>
                </div>
                <div class="p-3 bg-success-subtle rounded-3">
                    <h6 class="fw-bold text-success-emphasis mb-2"><i class="bi bi-heart-pulse-fill me-1"></i>Care Instructions</h6>
                    <p class="small text-dark mb-0" id="careInstructions" style="white-space: pre-wrap;"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
<script>
function viewCareGuide(plant) {
    document.getElementById('carePlantName').textContent = plant.plant_name;
    document.getElementById('careBotanicalName').textContent = plant.botanical_name ? `Botanical: ${plant.botanical_name}` : '';
    document.getElementById('careDescription').textContent = plant.description || 'No detailed description available.';
    document.getElementById('careInstructions').textContent = plant.care_instructions || 'Moderate watering, bright indirect sunlight, well-draining soil mix.';
    new bootstrap.Modal(document.getElementById('careModal')).show();
}
</script>
</body>
</html>
