<?php
/**
 * EcoSprout – Dynamic Sidebar Component
 *
 * Renders the correct sidebar menu based on the logged-in user's role.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$role       = $_SESSION['user_role'] ?? '';
$activePage = $activePage ?? 'dashboard';

// Helper: return 'active' CSS class if $page matches $activePage
$activeIf = fn(string $page): string => ($activePage === $page) ? 'active' : '';

$base = function_exists('getBaseUrl') ? getBaseUrl() : (defined('BASE_URL') ? BASE_URL : '/EcoSprout');
?>

<nav id="sidebar" class="sidebar d-flex flex-column">
    <!-- Brand -->
    <div class="sidebar-brand">
        <a href="<?= $base ?>/index.php" class="text-decoration-none">
            <span class="brand-icon"><i class="bi bi-tree-fill"></i></span>
            <span class="brand-name">EcoSprout</span>
        </a>
    </div>

    <!-- Role Badge -->
    <div class="sidebar-role-badge">
        <span class="badge rounded-pill bg-<?= $role === 'admin' ? 'danger' : ($role === 'staff' ? 'warning text-dark' : 'success') ?>">
            <i class="bi bi-person-fill me-1"></i>
            <?= ucfirst(htmlspecialchars($role)) ?>
        </span>
    </div>

    <!-- Navigation Links -->
    <ul class="sidebar-nav list-unstyled flex-grow-1">

        <?php if ($role === 'admin'): ?>
        <!-- ══ ADMIN MENU ══ -->
        <li class="nav-section-label">Main</li>

        <li class="sidebar-item <?= $activeIf('dashboard') ?>">
            <a href="<?= $base ?>/admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('plants') ?>">
            <a href="<?= $base ?>/admin/plants.php">
                <i class="bi bi-flower1"></i> Plant Catalogue
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('services') ?>">
            <a href="<?= $base ?>/admin/services.php">
                <i class="bi bi-tools"></i> Services
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('workshops') ?>">
            <a href="<?= $base ?>/admin/workshops.php">
                <i class="bi bi-calendar-event"></i> Workshops
            </a>
        </li>

        <li class="nav-section-label mt-2">Management</li>

        <li class="sidebar-item <?= $activeIf('staff') ?>">
            <a href="<?= $base ?>/admin/staff.php">
                <i class="bi bi-people-fill"></i> Staff & Users
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('orders') ?>">
            <a href="<?= $base ?>/admin/orders.php">
                <i class="bi bi-bag-check-fill"></i> Orders
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('queries') ?>">
            <a href="<?= $base ?>/admin/queries.php">
                <i class="bi bi-chat-dots-fill"></i> Customer Queries
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('reports') ?>">
            <a href="<?= $base ?>/admin/reports.php">
                <i class="bi bi-bar-chart-line-fill"></i> Sales Reports
            </a>
        </li>

        <?php elseif ($role === 'staff'): ?>
        <!-- ══ STAFF MENU ══ -->
        <li class="nav-section-label">Main</li>

        <li class="sidebar-item <?= $activeIf('dashboard') ?>">
            <a href="<?= $base ?>/staff/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('inventory') ?>">
            <a href="<?= $base ?>/staff/inventory.php">
                <i class="bi bi-flower1"></i> Plant Inventory
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('services') ?>">
            <a href="<?= $base ?>/staff/services.php">
                <i class="bi bi-tools"></i> Gardening Services
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('workshops') ?>">
            <a href="<?= $base ?>/staff/workshops.php">
                <i class="bi bi-calendar-event"></i> Workshops & Events
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('queries') ?>">
            <a href="<?= $base ?>/staff/queries.php">
                <i class="bi bi-chat-dots-fill"></i> Customer Queries
            </a>
        </li>

        <?php else: ?>
        <!-- ══ CUSTOMER MENU ══ -->
        <li class="nav-section-label">My Account</li>

        <li class="sidebar-item <?= $activeIf('dashboard') ?>">
            <a href="<?= $base ?>/customer/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('catalogue') ?>">
            <a href="<?= $base ?>/customer/catalogue.php">
                <i class="bi bi-flower1"></i> Browse Plants
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('services') ?>">
            <a href="<?= $base ?>/customer/services.php">
                <i class="bi bi-tools"></i> Gardening Services
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('cart') ?>">
            <a href="<?= $base ?>/customer/cart.php">
                <i class="bi bi-cart3"></i> My Cart
                <?php if (cartCount() > 0): ?>
                    <span class="badge bg-success rounded-pill ms-1"><?= cartCount() ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('orders') ?>">
            <a href="<?= $base ?>/customer/my-orders.php">
                <i class="bi bi-bag-check-fill"></i> My Orders
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('workshops') ?>">
            <a href="<?= $base ?>/customer/my-workshops.php">
                <i class="bi bi-calendar-event"></i> My Workshops
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('queries') ?>">
            <a href="<?= $base ?>/customer/my-queries.php">
                <i class="bi bi-chat-dots-fill"></i> My Queries
            </a>
        </li>
        <li class="sidebar-item <?= $activeIf('profile') ?>">
            <a href="<?= $base ?>/customer/profile.php">
                <i class="bi bi-person-circle"></i> My Profile
            </a>
        </li>
        <?php endif; ?>

    </ul>

    <!-- Logout at the bottom -->
    <div class="sidebar-footer">
        <a href="<?= $base ?>/logout.php" class="sidebar-logout-btn">
            <i class="bi bi-box-arrow-left me-2"></i> Logout
        </a>
    </div>
</nav>
