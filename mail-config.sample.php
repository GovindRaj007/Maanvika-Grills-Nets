<?php
/**
 * SMTP settings for the enquiry form.
 *
 * HOW TO USE THIS FILE
 *   1. Copy it to  mail-config.php  (same folder).
 *   2. Fill in the password below.
 *   3. Upload mail-config.php to the server.
 *
 * mail-config.php is deliberately kept out of git and blocked from the web,
 * so the password never ends up in the repository or readable in a browser.
 * This sample file carries no password and is safe to commit.
 *
 * ---------------------------------------------------------------------------
 * OPTION A — send through the Gmail account itself (recommended)
 * ---------------------------------------------------------------------------
 * The enquiry then arrives from the same account that receives it, so it can
 * never be filtered as spam or rejected. Gmail will not accept your ordinary
 * password here; it needs an App Password:
 *
 *   1. Sign in as maanvikasafetysolutions@gmail.com
 *   2. myaccount.google.com → Security → turn on 2-Step Verification
 *      (App Passwords do not exist until 2-Step Verification is on)
 *   3. Security → App passwords → create one, named e.g. "Website form"
 *   4. Google shows a 16-character password such as "abcd efgh ijkl mnop".
 *      Paste it below. Spaces are fine, they are ignored.
 *
 * ---------------------------------------------------------------------------
 * OPTION B — send through the hosting account's own mailbox
 * ---------------------------------------------------------------------------
 * If the host blocks outbound connections to Gmail, create an email account in
 * cPanel (for example no-reply@maanvikasafetynetschennai.com), then use:
 *
 *   'host'     => 'mail.maanvikasafetynetschennai.com',
 *   'port'     => 587,
 *   'security' => 'tls',
 *   'username' => 'no-reply@maanvikasafetynetschennai.com',
 *   'password' => 'the mailbox password',
 *   'from'     => 'no-reply@maanvikasafetynetschennai.com',
 *
 * ---------------------------------------------------------------------------
 * To go back to PHP's mail() for any reason, set 'enabled' => false.
 */

return [
    // false falls back to PHP mail(), which is what was failing before.
    'enabled' => true,

    'host'     => 'smtp.gmail.com',
    'port'     => 587,          // 587 with 'tls', or 465 with 'ssl'
    'security' => 'tls',        // 'tls' | 'ssl' | 'none'

    'username' => 'maanvikasafetysolutions@gmail.com',
    'password' => 'yavi zzav actf awii',

    // The address the mail is sent from. With Gmail this must be the account
    // above, or Gmail silently rewrites it.
    'from'      => 'maanvikasafetysolutions@gmail.com',
    'from_name' => 'Maanvika Grills & Nets Website',

    // Seconds to wait for the mail server before giving up.
    'timeout' => 20,
];
