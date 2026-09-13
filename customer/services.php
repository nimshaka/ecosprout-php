<?php
/**
 * EcoSprout – Customer Gardening Services & Booking
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

// Handle Service Booking Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_service') {
    verifyCsrf();
    $serviceId    = (int) ($_POST['service_id'] ?? 0);
    $serviceName  = trim($_POST['service_name'] ?? '');
    $preferredDate = trim($_POST['preferred_date'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');

    if (empty($serviceName) || empty($preferredDate) || empty($address)) {
        setFlash('error', 'Please fill in all required booking details (service, preferred date, and property address).');
        redirect($base . '/customer/services.php');
    }

    $subject = "Service Booking Request: " . $serviceName;
    $message = "Customer: " . ($user['full_name'] ?? 'Customer') . "\n"
             . "Contact Phone: " . ($phone ?: ($user['phone'] ?? 'Not provided')) . "\n"
             . "Preferred Date: " . $preferredDate . "\n"
             . "Property Address: " . $address . "\n"
             . "Notes & Requirements: " . ($notes ?: 'None');

    try {
        $stmt = $pdo->prepare("INSERT INTO queries (customer_id, subject, message, status) VALUES (?, ?, ?, 'open')");
        $stmt->execute([$userId, $subject, $message]);
        setFlash('success', 'Booking request for "' . htmlspecialchars($serviceName) . '" sent! Our team will contact you to confirm timing.');
        redirect($base . '/customer/my-queries.php');
    } catch (PDOException $e) {
        error_log('[EcoSprout] Service booking error: ' . $e->getMessage());
        setFlash('error', 'Could not submit your booking request. Please try again.');
        redirect($base . '/customer/services.php');
    }
}

// Search
$search = trim($_GET['search'] ?? '');
$params = [];
$whereClause = '';
if (!empty($search)) {
    $whereClause = "WHERE service_name LIKE ? OR description LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT * FROM gardening_services $whereClause ORDER BY price ASC");
$stmt->execute($params);
$services = $stmt->fetchAll();

$pageHeading = 'Gardening & Landscaping Services';
$activePage  = 'services';
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

            <!-- Page Hero -->
            <div class="p-4 rounded-4 mb-4 text-white shadow-sm" style="background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2 mb-2">
                            <i class="bi bi-tools me-1"></i> Professional Nursery Services
                        </span>
                        <h2 class="fw-bold mb-2">Expert Gardening & Landscape Solutions</h2>
                        <p class="text-white-50 mb-3">
                            From complete lawn design and tree pruning to drip irrigation installations in Matara, our certified horticulturists ensure your green spaces flourish sustainably.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="#services-list" class="btn btn-warning text-dark fw-semibold">
                                <i class="bi bi-calendar-check me-1"></i> Request a Consultation
                            </a>
                            <a href="<?= $base ?>/customer/my-queries.php" class="btn btn-outline-light">
                                <i class="bi bi-chat-dots me-1"></i> View My Inquiries
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Filter Bar -->
            <div class="card border-0 shadow-sm mb-4" id="services-list">
                <div class="card-body p-3">
                    <form method="GET" action="" class="row g-2 align-items-center">
                        <div class="col-12 col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control bg-light border-start-0" 
                                       placeholder="Search gardening service packages, lawn maintenance, pruning..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-12 col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                            <?php if ($search): ?>
                                <a href="<?= $base ?>/customer/services.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Services Grid -->
            <?php if (empty($services)): ?>
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-tools display-4 text-muted mb-3 d-block"></i>
                        <h5>No Gardening Services Matched Your Search</h5>
                        <p class="text-muted">Try clearing search keywords or check back soon.</p>
                        <a href="<?= $base ?>/customer/services.php" class="btn btn-primary">Browse All Services</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php 
                    $iconList = ['bi-flower3', 'bi-scissors', 'bi-droplet-half', 'bi-tree', 'bi-recycle', 'bi-map'];
                    $i = 0;
                    foreach ($services as $srv): 
                        $icon = $iconList[$i % count($iconList)];
                        $i++;
                    ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card border-0 shadow-sm h-100 service-card d-flex flex-column">
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="service-icon-box bg-success-subtle text-success rounded-3 p-3">
                                            <i class="bi <?= $icon ?> fs-3"></i>
                                        </div>
                                        <span class="badge bg-light text-secondary border">Matara & Surrounds</span>
                                    </div>

                                    <h5 class="card-title fw-bold text-dark mb-2"><?= htmlspecialchars($srv['service_name']) ?></h5>
                                    <p class="card-text text-secondary small flex-grow-1 lh-base">
                                        <?= nl2br(htmlspecialchars($srv['description'] ?? 'Comprehensive professional gardening service.')) ?>
                                    </p>

                                    <div class="bg-light rounded-3 p-2 my-3 small text-muted">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bi bi-check-circle-fill text-success"></i> Includes equipment & materials
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-shield-check text-primary"></i> Trained eco-horticulturist staff
                                        </div>
                                    </div>

                                    <div class="pt-3 border-top mt-auto d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-muted small d-block">Starting from</span>
                                            <span class="fw-bold text-success fs-5"><?= formatLKR($srv['price']) ?></span>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold" 
                                                onclick='openBookService(<?= json_encode($srv) ?>)'>
                                            <i class="bi bi-calendar-plus me-1"></i> Book Now
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

<!-- Book Service Modal -->
<div class="modal fade" id="bookServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar-check me-2 text-primary"></i>Book Gardening Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $base ?>/customer/services.php" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="action" value="book_service">
                <input type="hidden" name="service_id" id="bookServiceId">
                <input type="hidden" name="service_name" id="bookServiceNameHidden">

                <div class="modal-body">
                    <div class="alert alert-success-subtle p-3 rounded-3 mb-3 border-0">
                        <div class="fw-bold text-success" id="bookServiceNameDisplay">Service Name</div>
                        <div class="small text-muted" id="bookServicePriceDisplay">Starting Price</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Preferred Service Date <span class="text-danger">*</span></label>
                        <input type="date" name="preferred_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
                        <div class="form-text">Choose your ideal date for on-site visit or work start.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contact Phone <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '077 123 4567') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Property Address in Matara / Region <span class="text-danger">*</span></label>
                        <textarea name="address" rows="2" class="form-control" placeholder="Street address, town, and nearest landmark in Matara..." required>Matara, Sri Lanka</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Specific Garden Notes / Special Requests</label>
                        <textarea name="notes" rows="3" class="form-control" placeholder="Garden size, current conditions, plant types, or preferred time of day..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit Booking Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
<script>
function openBookService(service) {
    document.getElementById('bookServiceId').value = service.id;
    document.getElementById('bookServiceNameHidden').value = service.service_name;
    document.getElementById('bookServiceNameDisplay').textContent = service.service_name;
    document.getElementById('bookServicePriceDisplay').textContent = 'Starting from LKR ' + Number(service.price).toFixed(2);
    new bootstrap.Modal(document.getElementById('bookServiceModal')).show();
}
</script>
</body>
</html>
