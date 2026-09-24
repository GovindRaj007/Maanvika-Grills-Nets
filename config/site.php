<?php
/**
 * Site configuration.
 *
 * Every value the enquiry form depends on lives here, so nothing important is
 * buried in a handler. Included by core/bootstrap.php.
 */

// Internal file: refuses to run unless an entry point pulled it in.
if (!defined('ROOT_DIR')) {
    http_response_code(403);
    exit('Forbidden');
}

// ---------------------------------------------------------------------------
// Identity
// ---------------------------------------------------------------------------

const SITE_DOMAIN = 'maanvikasafetynetschennai.com';

// https, no www, no trailing slash — the canonical form .htaccess redirects to.
const BASE_URL  = 'https://' . SITE_DOMAIN;
const SITE_NAME = 'Maanvika Grills & Nets';

// Where enquiries are read.
const RECIPIENT_INBOX = 'maanvikasafetysolutions@gmail.com';

/**
 * Who the enquiry mail is sent from.
 *
 * This is the Gmail address because it is the only mailbox this business
 * actually has. There is no email account on the domain.
 *
 * That matters, because a sender address has to exist somewhere for mail to
 * behave. An earlier setting here was no-reply@<the domain>, which was never
 * created in the hosting panel: the mail server accepted each enquiry, found
 * it was sent from an address it did not own, and dropped it. Nothing reached
 * the inbox and no bounce came back, because the address the bounce was
 * addressed to did not exist either.
 *
 * The trade-off of using the Gmail address: the message leaves this web server
 * while claiming to come from gmail.com, which Gmail cannot verify, so it may
 * be filed as spam even though it arrives. Check the spam folder after the
 * first live test, and mark it "not spam" if it is there — that teaches the
 * filter for subsequent enquiries.
 *
 * THE BETTER SETUP, when there is ten minutes to spare:
 *   1. In cPanel → Email Accounts, create no-reply@maanvikasafetynetschennai.com
 *      (included free with the hosting; nothing ever has to log in to it).
 *   2. In the DNS panel, confirm the domain has an SPF record naming this host.
 *   3. Change the line below to 'no-reply@' . SITE_DOMAIN.
 * Mail then passes every check and lands in the inbox reliably. Nothing else
 * in the code needs to change — this one constant is the whole switch.
 */
const MAIL_FROM = RECIPIENT_INBOX;

// Phone shown to visitors when something goes wrong.
const SITE_PHONE         = '+91 95814 31299';
const SITE_PHONE_DIALABLE = '+919581431299';

// ---------------------------------------------------------------------------
// Environment
//
// Derived from the SAPI rather than a constant somebody could leave switched
// on after testing. The built-in server (php -S) is only ever development;
// anything else is treated as live, so live mail can never be diverted to a
// file by a stale setting.
// ---------------------------------------------------------------------------

define('SITE_ENV', PHP_SAPI === 'cli-server' ? 'development' : 'production');

// ---------------------------------------------------------------------------
// Form behaviour
// ---------------------------------------------------------------------------

// Seconds one visitor must wait between submissions.
const FORM_COOLDOWN_SECONDS = 30;

// Indian mobile numbers, after the country code and trunk zero are removed.
const PHONE_DIGITS = 10;

// Field length caps. Anything longer is cut rather than rejected, so a long
// message never costs somebody their enquiry.
const MAX_NAME    = 100;
const MAX_EMAIL   = 150;
const MAX_MESSAGE = 2000;

/**
 * Pages that carry the enquiry form.
 *
 * The handler redirects back to the page the visitor submitted from, and will
 * only ever use a key from this list — a path arriving in the POST is never
 * trusted as a redirect target.
 */
const FORM_PAGES = [
    'index.php'   => '/',
    'contact.php' => '/contact.php',
];

const FORM_ANCHOR = '#enquiry';

/**
 * Areas we cover. The key is what the form posts and what the handler checks
 * against; the label is what people see and what the email shows.
 */
const CITIES = [
    'anna-nagar'   => 'Anna Nagar',
    'ambattur'     => 'Ambattur',
    'adyar'        => 'Adyar',
    'chromepet'    => 'Chromepet',
    'guindy'       => 'Guindy',
    'mogappair'    => 'Mogappair',
    'omr'          => 'OMR / Sholinganallur',
    'perambur'     => 'Perambur',
    'porur'        => 'Porur',
    't-nagar'      => 'T. Nagar',
    'tambaram'     => 'Tambaram',
    'velachery'    => 'Velachery',
    'other'        => 'Somewhere else in Chennai',
];

/** Services offered. Same arrangement: key is posted, label is displayed. */
const SERVICES = [
    'balcony-safety-nets'   => 'Balcony Safety Nets',
    'pigeon-nets'           => 'Pigeon Safety Nets',
    'anti-bird-nets'        => 'Anti Bird Nets',
    'children-safety-nets'  => 'Children Safety Nets',
    'cricket-practice-nets' => 'Cricket Practice Nets',
    'sports-nets'           => 'All Sports Nets',
    'shade-nets'            => 'Shade Nets',
    'cloth-hanger'          => 'Balcony Cloth Hanger',
    'invisible-grills'      => 'Invisible Grill For Balcony',
    'bird-spikes'           => 'Bird Spikes',
    'other'                 => 'Something else',
];
