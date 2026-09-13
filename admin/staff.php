<?php
/**
 * EcoSprout – Admin Staff & User Management
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['admin']);

$pdo     = getPDO();
$base    = getBaseUrl();
$adminId = getCurrentUserId();

// ── Search & Filter ───────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$validRoles = ['admin', 'staff', 'customer'];

$where  = [];
$params = [];

if (!empty($search)) {
    $where[]  = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($roleFilter) && in_array($roleFilter, $validRoles)) {
    $where[]  = "role = ?";
    $params[] = $roleFilter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT * FROM users $whereClause ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Statistics counts
$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalStaff = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetchColumn();
$totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$totalCustomers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();

$pageHeading = 'Staff & User Management';
$activePage  = 'staff';
$actionUrl   = $base . '/actions/staff_action.php';
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
                    <p class="text-muted mb-0">Manage platform accounts, assign roles, and control access permissions.</p>
                </div>
                <div>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus me-1"></i> Add New Staff/Admin
                    </button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary-subtle text-primary">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Total Users</div>
                                <div class="stat-value"><?= number_format($totalUsers) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning-subtle text-warning">
                                <i class="bi bi-person-badge-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Nursery Staff</div>
                                <div class="stat-value"><?= number_format($totalStaff) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-danger-subtle text-danger">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <div>
                                <div class="stat-label">Administrators</div>
                                <div class="stat-value"><?= number_format($totalAdmins) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success-subtle text-success">
                                <i class="bi bi-person-heart"></i>
                            </div>
                            <div>
                                <div class="stat-label">Customers</div>
                                <div class="stat-value"><?= number_format($totalCustomers) ?></div>
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
                                       placeholder="Search by name, email, or phone..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <select name="role" class="form-select bg-light">
                                <option value="">All Roles</option>
                                <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="staff" <?= $roleFilter === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="customer" <?= $roleFilter === 'customer' ? 'selected' : '' ?>>Customer</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-secondary flex-grow-1">Filter</button>
                            <?php if ($search || $roleFilter): ?>
                                <a href="<?= $base ?>/admin/staff.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="card-title mb-0 fw-bold">User Accounts (<?= count($users) ?>)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-person-x display-6 d-block mb-2"></i>
                                        No users found matching your criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td class="text-muted small">#<?= (int)$u['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-sm rounded-circle bg-light text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($u['full_name']) ?></span>
                                                    <span class="text-muted small"><?= htmlspecialchars($u['email']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?= $u['phone'] ? htmlspecialchars($u['phone']) : '<span class="text-muted">—</span>' ?>
                                        </td>
                                        <td>
                                            <?php if ($u['role'] === 'admin'): ?>
                                                <span class="badge bg-danger rounded-pill">Admin</span>
                                            <?php elseif ($u['role'] === 'staff'): ?>
                                                <span class="badge bg-warning text-dark rounded-pill">Staff</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark rounded-pill">Customer</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($u['status'] ?? 'enabled') === 'enabled'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Disabled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= date('M d, Y', strtotime($u['created_at'])) ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <?php if ((int)$u['id'] !== (int)$adminId): ?>
                                                    <!-- Toggle Status Form -->
                                                    <form method="POST" action="<?= $actionUrl ?>" class="d-inline" onsubmit="return confirm('Toggle status for this user?');">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-<?= ($u['status'] ?? 'enabled') === 'enabled' ? 'warning' : 'success' ?>" title="<?= ($u['status'] ?? 'enabled') === 'enabled' ? 'Disable Account' : 'Enable Account' ?>">
                                                            <i class="bi bi-<?= ($u['status'] ?? 'enabled') === 'enabled' ? 'slash-circle' : 'check-circle' ?>"></i>
                                                        </button>
                                                    </form>

                                                    <!-- Change Role Button -->
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                            onclick='openChangeRole(<?= (int)$u['id'] ?>, <?= json_encode($u['full_name']) ?>, <?= json_encode($u['role']) ?>)' 
                                                            title="Change Role">
                                                        <i class="bi bi-shield-shaded"></i>
                                                    </button>

                                                    <!-- Delete User Form -->
                                                    <form method="POST" action="<?= $actionUrl ?>" class="d-inline"
                                                          id="del-usr-<?= (int)$u['id'] ?>">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Delete User"
                                                                onclick="confirmDelete('del-usr-<?= (int)$u['id'] ?>',
                                                                  'Permanently delete user &lt;strong&gt;<?= htmlspecialchars(addslashes($u['full_name'] ?? 'this user')) ?>&lt;/strong&gt;? All their data may be affected.', '👤')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border">Current User</span>
                                                <?php endif; ?>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Add Staff or Admin Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $actionUrl ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_user">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Ruwan Silva" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="user@ecosprout.lk" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" placeholder="077 123 4567">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="staff" selected>Nursery Staff</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" minlength="8" required>
                        <div class="form-text">Temporary password can be updated upon logging in.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Role Modal -->
<div class="modal fade" id="changeRoleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Change Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $actionUrl ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="change_role">
                <input type="hidden" name="user_id" id="changeRoleUserId">
                <div class="modal-body">
                    <p class="mb-2 small text-muted">User: <strong id="changeRoleUserName" class="text-dark"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select New Role</label>
                        <select name="role" id="changeRoleSelect" class="form-select" required>
                            <option value="customer">Customer</option>
                            <option value="staff">Nursery Staff</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Update Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
<script>
function openChangeRole(userId, userName, currentRole) {
    document.getElementById('changeRoleUserId').value = userId;
    document.getElementById('changeRoleUserName').textContent = userName;
    document.getElementById('changeRoleSelect').value = currentRole;
    new bootstrap.Modal(document.getElementById('changeRoleModal')).show();
}
</script>
</body>
</html>
