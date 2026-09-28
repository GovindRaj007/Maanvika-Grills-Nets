<?php
/**
 * TEMPORARY deployment check. DELETE THIS FILE once the form is delivering.
 *
 * Open it on the LIVE site:
 *   https://maanvikasafetynetschennai.com/form-check.php?key=a1d63f46b4c395dd
 *
 * It confirms the files are installed, shows every enquiry the server has
 * recorded, reports why any delivery failed, and can send one test submission
 * through Web3Forms. Opening the page sends nothing.
 */

const CHECK_KEY = 'a1d63f46b4c395dd';

if (!hash_equals(CHECK_KEY, (string) ($_GET['key'] ?? ''))) {
    http_response_code(404);
    exit('Not found');
}

define('ROOT_DIR', __DIR__);
require_once ROOT_DIR . '/config/site.php';
require_once ROOT_DIR . '/core/helpers.php';
require_once ROOT_DIR . '/core/web3forms.php';

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

date_default_timezone_set('Asia/Kolkata');

$runTest = ($_GET['send'] ?? '') === '1';

/** Print one label/value row. */
function row($label, $value, $status = '')
{
    $class = $status !== '' ? ' class="' . $status . '"' : '';
    echo '<tr><th>' . e($label) . '</th><td' . $class . '>'
       . nl2br(e((string) $value)) . "</td></tr>\n";
}

/** Read the last few lines of one of the data logs. */
function tail_log($path, $limit = 20)
{
    if (!is_file($path)) {
        return [];
    }

    $lines = array_slice(array_filter(explode("\n", (string) file_get_contents($path))), -$limit);

    return array_reverse($lines);
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Form check</title>
<style>
  body { font: 15px/1.5 system-ui, -apple-system, Segoe UI, Arial, sans-serif; margin: 0; padding: 24px; background: #f6f7f9; color: #222; }
  .wrap { max-width: 880px; margin: 0 auto; }
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
  .note ol { margin: 8px 0 0; padding-left: 20px; }
  .btn { display: inline-block; margin: 14px 0 0; background: #0b62d6; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; }
</style>
</head>
<body>
<div class="wrap">

<h1>Form check</h1>
<p class="lead"><?php echo e(SITE_NAME); ?> &middot; <?php echo e(date('d M Y, g:i A')); ?> IST</p>

<h2>1. Files and settings</h2>
<table>
<?php
foreach ([
    'config/site.php'              => ROOT_DIR . '/config/site.php',
    'core/helpers.php'             => ROOT_DIR . '/core/helpers.php',
    'core/csrf.php'                => ROOT_DIR . '/core/csrf.php',
    'core/bootstrap.php'           => ROOT_DIR . '/core/bootstrap.php',
    'core/web3forms.php'           => ROOT_DIR . '/core/web3forms.php',
    'actions/contact-handler.php'  => ROOT_DIR . '/actions/contact-handler.php',
    'enquiry-form.php'             => ROOT_DIR . '/enquiry-form.php',
] as $label => $path) {
    $there = is_file($path);
    row($label, $there ? 'present' : 'MISSING — upload it', $there ? 'good' : 'bad');
}

// Files from the old mail-based version. Left on the server they do nothing,
// but they are dead weight and one of them held credentials.
foreach (['core/mailer.php', 'mail-config.php', 'mail-config.sample.php', 'mail-test.php',
          'form-to-email-contact.php', 'enquiry-state.php', 'thank-you.php'] as $stale) {
    if (file_exists(ROOT_DIR . '/' . $stale)) {
        row('Old file still on the server', $stale . ' — delete it', 'warn');
    }
}

$keyOk = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', WEB3FORMS_ACCESS_KEY) === 1;
row('Web3Forms access key', $keyOk
        ? 'set, correct shape (' . substr(WEB3FORMS_ACCESS_KEY, 0, 8) . '…)'
        : 'does not look like a Web3Forms key', $keyOk ? 'good' : 'bad');
row('Enquiries are emailed to', RECIPIENT_INBOX . ' (set in the Web3Forms dashboard, not here)');

if (function_exists('curl_init')) {
    $transport = 'cURL';
    $transportOk = 'good';
} elseif (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
    $transport = 'NONE — no cURL and allow_url_fopen is off. Ask the host to enable the curl extension.';
    $transportOk = 'bad';
} elseif (!extension_loaded('openssl')) {
    $transport = 'NONE — no cURL and no OpenSSL, so PHP cannot open an HTTPS connection. '
               . 'Ask the host to enable the curl extension.';
    $transportOk = 'bad';
} else {
    $transport = 'stream wrapper (no cURL, but OpenSSL is present)';
    $transportOk = 'warn';
}
row('Outbound HTTPS method', $transport, $transportOk);
row('data/ writable', is_dir(ROOT_DIR . '/data') && is_writable(ROOT_DIR . '/data')
        ? 'yes' : 'NO — enquiries cannot be recorded locally',
    is_dir(ROOT_DIR . '/data') && is_writable(ROOT_DIR . '/data') ? 'good' : 'bad');
row('PHP version', PHP_VERSION, version_compare(PHP_VERSION, '8.0', '>=') ? 'good' : 'warn');
?>
</table>

<h2>2. Delivery attempts</h2>
<?php
$attempts = tail_log(ROOT_DIR . '/data/delivery.log', 12);

// Older builds only logged failures; show those too rather than losing history.
$legacy = tail_log(ROOT_DIR . '/data/send-errors.log', 6);

if ($attempts === [] && $legacy === []) {
    echo '<div class="note"><strong>Nothing recorded.</strong> No enquiry has reached the delivery step since '
       . 'these files were uploaded. If you have submitted the form, check that <code>data/</code> is writable '
       . 'in section 1 and that the old <code>mail-test.php</code> is gone.</div>';
} else {
    $accepted = 0;
    $failed   = 0;
    foreach ($attempts as $line) {
        $r = json_decode($line, true);
        if (!is_array($r)) { continue; }
        ($r['result'] ?? '') === 'accepted' ? $accepted++ : $failed++;
    }

    echo '<table>';
    foreach ($attempts as $line) {
        $r = json_decode($line, true);
        if (!is_array($r)) { continue; }
        $ok = ($r['result'] ?? '') === 'accepted';
        row(date('d M, g:i A', strtotime($r['at'] ?? 'now')),
            ($ok ? 'ACCEPTED by Web3Forms' : 'FAILED')
            . "\nLead: " . ($r['name'] ?? '?') . ' — ' . ($r['phone'] ?? '?')
            . "\nSaved on server: " . ($r['saved'] ?? '?')
            . "\nSent via: " . (($r['transport'] ?? '') !== '' ? $r['transport'] : 'n/a')
            . ' · HTTP ' . ($r['status'] ?? 0)
            . (($r['service'] ?? '') !== '' ? "\nWeb3Forms said: " . $r['service'] : '')
            . (($r['reason'] ?? '') !== '' ? "\nReason: " . $r['reason'] : ''),
            $ok ? 'good' : 'bad');
    }
    foreach ($legacy as $line) {
        $r = json_decode($line, true);
        if (!is_array($r)) { continue; }
        row(date('d M, g:i A', strtotime($r['at'] ?? 'now')),
            "FAILED (older log)\nLead: " . ($r['name'] ?? '?') . ' — ' . ($r['phone'] ?? '?')
            . "\nReason: " . ($r['reason'] ?? 'not recorded'), 'bad');
    }
    echo '</table>';

    if ($accepted > 0 && $failed === 0) {
        echo '<div class="note"><strong>Web3Forms accepted every submission.</strong> That means this website '
           . 'has done its part in full &mdash; the enquiry left the server and Web3Forms took charge of it. '
           . 'If nothing is arriving in the inbox, the cause is in the Web3Forms account, and there is one '
           . 'overwhelmingly likely reason:'
           . '<ol><li><strong>The form\'s email address has never been confirmed.</strong> When a form is '
           . 'created, Web3Forms emails that address a verification link, and until it is clicked they accept '
           . 'submissions and deliver nothing &mdash; exactly what you are seeing. Sign in at web3forms.com, '
           . 'open this form, and check whether it says the address is verified. Look in the spam folder of '
           . '<code>' . e(RECIPIENT_INBOX) . '</code> for their original verification email, or use the '
           . 'dashboard to resend it.</li>'
           . '<li>The address on the form is a different one from <code>' . e(RECIPIENT_INBOX) . '</code>. '
           . 'The destination is set there, not in this site\'s code &mdash; check what it actually says.</li>'
           . '<li>Their email is landing in spam. Search Gmail for <code>web3forms</code>, including '
           . 'Spam and All Mail.</li></ol>'
           . 'Nothing in this codebase can affect any of those, and no code change will fix them.</div>';
    } elseif ($failed > 0) {
        echo '<div class="note"><strong>Some submissions never left the server.</strong> The reason above is '
           . 'what the network or Web3Forms actually reported. "Saved on server: yes" means the enquiry itself '
           . 'is safe and listed in section 3, so no lead has been lost.</div>';
    }
}
?>

<h2>3. Enquiries recorded on the server</h2>
<?php
$leads = tail_log(ROOT_DIR . '/data/enquiries.log', 25);

if ($leads === []) {
    echo '<div class="note"><strong>None yet.</strong> Every submission is written here before delivery is '
       . 'attempted. If you have submitted the form since uploading these files and this stays empty, the '
       . 'problem is earlier than delivery — check that data/ is writable, above.</div>';
} else {
    echo '<div class="note"><strong>' . count($leads) . ' enquiry(ies) recorded.</strong> These reached the '
       . 'server safely, so nothing has been lost even if an email did not arrive.</div><table>';
    foreach ($leads as $line) {
        $r = json_decode($line, true);
        if (!is_array($r)) { continue; }
        row(date('d M, g:i A', strtotime($r['at'] ?? 'now')),
            ($r['name'] ?? '?') . ' — ' . ($r['phone'] ?? '?')
            . (($r['email'] ?? '') !== '' ? ' — ' . $r['email'] : '')
            . "\n" . ($r['service'] ?? '')
            . (($r['message'] ?? '') !== '' ? "\n" . $r['message'] : ''));
    }
    echo '</table>';
}
?>

<h2>4. Send a test through Web3Forms</h2>
<?php if (!$runTest): ?>
  <div class="note">This posts one clearly-marked test enquiry to Web3Forms, exactly the way the form does.
  It proves the access key works and that this server can reach the service. Check
  <code><?php echo e(RECIPIENT_INBOX); ?></code> afterwards, spam folder included.</div>
  <a class="btn" href="?key=<?php echo urlencode(CHECK_KEY); ?>&amp;send=1">Send a test submission</a>
<?php else:
    $err  = '';
    $sent = web3forms_send([
        'access_key' => WEB3FORMS_ACCESS_KEY,
        'subject'    => '[TEST] ' . SITE_NAME . ' form check ' . date('H:i:s'),
        'from_name'  => SITE_NAME . ' Website',
        'Name'       => 'Form check (not a real enquiry)',
        'Phone'      => '+910000000000',
        'Service'    => 'Test submission',
        'Received'   => date('d M Y, g:i A') . ' (IST)',
        'Message'    => 'Sent from form-check.php to confirm Web3Forms delivery. Ignore this.',
    ], $err);
?>
  <table>
    <?php row('Result', $sent ? 'ACCEPTED by Web3Forms — check the inbox now' : 'FAILED', $sent ? 'good' : 'bad'); ?>
    <?php if (!$sent) { row('Reason', $err, 'bad'); } ?>
  </table>
  <?php if ($sent): ?>
    <div class="note"><strong>Web3Forms accepted the message.</strong> Be precise about what that proves: the
    request left this server, the access key is valid, and Web3Forms has taken charge of the message. It does
    <em>not</em> prove the email was delivered.
    <br><br><strong>If this is accepted but nothing arrives, the problem is in the Web3Forms account.</strong>
    Almost always the form's email address has never been confirmed &mdash; until the verification link is
    clicked they accept submissions and send nothing. Sign in at web3forms.com, open this form, and check that
    <code><?php echo e(RECIPIENT_INBOX); ?></code> is listed and verified. Their verification email is often
    in the spam folder.</div>
  <?php else: ?>
    <div class="note">The request did not reach Web3Forms, so the reason above is a network or configuration
    problem on this server, not anything to do with the inbox. Check the outbound HTTPS method in section 1.</div>
  <?php endif; ?>
<?php endif; ?>

<h2>When you are done</h2>
<div class="note">Delete <code>form-check.php</code> from the server. It is not linked from the site and
search engines are told to ignore it, but it does not belong on a live site permanently.</div>

</div>
</body>
</html>
