/* eslint-env browser */
/**
 * Client-side filtering and pagination for ART/NY Directory blocks.
 */
( function () {
	'use strict';

	var ROOT_SELECTOR = '[data-rhd-artny-directory]';
	var DEFAULT_PER_PAGE = 20;

	/**
	 * @param {HTMLElement} root
	 * @returns {Array<{filter: string, attr: string, mode: string}>}
	 */
	function parseFilterConfig( root ) {
		var raw = root.getAttribute( 'data-rhd-artny-directory-filters' );

		if ( ! raw ) {
			return [
				{ filter: 'artistic_focus', attr: 'artistic-focus', mode: 'multi' },
				{ filter: 'org_focus', attr: 'org-focus', mode: 'multi' },
				{ filter: 'location', attr: 'location', mode: 'multi' },
			];
		}

		try {
			var parsed = JSON.parse( raw );
			return Array.isArray( parsed ) ? parsed : [];
		} catch ( error ) {
			return [];
		}
	}

	/**
	 * @param {HTMLElement} root
	 */
	function initDirectory( root ) {
		var form = root.querySelector( '[data-rhd-artny-directory-form]' );
		var searchInput = root.querySelector( '[data-rhd-artny-directory-search]' );
		var clearButton = root.querySelector( '[data-rhd-artny-directory-clear]' );
		var statusEl = root.querySelector( '[data-rhd-artny-directory-status]' );
		var statusFiltersEl = root.querySelector( '[data-rhd-artny-directory-status-filters]' );
		var statusCountEl = root.querySelector( '[data-rhd-artny-directory-status-count]' );
		var activeFiltersEl = root.querySelector( '[data-rhd-artny-directory-active-filters]' );
		var emptyEl = root.querySelector( '[data-rhd-artny-directory-empty]' );
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
		var filterConfig = parseFilterConfig( root );
		var entrySingular = root.getAttribute( 'data-rhd-artny-directory-entry-singular' ) || 'contact';
		var entryPlural = root.getAttribute( 'data-rhd-artny-directory-entry-plural' ) || 'contacts';
		var emptyMessage = emptyEl ? emptyEl.textContent.trim() : ( 'No ' + entryPlural + ' found.' );

		if ( ! form || ! searchInput || cards.length === 0 ) {
			return;
		}

		if ( ! perPage || perPage < 1 ) {
			perPage = DEFAULT_PER_PAGE;
		}

		var MASONRY_GAP_FALLBACK = 24; // px; used only if computed gap fails
		var masonryResizeTimer = 0;
		var masonryColumns = [];
		var masonryPark = null;

		function getMasonryGapPx() {
			if ( ! resultsEl ) {
				return MASONRY_GAP_FALLBACK;
			}
			var styles = window.getComputedStyle( resultsEl );
			var gap = parseFloat( styles.columnGap || styles.gap );
			return isNaN( gap ) ? MASONRY_GAP_FALLBACK : gap;
		}

		function getColumnCount() {
			if ( window.matchMedia( '(min-width: 1100px)' ).matches ) {
				return 3;
			}
			if ( window.matchMedia( '(min-width: 782px)' ).matches ) {
				return 2;
			}
			return 1;
		}

		function ensureMasonryStructure( columnCount ) {
			if ( ! resultsEl ) {
				return;
			}

			if ( ! masonryPark ) {
				masonryPark = resultsEl.querySelector( '[data-rhd-artny-directory-masonry-park]' );
				if ( ! masonryPark ) {
					masonryPark = document.createElement( 'div' );
					masonryPark.className = 'rhd-artny-directory__masonry-park';
					masonryPark.setAttribute( 'data-rhd-artny-directory-masonry-park', '' );
					masonryPark.setAttribute( 'hidden', '' );
					resultsEl.appendChild( masonryPark );
				}
			}

			masonryColumns = Array.prototype.slice.call(
				resultsEl.querySelectorAll( '[data-rhd-artny-directory-masonry-column]' )
			);

			while ( masonryColumns.length < columnCount ) {
				var col = document.createElement( 'div' );
				col.className = 'rhd-artny-directory__masonry-column';
				col.setAttribute( 'data-rhd-artny-directory-masonry-column', '' );
				resultsEl.insertBefore( col, masonryPark );
				masonryColumns.push( col );
			}

			while ( masonryColumns.length > columnCount ) {
				var removed = masonryColumns.pop();
				while ( removed.firstChild ) {
					masonryPark.appendChild( removed.firstChild );
				}
				removed.parentNode.removeChild( removed );
			}

			masonryColumns = Array.prototype.slice.call(
				resultsEl.querySelectorAll( '[data-rhd-artny-directory-masonry-column]' )
			);
		}

		function isCardVisible( card ) {
			return ! card.hidden && ! card.classList.contains( 'rhd-artny-directory__card--hidden' );
		}

		function layoutMasonry() {
			if ( ! resultsEl || cards.length === 0 ) {
				return;
			}

			var columnCount = getColumnCount();
			ensureMasonryStructure( columnCount );

			var gap = getMasonryGapPx();
			var heights = [];
			var i;

			for ( i = 0; i < columnCount; i++ ) {
				heights[ i ] = 0;
			}

			// Park every card first so measurements are clean, then place visibles.
			cards.forEach( function ( card ) {
				masonryPark.appendChild( card );
			} );

			var visible = cards.filter( isCardVisible );

			if ( columnCount === 1 ) {
				visible.forEach( function ( card ) {
					masonryColumns[ 0 ].appendChild( card );
				} );
			} else {
				visible.forEach( function ( card ) {
					var shortest = 0;
					for ( i = 1; i < columnCount; i++ ) {
						if ( heights[ i ] < heights[ shortest ] ) {
							shortest = i;
						}
					}
					masonryColumns[ shortest ].appendChild( card );
					heights[ shortest ] += card.offsetHeight + gap;
				} );
			}

			resultsEl.classList.add( 'is-masonry-ready' );
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
			var isSingle = dropdown.getAttribute( 'data-rhd-artny-directory-filter-mode' ) === 'single';

			if ( checked.length === 1 ) {
				nextText = checked[ 0 ].getAttribute( 'data-rhd-artny-directory-filter-label' ) || checked[ 0 ].value;
			} else if ( ! isSingle && ( checked.length === 2 || checked.length === 3 ) ) {
				nextText = Array.prototype.map.call( checked, function ( input ) {
					return input.getAttribute( 'data-rhd-artny-directory-filter-label' ) || input.value;
				} ).join( ', ' );
			} else if ( ! isSingle && checked.length > 3 ) {
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

				dropdown.querySelectorAll( '[data-rhd-artny-directory-filter]' ).forEach( function ( input ) {
					input.addEventListener( 'change', function () {
						updateDropdownSummary( dropdown );

						if ( dropdown.getAttribute( 'data-rhd-artny-directory-filter-mode' ) === 'single' && input.checked ) {
							setDropdownOpen( dropdown, false );
						}
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
					var openTrigger = dropdown.querySelector( '[data-rhd-artny-directory-filter-trigger]' );
					return openTrigger && openTrigger.getAttribute( 'aria-expanded' ) === 'true';
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
		var filterSearchTimer = 0;
		var filterAnimFallbackTimer = 0;
		var filterAnimGeneration = 0;
		var activeFiltersHighlightTimer = 0;
		var hasAppliedInitialFilters = false;
		var FILTER_SEARCH_DEBOUNCE_MS = 180;
		var FILTER_TRANSITION_FALLBACK_MS = 520;
		var ACTIVE_FILTERS_HIGHLIGHT_MS = 1400;

		/**
		 * @returns {Record<string, string|string[]>}
		 */
		function readFilters() {
			var filters = {
				q: ( searchInput.value || '' ).trim().toLowerCase(),
			};

			filterConfig.forEach( function ( config ) {
				var selected = [];
				form.querySelectorAll( '[data-rhd-artny-directory-filter="' + config.filter + '"]:checked' ).forEach( function ( el ) {
					selected.push( el.value );
				} );
				filters[ config.filter ] = selected;
			} );

			return filters;
		}

		/**
		 * @param {string} csv
		 * @param {string[]} selected
		 * @param {string} mode
		 * @returns {boolean}
		 */
		function matchesTaxonomy( csv, selected, mode ) {
			if ( ! selected.length ) {
				return true;
			}

			var values = csv ? csv.split( ',' ) : [];

			if ( mode === 'single' ) {
				return values.indexOf( selected[ 0 ] ) !== -1;
			}

			return selected.some( function ( slug ) {
				return values.indexOf( slug ) !== -1;
			} );
		}

		/**
		 * @param {HTMLElement} card
		 * @param {Record<string, string|string[]>} filters
		 * @returns {boolean}
		 */
		function cardMatches( card, filters ) {
			if ( filters.q ) {
				var blob = ( card.getAttribute( 'data-search' ) || '' ) + ' ' + ( card.getAttribute( 'data-name' ) || '' ).toLowerCase();
				if ( blob.indexOf( filters.q ) === -1 ) {
					return false;
				}
			}

			for ( var i = 0; i < filterConfig.length; i += 1 ) {
				var config = filterConfig[ i ];
				var selected = filters[ config.filter ];
				var attrName = 'data-' + config.attr;

				if ( ! Array.isArray( selected ) ) {
					continue;
				}

				if ( ! matchesTaxonomy( card.getAttribute( attrName ) || '', selected, config.mode ) ) {
					return false;
				}
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
		 * @returns {string[]}
		 */
		function getActiveFilterParts() {
			var parts = [];
			var query = ( searchInput.value || '' ).trim();

			if ( query ) {
				parts.push( 'Search: \u201C' + query + '\u201D' );
			}

			filterDropdowns.forEach( function ( dropdown ) {
				var checked = dropdown.querySelectorAll( '[data-rhd-artny-directory-filter]:checked' );
				if ( ! checked.length ) {
					return;
				}

				var groupLabelEl = dropdown.querySelector( '.rhd-artny-directory__filter-dropdown-label' );
				var groupLabel = groupLabelEl ? groupLabelEl.textContent.trim() : '';
				var values = Array.prototype.map.call( checked, function ( input ) {
					return input.getAttribute( 'data-rhd-artny-directory-filter-label' ) || input.value;
				} );

				if ( groupLabel ) {
					parts.push( groupLabel + ': ' + values.join( ', ' ) );
				} else {
					parts.push( values.join( ', ' ) );
				}
			} );

			return parts;
		}

		/**
		 * @returns {string}
		 */
		function formatActiveFiltersSummary() {
			var parts = getActiveFilterParts();
			if ( ! parts.length ) {
				return '';
			}

			return 'Filtered by ' + parts.join( ' \u00B7 ' ) + '.';
		}

		/**
		 * @param {boolean} highlight
		 */
		function updateActiveFiltersSummary( highlight ) {
			if ( ! activeFiltersEl ) {
				return;
			}

			var summary = formatActiveFiltersSummary();

			if ( ! summary ) {
				activeFiltersEl.textContent = '';
				activeFiltersEl.hidden = true;
				activeFiltersEl.classList.remove( 'is-just-updated' );
				return;
			}

			activeFiltersEl.textContent = summary;
			activeFiltersEl.hidden = false;

			if ( ! highlight || prefersReducedMotion ) {
				return;
			}

			if ( activeFiltersHighlightTimer ) {
				window.clearTimeout( activeFiltersHighlightTimer );
			}

			activeFiltersEl.classList.remove( 'is-just-updated' );
			// Force reflow so the highlight can re-run on repeated term clicks.
			void activeFiltersEl.offsetWidth;
			activeFiltersEl.classList.add( 'is-just-updated' );

			activeFiltersHighlightTimer = window.setTimeout( function () {
				activeFiltersHighlightTimer = 0;
				activeFiltersEl.classList.remove( 'is-just-updated' );
			}, ACTIVE_FILTERS_HIGHLIGHT_MS );
		}

		/**
		 * @param {number} matchedCount
		 * @param {number} page
		 * @param {number} totalPages
		 * @param {number} rangeStart
		 * @param {number} rangeEnd
		 * @param {{ announceFilters?: boolean }} [options]
		 */
		function updateStatus( matchedCount, page, totalPages, rangeStart, rangeEnd, options ) {
			var countText = '';

			if ( matchedCount === 0 ) {
				countText = emptyMessage;
			} else {
				var entryLabel = matchedCount === 1 ? entrySingular : entryPlural;

				if ( totalPages <= 1 ) {
					countText = 'Showing ' + matchedCount + ' ' + entryLabel + '.';
				} else {
					countText = 'Showing ' + rangeStart + '\u2013' + rangeEnd + ' of ' + matchedCount + ' ' + entryPlural + ' (page ' + page + ' of ' + totalPages + ').';
				}
			}

			var filterSummary = ( options && options.announceFilters ) ? formatActiveFiltersSummary() : '';

			if ( statusCountEl ) {
				statusCountEl.textContent = countText;
			} else if ( statusEl ) {
				statusEl.textContent = filterSummary ? filterSummary + ' ' + countText : countText;
				return;
			}

			if ( statusFiltersEl ) {
				statusFiltersEl.textContent = filterSummary ? filterSummary + ' ' : '';
			}
		}

		/**
		 * @param {boolean} resetPage
		 * @param {{ highlightActiveFilters?: boolean }} [options]
		 * @returns {{ matchedCount: number, totalPages: number, rangeStart: number, rangeEnd: number }}
		 */
		function applyVisibleCards( resetPage, options ) {
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

			updateActiveFiltersSummary( !!( options && options.highlightActiveFilters ) );
			updateStatus( matchedCount, currentPage, totalPages, rangeStart, rangeEnd, {
				announceFilters: !!( options && options.highlightActiveFilters ),
			} );
			updatePagination( currentPage, totalPages );

			if ( emptyEl ) {
				emptyEl.hidden = matchedCount > 0;
			}

			layoutMasonry();

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
		function clearResultsTransitionClasses( className ) {
			if ( ! resultsEl ) {
				return;
			}

			resultsEl.classList.remove(
				'is-paginating-out-next',
				'is-paginating-out-prev',
				'is-paginating-in-next',
				'is-paginating-in-prev',
				'is-filtering',
				'is-filtering-out',
				'is-filtering-in'
			);

			if ( className ) {
				resultsEl.classList.remove( className );
			}
		}

		/**
		 * @param {boolean} resetPage
		 * @param {{ highlightActiveFilters?: boolean }} [options]
		 */
		function animateFilterChange( resetPage, options ) {
			if ( ! resultsEl || prefersReducedMotion ) {
				applyVisibleCards( resetPage, options );
				return;
			}

			if ( filterAnimFallbackTimer ) {
				window.clearTimeout( filterAnimFallbackTimer );
				filterAnimFallbackTimer = 0;
			}

			filterAnimGeneration += 1;
			var generation = filterAnimGeneration;

			isAnimating = true;
			setPaginationBusy( true );

			if ( resultsViewportEl ) {
				resultsViewportEl.style.minHeight = resultsEl.offsetHeight + 'px';
			}

			function finishAnimation() {
				if ( generation !== filterAnimGeneration ) {
					return;
				}

				if ( filterAnimFallbackTimer ) {
					window.clearTimeout( filterAnimFallbackTimer );
					filterAnimFallbackTimer = 0;
				}

				clearResultsTransitionClasses();
				isAnimating = false;

				if ( resultsViewportEl ) {
					resultsViewportEl.style.minHeight = '';
				}

				setPaginationBusy( false );
				layoutMasonry();
			}

			clearResultsTransitionClasses();
			resultsEl.classList.add( 'is-filtering', 'is-filtering-out' );

			function onExitEnd( event ) {
				if ( generation !== filterAnimGeneration ) {
					resultsEl.removeEventListener( 'transitionend', onExitEnd );
					return;
				}

				if ( event.target !== resultsEl || event.propertyName !== 'opacity' ) {
					return;
				}

				resultsEl.removeEventListener( 'transitionend', onExitEnd );
				resultsEl.classList.remove( 'is-filtering-out' );
				applyVisibleCards( resetPage, options );
				resultsEl.classList.add( 'is-filtering-in' );

				window.requestAnimationFrame( function () {
					window.requestAnimationFrame( function () {
						if ( generation !== filterAnimGeneration ) {
							return;
						}

						resultsEl.classList.remove( 'is-filtering-in' );

						function onEnterEnd( enterEvent ) {
							if ( generation !== filterAnimGeneration ) {
								resultsEl.removeEventListener( 'transitionend', onEnterEnd );
								return;
							}

							if ( enterEvent.target !== resultsEl || enterEvent.propertyName !== 'opacity' ) {
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
			filterAnimFallbackTimer = window.setTimeout( finishAnimation, FILTER_TRANSITION_FALLBACK_MS );
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

				clearResultsTransitionClasses();
				isAnimating = false;

				if ( resultsViewportEl ) {
					resultsViewportEl.style.minHeight = '';
				}

				setPaginationBusy( false );
				layoutMasonry();
			}

			clearResultsTransitionClasses();
			resultsEl.classList.add( outClass );

			function onExitEnd( event ) {
				if ( event.target !== resultsEl || event.propertyName !== 'transform' ) {
					return;
				}

				resultsEl.removeEventListener( 'transitionend', onExitEnd );
				clearResultsTransitionClasses();
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
		 * @param {{ animate?: boolean, highlightActiveFilters?: boolean }} [options]
		 */
		function applyFilters( resetPage, slideDirection, options ) {
			var shouldAnimate = options && options.animate;
			var cardOptions = {
				highlightActiveFilters: !!( options && options.highlightActiveFilters ),
			};

			if ( slideDirection && ! resetPage ) {
				animatePageChange( slideDirection );
				return;
			}

			// Update the visible filter summary immediately so card-term clicks
			// feedback isn't deferred until the results fade finishes.
			updateActiveFiltersSummary( cardOptions.highlightActiveFilters );

			if ( shouldAnimate && hasAppliedInitialFilters ) {
				animateFilterChange( resetPage, cardOptions );
				return;
			}

			applyVisibleCards( resetPage, cardOptions );
			hasAppliedInitialFilters = true;
		}

		form.addEventListener( 'input', function ( event ) {
			var isSearchInput = event.target === searchInput;

			if ( isSearchInput ) {
				if ( filterSearchTimer ) {
					window.clearTimeout( filterSearchTimer );
				}

				filterSearchTimer = window.setTimeout( function () {
					filterSearchTimer = 0;
					applyFilters( true, null, { animate: true } );
				}, FILTER_SEARCH_DEBOUNCE_MS );
				return;
			}

			applyFilters( true, null, { animate: true } );
		} );
		form.addEventListener( 'change', function () {
			if ( filterSearchTimer ) {
				window.clearTimeout( filterSearchTimer );
				filterSearchTimer = 0;
			}

			applyFilters( true, null, { animate: true } );
		} );

		if ( clearButton ) {
			clearButton.addEventListener( 'click', function () {
				if ( filterSearchTimer ) {
					window.clearTimeout( filterSearchTimer );
					filterSearchTimer = 0;
				}

				searchInput.value = '';
				form.querySelectorAll( 'input[type="checkbox"], input[type="radio"]' ).forEach( function ( input ) {
					input.checked = false;
				} );
				closeAllDropdowns( null );
				filterDropdowns.forEach( updateDropdownSummary );
				applyFilters( true, null, { animate: true } );
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

			var checkbox = form.querySelector( 'input[type="checkbox"][value="' + slug + '"], input[type="radio"][value="' + slug + '"]' );
			if ( checkbox ) {
				var category = checkbox.getAttribute( 'data-rhd-artny-directory-filter' );

				if ( category ) {
					form.querySelectorAll( '[data-rhd-artny-directory-filter="' + category + '"]' ).forEach( function ( input ) {
						input.checked = input === checkbox;
					} );
				} else {
					checkbox.checked = true;
				}

				var dropdown = checkbox.closest( '[data-rhd-artny-directory-filter-dropdown]' );
				if ( dropdown ) {
					updateDropdownSummary( dropdown );
				}
				applyFilters( true, null, { animate: true, highlightActiveFilters: true } );
				return;
			}

			if ( label ) {
				searchInput.value = label;
				applyFilters( true, null, { animate: true, highlightActiveFilters: true } );
			}
		} );

		window.addEventListener( 'resize', function () {
			if ( masonryResizeTimer ) {
				window.clearTimeout( masonryResizeTimer );
			}
			masonryResizeTimer = window.setTimeout( function () {
				masonryResizeTimer = 0;
				layoutMasonry();
			}, 150 );
		} );

		if ( resultsEl ) {
			resultsEl.addEventListener(
				'toggle',
				function ( event ) {
					var target = event.target;
					if ( ! target || target.tagName !== 'DETAILS' ) {
						return;
					}
					if ( ! target.closest( '[data-rhd-artny-directory-card]' ) ) {
						return;
					}
					layoutMasonry();
				},
				true
			);
		}

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
