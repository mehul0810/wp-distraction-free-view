import '../../css/frontend/wpdfv.scss';

import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Flex,
	FlexItem,
	Modal,
	Notice,
	Spinner,
} from '@wordpress/components';
import { RawHTML, render, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { file, fullscreen } from '@wordpress/icons';

const CONTENT_PATH = '/wp-distraction-free-view/v1/content/';

const ReaderApp = () => {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ title, setTitle ] = useState( '' );
	const [ content, setContent ] = useState( '' );

	useEffect( () => {
		const handleClick = ( event ) => {
			const trigger = event.target.closest( '.wpdfv-fullscreen-btn' );

			if ( ! trigger ) {
				return;
			}

			event.preventDefault();
			openReader( trigger.dataset.postId );
		};

		document.addEventListener( 'click', handleClick );

		return () => document.removeEventListener( 'click', handleClick );
	}, [] );

	const openReader = ( postId ) => {
		if ( ! postId ) {
			return;
		}

		setIsOpen( true );
		setIsLoading( true );
		setError( '' );
		setTitle( '' );
		setContent( '' );

		apiFetch( { path: `${ CONTENT_PATH }${ postId }` } )
			.then( ( response ) => {
				setTitle( response.title );
				setContent( response.content );
			} )
			.catch( () => {
				setError(
					__(
						'This content could not be loaded in distraction free view.',
						'wp-distraction-free-view'
					)
				);
			} )
			.finally( () => setIsLoading( false ) );
	};

	const closeReader = () => {
		setIsOpen( false );
		setError( '' );
	};

	const toggleFullscreen = () => {
		const modal = document.querySelector( '.wpdfv-reader-modal' );

		if ( ! document.fullscreenElement && modal?.requestFullscreen ) {
			modal.requestFullscreen();
			return;
		}

		if ( document.fullscreenElement && document.exitFullscreen ) {
			document.exitFullscreen();
		}
	};

	const printReader = () => {
		window.print();
	};

	return (
		isOpen && (
			<Modal
				className="wpdfv-reader-modal"
				title={
					title ||
					__( 'Distraction Free View', 'wp-distraction-free-view' )
				}
				onRequestClose={ closeReader }
				shouldCloseOnClickOutside={ false }
			>
				<div className="wpdfv-reader-toolbar">
					<Flex justify="flex-end">
						<FlexItem>
							<Button
								variant="secondary"
								icon={ file }
								onClick={ printReader }
								disabled={ isLoading || ! content }
							>
								{ __( 'Print', 'wp-distraction-free-view' ) }
							</Button>
						</FlexItem>
						<FlexItem>
							<Button
								variant="secondary"
								icon={ fullscreen }
								onClick={ toggleFullscreen }
							>
								{ __(
									'Fullscreen',
									'wp-distraction-free-view'
								) }
							</Button>
						</FlexItem>
					</Flex>
				</div>

				<div className="wpdfv-reader-content" id="wpdfv-print">
					{ isLoading && (
						<div className="wpdfv-reader-loading">
							<Spinner />
						</div>
					) }

					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }

					{ ! isLoading && content && <RawHTML>{ content }</RawHTML> }
				</div>
			</Modal>
		)
	);
};

const root = document.createElement( 'div' );
root.id = 'wpdfv-reader-root';
document.body.appendChild( root );

render( <ReaderApp />, root );
