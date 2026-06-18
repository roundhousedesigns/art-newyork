/* eslint-env browser */
/**
 * Client-side filtering and pagination for the ART/NY Directory block.
 */
( function () {
	'use strict';

	var ROOT_SELECTOR = '[data-rhd-artny-directory]';
	var DEFAULT_PER_PAGE = 20;

	/**
	 * @param {HTMLElement} root
	 */
	function initDirectory( root ) {
		var form = root.querySelector( '[data-rhd-artny-directory-form]' );
		var searchInput = root.querySelector( '[data-rhd-artny-directory-search]' );
		var clearButton = root.querySelector( '[data-rhd-artny-directory-clear]' );
		var statusEl = root.querySelector( '[data-rhd-artny-directory-status]' );
		var emptyEl = root.querySelector( '[data-rhd-artny-directory-empty]' );
		var emptyMessage = emptyEl ? emptyEl.textContent.trim() : 'No contacts match your filters. Try adjusting your search or clearing filters.';
		var paginationEl = root.querySelector( '[data-rhd-artny-directory-pagination]' );
		var pageStatusEl = root.querySelector( '[data-rhd-artny-directory-page-status]' );
		var prevButton = root.querySelector( '[data-rhd-artny-directory-prev]' );
		var nextButton = root.querySelector( '[data-rhd-artny-directory-next]' );
		var resultsEl = root.querySelector( '[data-rhd-artny-directory-results]' );
		var resultsViewportEl = root.querySelector( '[data-rhd-artny-directory-results-viewport]' );
		var cards = Array.prototype.slice.call(
			root.querySelectorAll( '[data-rhd-artny-directory-card]' )
		);
		var perPage = parseInt( root.getAttribute( 'data-rhd-artny-directory-per-page' ), 10 );
		var prefersReducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		if ( ! form || ! searchInput || cards.length === 0 ) {
			return;
		}

		if ( ! perPage || perPage < 1 ) {
			perPage = DEFAULT_PER_PAGE;
		}

		var filterDropdowns = Array.prototype.slice.call(
			form.querySelectorAll( '[data-rhd-artny-directory-filter-dropdown]' )
		);

		/**
		 * @param {HTMLElement} dropdown
		 */
		function updateDropdownSummary( dropdown ) {
			var summaryEl = dropdown.querySelector( '[data-rhd-artny-directory-filter-summary]' );
			if ( ! summaryEl ) {
				return;
			}

			var defaultLabel = summaryEl.getAttribute( 'data-default-label' ) || '';
			var checked = dropdown.querySelectorAll( '[data-rhd-artny-directory-filter]:checked' );
			var nextText = defaultLabel;

			if ( checked.length === 1 ) {
				nextText = checked[ 0 ].getAttribute( 'data-rhd-artny-directory-filter-label' ) || checked[ 0 ].value;
			} else if ( checked.length === 2 || checked.length === 3 ) {
				nextText = Array.prototype.map.call( checked, function ( input ) {
					return input.getAttribute( 'data-rhd-artny-directory-filter-label' ) || input.value;
				} ).join( ', ' );
			} else if ( checked.length > 3 ) {
				nextText = checked.length + ' selected';
			}

			summaryEl.textContent = nextText;
		}

		/**
		 * @param {HTMLElement} dropdown
		 * @param {boolean} open
		 */
		function setDropdownOpen( dropdown, open ) {
			var trigger = dropdown.querySelector( '[data-rhd-artny-directory-filter-trigger]' );
			var panel = dropdown.querySelector( '[data-rhd-artny-directory-filter-panel]' );

			if ( ! trigger || ! panel ) {
				return;
			}

			trigger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			panel.hidden = ! open;
		}

		/**
		 * @param {HTMLElement|null} except
		 */
		function closeAllDropdowns( except ) {
			filterDropdowns.forEach( function ( dropdown ) {
				if ( except && dropdown === except ) {
					return;
				}

				setDropdownOpen( dropdown, false );
			} );
		}

		/**
		 */
		function initFilterDropdowns() {
			if ( ! filterDropdowns.length ) {
				return;
			}

			filterDropdowns.forEach( function ( dropdown ) {
				var trigger = dropdown.querySelector( '[data-rhd-artny-directory-filter-trigger]' );
				var panel = dropdown.querySelector( '[data-rhd-artny-directory-filter-panel]' );

				if ( ! trigger || ! panel ) {
					return;
				}

				trigger.addEventListener( 'click', function ( event ) {
					event.stopPropagation();
					var isOpen = trigger.getAttribute( 'aria-expanded' ) === 'true';

					if ( isOpen ) {
						setDropdownOpen( dropdown, false );
						return;
					}

					closeAllDropdowns( dropdown );
					setDropdownOpen( dropdown, true );
				} );

				panel.addEventListener( 'keydown', function ( event ) {
					if ( event.key !== 'Escape' ) {
						return;
					}

					event.preventDefault();
					setDropdownOpen( dropdown, false );
					trigger.focus();
				} );

				dropdown.querySelectorAll( '[data-rhd-artny-directory-filter]' ).forEach( function ( checkbox ) {
					checkbox.addEventListener( 'change', function () {
						updateDropdownSummary( dropdown );
					} );
				} );

				updateDropdownSummary( dropdown );
			} );

			document.addEventListener( 'click', function ( event ) {
				if ( event.target.closest( '[data-rhd-artny-directory-filter-dropdown]' ) ) {
					return;
				}

				closeAllDropdowns( null );
			} );

			document.addEventListener( 'keydown', function ( event ) {
				if ( event.key !== 'Escape' ) {
					return;
				}

				var openDropdown = filterDropdowns.find( function ( dropdown ) {
					var trigger = dropdown.querySelector( '[data-rhd-artny-directory-filter-trigger]' );
					return trigger && trigger.getAttribute( 'aria-expanded' ) === 'true';
				} );

				if ( ! openDropdown ) {
					return;
				}

				var openTrigger = openDropdown.querySelector( '[data-rhd-artny-directory-filter-trigger]' );
				setDropdownOpen( openDropdown, false );

				if ( openTrigger ) {
					openTrigger.focus();
				}
			} );
		}

		initFilterDropdowns();

		var currentPage = 1;
		var isAnimating = false;

		/**
		 * @returns {{ q: string, artistic_focus: string[], org_focus: string[], location: string[] }}
		 */
		function readFilters() {
			var artistic = [];
			var org = [];
			var location = [];

			form.querySelectorAll( '[data-rhd-artny-directory-filter="artistic_focus"]:checked' ).forEach( function ( el ) {
				artistic.push( el.value );
			} );
			form.querySelectorAll( '[data-rhd-artny-directory-filter="org_focus"]:checked' ).forEach( function ( el ) {
				org.push( el.value );
			} );
			form.querySelectorAll( '[data-rhd-artny-directory-filter="location"]:checked' ).forEach( function ( el ) {
				location.push( el.value );
			} );

			return {
				q: ( searchInput.value || '' ).trim().toLowerCase(),
				artistic_focus: artistic,
				org_focus: org,
				location: location,
			};
		}

		/**
		 * @param {string} csv
		 * @param {string[]} selected
		 * @returns {boolean}
		 */
		function matchesTaxonomy( csv, selected ) {
			if ( ! selected.length ) {
				return true;
			}

			var values = csv ? csv.split( ',' ) : [];
			return selected.some( function ( slug ) {
				return values.indexOf( slug ) !== -1;
			} );
		}

		/**
		 * @param {HTMLElement} card
		 * @param {{ q: string, artistic_focus: string[], org_focus: string[], location: string[] }} filters
		 * @returns {boolean}
		 */
		function cardMatches( card, filters ) {
			if ( filters.q ) {
				var blob = ( card.getAttribute( 'data-search' ) || '' ) + ' ' + ( card.getAttribute( 'data-name' ) || '' ).toLowerCase();
				if ( blob.indexOf( filters.q ) === -1 ) {
					return false;
				}
			}

			if ( ! matchesTaxonomy( card.getAttribute( 'data-artistic-focus' ) || '', filters.artistic_focus ) ) {
				return false;
			}

			if ( ! matchesTaxonomy( card.getAttribute( 'data-org-focus' ) || '', filters.org_focus ) ) {
				return false;
			}

			if ( ! matchesTaxonomy( card.getAttribute( 'data-location' ) || '', filters.location ) ) {
				return false;
			}

			return true;
		}

		/**
		 * @param {boolean} disabled
		 */
		function setPaginationBusy( disabled ) {
			if ( prevButton ) {
				prevButton.disabled = disabled || currentPage <= 1;
			}

			if ( nextButton ) {
				var filters = readFilters();
				var matchedCount = cards.filter( function ( card ) {
					return cardMatches( card, filters );
				} ).length;
				var totalPages = Math.max( 1, Math.ceil( matchedCount / perPage ) );
				nextButton.disabled = disabled || currentPage >= totalPages;
			}
		}

		/**
		 * @param {number} page
		 * @param {number} totalPages
		 */
		function updatePagination( page, totalPages ) {
			if ( ! paginationEl ) {
				return;
			}

			if ( totalPages <= 1 ) {
				paginationEl.hidden = true;
				return;
			}

			paginationEl.hidden = false;

			if ( pageStatusEl ) {
				pageStatusEl.textContent = 'Page ' + page + ' of ' + totalPages;
			}

			if ( ! isAnimating ) {
				setPaginationBusy( false );
			}
		}

		/**
		 * @param {number} matchedCount
		 * @param {number} page
		 * @param {number} totalPages
		 * @param {number} rangeStart
		 * @param {number} rangeEnd
		 */
		function updateStatus( matchedCount, page, totalPages, rangeStart, rangeEnd ) {
			if ( ! statusEl ) {
				return;
			}

			if ( matchedCount === 0 ) {
				statusEl.textContent = emptyMessage;
				return;
			}

			if ( totalPages <= 1 ) {
				statusEl.textContent = 'Showing all ' + matchedCount + ' contact' + ( matchedCount === 1 ? '' : 's' ) + '.';
				return;
			}

			statusEl.textContent = 'Showing ' + rangeStart + '\u2013' + rangeEnd + ' of ' + matchedCount + ' contacts (page ' + page + ' of ' + totalPages + ').';
		}

		/**
		 * @param {boolean} resetPage
		 * @returns {{ matchedCount: number, totalPages: number, rangeStart: number, rangeEnd: number }}
		 */
		function applyVisibleCards( resetPage ) {
			if ( resetPage ) {
				currentPage = 1;
			}

			var filters = readFilters();
			var matched = cards.filter( function ( card ) {
				return cardMatches( card, filters );
			} );
			var matchedCount = matched.length;
			var totalPages = Math.max( 1, Math.ceil( matchedCount / perPage ) );

			if ( currentPage > totalPages ) {
				currentPage = totalPages;
			}

			var start = ( currentPage - 1 ) * perPage;
			var end = start + perPage;
			var visibleOnPage = 0;
			var visibleCards = new Set( matched.slice( start, end ) );

			cards.forEach( function ( card ) {
				var show = visibleCards.has( card );

				card.classList.toggle( 'rhd-artny-directory__card--hidden', ! show );
				card.hidden = ! show;

				if ( show ) {
					visibleOnPage += 1;
				}
			} );

			var rangeStart = matchedCount === 0 ? 0 : start + 1;
			var rangeEnd = matchedCount === 0 ? 0 : start + visibleOnPage;

			updateStatus( matchedCount, currentPage, totalPages, rangeStart, rangeEnd );
			updatePagination( currentPage, totalPages );

			if ( emptyEl ) {
				emptyEl.hidden = matchedCount > 0;
			}

			return {
				matchedCount: matchedCount,
				totalPages: totalPages,
				rangeStart: rangeStart,
				rangeEnd: rangeEnd,
			};
		}

		/**
		 * @param {string} className
		 */
		function clearPaginationClasses( className ) {
			if ( ! resultsEl ) {
				return;
			}

			resultsEl.classList.remove(
				'is-paginating-out-next',
				'is-paginating-out-prev',
				'is-paginating-in-next',
				'is-paginating-in-prev'
			);

			if ( className ) {
				resultsEl.classList.remove( className );
			}
		}

		/**
		 * @param {'next'|'prev'} direction
		 */
		function animatePageChange( direction ) {
			if ( ! resultsEl || prefersReducedMotion ) {
				applyVisibleCards( false );
				return;
			}

			isAnimating = true;
			setPaginationBusy( true );

			var outClass = direction === 'next' ? 'is-paginating-out-next' : 'is-paginating-out-prev';
			var inClass = direction === 'next' ? 'is-paginating-in-next' : 'is-paginating-in-prev';
			var fallbackTimer = 0;

			if ( resultsViewportEl ) {
				resultsViewportEl.style.minHeight = resultsEl.offsetHeight + 'px';
			}

			function finishAnimation() {
				if ( fallbackTimer ) {
					window.clearTimeout( fallbackTimer );
					fallbackTimer = 0;
				}

				clearPaginationClasses();
				isAnimating = false;

				if ( resultsViewportEl ) {
					resultsViewportEl.style.minHeight = '';
				}

				setPaginationBusy( false );
			}

			clearPaginationClasses();
			resultsEl.classList.add( outClass );

			function onExitEnd( event ) {
				if ( event.target !== resultsEl || event.propertyName !== 'transform' ) {
					return;
				}

				resultsEl.removeEventListener( 'transitionend', onExitEnd );
				clearPaginationClasses();
				applyVisibleCards( false );
				resultsEl.classList.add( inClass );

				window.requestAnimationFrame( function () {
					window.requestAnimationFrame( function () {
						resultsEl.classList.remove( inClass );

						function onEnterEnd( enterEvent ) {
							if ( enterEvent.target !== resultsEl || enterEvent.propertyName !== 'transform' ) {
								return;
							}

							resultsEl.removeEventListener( 'transitionend', onEnterEnd );
							finishAnimation();
						}

						resultsEl.addEventListener( 'transitionend', onEnterEnd );
					} );
				} );
			}

			resultsEl.addEventListener( 'transitionend', onExitEnd );
			fallbackTimer = window.setTimeout( finishAnimation, 900 );
		}

		/**
		 * @param {boolean} resetPage
		 * @param {'next'|'prev'|null} slideDirection
		 */
		function applyFilters( resetPage, slideDirection ) {
			if ( slideDirection && ! resetPage ) {
				animatePageChange( slideDirection );
				return;
			}

			applyVisibleCards( resetPage );
		}

		form.addEventListener( 'input', function () {
			applyFilters( true );
		} );
		form.addEventListener( 'change', function () {
			applyFilters( true );
		} );

		if ( clearButton ) {
			clearButton.addEventListener( 'click', function () {
				searchInput.value = '';
				form.querySelectorAll( 'input[type="checkbox"]' ).forEach( function ( checkbox ) {
					checkbox.checked = false;
				} );
				closeAllDropdowns( null );
				filterDropdowns.forEach( updateDropdownSummary );
				applyFilters( true );
				searchInput.focus();
			} );
		}

		if ( prevButton ) {
			prevButton.addEventListener( 'click', function () {
				if ( isAnimating || currentPage <= 1 ) {
					return;
				}

				currentPage -= 1;
				applyFilters( false, 'prev' );
			} );
		}

		if ( nextButton ) {
			nextButton.addEventListener( 'click', function () {
				if ( isAnimating ) {
					return;
				}

				var filters = readFilters();
				var matchedCount = cards.filter( function ( card ) {
					return cardMatches( card, filters );
				} ).length;
				var totalPages = Math.max( 1, Math.ceil( matchedCount / perPage ) );

				if ( currentPage >= totalPages ) {
					return;
				}

				currentPage += 1;
				applyFilters( false, 'next' );
			} );
		}

		root.addEventListener( 'click', function ( event ) {
			var termLink = event.target.closest( '[data-rhd-artny-directory-term]' );
			if ( ! termLink || ! root.contains( termLink ) ) {
				return;
			}

			event.preventDefault();

			var slug = termLink.getAttribute( 'data-rhd-artny-directory-term' );
			var label = termLink.getAttribute( 'data-rhd-artny-directory-term-label' ) || '';
			if ( ! slug ) {
				return;
			}

			var checkbox = form.querySelector( 'input[type="checkbox"][value="' + slug + '"]' );
			if ( checkbox ) {
				checkbox.checked = true;
				var dropdown = checkbox.closest( '[data-rhd-artny-directory-filter-dropdown]' );
				if ( dropdown ) {
					updateDropdownSummary( dropdown );
				}
				applyFilters( true );
				return;
			}

			if ( label ) {
				searchInput.value = label;
				applyFilters( true );
			}
		} );

		applyFilters( true );
	}

	function init() {
		document.querySelectorAll( ROOT_SELECTOR ).forEach( initDirectory );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
