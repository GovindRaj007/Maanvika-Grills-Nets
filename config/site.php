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

// Where enquiries are read. Recorded here for reference only: the destination
// is set against the access key in the Web3Forms dashboard, not by this site.
const RECIPIENT_INBOX = 'maanvikasafetysolutions@gmail.com';

/**
 * Web3Forms access key.
 *
 * Enquiries are delivered by Web3Forms, not by this server. The form posts to
 * their API over HTTPS and they do the emailing, so delivery no longer depends
 * on the host's mail setup — which is what kept failing: PHP's mail() shares a
 * queue with every other site on the server, caps how much it will send in an
 * hour, and gives no way to find out what became of a message.
 *
 * The key belongs to whichever address was verified when the form was created
 * in the Web3Forms dashboard; that is where enquiries arrive. To change the
 * destination, change it there — nothing here needs editing.
 *
 * The key is not a password. It identifies the form, and Web3Forms treats it as
 * public (their own examples put it in the page source). This site posts from
 * the server instead, so it never appears in the page at all.
 */
const WEB3FORMS_ACCESS_KEY = '0b7caae7-8d85-4d99-8f1a-301799ac64c8';

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
