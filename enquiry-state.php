<?php
/**
 * Enquiry form state — include this as the FIRST thing in any page that shows
 * the enquiry form, before a single byte of HTML:
 *
 *     <?php include 'enquiry-state.php'; ?><!DOCTYPE html>
 *
 * When form-to-email-contact.php rejects a submission it stashes what the
 * visitor typed in the session and bounces them back here with
 * ?enquiry=error&code=... . This file turns that code into a sentence and
 * hands the typed values back to enquiry-form.php so nobody has to retype
 * their details.
 *
 * The session is only ever started on that error round trip, so ordinary
 * visitors still get no cookie and stay fully cacheable.
 */

$enquiryError = '';
$enquiryOld   = [];

if (($_GET['enquiry'] ?? '') === 'error') {
    $messages = [
        'required' => 'Please enter your name and mobile number.',
        'phone'    => 'Please enter a valid 10-digit mobile number.',
        'email'    => 'Please enter a valid email address, or leave it blank.',
        'send'     => 'Sorry — we could not send your enquiry just now. Please call us on 95814 31299 and we will take the details over the phone.',
    ];

    $code         = (string) ($_GET['code'] ?? '');
    $enquiryError = $messages[$code] ?? 'Something went wrong. Please try again.';

    // Silenced: if the host has sessions switched off the visitor still gets
    // the message above, just with empty boxes — never a warning on the page.
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        @session_start();
    }

    if (!empty($_SESSION['enquiry_old']) && is_array($_SESSION['enquiry_old'])) {
        $enquiryOld = $_SESSION['enquiry_old'];
        // One-shot: a refresh should not keep re-filling the form.
        unset($_SESSION['enquiry_old']);
    }
}

/** Value to put back in a field after a failed submission. */
function enquiry_old($field, $old)
{
    return htmlspecialchars((string) ($old[$field] ?? ''), ENT_QUOTES, 'UTF-8');
}
