<?php
/**
 * EcoSprout – HTML <head> block
 *
 * @param string $pageTitle  The page-specific title (appended to site name)
 */
function renderHead(string $pageTitle = 'EcoSprout'): void
{
    $title = ($pageTitle !== 'EcoSprout') ? $pageTitle . ' | EcoSprout' : 'EcoSprout | Plant Nursery & Gardening Services';
    $base = function_exists('getBaseUrl') ? getBaseUrl() : (defined('BASE_URL') ? BASE_URL : '/EcoSprout');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/assets/favicon.svg">
    <meta name="description" content="EcoSprout | Your premier plant nursery and gardening services in Matara, Sri Lanka. Discover plants, book services, and join workshops.">
    <title><?= htmlspecialchars($title) ?></title>

    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- EcoSprout Custom CSS -->
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
    <?php
}

// If called directly (not as function), just output the head for simple includes.
if (!function_exists('renderHead')) {
    renderHead();
}
