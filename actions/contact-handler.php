<?php
/**
 * Enquiry form handler.
 *
 * A standalone script: the form posts here, this validates and sends, then
 * redirects straight back to the page the visitor came from. It never renders
 * HTML, so a failure can never leave somebody looking at a half-drawn page,
 * and the 303 redirect means refreshing the result does not resend anything.
 *
 * Order of operations is deliberate — honeypot, CSRF, cooldown, validation,
 * compose, deliver — so the cheap rejections happen before any work is done.
 */

define('ROOT_DIR', dirname(__DIR__));

require_once ROOT_DIR . '/config/site.php';
require_once ROOT_DIR . '/core/helpers.php';
require_once ROOT_DIR . '/core/csrf.php';

// Optional. The form sends with PHP's mail() and needs nothing else; this file
// only adds an SMTP fallback for a host where mail() cannot deliver. Loading it
// conditionally means the form still works if it was never uploaded.
if (is_file(ROOT_DIR . '/core/mailer.php')) {
    require_once ROOT_DIR . '/core/mailer.php';
}

// Never print notices into the response: any output before the redirect would
// turn header() into a "headers already sent" error and strand the visitor on
// a blank page.
ini_set('display_errors', '0');
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kolkata');

// ---------------------------------------------------------------------------
// Where we send people back to
// ---------------------------------------------------------------------------

/**
 * The page this submission came from.
 *
 * Only a key of FORM_PAGES is honoured. A path arriving in the POST is never
 * used as a redirect target, so this cannot be turned into an open redirect.
 */
function form_page_path()
{
    $requested = basename((string) ($_POST['source'] ?? ''));

    return FORM_PAGES[$requested] ?? FORM_PAGES['contact.php'];
}

/** Redirect back to the form and stop. 303 so a refresh cannot resend. */
function back_to_form($query)
{
    $path = form_page_path();
    $sep  = strpos($path, '?') === false ? '?' : '&';

    header('Location: ' . $path . $sep . $query . FORM_ANCHOR, true, 303);
    exit;
}

/** Flash the messages and the typed values, then bounce back. */
function fail_with($errorCode, $notice, array $errors = [], array $old = [])
{
    flash_set('notice', $notice);
    if ($errors !== []) {
        flash_set('errors', $errors);
    }
    if ($old !== []) {
        flash_set('old', $old);
    }

    back_to_form('error=' . rawurlencode($errorCode));
}

// ---------------------------------------------------------------------------
// 0. Method
// ---------------------------------------------------------------------------

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . FORM_PAGES['contact.php'], true, 303);
    exit;
}

// ---------------------------------------------------------------------------
// 1. Honeypot
//
// Real visitors never see this field. A bot fills in everything it finds, so
// anything here means automation. Answer exactly as we would on success: the
// bot records a win, stops retrying, and learns nothing about the check.
// ---------------------------------------------------------------------------

if (trim((string) ($_POST['website'] ?? '')) !== '') {
    error_log('Enquiry honeypot tripped from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    back_to_form('sent=1');
}

// ---------------------------------------------------------------------------
// 2. CSRF
// ---------------------------------------------------------------------------

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    // The typed values are still worth keeping: an expired session is usually
    // a form left open too long, not an attack, and making that visitor retype
    // everything is how an enquiry gets abandoned.
    fail_with(
        'session',
        'Your session expired. Please check your details and send the form again.',
        [],
        collect_old()
    );
}

// ---------------------------------------------------------------------------
// 3. Cooldown
// ---------------------------------------------------------------------------

session_boot();

$lastSubmit = (int) ($_SESSION['last_submit'] ?? 0);

if ($lastSubmit > 0 && (time() - $lastSubmit) < FORM_COOLDOWN_SECONDS) {
    fail_with(
        'cooldown',
        'Your request was just sent. Please wait a few seconds before sending another.',
        [],
        collect_old()
    );
}

// ---------------------------------------------------------------------------
// 4. Validate
//
// The server is the source of truth. The browser's own required/pattern checks
// are a convenience that a bot, a script or a visitor with JavaScript off will
// never run, so every rule is repeated here.
// ---------------------------------------------------------------------------

$errors = [];

$name = header_safe(str_cap(trim((string) ($_POST['name'] ?? '')), MAX_NAME));
if ($name === '') {
    $errors['name'] = 'Please tell us your name.';
}

$phoneRaw = trim((string) ($_POST['phone'] ?? ''));
$phone    = normalise_phone($phoneRaw);
if ($phoneRaw === '') {
    $errors['phone'] = 'Please enter your mobile number.';
} elseif (!preg_match('/^[0-9]{' . PHONE_DIGITS . '}$/', $phone)) {
    $errors['phone'] = 'Please enter a valid ' . PHONE_DIGITS . '-digit mobile number.';
}

// Email is optional, but a typo in one that was offered is worth catching.
$email = header_safe(str_cap(trim((string) ($_POST['email'] ?? '')), MAX_EMAIL));
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please check the email address, or leave it blank.';
}

// Fixed choice: allowlisted against the config. An unknown value is dropped
// outright — never echoed back into the page, never put in the email. Posting
// something that is not in the list is not a typo a visitor can make with the
// dropdown, so it is treated as "nothing chosen" rather than given its own
// wording.
$serviceKey = (string) ($_POST['services'] ?? '');
if (!array_key_exists($serviceKey, SERVICES)) {
    $serviceKey = '';
    $errors['services'] = 'Please choose the service you need.';
}

$message = str_cap(trim((string) ($_POST['message'] ?? '')), MAX_MESSAGE);

// Optional. Somebody who fills in an enquiry form asking for a call back has
// already asked to be contacted, so a missing tick is not a reason to turn the
// enquiry away. The answer is still recorded either way, so there is a note of
// who gave explicit permission.
$consent = isset($_POST['consent']) && (string) $_POST['consent'] !== '';

/**
 * The values to put back in the form, gathered the same way whatever the
 * failure was. Only allowlisted keys for the fixed-choice fields, so a
 * rejected value can never be reflected back into the page.
 */
function collect_old()
{
    $service = (string) ($_POST['services'] ?? '');

    return [
        'name'     => header_safe(str_cap(trim((string) ($_POST['name'] ?? '')), MAX_NAME)),
        'phone'    => str_cap(trim((string) ($_POST['phone'] ?? '')), 20),
        'email'    => header_safe(str_cap(trim((string) ($_POST['email'] ?? '')), MAX_EMAIL)),
        'services' => array_key_exists($service, SERVICES) ? $service : '',
        'message'  => str_cap(trim((string) ($_POST['message'] ?? '')), MAX_MESSAGE),
        'consent'  => isset($_POST['consent']) ? '1' : '',
    ];
}

if ($errors !== []) {
    fail_with(
        'validation',
        'Please check the highlighted fields and send the form again.',
        $errors,
        collect_old()
    );
}

// ---------------------------------------------------------------------------
// 4b. Record the enquiry before trying to send it
//
// mail() reports success as soon as the local mail program takes the message,
// which says nothing about delivery — that is exactly how enquiries have gone
// missing here. Writing the lead to disk first means a delivery problem costs
// a phone call's delay, never the enquiry itself.
//
// data/ is blocked in .htaccess and carries its own deny rule, so the file is
// not reachable over the web even though it sits inside the site folder.
// ---------------------------------------------------------------------------

$record = [
    'at'      => date('c'),
    'name'    => $name,
    'phone'   => $phone,
    'email'   => $email,
    'service' => SERVICES[$serviceKey] ?? '',
    'message' => $message,
    'consent' => $consent ? 'yes' : 'no',
    'source'  => form_page_path(),
    'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
];

@file_put_contents(
    ROOT_DIR . '/data/enquiries.log',
    json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

// ---------------------------------------------------------------------------
// 5. Compose
// ---------------------------------------------------------------------------

$serviceLabel = SERVICES[$serviceKey];

$sourcePath = BASE_URL . form_page_path();
$submitted  = date('d M Y, g:i A');

$subject = header_safe('New enquiry - ' . $name . ' - ' . $serviceLabel);

$lines = [
    'New enquiry from the ' . SITE_NAME . ' website',
    '',
    'Name     : ' . $name,
    'Phone    : ' . $phone,
];

if ($email !== '') {
    $lines[] = 'Email    : ' . $email;
}

$lines[] = 'Service  : ' . $serviceLabel;

if ($message !== '') {
    $lines[] = '';
    $lines[] = 'Message  :';
    $lines[] = $message;
    $lines[] = '';
}

$lines[] = 'Consent  : ' . ($consent
    ? 'Yes - ticked the box agreeing to be contacted'
    : 'Not ticked (the box is optional; they still asked for a call back)');
$lines[] = 'Sent from: ' . $sourcePath;
$lines[] = 'Received : ' . $submitted . ' (IST)';
$lines[] = '';
$lines[] = 'Call back: +91' . $phone;

$body = implode("\r\n", $lines) . "\r\n";

$headers = [
    'From: ' . mime_header(SITE_NAME) . ' <' . MAIL_FROM . '>',
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
];

// Reply-To only when the visitor actually gave an address. Inventing one would
// mean replies vanishing into a mailbox nobody reads.
if ($email !== '') {
    $headers[] = 'Reply-To: ' . mime_header($name) . ' <' . $email . '>';
}

$encodedSubject = mime_header($subject);

// ---------------------------------------------------------------------------
// 6. Deliver
//
// Keyed on SITE_ENV, which comes from the SAPI, so a stale setting can never
// divert live mail into a file or a test inbox.
//
// Production goes through an authenticated SMTP login when credentials are
// installed. That is deliberate: on this host mail() reports success and then
// delivers nothing, because the message leaves a server that our domain's SPF
// record does not authorise, and Gmail discards it. Logging in to the mailbox
// sends the message as that account, so there is nothing left to fail. mail()
// stays as the fallback for a server where it does work.
// ---------------------------------------------------------------------------

$sent      = false;
$sendError = '';

if (SITE_ENV === 'development') {
    // Never under the web root: this file holds a customer's name and number.
    $dump = sys_get_temp_dir() . '/enquiry-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.txt';

    $sent = (bool) @file_put_contents(
        $dump,
        'To: ' . RECIPIENT_INBOX . "\r\n"
        . 'Subject: ' . $encodedSubject . "\r\n"
        . implode("\r\n", $headers) . "\r\n\r\n"
        . $body
    );

    if (!$sent) {
        $sendError = 'Could not write the development copy to ' . $dump;
    }
} else {
    // PHP's own mail(). This is the delivery method: no password, no library,
    // nothing to configure beyond MAIL_FROM in config/site.php.
    //
    // MAIL_FROM must stay an address on this site's own domain. This host only
    // relays for domains it owns, so a message sent as anything else — the
    // business Gmail address included — is accepted by the local queue and then
    // quietly discarded. config/site.php records the test that established it.
    //
    // The '-f' argument sets the envelope sender, the address the receiving
    // server checks against the sending domain's SPF record. Without it the
    // check runs against whatever default the host uses.
    $before = error_get_last();

    $sent = @mail(RECIPIENT_INBOX, $encodedSubject, $body, implode("\r\n", $headers), '-f' . MAIL_FROM);

    if (!$sent) {
        // A few hosts refuse the extra argument outright. Retry without it
        // rather than lose the enquiry over a configuration detail.
        $sent = @mail(RECIPIENT_INBOX, $encodedSubject, $body, implode("\r\n", $headers));
    }

    if (!$sent) {
        $after     = error_get_last();
        $sendError = ($after !== $before && isset($after['message']))
            ? (string) $after['message']
            : 'mail() returned false';

        error_log('Enquiry mail() failed for ' . $phone . ': ' . $sendError);

        // Optional last resort, used only if someone has installed SMTP
        // credentials. Absent that file this block does nothing at all.
        if (function_exists('smtp_config')) {
            $smtp = smtp_config();

            if (!empty($smtp['enabled']) && !empty($smtp['host']) && !empty($smtp['password'])) {
                // The From: header has to match the account we log in as, or
                // the provider rewrites it and the message looks forged.
                $smtpHeaders    = $headers;
                $smtpHeaders[0] = 'From: ' . mime_header((string) ($smtp['from_name'] ?? SITE_NAME))
                    . ' <' . ($smtp['from'] ?? $smtp['username']) . '>';

                $smtpError = '';
                $sent = smtp_send($smtp, RECIPIENT_INBOX, $encodedSubject, $body, $smtpHeaders, $smtpError);

                if (!$sent) {
                    error_log('Enquiry SMTP fallback also failed: ' . $smtpError);
                }
            }
        }
    }
}

// ---------------------------------------------------------------------------
// 7. Result
// ---------------------------------------------------------------------------

if (!$sent) {
    fail_with(
        'mail',
        'Your enquiry could not be sent just now. Please call us on ' . SITE_PHONE
            . ' and we will take the details over the phone.',
        [],
        collect_old()
    );
}

// Only a real send starts the cooldown, so a failed attempt never locks the
// visitor out of trying again.
if (session_boot()) {
    $_SESSION['last_submit'] = time();
}

back_to_form('sent=1');
