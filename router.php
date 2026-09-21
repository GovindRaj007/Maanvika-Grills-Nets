<?php
/**
 * Local development router for PHP's built-in server. NOT used in production.
 *
 * On the live host, Apache/LiteSpeed reads .htaccess and handles the custom
 * 404 page. The built-in server ignores .htaccess, so without this a missing
 * URL shows PHP's bare "Not Found" instead of the site's 404 page.
 *
 * Run it like this, from the project folder:
 *
 *     php -S localhost:8001 router.php
 *
 * Note the file goes AFTER the address. Passing index.php there instead
 * routes every request -- including /css/custom.css -- through the home page,
 * which is why the site would load with no styling.
 *
 * Plain `php -S localhost:8001` also works; you just lose the 404 page.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . urldecode($path);

// Let the server deliver real files itself, so CSS, JS, images and fonts keep
// their correct Content-Type headers.
if ($path !== '/' && is_file($file)) {
    return false;
}

// Directory requests fall back to index.php, the way DirectoryIndex does.
if ($path === '/' || is_dir($file)) {
    $index = rtrim($file, '/') . '/index.php';
    if (is_file($index)) {
        require $index;
        return true;
    }
}

// Anything else is a genuine 404: serve the styled page, with the right status.
http_response_code(404);
require __DIR__ . '/404.php';
return true;
