<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_shared.php';

if (!defined('USERS')) {
    http_response_code(500);
    die('Server under maintenance');
}

if (!defined('BASE_URL'))
    define('BASE_URL', '');

if (
    (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) ||
    !isset(USERS[$_SERVER['PHP_AUTH_USER']]) ||
    !password_verify($_SERVER['PHP_AUTH_PW'], USERS[$_SERVER['PHP_AUTH_USER']])
) {
    header('WWW-Authenticate: Basic');
    http_response_code(401);
    die('Authentication required');
}

if (isset($_POST['submit'])) {

    if (count($_FILES) !== 1) {
        http_response_code(400);
        die('Invalid request');
    }

    $imgType = $_POST['type'] ?? 'webp';
    $imgQuality = $_POST['quality'] ?? null;
    $imgMaxWidth = $_POST['width'] ?? -1;
    $imgMaxHeight = $_POST['height'] ?? -1;
    if (
        ($imgMaxWidth !== -1 && !is_numeric($imgMaxWidth)) ||
        ($imgMaxHeight !== -1 && !is_numeric($imgMaxHeight)) ||
        ($imgQuality !== null && !is_numeric($imgQuality))
    ) {
        http_response_code(400);
        die('Invalid request');
    }

    if ($imgMaxWidth === -1 && $imgMaxHeight !== -1) {
        # This is not supported by default GD.
        http_response_code(400);
        die('Invalid request');
    }

    function findImgCode(string $ext): string
    {
        $imgCode = time() . rand(1000000, 9999999);
        if (file_exists(__DIR__ . '/images/' . $imgCode . '.' . $ext))
            return findImgCode($ext);
        return $imgCode;
    }

    $imgCode = findImgCode($imgType);

    $imgobj = imagecreatefromstring(file_get_contents($_FILES['image']['tmp_name']));
    if ($imgobj === false) {
        http_response_code(400);
        die('Invalid file');
    }

    if ($imgMaxWidth !== -1 /* || $height !== null */) {
        $imgobj = imagescale($imgobj, $imgMaxWidth, $imgMaxHeight);
        if ($imgobj === false) {
            http_response_code(500);
            die('Unknown error');
        }
    }

    switch ($imgType) {
        case 'webp':
            quality_check($imgQuality, 0, 100);
            imagewebp($imgobj, __DIR__ . '/images/' . $imgCode . '.webp', $imgQuality ?? 100);
            break;

        case 'png':
            quality_check($imgQuality, 0, 9);
            imagepng($imgobj, __DIR__ . '/images/' . $imgCode . '.png', $imgQuality ?? 9);
            break;

        case 'jpeg':
            quality_check($imgQuality, 0, 9);
            imagejpeg($imgobj, __DIR__ . '/images/' . $imgCode . '.jpeg', $imgQuality ?? 100);
            break;

        default:
            http_response_code(400);
            die('Invalid request');
    }

    $url = BASE_URL . 'img.php?i=' . urlencode($imgCode);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload image</title>
    <style>
        th,
        td {
            padding: 3px 15px;
        }
    </style>
</head>

<body>
    <?php if (isset($_POST['submit'])) : ?>
        <section>
            <h2>Result</h2>
            <div>
                <a href="img.php?i=<?= urlencode($imgCode) ?>" target="_blank">
                    <img src="img.php?i=<?= urlencode($imgCode) ?>&w=300" alt="Uploaded image">
                </a>
            </div>
            <table>
                <tbody>
                    <tr>
                        <th scope="row">Image URL</th>
                        <td><code><?= htmlentities($url) ?></code></td>
                        <td><a href="javascript:void(0)" title="Copy URL" onclick="navigator.clipboard.writeText(<?= json_encode([$url]) ?>[0])">📋</a></td>
                    </tr>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
    <section>
        <h2>Upload</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <div>
                <label for="form-file">Upload File</label>
                <input type="file" name="image" id="form-file" required>
            </div>
            <div>
                <label for="form-type">Type</label>
                <select name="type" id="form-type">
                    <option value="webp" selected>WebP</option>
                    <option value="png">PNG</option>
                    <option value="jpeg">JPEG</option>
                </select>
            </div>
            <div>
                <label for="form-quality">Quality</label>
                <input type="number" name="quality" id="form-quality" min="0" max="100" value="<?= htmlspecialchars($_POST['quality'] ?? '100') ?>">
            </div>
            <div>
                Max Size:
                <label for="form-width">w</label>
                <input type="number" name="width" id="form-width" min="-1" value="<?= htmlspecialchars($_POST['width'] ?? '-1') ?>">
                &times;
                <label for="form-height">h</label>
                <input type="number" name="height" id="form-height" min="-1" value="<?= htmlspecialchars($_POST['height'] ?? '-1') ?>">
            </div>
            <div>
                <input type="submit" value="Upload" name="submit">
            </div>
        </form>
    </section>
</body>

</html>