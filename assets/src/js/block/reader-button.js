import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from '../../../../blocks/reader-button/block.json';

const Edit = ( { attributes, setAttributes } ) => {
	const { buttonText } = attributes;
	const blockProps = useBlockProps( {
		className: 'wpdfv-fullscreen-container',
	} );
	const label = buttonText || __( 'Read Mode', 'wp-distraction-free-view' );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Button settings',
						'wp-distraction-free-view'
					) }
				>
					<TextControl
						__next40pxDefaultSize
						label={ __(
							'Button text',
							'wp-distraction-free-view'
						) }
						value={ buttonText }
						onChange={ ( value ) =>
							setAttributes( { buttonText: value } )
						}
						placeholder={ __(
							'Read Mode',
							'wp-distraction-free-view'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<button type="button" className="wpdfv-fullscreen-btn">
					{ label }
				</button>
			</div>
		</>
	);
};

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	save: () => null,
} );
