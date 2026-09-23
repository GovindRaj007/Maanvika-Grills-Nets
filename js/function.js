(function ($) {
    "use strict";
	
	var $window = $(window); 
	var $body = $('body'); 

	/* Preloader Effect */
	$window.on('load', function(){
		$(".preloader").fadeOut(600);
	});

	/* Brand wordmark: make "Maanvika" exactly as wide as "Grills & Nets".

	   Widths are measured with a Range over each line's text, not the
	   element box. letter-spacing is also added after the last letter, which
	   would push that line's right edge out, so it is subtracted. The name's
	   font-size is scaled by subWidth / nameWidth; a second pass absorbs the
	   small drift from kerning at the new size. The CSS font sizes remain the
	   fallback if this never runs. */
	(function () {
		var boxes = document.querySelectorAll('[data-brand-fit]');
		if (!boxes.length) { return; }

		function inkWidth(el) {
			var range = document.createRange();
			range.selectNodeContents(el);
			var width = range.getBoundingClientRect().width;
			var spacing = parseFloat(getComputedStyle(el).letterSpacing) || 0;
			return width - spacing;
		}

		function fitAll() {
			Array.prototype.forEach.call(boxes, function (box) {
				var name = box.querySelector('.brand__name');
				var sub = box.querySelector('.brand__sub');
				if (!name || !sub) { return; }

				name.style.fontSize = '';
				for (var pass = 0; pass < 2; pass++) {
					var nameWidth = inkWidth(name);
					var subWidth = inkWidth(sub);
					/* Zero when the lockup is not laid out (the drawer is
					   display:none on desktop); leave the CSS size alone. */
					if (!nameWidth || !subWidth) { return; }
					var size = parseFloat(getComputedStyle(name).fontSize);
					name.style.fontSize = (size * subWidth / nameWidth) + 'px';
				}
			});
		}

		fitAll();
		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(fitAll);
		}

		var resizeTimer;
		window.addEventListener('resize', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(fitAll, 150);
		});
	})();

	/* Nav drawer.

	   The burger toggles .is-menu-open on <body>; CSS does the rest. The
	   drawer markup is always present, so with this script blocked the
	   panels below are simply open and every link is still reachable. */
	(function () {
		var toggle = document.getElementById('nav-toggle');
		var drawer = document.getElementById('nav-drawer');
		if (!toggle || !drawer) { return; }

		var body = document.body;

		function setOpen(open) {
			body.classList.toggle('is-menu-open', open);
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		}

		toggle.addEventListener('click', function () {
			setOpen(!body.classList.contains('is-menu-open'));
		});

		/* Following a link should close the drawer, including the in-page
		   ones where no navigation event fires. */
		drawer.addEventListener('click', function (e) {
			if (e.target.closest('a')) { setOpen(false); }
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && body.classList.contains('is-menu-open')) {
				setOpen(false);
				toggle.focus();
			}
		});

		/* Crossing up into the desktop layout retires the drawer. */
		window.addEventListener('resize', function () {
			if (window.innerWidth >= 1024) { setOpen(false); }
		});

		/* Accordions: open is the CSS default for the no-JS case, so close
		   them here and let taps take over. One panel open at a time. */
		var groups = drawer.querySelectorAll('[data-nav-group]');
		Array.prototype.forEach.call(groups, function (group) {
			var btn = group.querySelector('.drawer__toggle');
			var panel = group.querySelector('.drawer__panel');
			if (!btn || !panel) { return; }

			panel.hidden = true;
			btn.setAttribute('aria-expanded', 'false');

			btn.addEventListener('click', function () {
				var willOpen = panel.hidden;

				Array.prototype.forEach.call(groups, function (other) {
					var oBtn = other.querySelector('.drawer__toggle');
					var oPanel = other.querySelector('.drawer__panel');
					if (oPanel) { oPanel.hidden = true; }
					if (oBtn) { oBtn.setAttribute('aria-expanded', 'false'); }
				});

				if (willOpen) {
					panel.hidden = false;
					btn.setAttribute('aria-expanded', 'true');
				}
			});
		});
	})();

	if($("a[href='#top']").length){
		$(document).on("click", "a[href='#top']", function() {
			$("html, body").animate({ scrollTop: 0 }, "slow");
			return false;
		});
	}

	/* Hero Slider Layout JS */
	if ($('.hero-slider-layout .swiper').length) {
	const hero_slider_layout = new Swiper('.hero-slider-layout .swiper', {
		effect: 'fade',
		slidesPerView : 1,
		speed: 1000,
		spaceBetween: 0,
		loop: true,
		autoplay: {
			delay: 4000,
		},
		pagination: {
			el: '.hero-pagination',
			clickable: true,
		},
	});
	}

	/* Sticky header on the home page.

	   The header starts transparent, sitting on the hero image. Once roughly
	   half the hero has scrolled past, it detaches into a solid white bar
	   fixed to the top. Because it is absolutely positioned to begin with it
	   is already out of flow, so switching to fixed causes no content jump. */
	if ($('.hm-home .site-header-wrap').length && $('.hm-hero').length) {
		var $wrap = $('.hm-home .site-header-wrap');
		var $hero = $('.hm-hero');
		var stuck = false;
		var trigger = 0;

		function measureTrigger() {
			/* Fire the moment the hero panel has scrolled fully out of view.
			   The hero is one viewport tall, so this is the point where the
			   first screenful is gone and there is no longer a photo behind
			   the header for the gradient to sit on. */
			trigger = $hero.outerHeight();
		}

		function onScroll() {
			var shouldStick = $window.scrollTop() > trigger;
			if (shouldStick !== stuck) {
				stuck = shouldStick;
				$wrap.toggleClass('is-stuck', stuck);
			}
		}

		measureTrigger();
		onScroll();

		$window.on('scroll', onScroll);
		$window.on('resize', function () {
			measureTrigger();
			onScroll();
		});
		$window.on('load', function () {
			measureTrigger();
			onScroll();
		});
	}

	/* Home hero slider: one slide per service, fading between them. */
	if ($('.hm-hero-slider .swiper').length) {
		new Swiper('.hm-hero-slider .swiper', {
			effect: 'fade',
			fadeEffect: { crossFade: true },
			slidesPerView: 1,
			speed: 900,
			spaceBetween: 0,
			/* rewind rather than loop: loop mode clones slides into the DOM,
			   which would duplicate the page's only <h1>. rewind just jumps
			   back to the first slide at the end. */
			rewind: true,
			autoHeight: false,
			autoplay: {
				delay: 5500,
				disableOnInteraction: false,
			},
			pagination: {
				el: '.hm-hero-pagination',
				clickable: true,
			},
			a11y: {
				prevSlideMessage: 'Previous service',
				nextSlideMessage: 'Next service',
			},
		});
	}

	/* Sideways card carousels: the home services row and the service-page
	   photo rows share this setup so they all move the same way.

	   - Touch and mouse drag work out of the box (grabCursor shows it).
	   - mousewheel lets a trackpad or wheel scroll the row on desktop.
	     forceToAxis means only a *horizontal* gesture moves it, so scrolling
	     down over the cards still scrolls the page; releaseOnEdges hands the
	     gesture back to the page once the row reaches either end.
	   - slidesPerView is fractional so the next card peeks in and people can
	     tell there is more to see. */
	function cardCarousel(selector, pagination, breakpoints, label) {
		if (!$(selector).length) { return; }
		new Swiper(selector, {
			slidesPerView: breakpoints[0],
			spaceBetween: 16,
			speed: 600,
			grabCursor: true,
			watchOverflow: true,
			mousewheel: { forceToAxis: true, releaseOnEdges: true },
			keyboard: { enabled: true, onlyInViewport: true },
			pagination: { el: pagination, clickable: true },
			breakpoints: breakpoints[1],
			a11y: {
				prevSlideMessage: 'Previous ' + label,
				nextSlideMessage: 'Next ' + label,
			},
		});
	}

	cardCarousel('.hm-services-slider .swiper', '.hm-services-pagination', [1.15, {
		576: { slidesPerView: 1.8,  spaceBetween: 18 },
		768: { slidesPerView: 2.3,  spaceBetween: 20 },
		992: { slidesPerView: 3.2,  spaceBetween: 24 },
		1200:{ slidesPerView: 3.6,  spaceBetween: 24 },
	}], 'service');

	/* Service pages sit in an 8-column content area, hence fewer per view. */
	cardCarousel('.photo-slider .swiper', '.photo-slider-pagination', [1.3, {
		576: { slidesPerView: 1.8,  spaceBetween: 16 },
		992: { slidesPerView: 2.3,  spaceBetween: 18 },
	}], 'photo');

	/* Photos in those rows open in the lightbox; the "View all photos" card
	   is a normal link, so only .js-lightbox anchors are delegated. */
	if ($('.photo-slider').length) {
		$('.photo-slider').magnificPopup({
			delegate: 'a.js-lightbox',
			type: 'image',
			gallery: { enabled: true },
			mainClass: 'mfp-fade',
			image: { titleSrc: 'title' },
		});
	}

	/* testimonial Slider JS */
	if ($('.testimonial-slider').length) {
		const testimonial_slider = new Swiper('.testimonial-slider .swiper', {
			slidesPerView : 1,
			speed: 1000,
			spaceBetween: 30,
			loop: true,
			autoplay: {
				delay: 5000,
			},
			pagination: {
				el: '.testimonial-pagination',
				clickable: true,
			},
			navigation: {
				nextEl: '.testimonial-btn-next',
				prevEl: '.testimonial-btn-prev',
			},
			breakpoints: {
				768:{
					slidesPerView: 2,
				},
				991:{
					slidesPerView: 3,
				}
			}
		});
	}

	/* Services Slider JS */
	if ($('.services-slider').length) {
		const services_slider = new Swiper('.services-slider .swiper', {
			slidesPerView : 1,
			speed: 1000,
			spaceBetween: 30,
			loop: true,
			autoplay: {
				delay: 5000,
			},
			pagination: {
				el: '.services-pagination',
				clickable: true,
			},
			breakpoints: {
				768:{
					slidesPerView: 2,
				},
				991:{
					slidesPerView: 4,
				}
			}
		});
	}

	/* Skill Bar */
	if ($('.skills-progress-bar').length) {
		$('.skills-progress-bar').waypoint(function() {
			$('.skillbar').each(function() {
				$(this).find('.count-bar').animate({
				width:$(this).attr('data-percent')
				},2000);
			});
		},{
			offset: '50%'
		});
	}

	/* Youtube Background Video JS */
	if ($('#herovideo').length) {
		var myPlayer = $("#herovideo").YTPlayer();
	}

	/* Init Counter */
	if ($('.counter').length) {
		$('.counter').counterUp({ delay: 6, time: 3000 });
	}

	/* Image Reveal Animation */
	if ($('.reveal').length) {
        gsap.registerPlugin(ScrollTrigger);
        let revealContainers = document.querySelectorAll(".reveal");
        revealContainers.forEach((container) => {
            let image = container.querySelector("img");
            let tl = gsap.timeline({
                scrollTrigger: {
                    trigger: container,
                    toggleActions: "play none none none"
                }
            });
            tl.set(container, {
                autoAlpha: 1
            });
            tl.from(container, 1, {
                xPercent: -100,
                ease: "power2.out"
            });
            tl.from(image, 1, {
                xPercent: 100,
                scale: 1,
                delay: -1,
                ease: "power2.out"
            });
        });
    }

	/* Text Effect Animation */
	if ($('.text-anime-style-1').length) {
		let staggerAmount 	= 0.05,
			translateXValue = 0,
			delayValue 		= 0.5,
		   animatedTextElements = document.querySelectorAll('.text-anime-style-1');
		
		animatedTextElements.forEach((element) => {
			let animationSplitText = new SplitText(element, { type: "chars, words" });
				gsap.from(animationSplitText.words, {
				duration: 1,
				delay: delayValue,
				x: 20,
				autoAlpha: 0,
				stagger: staggerAmount,
				scrollTrigger: { trigger: element, start: "top 85%" },
				});
		});		
	}
	
	if ($('.text-anime-style-2').length) {				
		let	 staggerAmount 		= 0.03,
			 translateXValue	= 20,
			 delayValue 		= 0.1,
			 easeType 			= "power2.out",
			 animatedTextElements = document.querySelectorAll('.text-anime-style-2');
		
		animatedTextElements.forEach((element) => {
			let animationSplitText = new SplitText(element, { type: "chars, words" });
				gsap.from(animationSplitText.chars, {
					duration: 1,
					delay: delayValue,
					x: translateXValue,
					autoAlpha: 0,
					stagger: staggerAmount,
					ease: easeType,
					scrollTrigger: { trigger: element, start: "top 85%"},
				});
		});		
	}
	
	if ($('.text-anime-style-3').length) {		
		let	animatedTextElements = document.querySelectorAll('.text-anime-style-3');
		
		 animatedTextElements.forEach((element) => {
			//Reset if needed
			if (element.animation) {
				element.animation.progress(1).kill();
				element.split.revert();
			}

			element.split = new SplitText(element, {
				type: "lines,words,chars",
				linesClass: "split-line",
			});
			gsap.set(element, { perspective: 400 });

			gsap.set(element.split.chars, {
				opacity: 0,
				x: "50",
			});

			element.animation = gsap.to(element.split.chars, {
				scrollTrigger: { trigger: element,	start: "top 90%" },
				x: "0",
				y: "0",
				rotateX: "0",
				opacity: 1,
				duration: 1,
				ease: "back.out(1.7)",
				stagger: 0.02,
			});
		});		
	}

	/* Parallaxie js */
	/* var $parallaxie = $('.parallaxie');
	if($parallaxie.length && ($window.width() > 991))
	{
		if ($window.width() > 768) {
			$parallaxie.parallaxie({
				speed: 0.55,
				offset: 0,
			});
		}
	} */

	/* Gallery lightbox. Each .gallery-items block is its own set - one per
	   service on the gallery page - so the arrows stay within that service
	   instead of running on into the next category's photos. */
	$('.gallery-items').each(function () {
		$(this).magnificPopup({
			delegate: 'a',
			type: 'image',
			closeOnContentClick: false,
			closeBtnInside: false,
			mainClass: 'mfp-with-zoom',
			image: {
				verticalFit: true,
				titleSrc: 'title',
			},
			gallery: {
				enabled: true
			},
			zoom: {
				enabled: true,
				duration: 300, // keep in step with the duration in the CSS
				opener: function (element) {
					return element.find('img');
				}
			}
		});
	});

	/* Enquiry form.

	   It posts straight to form-to-email-contact.php, which validates server
	   side and redirects to thank-you.php, so there is no AJAX handler here.
	   Two small things are worth doing on the page itself:

	   1. Lock the button once a valid form is on its way. On a slow mobile
	      connection an impatient second tap sends the same lead twice.
	   2. Put the cursor on the error message after a rejected submission, so
	      a screen reader announces it and everyone else lands beside it. */
	(function () {
		var form = document.querySelector('.enquiry-form');
		if (form) {
			form.addEventListener('submit', function () {
				/* checkValidity() is false when the browser is about to block
				   the submit and show its own bubble; the button must stay
				   usable in that case. */
				if (form.checkValidity && !form.checkValidity()) { return; }

				var button = form.querySelector('.enquiry-submit');
				if (!button) { return; }

				/* Disabling in the submit handler would drop the button's own
				   name/value from the POST, so wait a tick. */
				window.setTimeout(function () {
					button.disabled = true;
					button.textContent = 'Sending...';
				}, 0);
			});
		}

		/* Coming back with the Back button restores the page from the cache
		   exactly as it was left, dead "Sending..." button and all. */
		window.addEventListener('pageshow', function () {
			var button = document.querySelector('.enquiry-submit');
			if (button && button.disabled) {
				button.disabled = false;
				button.textContent = 'Send enquiry';
			}
		});

		var alertBox = document.getElementById('enquiry-alert');
		if (alertBox) { alertBox.focus(); }
	})();


	/* Our Project (filtering) Start */
	$window.on( "load", function(){
		if( $(".project-item-boxes").length ) {
				
			/* Init Isotope */
			var $menuitem = $(".project-item-boxes").isotope({
				itemSelector: ".project-item-box",
				layoutMode: "masonry",
				masonry: {
					// use outer width of grid-sizer for columnWidth
					columnWidth: 1,
				}
			});
				
			/* Filter items on click */
			var $menudisesnav = $(".our-project-nav li a");
				$menudisesnav.on('click', function (e) { 
			
				var filterValue = $(this).attr('data-filter');
				$menuitem.isotope({
					filter: filterValue
				}); 
				
				$menudisesnav.removeClass("active-btn"); 
				$(this).addClass("active-btn");
				e.preventDefault();
			});		
			$menuitem.isotope({ filter: "*" });
		}			
	});
	/* Our Project (filtering) End */

	/* Scroll reveals.

	   This replaces wow.js. WOW decided whether a box was on screen with its
	   own arithmetic - it summed offsetTop up the offsetParent chain and
	   compared that against window.pageYOffset. On this page that maths came
	   out short, so sections below the enquiry form stayed at
	   visibility:hidden on the way down and only appeared when you scrolled
	   back up into the range it believed they occupied.

	   IntersectionObserver asks the browser whether the element is actually
	   on screen, so there is no position maths to go stale when images load
	   or the mobile address bar changes the viewport height.

	   The elements keep their existing .wow / .fadeInUp / data-wow-delay
	   markup; adding .animated is what starts the animate.css keyframes. */
	(function () {
		var boxes = document.querySelectorAll('.wow');
		if (!boxes.length) { return; }

		/* No observer, or motion is unwelcome: show everything, animate
		   nothing. Content visibility must never depend on the animation. */
		if (!('IntersectionObserver' in window) ||
			window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			return;
		}

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) { return; }
				var el = entry.target;
				var delay = el.getAttribute('data-wow-delay');
				if (delay) { el.style.animationDelay = delay; }
				el.style.visibility = 'visible';
				el.classList.add('animated');
				observer.unobserve(el);
			});
		}, {
			/* Start just before the element reaches the fold. */
			rootMargin: '0px 0px -5% 0px',
			threshold: 0
		});

		Array.prototype.forEach.call(boxes, function (el) {
			el.style.visibility = 'hidden';
			observer.observe(el);
		});
	})();

	/* Popup Video */
	if ($('.popup-video').length) {
		$('.popup-video').magnificPopup({
			type: 'iframe',
			mainClass: 'mfp-fade',
			removalDelay: 160,
			preloader: false,
			fixedContentPos: true
		});
	}
	
})(jQuery);