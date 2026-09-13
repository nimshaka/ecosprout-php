<?php
/**
 * EcoSprout – HTML closing tags & JS bundles.
 * Include at the very end of every page.
 */
$base = function_exists('getBaseUrl') ? getBaseUrl() : (defined('BASE_URL') ? BASE_URL : '/EcoSprout');
?>
    <!-- Bootstrap 5.3 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- EcoSprout Custom JS -->
    <script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
