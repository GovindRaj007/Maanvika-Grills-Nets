<?php
/**
 * Photo catalogue, grouped by service.
 *
 * One list feeds both the categorised gallery (gallery.php) and the photo
 * sliders on the service pages (service-photos.php), so a new photo only
 * ever needs adding here.
 *
 * Each photo:
 *   src  - the file shown on the page (a lighter copy where one exists)
 *   full - the file the lightbox opens; defaults to src
 *   alt  - what the photo actually shows
 *
 * `page` is the service page each category's "View ..." link points to.
 */

return [

    'invisible-grills' => [
        'title' => 'Invisible Grills',
        'page'  => 'invisible-grill-for-balcony.php',
        'intro' => 'Stainless steel cable grills that keep balconies safe without blocking the view.',
        'photos' => [
            ['src' => 'images/invisible-grills-1-800.webp', 'full' => 'images/invisible-grills-1.webp',
             'alt' => 'Invisible grill cables across a balcony with a glass railing and trees beyond'],
            ['src' => 'images/invisible-grills-2.webp',
             'alt' => 'Invisible grill fitted over a balcony with a black metal railing'],
            ['src' => 'images/invisible-grills-3.webp',
             'alt' => 'Invisible grill on a balcony at night, houses across the street still visible'],
        ],
    ],

    'balcony-safety-nets' => [
        'title' => 'Balcony Safety Nets',
        'page'  => 'balcony-safety-nets.php',
        'intro' => 'Near-invisible netting that closes open balconies on apartments and high-rises.',
        'photos' => [
            ['src' => 'images/slider/2.webp',
             'alt' => 'Balcony safety net fitted to a Chennai apartment balcony'],
            ['src' => 'images/services-detail-img/bal2.webp',
             'alt' => 'High-rise balcony safety net with a wide city view'],
            ['src' => 'images/G-1.webp',
             'alt' => 'Balcony safety net above a wrought-iron railing on a high-rise'],
            ['src' => 'images/safety-nets-3.webp',
             'alt' => 'Safety net enclosing a high-rise balcony above a white railing'],
            ['src' => 'images/G-10.webp',
             'alt' => 'White knotted balcony net with neighbouring houses behind'],
        ],
    ],

    'children-safety-nets' => [
        'title' => 'Children Safety Nets',
        'page'  => 'children-safety-nets.php',
        'intro' => 'Heavy knotted netting tensioned to keep children safe on higher floors.',
        'photos' => [
            ['src' => 'images/services-detail-img/child2.webp',
             'alt' => 'Child standing at a balcony protected by green safety netting'],
            ['src' => 'images/safety-nets-2.webp',
             'alt' => 'Heavy knotted safety net running the length of a high-rise balcony'],
            ['src' => 'images/G-3.webp',
             'alt' => 'Green knotted safety net fitted along an apartment block'],
        ],
    ],

    'pigeon-safety-nets' => [
        'title' => 'Pigeon Safety Nets',
        'page'  => 'pigeon-safety-nets.php',
        'intro' => 'Sealed netting that stops pigeons nesting on balconies, windows and ducts.',
        'photos' => [
            ['src' => 'images/pigeon-nets-1.webp',
             'alt' => 'Pigeon stopped by a transparent balcony net'],
            ['src' => 'images/pigeon-nets-2.webp',
             'alt' => 'Pigeon above a net sealing an open terrace duct'],
            ['src' => 'images/pigeon-nets-3.webp',
             'alt' => 'Pigeon nets sealing open shafts on two apartment buildings'],
            ['src' => 'images/G-4.webp',
             'alt' => 'Green pigeon net behind a window grill, pigeons kept outside'],
        ],
    ],

    'anti-bird-nets' => [
        'title' => 'Anti Bird Nets & Bird Spikes',
        'page'  => 'anti-bird-nets.php',
        'intro' => 'Nets for shafts and ducts, and spikes for ledges, to keep birds off the building.',
        'photos' => [
            ['src' => 'images/safety-nets-1.webp',
             'alt' => 'Balcony bird net keeping a pigeon outside'],
            ['src' => 'images/G-5.webp',
             'alt' => 'Net covering an open stairwell shaft on an apartment building'],
            ['src' => 'images/G-6.webp',
             'alt' => 'Green net closing off an open shaft between apartment floors'],
            ['src' => 'images/services-detail-img/bid2.webp',
             'alt' => 'White bird net across a window with rooftops behind'],
            ['src' => 'images/services-detail-img/anti2.webp',
             'alt' => 'Bird turned away by a black anti bird net over fruit trees'],
            ['src' => 'images/services-detail-img/sp2.webp',
             'alt' => 'Bird spikes stopping a pigeon from landing on a parapet'],
            ['src' => 'images/G-8.webp',
             'alt' => 'Stainless steel bird spikes on clear polycarbonate bases'],
        ],
    ],

    'cricket-practice-nets' => [
        'title' => 'Cricket Practice Nets',
        'page'  => 'cricket-practice-nets.php',
        'intro' => 'Practice lanes and cages for terraces, schools, clubs and academies.',
        'photos' => [
            ['src' => 'images/sports-nets-1.webp',
             'alt' => 'Netted cricket practice lane with a green steel frame'],
            ['src' => 'images/sports-nets-2.webp',
             'alt' => 'Players batting inside a floodlit cricket net'],
            ['src' => 'images/sports-nets-3.webp',
             'alt' => 'Bowling and batting practice in outdoor cricket nets'],
            ['src' => 'images/G-2.webp',
             'alt' => 'Rooftop cricket practice cage on an apartment terrace'],
            ['src' => 'images/services-detail-img/cri2.webp',
             'alt' => 'Freestanding cricket practice net on a lawn'],
        ],
    ],

    'all-sports-nets' => [
        'title' => 'Sports Nets',
        'page'  => 'all-sports-nets.php',
        'intro' => 'Enclosures that keep the ball in play on turfs, courts and rooftops.',
        'photos' => [
            ['src' => 'images/G-7.webp',
             'alt' => 'Football turf enclosed by side nets under a shade net roof'],
            ['src' => 'images/G-9.webp',
             'alt' => 'Green overhead sports net above a play area'],
        ],
    ],

    'balcony-cloth-hanger' => [
        'title' => 'Balcony Cloth Hangers',
        'page'  => 'balcony-cloth-hanger.php',
        'intro' => 'Ceiling-mounted drying hangers that free up balcony floor space.',
        'photos' => [
            ['src' => 'images/hangers.webp',
             'alt' => 'Ceiling-mounted cloth drying hanger on an apartment balcony'],
        ],
    ],

];
