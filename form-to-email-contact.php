<?php
/**
 * Enquiry form handler.
 *
 * Accepts POSTs from the enquiry form on index.php and contact.php, writes the
 * lead to a local log, emails it to the office inbox with PHP's built-in
 * mail(), then redirects to thank-you.php.
 *
 * On a validation failure the visitor goes back to the page they came from,
 * with their answers still in the boxes and one plain-English message at the
 * top of the form.
 */

// Never print PHP notices into the response: any output before the redirects
// below would turn header() into a "headers already sent" error and strand the
// visitor on a blank page.
ini_set('display_errors', '0');
error_reporting(E_ALL);

// The office reads these timestamps; the server may well be on UTC.
date_default_timezone_set('Asia/Kolkata');

// ---------------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------------

// Where enquiries are delivered.
const ENQUIRY_TO = 'maanvikasafetysolutions@gmail.com';

// Who the mail is *from*. This must stay an address on our own domain: mail
// sent by this server claiming to come from a gmail.com address fails Gmail's
// SPF and DMARC checks and lands in spam. The visitor's own address goes in
// Reply-To instead, so hitting reply still answers the customer.
const ENQUIRY_FROM      = 'no-reply@maanvikasafetynetschennai.com';
const ENQUIRY_FROM_NAME = 'Maanvika Grills & Nets Website';

// Every lead is written here before the mail is attempted, so a mail outage
// can never lose an enquiry. Both .htaccess and .gitignore block *.log.
const ENQUIRY_LOG = __DIR__ . '/enquiry-leads.log';

// The only pages a failed submission may be sent back to.
const ENQUIRY_RETURN_PAGES = ['index.php' => '/', 'contact.php' => 'contact.php'];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Strip anything that could break out of a mail header.
 *
 * A newline in a submitted value would let an attacker append their own
 * Bcc/To headers and turn this form into an open relay, so CR, LF and NUL are
 * removed before any value reaches a header line.
 */
function header_safe($value, $maxLength = 200)
{
    $value = str_replace(["\r", "\n", "\0", '%0a', '%0d', '%0A', '%0D'], ' ', (string) $value);
    // mbstring is not enabled on every PHP build, so fall back to substr.
    $value = function_exists('mb_substr')
        ? mb_substr($value, 0, $maxLength)
        : substr($value, 0, $maxLength);
    return trim(preg_replace('/\s+/', ' ', $value));
}

/**
 * Make a value safe to use as the display name in a From/Reply-To header.
 *
 * On top of the newline stripping above, the characters RFC 5322 treats as
 * special in an address ( : ; , @ < > " \ ) are removed, so a submitted name
 * can never turn a well-formed header into a malformed one.
 */
function display_name_safe($value, $maxLength = 60)
{
    $value = header_safe($value, $maxLength);
    return trim(str_replace([':', ';', ',', '@', '<', '>', '"', '\\', '(', ')'], '', $value));
}

/**
 * Encode a header value that may hold non-ASCII text (a Tamil name, a rupee
 * sign). Raw 8-bit bytes in a header arrive as mojibake.
 */
function header_encode($value)
{
    if (preg_match('//u', $value) !== 1) {
        // Not valid UTF-8: drop the high bytes rather than send junk.
        $value = preg_replace('/[\x80-\xFF]/', '', $value);
    }

    return preg_match('/[\x80-\xFF]/', $value)
        ? '=?UTF-8?B?' . base64_encode($value) . '?='
        : $value;
}

/** Escape a value for the HTML part of the mail. */
function enquiry_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Reduce whatever the visitor typed to the ten digits we can actually dial.
 * "+91 95814 31299", "095814-31299" and "9581431299" all mean one number, and
 * a phone's contact list pastes the first of those far more often than the
 * last.
 */
function normalise_phone($value)
{
    $digits = preg_replace('/\D+/', '', (string) $value);
    $length = strlen($digits);

    if ($length === 12 && strpos($digits, '91') === 0) {
        $digits = substr($digits, 2);           // +91 country code
    } elseif ($length === 13 && strpos($digits, '091') === 0) {
        $digits = substr($digits, 3);           // 0091
    } elseif ($length === 11 && $digits[0] === '0') {
        $digits = substr($digits, 1);           // trunk prefix
    }

    return $digits;
}

/**
 * Send the visitor back to the form with their answers intact.
 *
 * $code is a fixed key, never free text from the request: enquiry-state.php
 * looks it up in its own table, so nothing a stranger puts in a link can be
 * echoed onto the page.
 */
function enquiry_fail($code, array $keep = [])
{
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SESSION['enquiry_old'] = $keep;

    $requested = basename((string) ($_POST['return'] ?? ''));
    $page      = ENQUIRY_RETURN_PAGES[$requested] ?? 'contact.php';

    header('Location: ' . $page . '?enquiry=error&code=' . rawurlencode($code) . '#enquiry');
    exit;
}

// ---------------------------------------------------------------------------
// Accept the submission
// ---------------------------------------------------------------------------

// Only the method is checked. Keying off the submit button's name would drop
// an enquiry silently whenever a browser submits the form by Enter key without
// sending that button's value.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: contact.php');
    exit;
}

// Honeypot: real visitors never see this field, bots fill everything in.
// Pretend it worked so the bot does not retry.
if (trim($_POST['website'] ?? '') !== '') {
    error_log('Enquiry honeypot tripped from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    header('Location: thank-you.php');
    exit;
}

// Values are kept as typed and escaped only where they are used, so validation
// sees the real address and the subject line reads "R&B" and not "R&amp;B".
$name    = header_safe(trim($_POST['name'] ?? ''), 100);
$phone   = normalise_phone($_POST['phone'] ?? '');
$email   = header_safe(trim($_POST['email'] ?? ''), 150);
$service = header_safe(trim($_POST['services'] ?? ''), 100);
$message = trim((string) ($_POST['message'] ?? ''));
$source  = header_safe(trim($_POST['source'] ?? ''), 60) ?: 'Website';

$message = function_exists('mb_substr')
    ? mb_substr($message, 0, 2000)
    : substr($message, 0, 2000);

// What goes back into the form if we have to bounce them. The phone is kept as
// typed so the visitor recognises their own entry.
$keep = [
    'name'     => $name,
    'phone'    => trim((string) ($_POST['phone'] ?? '')),
    'email'    => $email,
    'services' => $service,
    'message'  => $message,
];

if ($name === '' || $phone === '') {
    enquiry_fail('required', $keep);
}

// Ten digits is what a phone here dials; normalise_phone() has already dealt
// with +91, 0091 and a leading 0.
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    enquiry_fail('phone', $keep);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    enquiry_fail('email', $keep);
}

// ---------------------------------------------------------------------------
// Keep a copy before anything can fail
// ---------------------------------------------------------------------------

$submittedAt = date('d M Y, g:i A');

@file_put_contents(
    ENQUIRY_LOG,
    json_encode([
        'at'      => date('c'),
        'source'  => $source,
        'name'    => $name,
        'phone'   => $phone,
        'email'   => $email,
        'service' => $service,
        'message' => $message,
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

// ---------------------------------------------------------------------------
// Build the message
// ---------------------------------------------------------------------------

$rows = [
    'Received on' => $source,
    'Name'        => $name,
    'Phone'       => $phone,
];
if ($email !== '')   { $rows['Email']   = $email; }
if ($service !== '') { $rows['Service'] = $service; }
if ($message !== '') { $rows['Message'] = $message; }
$rows['Submitted'] = $submittedAt;

$rowsHtml = '';
foreach ($rows as $label => $value) {
    $rowsHtml .= '<tr>'
        . '<td style="padding:8px 20px;text-align:right;vertical-align:top;width:30%;min-width:90px;color:#777">' . enquiry_e($label) . '</td>'
        . '<td style="padding:8px 0;text-align:center;vertical-align:top;width:5%">:</td>'
        . '<td style="padding:8px 20px 8px 5px;text-align:left;vertical-align:top;width:65%;font-weight:600">' . nl2br(enquiry_e($value)) . '</td>'
        . '</tr>';
}

$html = '<!DOCTYPE html>
<html lang="en-IN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New website enquiry</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7">
  <table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse:collapse;color:#333;font-family:Helvetica,Arial,sans-serif;font-size:14px;line-height:20px;margin:24px auto;width:100%;max-width:600px">
    <tr>
      <td style="background:#ffffff;border-radius:6px;padding:24px">
        <p style="margin:0 0 4px;font-size:20px;font-weight:700;text-align:center">New enquiry from the website</p>
        <p style="margin:0 0 16px;font-size:13px;color:#888;text-align:center">maanvikasafetynetschennai.com</p>
        <div style="height:1px;background:#e4e4e4;margin:0 0 16px"></div>
        <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;width:100%">
          ' . $rowsHtml . '
        </table>
        <div style="height:1px;background:#e4e4e4;margin:16px 0"></div>
        <p style="margin:0;text-align:center">
          <a href="tel:+91' . $phone . '" style="background:#007bff;color:#fff;text-decoration:none;padding:10px 22px;border-radius:4px;display:inline-block">Call ' . $phone . '</a>
        </p>
      </td>
    </tr>
  </table>
</body>
</html>';

// A plain-text part alongside the HTML: HTML-only mail scores worse with spam
// filters, and the text part is what a phone's notification preview shows.
$text = "New enquiry from the website\r\n\r\n";
foreach ($rows as $label => $value) {
    $text .= $label . ': ' . str_replace(["\r\n", "\r", "\n"], ' / ', $value) . "\r\n";
}
$text .= "\r\nCall back: +91" . $phone . "\r\n";

$boundary = 'mvk-' . bin2hex(random_bytes(12));

$body = '--' . $boundary . "\r\n"
      . "Content-Type: text/plain; charset=UTF-8\r\n"
      . "Content-Transfer-Encoding: 8bit\r\n\r\n"
      . $text . "\r\n"
      . '--' . $boundary . "\r\n"
      . "Content-Type: text/html; charset=UTF-8\r\n"
      . "Content-Transfer-Encoding: 8bit\r\n\r\n"
      . $html . "\r\n"
      . '--' . $boundary . "--\r\n";

$subjectName = display_name_safe($name);
$subject     = header_encode('New enquiry from ' . ($subjectName !== '' ? $subjectName : 'website') . ' - ' . $phone);

// Reply-To points at the visitor when they gave an address, so hitting reply
// in Gmail answers the customer rather than the website.
$replyTo     = $email !== '' ? $email : ENQUIRY_TO;
$replyToName = $subjectName !== '' ? $subjectName : 'Website Enquiry';

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    'From: ' . header_encode(ENQUIRY_FROM_NAME) . ' <' . ENQUIRY_FROM . '>',
    'Reply-To: ' . header_encode($replyToName) . ' <' . $replyTo . '>',
    'X-Mailer: PHP/' . phpversion(),
];

// The -f parameter sets the envelope sender, which is what the receiving
// server checks SPF against. A few hosts refuse that parameter outright, so
// retry without it rather than lose the enquiry.
$sent = @mail(ENQUIRY_TO, $subject, $body, implode("\r\n", $headers), '-f' . ENQUIRY_FROM);

if (!$sent) {
    $sent = @mail(ENQUIRY_TO, $subject, $body, implode("\r\n", $headers));
}

if (!$sent) {
    // The lead is already in ENQUIRY_LOG so nothing is lost, but the visitor
    // must not be left believing we have their number.
    error_log('Enquiry mail failed for ' . $phone . ' from ' . $source);
    enquiry_fail('send', $keep);
}

header('Location: thank-you.php');
exit;
