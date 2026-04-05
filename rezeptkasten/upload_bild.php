<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Accept JSON body or form-encoded body
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input    = json_decode(file_get_contents('php://input'), true) ?? [];
    $bildData = $input['bildData'] ?? '';
    $slug     = $input['slug']     ?? '';
} else {
    $bildData = $_POST['bildData'] ?? '';
    $slug     = $_POST['slug']     ?? '';
}

if (empty($bildData) || empty($slug)) {
    echo json_encode(['ok' => false, 'error' => 'Missing bildData or slug']);
    exit;
}

// Validate slug: only lowercase alphanumeric and hyphens
if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid slug']);
    exit;
}

// Parse the data URL  e.g. "data:image/png;base64,..."
if (!preg_match('/^data:(image\/(png|jpeg|webp|gif));base64,(.+)$/', $bildData, $m)) {
    echo json_encode(['ok' => false, 'error' => 'Unsupported image format (only png, jpeg, webp, gif)']);
    exit;
}
$mime    = $m[1];
$binData = base64_decode($m[3], true);

if ($binData === false || strlen($binData) < 8) {
    echo json_encode(['ok' => false, 'error' => 'Base64 decode failed']);
    exit;
}

$bilderDir = __DIR__ . '/bilder';
if (!is_dir($bilderDir)) {
    mkdir($bilderDir, 0755, true);
}
$destPath = $bilderDir . '/' . $slug . '.webp';

// Write binary to a temp file (needed for exif_read_data)
$tmpFile = tempnam(sys_get_temp_dir(), 'rezept_img_');
file_put_contents($tmpFile, $binData);

$ok    = false;
$error = '';

// ── GD conversion ────────────────────────────────────────────────────────────
if (!$ok && function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
    $src = @imagecreatefromstring($binData);
    if ($src !== false) {
        // EXIF orientation correction (JPEG only)
        $orientation = 1;
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif        = @exif_read_data($tmpFile);
            $orientation = isset($exif['Orientation']) ? (int)$exif['Orientation'] : 1;
        }
        if ($orientation > 1) {
            switch ($orientation) {
                case 2: imageflip($src, IMG_FLIP_HORIZONTAL); break;
                case 3: $src = imagerotate($src, 180, 0); break;
                case 4: imageflip($src, IMG_FLIP_VERTICAL); break;
                case 5:
                    $src = imagerotate($src, -90, 0);
                    imageflip($src, IMG_FLIP_HORIZONTAL);
                    break;
                case 6: $src = imagerotate($src, -90, 0); break;
                case 7:
                    $src = imagerotate($src, 90, 0);
                    imageflip($src, IMG_FLIP_HORIZONTAL);
                    break;
                case 8: $src = imagerotate($src, 90, 0); break;
            }
        }

        $w   = imagesx($src);
        $h   = imagesy($src);
        $dst = imagecreatetruecolor($w, $h);

        // Fill with white so transparent pixels blend onto white, not black
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        // Composite source onto the white canvas (handles alpha channels)
        imagealphablending($dst, true);
        imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);

        $ok = imagewebp($dst, $destPath, 82);
        if (!$ok) {
            $error = 'GD imagewebp() failed';
        }

        imagedestroy($src);
        imagedestroy($dst);
    } else {
        $error = 'GD imagecreatefromstring() failed';
    }
}

// ── Imagick fallback ──────────────────────────────────────────────────────────
if (!$ok && class_exists('Imagick')) {
    try {
        $imagick = new Imagick();
        $imagick->readImageBlob($binData);
        $imagick->autoOrientImage();

        // Flatten alpha onto white background
        $imagick->setImageBackgroundColor(new ImagickPixel('white'));
        $imagick = $imagick->flattenImages();

        $imagick->setImageFormat('webp');
        $imagick->setImageCompressionQuality(82);
        $imagick->writeImage($destPath);
        $imagick->destroy();

        $ok    = true;
        $error = '';
    } catch (Exception $e) {
        $error = 'Imagick failed: ' . $e->getMessage();
    }
}

@unlink($tmpFile);

if (!$ok) {
    echo json_encode(['ok' => false, 'error' => $error ?: 'WebP conversion failed']);
    exit;
}

// Validate that a real WebP was written
$check = @getimagesize($destPath);
if (!$check || $check['mime'] !== 'image/webp') {
    @unlink($destPath);
    echo json_encode(['ok' => false, 'error' => 'Output validation failed: not a valid WebP']);
    exit;
}

echo json_encode(['ok' => true, 'url' => 'bilder/' . $slug . '.webp']);
