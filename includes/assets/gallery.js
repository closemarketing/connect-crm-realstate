/**
 * Property Gallery Carousel Script
 *
 * @package    Connect CRM Real State
 * @author     David Perez
 * @copyright  2023 Closemarketing
 */

(function() {
	'use strict';

	// Wait for DOM to be ready.
	document.addEventListener('DOMContentLoaded', function() {
		const galleries = document.querySelectorAll('.ccrmre-property-gallery');

		galleries.forEach(function(gallery) {
			initGallery(gallery);
		});
	});

	/**
	 * Initialize a gallery instance
	 *
	 * @param {Element} gallery Gallery container element
	 */
	function initGallery(gallery) {
		const slides         = gallery.querySelectorAll('.ccrmre-gallery-slide');
		const thumbnails     = gallery.querySelectorAll('.ccrmre-gallery-thumb');
		const prevBtn        = gallery.querySelector('.ccrmre-gallery-prev');
		const nextBtn        = gallery.querySelector('.ccrmre-gallery-next');
		const currentCounter = gallery.querySelector('.ccrmre-gallery-current');
		let currentIndex     = 0;

		if (slides.length === 0) {
			return;
		}

		/**
		 * Show slide at specified index
		 *
		 * @param {number} index Slide index
		 */
		function showSlide(index) {
			// Wrap around.
			if (index >= slides.length) {
				index = 0;
			} else if (index < 0) {
				index = slides.length - 1;
			}

			// Remove active class from all.
			slides.forEach(function(slide) {
				slide.classList.remove('active');
			});
			thumbnails.forEach(function(thumb) {
				thumb.classList.remove('active');
			});

			// Add active class to current.
			slides[index].classList.add('active');
			thumbnails[index].classList.add('active');

			// Update counter.
			currentCounter.textContent = index + 1;

			// Scroll thumbnail into view.
			thumbnails[index].scrollIntoView({
				behavior: 'smooth',
				block: 'nearest',
				inline: 'center'
			});

			currentIndex = index;
		}

		/**
		 * Go to next slide
		 */
		function nextSlide() {
			showSlide(currentIndex + 1);
		}

		/**
		 * Go to previous slide
		 */
		function prevSlide() {
			showSlide(currentIndex - 1);
		}

		// Event listeners for buttons.
		prevBtn.addEventListener('click', prevSlide);
		nextBtn.addEventListener('click', nextSlide);

		// Event listeners for thumbnails.
		thumbnails.forEach(function(thumb, index) {
			thumb.addEventListener('click', function() {
				showSlide(index);
			});
		});

		// Lightbox: open on main slide image click.
		slides.forEach(function(slide, index) {
			var img = slide.querySelector('img');
			if (img) {
				img.addEventListener('click', function() {
					var urls = Array.prototype.map.call(slides, function(s) {
						var im = s.querySelector('img');
						return im ? im.src : '';
					}).filter(Boolean);
					openLightbox(urls, index);
				});
				slide.style.cursor = 'pointer';
			}
		});

		// Keyboard navigation.
		document.addEventListener('keydown', function(e) {
			// Only if gallery is in viewport.
			const rect = gallery.getBoundingClientRect();
			const inViewport = (
				rect.top >= 0 &&
				rect.left >= 0 &&
				rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
				rect.right <= (window.innerWidth || document.documentElement.clientWidth)
			);

			if (!inViewport) {
				return;
			}

			if (e.key === 'ArrowLeft') {
				prevSlide();
				e.preventDefault();
			} else if (e.key === 'ArrowRight') {
				nextSlide();
				e.preventDefault();
			}
		});

		// Touch/swipe support.
		let touchStartX = 0;
		let touchEndX   = 0;

		gallery.addEventListener('touchstart', function(e) {
			touchStartX = e.changedTouches[0].screenX;
		}, { passive: true });

		gallery.addEventListener('touchend', function(e) {
			touchEndX = e.changedTouches[0].screenX;
			handleSwipe();
		}, { passive: true });

		/**
		 * Handle swipe gesture
		 */
		function handleSwipe() {
			const swipeThreshold = 50;
			const diff = touchStartX - touchEndX;

			if (Math.abs(diff) < swipeThreshold) {
				return;
			}

			if (diff > 0) {
				// Swipe left - next slide.
				nextSlide();
			} else {
				// Swipe right - previous slide.
				prevSlide();
			}
		}

		// Auto-advance (optional, can be enabled).
		// Uncomment to enable auto-advance every 5 seconds.
		/*
		let autoAdvanceInterval = setInterval(nextSlide, 5000);

		// Pause on hover.
		gallery.addEventListener('mouseenter', function() {
			clearInterval(autoAdvanceInterval);
		});

		gallery.addEventListener('mouseleave', function() {
			autoAdvanceInterval = setInterval(nextSlide, 5000);
		});
		*/
	}

	/**
	 * Lightbox: open overlay with image and prev/next/close.
	 *
	 * @param {string[]} urls  Array of image URLs.
	 * @param {number}   start Start index.
	 */
	function openLightbox(urls, start) {
		if (!urls.length) return;

		var index = start;
		if (index >= urls.length) index = 0;
		if (index < 0) index = urls.length - 1;

		var overlay = document.createElement('div');
		overlay.className = 'ccrmre-lightbox';
		overlay.setAttribute('role', 'dialog');
		overlay.setAttribute('aria-label', 'Image gallery lightbox');

		var img = document.createElement('img');
		img.src = urls[index];
		img.alt = '';

		var prevBtn = document.createElement('button');
		prevBtn.className = 'ccrmre-lightbox-prev';
		prevBtn.setAttribute('type', 'button');
		prevBtn.setAttribute('aria-label', 'Previous image');
		prevBtn.innerHTML = '&lsaquo;';

		var nextBtn = document.createElement('button');
		nextBtn.className = 'ccrmre-lightbox-next';
		nextBtn.setAttribute('type', 'button');
		nextBtn.setAttribute('aria-label', 'Next image');
		nextBtn.innerHTML = '&rsaquo;';

		var closeBtn = document.createElement('button');
		closeBtn.className = 'ccrmre-lightbox-close';
		closeBtn.setAttribute('type', 'button');
		closeBtn.setAttribute('aria-label', 'Close');
		closeBtn.innerHTML = '&times;';

		var counter = document.createElement('div');
		counter.className = 'ccrmre-lightbox-counter';
		counter.textContent = (index + 1) + ' / ' + urls.length;

		var inner = document.createElement('div');
		inner.className = 'ccrmre-lightbox-inner';
		inner.appendChild(prevBtn);
		inner.appendChild(img);
		inner.appendChild(nextBtn);
		inner.appendChild(closeBtn);
		inner.appendChild(counter);

		overlay.appendChild(inner);

		function setImage(i) {
			if (i >= urls.length) i = 0;
			if (i < 0) i = urls.length - 1;
			index = i;
			img.src = urls[index];
			counter.textContent = (index + 1) + ' / ' + urls.length;
		}

		function close() {
			overlay.classList.remove('ccrmre-lightbox-open');
			document.removeEventListener('keydown', onKeydown);
			document.body.style.overflow = '';
			setTimeout(function() {
				if (overlay.parentNode) {
					document.body.removeChild(overlay);
				}
			}, 300);
		}

		function onKeydown(e) {
			if (e.key === 'Escape') {
				close();
				e.preventDefault();
			} else if (e.key === 'ArrowLeft') {
				setImage(index - 1);
				e.preventDefault();
			} else if (e.key === 'ArrowRight') {
				setImage(index + 1);
				e.preventDefault();
			}
		}

		prevBtn.addEventListener('click', function(e) {
			e.stopPropagation();
			setImage(index - 1);
		});
		nextBtn.addEventListener('click', function(e) {
			e.stopPropagation();
			setImage(index + 1);
		});
		closeBtn.addEventListener('click', function(e) {
			e.stopPropagation();
			close();
		});
		overlay.addEventListener('click', function(e) {
			if (e.target === overlay) close();
		});
		inner.addEventListener('click', function(e) {
			e.stopPropagation();
		});

		document.addEventListener('keydown', onKeydown);
		document.body.appendChild(overlay);
		document.body.style.overflow = 'hidden';
		setTimeout(function() {
			overlay.classList.add('ccrmre-lightbox-open');
		}, 10);
	}

})();
