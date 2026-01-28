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
})();
