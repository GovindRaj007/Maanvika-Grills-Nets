<?php
/**
 * TEMPORARY mail diagnostic. DELETE THIS FILE once the form is delivering.
 *
 * Open it on the LIVE site:
 *   https://maanvikasafetynetschennai.com/mail-test.php?key=a1d63f46b4c395dd
 *
 * It reports what this server can actually do, and only sends test mail when
 * you add &send=1 to the address, so simply opening the page is harmless.
 *
 * The key stops strangers from using this page to probe the server or fire
 * mail from it.
 */

const DIAG_KEY = 'a1d63f46b4c395dd';
const DIAG_TO  = 'maanvikasafetysolutions@gmail.com';

if (!hash_equals(DIAG_KEY, (string) ($_GET['key'] ?? ''))) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/mailer.php';

$send   = ($_GET['send'] ?? '') === '1';
$smtp   = ($_GET['smtp'] ?? '') === '1';
$domain = $_SERVER['HTTP_HOST'] ?? 'maanvikasafetynetschennai.com';
$domain = preg_replace('/^www\./', '', strtolower($domain));

/** Print one label/value row. */
function row($label, $value, $status = '')
{
    $class = $status !== '' ? ' class="' . $status . '"' : '';
    echo '<tr><th>' . htmlspecialchars($label) . '</th><td' . $class . '>'
       . nl2br(htmlspecialchars((string) $value)) . "</td></tr>\n";
}

/** Run one mail() attempt and describe exactly what happened. */
function try_send($title, $to, $subject, $body, array $headers, $envelope = null)
{
    // error_get_last() is how we recover the reason mail() refused: PHP raises
    // a warning there rather than returning anything useful.
    $before = error_get_last();

    $ok = $envelope === null
        ? @mail($to, $subject, $body, implode("\r\n", $headers))
        : @mail($to, $subject, $body, implode("\r\n", $headers), '-f' . $envelope);

    $after  = error_get_last();
    $reason = ($after !== $before && isset($after['message'])) ? $after['message'] : '';

    return [
        'title'    => $title,
        'ok'       => $ok,
        'reason'   => $reason,
        'headers'  => $headers,
        'envelope' => $envelope,
    ];
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Mail diagnostic</title>
<style>
  body { font: 15px/1.5 system-ui, -apple-system, Segoe UI, Arial, sans-serif; margin: 0; padding: 24px; background: #f6f7f9; color: #222; }
  .wrap { max-width: 860px; margin: 0 auto; }
  h1 { font-size: 22px; margin: 0 0 4px; }
  h2 { font-size: 17px; margin: 32px 0 8px; }
  p.lead { color: #666; margin: 0 0 20px; }
  table { border-collapse: collapse; width: 100%; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,.08); }
  th, td { text-align: left; padding: 9px 14px; border-bottom: 1px solid #eee; vertical-align: top; font-size: 14px; }
  th { width: 34%; color: #555; font-weight: 600; background: #fafbfc; }
  td.good { color: #1a7f37; font-weight: 600; }
  td.bad  { color: #b3261e; font-weight: 600; }
  td.warn { color: #9a6700; font-weight: 600; }
  code { background: #eef0f3; padding: 1px 5px; border-radius: 4px; font-size: 13px; }
  .note { background: #fff8e5; border: 1px solid #f0dca4; border-radius: 8px; padding: 14px 16px; margin: 20px 0; font-size: 14px; }
  .btn { display: inline-block; margin-top: 14px; background: #0b62d6; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; }
</style>
</head>
<body>
<div class="wrap">

<h1>Mail diagnostic</h1>
<p class="lead">Run on <?php echo htmlspecialchars($domain); ?> at <?php echo date('d M Y, g:i A'); ?> (IST)</p>

<h2>1. Can this server send mail at all?</h2>
<table>
<?php
$disabled     = array_map('trim', explode(',', (string) ini_get('disable_functions')));
$mailDisabled = in_array('mail', $disabled, true) || !function_exists('mail');

row('PHP version', PHP_VERSION, version_compare(PHP_VERSION, '7.0', '>=') ? 'good' : 'bad');
row('mail() available', $mailDisabled ? 'NO - the host has disabled it' : 'yes', $mailDisabled ? 'bad' : 'good');
row('sendmail_path', ini_get('sendmail_path') ?: '(empty - this server has no local mail program)',
    ini_get('sendmail_path') ? '' : 'warn');
row('SMTP / smtp_port (Windows only)', (ini_get('SMTP') ?: '-') . ' : ' . (ini_get('smtp_port') ?: '-'));
row('Server software', $_SERVER['SERVER_SOFTWARE'] ?? 'unknown');
row('Document root', $_SERVER['DOCUMENT_ROOT'] ?? 'unknown');
?>
</table>

<h2>2. Are the current form files actually on this server?</h2>
<table>
<?php
$handler = __DIR__ . '/form-to-email-contact.php';
$state   = __DIR__ . '/enquiry-state.php';
$log     = __DIR__ . '/enquiry-leads.log';

$handlerSrc = is_file($handler) ? (string) file_get_contents($handler) : '';
$isNew      = strpos($handlerSrc, 'ENQUIRY_LOG') !== false;

row('form-to-email-contact.php', is_file($handler)
        ? 'present, updated ' . date('d M Y, g:i A', filemtime($handler))
        : 'MISSING', is_file($handler) ? 'good' : 'bad');
row('Which version is live', $isNew
        ? 'the updated handler (writes a lead log, normalises phone numbers)'
        : 'the OLD handler - the new files were not uploaded', $isNew ? 'good' : 'bad');
row('enquiry-state.php', is_file($state) ? 'present' : 'MISSING - upload it', is_file($state) ? 'good' : 'bad');

if ($isNew) {
    // Read the addresses the live handler is really using, rather than assuming.
    preg_match("/ENQUIRY_FROM\s*=\s*'([^']*)'/", $handlerSrc, $m);
    $liveFrom = $m[1] ?? '(not found)';
    preg_match("/ENQUIRY_TO\s*=\s*'([^']*)'/", $handlerSrc, $m2);
    row('Delivering to', $m2[1] ?? '(not found)');

    // With SMTP switched on the config decides the sender, so report the one
    // that will really be used rather than the constant in the handler.
    $cfgPeek = smtp_config();
    $smtpOn  = !empty($cfgPeek['enabled']) && !empty($cfgPeek['host']) && !empty($cfgPeek['password'])
               && stripos((string) $cfgPeek['password'], 'PASTE') === false;

    row('How it sends', $smtpOn
            ? 'SMTP login (reliable)'
            : 'PHP mail() — this is what was silently failing', $smtpOn ? 'good' : 'warn');
    row('Sending as (From)', $smtpOn
            ? (string) ($cfgPeek['from'] ?? $cfgPeek['username']) . ' (from mail-config.php)'
            : $liveFrom);
}

row('Folder writable for the lead log', is_writable(__DIR__) ? 'yes' : 'NO - leads cannot be logged',
    is_writable(__DIR__) ? 'good' : 'warn');
?>
</table>

<h2>3. Did your test submission reach the handler?</h2>
<?php if (!$isNew): ?>
  <div class="note">The old handler is still live, so there is no lead log to check. Upload the updated files first.</div>
<?php elseif (!is_file($log)): ?>
  <div class="note"><strong>No lead log exists yet.</strong> The updated handler writes every submission here
  <em>before</em> it tries to email. If you have submitted the form since uploading these files and this file is
  still missing, the form never reached the handler at all &mdash; that is a different problem from mail delivery,
  and the redirect or file permissions are the place to look.</div>
<?php else:
    $lines = array_slice(array_filter(explode("\n", (string) file_get_contents($log))), -10);
?>
  <div class="note"><strong>The handler received <?php echo count($lines); ?> recent submission(s).</strong>
  If your test is listed here, the form itself is working perfectly and the problem is purely mail delivery.</div>
  <table>
<?php   foreach ($lines as $line) {
            $lead = json_decode($line, true);
            if (!is_array($lead)) { continue; }
            row(date('d M, g:i A', strtotime($lead['at'] ?? 'now')),
                ($lead['name'] ?? '?') . ' - ' . ($lead['phone'] ?? '?')
                . (($lead['email'] ?? '') !== '' ? ' - ' . $lead['email'] : '')
                . "\n" . 'from: ' . ($lead['source'] ?? '?'));
        } ?>
  </table>
<?php endif; ?>

<h2>4. SMTP login (the fix)</h2>
<?php
$cfg       = smtp_config();
$haveCfg   = !empty($cfg);
$cfgOn     = !empty($cfg['enabled']);
$cfgPass   = (string) ($cfg['password'] ?? '');
$passLooks = $cfgPass !== '' && stripos($cfgPass, 'PASTE') === false;
?>
<table>
<?php
$sampleFile = __DIR__ . '/mail-config.sample.php';
$sampleSrc  = is_file($sampleFile) ? (string) file_get_contents($sampleFile) : '';
// The sample file mentions 'password' more than once — the commented Option B
// example appears before the real setting — so every occurrence is checked and
// anything that is not a known placeholder counts as a real password.
$sampleHasPw = false;
if ($sampleSrc !== '' && preg_match_all("/'password'\s*=>\s*'([^']*)'/", $sampleSrc, $pm)) {
    foreach ($pm[1] as $candidate) {
        $candidate = trim($candidate);
        if ($candidate === ''
            || stripos($candidate, 'PASTE') !== false
            || stripos($candidate, 'mailbox password') !== false) {
            continue;
        }
        $sampleHasPw = true;
        break;
    }
}

row('mail-config.php', $haveCfg ? 'present' : 'MISSING — this is why the form still used mail()',
    $haveCfg ? 'good' : 'bad');

if (!empty($cfg['template_in_use'])) {
    row('Problem', 'mail-config.php is a straight copy of the template and still carries '
        . "'is_template' => true. Delete that line from mail-config.php.", 'bad');
}

if ($sampleHasPw) {
    row('SECURITY', 'A real password is sitting in mail-config.sample.php. That file is committed '
        . 'to git and pushed to GitHub, so the password is exposed. Revoke it in the Google account '
        . 'immediately, generate a new one, and put the new one in mail-config.php only.', 'bad');
}
if ($haveCfg) {
    row('Enabled', $cfgOn ? 'yes' : "no ('enabled' => false, so the form still uses mail())",
        $cfgOn ? 'good' : 'warn');
    row('Mail server', ($cfg['host'] ?? '?') . ':' . ($cfg['port'] ?? '?')
        . ' (' . ($cfg['security'] ?? '?') . ')');
    row('Logging in as', $cfg['username'] ?? '(blank)');
    row('Password filled in', $passLooks
            ? 'yes (' . strlen(str_replace(' ', '', $cfgPass)) . ' characters)'
            : 'NO — the placeholder is still there', $passLooks ? 'good' : 'bad');
    row('OpenSSL available', extension_loaded('openssl') ? 'yes' : 'NO — encrypted SMTP will not work',
        extension_loaded('openssl') ? 'good' : 'bad');
}
?>
</table>

<?php if ($haveCfg && $passLooks): ?>
  <?php if (!$smtp): ?>
    <a class="btn" href="?key=<?php echo urlencode(DIAG_KEY); ?>&amp;smtp=1">Send a test email over SMTP</a>
  <?php else:
      $err  = '';
      $body = "This is the SMTP test from the Maanvika enquiry form.\r\n\r\n"
            . "If you are reading this in the inbox, the form is fixed.\r\n"
            . 'Sent ' . date('d M Y, g:i A') . " IST\r\n";
      $hdrs = [
          'MIME-Version: 1.0',
          'Content-Type: text/plain; charset=UTF-8',
          'From: ' . ($cfg['from_name'] ?? 'Maanvika Website') . ' <' . ($cfg['from'] ?? $cfg['username']) . '>',
      ];
      $ok = smtp_send($cfg, DIAG_TO, '[SMTP TEST] Maanvika enquiry form ' . date('H:i:s'), $body, $hdrs, $err);
  ?>
    <table>
      <?php row('SMTP send', $ok ? 'DELIVERED — check the inbox now' : 'FAILED', $ok ? 'good' : 'bad'); ?>
      <?php if (!$ok) { row('Reason', $err, 'bad'); } ?>
    </table>
    <?php if ($ok): ?>
      <div class="note"><strong>That message was handed directly to the mail server and accepted.</strong>
      Unlike mail(), this is a real confirmation: the server took responsibility for it. Submit the form once
      more to confirm the whole path, then delete this file.</div>
    <?php endif; ?>
  <?php endif; ?>
<?php elseif ($haveCfg): ?>
  <div class="note">Fill in the App Password in <code>mail-config.php</code>, then reload this page.</div>
<?php endif; ?>

<h2>5. Old mail() behaviour, for comparison</h2>
<?php if (!$send): ?>
  <div class="note">Nothing has been sent yet. The button below sends three test emails to
  <code><?php echo htmlspecialchars(DIAG_TO); ?></code>, each using a different sender setup, so we can see
  which one this host accepts. Then check the inbox <strong>and the spam folder</strong>.</div>
  <a class="btn" href="?key=<?php echo urlencode(DIAG_KEY); ?>&amp;send=1">Send the three test emails</a>
<?php else:
    $stamp = date('H:i:s');
    $tests = [];

    // A: what the updated handler does now - sender on our own domain.
    $tests[] = try_send(
        'A - From no-reply@' . $domain . ', envelope set (current form setting)',
        DIAG_TO,
        '[TEST A] Maanvika form diagnostic ' . $stamp,
        "Test A: sender on the site's own domain, envelope sender set with -f.\r\n",
        [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: Maanvika Website <no-reply@' . $domain . '>',
            'Reply-To: no-reply@' . $domain,
        ],
        'no-reply@' . $domain
    );

    // B: what the form did before - sender claiming to be the gmail address.
    $tests[] = try_send(
        'B - From ' . DIAG_TO . ' (the old setting)',
        DIAG_TO,
        '[TEST B] Maanvika form diagnostic ' . $stamp,
        "Test B: sender claiming to be the gmail address.\r\n",
        [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: Maanvika Website <' . DIAG_TO . '>',
        ],
        DIAG_TO
    );

    // C: the barest possible call - isolates mail() itself from our headers.
    $tests[] = try_send(
        'C - bare mail(), no custom headers',
        DIAG_TO,
        '[TEST C] Maanvika form diagnostic ' . $stamp,
        "Test C: plainest possible mail() call.\r\n",
        []
    );
?>
  <table>
<?php   foreach ($tests as $t) {
            row($t['title'],
                ($t['ok'] ? 'ACCEPTED by the server' : 'REFUSED')
                . ($t['reason'] !== '' ? "\n" . $t['reason'] : ''),
                $t['ok'] ? 'good' : 'bad');
        } ?>
  </table>
  <div class="note">
    <strong>Reading the result.</strong><br>
    &bull; <em>All three refused</em> &rarr; this host will not send mail from PHP at all. The form must send
    through an SMTP login instead.<br>
    &bull; <em>Accepted, but nothing arrives (check spam too)</em> &rarr; the host sends, but Gmail is discarding it.
    An SMTP login fixes this permanently; an SPF record is the lighter fix.<br>
    &bull; <em>B arrives but A does not</em> &rarr; tell me, and I will point the form back at the old sender.<br>
    &bull; <em>A arrives</em> &rarr; mail works; the problem is elsewhere in the form and section 3 above will show it.
  </div>
<?php endif; ?>

<h2>When you are done</h2>
<div class="note">Delete <code>mail-test.php</code> from the server once the form is delivering. It is not linked
from the site and search engines are told to ignore it, but it does not belong on a live site permanently.</div>

</div>
</body>
</html>
