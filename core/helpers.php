<?php
/**
 * Shared helpers: session handling, flash messages, escaping and the header
 * sanitiser the mail code depends on.
 */

if (!defined('ROOT_DIR')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Start the session with cookie settings decided here rather than inherited
 * from whatever the host's php.ini happens to say.
 *
 * Called only when there is something to store or read, so a visitor who
 * never submits the form is never given a cookie.
 */
function session_boot()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }

    if (headers_sent()) {
        // Too late to send a cookie; better to carry on without a session than
        // to spray warnings across a page the visitor is reading.
        return false;
    }

    // Shared hosting terminates TLS at a proxy, so HTTPS is often unset even
    // though the visitor is on https. Trusting only $_SERVER['HTTPS'] would
    // leave the cookie without the Secure flag on every live request.
    $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // A name of our own, so the session cannot be confused with another
    // application's on the same host.
    session_name('MVKSESS');

    return @session_start();
}

/** True when the visitor already carries our session cookie. */
function session_cookie_present()
{
    return isset($_COOKIE[session_name() !== '' ? session_name() : 'MVKSESS'])
        || isset($_COOKIE['MVKSESS']);
}

/** Store a one-shot value for the next request. */
function flash_set($key, $value)
{
    if (!session_boot()) {
        return;
    }
    $_SESSION['__flash'][$key] = $value;
}

/**
 * Read a one-shot value and remove it.
 *
 * Returns the default without touching the session when no session cookie was
 * sent, which is what keeps ordinary visitors cookie-free.
 */
function flash_get($key, $default = null)
{
    if (!session_cookie_present()) {
        return $default;
    }

    if (!session_boot()) {
        return $default;
    }

    if (!isset($_SESSION['__flash'][$key])) {
        return $default;
    }

    $value = $_SESSION['__flash'][$key];
    unset($_SESSION['__flash'][$key]);

    if (empty($_SESSION['__flash'])) {
        unset($_SESSION['__flash']);
    }

    return $value;
}

/**
 * Strip anything that could break out of a mail header.
 *
 * Values such as the subject and the reply-to address end up in real mail
 * headers once the delivery service builds the notification, and a line break
 * in a header is how a message acquires extra recipients. CR, LF and NUL become
 * spaces before any value can get that far.
 */
function header_safe($value)
{
    return trim(str_replace(["\r", "\n", "\0"], ' ', (string) $value));
}

/**
 * Cut a string to a maximum length.
 *
 * mbstring is not installed on every shared host, so fall back to substr and
 * accept that a multi-byte character may be split in that case — a truncated
 * character is better than a fatal error.
 */
function str_cap($value, $limit)
{
    $value = (string) $value;

    return function_exists('mb_substr')
        ? mb_substr($value, 0, $limit)
        : substr($value, 0, $limit);
}

/** Escape for HTML output. */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Reduce a typed phone number to the digits we can dial.
 *
 * "+91 95814 31299", "095814-31299" and "9581431299" all mean one number, and
 * a phone's contact list pastes the first of those far more often than the
 * last.
 */
function normalise_phone($value)
{
    $digits = preg_replace('/\D+/', '', (string) $value);
    $length = strlen($digits);

    if ($length === PHONE_DIGITS + 2 && strpos($digits, '91') === 0) {
        $digits = substr($digits, 2);           // +91
    } elseif ($length === PHONE_DIGITS + 3 && strpos($digits, '091') === 0) {
        $digits = substr($digits, 3);           // 0091
    } elseif ($length === PHONE_DIGITS + 1 && $digits[0] === '0') {
        $digits = substr($digits, 1);           // trunk zero
    }

    return $digits;
}
