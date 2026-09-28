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

row('How submissions are sent', 'The visitor’s browser posts to Web3Forms. Their free plan refuses '
    . 'posts made by the server, so nothing here needs outbound HTTPS.', 'good');
row('data/ writable', is_dir(ROOT_DIR . '/data') && is_writable(ROOT_DIR . '/data')
        ? 'yes' : 'NO — enquiries cannot be recorded locally',
    is_dir(ROOT_DIR . '/data') && is_writable(ROOT_DIR . '/data') ? 'good' : 'bad');
row('PHP version', PHP_VERSION, version_compare(PHP_VERSION, '8.0', '>=') ? 'good' : 'warn');
?>
</table>

<h2>2. Submissions handed to Web3Forms</h2>
<?php
$attempts = tail_log(ROOT_DIR . '/data/delivery.log', 12);

// Earlier builds posted from the server and logged the outcome here. Web3Forms
// refuses that on the free plan, so those lines are history now — shown rather
// than dropped, because they explain why the approach changed.
$legacy = tail_log(ROOT_DIR . '/data/send-errors.log', 5);

if ($attempts === [] && $legacy === []) {
    echo '<div class="note"><strong>Nothing recorded.</strong> No enquiry has reached the hand-over step since '
       . 'these files were uploaded. If you have submitted the form, check that <code>data/</code> is writable '
       . 'in section 1.</div>';
} else {
    if ($attempts !== []) {
        echo '<div class="note"><strong>' . count($attempts) . ' submission(s) handed over.</strong> '
           . 'The browser is what posts to Web3Forms now, so this records that the enquiry was validated, '
           . 'saved and passed on. Whether the email then arrived is answered by the inbox, and by section 4 '
           . 'below.</div>';
    }
    echo '<table>';
    foreach ($attempts as $line) {
        $r = json_decode($line, true);
        if (!is_array($r)) { continue; }
        row(date('d M, g:i A', strtotime($r['at'] ?? 'now')),
            ($r['result'] ?? '?')
            . "\nLead: " . ($r['name'] ?? '?') . ' — ' . ($r['phone'] ?? '?')
            . "\nSaved on server: " . ($r['saved'] ?? '?'),
            ($r['saved'] ?? '') === 'yes' ? 'good' : 'warn');
    }
    foreach ($legacy as $line) {
        $r = json_decode($line, true);
        if (!is_array($r)) { continue; }
        row(date('d M, g:i A', strtotime($r['at'] ?? 'now')),
            "From the earlier server-side method, which Web3Forms blocks on the free plan:\n"
            . ($r['name'] ?? '?') . ' — ' . ($r['phone'] ?? '?')
            . "\n" . ($r['reason'] ?? ''), 'warn');
    }
    echo '</table>';
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
<div class="note">
  This posts one clearly-marked test straight from your browser to Web3Forms, which is the only way their
  free plan accepts submissions. It proves the access key is valid and the form is live. Afterwards check
  <code><?php echo e(RECIPIENT_INBOX); ?></code>, <strong>spam folder included</strong>.
  <br><br>
  Web3Forms will show its own confirmation page; use the back button to return here.
</div>
<form method="POST" action="<?php echo e(WEB3FORMS_ENDPOINT); ?>">
  <input type="hidden" name="access_key" value="<?php echo e(WEB3FORMS_ACCESS_KEY); ?>">
  <input type="hidden" name="subject" value="[TEST] <?php echo e(SITE_NAME); ?> form check">
  <input type="hidden" name="from_name" value="<?php echo e(SITE_NAME); ?> Website">
  <input type="hidden" name="Name" value="Form check (not a real enquiry)">
  <input type="hidden" name="Phone" value="+910000000000">
  <input type="hidden" name="Service" value="Test submission">
  <input type="hidden" name="Sent" value="<?php echo e(date('d M Y, g:i A')); ?> IST">
  <input type="hidden" name="Message" value="Sent from form-check.php to confirm delivery. Ignore this.">
  <button type="submit" class="btn" style="border:0;cursor:pointer">Send a test submission</button>
</form>

<div class="note">
  <strong>Reading the result.</strong><br><br>
  &bull; <em>The test arrives in the inbox</em> &rarr; the key and the form are fine, and the enquiry form
    will deliver too.<br>
  &bull; <em>Web3Forms shows an error</em> &rarr; the message on their page names the problem; the access key
    or the form's status in the dashboard is the place to look.<br>
  &bull; <em>Their page says success but nothing arrives</em> &rarr; the form's email address has most likely
    never been confirmed. Until the verification link is clicked, Web3Forms accepts submissions and delivers
    nothing. Sign in at web3forms.com, open this form, and check that
    <code><?php echo e(RECIPIENT_INBOX); ?></code> is listed and verified &mdash; their verification email is
    often in the spam folder.
</div>

<h2>When you are done</h2>
<div class="note">Delete <code>form-check.php</code> from the server. It is not linked from the site and
search engines are told to ignore it, but it does not belong on a live site permanently.</div>

</div>
</body>
</html>
