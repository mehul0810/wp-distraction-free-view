import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, TextControl } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import metadata from '../../../../blocks/reader-button/block.json';

const Edit = ( { attributes, setAttributes } ) => {
	const { buttonText } = attributes;
	const blockProps = useBlockProps( {
		className: 'wpdfv-fullscreen-container',
	} );
	const label =
		buttonText || __( 'Read in Reader Mode', 'wp-distraction-free-view' );

	return createElement(
		Fragment,
		null,
		createElement(
			InspectorControls,
			null,
			createElement(
				PanelBody,
				{
					title: __(
						'Reader Mode toggle',
						'wp-distraction-free-view'
					),
				},
				createElement( TextControl, {
					__next40pxDefaultSize: true,
					label: __( 'Button text', 'wp-distraction-free-view' ),
					value: buttonText,
					onChange: ( value ) =>
						setAttributes( { buttonText: value } ),
					placeholder: __(
						'Read in Reader Mode',
						'wp-distraction-free-view'
					),
				} )
			)
		),
		createElement(
			'div',
			blockProps,
			createElement(
				'button',
				{
					type: 'button',
					className: 'wpdfv-fullscreen-btn wpdfv-reader-toggle',
				},
				label
			)
		)
	);
};

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	save: () => null,
} );
