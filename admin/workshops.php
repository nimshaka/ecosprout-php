<?php
/**
 * EcoSprout – Workshops & Events Management (Admin / Staff)
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
    $whereClause = "WHERE w.title LIKE ? OR w.description LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Fetch workshops with registrations count
$stmt = $pdo->prepare("
    SELECT w.*, 
           COUNT(r.id) AS registered_count
    FROM workshops w
    LEFT JOIN workshop_registrations r ON w.id = r.workshop_id
    $whereClause
    GROUP BY w.id
    ORDER BY w.schedule_date ASC
");
$stmt->execute($params);
$workshops = $stmt->fetchAll();

// Attendees lookup per workshop for quick modal display
$attendeesStmt = $pdo->query("
    SELECT r.workshop_id, r.registered_at, u.full_name, u.email, u.phone
    FROM workshop_registrations r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.registered_at ASC
");
$allAttendees = [];
while ($row = $attendeesStmt->fetch()) {
    $allAttendees[$row['workshop_id']][] = $row;
}

// Stats
$totalWorkshops = count($workshops);
$upcomingCount = 0;
$totalAttendees = 0;
$now = date('Y-m-d H:i:s');
foreach ($workshops as $ws) {
    if ($ws['schedule_date'] >= $now) {
        $upcomingCount++;
    }
    $totalAttendees += (int)$ws['registered_count'];
}

$pageHeading = 'Workshops & Events';
$activePage  = 'workshops';
$actionUrl   = $base . '/actions/workshop_action.php';
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

            <!-- Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h2 class="h3 mb-1 text-dark fw-bold"><?= htmlspecialchars($pageHeading) ?></h2>
                    <p class="text-muted mb-0">Plan educational sessions, hands-on masterclasses, and manage participant rosters.</p>
                </div>
                <div>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addWorkshopModal">
                        <i class="bi bi-calendar-plus me-1"></i> Schedule New Workshop
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary-subtle text-primary">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div>
                                <div class="stat-label">Total Workshops</div>
                                <div class="stat-value"><?= $totalWorkshops ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success-subtle text-success">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <div class="stat-label">Upcoming Sessions</div>
                                <div class="stat-value"><?= $upcomingCount ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning-subtle text-warning">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Total Enrolled Participants</div>
                                <div class="stat-value"><?= $totalAttendees ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="" class="row g-2 align-items-center">
                        <div class="col-12 col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control bg-light border-start-0" 
                                       placeholder="Search workshop topic or details..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-12 col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-secondary flex-grow-1">Search</button>
                            <?php if ($search): ?>
                                <a href="<?= $base ?>/<?= $role ?>/workshops.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Workshop List -->
            <?php if (empty($workshops)): ?>
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-calendar-x display-4 text-muted mb-3 d-block"></i>
                        <h5>No Workshops Found</h5>
                        <p class="text-muted">Schedule your first gardening or plant care workshop today.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWorkshopModal">
                            <i class="bi bi-calendar-plus me-1"></i> Add Workshop
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($workshops as $ws): 
                        $isPast = strtotime($ws['schedule_date']) < time();
                        $pctBooked = $ws['capacity'] > 0 ? min(100, round(($ws['registered_count'] / $ws['capacity']) * 100)) : 0;
                        $attendeeList = $allAttendees[$ws['id']] ?? [];
                    ?>
                        <div class="col-12 col-lg-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <?php if ($isPast): ?>
                                                <span class="badge bg-secondary mb-2">Completed / Past</span>
                                            <?php elseif ($ws['registered_count'] >= $ws['capacity']): ?>
                                                <span class="badge bg-danger mb-2">Fully Booked</span>
                                            <?php else: ?>
                                                <span class="badge bg-success mb-2">Open for Booking</span>
                                            <?php endif; ?>
                                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($ws['title']) ?></h5>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border-0" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <button class="dropdown-item" 
                                                            onclick='openEditWorkshop(<?= json_encode($ws) ?>)'>
                                                        <i class="bi bi-pencil me-2 text-primary"></i> Edit Workshop
                                                    </button>
                                                </li>
                                                <li>
                                                    <form method="POST" action="<?= $actionUrl ?>" id="del-ws-<?= (int)$ws['id'] ?>"
                                                          data-eco-delete="<?= htmlspecialchars($ws['title'] ?? 'this workshop') ?>"
                                                          data-eco-icon="📅">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="workshop_id" value="<?= (int)$ws['id'] ?>">
                                                        <button type="button" class="dropdown-item text-danger"
                                                                onclick="confirmDelete('del-ws-<?= (int)$ws['id'] ?>',
                                                                  'Delete &lt;strong&gt;<?= htmlspecialchars(addslashes($ws['title'] ?? 'this workshop')) ?>&lt;/strong&gt; and all its registrations?', '📅')">
                                                            <i class="bi bi-trash me-2"></i> Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <p class="text-muted small flex-grow-1 mb-3">
                                        <?= nl2br(htmlspecialchars($ws['description'] ?? '')) ?>
                                    </p>

                                    <!-- Date & Fee Badges -->
                                    <div class="bg-light p-3 rounded-3 mb-3">
                                        <div class="row g-2 text-dark small">
                                            <div class="col-6">
                                                <i class="bi bi-calendar3 text-primary me-1"></i>
                                                <strong><?= date('M d, Y', strtotime($ws['schedule_date'])) ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <i class="bi bi-clock text-primary me-1"></i>
                                                <strong><?= date('h:i A', strtotime($ws['schedule_date'])) ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <i class="bi bi-cash text-success me-1"></i>
                                                Fee: <strong><?= (float)$ws['fee'] > 0 ? formatLKR($ws['fee']) : '<span class="text-success fw-bold">FREE</span>' ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <i class="bi bi-geo-alt text-danger me-1"></i>
                                                Matara Nursery
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Capacity Progress -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between small text-muted mb-1">
                                            <span>Enrollment</span>
                                            <span><strong><?= $ws['registered_count'] ?></strong> / <?= $ws['capacity'] ?> spots filled</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar <?= $pctBooked >= 100 ? 'bg-danger' : ($pctBooked >= 75 ? 'bg-warning' : 'bg-primary') ?>" 
                                                 role="progressbar" style="width: <?= $pctBooked ?>%"></div>
                                        </div>
                                    </div>

                                    <!-- Action footer -->
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                                        <button class="btn btn-outline-secondary btn-sm" 
                                                onclick='showAttendees(<?= (int)$ws['id'] ?>, <?= json_encode($ws['title']) ?>, <?= json_encode($attendeeList) ?>)'>
                                            <i class="bi bi-people me-1"></i> View Attendees (<?= count($attendeeList) ?>)
                                        </button>
                                        <button class="btn btn-outline-primary btn-sm" 
                                                onclick='openEditWorkshop(<?= json_encode($ws) ?>)'>
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

<!-- Add Workshop Modal -->
<div class="modal fade" id="addWorkshopModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus me-2 text-primary"></i>Schedule New Workshop</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $actionUrl ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Workshop Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Organic Composting Masterclass" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="schedule_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Maximum Capacity</label>
                            <input type="number" min="1" name="capacity" class="form-control" value="25" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Registration Fee (LKR)</label>
                        <input type="number" step="0.01" min="0" name="fee" class="form-control" value="0.00">
                        <div class="form-text">Set to 0 for complimentary / free community workshops.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description & Syllabus</label>
                        <textarea name="description" rows="4" class="form-control" placeholder="Session goals, instructor details, what participants should bring..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Publish Workshop</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Workshop Modal -->
<div class="modal fade" id="editWorkshopModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Workshop</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $actionUrl ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="workshop_id" id="editWsId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Workshop Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="editWsTitle" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="schedule_date" id="editWsSchedule" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Maximum Capacity</label>
                            <input type="number" min="1" name="capacity" id="editWsCapacity" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Registration Fee (LKR)</label>
                        <input type="number" step="0.01" min="0" name="fee" id="editWsFee" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="editWsDescription" rows="4" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Workshop</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Attendees Roster Modal -->
<div class="modal fade" id="attendeesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-people-fill me-2 text-primary"></i>Attendees Roster: <span id="rosterTitle" class="text-dark"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Participant Name</th>
                                <th>Email Address</th>
                                <th>Phone</th>
                                <th>Registered At</th>
                            </tr>
                        </thead>
                        <tbody id="rosterTableBody">
                        </tbody>
                    </table>
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
function openEditWorkshop(ws) {
    document.getElementById('editWsId').value = ws.id;
    document.getElementById('editWsTitle').value = ws.title;
    // Format YYYY-MM-DDTHH:mm for datetime-local
    const dt = new Date(ws.schedule_date.replace(' ', 'T'));
    const pad = num => String(num).padStart(2, '0');
    const formatted = `${dt.getFullYear()}-${pad(dt.getMonth()+1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
    document.getElementById('editWsSchedule').value = formatted;
    document.getElementById('editWsCapacity').value = ws.capacity;
    document.getElementById('editWsFee').value = ws.fee;
    document.getElementById('editWsDescription').value = ws.description || '';
    new bootstrap.Modal(document.getElementById('editWorkshopModal')).show();
}

function showAttendees(wsId, wsTitle, attendees) {
    document.getElementById('rosterTitle').textContent = wsTitle;
    const tbody = document.getElementById('rosterTableBody');
    tbody.innerHTML = '';

    if (!attendees || attendees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No attendees registered yet for this workshop.</td></tr>';
    } else {
        attendees.forEach((att, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${idx + 1}</td>
                <td class="fw-bold">${escapeHtml(att.full_name)}</td>
                <td>${escapeHtml(att.email)}</td>
                <td>${att.phone ? escapeHtml(att.phone) : '—'}</td>
                <td class="text-muted small">${escapeHtml(att.registered_at)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    new bootstrap.Modal(document.getElementById('attendeesModal')).show();
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
