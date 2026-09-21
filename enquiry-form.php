<?php
/**
 * Shared enquiry form.
 *
 * Set $enquiryFormSource before including to label where the lead came from,
 * e.g. <?php $enquiryFormSource = 'Contact Page'; include 'enquiry-form.php'; ?>
 */
$enquiryFormSource = $enquiryFormSource ?? 'Website';
$enquiryError = ($_GET['enquiry'] ?? '') === 'error'
    ? htmlspecialchars($_GET['reason'] ?? 'Something went wrong. Please try again.', ENT_QUOTES, 'UTF-8')
    : '';
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
          <div class="enquiry-alert" role="alert"><?php echo $enquiryError; ?></div>
        <?php endif; ?>

        <form class="enquiry-form" method="POST" action="form-to-email-contact.php" novalidate>
          <input type="hidden" name="source" value="<?php echo htmlspecialchars($enquiryFormSource, ENT_QUOTES, 'UTF-8'); ?>">

          <!-- Honeypot: hidden from people, filled in by bots. -->
          <div class="enquiry-hp" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="enquiry-field">
                <label for="enq-name">Your name <span aria-hidden="true">*</span></label>
                <input type="text" id="enq-name" name="name" autocomplete="name" required placeholder="e.g. Priya R">
              </div>
            </div>
            <div class="col-md-6">
              <div class="enquiry-field">
                <label for="enq-phone">Mobile number <span aria-hidden="true">*</span></label>
                <input type="tel" id="enq-phone" name="phone" inputmode="numeric" autocomplete="tel"
                       pattern="[0-9+ ]{10,15}" required placeholder="10-digit mobile number">
              </div>
            </div>
            <div class="col-md-6">
              <div class="enquiry-field">
                <label for="enq-email">Email <span class="enquiry-optional">(optional)</span></label>
                <input type="email" id="enq-email" name="email" autocomplete="email" placeholder="you@example.com">
              </div>
            </div>
            <div class="col-md-6">
              <div class="enquiry-field">
                <label for="enq-service">Service needed</label>
                <select id="enq-service" name="services">
                  <option value="">Select a service</option>
                  <option>Balcony Safety Nets</option>
                  <option>Pigeon Safety Nets</option>
                  <option>Anti Bird Nets</option>
                  <option>Bird Nets</option>
                  <option>Children Safety Nets</option>
                  <option>Cricket Practice Nets</option>
                  <option>All Sports Nets</option>
                  <option>Shade Nets</option>
                  <option>Balcony Cloth Hanger</option>
                  <option>Invisible Grill For Balcony</option>
                  <option>Bird Spikes</option>
                  <option>Something else</option>
                </select>
              </div>
            </div>
            <div class="col-12">
              <div class="enquiry-field">
                <label for="enq-message">Message <span class="enquiry-optional">(optional)</span></label>
                <textarea id="enq-message" name="message" rows="4" placeholder="Area to be covered, floor number, preferred visit time..."></textarea>
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
