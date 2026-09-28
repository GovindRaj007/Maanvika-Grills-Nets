<?php
/**
 * Delivery through Web3Forms.
 *
 * WHY IT WORKS THIS WAY
 *
 * Web3Forms only accepts submissions posted by the visitor's browser. Posting
 * from PHP is a paid feature, and on the free plan the API answers:
 *
 *   HTTP 403 — This method is not allowed. Use our API in client side or
 *   contact support with server IP address (Pro plan is required)
 *
 * The obvious response would be to point the form's action straight at their
 * API. That works, but the browser would then never touch this site on the way
 * past, so there would be no server-side validation, no allowlist on the
 * service field, no honeypot check and no record of the enquiry if anything
 * downstream went wrong.
 *
 * So the enquiry still posts here first and is validated and recorded exactly as
 * before; this file then returns a small page whose only job is to post those
 * same values on to Web3Forms from the browser. The visitor sees it for a
 * fraction of a second before Web3Forms redirects them back to the thank-you
 * message. With JavaScript switched off there is a button instead, so the form
 * still works either way.
 *
 * The access key appears in that page's source, which is how Web3Forms intend it
 * to be used: it identifies the form rather than authorising anything, and their
 * own examples put it in plain HTML.
 */

if (!defined('ROOT_DIR')) {
    http_response_code(403);
    exit('Forbidden');
}

const WEB3FORMS_ENDPOINT = 'https://api.web3forms.com/submit';

/**
 * Send the relay page and stop.
 *
 * $fields are handed on to Web3Forms as hidden inputs. $redirectUrl is where
 * Web3Forms sends the visitor afterwards — the form page's own thank-you state,
 * so the result stays on this site.
 */
function web3forms_relay(array $fields, $redirectUrl)
{
    $fields['redirect'] = $redirectUrl;

    // A step inside a submission: never cached, never indexed.
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Robots-Tag: noindex, nofollow');

    $inputs = '';
    foreach ($fields as $name => $value) {
        $inputs .= '      <input type="hidden" name="' . e($name) . '" value="' . e($value) . '">' . "\n";
    }

    echo '<!DOCTYPE html>
<html lang="en-IN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sending your enquiry...</title>
<style>
  body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
         background: #f6f7f9; color: #2b2b2b;
         font: 16px/1.5 system-ui, -apple-system, "Segoe UI", Arial, sans-serif; }
  .box { text-align: center; padding: 32px 24px; max-width: 420px; }
  .spinner { width: 34px; height: 34px; margin: 0 auto 18px; border-radius: 50%;
             border: 3px solid #dcdfe3; border-top-color: #299B46; animation: spin .8s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
  @media (prefers-reduced-motion: reduce) { .spinner { animation: none; } }
  button { font: inherit; font-weight: 600; color: #fff; background: #299B46; border: 0;
           border-radius: 6px; padding: 12px 24px; cursor: pointer; }
</style>
</head>
<body>
  <div class="box">
    <div class="spinner" aria-hidden="true"></div>
    <p role="status">Sending your enquiry...</p>

    <form id="relay" method="POST" action="' . e(WEB3FORMS_ENDPOINT) . '">
' . $inputs . '      <noscript>
        <p>Please press the button below to finish sending your enquiry.</p>
        <button type="submit">Send my enquiry</button>
      </noscript>
    </form>
  </div>

  <script>
    /* Submitted from script so the visitor never has to do anything. The form
       above is a real form, so with JavaScript off there is a working button
       rather than a dead end. */
    document.getElementById("relay").submit();
  </script>
</body>
</html>';

    exit;
}
