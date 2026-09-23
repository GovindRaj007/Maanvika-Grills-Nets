<?php
/**
 * Minimal SMTP sender — no Composer, no libraries, one file.
 *
 * WHY THIS EXISTS
 *
 * PHP's mail() hands the message to whatever mail program the hosting account
 * happens to run, and returns true as soon as that program accepts it. That
 * says nothing about whether the message was ever delivered. Mail sent this
 * way claims to come from our domain but leaves from the web host's servers,
 * so Gmail checks SPF, finds nothing authorising that server, and rejects the
 * message at its gateway. The rejection bounces to the sender address, which
 * on most sites is a mailbox nobody reads — so the enquiry disappears with no
 * error anywhere.
 *
 * Logging in to a real mailbox and sending through it removes the guesswork:
 * the message is genuinely sent by that account, so it passes every check and
 * lands in the inbox. It also fails loudly and specifically when something is
 * wrong, which mail() never does.
 *
 * Configure it in mail-config.php (copy mail-config.sample.php).
 */

/**
 * Read one complete SMTP reply, including multi-line ones.
 *
 * A server answers EHLO with several lines; every line but the last has a
 * hyphen after the status code ("250-PIPELINING", then "250 HELP"). Stopping
 * at the first line would leave the rest in the buffer and throw every later
 * command out of step.
 */
function smtp_read($socket, &$code)
{
    $code  = 0;
    $reply = '';

    while (($line = fgets($socket, 1024)) !== false) {
        $reply .= $line;
        // "250-" continues, "250 " ends. Anything shorter is a broken server.
        if (strlen($line) < 4 || $line[3] !== '-') {
            $code = (int) substr($line, 0, 3);
            break;
        }
    }

    return rtrim($reply, "\r\n");
}

/** Send one command and return true when the reply code is expected. */
function smtp_cmd($socket, $command, array $expected, &$reply, &$code)
{
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }
    $reply = smtp_read($socket, $code);

    return in_array($code, $expected, true);
}

/**
 * Send one message over SMTP.
 *
 * $headers is a list of ready-made header lines, exactly as mail() takes them.
 * Returns true on success; on failure $error holds a sentence naming the step
 * that failed, so the diagnostic page can show something actionable.
 */
function smtp_send(array $cfg, $to, $subject, $body, array $headers, &$error)
{
    $error = '';

    $host     = (string) ($cfg['host'] ?? '');
    $port     = (int) ($cfg['port'] ?? 587);
    $user     = (string) ($cfg['username'] ?? '');
    $pass     = (string) ($cfg['password'] ?? '');
    $security = strtolower((string) ($cfg['security'] ?? 'tls'));
    $from     = (string) ($cfg['from'] ?? $user);
    $timeout  = (int) ($cfg['timeout'] ?? 20);

    if ($host === '' || $user === '' || $pass === '') {
        $error = 'SMTP is not configured (host, username or password is blank in mail-config.php).';
        return false;
    }

    // Both encrypted options need the openssl extension. Saying so plainly
    // here saves a long hunt through a connection error later.
    if ($security !== 'none' && !extension_loaded('openssl')) {
        $error = 'This server has no OpenSSL support in PHP, so it cannot make an encrypted '
               . 'connection. Ask the host to enable the openssl extension.';
        return false;
    }

    // Port 465 is encrypted from the first byte; 587 starts in the clear and
    // is upgraded with STARTTLS immediately after the greeting.
    $scheme = $security === 'ssl' ? 'ssl' : 'tcp';

    $context = stream_context_create([
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true],
    ]);

    $socket = @stream_socket_client(
        $scheme . '://' . $host . ':' . $port,
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$socket) {
        $error = 'Could not reach ' . $host . ':' . $port . ' — ' . ($errstr ?: 'connection failed')
               . '. Many shared hosts block outbound SMTP; if so, ask support to open port ' . $port . '.';
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $fail = function ($message) use ($socket, &$error) {
        $error = $message;
        @fclose($socket);
        return false;
    };

    // Greeting.
    if (!smtp_cmd($socket, null, [220], $reply, $code)) {
        return $fail('The mail server did not greet us properly: ' . $reply);
    }

    $helo = (string) ($cfg['helo'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $helo = preg_replace('/[^A-Za-z0-9.\-]/', '', $helo) ?: 'localhost';

    if (!smtp_cmd($socket, 'EHLO ' . $helo, [250], $reply, $code)) {
        return $fail('EHLO was refused: ' . $reply);
    }

    if ($security === 'tls') {
        if (!smtp_cmd($socket, 'STARTTLS', [220], $reply, $code)) {
            return $fail('The server refused to start encryption: ' . $reply);
        }

        $crypto = @stream_socket_enable_crypto(
            $socket,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
        );

        if ($crypto !== true) {
            return $fail('The encrypted connection could not be established (TLS handshake failed).');
        }

        // The server must be greeted again inside the encrypted session.
        if (!smtp_cmd($socket, 'EHLO ' . $helo, [250], $reply, $code)) {
            return $fail('EHLO after STARTTLS was refused: ' . $reply);
        }
    }

    // AUTH LOGIN is what Gmail and cPanel both accept.
    if (!smtp_cmd($socket, 'AUTH LOGIN', [334], $reply, $code)) {
        return $fail('The server would not start a login: ' . $reply);
    }
    if (!smtp_cmd($socket, base64_encode($user), [334], $reply, $code)) {
        return $fail('The username was not accepted: ' . $reply);
    }
    if (!smtp_cmd($socket, base64_encode($pass), [235], $reply, $code)) {
        return $fail('Login failed — check the username and password. For Gmail this must be a '
                   . '16-character App Password, not the normal account password. Server said: ' . $reply);
    }

    if (!smtp_cmd($socket, 'MAIL FROM:<' . $from . '>', [250], $reply, $code)) {
        return $fail('The sender address was rejected: ' . $reply);
    }
    if (!smtp_cmd($socket, 'RCPT TO:<' . $to . '>', [250, 251], $reply, $code)) {
        return $fail('The recipient address was rejected: ' . $reply);
    }
    if (!smtp_cmd($socket, 'DATA', [354], $reply, $code)) {
        return $fail('The server refused the message data: ' . $reply);
    }

    // Headers mail() would have added for us must be written out in full here.
    $message = 'Date: ' . date('r') . "\r\n"
             . 'To: ' . $to . "\r\n"
             . 'Subject: ' . $subject . "\r\n"
             . implode("\r\n", $headers) . "\r\n\r\n"
             . $body;

    // Normalise to CRLF, then dot-stuff: a line consisting of a single dot is
    // how DATA ends, so any real line starting with one must be doubled or it
    // would truncate the message.
    $message = str_replace(["\r\n", "\r", "\n"], "\n", $message);
    $message = str_replace("\n", "\r\n", $message);
    $message = preg_replace('/^\./m', '..', $message);

    fwrite($socket, $message . "\r\n.\r\n");

    if (!smtp_cmd($socket, null, [250], $reply, $code)) {
        return $fail('The server did not accept the message: ' . $reply);
    }

    @smtp_cmd($socket, 'QUIT', [221], $reply, $code);
    @fclose($socket);

    return true;
}

/** Load mail-config.php if it is present, otherwise an empty configuration. */
function smtp_config()
{
    $file = __DIR__ . '/mail-config.php';

    if (!is_file($file)) {
        return [];
    }

    $cfg = include $file;

    return is_array($cfg) ? $cfg : [];
}
