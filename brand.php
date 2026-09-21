<?php
/**
 * Animated brand lockup: logo mark beside the two-line wordmark.
 * Rendered in the header, the mobile drawer and the footer.
 *
 * Set before including (all optional):
 *   $brandVariant  - '' (header), 'drawer' or 'footer'
 *   $brandOnLight  - true on a light surface (the inner-page header):
 *                    navy logo + navy wordmark. Otherwise the white-house
 *                    logo and white wordmark are used, for dark surfaces.
 *   $brandLazy     - true below the fold (the footer)
 *
 * The logo files are landscape (920x820 source), so the srcset widths are
 * the real 128/256 px widths and --logo-ratio is 920 / 820.
 *
 * --logo feeds the CSS mask that shapes the shine, so it uses a root path:
 * a relative url() inside a custom property can resolve against the
 * stylesheet that reads it rather than this page.
 */

$brandVariant = $brandVariant ?? '';
$brandOnLight = $brandOnLight ?? false;
$brandLazy    = $brandLazy ?? false;

$brandFile  = $brandOnLight ? 'logo' : 'logo-light';
$brandClass = 'brand'
    . ($brandVariant !== '' ? ' brand--' . $brandVariant : '')
    . ($brandOnLight ? ' brand--on-light' : '');
?>
<a class="<?= $brandClass ?>" href="/" aria-label="Maanvika Grills &amp; Nets &mdash; home">
  <span class="brand__mark" style="--logo: url('/images/logo-256.webp'); --logo-ratio: 920 / 820">
    <img src="images/<?= $brandFile ?>-128.webp"
         srcset="images/<?= $brandFile ?>-128.webp 128w, images/<?= $brandFile ?>-256.webp 256w"
         sizes="64px" width="920" height="820" alt=""<?= $brandLazy ? ' loading="lazy" decoding="async"' : '' ?>>
  </span>
  <span class="brand__text" aria-hidden="true" data-brand-fit>
    <span class="brand__name">Maanvika</span>
    <span class="brand__sub">Grills &amp; Nets</span>
  </span>
</a>
<?php
// Reset so the next include starts from the defaults.
unset($brandVariant, $brandOnLight, $brandLazy, $brandFile, $brandClass);
