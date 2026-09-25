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
 * DO NOT change this to the Gmail address. It has been tested on this host and
 * it does not work.
 *
 * This server sends through /usr/sbin/hsendmail, which relays only for domains
 * the hosting account owns. Three identical messages were sent from the live
 * site to prove it:
 *
 *   From maanvikasafetysolutions@gmail.com, envelope set   -> never arrived
 *   From maanvikasafetysolutions@gmail.com, no envelope    -> never arrived
 *   From no-reply@maanvikasafetynetschennai.com            -> arrived
 *
 * All three were "accepted" by the mail program. Only the third was delivered.
 * A message claiming to come from gmail.com is taken by the local queue and
 * then dropped, because this server has no authority to send as gmail.com —
 * and no bounce comes back, which is why enquiries vanished with no error
 * anywhere.
 *
 * This address does not need a mailbox behind it. A mailbox is what receives
 * mail; sending only requires that the domain belongs to this hosting account,
 * which it does. Replies go to the visitor via Reply-To, and enquiries are read
 * in the Gmail inbox named in RECIPIENT_INBOX above.
 *
 * Optional polish, not required for delivery: create no-reply@ as a real
 * mailbox in the hosting panel and forward it to the Gmail address. Bounces and
 * out-of-office replies would then be visible instead of disappearing.
 */
const MAIL_FROM = 'no-reply@' . SITE_DOMAIN;

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
