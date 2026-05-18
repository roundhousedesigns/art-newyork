/**
 * Frontend JavaScript for RHD ART/New York.
 *
 * @package RHD ART/New York
 * @since RHD ART/New York 1.0
 */

/* global document, rhdArtNewYork, MutationObserver */

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

  /**
   * Accessible name for the card link from heading lines in content.
   *
   * @param {Element} el CTA reveal block.
   * @return {string}
   */
  function getArtnyCtaRevealLabel(el) {
    var content = el.querySelector(".wp-block-media-text__content");
    if (!content) {
      return "";
    }

    var titleGroup = content.querySelector(
      ".wp-block-group.is-layout-constrained, .wp-block-group.has-global-padding"
    );
    if (titleGroup) {
      var parts = [];
      titleGroup.querySelectorAll("p").forEach(function (paragraph) {
        var text = (paragraph.textContent || "").trim();
        if (text) {
          parts.push(text);
        }
      });
      if (parts.length) {
        return parts.join(" ");
      }
    }

    return (content.textContent || "").replace(/\s+/g, " ").trim().slice(0, 120);
  }

  /**
   * @param {HTMLAnchorElement} mediaLink
   */
  function unwrapArtnyCtaRevealMediaLink(mediaLink) {
    var img = mediaLink.querySelector("img");
    if (img) {
      mediaLink.replaceWith(img);
      return;
    }
    mediaLink.remove();
  }

  /**
   * @param {Element} el
   * @param {string} href
   * @param {HTMLAnchorElement} mediaLink
   * @return {HTMLAnchorElement}
   */
  function createArtnyCtaRevealCardLink(el, href, mediaLink) {
    var cardLink = document.createElement("a");
    cardLink.className = "artny-cta-reveal__link";
    cardLink.href = href;

    if (mediaLink.target) {
      cardLink.target = mediaLink.target;
    }
    if (mediaLink.rel) {
      cardLink.rel = mediaLink.rel;
    }

    var label = getArtnyCtaRevealLabel(el);
    if (label) {
      cardLink.setAttribute("aria-label", label);
    }

    return cardLink;
  }

  /**
   * Move the media-column link to wrap the full card (image + content).
   *
   * @param {Element} el CTA reveal block.
   */
  function expandArtnyCtaRevealLink(el) {
    if (el.querySelector(":scope > .artny-cta-reveal__link")) {
      return;
    }

    var mediaLink = el.querySelector(".wp-block-media-text__media > a[href]");
    if (!mediaLink || !mediaLink.getAttribute("href")) {
      return;
    }

    if (
      el.querySelector(".wp-block-media-text__content a[href]")
    ) {
      return;
    }

    var cardLink = createArtnyCtaRevealCardLink(
      el,
      mediaLink.getAttribute("href"),
      mediaLink
    );
    unwrapArtnyCtaRevealMediaLink(mediaLink);

    while (el.firstChild) {
      cardLink.appendChild(el.firstChild);
    }
    el.appendChild(cardLink);
  }

  /**
   * @param {SVGElement} svg
   * @param {string} src
   * @param {string} modifier BEM-style suffix for the img class.
   */
  function replaceSuperblockSliderArrowSvg(svg, src, modifier) {
    var icon = svg.parentElement;
    if (!icon || icon.querySelector("img.artny-slider-arrow")) {
      return;
    }

    var img = document.createElement("img");
    img.className = "artny-slider-arrow artny-slider-arrow--" + modifier;
    img.src = src;
    img.alt = "";
    img.setAttribute("aria-hidden", "true");
    img.setAttribute("decoding", "async");
    svg.replaceWith(img);

    var slider = icon.closest(".superblockslider");
    if (slider) {
      slider.classList.add("artny-slider-arrows-ready");
    }
  }

  function replaceSuperblockSliderArrows() {
    var config = typeof rhdArtNewYork === "object" ? rhdArtNewYork : null;
    if (!config || !config.sliderArrowLeft || !config.sliderArrowRight) {
      return;
    }

    document
      .querySelectorAll(
        ".superblockslider__button__previous--icon > svg"
      )
      .forEach(function (svg) {
        replaceSuperblockSliderArrowSvg(
          svg,
          config.sliderArrowLeft,
          "previous"
        );
      });

    document
      .querySelectorAll(".superblockslider__button__next--icon > svg")
      .forEach(function (svg) {
        replaceSuperblockSliderArrowSvg(svg, config.sliderArrowRight, "next");
      });
  }

  function observeSuperblockSliderArrows() {
    if (
      typeof MutationObserver === "undefined" ||
      !document.body
    ) {
      return;
    }

    var observer = new MutationObserver(function () {
      replaceSuperblockSliderArrows();
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    var ctaReveals = document.querySelectorAll(".artny-cta-reveal");
    ctaReveals.forEach(function (el) {
      expandArtnyCtaRevealLink(el);
      bindArtnyCtaRevealHover(el);
    });

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

    replaceSuperblockSliderArrows();
    observeSuperblockSliderArrows();
  });
})();
