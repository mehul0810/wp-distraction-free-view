import '../../css/frontend/wpdfv.scss';

import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	ButtonGroup,
	Modal,
	Notice,
	Spinner,
} from '@wordpress/components';
import {
	createElement,
	RawHTML,
	render,
	useEffect,
	useMemo,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Path, SVG } from '@wordpress/primitives';

const CONTENT_PATH = '/wp-distraction-free-view/v1/content/';
const READER_CONFIG = window.wpdfvReaderMode || {};
const DEFAULT_STORAGE_KEY = 'wpdfv_reader_preferences';
const PREFERENCE_OPTIONS = {
	fontSize: [
		{ label: __( 'Small', 'wp-distraction-free-view' ), value: 'small' },
		{
			label: __( 'Default', 'wp-distraction-free-view' ),
			value: 'default',
		},
		{ label: __( 'Large', 'wp-distraction-free-view' ), value: 'large' },
	],
	theme: [
		{ label: __( 'Light', 'wp-distraction-free-view' ), value: 'light' },
		{ label: __( 'Dark', 'wp-distraction-free-view' ), value: 'dark' },
		{ label: __( 'Sepia', 'wp-distraction-free-view' ), value: 'sepia' },
	],
	width: [
		{ label: __( 'Narrow', 'wp-distraction-free-view' ), value: 'narrow' },
		{
			label: __( 'Default', 'wp-distraction-free-view' ),
			value: 'default',
		},
		{ label: __( 'Wide', 'wp-distraction-free-view' ), value: 'wide' },
	],
};
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

const isEnabled = ( key ) => READER_CONFIG[ key ] !== false;

const normalizePreference = ( type, value, fallback ) => {
	const allowed = PREFERENCE_OPTIONS[ type ].map(
		( option ) => option.value
	);

	return allowed.includes( value ) ? value : fallback;
};

const getDefaultPreferences = () => ( {
	fontSize: normalizePreference(
		'fontSize',
		READER_CONFIG.defaultFontSize,
		'default'
	),
	theme: normalizePreference(
		'theme',
		READER_CONFIG.defaultReaderTheme,
		'light'
	),
	width: normalizePreference(
		'width',
		READER_CONFIG.defaultContentWidth,
		'default'
	),
} );

const getStoredPreferences = () => {
	const defaults = getDefaultPreferences();
	const storageKey =
		READER_CONFIG.preferencesStorageKey || DEFAULT_STORAGE_KEY;

	try {
		const stored = window.localStorage.getItem( storageKey );

		if ( ! stored ) {
			return defaults;
		}

		const parsed = JSON.parse( stored );

		return {
			fontSize: normalizePreference(
				'fontSize',
				parsed.fontSize,
				defaults.fontSize
			),
			theme: normalizePreference( 'theme', parsed.theme, defaults.theme ),
			width: normalizePreference( 'width', parsed.width, defaults.width ),
		};
	} catch {
		return defaults;
	}
};

const PreferenceGroup = ( { label, options, value, onChange } ) => (
	<fieldset className="wpdfv-reader-preference-group">
		<legend>{ label }</legend>
		<ButtonGroup aria-label={ label }>
			{ options.map( ( option ) => (
				<Button
					key={ option.value }
					variant={ value === option.value ? 'primary' : 'secondary' }
					size="compact"
					aria-pressed={ value === option.value }
					onClick={ () => onChange( option.value ) }
				>
					{ option.label }
				</Button>
			) ) }
		</ButtonGroup>
	</fieldset>
);

const PreferenceControls = ( { preferences, onChange } ) => (
	<div className="wpdfv-reader-preferences">
		<PreferenceGroup
			label={ __( 'Font size', 'wp-distraction-free-view' ) }
			options={ PREFERENCE_OPTIONS.fontSize }
			value={ preferences.fontSize }
			onChange={ ( value ) => onChange( 'fontSize', value ) }
		/>
		<PreferenceGroup
			label={ __( 'Theme', 'wp-distraction-free-view' ) }
			options={ PREFERENCE_OPTIONS.theme }
			value={ preferences.theme }
			onChange={ ( value ) => onChange( 'theme', value ) }
		/>
		<PreferenceGroup
			label={ __( 'Content width', 'wp-distraction-free-view' ) }
			options={ PREFERENCE_OPTIONS.width }
			value={ preferences.width }
			onChange={ ( value ) => onChange( 'width', value ) }
		/>
	</div>
);

const ReaderApp = () => {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ title, setTitle ] = useState( '' );
	const [ content, setContent ] = useState( '' );
	const [ readingTime, setReadingTime ] = useState( null );
	const [ progress, setProgress ] = useState( 0 );
	const [ preferences, setPreferences ] = useState( getStoredPreferences );
	const modalClassName = useMemo(
		() =>
			[
				'wpdfv-reader-modal',
				`wpdfv-reader-modal--font-${ preferences.fontSize }`,
				`wpdfv-reader-modal--theme-${ preferences.theme }`,
				`wpdfv-reader-modal--width-${ preferences.width }`,
			].join( ' ' ),
		[ preferences ]
	);

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

	useEffect( () => {
		if ( ! READER_CONFIG.autoOpen || ! READER_CONFIG.currentPostId ) {
			return;
		}

		openReader( READER_CONFIG.currentPostId );
	}, [] );

	useEffect( () => {
		const storageKey =
			READER_CONFIG.preferencesStorageKey || DEFAULT_STORAGE_KEY;

		try {
			window.localStorage.setItem(
				storageKey,
				JSON.stringify( preferences )
			);
		} catch {
			// localStorage may be unavailable in private browsing contexts.
		}
	}, [ preferences ] );

	useEffect( () => {
		if ( ! isOpen ) {
			return undefined;
		}

		document.documentElement.classList.add(
			'wpdfv-reader-modal-open',
			'wpdfv-reader-mode-active'
		);
		document.body.classList.add( 'wpdfv-reader-mode-active' );

		return () => {
			document.documentElement.classList.remove(
				'wpdfv-reader-modal-open',
				'wpdfv-reader-mode-active'
			);
			document.body.classList.remove( 'wpdfv-reader-mode-active' );
		};
	}, [ isOpen ] );

	useEffect( () => {
		if ( ! isOpen || ! isEnabled( 'readingProgressEnabled' ) ) {
			setProgress( 0 );
			return undefined;
		}

		const scrollContainer = document.querySelector(
			'.wpdfv-reader-modal .components-modal__content'
		);

		if ( ! scrollContainer ) {
			return undefined;
		}

		const updateProgress = () => {
			const scrollable =
				scrollContainer.scrollHeight - scrollContainer.clientHeight;
			const nextProgress =
				scrollable > 0
					? ( scrollContainer.scrollTop / scrollable ) * 100
					: 100;

			setProgress( Math.min( 100, Math.max( 0, nextProgress ) ) );
		};

		updateProgress();
		scrollContainer.addEventListener( 'scroll', updateProgress, {
			passive: true,
		} );
		window.addEventListener( 'resize', updateProgress );

		return () => {
			scrollContainer.removeEventListener( 'scroll', updateProgress );
			window.removeEventListener( 'resize', updateProgress );
		};
	}, [ isOpen, content ] );

	const updatePreference = ( key, value ) => {
		setPreferences( ( current ) => ( {
			...current,
			[ key ]: normalizePreference( key, value, current[ key ] ),
		} ) );
	};

	const openReader = ( postId ) => {
		if ( ! postId ) {
			return;
		}

		setIsOpen( true );
		setIsLoading( true );
		setError( '' );
		setTitle( '' );
		setContent( '' );
		setReadingTime( null );
		setProgress( 0 );

		apiFetch( { path: `${ CONTENT_PATH }${ postId }` } )
			.then( ( response ) => {
				setTitle( response.title );
				setContent( response.content );
				setReadingTime( response.readingTime );
			} )
			.catch( () => {
				setError(
					__(
						'This content could not be loaded in Reader Mode.',
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
				bodyOpenClassName="wpdfv-reader-modal-open"
				className={ modalClassName }
				closeButtonLabel={
					READER_CONFIG.exitButtonText ||
					__( 'Exit Reader Mode', 'wp-distraction-free-view' )
				}
				headerActions={
					<div className="wpdfv-reader-header-actions">
						<Button
							variant="link"
							size="compact"
							icon={ printIcon }
							label={ __( 'Print', 'wp-distraction-free-view' ) }
							showTooltip={ false }
							onClick={ printReader }
							disabled={ isLoading || ! content }
						/>
						<Button
							variant="link"
							size="compact"
							icon={ fullscreenIcon }
							label={ __(
								'Fullscreen',
								'wp-distraction-free-view'
							) }
							showTooltip={ false }
							onClick={ toggleFullscreen }
						/>
					</div>
				}
				title={
					title || __( 'Reader Mode', 'wp-distraction-free-view' )
				}
				onRequestClose={ closeReader }
				shouldCloseOnClickOutside={ false }
			>
				{ isEnabled( 'readingProgressEnabled' ) && (
					<div className="wpdfv-reading-progress" aria-hidden="true">
						<span style={ { width: `${ progress }%` } } />
					</div>
				) }

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

					{ ! isLoading && ! error && (
						<div className="wpdfv-reader-toolbar">
							{ isEnabled( 'readingTimeEnabled' ) &&
								readingTime?.label && (
									<p className="wpdfv-reading-time">
										{ readingTime.label }
									</p>
								) }
							{ isEnabled( 'preferenceControlsEnabled' ) && (
								<PreferenceControls
									preferences={ preferences }
									onChange={ updatePreference }
								/>
							) }
						</div>
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
