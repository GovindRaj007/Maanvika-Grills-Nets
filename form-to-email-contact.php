<?php
/**
 * Enquiry form handler.
 *
 * Accepts POSTs from the enquiry forms on index.php and contact.php, emails
 * the lead to the office inbox with PHP's built-in mail(), then redirects to
 * thank-you.php.
 *
 * Mail is sent From the domain address so it passes SPF/DMARC on the hosting
 * account, and delivered To the Gmail inbox the business actually monitors.
 * No SMTP password is stored anywhere.
 */

// Never print PHP notices into the response: any output before the redirect
// below would turn header() into a "headers already sent" error and strand
// the visitor on a blank page.
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Where enquiries are delivered.
const ENQUIRY_TO        = 'maanvikasafetynetschennai@gmail.com';
// Must stay on the site's own domain or the mail fails SPF and lands in spam.
const ENQUIRY_FROM      = 'info@maanvikasafetynetschennai.com';
const ENQUIRY_FROM_NAME = 'Maanvika Grills & Nets Website';

function enquiry_fail($message)
{
    header('Location: contact.php?enquiry=error&reason=' . rawurlencode($message));
    exit;
}

/**
 * Strip anything that could break out of a mail header.
 *
 * A newline in a submitted value would let an attacker append their own
 * Bcc/To headers and turn this form into an open relay, so CR, LF and NUL
 * are removed before any value reaches a header line.
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
    header('Location: contact.php');
    exit;
}

// Honeypot: real visitors never see this field, bots fill everything in.
// Pretend it worked so the bot does not retry.
if (trim($_POST['website'] ?? '') !== '') {
    header('Location: thank-you.php');
    exit;
}

$clean = static function ($key) {
    return htmlspecialchars(trim($_POST[$key] ?? ''), ENT_QUOTES, 'UTF-8');
};

$name    = $clean('name');
$phone   = $clean('phone');
$email   = $clean('email');
$service = $clean('services');
$message = $clean('message');
$source  = $clean('source') ?: 'Website';

if ($name === '' || $phone === '') {
    enquiry_fail('Please enter your name and mobile number.');
}

// Indian mobile numbers: 10 digits, optionally with a +91 / 0 prefix.
$digits = preg_replace('/\D+/', '', $phone);
if (!preg_match('/^(?:91|0)?[6-9]\d{9}$/', $digits)) {
    enquiry_fail('Please enter a valid 10-digit mobile number.');
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    enquiry_fail('Please enter a valid email address, or leave it blank.');
}

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
if ($message !== '') { $rows['Message'] = nl2br($message); }
$rows['Submitted'] = date('d M Y, g:i A');

$rowsHtml = '';
foreach ($rows as $label => $value) {
    $rowsHtml .= '<tr>'
        . '<td style="padding:8px 20px;text-align:right;vertical-align:top;width:30%;min-width:90px;color:#777">' . $label . '</td>'
        . '<td style="padding:8px 0;text-align:center;vertical-align:top;width:5%">:</td>'
        . '<td style="padding:8px 20px 8px 5px;text-align:left;vertical-align:top;width:65%;font-weight:600">' . $value . '</td>'
        . '</tr>';
}

$callDigits = preg_replace('/\D+/', '', $phone);

$body = '<!DOCTYPE html>
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
          <a href="tel:' . $callDigits . '" style="background:#007bff;color:#fff;text-decoration:none;padding:10px 22px;border-radius:4px;display:inline-block">Call ' . $phone . '</a>
        </p>
      </td>
    </tr>
  </table>
</body>
</html>';

$subjectName = display_name_safe($name);
$subject     = 'New enquiry from ' . ($subjectName !== '' ? $subjectName : 'website') . ' - ' . header_safe($digits, 20);

// Reply-To points at the visitor when they gave an address, so hitting reply
// in Gmail answers the customer rather than the website. Re-validate here:
// only a genuinely well-formed address is allowed into the header.
$replyTo = ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
    ? $email
    : ENQUIRY_FROM;
$replyToName = $subjectName !== '' ? $subjectName : 'Website Enquiry';

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . ENQUIRY_FROM_NAME . ' <' . ENQUIRY_FROM . '>',
    'Reply-To: ' . $replyToName . ' <' . $replyTo . '>',
    'Return-Path: ' . ENQUIRY_FROM,
    'X-Mailer: PHP/' . phpversion(),
];

// The -f parameter sets the envelope sender, which is what the receiving
// server checks SPF against.
$sent = @mail(
    ENQUIRY_TO,
    $subject,
    $body,
    implode("\r\n", $headers),
    '-f' . ENQUIRY_FROM
);

if (!$sent) {
    // Log for the site owner; never expose mail server details to the visitor.
    error_log('Enquiry mail failed for ' . $digits . ' from ' . $source);
    enquiry_fail('We could not send your enquiry right now. Please call us on 95814 31299.');
}

header('Location: thank-you.php');
exit;
