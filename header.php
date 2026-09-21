<?php
/**
 * Site header: the announcement marquee, the nav bar, and the drawer that
 * bar opens below 1024px. All three live here because they share the brand
 * lockup and the same open/closed state (js/function.js toggles
 * .is-menu-open on <body>).
 *
 * The bar's inline link row and the drawer's big links are deliberately the
 * same destinations, so navigation does not change meaning when the viewport
 * crosses a breakpoint. The drawer adds Home above them (the bar's logo
 * already does that job) plus Blog and Contact, which the bar has no room
 * for once the phone number and quote button are in place.
 *
 * Services is an accordion rather than a link in the drawer: it used to be a
 * hover dropdown, which a phone cannot do, and "Services" on its own is a
 * poor answer to "what do you actually fit". The panel lists the real pages.
 *
 * Areas stays a plain link to the home page's #areas strip because the
 * Chennai zones are sections there, not standalone pages - there is nothing
 * else to point an accordion at.
 */

$business = [
    'phone_display' => '95814 31299',
    'tel_href'      => 'tel:+919581431299',
    'whatsapp'      => 'https://api.whatsapp.com/send?phone=919581431299',
    'opening_hours' => 'Open 8am to 8pm, all week',
];

/* Same order as the services carousel on the home page. */
$navServices = [
    ['label' => 'Balcony Safety Nets',    'href' => 'balcony-safety-nets.php'],
    ['label' => 'Invisible Grills',       'href' => 'invisible-grill-for-balcony.php'],
    ['label' => 'Pigeon Safety Nets',     'href' => 'pigeon-safety-nets.php'],
    ['label' => 'Children Safety Nets',   'href' => 'children-safety-nets.php'],
    ['label' => 'Anti Bird Nets',         'href' => 'anti-bird-nets.php'],
    ['label' => 'Bird Nets',              'href' => 'bird-nets.php'],
    ['label' => 'Cricket Practice Nets',  'href' => 'cricket-practice-nets.php'],
    ['label' => 'All Sports Nets',        'href' => 'all-sports-nets.php'],
    ['label' => 'Shade Nets',             'href' => 'shade-nets.php'],
    ['label' => 'Balcony Cloth Hangers',  'href' => 'balcony-cloth-hanger.php'],
    ['label' => 'All Safety Nets',        'href' => 'safety-nets.php'],
];

/* Shown in the bar and, with Home / Blog / Contact added, in the drawer. */
$navLinks = [
    ['label' => 'About',    'href' => 'about.php'],
    ['label' => 'Services', 'href' => '#services'],
    ['label' => 'Gallery',  'href' => 'gallery.php'],
    ['label' => 'Areas',    'href' => 'index.php#areas'],
    ['label' => 'FAQs',     'href' => 'faqs.php'],
];

$navFile = basename(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: 'index.php');
if ($navFile === '' || $navFile === '/') { $navFile = 'index.php'; }

/** True when a nav href points at the page currently being rendered. */
$navIsCurrent = static function ($href) use ($navFile) {
    if (strpos($href, '#') !== false) { return false; }
    return basename($href) === $navFile;
};

$navServiceActive = false;
foreach ($navServices as $navSvc) {
    if ($navIsCurrent($navSvc['href'])) { $navServiceActive = true; break; }
}

$e = static function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
?>
    <!-- Lets keyboard and screen-reader users jump past the nav. -->
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <!-- Wraps marquee + bar so the home page can float the whole block over the hero -->
    <div class="site-header-wrap">

    <!-- Announcement bar: the track is duplicated so the marquee can loop
         seamlessly. The copy is aria-hidden so screen readers hear it once. -->
    <div class="topbar">
        <div class="topbar-marquee">
            <ul class="topbar-track">
                <li><i class="fa-solid fa-location-dot" aria-hidden="true"></i> We Cover Entire Chennai &ndash; Tamil Nadu</li>
                <li><i class="fa-solid fa-grip-lines-vertical" aria-hidden="true"></i> Invisible Grill Installation</li>
                <li><i class="fa-solid fa-dove" aria-hidden="true"></i> Pigeon Nets Installation</li>
                <li><i class="fa-solid fa-volleyball" aria-hidden="true"></i> Sports Nets Installation</li>
                <li><i class="fa-solid fa-globe" aria-hidden="true"></i> Free Installation &amp; Site Visit</li>
                <li><i class="fa-solid fa-shield-cat" aria-hidden="true"></i> Bird Spikes Installation</li>
                <li><i class="fa-solid fa-phone" aria-hidden="true"></i> Call <?= $e($business['phone_display']) ?></li>
            </ul>
            <ul class="topbar-track" aria-hidden="true">
                <li><i class="fa-solid fa-location-dot"></i> We Cover Entire Chennai &ndash; Tamil Nadu</li>
                <li><i class="fa-solid fa-grip-lines-vertical"></i> Invisible Grill Installation</li>
                <li><i class="fa-solid fa-dove"></i> Pigeon Nets Installation</li>
                <li><i class="fa-solid fa-volleyball"></i> Sports Nets Installation</li>
                <li><i class="fa-solid fa-globe"></i> Free Installation &amp; Site Visit</li>
                <li><i class="fa-solid fa-shield-cat"></i> Bird Spikes Installation</li>
                <li><i class="fa-solid fa-phone"></i> Call <?= $e($business['phone_display']) ?></li>
            </ul>
        </div>
    </div>

    <!-- Nav bar -->
    <header class="bar">
        <div class="container bar__inner">

            <?php /* The home header sits on a dark photo; every other page has
                     a white bar, so it gets the navy logo and wordmark. */
                  $brandOnLight = $navFile !== 'index.php';
                  include __DIR__ . '/brand.php'; ?>

            <nav class="bar__links" aria-label="Primary">
                <a href="index.php"<?= $navFile === 'index.php' ? ' aria-current="page"' : '' ?>>Home</a>

                <?php foreach ($navLinks as $link): ?>
                    <?php if ($link['label'] === 'Services'): ?>
                        <?php /* Hover-opened panel on desktop; the drawer turns the
                                 same list into a tap accordion below 1024px. */ ?>
                        <div class="bar__drop">
                            <a href="index.php#services" class="bar__drop-trigger"<?= $navServiceActive ? ' aria-current="page"' : '' ?>>
                                Services
                                <span class="bar__caret" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
                            </a>
                            <div class="bar__panel">
                                <?php foreach ($navServices as $svc): ?>
                                    <a href="<?= $e($svc['href']) ?>"<?= $navIsCurrent($svc['href']) ? ' aria-current="page"' : '' ?>><?= $e($svc['label']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= $e($link['href']) ?>"<?= $navIsCurrent($link['href']) ? ' aria-current="page"' : '' ?>><?= $e($link['label']) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <a class="bar__tel" href="<?= $e($business['tel_href']) ?>">
                <i class="fa-solid fa-phone" aria-hidden="true"></i>
                +91 <?= $e($business['phone_display']) ?>
            </a>

            <a class="btn-default btn-noarrow bar__quote" href="contact.php">Get a free quote</a>

            <?php /* The label is an aria-label rather than a hidden child span so
                     the button contains exactly the two bars, which is what lets
                     them be addressed as first and last when they cross into an X. */ ?>
            <button type="button" class="burger" id="nav-toggle"
                    aria-label="Menu" aria-expanded="false" aria-controls="nav-drawer">
                <span class="burger__bar" aria-hidden="true"></span>
                <span class="burger__bar" aria-hidden="true"></span>
            </button>

        </div>
    </header>

    </div>
    <!-- site-header-wrap end -->

<!-- Full-screen nav drawer, opened by the burger below 1024px -->
<nav class="drawer" id="nav-drawer" aria-label="Main menu">
    <div class="drawer__blob" aria-hidden="true"></div>
    <?php /* .container gives the drawer the same column as the bar, so the
             drawer's logo lands exactly where the header's logo was. */ ?>
    <div class="drawer__inner container">

        <div class="drawer__brandrow">
            <?php $brandVariant = 'drawer'; include __DIR__ . '/brand.php'; ?>
        </div>

        <p class="drawer__kicker">Menu</p>

        <?php /* Services is a <button aria-expanded> controlling the panel below
                 it, so it is a real disclosure to a screen reader. With the
                 script blocked the panel is simply open, which is why the open
                 state is the CSS default and the script closes it. */ ?>
        <div class="drawer__links">
            <a href="index.php"<?= $navFile === 'index.php' ? ' aria-current="page"' : '' ?>>Home</a>

            <?php foreach ($navLinks as $link): ?>
                <?php if ($link['label'] === 'Services'): ?>
                    <div class="drawer__group" data-nav-group>
                        <button type="button" class="drawer__toggle"
                                aria-expanded="true" aria-controls="nav-panel-services">
                            Services
                            <span class="drawer__caret" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                        </button>
                        <div class="drawer__panel" id="nav-panel-services">
                            <?php foreach ($navServices as $svc): ?>
                                <a href="<?= $e($svc['href']) ?>"<?= $navIsCurrent($svc['href']) ? ' aria-current="page"' : '' ?>><?= $e($svc['label']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= $e($link['href']) ?>"<?= $navIsCurrent($link['href']) ? ' aria-current="page"' : '' ?>><?= $e($link['label']) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <a href="blog.php"<?= $navFile === 'blog.php' ? ' aria-current="page"' : '' ?>>Blog</a>
            <a href="contact.php"<?= $navFile === 'contact.php' ? ' aria-current="page"' : '' ?>>Contact Us</a>
        </div>

        <div class="drawer__foot">
            <a class="btn-default btn-noarrow drawer__cta" href="contact.php">Get a free quote</a>
            <a class="btn-default btn-noarrow drawer__cta drawer__cta--wa"
               href="<?= $e($business['whatsapp']) ?>" target="_blank" rel="noopener noreferrer">
                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp us
            </a>
            <p class="drawer__tel">
                <a href="<?= $e($business['tel_href']) ?>">+91 <?= $e($business['phone_display']) ?></a>
                &middot; <?= $e($business['opening_hours']) ?>
            </p>
        </div>

    </div>
</nav>

     <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-KRTV43ZC');</script>
<!-- End Google Tag Manager -->

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-17258529933"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-17258529933');
</script>
<script>
  gtag('config', 'AW-17258529933/UAD3CM_cqecaEI2JwaVA', {
    'phone_conversion_number': '9581431299'
  });
</script>

<!-- Landmark target for the skip link; closed in footer.php -->
<main id="main-content" tabindex="-1">
