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
import {
	createElement,
	RawHTML,
	render,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Path, SVG } from '@wordpress/primitives';

const CONTENT_PATH = '/wp-distraction-free-view/v1/content/';
const printIcon = createElement(
	SVG,
	{
		xmlns: 'http://www.w3.org/2000/svg',
		viewBox: '0 0 24 24',
	},
	createElement( Path, {
		clipRule: 'evenodd',
		d: 'M12.848 8a1 1 0 0 1-.914-.594l-.723-1.63a.5.5 0 0 0-.447-.276H5a.5.5 0 0 0-.5.5v11.5a.5.5 0 0 0 .5.5h14a.5.5 0 0 0 .5-.5v-9A.5.5 0 0 0 19 8h-6.152Zm.612-1.5a.5.5 0 0 1-.462-.31l-.445-1.084A2 2 0 0 0 10.763 4H5a2 2 0 0 0-2 2v11.5a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-5.54Z',
		fillRule: 'evenodd',
	} )
);
const fullscreenIcon = createElement(
	SVG,
	{
		xmlns: 'http://www.w3.org/2000/svg',
		viewBox: '0 0 24 24',
	},
	createElement( Path, {
		d: 'M6 4a2 2 0 0 0-2 2v3h1.5V6a.5.5 0 0 1 .5-.5h3V4H6Zm3 14.5H6a.5.5 0 0 1-.5-.5v-3H4v3a2 2 0 0 0 2 2h3v-1.5Zm6 1.5v-1.5h3a.5.5 0 0 0 .5-.5v-3H20v3a2 2 0 0 1-2 2h-3Zm3-16a2 2 0 0 1 2 2v3h-1.5V6a.5.5 0 0 0-.5-.5h-3V4h3Z',
	} )
);

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
								icon={ printIcon }
								onClick={ printReader }
								disabled={ isLoading || ! content }
							>
								{ __( 'Print', 'wp-distraction-free-view' ) }
							</Button>
						</FlexItem>
						<FlexItem>
							<Button
								variant="secondary"
								icon={ fullscreenIcon }
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
