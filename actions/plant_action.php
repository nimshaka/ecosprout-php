<?php
/**
 * EcoSprout – Plant CRUD Action Handler
 * Handles: create, update, delete plant records.
 * POST param 'action': 'create' | 'update' | 'delete'
 *
 * ASSUMPTION: Plant images are uploaded to /assets/images/plants/
 * Server validates MIME type and limits file size to 2MB.
 * If no image is uploaded during an update, the existing image is kept.
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBaseUrl() . '/' . getCurrentRole() . '/inventory.php');
}

verifyCsrf();

$action   = $_POST['action'] ?? '';
$pdo      = getPDO();
$referer  = $_SERVER['HTTP_REFERER'] ?? getBaseUrl() . '/' . getCurrentRole() . '/inventory.php';
// Determine return page based on role
$returnUrl = getCurrentRole() === 'admin'
    ? getBaseUrl() . '/admin/plants.php'
    : getBaseUrl() . '/staff/inventory.php';

// ── Image upload helper ───────────────────────────────────────
function handlePlantImageUpload(): ?string
{
    if (empty($_FILES['image']['name'])) return null;

    $file     = $_FILES['image'];
    $maxSize  = 2 * 1024 * 1024; // 2 MB
    $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed (error code: ' . $file['error'] . ').');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('The uploaded image could not be read by the server.');
    }
    if ($file['size'] > $maxSize) {
        throw new RuntimeException('Image must be smaller than 2 MB.');
    }

    // Verify MIME type server-side using finfo
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowed, true)) {
        throw new RuntimeException('Only JPG, PNG, GIF or WebP images are allowed.');
    }

    $ext      = $extMap[$mimeType];
    $filename = 'plant_' . bin2hex(random_bytes(16)) . '.' . $ext;
    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'plants';
    $dest = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    // Ensure directory exists
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('The plant image directory could not be created.');
    }
    if (!is_writable($uploadDir)) {
        throw new RuntimeException('The plant image directory is not writable by PHP.');
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }
    if (!is_file($dest)) {
        throw new RuntimeException('The uploaded image was not saved correctly.');
    }

    return $filename;
}

// ── Validate common plant fields ──────────────────────────────
function validatePlantFields(): array
{
    $errors = [];
    $data   = [];

    $data['plant_name']        = trim($_POST['plant_name'] ?? '');
    $data['botanical_name']    = trim($_POST['botanical_name'] ?? '');
    $data['category']          = trim($_POST['category'] ?? '');
    $data['description']       = trim($_POST['description'] ?? '');
    $data['care_instructions'] = trim($_POST['care_instructions'] ?? '');
    $data['price']             = (float) ($_POST['price'] ?? 0);
    $data['stock_quantity']    = (int)   ($_POST['stock_quantity'] ?? 0);

    $validCategories = ['indoor', 'outdoor', 'ornamental', 'edible'];

    if (empty($data['plant_name']))                         $errors[] = 'Plant name is required.';
    if (mb_strlen($data['plant_name']) > 150)               $errors[] = 'Plant name is too long (max 150 chars).';
    if (!in_array($data['category'], $validCategories))     $errors[] = 'Invalid plant category.';
    if ($data['price'] < 0)                                 $errors[] = 'Price cannot be negative.';
    if ($data['stock_quantity'] < 0)                        $errors[] = 'Stock quantity cannot be negative.';

    return ['errors' => $errors, 'data' => $data];
}

// ════════════════════════════════════════════════════════════════
try {

    // ── CREATE ────────────────────────────────────────────────
    if ($action === 'create') {
        ['errors' => $errors, 'data' => $data] = validatePlantFields();

        if (!empty($errors)) {
            foreach ($errors as $e) setFlash('error', $e);
            redirect($returnUrl);
        }

        $imageFile = handlePlantImageUpload();

        $stmt = $pdo->prepare(
            "INSERT INTO plants (plant_name, botanical_name, category, description, care_instructions, price, stock_quantity, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['plant_name'], $data['botanical_name'], $data['category'],
            $data['description'], $data['care_instructions'],
            $data['price'], $data['stock_quantity'], $imageFile
        ]);

        setFlash('success', 'Plant "' . htmlspecialchars($data['plant_name']) . '" added successfully!');
        redirect($returnUrl);
    }

    // ── UPDATE ────────────────────────────────────────────────
    if ($action === 'update') {
        $plantId = (int) ($_POST['plant_id'] ?? 0);
        if ($plantId <= 0) { setFlash('error', 'Invalid plant ID.'); redirect($returnUrl); }

        ['errors' => $errors, 'data' => $data] = validatePlantFields();
        if (!empty($errors)) {
            foreach ($errors as $e) setFlash('error', $e);
            redirect($returnUrl);
        }

        // Fetch existing image to preserve if no new image is uploaded
        $existing = $pdo->prepare("SELECT image FROM plants WHERE id = ?");
        $existing->execute([$plantId]);
        $existingPlant = $existing->fetch();
        if (!$existingPlant) { setFlash('error', 'Plant not found.'); redirect($returnUrl); }

        $removeImage = ($_POST['remove_image'] ?? '0') === '1';

        if (!empty($_FILES['image']['name'])) {
            // New image uploaded – use it (and delete old one)
            $imageFile = handlePlantImageUpload();
            if (!$removeImage && !empty($existingPlant['image'])) {
                $oldPath = __DIR__ . '/../assets/images/plants/' . $existingPlant['image'];
                if (file_exists($oldPath)) @unlink($oldPath);
            }
        } elseif ($removeImage) {
            // User explicitly removed the image
            if (!empty($existingPlant['image'])) {
                $oldPath = __DIR__ . '/../assets/images/plants/' . $existingPlant['image'];
                if (file_exists($oldPath)) @unlink($oldPath);
            }
            $imageFile = null;
        } else {
            // No change – keep existing image
            $imageFile = $existingPlant['image'];
        }

        $stmt = $pdo->prepare(
            "UPDATE plants SET plant_name=?, botanical_name=?, category=?, description=?,
             care_instructions=?, price=?, stock_quantity=?, image=?
             WHERE id=?"
        );
        $stmt->execute([
            $data['plant_name'], $data['botanical_name'], $data['category'],
            $data['description'], $data['care_instructions'],
            $data['price'], $data['stock_quantity'], $imageFile, $plantId
        ]);

        setFlash('success', 'Plant updated successfully!');
        redirect($returnUrl);
    }


    // ── DELETE ────────────────────────────────────────────────
    if ($action === 'delete') {
        $plantId = (int) ($_POST['plant_id'] ?? 0);
        if ($plantId <= 0) { setFlash('error', 'Invalid plant ID.'); redirect($returnUrl); }

        // Fetch image filename to delete the file too
        $stmt = $pdo->prepare("SELECT image FROM plants WHERE id = ?");
        $stmt->execute([$plantId]);
        $plant = $stmt->fetch();

        if (!$plant) { setFlash('error', 'Plant not found.'); redirect($returnUrl); }

        // Check if referenced in order_items
        $checkOrder = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE plant_id = ?");
        $checkOrder->execute([$plantId]);
        if ((int)$checkOrder->fetchColumn() > 0) {
            setFlash('warning', 'Cannot delete "' . htmlspecialchars($plant['plant_name']) . '" because it is recorded in customer purchase orders. You can set its stock to 0 instead.');
            redirect($returnUrl);
        }

        // Delete the plant record
        $del = $pdo->prepare("DELETE FROM plants WHERE id = ?");
        $del->execute([$plantId]);

        // Remove the image file if it exists
        if (!empty($plant['image'])) {
            $imgPath = __DIR__ . '/../assets/images/plants/' . $plant['image'];
            if (file_exists($imgPath)) @unlink($imgPath);
        }

        setFlash('success', 'Plant deleted successfully.');
        redirect($returnUrl);
    }

    setFlash('error', 'Unknown action.');
    redirect($returnUrl);

} catch (RuntimeException $e) {
    setFlash('error', $e->getMessage());
    redirect($returnUrl);
} catch (PDOException $e) {
    error_log('[EcoSprout] Plant action error: ' . $e->getMessage());
    setFlash('error', 'A database error occurred. Please try again.');
    redirect($returnUrl);
}
