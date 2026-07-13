/* eslint-env browser */
/**
 * Register ART/NY Directory blocks in the block editor (required for WP 7+).
 */
( function ( wp ) {
	if ( ! wp || ! wp.blocks || ! wp.element || ! wp.blockEditor ) {
		return;
	}

	var el = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var ServerSideRender = wp.serverSideRender;

	/**
	 * @param {object} settings
	 * @returns {object}
	 */
	function createBlockSettings( settings ) {
		return {
			apiVersion: 3,
			category: 'widgets',
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
				var renderAttributes = {};

				if ( props.attributes.align ) {
					renderAttributes.align = props.attributes.align;
				}

				return el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: settings.blockName,
						attributes: renderAttributes,
						EmptyResponsePlaceholder: function () {
							return el(
								'p',
								null,
								settings.previewMessage
							);
						},
					} )
				);
			},
			save: function () {
				return null;
			},
		};
	}

	/**
	 * @param {object} block
	 */
	function registerDirectoryBlock( block ) {
		if ( wp.blocks.getBlockType( block.blockName ) ) {
			wp.blocks.unregisterBlockType( block.blockName );
		}

		registerBlockType(
			block.blockName,
			Object.assign( createBlockSettings( block ), {
				title: block.title,
				icon: block.icon,
				description: block.description,
				keywords: block.keywords,
			} )
		);
	}

	var blocks = [
		{
			blockName: 'rhd/artny-directory',
			title: 'ART/NY Organizations Directory',
			icon: 'groups',
			description:
				'Searchable member directory with filters for Xplor organizations.',
			keywords: [ 'directory', 'organizations', 'xplor', 'members' ],
			previewMessage: 'ART/NY Organizations Directory preview is loading…',
		},
		{
			blockName: 'rhd/artny-individuals-directory',
			title: 'ART/NY Individuals Directory',
			icon: 'admin-users',
			description:
				'Searchable individuals directory with filters for Xplor contacts.',
			keywords: [ 'directory', 'individuals', 'contacts', 'xplor' ],
			previewMessage: 'ART/NY Individuals Directory preview is loading…',
		},
	];

	blocks.forEach( registerDirectoryBlock );
} )( window.wp );
