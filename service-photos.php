<?php
/**
 * "Recent installations" photo slider for a service page.
 *
 * Set before including:
 *   $servicePhotoKeys  - one or more category keys from gallery-data.php
 *   $servicePhotoSkip  - optional: photo srcs to leave out on this page
 *   $servicePhotoAdd   - optional: photo srcs from any category to append
 *
 * Skip/Add tailor one page's slider without changing the gallery, which
 * reads the same catalogue.
 *
 * The first key decides where "View all photos" lands in the gallery.
 * Photos swipe sideways (Swiper, set up in js/function.js) and open in the
 * lightbox; the last slide links through to the full gallery.
 */

$servicePhotoKeys = $servicePhotoKeys ?? [];
$servicePhotoSkip = $servicePhotoSkip ?? [];
$servicePhotoAdd  = $servicePhotoAdd ?? [];
$serviceCatalogue = require __DIR__ . '/gallery-data.php';

$servicePhotos = [];
foreach ($servicePhotoKeys as $key) {
    if (!isset($serviceCatalogue[$key])) { continue; }
    foreach ($serviceCatalogue[$key]['photos'] as $photo) {
        if (!in_array($photo['src'], $servicePhotoSkip, true)) { $servicePhotos[] = $photo; }
    }
}
foreach ($servicePhotoAdd as $src) {
    foreach ($serviceCatalogue as $cat) {
        foreach ($cat['photos'] as $photo) {
            if ($photo['src'] === $src) { $servicePhotos[] = $photo; continue 3; }
        }
    }
}
if (!$servicePhotos) { return; }

$serviceGalleryAnchor = 'gallery.php#' . $servicePhotoKeys[0];
$sp = static function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
?>
<div class="service-photos">
  <div class="service-photos-head">
    <h3>Recent installations</h3>
    <span class="service-photos-hint" aria-hidden="true">
      Swipe <i class="fa-solid fa-arrow-right-long"></i>
    </span>
  </div>

  <div class="photo-slider">
    <div class="swiper">
      <div class="swiper-wrapper">
        <?php foreach ($servicePhotos as $photo): ?>
          <div class="swiper-slide">
            <a class="photo-slide js-lightbox" href="<?= $sp($photo['full'] ?? $photo['src']) ?>" title="<?= $sp($photo['alt']) ?>">
              <img src="<?= $sp($photo['src']) ?>" alt="<?= $sp($photo['alt']) ?>"
                   loading="lazy" decoding="async" draggable="false">
            </a>
          </div>
        <?php endforeach; ?>

        <div class="swiper-slide">
          <a class="photo-slide photo-slide--all" href="<?= $sp($serviceGalleryAnchor) ?>">
            <span class="photo-slide-all-icon"><i class="fa-solid fa-images" aria-hidden="true"></i></span>
            <strong>View all photos</strong>
            <small>See every project in our gallery</small>
            <span class="photo-slide-all-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
          </a>
        </div>
      </div>
    </div>
    <div class="photo-slider-pagination"></div>
  </div>
</div>
