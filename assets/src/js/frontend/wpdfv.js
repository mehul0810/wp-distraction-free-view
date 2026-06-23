import '../../css/frontend/wpdfv.scss';

import apiFetch from '@wordpress/api-fetch';
import {
	createElement,
	forwardRef,
	RawHTML,
	render,
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Path, SVG } from '@wordpress/primitives';

const CONTENT_PATH = '/wp-distraction-free-view/v1/content/';
const READER_CONFIG = window.wpdfvReaderMode || {};
const DEFAULT_STORAGE_KEY = 'wpdfv_reader_preferences';
const FOCUSABLE_SELECTOR = [
	'a[href]',
	'button:not([disabled])',
	'input:not([disabled])',
	'select:not([disabled])',
	'textarea:not([disabled])',
	'[tabindex]:not([tabindex="-1"])',
].join( ',' );
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
	lineHeight: [
		{
			label: __( 'Default', 'wp-distraction-free-view' ),
			value: 'default',
		},
		{
			label: __( 'Relaxed', 'wp-distraction-free-view' ),
			value: 'relaxed',
		},
		{
			label: __( 'Spacious', 'wp-distraction-free-view' ),
			value: 'spacious',
		},
	],
	paragraphSpacing: [
		{
			label: __( 'Default', 'wp-distraction-free-view' ),
			value: 'default',
		},
		{
			label: __( 'Relaxed', 'wp-distraction-free-view' ),
			value: 'relaxed',
		},
		{
			label: __( 'Spacious', 'wp-distraction-free-view' ),
			value: 'spacious',
		},
	],
};
const createHeroIcon = ( paths ) =>
	createElement(
		SVG,
		{
			fill: 'none',
			xmlns: 'http://www.w3.org/2000/svg',
			stroke: 'currentColor',
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
			strokeWidth: '1.5',
			viewBox: '0 0 24 24',
		},
		paths.map( ( path ) =>
			createElement( Path, {
				d: path,
				key: path,
			} )
		)
	);
const settingsIcon = createHeroIcon( [
	'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.991l1.005.828c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.241.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.991l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281Z',
	'M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
] );
const printIcon = createHeroIcon( [
	'M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231a1.125 1.125 0 0 1-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Z',
] );
const closeIcon = createHeroIcon( [ 'M6 18 18 6M6 6l12 12' ] );
const tableOfContentsIcon = createHeroIcon( [
	'M8.25 6.75h12',
	'M8.25 12h12',
	'M8.25 17.25h12',
	'M3.75 6.75h.008v.008H3.75V6.75Z',
	'M3.75 12h.008v.008H3.75V12Z',
	'M3.75 17.25h.008v.008H3.75v-.008Z',
] );
const fullscreenIcon = createHeroIcon( [
	'M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9',
	'M20.25 3.75v4.5m0-4.5h-4.5m4.5 0L15 9',
	'M20.25 20.25v-4.5m0 4.5h-4.5m4.5 0L15 15',
	'M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15',
] );
const exitFullscreenIcon = createHeroIcon( [
	'M9 9V4.5M9 9H4.5M9 9 3.75 3.75',
	'M9 15v4.5M9 15H4.5M9 15l-5.25 5.25',
	'M15 9h4.5M15 9V4.5M15 9l5.25-5.25',
	'M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25',
] );

const isEnabled = ( key ) => READER_CONFIG[ key ] !== false;

const SCRIPT_BOOLEAN_ATTRIBUTES = [ 'async', 'defer', 'nomodule' ];

const createReaderScriptElement = ( script ) => {
	if ( ! script || 'object' !== typeof script ) {
		return null;
	}

	const element = document.createElement( 'script' );
	const attributes = script.attributes || {};

	Object.entries( attributes ).forEach( ( [ name, value ] ) => {
		if ( ! /^[a-z][a-z0-9:-]*$/i.test( name ) ) {
			return;
		}

		if ( SCRIPT_BOOLEAN_ATTRIBUTES.includes( name ) ) {
			element[ name ] = true;
			element.setAttribute( name, name );
			return;
		}

		element.setAttribute( name, String( value ) );
	} );

	if (
		script.src &&
		! Object.prototype.hasOwnProperty.call( attributes, 'async' )
	) {
		element.async = false;
	}

	if ( script.src ) {
		element.src = script.src;
	}

	if ( script.content ) {
		element.text = script.content;
	}

	return element.src || element.text ? element : null;
};

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
	lineHeight: normalizePreference(
		'lineHeight',
		READER_CONFIG.defaultLineHeight,
		'default'
	),
	paragraphSpacing: normalizePreference(
		'paragraphSpacing',
		READER_CONFIG.defaultParagraphSpacing,
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
			lineHeight: normalizePreference(
				'lineHeight',
				parsed.lineHeight,
				defaults.lineHeight
			),
			paragraphSpacing: normalizePreference(
				'paragraphSpacing',
				parsed.paragraphSpacing,
				defaults.paragraphSpacing
			),
		};
	} catch {
		return defaults;
	}
};

const getFocusableElements = ( container ) =>
	Array.from( container.querySelectorAll( FOCUSABLE_SELECTOR ) ).filter(
		( element ) => {
			const ownerWindow = element.ownerDocument.defaultView;
			const style = ownerWindow?.getComputedStyle( element );

			return (
				! element.hidden &&
				! element.getAttribute( 'aria-hidden' ) &&
				'none' !== style?.display &&
				'hidden' !== style?.visibility &&
				element.getClientRects().length > 0
			);
		}
	);

const ReaderButton = forwardRef( function ReaderButton(
	{
		ariaLabel,
		children,
		className = '',
		disabled = false,
		icon,
		isPressed,
		label,
		onClick,
		variant = 'secondary',
		...props
	},
	ref
) {
	const classes = [
		'components-button',
		`is-${ variant }`,
		icon && ! children ? 'has-icon' : '',
		className,
	]
		.filter( Boolean )
		.join( ' ' );
	const accessibleLabel = ariaLabel || label;

	return (
		<button
			ref={ ref }
			type="button"
			className={ classes }
			disabled={ disabled }
			aria-label={ accessibleLabel }
			aria-pressed={ isPressed }
			title={ accessibleLabel }
			onClick={ onClick }
			{ ...props }
		>
			{ icon && (
				<span className="wpdfv-reader-button__icon">{ icon }</span>
			) }
			{ children && (
				<span className="wpdfv-reader-button__text">{ children }</span>
			) }
		</button>
	);
} );

const ReaderSpinner = () => (
	<span className="wpdfv-reader-spinner" aria-hidden="true" />
);

const ReaderNotice = ( { children, status = 'info' } ) => (
	<div
		className={ `wpdfv-reader-notice wpdfv-reader-notice--${ status }` }
		role={ 'error' === status ? 'alert' : 'status' }
	>
		{ children }
	</div>
);

const ReaderDialog = ( {
	bodyOpenClassName,
	children,
	className,
	closeButtonLabel,
	headerActions,
	onRequestClose,
	title,
} ) => {
	const dialogRef = useRef( null );
	const titleId = 'wpdfv-reader-modal-title';
	const closeRef = useRef( null );
	const previouslyFocusedRef = useRef( null );

	useEffect( () => {
		const ownerDocument = dialogRef.current?.ownerDocument || document;

		previouslyFocusedRef.current = ownerDocument.activeElement;
		ownerDocument.body.classList.add( bodyOpenClassName );
		closeRef.current?.focus();

		return () => {
			ownerDocument.body.classList.remove( bodyOpenClassName );

			if (
				previouslyFocusedRef.current &&
				ownerDocument.contains( previouslyFocusedRef.current )
			) {
				previouslyFocusedRef.current.focus();
			}
		};
	}, [ bodyOpenClassName ] );

	useEffect( () => {
		const ownerDocument = dialogRef.current?.ownerDocument || document;
		const handleKeyDown = ( event ) => {
			if ( 'Escape' === event.key ) {
				event.preventDefault();
				onRequestClose();
				return;
			}

			if ( 'Tab' !== event.key || ! dialogRef.current ) {
				return;
			}

			const focusable = getFocusableElements( dialogRef.current );

			if ( 0 === focusable.length ) {
				event.preventDefault();
				dialogRef.current.focus();
				return;
			}

			const first = focusable[ 0 ];
			const last = focusable[ focusable.length - 1 ];

			if ( event.shiftKey && ownerDocument.activeElement === first ) {
				event.preventDefault();
				last.focus();
				return;
			}

			if ( ! event.shiftKey && ownerDocument.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		};

		ownerDocument.addEventListener( 'keydown', handleKeyDown );

		return () =>
			ownerDocument.removeEventListener( 'keydown', handleKeyDown );
	}, [ onRequestClose ] );

	return (
		<div className="components-modal__screen-overlay">
			<div
				ref={ dialogRef }
				className={ className }
				role="dialog"
				aria-modal="true"
				aria-labelledby={ titleId }
				tabIndex="-1"
			>
				<div className="components-modal__header">
					<div className="components-modal__header-heading-container">
						<h1
							className="components-modal__header-heading"
							id={ titleId }
						>
							{ title }
						</h1>
					</div>
					{ headerActions }
					<ReaderButton
						ref={ closeRef }
						variant="link"
						icon={ closeIcon }
						label={ closeButtonLabel }
						onClick={ onRequestClose }
					/>
				</div>
				<div className="components-modal__content">{ children }</div>
			</div>
		</div>
	);
};

const PreferenceGroup = ( { label, options, value, onChange } ) => (
	<fieldset className="wpdfv-reader-preference-group">
		<legend>{ label }</legend>
		<div
			className="components-button-group"
			role="group"
			aria-label={ label }
		>
			{ options.map( ( option ) => (
				<ReaderButton
					key={ option.value }
					variant={ value === option.value ? 'primary' : 'secondary' }
					isPressed={ value === option.value }
					onClick={ () => onChange( option.value ) }
				>
					{ option.label }
				</ReaderButton>
			) ) }
		</div>
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
		<PreferenceGroup
			label={ __( 'Line height', 'wp-distraction-free-view' ) }
			options={ PREFERENCE_OPTIONS.lineHeight }
			value={ preferences.lineHeight }
			onChange={ ( value ) => onChange( 'lineHeight', value ) }
		/>
		<PreferenceGroup
			label={ __( 'Paragraph spacing', 'wp-distraction-free-view' ) }
			options={ PREFERENCE_OPTIONS.paragraphSpacing }
			value={ preferences.paragraphSpacing }
			onChange={ ( value ) => onChange( 'paragraphSpacing', value ) }
		/>
	</div>
);

const ReaderTableOfContents = ( { items, onNavigate } ) => (
	<nav
		className="wpdfv-reader-toc"
		aria-label={ __( 'Table of contents', 'wp-distraction-free-view' ) }
	>
		<div className="wpdfv-reader-toc__heading">
			<span aria-hidden="true">{ tableOfContentsIcon }</span>
			<h2>{ __( 'Contents', 'wp-distraction-free-view' ) }</h2>
		</div>
		<ol>
			{ items.map( ( item ) => (
				<li
					key={ item.id }
					className={ `wpdfv-reader-toc__item wpdfv-reader-toc__item--level-${ item.level }` }
				>
					<a
						href={ `#${ item.id }` }
						onClick={ ( event ) => {
							event.preventDefault();
							onNavigate( item.id );
						} }
					>
						{ item.text }
					</a>
				</li>
			) ) }
		</ol>
	</nav>
);

const ReaderApp = () => {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ isSettingsOpen, setIsSettingsOpen ] = useState( false );
	const [ isFullscreen, setIsFullscreen ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ title, setTitle ] = useState( '' );
	const [ permalink, setPermalink ] = useState( '' );
	const [ content, setContent ] = useState( '' );
	const [ scripts, setScripts ] = useState( [] );
	const [ tocItems, setTocItems ] = useState( [] );
	const [ readingTime, setReadingTime ] = useState( null );
	const [ progress, setProgress ] = useState( 0 );
	const [ preferences, setPreferences ] = useState( getStoredPreferences );
	const contentRef = useRef( null );
	const scriptNodesRef = useRef( [] );
	const modalClassName = useMemo(
		() =>
			[
				'wpdfv-reader-modal',
				`wpdfv-reader-modal--font-${ preferences.fontSize }`,
				`wpdfv-reader-modal--theme-${ preferences.theme }`,
				`wpdfv-reader-modal--width-${ preferences.width }`,
				`wpdfv-reader-modal--line-height-${ preferences.lineHeight }`,
				`wpdfv-reader-modal--paragraph-spacing-${ preferences.paragraphSpacing }`,
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
		if ( ! isOpen ) {
			setIsFullscreen( false );
			return undefined;
		}

		const handleFullscreenChange = () => {
			setIsFullscreen(
				document.fullscreenElement?.classList?.contains(
					'wpdfv-reader-modal'
				) || false
			);
		};

		document.addEventListener( 'fullscreenchange', handleFullscreenChange );
		handleFullscreenChange();

		return () =>
			document.removeEventListener(
				'fullscreenchange',
				handleFullscreenChange
			);
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

	useEffect( () => {
		if ( ! isOpen ) {
			return undefined;
		}

		const overlay = document
			.querySelector( '.wpdfv-reader-modal' )
			?.closest( '.components-modal__screen-overlay' );

		if (
			overlay?.parentNode === document.body &&
			overlay !== document.body.firstChild
		) {
			document.body.insertBefore( overlay, document.body.firstChild );
		}

		return undefined;
	}, [ isOpen, content ] );

	useEffect( () => {
		scriptNodesRef.current.forEach( ( node ) => node.remove() );
		scriptNodesRef.current = [];

		if (
			! isOpen ||
			isLoading ||
			error ||
			! content ||
			! contentRef.current ||
			! Array.isArray( scripts )
		) {
			return undefined;
		}

		const nodes = scripts
			.map( createReaderScriptElement )
			.filter( Boolean );

		nodes.forEach( ( node ) => contentRef.current.appendChild( node ) );
		scriptNodesRef.current = nodes;

		return () => {
			nodes.forEach( ( node ) => node.remove() );
			scriptNodesRef.current = scriptNodesRef.current.filter(
				( node ) => ! nodes.includes( node )
			);
		};
	}, [ isOpen, isLoading, error, content, scripts ] );

	const updatePreference = ( key, value ) => {
		setPreferences( ( current ) => ( {
			...current,
			[ key ]: normalizePreference( key, value, current[ key ] ),
		} ) );
	};

	const closeReader = useCallback( () => {
		setIsOpen( false );
		setIsSettingsOpen( false );
		setIsFullscreen( false );
		setError( '' );
		setScripts( [] );
		setTocItems( [] );
	}, [] );

	const openReader = ( postId ) => {
		if ( ! postId ) {
			return;
		}

		setIsOpen( true );
		setIsLoading( true );
		setError( '' );
		setTitle( '' );
		setPermalink( '' );
		setContent( '' );
		setScripts( [] );
		setTocItems( [] );
		setReadingTime( null );
		setIsSettingsOpen( false );
		setProgress( 0 );

		apiFetch( { path: `${ CONTENT_PATH }${ postId }` } )
			.then( ( response ) => {
				setTitle( response.title );
				setPermalink( response.permalink || '' );
				setContent( response.content );
				setScripts(
					Array.isArray( response.scripts ) ? response.scripts : []
				);
				setTocItems(
					Array.isArray( response.toc ) ? response.toc : []
				);
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

	const navigateToHeading = ( headingId ) => {
		const target =
			contentRef.current?.ownerDocument.getElementById( headingId );

		if ( ! target || ! contentRef.current?.contains( target ) ) {
			return;
		}

		target.setAttribute( 'tabindex', '-1' );
		target.focus( { preventScroll: true } );
		target.scrollIntoView( { block: 'start', behavior: 'smooth' } );
	};

	const showReadingTime =
		isEnabled( 'readingTimeEnabled' ) &&
		! isLoading &&
		! error &&
		readingTime?.label;
	const showTableOfContents =
		isEnabled( 'readerTocEnabled' ) &&
		! isLoading &&
		! error &&
		tocItems.length > 1;
	const showPreferenceControls = isEnabled( 'preferenceControlsEnabled' );

	return (
		isOpen && (
			<ReaderDialog
				bodyOpenClassName="wpdfv-reader-modal-open"
				className={ modalClassName }
				closeButtonLabel={
					READER_CONFIG.exitButtonText ||
					__( 'Exit Reader Mode', 'wp-distraction-free-view' )
				}
				headerActions={
					<div className="wpdfv-reader-header-actions">
						{ showReadingTime && (
							<span className="wpdfv-reading-time wpdfv-reading-time--header">
								{ readingTime.label }
							</span>
						) }
						{ showPreferenceControls && (
							<ReaderButton
								variant="link"
								icon={ settingsIcon }
								label={ __(
									'Reader settings',
									'wp-distraction-free-view'
								) }
								showTooltip={ false }
								aria-controls="wpdfv-reader-settings-panel"
								aria-expanded={ isSettingsOpen }
								onClick={ () =>
									setIsSettingsOpen( ( value ) => ! value )
								}
							/>
						) }
						<ReaderButton
							variant="link"
							icon={ printIcon }
							label={ __( 'Print', 'wp-distraction-free-view' ) }
							onClick={ printReader }
							disabled={ isLoading || ! content }
						/>
						<ReaderButton
							variant="link"
							icon={
								isFullscreen
									? exitFullscreenIcon
									: fullscreenIcon
							}
							label={
								isFullscreen
									? __(
											'Exit fullscreen',
											'wp-distraction-free-view'
									  )
									: __(
											'Fullscreen',
											'wp-distraction-free-view'
									  )
							}
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

				{ showPreferenceControls && isSettingsOpen && (
					<aside
						className="wpdfv-reader-settings-panel"
						id="wpdfv-reader-settings-panel"
						aria-label={ __(
							'Reader settings',
							'wp-distraction-free-view'
						) }
					>
						<div className="wpdfv-reader-settings-panel__header">
							<h2>
								{ __(
									'Reader settings',
									'wp-distraction-free-view'
								) }
							</h2>
							<ReaderButton
								variant="link"
								icon={ closeIcon }
								label={ __(
									'Close reader settings',
									'wp-distraction-free-view'
								) }
								showTooltip={ false }
								onClick={ () => setIsSettingsOpen( false ) }
							/>
						</div>
						<PreferenceControls
							preferences={ preferences }
							onChange={ updatePreference }
						/>
					</aside>
				) }

				{ showTableOfContents && (
					<ReaderTableOfContents
						items={ tocItems }
						onNavigate={ navigateToHeading }
					/>
				) }

				<div
					className="wpdfv-reader-content"
					id="wpdfv-print"
					ref={ contentRef }
				>
					{ ! isLoading && content && (
						<header className="wpdfv-reader-print-header">
							<h1>{ title }</h1>
							{ permalink && (
								<p>
									{ sprintf(
										/* translators: %s: Source URL for printed Reader Mode content. */
										__(
											'Source: %s',
											'wp-distraction-free-view'
										),
										permalink
									) }
								</p>
							) }
						</header>
					) }
					{ isLoading && (
						<div className="wpdfv-reader-loading">
							<ReaderSpinner />
						</div>
					) }

					{ error && (
						<ReaderNotice status="error">{ error }</ReaderNotice>
					) }

					{ ! isLoading && content && <RawHTML>{ content }</RawHTML> }
				</div>
			</ReaderDialog>
		)
	);
};

if ( ! window.wpdfvReaderModeInitialized ) {
	window.wpdfvReaderModeInitialized = true;

	const root =
		document.getElementById( 'wpdfv-reader-root' ) ||
		document.createElement( 'div' );

	root.id = 'wpdfv-reader-root';

	if (
		root.parentNode !== document.body ||
		root !== document.body.firstChild
	) {
		document.body.insertBefore( root, document.body.firstChild );
	}

	render( <ReaderApp />, root );
}
