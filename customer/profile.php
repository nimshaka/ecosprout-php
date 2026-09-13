<?php
/**
 * EcoSprout – Customer & User Profile Settings
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$pdo    = getPDO();
$base   = getBaseUrl();
$userId = getCurrentUserId();
$user   = getCurrentUser();

// Handle Profile Updates (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // Update Details
    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');

        if (empty($fullName)) {
            setFlash('error', 'Full name is required.');
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$fullName, $phone ?: null, $userId]);
            $_SESSION['user_name'] = $fullName;
            setFlash('success', 'Profile information updated successfully!');
        }
        redirect($base . '/customer/profile.php');
    }

    // Change Password
    if ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($currentPass) || empty($newPass)) {
            setFlash('error', 'All password fields are required.');
        } elseif ($newPass !== $confirmPass) {
            setFlash('error', 'New passwords do not match.');
        } elseif (mb_strlen($newPass) < 8) {
            setFlash('error', 'New password must be at least 8 characters long.');
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $hash = $stmt->fetchColumn();

            if (!password_verify($currentPass, $hash)) {
                setFlash('error', 'Current password is incorrect.');
            } else {
                $newHash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->execute([$newHash, $userId]);
                setFlash('success', 'Your password has been changed successfully!');
            }
        }
        redirect($base . '/customer/profile.php');
    }
}

// Refresh user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$pageHeading = 'Account Settings & Profile';
$activePage  = 'profile';
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
            <div class="mb-4">
                <h2 class="h3 mb-1 text-dark fw-bold"><?= htmlspecialchars($pageHeading) ?></h2>
                <p class="text-muted mb-0">Manage personal contact credentials, phone numbers, and login security.</p>
            </div>

            <div class="row g-4">
                <!-- Profile Information Form -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="card-title mb-0 fw-bold">
                                <i class="bi bi-person-lines-fill text-primary me-2"></i>Personal Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_profile">

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Email Address</label>
                                    <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                    <div class="form-text">Account email cannot be modified. Contact administration for email updates.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Contact Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="077 123 4567">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Account Role</label>
                                    <div>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill text-capitalize">
                                            <?= htmlspecialchars($user['role']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Member Since</label>
                                    <div class="text-muted small">
                                        <?= date('F d, Y', strtotime($user['created_at'])) ?>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Password Change Form -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="card-title mb-0 fw-bold">
                                <i class="bi bi-shield-lock-fill text-warning me-2"></i>Change Security Password
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="change_password">

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                                    <input type="password" name="current_password" class="form-control" placeholder="Enter your current password" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                                    <input type="password" name="new_password" class="form-control" placeholder="Minimum 8 characters" minlength="8" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control" placeholder="Re-type new password" minlength="8" required>
                                </div>

                                <button type="submit" class="btn btn-warning text-dark fw-semibold">
                                    <i class="bi bi-key-fill me-1"></i> Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
