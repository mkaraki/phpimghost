<?php
require_once __DIR__ . '/_shared.php';

if (
    !isset($_GET['i']) ||
    !preg_match('/^[a-zA-Z0-9]+$/', $_GET['i'])
) {
    http_response_code(400);
    die('Invalid request');
}

$format = $_GET['f'] ?? null;
$width = $_GET['w'] ?? null;
$height = $_GET['h'] ?? null;
$quality = $_GET['q'] ?? null;
if (
    ($width !== null && !is_numeric($width)) ||
    ($height !== null && !is_numeric($height)) ||
    ($quality !== null && !is_numeric($quality))
) {
    http_response_code(400);
    die('Invalid request');
}

if ($width === null && $height !== null) {
    # This is not supported by default GD.
    http_response_code(400);
    die('Invalid request');
}

$use_default = $format === null && $width === null && $height === null && $quality === null;

function try_default_output($filepath, $mimetype)
{
    global $use_default;
    if ($use_default) {
        header('Content-Type: ' . $mimetype);
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    }
}

$image = null;
$filepath = __DIR__ . '/images/' . $_GET['i'];

if (file_exists($filepath . '.webp')) {
    $filepath .= '.webp';
    try_default_output($filepath, 'image/webp');
    $image = imagecreatefromwebp($filepath);
} else if (file_exists($filepath . '.png')) {
    $filepath .= '.png';
    try_default_output($filepath, 'image/png');
    $image = imagecreatefrompng($filepath);
} else if (file_exists($filepath . '.jpeg')) {
    $filepath .= '.jpeg';
    try_default_output($filepath, 'image/jpeg');
    $image = imagecreatefromjpeg($filepath);
} else {
    http_response_code(404);
    die('Not found');
}

if ($image === false) {
    http_response_code(500);
    die('Unknown error');
}

if ($width !== null /* || $height !== null */) {
    $image = imagescale($image, $width, $height ?? -1);
    if ($image === false) {
        http_response_code(500);
        die('Unknown error');
    }
}

switch ($format) {
    case 'webp':
    case null:
        quality_check($quality, 0, 100);
        header('Content-Type: image/webp');
        imagewebp($image, null, intval($quality ?? 80));
        break;

    case 'png':
        quality_check($quality, 0, 9);
        header('Content-Type: image/png');
        imagepng($image, null, intval($quality ?? 9));
        break;

    case 'jpeg':
        quality_check($quality, 0, 100);
        header('Content-Type: image/jpeg');
        imagejpeg($image, null, intval($quality ?? 80));
        break;

    default:
        http_response_code(400);
        die('Invalid request');
}

if ($image !== null)
    imagedestroy($image);
