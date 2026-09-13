<?php
/**
 * EcoSprout – Customer Plant Care Inquiries (Ask a Botanist)
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

// Fetch customer queries
$stmt = $pdo->prepare("
    SELECT q.*, s.full_name AS responder_name
    FROM queries q
    LEFT JOIN users s ON q.responded_by = s.id
    WHERE q.customer_id = ?
    ORDER BY q.created_at DESC
");
$stmt->execute([$userId]);
$myQueries = $stmt->fetchAll();

$pageHeading = 'Plant Care Inquiries';
$activePage  = 'queries';
$queryAction = $base . '/actions/query_action.php';
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
                    <p class="text-muted mb-0">Have a sick houseplant, pest issue, or soil question? Consult our nursery horticulturists.</p>
                </div>
                <div>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#newQueryModal">
                        <i class="bi bi-question-circle me-1"></i> Ask a Botanist
                    </button>
                </div>
            </div>

            <!-- Inquiries List -->
            <?php if (empty($myQueries)): ?>
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-chat-heart display-4 text-muted mb-3 d-block"></i>
                        <h5>No Queries Submitted Yet</h5>
                        <p class="text-muted">Need advice on repotting, yellowing leaves, or fertilizer? Ask our expert nursery team.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newQueryModal">
                            <i class="bi bi-chat-dots me-1"></i> Submit Plant Care Question
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($myQueries as $q): ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-dark fs-5"><?= htmlspecialchars($q['subject']) ?></span>
                                        <span class="text-muted small">&bull; <?= date('M d, Y', strtotime($q['created_at'])) ?></span>
                                    </div>
                                    <div>
                                        <?php if ($q['status'] === 'answered'): ?>
                                            <span class="badge bg-success rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i>Answered</span>
                                        <?php elseif ($q['status'] === 'open'): ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="bi bi-hourglass-split me-1"></i>Under Review by Botanist</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-3 py-2">Closed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card-body pt-0">
                                    <!-- Customer question -->
                                    <div class="p-3 bg-light rounded-3 mb-3 text-secondary" style="white-space: pre-wrap;"><?= htmlspecialchars($q['message']) ?></div>

                                    <!-- Botanical response if answered -->
                                    <?php if (!empty($q['response'])): ?>
                                        <div class="p-3 rounded-3 border-start border-4 border-success bg-success-subtle">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="fw-bold text-success-emphasis">
                                                    <i class="bi bi-patch-check-fill me-1"></i>
                                                    Botanical Response <?= $q['responder_name'] ? 'from ' . htmlspecialchars($q['responder_name']) : 'from EcoSprout Horticulturist' ?>
                                                </div>
                                            </div>
                                            <div class="text-dark small lh-lg" style="white-space: pre-wrap;"><?= htmlspecialchars($q['response']) ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="small text-muted fst-italic">
                                            <i class="bi bi-info-circle me-1"></i> Our nursery staff in Kegalle will review this ticket and publish botanical care advice shortly.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- New Query Modal -->
<div class="modal fade" id="newQueryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-flower2 me-2 text-primary"></i>Ask Our Plant Horticulturists</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $queryAction ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="submit">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject / Question Summary <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" 
                               placeholder="e.g. Yellow leaves on my Fiddle Leaf Fig, Brown spots on Monstera..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Detailed Symptoms & Environment <span class="text-danger">*</span></label>
                        <textarea name="message" rows="5" class="form-control" 
                                  placeholder="Describe how often you water, room lighting conditions (direct/indirect), pot drainage, and when symptoms first appeared..." required></textarea>
                    </div>
                    <div class="p-3 bg-light rounded-3 small text-muted">
                        <i class="bi bi-lightbulb text-warning me-1"></i>
                        Tip: Provide specific details about soil moisture and sunlight exposure to help our team diagnose accurately.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill me-1"></i>Submit Inquiry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
