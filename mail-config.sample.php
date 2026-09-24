<?php
/**
 * OPTIONAL SMTP settings — TEMPLATE ONLY.
 *
 * THE FORM DOES NOT NEED THIS FILE. It sends with PHP mail(), which needs no
 * password and no configuration beyond MAIL_FROM in config/site.php.
 *
 * This exists only as a fallback for a host where mail() cannot deliver at
 * all. If mail() works, leave this file alone and never create mail-config.php.
 *
 * ###########################################################################
 * #  DO NOT PUT THE PASSWORD IN THIS FILE.                                  #
 * #                                                                         #
 * #  This file is committed to git and pushed to GitHub. A password typed   #
 * #  here becomes public. It has happened once already, and the App         #
 * #  Password had to be revoked.                                            #
 * #                                                                         #
 * #  The password belongs in  mail-config.php  — a copy of this file that   #
 * #  git ignores and the web server refuses to serve.                       #
 * ###########################################################################
 *
 * HOW TO USE THIS FILE
 *   1. Copy it to  mail-config.php  (same folder, note: no ".sample").
 *   2. Fill the password into mail-config.php — never into this one.
 *   3. Upload mail-config.php to the server, in the same folder as index.php.
 *
 * As a safety net the form ignores this file even if it is renamed wrongly:
 * the 'is_template' flag below makes it refuse to be used as a live config.
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

// Refuses to run unless an entry point pulled it in.
if (!defined('ROOT_DIR')) {
    http_response_code(403);
    exit('Forbidden');
}

return [
    // Marks this as the template. smtp_config() refuses any config carrying
    // this flag, so the form cannot accidentally run on the committed file.
    // Delete this line in your mail-config.php.
    'is_template' => true,

    // false falls back to PHP mail(), which is what was failing before.
    'enabled' => true,

    'host'     => 'smtp.gmail.com',
    'port'     => 587,          // 587 with 'tls', or 465 with 'ssl'
    'security' => 'tls',        // 'tls' | 'ssl' | 'none'

    'username' => 'maanvikasafetysolutions@gmail.com',
    'password' => 'PASTE THE 16-CHARACTER APP PASSWORD HERE',

    // The address the mail is sent from. With Gmail this must be the account
    // above, or Gmail silently rewrites it.
    'from'      => 'maanvikasafetysolutions@gmail.com',
    'from_name' => 'Maanvika Grills & Nets Website',

    // Seconds to wait for the mail server before giving up.
    'timeout' => 20,
];
