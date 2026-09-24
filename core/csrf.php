<?php
/**
 * CSRF protection for the enquiry form.
 *
 * Without it another site could post to our handler using a visitor's browser.
 * For an enquiry form the prize is small — junk leads — but the protection is
 * three functions, and it also blocks the simplest replay scripts.
 */

if (!defined('ROOT_DIR')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * The token for this session, created on first use.
 *
 * Calling this starts a session, so only the pages that actually render the
 * form should call it.
 */
function csrf_token()
{
    if (!session_boot()) {
        return '';
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/** The hidden field to drop into the form. */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Check a submitted token.
 *
 * hash_equals compares in constant time, so the comparison cannot be used to
 * guess a token one character at a time. Missing on either side is a failure,
 * never a pass.
 */
function csrf_verify($token)
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    // Read the stored token without creating a session: if there is no session
    // there is no token, and the check has already failed.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!session_cookie_present() || !session_boot()) {
            return false;
        }
    }

    $stored = $_SESSION['csrf_token'] ?? '';

    if (!is_string($stored) || $stored === '') {
        return false;
    }

    return hash_equals($stored, $token);
}
