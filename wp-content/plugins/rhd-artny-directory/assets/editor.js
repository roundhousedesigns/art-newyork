/* eslint-env browser */
/**
 * Register the ART/NY Directory block in the block editor (required for WP 7+).
 */
( function ( wp ) {
	if ( ! wp || ! wp.blocks || ! wp.element || ! wp.blockEditor ) {
		return;
	}

	var el = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var ServerSideRender = wp.serverSideRender;

	var blockSettings = {
		apiVersion: 3,
		title: 'ART/NY Directory',
		category: 'widgets',
		icon: 'groups',
		description:
			'Searchable member directory with filters for Perfectmind/Xplor contacts.',
		keywords: [ 'directory', 'contacts', 'perfectmind', 'members' ],
		supports: {
			html: false,
			align: [ 'wide', 'full' ],
		},
		attributes: {
			align: {
				type: 'string',
			},
		},
		edit: function ( props ) {
			var blockProps = useBlockProps();
			var renderBlock = props.name;

			return el(
				'div',
				blockProps,
				el( ServerSideRender, {
					block: renderBlock,
					attributes: props.attributes,
					EmptyResponsePlaceholder: function () {
						return el(
							'p',
							null,
							'ART/NY Directory preview is loading…'
						);
					},
				} )
			);
		},
		save: function () {
			return null;
		},
	};

	[ 'rhd/artny-directory', 'rhd/contacts-directory' ].forEach( function (
		blockName
	) {
		if ( wp.blocks.getBlockType( blockName ) ) {
			return;
		}

		registerBlockType( blockName, blockSettings );
	} );
} )( window.wp );
