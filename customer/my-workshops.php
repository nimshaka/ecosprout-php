<?php
/**
 * EcoSprout – Customer Workshops & Events
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

// Customer's registered workshop IDs
$regStmt = $pdo->prepare("SELECT workshop_id FROM workshop_registrations WHERE user_id = ?");
$regStmt->execute([$userId]);
$myRegIds = $regStmt->fetchAll(PDO::FETCH_COLUMN);

// All workshops with participant counts
$allWsStmt = $pdo->query("
    SELECT w.*, COUNT(r.id) AS registered_count
    FROM workshops w
    LEFT JOIN workshop_registrations r ON w.id = r.workshop_id
    GROUP BY w.id
    ORDER BY w.schedule_date ASC
");
$allWorkshops = $allWsStmt->fetchAll();

$pageHeading = 'Gardening Workshops & Masterclasses';
$activePage  = 'workshops';
$wsAction    = $base . '/actions/workshop_action.php';
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
                    <p class="text-muted mb-0">Learn organic composting, bonsai cultivation, and tropical indoor plant care directly from experts.</p>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-pills mb-4" id="workshopTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active px-4 py-2 fw-semibold" id="all-tab" data-bs-toggle="pill" data-bs-target="#all-workshops" type="button">
                        <i class="bi bi-calendar2-range me-1"></i> All Workshops (<?= count($allWorkshops) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link px-4 py-2 fw-semibold" id="my-tab" data-bs-toggle="pill" data-bs-target="#my-workshops" type="button">
                        <i class="bi bi-bookmark-check-fill me-1"></i> My Enrolled Workshops (<?= count($myRegIds) ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="workshopTabContent">
                <!-- Tab 1: All Workshops -->
                <div class="tab-pane fade show active" id="all-workshops">
                    <?php if (empty($allWorkshops)): ?>
                        <div class="card border-0 shadow-sm text-center py-5">
                            <div class="card-body">
                                <i class="bi bi-calendar-x display-4 text-muted mb-3 d-block"></i>
                                <h5>No Workshops Currently Scheduled</h5>
                                <p class="text-muted">Check back soon for new hands-on sessions at our Kegalle nursery.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($allWorkshops as $ws): 
                                $isRegistered = in_array((int)$ws['id'], $myRegIds);
                                $isFull = (int)$ws['registered_count'] >= (int)$ws['capacity'];
                                $isPast = strtotime($ws['schedule_date']) < time();
                            ?>
                                <div class="col-12 col-md-6">
                                    <div class="card border-0 shadow-sm h-100 <?= $isRegistered ? 'border-start border-4 border-success' : '' ?>">
                                        <div class="card-body d-flex flex-column">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <?php if ($isPast): ?>
                                                        <span class="badge bg-secondary mb-2">Past Event</span>
                                                    <?php elseif ($isRegistered): ?>
                                                        <span class="badge bg-success mb-2"><i class="bi bi-check-circle me-1"></i>You are Enrolled</span>
                                                    <?php elseif ($isFull): ?>
                                                        <span class="badge bg-danger mb-2">Fully Booked</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary mb-2">Registration Open</span>
                                                    <?php endif; ?>
                                                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($ws['title']) ?></h5>
                                                </div>
                                                <span class="badge bg-light text-dark border fs-6">
                                                    <?= (float)$ws['fee'] > 0 ? formatLKR($ws['fee']) : '<span class="text-success fw-bold">FREE</span>' ?>
                                                </span>
                                            </div>

                                            <p class="text-secondary small mb-3 flex-grow-1">
                                                <?= nl2br(htmlspecialchars($ws['description'] ?? '')) ?>
                                            </p>

                                            <!-- Details Meta -->
                                            <div class="bg-light p-3 rounded-3 mb-3 small">
                                                <div class="row g-2 text-dark">
                                                    <div class="col-6">
                                                        <i class="bi bi-calendar3 text-primary me-1"></i>
                                                        <strong><?= date('M d, Y', strtotime($ws['schedule_date'])) ?></strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <i class="bi bi-clock text-primary me-1"></i>
                                                        <strong><?= date('h:i A', strtotime($ws['schedule_date'])) ?></strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <i class="bi bi-geo-alt text-danger me-1"></i>
                                                        EcoSprout Nursery, Kegalle
                                                    </div>
                                                    <div class="col-6">
                                                        <i class="bi bi-people text-info me-1"></i>
                                                        <?= $ws['registered_count'] ?> / <?= $ws['capacity'] ?> enrolled
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Action Button -->
                                            <div class="pt-2 border-top mt-auto">
                                                <?php if ($isPast): ?>
                                                    <button class="btn btn-secondary btn-sm w-100 disabled" disabled>Event Concluded</button>
                                                <?php elseif ($isRegistered): ?>
                                                    <form method="POST" action="<?= $wsAction ?>" onsubmit="return confirm('Unregister from this workshop?');">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="unregister">
                                                        <input type="hidden" name="workshop_id" value="<?= (int)$ws['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                                            <i class="bi bi-x-circle me-1"></i> Cancel Registration
                                                        </button>
                                                    </form>
                                                <?php elseif ($isFull): ?>
                                                    <button class="btn btn-secondary btn-sm w-100 disabled" disabled>Registration Full</button>
                                                <?php else: ?>
                                                    <form method="POST" action="<?= $wsAction ?>">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="register">
                                                        <input type="hidden" name="workshop_id" value="<?= (int)$ws['id'] ?>">
                                                        <button type="submit" class="btn btn-success btn-sm w-100 fw-semibold">
                                                            <i class="bi bi-check2-circle me-1"></i> Book My Seat
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab 2: My Enrolled Workshops -->
                <div class="tab-pane fade" id="my-workshops">
                    <?php 
                    $myWorkshopsList = array_filter($allWorkshops, fn($ws) => in_array((int)$ws['id'], $myRegIds));
                    ?>
                    <?php if (empty($myWorkshopsList)): ?>
                        <div class="card border-0 shadow-sm text-center py-5">
                            <div class="card-body">
                                <i class="bi bi-calendar2-check display-4 text-muted mb-3 d-block"></i>
                                <h5>You haven't enrolled in any workshops yet</h5>
                                <p class="text-muted">Explore upcoming sessions in the first tab and secure your spot!</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($myWorkshopsList as $mws): ?>
                                <div class="col-12 col-md-6">
                                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                                        <div class="card-body d-flex flex-column">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($mws['title']) ?></h5>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Confirmed</span>
                                            </div>
                                            <p class="text-secondary small mb-3 flex-grow-1">
                                                <?= nl2br(htmlspecialchars($mws['description'] ?? '')) ?>
                                            </p>
                                            <div class="bg-light p-3 rounded-3 mb-3 small">
                                                <div class="row g-2 text-dark">
                                                    <div class="col-6"><i class="bi bi-calendar3 text-primary me-1"></i><strong><?= date('M d, Y', strtotime($mws['schedule_date'])) ?></strong></div>
                                                    <div class="col-6"><i class="bi bi-clock text-primary me-1"></i><strong><?= date('h:i A', strtotime($mws['schedule_date'])) ?></strong></div>
                                                </div>
                                            </div>
                                            <form method="POST" action="<?= $wsAction ?>" onsubmit="return confirm('Cancel your registration?');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="unregister">
                                                <input type="hidden" name="workshop_id" value="<?= (int)$mws['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                                    <i class="bi bi-x-circle me-1"></i> Cancel Registration
                                                </button>
                                            </form>
                                        </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
