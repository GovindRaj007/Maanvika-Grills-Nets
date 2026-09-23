<?php
/**
 * Shared enquiry form.
 *
 * The page including this must pull in enquiry-state.php before any output,
 * and may set $enquiryFormSource to label where the lead came from:
 *
 *     <?php include 'enquiry-state.php'; ?><!DOCTYPE html>
 *     ...
 *     <?php $enquiryFormSource = 'Contact Page'; include 'enquiry-form.php'; ?>
 */
$enquiryFormSource = $enquiryFormSource ?? 'Website';

// Set by enquiry-state.php after a rejected submission. The fallbacks keep
// this file working even if a page forgets to include it.
$enquiryError = $enquiryError ?? '';
$enquiryOld   = $enquiryOld ?? [];

if (!function_exists('enquiry_old')) {
    function enquiry_old($field, $old)
    {
        return htmlspecialchars((string) ($old[$field] ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// Which page to come back to if the submission is rejected.
$enquiryReturn = basename($_SERVER['SCRIPT_NAME'] ?? 'contact.php');
$enquiryService = (string) ($enquiryOld['services'] ?? '');
$enquiryServices = [
    'Balcony Safety Nets',
    'Pigeon Safety Nets',
    'Anti Bird Nets',
    'Bird Nets',
    'Children Safety Nets',
    'Cricket Practice Nets',
    'All Sports Nets',
    'Shade Nets',
    'Balcony Cloth Hanger',
    'Invisible Grill For Balcony',
    'Bird Spikes',
    'Something else',
];
?>
<section class="enquiry-section" id="enquiry">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-xl-7">

        <div class="section-title text-center">
          <h3 class="wow fadeInUp">Get in touch</h3>
          <h2 class="text-anime-style-2">Request a <span>free site visit</span></h2>
          <p class="wow fadeInUp" data-wow-delay="0.2s">Tell us what you need and we will call you back the same day with a no-obligation quote.</p>
        </div>

        <?php if ($enquiryError !== ''): ?>
          <div class="enquiry-alert" id="enquiry-alert" role="alert" tabindex="-1"><?php echo htmlspecialchars($enquiryError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form class="enquiry-form" method="POST" action="form-to-email-contact.php">
          <input type="hidden" name="source" value="<?php echo htmlspecialchars($enquiryFormSource, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="return" value="<?php echo htmlspecialchars($enquiryReturn, ENT_QUOTES, 'UTF-8'); ?>">

          <!-- Honeypot: hidden from people, filled in by bots. -->
          <div class="enquiry-hp" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="enquiry-field">
                <label for="enq-name">Your name <span aria-hidden="true">*</span></label>
                <input type="text" id="enq-name" name="name" autocomplete="name" maxlength="100" required
                       placeholder="e.g. Priya R"
                       value="<?php echo enquiry_old('name', $enquiryOld); ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="enquiry-field">
                <?php /* The pattern allows the spaces, dashes and +91 people paste
                          from their contacts; the handler reduces it to 10 digits. */ ?>
                <label for="enq-phone">Mobile number <span aria-hidden="true">*</span></label>
                <input type="tel" id="enq-phone" name="phone" inputmode="tel" autocomplete="tel"
                       pattern="[0-9+\-\s()]{10,18}" maxlength="18" required
                       title="Please enter your 10-digit mobile number"
                       placeholder="10-digit mobile number"
                       value="<?php echo enquiry_old('phone', $enquiryOld); ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="enquiry-field">
                <label for="enq-email">Email <span class="enquiry-optional">(optional)</span></label>
                <input type="email" id="enq-email" name="email" autocomplete="email" maxlength="150"
                       placeholder="you@example.com"
                       value="<?php echo enquiry_old('email', $enquiryOld); ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="enquiry-field">
                <label for="enq-service">Service needed</label>
                <select id="enq-service" name="services">
                  <option value="">Select a service</option>
                  <?php foreach ($enquiryServices as $service): ?>
                    <option<?php echo $service === $enquiryService ? ' selected' : ''; ?>><?php echo htmlspecialchars($service, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-12">
              <div class="enquiry-field">
                <label for="enq-message">Message <span class="enquiry-optional">(optional)</span></label>
                <textarea id="enq-message" name="message" rows="4" maxlength="2000" placeholder="Area to be covered, floor number, preferred visit time..."><?php echo enquiry_old('message', $enquiryOld); ?></textarea>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" name="submit" value="1" class="btn-default btn-noarrow enquiry-submit">Send enquiry</button>
              <p class="enquiry-note">Or call us directly on <a href="tel:+919581431299">+91 95814 31299</a> — free site visit across Chennai.</p>
            </div>
          </div>
        </form>

      </div>
    </div>
  </div>
</section>
