<?php
/**
 * Entry point bootstrap.
 *
 * Any page that shows the enquiry form includes this as its very first line,
 * before a single byte of HTML:
 *
 *     <?php require __DIR__ . '/core/bootstrap.php'; ?><!DOCTYPE html>
 *
 * It must run before output because reading the form's result may need to set
 * a session cookie, and a cookie cannot follow the page it belongs to.
 */

// This file defines ROOT_DIR, so it cannot use the guard the other internal
// files use. Instead it refuses when it is the script being requested rather
// than one being included by a page.
if (isset($_SERVER['SCRIPT_FILENAME'])
    && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

require_once ROOT_DIR . '/config/site.php';
require_once ROOT_DIR . '/core/helpers.php';
require_once ROOT_DIR . '/core/csrf.php';

date_default_timezone_set('Asia/Kolkata');

/**
 * Create the CSRF token now, while headers can still be sent.
 *
 * enquiry-form.php renders in the middle of the page, long after the first
 * byte has gone out, and a session cookie cannot be sent at that point. Only
 * the pages that carry the form include this bootstrap, so this does not put a
 * cookie on the rest of the site.
 */
csrf_token();

/**
 * Read the result of a submission out of the query string and the flash data.
 *
 * Returns everything enquiry-form.php needs:
 *   sent    bool    show the thank-you panel instead of the form
 *   error   string  '' | mail | validation | session | cooldown
 *   notice  string  a sentence to show above the form
 *   errors  array   field name => message
 *   old     array   field name => previously submitted value
 */
function enquiry_result()
{
    $sent  = ($_GET['sent'] ?? '') === '1';
    $error = (string) ($_GET['error'] ?? '');

    $allowed = ['mail', 'validation', 'session', 'cooldown'];
    if (!in_array($error, $allowed, true)) {
        $error = '';
    }

    $result = [
        'sent'   => $sent,
        'error'  => $error,
        'notice' => '',
        'errors' => [],
        'old'    => [],
    ];

    if ($sent || $error === '') {
        return $result;
    }

    // Only now is the session touched, and only for a visitor who has just
    // submitted something.
    $flashedErrors = flash_get('errors', []);
    $flashedOld    = flash_get('old', []);
    $flashedNotice = flash_get('notice', '');

    $result['errors'] = is_array($flashedErrors) ? $flashedErrors : [];
    $result['old']    = is_array($flashedOld) ? $flashedOld : [];

    // The handler flashes the wording; these are the fallbacks for a visitor
    // who arrives on the URL directly, or after the session has expired.
    $defaults = [
        'mail'       => 'Your enquiry could not be sent just now. Please call us on '
                        . SITE_PHONE . ' and we will take the details over the phone.',
        'validation' => 'Please check the highlighted fields and send the form again.',
        'session'    => 'Your session expired. Please check your details and send the form again.',
        'cooldown'   => 'Your request was just sent. Please wait a few seconds before sending another.',
    ];

    $result['notice'] = is_string($flashedNotice) && $flashedNotice !== ''
        ? $flashedNotice
        : ($defaults[$error] ?? '');

    return $result;
}
