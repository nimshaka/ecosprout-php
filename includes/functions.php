<?php
/**
 * EcoSprout – Shared Utility Functions
 */

// ── Flash Messages ────────────────────────────────────────────

/**
 * Store a flash message in the session.
 * Type: 'success' | 'error' | 'info' | 'warning'
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

/**
 * Retrieve and clear all flash messages.
 * Returns an array keyed by type.
 */
function getFlashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Render Bootstrap 5 alert HTML for all pending flash messages.
 * Call this once inside the page layout (after navbar, before main content).
 */
function renderFlashes(): void
{
    $flashes = getFlashes();
    if (empty($flashes)) return;

    $map = [
        'success' => 'success',
        'error'   => 'danger',
        'info'    => 'info',
        'warning' => 'warning',
    ];

    foreach ($flashes as $type => $messages) {
        $bsClass = $map[$type] ?? 'secondary';
        foreach ($messages as $msg) {
            echo '<div class="alert alert-' . $bsClass
                . ' alert-dismissible fade show" role="alert">'
                . htmlspecialchars($msg)
                . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
                . '</div>';
        }
    }
}

/**
 * Render flash messages (alias for renderFlashes).
 */
function renderFlash(): void
{
    renderFlashes();
}

// ── Input Sanitization ────────────────────────────────────────

/**
 * Sanitize a string input: trim whitespace and remove tags.
 * Always run user input through this before using it.
 */
function sanitize(string $value): string
{
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

/**
 * Get a sanitized POST value by key. Returns '' if key is missing.
 */
function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? sanitize((string) $_POST[$key]) : $default;
}

/**
 * Get a sanitized GET value by key.
 */
function get(string $key, string $default = ''): string
{
    return isset($_GET[$key]) ? sanitize((string) $_GET[$key]) : $default;
}

// ── CSRF Protection ───────────────────────────────────────────

/**
 * Generate (or retrieve) the session CSRF token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field. Place inside every <form>.
 */
function csrfField(): void
{
    echo '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8')
        . '">';
}

/**
 * Validate the CSRF token from a POST request.
 * Exits with 403 if invalid.
 */
function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ── Redirects ─────────────────────────────────────────────────

/**
 * Redirect to a URL and exit immediately.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ── Formatting ────────────────────────────────────────────────

/**
 * Format a decimal amount as Sri Lankan Rupees.
 */
function formatLKR(float $amount): string
{
    return 'LKR ' . number_format($amount, 2);
}

/**
 * Truncate a string to a maximum length, appending '…'.
 */
function truncate(string $text, int $length = 80): string
{
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

/**
 * Return a Bootstrap badge HTML for a plant category.
 */
function categoryBadge(string $category): string
{
    $map = [
        'indoor'     => 'primary',
        'outdoor'    => 'success',
        'ornamental' => 'warning',
        'edible'     => 'info',
    ];
    $color = $map[$category] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst(htmlspecialchars($category)) . '</span>';
}

/**
 * Return a Bootstrap badge HTML for an order status.
 */
function orderStatusBadge(string $status): string
{
    $map = [
        'pending'    => 'warning',
        'processing' => 'info',
        'completed'  => 'success',
        'cancelled'  => 'danger',
    ];
    $color = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst(htmlspecialchars($status)) . '</span>';
}

/**
 * Return a Bootstrap badge for query status.
 */
function queryStatusBadge(string $status): string
{
    $map = [
        'open'     => 'danger',
        'answered' => 'success',
        'closed'   => 'secondary',
    ];
    $color = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst(htmlspecialchars($status)) . '</span>';
}

/**
 * Return a Bootstrap badge for user status.
 */
function userStatusBadge(string $status): string
{
    return $status === 'enabled'
        ? '<span class="badge bg-success">Enabled</span>'
        : '<span class="badge bg-danger">Disabled</span>';
}

/**
 * Return the relative path to the plant image or a placeholder.
 */
function plantImageSrc(string $imagePath, string $baseUrl = ''): string
{
    if (!empty($imagePath) && file_exists(__DIR__ . '/../assets/images/plants/' . $imagePath)) {
        return $baseUrl . '/assets/images/plants/' . htmlspecialchars($imagePath);
    }
    return $baseUrl . '/assets/images/plant-placeholder.png';
}

// ── Cart Helpers ──────────────────────────────────────────────

/**
 * Get the number of distinct items in the session cart.
 */
function cartCount(): int
{
    return array_sum($_SESSION['cart'] ?? []);
}

// ── Pagination ────────────────────────────────────────────────

/**
 * Build simple Bootstrap pagination HTML.
 *
 * @param int    $currentPage  1-indexed current page
 * @param int    $totalPages   Total number of pages
 * @param string $baseUrl      URL without page param
 * @param string $param        Query parameter name (default 'page')
 */
function renderPagination(int $currentPage, int $totalPages, string $baseUrl, string $param = 'page'): void
{
    if ($totalPages <= 1) return;

    echo '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

    // Previous
    $prevDisabled = $currentPage <= 1 ? 'disabled' : '';
    $prevUrl      = $currentPage > 1 ? $baseUrl . '&' . $param . '=' . ($currentPage - 1) : '#';
    echo "<li class=\"page-item $prevDisabled\"><a class=\"page-link\" href=\"$prevUrl\">&laquo; Prev</a></li>";

    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $currentPage ? 'active' : '';
        $url    = $baseUrl . '&' . $param . '=' . $i;
        echo "<li class=\"page-item $active\"><a class=\"page-link\" href=\"$url\">$i</a></li>";
    }

    // Next
    $nextDisabled = $currentPage >= $totalPages ? 'disabled' : '';
    $nextUrl      = $currentPage < $totalPages ? $baseUrl . '&' . $param . '=' . ($currentPage + 1) : '#';
    echo "<li class=\"page-item $nextDisabled\"><a class=\"page-link\" href=\"$nextUrl\">Next &raquo;</a></li>";

    echo '</ul></nav>';
}
