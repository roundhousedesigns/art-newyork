/**
 * Frontend JavaScript for RHD ART/New York.
 *
 * @package RHD ART/New York
 * @since RHD ART/New York 1.0
 */

(function () {
  "use strict";

  // Add hover listeners for .artny-cta-reveal to toggle .is-active

  // Wait for DOMContentLoaded to ensure the elements are present
  document.addEventListener("DOMContentLoaded", function () {
    var ctaReveals = document.querySelectorAll(".artny-cta-reveal");
    if (ctaReveals.length > 0) {
      ctaReveals.forEach(function (el) {
        el.addEventListener("mouseenter", function () {
          el.classList.add("is-active");
        });
        el.addEventListener("mouseleave", function () {
          el.classList.remove("is-active");
        });
      });
    }
  });
})();
