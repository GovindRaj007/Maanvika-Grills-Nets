<?php
/**
 * TEMPORARY deployment check. DELETE THIS FILE once the form is delivering.
 *
 * Open it on the LIVE site:
 *   https://maanvikasafetynetschennai.com/mail-test.php?key=a1d63f46b4c395dd
 *
 * The form sends with PHP's mail(). Whether that reaches the inbox is decided
 * by the hosting account, not by the code, and the usual culprit is a sender
 * address that does not exist as a real mailbox on the domain. Section 3 sends
 * the same message from three different senders so you can see which one this
 * host accepts and which one actually arrives.
 *
 * Opening the page sends nothing; you have to press a button.
 */

const DIAG_KEY = 'a1d63f46b4c395dd';

if (!hash_equals(DIAG_KEY, (string) ($_GET['key'] ?? ''))) {
    http_response_code(404);
    exit('Not found');
}

define('ROOT_DIR', __DIR__);
require_once ROOT_DIR . '/config/site.php';
require_once ROOT_DIR . '/core/helpers.php';

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

date_default_timezone_set('Asia/Kolkata');

$runSend = ($_GET['send'] ?? '') === '1';

/** Print one label/value row. */
function row($label, $value, $status = '')
{
    $class = $status !== '' ? ' class="' . $status . '"' : '';
    echo '<tr><th>' . e($label) . '</th><td' . $class . '>'
       . nl2br(e((string) $value)) . "</td></tr>\n";
}

/** Present / missing, as a row. */
function row_file($label, $path)
{
    $there = is_file($path);
    row($label, $there ? 'present' : 'MISSING — upload it', $there ? 'good' : 'bad');
    return $there;
}

/**
 * Send one test message and report precisely what mail() did.
 *
 * mail() returns true as soon as the local mail program accepts the message,
 * which is not the same as delivery — so "accepted" here means only that the
 * server took it, and the inbox is the real test.
 */
function try_send($label, $fromAddress, $useEnvelope)
{
    $subject = mime_header('[' . $label . '] ' . SITE_NAME . ' ' . date('H:i:s'));

    $headers = [
        'From: ' . mime_header(SITE_NAME) . ' <' . $fromAddress . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    $body = "Test [" . $label . "] from the enquiry form diagnostic.\r\n\r\n"
          . 'Sender (From): ' . $fromAddress . "\r\n"
          . 'Envelope -f  : ' . ($useEnvelope ? $fromAddress : 'not set') . "\r\n"
          . 'Sent         : ' . date('d M Y, g:i A') . " IST\r\n";

    $before = error_get_last();

    $ok = $useEnvelope
        ? @mail(RECIPIENT_INBOX, $subject, $body, implode("\r\n", $headers), '-f' . $fromAddress)
        : @mail(RECIPIENT_INBOX, $subject, $body, implode("\r\n", $headers));

    $after  = error_get_last();
    $reason = ($after !== $before && isset($after['message'])) ? (string) $after['message'] : '';

    return ['ok' => $ok, 'reason' => $reason, 'from' => $fromAddress];
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Form deployment check</title>
<style>
  body { font: 15px/1.5 system-ui, -apple-system, Segoe UI, Arial, sans-serif; margin: 0; padding: 24px; background: #f6f7f9; color: #222; }
  .wrap { max-width: 880px; margin: 0 auto; }
  h1 { font-size: 22px; margin: 0 0 4px; }
  h2 { font-size: 17px; margin: 32px 0 8px; }
  p.lead { color: #666; margin: 0 0 20px; }
  table { border-collapse: collapse; width: 100%; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,.08); }
  th, td { text-align: left; padding: 9px 14px; border-bottom: 1px solid #eee; vertical-align: top; font-size: 14px; }
  th { width: 36%; color: #555; font-weight: 600; background: #fafbfc; }
  td.good { color: #1a7f37; font-weight: 600; }
  td.bad  { color: #b3261e; font-weight: 600; }
  td.warn { color: #9a6700; font-weight: 600; }
  code { background: #eef0f3; padding: 1px 5px; border-radius: 4px; font-size: 13px; }
  .note { background: #fff8e5; border: 1px solid #f0dca4; border-radius: 8px; padding: 14px 16px; margin: 20px 0; font-size: 14px; }
  .note ol { margin: 8px 0 0; padding-left: 20px; }
  .note li { margin-bottom: 6px; }
  .btn { display: inline-block; margin: 14px 8px 0 0; background: #0b62d6; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; }
</style>
</head>
<body>
<div class="wrap">

<h1>Form deployment check</h1>
<p class="lead"><?php echo e(SITE_NAME); ?> &middot; <?php echo e(date('d M Y, g:i A')); ?> IST</p>

<h2>1. Is every file installed?</h2>
<table>
<?php
row_file('config/site.php',             ROOT_DIR . '/config/site.php');
row_file('core/helpers.php',            ROOT_DIR . '/core/helpers.php');
row_file('core/csrf.php',               ROOT_DIR . '/core/csrf.php');
row_file('core/bootstrap.php',          ROOT_DIR . '/core/bootstrap.php');
row_file('actions/contact-handler.php', ROOT_DIR . '/actions/contact-handler.php');
row_file('enquiry-form.php',            ROOT_DIR . '/enquiry-form.php');

// Files that were deleted locally must be deleted on the server too, or an old
// copy stays reachable.
foreach (['form-to-email-contact.php', 'enquiry-state.php', 'thank-you.php'] as $stale) {
    if (is_file(ROOT_DIR . '/' . $stale)) {
        row('Old file still on the server', $stale . ' — delete it', 'warn');
    }
}

// SMTP is entirely optional now; say so rather than flagging it as missing.
row('core/mailer.php (optional)', is_file(ROOT_DIR . '/core/mailer.php')
        ? 'present — only used if mail() fails and SMTP credentials exist'
        : 'not installed — fine, the form does not need it');
?>
</table>

<h2>2. Environment</h2>
<table>
<?php
$disabled     = array_map('trim', explode(',', (string) ini_get('disable_functions')));
$mailDisabled = in_array('mail', $disabled, true) || !function_exists('mail');

row('PHP version', PHP_VERSION, version_compare(PHP_VERSION, '8.0', '>=') ? 'good' : 'warn');
row('SITE_ENV', SITE_ENV, SITE_ENV === 'production' ? 'good' : 'warn');
row('mail() available', $mailDisabled ? 'NO — the host has disabled it' : 'yes',
    $mailDisabled ? 'bad' : 'good');
row('sendmail_path', ini_get('sendmail_path') ?: '(empty — no local mail program)',
    ini_get('sendmail_path') ? 'good' : 'warn');
row('Sessions', session_status() !== PHP_SESSION_DISABLED ? 'available' : 'DISABLED — CSRF cannot work',
    session_status() !== PHP_SESSION_DISABLED ? 'good' : 'bad');
row('mbstring', function_exists('mb_substr') ? 'yes' : 'no (substr fallback in use)',
    function_exists('mb_substr') ? 'good' : 'warn');
row('Enquiries delivered to', RECIPIENT_INBOX);
row('Form sends as (MAIL_FROM)', MAIL_FROM);
row('Server software', $_SERVER['SERVER_SOFTWARE'] ?? 'unknown');
?>
</table>

<div class="note">
<strong>Why the form sends from a Gmail address.</strong>
There is no mailbox on this domain, so the sender has to be one that genuinely exists. The previous
setting, <code>no-reply@<?php echo e(SITE_DOMAIN); ?></code>, was never created in the hosting panel:
the server accepted each enquiry, found it came from an address it did not own, and dropped it &mdash;
with no bounce, because the bounce had nowhere to go either. Sending as
<code><?php echo e(MAIL_FROM); ?></code> works today, but Gmail cannot verify that this web server is
allowed to send as gmail.com, so the message may land in spam. Test it below.
</div>

<h2>3. Which sender does this host actually deliver?</h2>
<?php if (!$runSend): ?>
  <div class="note">
    This sends three messages to <code><?php echo e(RECIPIENT_INBOX); ?></code>, each from a different
    sender. Afterwards check the inbox <strong>and the spam folder</strong> and note which ones arrived:
    <ol>
      <li><strong>A</strong> &mdash; from <code><?php echo e(MAIL_FROM); ?></code> with the envelope sender set.
          This is exactly what the form does now.</li>
      <li><strong>B</strong> &mdash; the same sender without the envelope argument, in case this host
          rejects it.</li>
      <li><strong>C</strong> &mdash; from <code>no-reply@<?php echo e(SITE_DOMAIN); ?></code>, which has no
          mailbox behind it. This is the setting that was failing, kept here only so you can see the
          difference for yourself.</li>
    </ol>
  </div>
  <a class="btn" href="?key=<?php echo urlencode(DIAG_KEY); ?>&amp;send=1">Send the three test emails</a>
<?php else:
    $tests = [
        'A' => try_send('TEST A', MAIL_FROM, true),
        'B' => try_send('TEST B', MAIL_FROM, false),
        'C' => try_send('TEST C', 'no-reply@' . SITE_DOMAIN, true),
    ];
?>
  <table>
<?php foreach ($tests as $label => $t) {
        row('Test ' . $label . ' — from ' . $t['from'],
            ($t['ok'] ? 'accepted by the server' : 'REFUSED')
            . ($t['reason'] !== '' ? "\n" . $t['reason'] : ''),
            $t['ok'] ? 'good' : 'bad');
      } ?>
  </table>
  <div class="note">
    <strong>Now check the inbox and the spam folder, then read the result like this.</strong><br><br>
    &bull; <em>A arrives (inbox or spam)</em> &rarr; the form will deliver. If it was in spam, open it and
      press <strong>Not spam</strong>; that trains the filter for future enquiries.<br>
    &bull; <em>A does not arrive but B does</em> &rarr; this host rejects the <code>-f</code> argument.
      Tell me and I will drop it.<br>
    &bull; <em>C arrives but A does not</em> &rarr; the opposite of what we expect; tell me and I will switch
      the sender back to the domain.<br>
    &bull; <em>All three accepted but none arrive</em> &rarr; the host is not delivering outbound mail at all.
      That is a support ticket, not a code change &mdash; ask them to confirm PHP <code>mail()</code> is
      enabled and not blocked for this account.<br><br>
    <strong>Either way, the permanent fix is ten minutes in cPanel:</strong> create
    <code>no-reply@<?php echo e(SITE_DOMAIN); ?></code> under Email Accounts (free with the hosting, nothing
    needs to log in to it), confirm the domain has an SPF record, then change <code>MAIL_FROM</code> in
    <code>config/site.php</code> to that address. Enquiries then land in the inbox every time instead of
    depending on how Gmail feels about an unverified sender.
  </div>
<?php endif; ?>

<h2>When you are done</h2>
<div class="note">Delete <code>mail-test.php</code> from the server. It is not linked from the site and search
engines are told to ignore it, but it does not belong on a live site permanently.</div>

</div>
</body>
</html>
