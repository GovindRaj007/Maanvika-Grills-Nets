<!DOCTYPE html>
<html lang="en-IN">

<head>
  <!-- Meta -->
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Mobile browser chrome -->
    <meta name="theme-color" content="#002F47">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=yes">
    <meta name="geo.region" content="IN-TN">
    <meta name="geo.placename" content="Chennai">
    <meta name="geo.position" content="13.0878;80.2101">
    <meta name="ICBM" content="13.0878, 80.2101">

    <!-- Resource hints -->
    <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    <link rel="preload" as="style" href="css/custom.css">


  <!-- Page Title -->
  <title>Gallery | Safety Net Installations across Chennai</title>

  <!-- Meta Description (150 characters) -->
  <meta name="description" content="Photos of balcony nets, pigeon nets, children safety nets and sports nets we have installed in homes and buildings around Chennai.">

  <!-- Robots Tag -->
  <meta name="robots" content="index, follow">

  <!-- Canonical URL -->
  <link rel="canonical" href="https://maanvikasafetynetschennai.com/gallery.php" />

  <!-- Keywords -->
  <meta name="keywords" content="safety net gallery Chennai, balcony nets projects, pigeon nets photos, construction safety nets, bird netting installation, Maanvika nets work samples">

  <!-- Publisher -->
  <meta name="publisher" content="Maanvika Grills &amp; Nets Chennai">

  <!-- OG Tags -->
  <meta property="og:title" content="Gallery | Safety Net Installations across Chennai">
  <meta property="og:description" content="Photos of balcony nets, pigeon nets, children safety nets and sports nets we have installed in homes and buildings around Chennai.">
  <meta property="og:url" content="https://maanvikasafetynetschennai.com/gallery.php">
  <meta property="og:type" content="website">
  <meta property="og:image" content="https://maanvikasafetynetschennai.com/images/G-1.jpg">

  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="images/favicon-192.png">
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com/">
  <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Onest:wght@100..900&display=swap" rel="stylesheet">

  <!-- CSS -->
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/swiper-bundle.min.css" rel="stylesheet">
  <link href="css/all.min.css" rel="stylesheet">
  <link href="css/animate.css" rel="stylesheet">
  <link href="css/magnific-popup.css" rel="stylesheet">
  <link href="css/mousecursor.css" rel="stylesheet">
  <link href="css/custom.css" rel="stylesheet">

  <!-- Schema Markup (Gallery Page + Business) -->
  <script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "@id": "https://maanvikasafetynetschennai.com/#business",
    "name": "Maanvika Grills & Nets Chennai",
    "alternateName": "Maanvika Grills & Nets",
    "description": "Safety net installation across Chennai: balcony nets, pigeon nets, bird nets, children safety nets, sports nets, shade nets and invisible grills. Free site visit.",
    "image": "https://maanvikasafetynetschennai.com/images/services-detail-img/bal2.jpg",
    "logo": "https://maanvikasafetynetschennai.com/images/logo-full.webp",
    "url": "https://maanvikasafetynetschennai.com/",
    "telephone": "+91-95814-31299",
    "email": "maanvikasafetysolutions@gmail.com",
    "priceRange": "₹₹",
    "currenciesAccepted": "INR",
    "paymentAccepted": "Cash, UPI, Bank Transfer",
    "address": {
        "@type": "PostalAddress",
        "streetAddress": "No A A 150, 2nd Floor, Near Hotel Vasantha Bhavan, 3rd Avenue, Anna Nagar",
        "addressLocality": "Chennai",
        "addressRegion": "Tamil Nadu",
        "postalCode": "600040",
        "addressCountry": "IN"
    },
    "geo": {
        "@type": "GeoCoordinates",
        "latitude": 13.0878,
        "longitude": 80.2101
    },
    "areaServed": [
        {
            "@type": "City",
            "name": "Chennai"
        },
        {
            "@type": "AdministrativeArea",
            "name": "Tamil Nadu"
        }
    ],
    "openingHoursSpecification": [
        {
            "@type": "OpeningHoursSpecification",
            "dayOfWeek": [
                "Monday",
                "Tuesday",
                "Wednesday",
                "Thursday",
                "Friday",
                "Saturday",
                "Sunday"
            ],
            "opens": "08:00",
            "closes": "20:00"
        }
    ],
    "sameAs": [
        "https://www.facebook.com/maanvikasafetynets",
        "https://www.instagram.com/maanvikasafetynets"
    ]
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": "https://maanvikasafetynetschennai.com/"
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": "Gallery",
            "item": "https://maanvikasafetynetschennai.com/gallery.php"
        }
    ]
}
</script>
</head>


<body>
  <?php include 'header.php' ?>

  <!-- Page Header Start -->
  <div class="page-header parallaxie">
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <!-- Page Header Box Start -->
          <div class="page-header-box">
            <h1 class="text-anime-style-2" >
            Gallery
            </h1>
          </div>
          <!-- Page Header Box End -->
        </div>
      </div>
    </div>
  </div>
  <!-- Page Header End -->

  <!-- Gallery, grouped by service. Categories come from gallery-data.php,
       the same list the service-page photo sliders use. -->
  <?php
    $galleryCats = require __DIR__ . '/gallery-data.php';
    $ge = static function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
  ?>
  <div class="page-gallery">
    <div class="container">

      <!-- Jump to a service -->
      <nav class="gallery-nav" aria-label="Gallery categories">
        <?php foreach ($galleryCats as $key => $cat): ?>
          <a href="#<?= $ge($key) ?>"><?= $ge($cat['title']) ?> <span><?= count($cat['photos']) ?></span></a>
        <?php endforeach; ?>
      </nav>

      <?php foreach ($galleryCats as $key => $cat): ?>
        <section class="gallery-cat" id="<?= $ge($key) ?>">
          <div class="gallery-cat-head">
            <div>
              <h2><?= $ge($cat['title']) ?></h2>
              <p><?= $ge($cat['intro']) ?></p>
            </div>
            <a class="gallery-cat-link" href="<?= $ge($cat['page']) ?>">
              View <?= $ge($cat['title']) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>

          <!-- Each category is its own lightbox set. -->
          <div class="gallery-grid gallery-items">
            <?php foreach ($cat['photos'] as $photo): ?>
              <a href="<?= $ge($photo['full'] ?? $photo['src']) ?>" title="<?= $ge($photo['alt']) ?>">
                <img src="<?= $ge($photo['src']) ?>" alt="<?= $ge($photo['alt']) ?>"
                     loading="lazy" decoding="async">
              </a>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>

    </div>
  </div>
  <!-- Gallery End -->

  <!-- Main Footer Start -->
  <?php include 'footer.php' ?>

  <!-- Main Footer End -->

  <!-- Jquery Library File -->
  <script src="js/jquery-3.7.1.min.js"></script>
  <!-- Bootstrap js file -->
  <script src="js/bootstrap.min.js"></script>
  <!-- Validator js file -->
  <script src="js/validator.min.js"></script>
  <!-- Swiper js file -->
  <script src="js/swiper-bundle.min.js"></script>
  <!-- Counter js file -->
  <script src="js/jquery.waypoints.min.js"></script>
  <script src="js/jquery.counterup.min.js"></script>
  <!-- Isotop js file -->
  <script src="js/isotope.min.js"></script>
  <!-- Magnific js file -->
  <script src="js/jquery.magnific-popup.min.js"></script>
  <!-- SmoothScroll -->
  <script src="js/SmoothScroll.js"></script>
  <!-- Parallax js -->
  <script src="js/parallaxie.js"></script>
  <!-- MagicCursor js file -->
  <script src="js/gsap.min.js"></script>
  <script src="js/magiccursor.js"></script>
  <!-- Text Effect js file -->
  <script src="js/SplitText.js"></script>
  <script src="js/ScrollTrigger.min.js"></script>
  <!-- YTPlayer js File -->
  <script src="js/jquery.mb.YTPlayer.min.js"></script>
  <!-- Main Custom js file -->
  <script src="js/function.js"></script>
</body>


</html>