/**
 * Frontend JavaScript for RHD ART/New York.
 *
 * @package RHD ART/New York
 * @since RHD ART/New York 1.0
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    // CTA Reveal pattern: add hover listeners for .artny-cta-reveal to toggle .is-active.
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

    // CTA Grid links: selector structure changed in WP blocks, so we detect the
    // intended grid container and enhance hover/focus behavior without relying
    // on generated wp-container-* class names.
    var gridCandidates = document.querySelectorAll(
      "main#wp--skip-link--target .entry-content .wp-block-group.is-layout-grid"
    );

    if (gridCandidates.length > 0) {
      gridCandidates.forEach(function (gridEl) {
        // Heuristic: only enhance the specific CTA grid (e.g. "NEED SPACE?" / "NEED HELP?").
        var text = (gridEl.textContent || "").toUpperCase();
        var looksLikeNeedSpace =
          text.indexOf("NEED SPACE") !== -1 || text.indexOf("NEED HELP") !== -1;

        // If the copy changes, fall back to "grid with 2+ links and arrows".
        var linkCount = gridEl.querySelectorAll("a").length;
        var arrowCount = (gridEl.textContent || "").split("→").length - 1;
        var looksLikeCtaGrid =
          looksLikeNeedSpace || (linkCount >= 2 && arrowCount >= 1);

        if (!looksLikeCtaGrid) return;

        gridEl.classList.add("artny-cta-grid-links");

        // Prefer direct child groups as interactive cards; otherwise fall back to any group children.
        var cards = gridEl.querySelectorAll(":scope > .wp-block-group");
        if (cards.length === 0) {
          cards = gridEl.querySelectorAll(".wp-block-group");
        }

        cards.forEach(function (cardEl) {
          cardEl.addEventListener("pointerenter", function () {
            cardEl.classList.add("is-active");
          });
          cardEl.addEventListener("pointerleave", function () {
            cardEl.classList.remove("is-active");
          });
          cardEl.addEventListener("focusin", function () {
            cardEl.classList.add("is-active");
          });
          cardEl.addEventListener("focusout", function () {
            cardEl.classList.remove("is-active");
          });
        });
      });
    }
  });
})();
