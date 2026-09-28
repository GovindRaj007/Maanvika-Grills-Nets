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

require_once ROOT_DIR . '/core/web3forms.php';

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
// Delivery goes over the network to another service, so it can fail for reasons
// that have nothing to do with this site: an outage, a DNS hiccup, a blocked
// outbound connection. Writing the lead to disk first means any of those costs
// a delay in noticing, never the enquiry itself.
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

// $logged decides what the visitor is told if the email fails: with the lead
// safely on disk there is nothing to apologise for, because the business can
// still call them back.
$logged = (bool) @file_put_contents(
    ROOT_DIR . '/data/enquiries.log',
    json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

// ---------------------------------------------------------------------------
// 5. Compose
// ---------------------------------------------------------------------------

$serviceLabel = SERVICES[$serviceKey];

$submitted = date('d M Y, g:i A');

// The fields Web3Forms shows in the email. Keys are written the way they should
// read in the inbox, because anything that is not one of their reserved names
// becomes a labelled line in the message.
//
// header_safe() still strips CR, LF and NUL from every value. JSON would escape
// them safely, but the subject and reply-to end up in real mail headers at the
// far end, and a line break there is how a message gets extra recipients.
$fields = [
    'access_key' => WEB3FORMS_ACCESS_KEY,
    'subject'    => header_safe('New enquiry - ' . $name . ' - ' . $serviceLabel),
    'from_name'  => SITE_NAME . ' Website',

    'Name'      => $name,
    'Phone'     => '+91' . $phone,
    'Service'   => $serviceLabel,
    'Consent'   => $consent
        ? 'Yes - ticked the box agreeing to be contacted'
        : 'Not ticked (the box is optional; they still asked for a call back)',
    'Sent from' => BASE_URL . form_page_path(),
    'Received'  => $submitted . ' (IST)',
];

// Only include what the visitor actually gave, so the email has no blank rows.
if ($email !== '') {
    $fields['Email'] = $email;
    // Reply-To at the far end, so answering the notification answers the
    // customer. Never invented: without an address there is nothing to reply to.
    $fields['replyto'] = header_safe($email);
}

if ($message !== '') {
    $fields['Message'] = $message;
}

// ---------------------------------------------------------------------------
// 6. Deliver
//
// Keyed on SITE_ENV, which comes from the SAPI, so a stale setting can never
// send test enquiries to the live inbox or divert live ones into a file.
// ---------------------------------------------------------------------------

$sent      = false;
$sendError = '';

if (SITE_ENV === 'development') {
    // Written outside the web root: it holds a customer's name and number.
    $dump = sys_get_temp_dir() . '/enquiry-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.txt';

    $readable = '';
    foreach ($fields as $label => $value) {
        $readable .= str_pad($label, 12) . ': ' . $value . PHP_EOL;
    }

    $sent = (bool) @file_put_contents($dump, $readable);

    if (!$sent) {
        $sendError = 'Could not write the development copy to ' . $dump;
    }
} else {
    $sent = web3forms_send($fields, $sendError);
}
// ---------------------------------------------------------------------------
// 7. Result
// ---------------------------------------------------------------------------

if (!$sent) {
    // Write the reason somewhere the site owner can actually read it. error_log
    // goes wherever the host decides, which on shared hosting is often nowhere
    // findable; this file is what form-check.php displays.
    //
    // PHP wraps its own error text in HTML when html_errors is on, which turns
    // any quotes in the message into entities. Strip that back out so the reason
    // reads properly whatever the host's settings are.
    $reason = $sendError !== '' ? $sendError : 'the send failed without giving a reason';
    $reason = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($reason, ENT_QUOTES, 'UTF-8'))));

    @file_put_contents(
        ROOT_DIR . '/data/send-errors.log',
        json_encode([
            'at'     => date('c'),
            'name'   => $name,
            'phone'  => $phone,
            'reason' => $reason,
            'saved'  => $logged ? 'yes' : 'NO',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );

    error_log('Enquiry delivery failed for ' . $phone . ': ' . $reason);
}

// Two different failures, told apart deliberately.
//
// If the email did not go out but the enquiry is saved on the server, the
// visitor has not lost anything: their name and number are on file and the
// business can ring them. Telling them "we could not send it, please phone us"
// in that situation is both untrue and a good way to lose the job, so they get
// the ordinary thank-you and the owner sees the unsent lead in form-check.php.
//
// Only when the enquiry could not even be recorded is there really nothing to
// act on, and that is the one case worth asking somebody to pick up the phone.
if (!$sent && !$logged) {
    fail_with(
        'mail',
        'Your enquiry could not be sent just now. Please call us on ' . SITE_PHONE
            . ' and we will take the details over the phone.',
        [],
        collect_old()
    );
}

// Only a completed submission starts the cooldown, so a failure never locks the
// visitor out of trying again.
if (session_boot()) {
    $_SESSION['last_submit'] = time();
}

back_to_form('sent=1');
