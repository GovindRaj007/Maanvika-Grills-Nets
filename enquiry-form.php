<?php
/**
 * Shared enquiry form.
 *
 * The page including this must require core/bootstrap.php before any output:
 *
 *     <?php require __DIR__ . '/core/bootstrap.php'; ?><!DOCTYPE html>
 *     ...
 *     <?php include 'enquiry-form.php'; ?>
 *
 * After a submission the same URL shows either the thank-you panel (?sent=1)
 * or the form again with each message beside its field (?error=...). Keeping
 * both states on the form's own URL means there is no separate page that could
 * be linked to, indexed, or reloaded to resend anything.
 */

if (!defined('ROOT_DIR')) {
    http_response_code(403);
    exit('Forbidden');
}

$result = enquiry_result();

$old        = $result['old'];
$fieldError = $result['errors'];

// Which page this is, so the handler knows where to send the visitor back to.
$formSource = basename($_SERVER['SCRIPT_NAME'] ?? 'contact.php');

/** A previously submitted value, escaped. */
$oldValue = static function ($field) use ($old) {
    return e((string) ($old[$field] ?? ''));
};

/** Was this service ticked last time? */
$wasChosen = static function ($key) use ($old) {
    return in_array($key, (array) ($old['services'] ?? []), true);
};

/** The message for one field, if it has one. */
$errorFor = static function ($field) use ($fieldError) {
    return isset($fieldError[$field]) ? (string) $fieldError[$field] : '';
};
?>
<section class="enquiry-section" id="enquiry">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-xl-7">

<?php if ($result['sent']): ?>

        <div class="section-title text-center">
          <h3 class="wow fadeInUp">Thank you</h3>
          <h2 class="text-anime-style-2">We have your <span>enquiry</span></h2>
        </div>

        <div class="enquiry-thanks" role="status" tabindex="-1" id="enquiry-thanks">
          <p>Thanks for getting in touch. We will call you back the same day to arrange a free site visit.</p>
          <p>If it is urgent, call us now on
            <a href="tel:<?php echo e(SITE_PHONE_DIALABLE); ?>"><?php echo e(SITE_PHONE); ?></a>.</p>
        </div>

<?php else: ?>

        <div class="section-title text-center">
          <h3 class="wow fadeInUp">Get in touch</h3>
          <h2 class="text-anime-style-2">Request a <span>free site visit</span></h2>
          <p class="wow fadeInUp" data-wow-delay="0.2s">Tell us what you need and we will call you back the same day with a no-obligation quote.</p>
        </div>

  <?php if ($result['notice'] !== ''): ?>
        <div class="enquiry-alert" id="enquiry-alert" role="alert" tabindex="-1"><?php echo e($result['notice']); ?></div>
  <?php endif; ?>

        <form class="enquiry-form" method="post" action="actions/contact-handler.php" novalidate>
          <?php echo csrf_field(); ?>
          <input type="hidden" name="source" value="<?php echo e($formSource); ?>">

          <!-- Honeypot: moved off-screen rather than hidden with display:none,
               which the better bots know to skip. -->
          <div class="enquiry-hp" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="enquiry-field">
                <label for="enq-name">Your name <span aria-hidden="true">*</span></label>
                <input type="text" id="enq-name" name="name" autocomplete="name" maxlength="<?php echo MAX_NAME; ?>"
                       placeholder="e.g. Priya R" value="<?php echo $oldValue('name'); ?>"
                       <?php echo $errorFor('name') !== '' ? 'aria-invalid="true" aria-describedby="err-name"' : ''; ?>>
                <?php if ($errorFor('name') !== ''): ?>
                  <p class="enquiry-error" id="err-name"><?php echo e($errorFor('name')); ?></p>
                <?php endif; ?>
              </div>
            </div>

            <div class="col-md-6">
              <div class="enquiry-field">
                <label for="enq-phone">Mobile number <span aria-hidden="true">*</span></label>
                <input type="tel" id="enq-phone" name="phone" inputmode="tel" autocomplete="tel" maxlength="18"
                       placeholder="10-digit mobile number" value="<?php echo $oldValue('phone'); ?>"
                       <?php echo $errorFor('phone') !== '' ? 'aria-invalid="true" aria-describedby="err-phone"' : ''; ?>>
                <?php if ($errorFor('phone') !== ''): ?>
                  <p class="enquiry-error" id="err-phone"><?php echo e($errorFor('phone')); ?></p>
                <?php endif; ?>
              </div>
            </div>

            <div class="col-md-6">
              <div class="enquiry-field">
                <label for="enq-email">Email <span class="enquiry-optional">(optional)</span></label>
                <input type="email" id="enq-email" name="email" autocomplete="email" maxlength="<?php echo MAX_EMAIL; ?>"
                       placeholder="you@example.com" value="<?php echo $oldValue('email'); ?>"
                       <?php echo $errorFor('email') !== '' ? 'aria-invalid="true" aria-describedby="err-email"' : ''; ?>>
                <?php if ($errorFor('email') !== ''): ?>
                  <p class="enquiry-error" id="err-email"><?php echo e($errorFor('email')); ?></p>
                <?php endif; ?>
              </div>
            </div>

            <div class="col-md-6">
              <div class="enquiry-field">
                <label for="enq-city">Area <span aria-hidden="true">*</span></label>
                <select id="enq-city" name="city"
                        <?php echo $errorFor('city') !== '' ? 'aria-invalid="true" aria-describedby="err-city"' : ''; ?>>
                  <option value="">Select your area</option>
                  <?php foreach (CITIES as $key => $label): ?>
                    <option value="<?php echo e($key); ?>"<?php echo ($old['city'] ?? '') === $key ? ' selected' : ''; ?>><?php echo e($label); ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if ($errorFor('city') !== ''): ?>
                  <p class="enquiry-error" id="err-city"><?php echo e($errorFor('city')); ?></p>
                <?php endif; ?>
              </div>
            </div>

            <div class="col-12">
              <fieldset class="enquiry-field enquiry-choices"
                        <?php echo $errorFor('services') !== '' ? 'aria-describedby="err-services"' : ''; ?>>
                <legend>What do you need? <span aria-hidden="true">*</span></legend>
                <div class="enquiry-choice-grid">
                  <?php foreach (SERVICES as $key => $label): ?>
                    <label class="enquiry-choice">
                      <input type="checkbox" name="services[]" value="<?php echo e($key); ?>"<?php echo $wasChosen($key) ? ' checked' : ''; ?>>
                      <span><?php echo e($label); ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
                <?php if ($errorFor('services') !== ''): ?>
                  <p class="enquiry-error" id="err-services"><?php echo e($errorFor('services')); ?></p>
                <?php endif; ?>
              </fieldset>
            </div>

            <div class="col-12">
              <div class="enquiry-field">
                <label for="enq-message">Message <span class="enquiry-optional">(optional)</span></label>
                <textarea id="enq-message" name="message" rows="4" maxlength="<?php echo MAX_MESSAGE; ?>"
                          placeholder="Area to be covered, floor number, preferred visit time..."><?php echo $oldValue('message'); ?></textarea>
              </div>
            </div>

            <div class="col-12">
              <div class="enquiry-field enquiry-consent">
                <label class="enquiry-choice">
                  <input type="checkbox" name="consent" value="1"<?php echo ($old['consent'] ?? '') !== '' ? ' checked' : ''; ?>>
                  <span>Yes, you may contact me about this enquiry.
                    <span class="enquiry-optional">(optional)</span></span>
                </label>
              </div>
            </div>

            <div class="col-12">
              <button type="submit" name="submit" value="1" class="btn-default btn-noarrow enquiry-submit">Send enquiry</button>
              <p class="enquiry-note">Or call us directly on
                <a href="tel:<?php echo e(SITE_PHONE_DIALABLE); ?>"><?php echo e(SITE_PHONE); ?></a>
                &mdash; free site visit across Chennai.</p>
            </div>
          </div>
        </form>

<?php endif; ?>

      </div>
    </div>
  </div>
</section>
