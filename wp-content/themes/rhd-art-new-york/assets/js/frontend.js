/**
 * Frontend JavaScript for RHD ART/New York.
 *
 * @package RHD ART/New York
 * @since RHD ART/New York 1.0
 */

/* global document */

(function () {
  "use strict";

  function bindArtnyCtaRevealHover(el) {
    el.addEventListener("mouseenter", function () {
      el.classList.add("is-active");
    });
    el.addEventListener("mouseleave", function () {
      el.classList.remove("is-active");
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    var ctaReveals = document.querySelectorAll(".artny-cta-reveal");
    ctaReveals.forEach(bindArtnyCtaRevealHover);

    /*
     * Mark the NEED SPACE / NEED HELP grid wrapper for layout-only CSS.
     * Cards are direct child `.wp-block-media-text.artny-cta-reveal`; do not
     * attach hover here — that would target nested `.wp-block-group` nodes and
     * miss `.is-active` on the reveal block (which the stylesheet expects).
     */
    var gridCandidates = document.querySelectorAll(
      "main#wp--skip-link--target .entry-content .wp-block-group.is-layout-grid"
    );

    gridCandidates.forEach(function (gridEl) {
      var raw = gridEl.textContent || "";
      var text = raw.toUpperCase();
      var looksLikeNeedSpace =
        text.indexOf("NEED SPACE") !== -1 || text.indexOf("NEED HELP") !== -1;

      var linkCount = gridEl.querySelectorAll("a").length;
      var arrowCount = raw.split("→").length - 1;
      var looksLikeCtaGrid =
        looksLikeNeedSpace || (linkCount >= 2 && arrowCount >= 1);

      if (!looksLikeCtaGrid) {
        return;
      }

      gridEl.classList.add("artny-cta-grid-links");
    });
  });
})();
