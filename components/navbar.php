<?php
/**
 * EcoSprout – Top Navbar Component
 *
 * Displays: hamburger toggle, page title, user badge, and logout.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageHeading = $pageHeading ?? 'Dashboard';
$userName    = htmlspecialchars($_SESSION['user_name']  ?? 'User');
$userRole    = htmlspecialchars($_SESSION['user_role']  ?? '');
$userEmail   = htmlspecialchars($_SESSION['user_email'] ?? '');
$base = function_exists('getBaseUrl') ? getBaseUrl() : (defined('BASE_URL') ? BASE_URL : '/EcoSprout');

$roleBadge = match($userRole) {
    'admin'  => 'danger',
    'staff'  => 'warning',
    default  => 'success',
};
?>

<nav class="app-navbar navbar">
    <!-- Sidebar Toggle -->
    <button id="sidebarToggle" class="btn btn-link sidebar-toggle-btn" type="button" aria-label="Toggle sidebar">
        <i class="bi bi-list fs-4"></i>
    </button>

    <!-- Page Heading -->
    <h1 class="navbar-page-title mb-0 fs-5 fw-semibold"><?= htmlspecialchars($pageHeading) ?></h1>

    <!-- Right side: user info + logout -->
    <div class="ms-auto d-flex align-items-center gap-3">
        <!-- Cart icon for customers -->
        <?php if ($userRole === 'customer'): ?>
        <a href="<?= $base ?>/customer/cart.php" class="btn btn-sm btn-outline-success position-relative" title="Cart">
            <i class="bi bi-cart3"></i>
            <?php if (cartCount() > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= cartCount() ?>
                </span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <!-- User dropdown -->
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar-circle bg-<?= $roleBadge ?>">
                    <?= strtoupper(substr($userName, 0, 1)) ?>
                </span>
                <span class="d-none d-md-inline"><?= $userName ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li>
                    <span class="dropdown-item-text small text-muted"><?= $userEmail ?></span>
                </li>
                <li>
                    <span class="dropdown-item-text">
                        <span class="badge bg-<?= $roleBadge ?>"><?= ucfirst($userRole) ?></span>
                    </span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <?php if ($userRole === 'customer'): ?>
                <li>
                    <a class="dropdown-item" href="<?= $base ?>/customer/profile.php">
                        <i class="bi bi-person me-2"></i> My Profile
                    </a>
                </li>
                <?php endif; ?>
                <li>
                          <a class="dropdown-item text-danger" href="<?= $base ?>/logout.php"
                              onclick="confirmNavigation('<?= $base ?>/logout.php'); return false;">
                        <i class="bi bi-box-arrow-left me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
