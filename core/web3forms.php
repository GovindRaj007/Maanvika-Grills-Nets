<?php
/**
 * Delivery through Web3Forms.
 *
 * The site no longer sends mail itself. PHP's mail() handed the message to a
 * queue shared with every other site on the server, with an hourly cap, no
 * authentication and no way to find out what happened to a message — which is
 * why enquiries arrived for a while and then silently stopped.
 *
 * Web3Forms takes the submitted fields over HTTPS and does the emailing from
 * its own infrastructure, so delivery no longer depends on this host's mail
 * configuration at all.
 *
 * The request is made from the server rather than from the visitor's browser.
 * That keeps the access key out of the page source, and means the enquiry is
 * validated here before anything leaves the building.
 */

if (!defined('ROOT_DIR')) {
    http_response_code(403);
    exit('Forbidden');
}

const WEB3FORMS_ENDPOINT = 'https://api.web3forms.com/submit';

/**
 * Post one enquiry to Web3Forms.
 *
 * $fields is sent as JSON; anything that is not one of the service's reserved
 * names (access_key, subject, from_name, replyto, botcheck) is shown as a
 * labelled line in the email, so the array keys are written the way they should
 * read in the inbox.
 *
 * Returns true when the service confirms it accepted the submission. On failure
 * $error holds a sentence naming what went wrong, for data/send-errors.log.
 */
function web3forms_send(array $fields, &$error)
{
    $error   = '';
    $payload = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
        $error = 'Could not encode the enquiry as JSON: ' . json_last_error_msg();
        return false;
    }

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    // cURL is the normal route and brings its own TLS. The stream wrapper is
    // only a fallback, and it can only speak https when the openssl extension
    // is loaded — without it the request would fail with an unhelpful "unable
    // to find the wrapper" message, so say what is actually wrong.
    if (function_exists('curl_init')) {
        [$ok, $status, $body, $transportError] = web3forms_post_curl($payload, $headers);
    } elseif (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $error = 'This server has neither cURL nor allow_url_fopen, so it cannot make outbound requests. '
               . 'Ask the host to enable the curl extension.';
        return false;
    } elseif (!extension_loaded('openssl') && stripos(WEB3FORMS_ENDPOINT, 'https://') === 0) {
        $error = 'This server has no cURL and no OpenSSL, so PHP cannot open an HTTPS connection. '
               . 'Ask the host to enable the curl extension (or openssl).';
        return false;
    } else {
        [$ok, $status, $body, $transportError] = web3forms_post_stream($payload, $headers);
    }

    if (!$ok) {
        $error = 'Could not reach Web3Forms: ' . $transportError
               . '. If this persists the host may be blocking outbound HTTPS.';
        return false;
    }

    $decoded = json_decode((string) $body, true);

    // A 200 with success:true is the only result worth treating as delivered.
    if (is_array($decoded) && !empty($decoded['success'])) {
        return true;
    }

    $message = is_array($decoded) && isset($decoded['message'])
        ? (string) $decoded['message']
        : trim((string) $body);

    $error = 'Web3Forms rejected the submission (HTTP ' . $status . ')'
           . ($message !== '' ? ': ' . $message : '.');

    // The commonest cause by far, worth naming rather than leaving to guesswork.
    if ($status === 401 || $status === 403 || stripos($message, 'access') !== false) {
        $error .= ' Check WEB3FORMS_ACCESS_KEY in config/site.php against the key shown in the '
                . 'Web3Forms dashboard, and that the form there is still active.';
    }

    return false;
}

/** POST via cURL. Returns [reached, httpStatus, body, transportError]. */
function web3forms_post_curl($payload, array $headers)
{
    $ch = curl_init(WEB3FORMS_ENDPOINT);

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        // Certificate checking stays on: without it the connection could be
        // intercepted and the enquiry read in transit.
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);

    // curl_close() is deprecated from PHP 8.5 and the handle is freed when it
    // goes out of scope, so it is only called where it still does something.
    if (PHP_VERSION_ID < 80500) {
        curl_close($ch);
    }

    if ($body === false) {
        return [false, $status, '', $err !== '' ? $err : 'the request failed'];
    }

    return [true, $status, $body, ''];
}

/** POST via the stream wrapper, for a server without cURL. */
function web3forms_post_stream($payload, array $headers)
{
    $context = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => implode("\r\n", $headers),
            'content'       => $payload,
            'timeout'       => 20,
            // Read the body on a 4xx/5xx instead of returning false, so the
            // service's own explanation can be reported.
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);

    $body = @file_get_contents(WEB3FORMS_ENDPOINT, false, $context);

    if ($body === false) {
        $last = error_get_last();
        return [false, 0, '', trim((string) ($last['message'] ?? 'the request failed'))];
    }

    $status = 0;
    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
            $status = (int) $m[1];
        }
    }

    return [true, $status, $body, ''];
}
