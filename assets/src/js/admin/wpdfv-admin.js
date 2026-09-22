import '../../css/admin/main.scss';

import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	CheckboxControl,
	ExternalLink,
	Notice,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import {
	createElement,
	render,
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Path, SVG } from '@wordpress/primitives';

const SETTINGS_PATH = '/wp-distraction-free-view/v1/settings';
const PLUGIN_ACTION_PATH = '/wp-distraction-free-view/v1/plugins';
const DOCUMENTATION_URL =
	'https://github.com/mehul0810/wp-distraction-free-view#readme';
const SUPPORT_URL =
	'https://wordpress.org/support/plugin/wp-distraction-free-view/';
const checkIcon = createElement(
	SVG,
	{
		xmlns: 'http://www.w3.org/2000/svg',
		viewBox: '0 0 24 24',
	},
	createElement( Path, {
		d: 'M16.5 7.5 10 13.9l-2.5-2.4-1 1 3.5 3.6 7.5-7.6z',
	} )
);
const SettingsApp = () => {
	const [ settings, setSettings ] = useState( null );
	const [ postTypes, setPostTypes ] = useState( [] );
	const [ displayLocations, setDisplayLocations ] = useState( [] );
	const [ readerThemes, setReaderThemes ] = useState( [] );
	const [ contentWidths, setContentWidths ] = useState( [] );
	const [ fontSizes, setFontSizes ] = useState( [] );
	const [ modalTemplates, setModalTemplates ] = useState( [] );
	const [ canEditCustomCss, setCanEditCustomCss ] = useState( false );
	const [ morePlugins, setMorePlugins ] = useState( {
		free: [],
		paid: [],
	} );
	const [ aboutInfo, setAboutInfo ] = useState( {
		brandIconUrl: '',
		minimumPhp: '',
		minimumWordPress: '',
		pluginVersion: '',
	} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ activeTab, setActiveTab ] = useState( 'about' );
	const [ activePluginAction, setActivePluginAction ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const [ isDirty, setIsDirty ] = useState( false );
	const tabs = [
		{
			name: 'about',
			title: __( 'About', 'wp-distraction-free-view' ),
		},
		{
			name: 'configure',
			title: __( 'Configure', 'wp-distraction-free-view' ),
		},
		{
			name: 'more-plugins',
			title: __( 'More Plugins', 'wp-distraction-free-view' ),
		},
	];

	const applySettingsResponse = useCallback( ( response ) => {
		setSettings( response.settings );
		setIsDirty( false );
		setPostTypes( response.postTypes );
		setDisplayLocations( response.displayLocations );
		setReaderThemes( response.readerThemes );
		setContentWidths( response.contentWidths );
		setFontSizes( response.fontSizes );
		setModalTemplates( response.modalTemplates );
		setCanEditCustomCss( !! response.canEditCustomCss );
		setMorePlugins(
			normalizeMorePlugins(
				response.morePlugins || response.recommendedPlugins || []
			)
		);
		setAboutInfo( {
			brandIconUrl: response.brandIconUrl,
			minimumPhp: response.minimumPhp,
			minimumWordPress: response.minimumWordPress,
			pluginVersion: response.pluginVersion,
		} );
	}, [] );

	const loadSettings = useCallback( () => {
		setIsLoading( true );
		setNotice( null );
		apiFetch( { path: SETTINGS_PATH } )
			.then( applySettingsResponse )
			.catch( () => {
				setNotice( {
					status: 'error',
					message: __(
						'Settings could not be loaded.',
						'wp-distraction-free-view'
					),
				} );
			} )
			.finally( () => setIsLoading( false ) );
	}, [ applySettingsResponse ] );

	useEffect( () => {
		loadSettings();
	}, [ loadSettings ] );

	const selectedPostTypes = useMemo(
		() => settings?.where_to_display || [],
		[ settings ]
	);

	const updateSetting = ( key, value ) => {
		setIsDirty( true );
		setSettings( ( current ) => ( {
			...current,
			[ key ]: value,
		} ) );
	};

	const togglePostType = ( slug, isChecked ) => {
		const nextSelection = isChecked
			? [ ...selectedPostTypes, slug ]
			: selectedPostTypes.filter(
					( selectedSlug ) => selectedSlug !== slug
			  );

		updateSetting( 'where_to_display', [ ...new Set( nextSelection ) ] );
	};

	const saveSettings = () => {
		setIsSaving( true );
		setNotice( null );

		apiFetch( {
			path: SETTINGS_PATH,
			method: 'POST',
			data: settings,
		} )
			.then( ( response ) => {
				applySettingsResponse( response );
				setNotice( {
					status: 'success',
					message: __(
						'Settings saved.',
						'wp-distraction-free-view'
					),
				} );
			} )
			.catch( () => {
				setNotice( {
					status: 'error',
					message: __(
						'Settings could not be saved.',
						'wp-distraction-free-view'
					),
				} );
			} )
			.finally( () => setIsSaving( false ) );
	};

	const runPluginAction = ( slug, action ) => {
		setActivePluginAction( `${ slug }:${ action }` );
		setNotice( null );

		apiFetch( {
			path: `${ PLUGIN_ACTION_PATH }/${ encodeURIComponent(
				slug
			) }/${ action }`,
			method: 'POST',
		} )
			.then( ( response ) => {
				setMorePlugins(
					normalizeMorePlugins( response.morePlugins || [] )
				);
				setNotice( {
					status: 'success',
					message:
						'install' === action
							? __(
									'Plugin installed. You can activate it now.',
									'wp-distraction-free-view'
							  )
							: __(
									'Plugin activated.',
									'wp-distraction-free-view'
							  ),
				} );
			} )
			.catch( ( error ) => {
				setNotice( {
					status: 'error',
					message:
						error?.message ||
						__(
							'Plugin action could not be completed.',
							'wp-distraction-free-view'
						),
				} );
			} )
			.finally( () => setActivePluginAction( '' ) );
	};

	const renderActivePanel = () => {
		if ( 'configure' === activeTab ) {
			return (
				<ConfigurePanel
					settings={ settings }
					postTypes={ postTypes }
					displayLocations={ displayLocations }
					readerThemes={ readerThemes }
					contentWidths={ contentWidths }
					fontSizes={ fontSizes }
					modalTemplates={ modalTemplates }
					canEditCustomCss={ canEditCustomCss }
					selectedPostTypes={ selectedPostTypes }
					isSaving={ isSaving }
					isDirty={ isDirty }
					onTogglePostType={ togglePostType }
					onUpdateSetting={ updateSetting }
					onSave={ saveSettings }
				/>
			);
		}

		if ( 'more-plugins' === activeTab ) {
			return (
				<MorePluginsPanel
					plugins={ morePlugins }
					activePluginAction={ activePluginAction }
					onPluginAction={ runPluginAction }
				/>
			);
		}

		return (
			<AboutPanel
				aboutInfo={ aboutInfo }
				settings={ settings }
				readerThemes={ readerThemes }
				contentWidths={ contentWidths }
				fontSizes={ fontSizes }
				selectedPostTypes={ selectedPostTypes }
				onConfigure={ () => setActiveTab( 'configure' ) }
			/>
		);
	};
	let activeContent;

	if ( isLoading ) {
		activeContent = (
			<div className="wpdfv-settings-state" role="status">
				<Spinner />
				{ __( 'Loading settings…', 'wp-distraction-free-view' ) }
			</div>
		);
	} else if ( ! settings ) {
		activeContent = (
			<div className="wpdfv-settings-state">
				<p>
					{ __(
						'Check your connection, then try again.',
						'wp-distraction-free-view'
					) }
				</p>
				<Button variant="secondary" onClick={ loadSettings }>
					{ __( 'Retry loading', 'wp-distraction-free-view' ) }
				</Button>
			</div>
		);
	} else {
		activeContent = (
			<section
				className="wpdfv-settings-tab-panel"
				id={ `wpdfv-panel-${ activeTab }` }
				role="tabpanel"
				aria-labelledby={ `wpdfv-tab-${ activeTab }` }
			>
				{ renderActivePanel() }
			</section>
		);
	}

	return (
		<div
			className={ `wpdfv-settings-app${
				isLoading ? ' wpdfv-settings-app--loading' : ''
			}` }
		>
			<SettingsHeader
				tabs={ tabs }
				activeTab={ activeTab }
				brandIconUrl={ aboutInfo.brandIconUrl }
				pluginVersion={ aboutInfo.pluginVersion }
				onChangeTab={ setActiveTab }
			/>

			<div className="wpdfv-settings-content">
				{ notice && (
					<Notice
						status={ notice.status }
						onRemove={ () => setNotice( null ) }
					>
						{ notice.message }
					</Notice>
				) }

				{ activeContent }
			</div>
		</div>
	);
};

const SettingsHeader = ( {
	tabs,
	activeTab,
	brandIconUrl,
	pluginVersion,
	onChangeTab,
} ) => {
	const handleTabKeyDown = ( event, index ) => {
		let nextIndex;

		if ( 'ArrowRight' === event.key ) {
			nextIndex = ( index + 1 ) % tabs.length;
		} else if ( 'ArrowLeft' === event.key ) {
			nextIndex = ( index - 1 + tabs.length ) % tabs.length;
		} else if ( 'Home' === event.key ) {
			nextIndex = 0;
		} else if ( 'End' === event.key ) {
			nextIndex = tabs.length - 1;
		} else {
			return;
		}

		event.preventDefault();
		onChangeTab( tabs[ nextIndex ].name );
		document
			.getElementById( `wpdfv-tab-${ tabs[ nextIndex ].name }` )
			?.focus();
	};

	return (
		<header className="wpdfv-settings-header">
			<div className="wpdfv-settings-header__inner">
				<div className="wpdfv-settings-header__brand-row">
					<div className="wpdfv-settings-brand">
						<span
							className="wpdfv-settings-brand__mark"
							aria-hidden="true"
						>
							{ brandIconUrl && (
								<img
									src={ brandIconUrl }
									alt=""
									loading="eager"
								/>
							) }
						</span>
						<h1 className="wpdfv-settings-brand__name">
							<span>
								{ __(
									'WP Distraction',
									'wp-distraction-free-view'
								) }
							</span>{ ' ' }
							<span>
								{ __(
									'Free View',
									'wp-distraction-free-view'
								) }
							</span>
						</h1>
					</div>

					<span className="wpdfv-settings-version">
						{ sprintf(
							/* translators: %s: Plugin version. */
							__( 'v%s', 'wp-distraction-free-view' ),
							pluginVersion
						) }
					</span>
				</div>

				<div className="wpdfv-settings-header__nav-row">
					<div
						className="wpdfv-settings-tabs"
						aria-label={ __(
							'WP Distraction Free View settings',
							'wp-distraction-free-view'
						) }
						role="tablist"
					>
						{ tabs.map( ( tab, index ) => {
							const isActive = tab.name === activeTab;

							return (
								<button
									key={ tab.name }
									id={ `wpdfv-tab-${ tab.name }` }
									className={
										isActive
											? 'wpdfv-settings-tabs__item is-active'
											: 'wpdfv-settings-tabs__item'
									}
									type="button"
									role="tab"
									aria-controls={ `wpdfv-panel-${ tab.name }` }
									aria-selected={ isActive }
									tabIndex={ isActive ? 0 : -1 }
									onClick={ () => onChangeTab( tab.name ) }
									onKeyDown={ ( event ) =>
										handleTabKeyDown( event, index )
									}
								>
									{ tab.title }
								</button>
							);
						} ) }
					</div>

					<div className="wpdfv-settings-header__links">
						<ExternalLink href={ DOCUMENTATION_URL }>
							{ __(
								'View Documentation',
								'wp-distraction-free-view'
							) }
						</ExternalLink>
						<ExternalLink href={ SUPPORT_URL }>
							{ __( 'Support', 'wp-distraction-free-view' ) }
						</ExternalLink>
					</div>
				</div>
			</div>
		</header>
	);
};

const ConfigurePanel = ( {
	settings,
	postTypes,
	displayLocations,
	readerThemes,
	contentWidths,
	fontSizes,
	modalTemplates,
	canEditCustomCss,
	selectedPostTypes,
	isSaving,
	isDirty,
	onTogglePostType,
	onUpdateSetting,
	onSave,
} ) => {
	const [ activeSection, setActiveSection ] = useState( 'general' );
	const sections = [
		{ id: 'general', label: __( 'General', 'wp-distraction-free-view' ) },
		{
			id: 'appearance',
			label: __( 'Appearance', 'wp-distraction-free-view' ),
		},
		{
			id: 'reading',
			label: __( 'Reading tools', 'wp-distraction-free-view' ),
		},
		{ id: 'advanced', label: __( 'Advanced', 'wp-distraction-free-view' ) },
	];
	const intros = {
		general: {
			title: __( 'Reader Mode settings', 'wp-distraction-free-view' ),
			description: __(
				'Choose where readers can open a more focused view.',
				'wp-distraction-free-view'
			),
		},
		appearance: {
			title: __( 'Appearance', 'wp-distraction-free-view' ),
			description: __(
				'Set a comfortable starting point for every reader.',
				'wp-distraction-free-view'
			),
		},
		reading: {
			title: __( 'Reading tools', 'wp-distraction-free-view' ),
			description: __(
				'Give readers useful controls without adding distractions.',
				'wp-distraction-free-view'
			),
		},
		advanced: {
			title: __( 'Advanced settings', 'wp-distraction-free-view' ),
			description: __(
				'Fine-tune Reader Mode with scoped custom styles.',
				'wp-distraction-free-view'
			),
		},
	};
	const intro = intros[ activeSection ];
	const selectPlacement = ( value ) => {
		onUpdateSetting( 'display_location', value );
		onUpdateSetting( 'automatic_button_enabled', 'manual_only' !== value );
	};

	return (
		<form
			className="wpdfv-configure"
			onSubmit={ ( event ) => {
				event.preventDefault();
				onSave();
			} }
		>
			<div className="wpdfv-configure-heading">
				<ScreenIntro
					title={ intro.title }
					description={ intro.description }
				/>
				<div className="wpdfv-configure-heading__action">
					{ isDirty && ! isSaving && (
						<span className="wpdfv-unsaved" role="status">
							{ __(
								'Unsaved changes',
								'wp-distraction-free-view'
							) }
						</span>
					) }
					<Button
						variant="primary"
						icon={ checkIcon }
						isBusy={ isSaving }
						disabled={ ! isDirty || isSaving }
						type="submit"
					>
						{ isSaving
							? __( 'Saving', 'wp-distraction-free-view' )
							: __(
									'Save settings',
									'wp-distraction-free-view'
							  ) }
					</Button>
				</div>
			</div>

			<div className="wpdfv-configure-layout">
				<nav
					className="wpdfv-configure-nav"
					aria-label={ __(
						'Configure sections',
						'wp-distraction-free-view'
					) }
				>
					{ sections.map( ( item ) => (
						<button
							key={ item.id }
							type="button"
							className={ `wpdfv-configure-nav__item${
								item.id === activeSection ? ' is-active' : ''
							}` }
							aria-current={
								item.id === activeSection ? 'page' : undefined
							}
							onClick={ () => setActiveSection( item.id ) }
						>
							{ item.label }
						</button>
					) ) }
				</nav>
				<div className="wpdfv-configure-select">
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __(
							'Settings section',
							'wp-distraction-free-view'
						) }
						value={ activeSection }
						options={ sections.map( ( item ) => ( {
							value: item.id,
							label: item.label,
						} ) ) }
						onChange={ setActiveSection }
					/>
				</div>

				<div
					className="wpdfv-settings-panels"
					id="wpdfv-configure-section"
				>
					{ 'general' === activeSection && (
						<>
							<SettingsSection
								title={ __(
									'Where Reader Mode is available',
									'wp-distraction-free-view'
								) }
								description={ __(
									'Enable Reader Mode for the public content types on your site.',
									'wp-distraction-free-view'
								) }
							>
								<div className="wpdfv-choice-grid">
									{ postTypes.map( ( postType ) => (
										<div
											className="wpdfv-choice-card"
											key={ postType.slug }
										>
											<CheckboxControl
												__nextHasNoMarginBottom
												label={ postType.label }
												checked={ selectedPostTypes.includes(
													postType.slug
												) }
												onChange={ ( value ) =>
													onTogglePostType(
														postType.slug,
														value
													)
												}
											/>
										</div>
									) ) }
								</div>
								{ 0 === selectedPostTypes.length && (
									<p className="wpdfv-inline-note">
										{ __(
											'Choose at least one content type to make Reader Mode available.',
											'wp-distraction-free-view'
										) }
									</p>
								) }
							</SettingsSection>

							<SettingsSection
								title={ __(
									'Toggle placement',
									'wp-distraction-free-view'
								) }
								description={ __(
									'Choose how visitors enter Reader Mode.',
									'wp-distraction-free-view'
								) }
							>
								<fieldset className="wpdfv-placement-list">
									<legend className="screen-reader-text">
										{ __(
											'Toggle placement',
											'wp-distraction-free-view'
										) }
									</legend>
									{ displayLocations.map( ( location ) => (
										<label
											className={ `wpdfv-placement-option${
												location.value ===
												settings.display_location
													? ' is-selected'
													: ''
											}` }
											key={ location.value }
											htmlFor={ `wpdfv-display-${ location.value }` }
											aria-label={ location.label }
										>
											<input
												id={ `wpdfv-display-${ location.value }` }
												type="radio"
												name="wpdfv-display-location"
												value={ location.value }
												checked={
													location.value ===
													settings.display_location
												}
												onChange={ () =>
													selectPlacement(
														location.value
													)
												}
											/>
											<span>
												<strong>
													{ location.label }
												</strong>
												<small>
													{ getPlacementDescription(
														location.value
													) }
												</small>
											</span>
										</label>
									) ) }
								</fieldset>
							</SettingsSection>

							<SettingsSection
								title={ __(
									'Button labels',
									'wp-distraction-free-view'
								) }
								description={ __(
									'Use clear labels that fit your site.',
									'wp-distraction-free-view'
								) }
							>
								<div className="wpdfv-field-grid">
									<TextControl
										__nextHasNoMarginBottom
										__next40pxDefaultSize
										label={ __(
											'Open label',
											'wp-distraction-free-view'
										) }
										value={ settings.button_text }
										onChange={ ( value ) =>
											onUpdateSetting(
												'button_text',
												value
											)
										}
										help={ __(
											'Shown on the Reader Mode toggle and shortcode output.',
											'wp-distraction-free-view'
										) }
									/>
									<TextControl
										__nextHasNoMarginBottom
										__next40pxDefaultSize
										label={ __(
											'Close label',
											'wp-distraction-free-view'
										) }
										value={ settings.exit_button_text }
										onChange={ ( value ) =>
											onUpdateSetting(
												'exit_button_text',
												value
											)
										}
										help={ __(
											'Accessible label for the Reader Mode close control.',
											'wp-distraction-free-view'
										) }
									/>
								</div>
							</SettingsSection>
						</>
					) }

					{ 'appearance' === activeSection && (
						<>
							<SettingsSection
								title={ __(
									'Reader theme',
									'wp-distraction-free-view'
								) }
								description={ __(
									'Choose the default look. Visitors can adjust it in Reader Mode.',
									'wp-distraction-free-view'
								) }
							>
								<fieldset className="wpdfv-theme-grid">
									<legend className="screen-reader-text">
										{ __(
											'Default reader theme',
											'wp-distraction-free-view'
										) }
									</legend>
									{ readerThemes.map( ( theme ) => (
										<label
											className={ `wpdfv-theme-choice wpdfv-theme-choice--${
												theme.value
											}${
												theme.value ===
												settings.default_reader_theme
													? ' is-selected'
													: ''
											}` }
											key={ theme.value }
											htmlFor={ `wpdfv-theme-${ theme.value }` }
										>
											<input
												id={ `wpdfv-theme-${ theme.value }` }
												type="radio"
												name="wpdfv-reader-theme"
												value={ theme.value }
												checked={
													theme.value ===
													settings.default_reader_theme
												}
												onChange={ () =>
													onUpdateSetting(
														'default_reader_theme',
														theme.value
													)
												}
											/>
											<span
												className="wpdfv-theme-choice__sample"
												aria-hidden="true"
											>
												Aa
											</span>
											<span className="wpdfv-theme-choice__label">
												{ theme.label }
											</span>
										</label>
									) ) }
								</fieldset>
							</SettingsSection>
							<SettingsSection
								title={ __(
									'Reading layout',
									'wp-distraction-free-view'
								) }
								description={ __(
									'Comfortable defaults for longer articles.',
									'wp-distraction-free-view'
								) }
							>
								<SegmentedChoices
									label={ __(
										'Content width',
										'wp-distraction-free-view'
									) }
									name="content-width"
									options={ contentWidths }
									value={ settings.default_content_width }
									onChange={ ( value ) =>
										onUpdateSetting(
											'default_content_width',
											value
										)
									}
								/>
								<SegmentedChoices
									label={ __(
										'Font size',
										'wp-distraction-free-view'
									) }
									name="font-size"
									options={ fontSizes }
									value={ settings.default_font_size }
									onChange={ ( value ) =>
										onUpdateSetting(
											'default_font_size',
											value
										)
									}
								/>
							</SettingsSection>
							<SettingsSection
								title={ __(
									'Reader template',
									'wp-distraction-free-view'
								) }
								description={ __(
									'Choose the block-based layout used inside Reader Mode.',
									'wp-distraction-free-view'
								) }
							>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __(
										'Template',
										'wp-distraction-free-view'
									) }
									value={ settings.modal_template }
									options={ modalTemplates }
									onChange={ ( value ) =>
										onUpdateSetting(
											'modal_template',
											value
										)
									}
								/>
							</SettingsSection>
							<SettingsSection
								title={ __(
									'Visitor preferences',
									'wp-distraction-free-view'
								) }
								description={ __(
									'Let readers make the experience their own.',
									'wp-distraction-free-view'
								) }
							>
								<ToggleControl
									__nextHasNoMarginBottom
									label={ __(
										'Show reader preference controls',
										'wp-distraction-free-view'
									) }
									checked={
										settings.preference_controls_enabled
									}
									onChange={ ( value ) =>
										onUpdateSetting(
											'preference_controls_enabled',
											value
										)
									}
									help={ __(
										'Theme, text size, and width preferences are saved only in the visitor browser.',
										'wp-distraction-free-view'
									) }
								/>
							</SettingsSection>
						</>
					) }

					{ 'reading' === activeSection && (
						<>
							<SettingsSection
								title={ __(
									'Reading assistance',
									'wp-distraction-free-view'
								) }
								description={ __(
									'Choose the tools available inside Reader Mode.',
									'wp-distraction-free-view'
								) }
							>
								<div className="wpdfv-toggle-list">
									<ToggleControl
										__nextHasNoMarginBottom
										label={ __(
											'Reading progress',
											'wp-distraction-free-view'
										) }
										checked={
											settings.reading_progress_enabled
										}
										onChange={ ( value ) =>
											onUpdateSetting(
												'reading_progress_enabled',
												value
											)
										}
										help={ __(
											'Show how far the visitor has read.',
											'wp-distraction-free-view'
										) }
									/>
									<ToggleControl
										__nextHasNoMarginBottom
										label={ __(
											'Estimated reading time',
											'wp-distraction-free-view'
										) }
										checked={
											settings.reading_time_enabled
										}
										onChange={ ( value ) =>
											onUpdateSetting(
												'reading_time_enabled',
												value
											)
										}
										help={ __(
											'Help readers know what to expect.',
											'wp-distraction-free-view'
										) }
									/>
									<ToggleControl
										__nextHasNoMarginBottom
										label={ __(
											'Table of contents',
											'wp-distraction-free-view'
										) }
										checked={ settings.reader_toc_enabled }
										onChange={ ( value ) =>
											onUpdateSetting(
												'reader_toc_enabled',
												value
											)
										}
										help={ __(
											'Show navigation when an article has multiple headings.',
											'wp-distraction-free-view'
										) }
									/>
									<ToggleControl
										__nextHasNoMarginBottom
										label={ __(
											'Resume reading',
											'wp-distraction-free-view'
										) }
										checked={
											settings.reader_resume_enabled
										}
										onChange={ ( value ) =>
											onUpdateSetting(
												'reader_resume_enabled',
												value
											)
										}
										help={ __(
											'Offer to continue from the last position in this browser.',
											'wp-distraction-free-view'
										) }
									/>
								</div>
							</SettingsSection>
							<div className="wpdfv-privacy-note">
								<strong>
									{ __(
										'Reading stays personal',
										'wp-distraction-free-view'
									) }
								</strong>
								<p>
									{ __(
										'Resume positions and reader preferences are stored in the visitor browser. No account is required.',
										'wp-distraction-free-view'
									) }
								</p>
							</div>
						</>
					) }

					{ 'advanced' === activeSection &&
						( canEditCustomCss ? (
							<SettingsSection
								title={ __(
									'Custom CSS',
									'wp-distraction-free-view'
								) }
								description={ __(
									'Apply styles only to the Reader Mode experience.',
									'wp-distraction-free-view'
								) }
							>
								<CustomCssControl
									value={ settings.custom_css || '' }
									isDirty={ isDirty }
									codeEditorSettings={
										window.wpdfvAdminSettings?.codeEditor
									}
									onChange={ ( value ) =>
										onUpdateSetting( 'custom_css', value )
									}
								/>
							</SettingsSection>
						) : (
							<div className="wpdfv-privacy-note">
								<strong>
									{ __(
										'Custom CSS is unavailable',
										'wp-distraction-free-view'
									) }
								</strong>
								<p>
									{ __(
										'An administrator with permission to edit CSS can configure Reader Mode styles.',
										'wp-distraction-free-view'
									) }
								</p>
							</div>
						) ) }
				</div>
				<ReaderPreview settings={ settings } />
			</div>
			<details className="wpdfv-mobile-preview">
				<summary>
					{ __( 'Reader preview', 'wp-distraction-free-view' ) }
				</summary>
				<ReaderPreview settings={ settings } />
			</details>
			<div className="wpdfv-mobile-save">
				<Button
					variant="primary"
					icon={ checkIcon }
					isBusy={ isSaving }
					disabled={ ! isDirty || isSaving }
					type="submit"
				>
					{ isSaving
						? __( 'Saving', 'wp-distraction-free-view' )
						: __( 'Save settings', 'wp-distraction-free-view' ) }
				</Button>
			</div>
		</form>
	);
};

const SegmentedChoices = ( { label, name, options, value, onChange } ) => (
	<fieldset className="wpdfv-segmented-field">
		<legend>{ label }</legend>
		<div className="wpdfv-segmented-choices">
			{ options.map( ( option ) => (
				<label
					className={ option.value === value ? 'is-selected' : '' }
					key={ option.value }
					htmlFor={ `wpdfv-${ name }-${ option.value }` }
				>
					<input
						id={ `wpdfv-${ name }-${ option.value }` }
						type="radio"
						name={ `wpdfv-${ name }` }
						value={ option.value }
						checked={ option.value === value }
						onChange={ () => onChange( option.value ) }
					/>
					<span>{ option.label }</span>
				</label>
			) ) }
		</div>
	</fieldset>
);

const ReaderPreview = ( { settings } ) => (
	<aside
		className="wpdfv-preview-column"
		aria-label={ __( 'Sample reader preview', 'wp-distraction-free-view' ) }
	>
		<div className="wpdfv-preview-label">
			{ __( 'Reader preview', 'wp-distraction-free-view' ) }
		</div>
		<div
			className={ `wpdfv-reader-preview wpdfv-reader-preview--${ settings.default_reader_theme } wpdfv-reader-preview--${ settings.default_font_size }` }
		>
			<div className="wpdfv-reader-preview__bar">
				<span aria-hidden="true">▤</span>
				<span aria-hidden="true">Aa &nbsp; ⛶ &nbsp; ×</span>
			</div>
			<article
				className={ `wpdfv-reader-preview__article wpdfv-reader-preview__article--${ settings.default_content_width }` }
			>
				<p className="wpdfv-reader-preview__eyebrow">
					{ __( 'The reading room', 'wp-distraction-free-view' ) }
				</p>
				<h3>
					{ __(
						'A little space to think.',
						'wp-distraction-free-view'
					) }
				</h3>
				{ settings.reading_time_enabled && (
					<p className="wpdfv-reader-preview__time">
						{ __( '4 min read', 'wp-distraction-free-view' ) }
					</p>
				) }
				<p>
					{ __(
						'Good ideas need room to breathe. Step away from the noise and settle into the words in front of you.',
						'wp-distraction-free-view'
					) }
				</p>
				<p>
					{ __(
						'A simpler view brings the story into focus, one paragraph at a time.',
						'wp-distraction-free-view'
					) }
				</p>
			</article>
			{ settings.reading_progress_enabled && (
				<div
					className="wpdfv-reader-preview__progress"
					aria-hidden="true"
				/>
			) }
		</div>
		<p className="wpdfv-preview-caption">
			{ __(
				'Sample content. Preview of reader defaults, not a live post.',
				'wp-distraction-free-view'
			) }
		</p>
		<div className="wpdfv-preview-note">
			<strong>
				{ __( 'Your theme stays yours.', 'wp-distraction-free-view' ) }
			</strong>
			<p>
				{ __(
					'Reader Mode opens over your content. Your original page layout stays unchanged.',
					'wp-distraction-free-view'
				) }
			</p>
		</div>
	</aside>
);

const getPlacementDescription = ( value ) => {
	const descriptions = {
		manual_only: __(
			'Add the Reader Mode Toggle block or shortcode.',
			'wp-distraction-free-view'
		),
		before_content: __(
			'Place a toggle above the article.',
			'wp-distraction-free-view'
		),
		after_content: __(
			'Place a toggle below the article.',
			'wp-distraction-free-view'
		),
		floating: __(
			'Keep a toggle visible as visitors scroll.',
			'wp-distraction-free-view'
		),
	};
	return descriptions[ value ] || '';
};

const CustomCssControl = ( {
	value,
	isDirty,
	codeEditorSettings,
	onChange,
} ) => {
	const textareaRef = useRef();
	const editorRef = useRef();
	const initialValueRef = useRef( value );
	const onChangeRef = useRef( onChange );
	const isSyncingRef = useRef( false );
	onChangeRef.current = onChange;
	const textareaId = 'wpdfv-custom-css';
	const exampleCss = `.wpdfv-reader-modal .wpdfv-reader-content {
\tfont-family: Georgia, serif;
}

.wpdfv-reader-modal .wpdfv-reader-content h1,
.wpdfv-reader-modal .wpdfv-reader-content h2 {
\tcolor: #1f2937;
}

.wpdfv-reader-modal {
\t--wpdfv-reader-accent-color: #3858e9;
}`;

	useEffect( () => {
		if (
			editorRef.current ||
			! textareaRef.current ||
			! codeEditorSettings ||
			! window.wp?.codeEditor
		) {
			return;
		}

		const editor = window.wp.codeEditor.initialize(
			textareaRef.current,
			codeEditorSettings
		);

		editorRef.current = editor;
		const handleChange = () => {
			if ( ! isSyncingRef.current ) {
				onChangeRef.current( editor.codemirror.getValue() );
			}
		};
		editor.codemirror.on( 'change', handleChange );

		return () => {
			editor.codemirror.off( 'change', handleChange );
			editor.codemirror.toTextArea();
			editorRef.current = null;
		};
	}, [ codeEditorSettings ] );

	useEffect( () => {
		if (
			! isDirty &&
			editorRef.current &&
			editorRef.current.codemirror.getValue() !== value
		) {
			isSyncingRef.current = true;
			editorRef.current.codemirror.setValue( value );
			isSyncingRef.current = false;
		}
	}, [ value, isDirty ] );

	return (
		<div className="wpdfv-custom-css">
			<label className="wpdfv-custom-css__label" htmlFor={ textareaId }>
				{ __( 'Reader Mode CSS', 'wp-distraction-free-view' ) }
			</label>
			<textarea
				ref={ textareaRef }
				id={ textareaId }
				className="wpdfv-custom-css__textarea"
				defaultValue={ initialValueRef.current }
				rows={ 12 }
				onChange={ ( event ) =>
					onChangeRef.current( event.target.value )
				}
				aria-describedby="wpdfv-custom-css-help"
			/>
			<p
				id="wpdfv-custom-css-help"
				className="wpdfv-field-row__description"
			>
				{ __(
					'Scope selectors to .wpdfv-reader-modal, .wpdfv-reader-content, or .wpdfv-fullscreen-container.',
					'wp-distraction-free-view'
				) }
			</p>
			<div className="wpdfv-custom-css__examples">
				<h4>
					{ __(
						'Scoped selector examples',
						'wp-distraction-free-view'
					) }
				</h4>
				<pre
					aria-label={ __(
						'Example CSS',
						'wp-distraction-free-view'
					) }
				>
					<code>{ exampleCss }</code>
				</pre>
			</div>
		</div>
	);
};

const AboutPanel = ( {
	aboutInfo,
	settings,
	readerThemes,
	contentWidths,
	fontSizes,
	selectedPostTypes,
	onConfigure,
} ) => (
	<div className="wpdfv-about">
		<section className="wpdfv-about-hero">
			<div className="wpdfv-about-hero__copy">
				<p className="wpdfv-eyebrow">
					{ __( 'Frontend Reader Mode', 'wp-distraction-free-view' ) }
				</p>
				<h2>
					{ __(
						'Give visitors a calmer way to read.',
						'wp-distraction-free-view'
					) }
				</h2>
				<p>
					{ __(
						'WP Distraction Free View turns long-form content into a focused reading experience without changing the original theme layout.',
						'wp-distraction-free-view'
					) }
				</p>
				<div className="wpdfv-about-hero__actions">
					<Button variant="primary" onClick={ onConfigure }>
						{ __(
							'Configure Reader Mode',
							'wp-distraction-free-view'
						) }
					</Button>
					<ExternalLink href={ DOCUMENTATION_URL }>
						{ __( 'Read the guide', 'wp-distraction-free-view' ) }
					</ExternalLink>
				</div>
			</div>
			<div
				className="wpdfv-about-hero__sample"
				aria-label={ __(
					'Sample Reader Mode appearance',
					'wp-distraction-free-view'
				) }
			>
				<p className="wpdfv-eyebrow">
					{ __(
						'Reader Mode · 4 min read',
						'wp-distraction-free-view'
					) }
				</p>
				<h3>
					{ __(
						'Make room for the story.',
						'wp-distraction-free-view'
					) }
				</h3>
				<p>
					{ __(
						'A quiet space for the words that matter. Comfortable typography, thoughtful controls, and fewer distractions.',
						'wp-distraction-free-view'
					) }
				</p>
				<span>
					{ __( 'Sample content', 'wp-distraction-free-view' ) }
				</span>
			</div>
		</section>

		<div className="wpdfv-about-layout">
			<InfoPanel
				title={ __(
					'Start with three simple steps',
					'wp-distraction-free-view'
				) }
			>
				<ol className="wpdfv-steps">
					<li>
						<strong>
							{ __(
								'Choose your content',
								'wp-distraction-free-view'
							) }
						</strong>
						<span>
							{ __(
								'Enable Reader Mode for posts, pages, or selected public post types.',
								'wp-distraction-free-view'
							) }
						</span>
					</li>
					<li>
						<strong>
							{ __(
								'Add an entry point',
								'wp-distraction-free-view'
							) }
						</strong>
						<span>
							{ __(
								'Choose automatic placement or add the Reader Mode Toggle block.',
								'wp-distraction-free-view'
							) }
						</span>
					</li>
					<li>
						<strong>
							{ __(
								'Set the reading experience',
								'wp-distraction-free-view'
							) }
						</strong>
						<span>
							{ __(
								'Pick a default theme, text size, and comfortable content width.',
								'wp-distraction-free-view'
							) }
						</span>
					</li>
				</ol>
				<p className="wpdfv-about-shortcode">
					{ __(
						'Need manual placement?',
						'wp-distraction-free-view'
					) }{ ' ' }
					<code>[wpdfv]</code>
				</p>
			</InfoPanel>
			<InfoPanel
				title={ __( 'Current defaults', 'wp-distraction-free-view' ) }
			>
				<dl className="wpdfv-compact-details">
					<div>
						<dt>{ __( 'Theme', 'wp-distraction-free-view' ) }</dt>
						<dd>
							{ getOptionLabel(
								readerThemes,
								settings.default_reader_theme
							) }
						</dd>
					</div>
					<div>
						<dt>
							{ __(
								'Content width',
								'wp-distraction-free-view'
							) }
						</dt>
						<dd>
							{ getOptionLabel(
								contentWidths,
								settings.default_content_width
							) }
						</dd>
					</div>
					<div>
						<dt>
							{ __( 'Font size', 'wp-distraction-free-view' ) }
						</dt>
						<dd>
							{ getOptionLabel(
								fontSizes,
								settings.default_font_size
							) }
						</dd>
					</div>
					<div>
						<dt>
							{ __(
								'Reading progress',
								'wp-distraction-free-view'
							) }
						</dt>
						<dd>
							{ settings.reading_progress_enabled
								? __( 'On', 'wp-distraction-free-view' )
								: __( 'Off', 'wp-distraction-free-view' ) }
						</dd>
					</div>
					<div>
						<dt>
							{ __(
								'Content types',
								'wp-distraction-free-view'
							) }
						</dt>
						<dd>
							{ sprintf(
								/* translators: %d: Number of enabled post types. */ __(
									'%d enabled',
									'wp-distraction-free-view'
								),
								selectedPostTypes.length
							) }
						</dd>
					</div>
				</dl>
			</InfoPanel>
		</div>

		<dl className="wpdfv-about-facts">
			<div>
				<dt>{ __( 'Shortcode', 'wp-distraction-free-view' ) }</dt>
				<dd>
					<code>[wpdfv]</code>
				</dd>
			</div>
			<div>
				<dt>{ __( 'Plugin version', 'wp-distraction-free-view' ) }</dt>
				<dd>{ aboutInfo.pluginVersion }</dd>
			</div>
			<div>
				<dt>
					{ __( 'Minimum WordPress', 'wp-distraction-free-view' ) }
				</dt>
				<dd>{ aboutInfo.minimumWordPress }</dd>
			</div>
			<div>
				<dt>{ __( 'Minimum PHP', 'wp-distraction-free-view' ) }</dt>
				<dd>{ aboutInfo.minimumPhp }</dd>
			</div>
		</dl>
	</div>
);

const MorePluginsPanel = ( {
	plugins,
	activePluginAction,
	onPluginAction,
} ) => {
	const freePlugins = plugins.free || [];
	const paidPlugins = plugins.paid || [];

	return (
		<div className="wpdfv-more-plugins">
			<ScreenIntro
				title={ __(
					'More tools for your WordPress site',
					'wp-distraction-free-view'
				) }
				description={ __(
					'Optional companion plugins for performance, cleaner links, and focused site experiences.',
					'wp-distraction-free-view'
				) }
			/>

			<PluginSection
				title={ __( 'Free plugins', 'wp-distraction-free-view' ) }
				description={ __(
					'Install or activate WordPress.org plugins without leaving this screen.',
					'wp-distraction-free-view'
				) }
			>
				<div className="wpdfv-plugin-grid">
					{ freePlugins.map( ( plugin ) => (
						<FreePluginCard
							key={ plugin.slug }
							plugin={ plugin }
							activePluginAction={ activePluginAction }
							onPluginAction={ onPluginAction }
						/>
					) ) }
				</div>
			</PluginSection>

			<PluginSection
				title={ __( 'Paid plugins', 'wp-distraction-free-view' ) }
				description={ __(
					'Premium products for stronger protection and controlled theme experiences.',
					'wp-distraction-free-view'
				) }
			>
				<div className="wpdfv-plugin-grid">
					{ paidPlugins.map( ( plugin ) => (
						<PaidPluginCard key={ plugin.slug } plugin={ plugin } />
					) ) }
				</div>
			</PluginSection>
			<p className="wpdfv-plugin-footnote">
				{ __(
					'All companion plugins are optional. Reader Mode works independently.',
					'wp-distraction-free-view'
				) }
			</p>
		</div>
	);
};

const PluginSection = ( { title, description, children } ) => (
	<section className="wpdfv-plugin-section">
		<div className="wpdfv-plugin-section__header">
			<h3>{ title }</h3>
			<p className="wpdfv-plugin-section__description">{ description }</p>
		</div>
		{ children }
	</section>
);

const FreePluginCard = ( { plugin, activePluginAction, onPluginAction } ) => {
	const action = getFreePluginAction( plugin );
	const isBusy = action
		? activePluginAction === `${ plugin.slug }:${ action }`
		: false;

	return (
		<article className="wpdfv-plugin-card">
			<span className="wpdfv-plugin-card__mark" aria-hidden="true">
				{ plugin.label.charAt( 0 ) }
			</span>
			<div className="wpdfv-plugin-card__content">
				<h4>{ plugin.label }</h4>
				<p className="wpdfv-plugin-card__description">
					{ plugin.description }
				</p>
			</div>
			<div className="wpdfv-plugin-card__links">
				<ExternalLink
					className="wpdfv-plugin-link"
					href={ plugin.wordpressUrl }
				>
					{ __( 'WordPress.org', 'wp-distraction-free-view' ) }
				</ExternalLink>
				{ plugin.websiteUrl && (
					<ExternalLink
						className="wpdfv-plugin-link"
						href={ plugin.websiteUrl }
					>
						{ __( 'Website', 'wp-distraction-free-view' ) }
					</ExternalLink>
				) }
			</div>
			<div className="wpdfv-plugin-card__actions">
				<FreePluginAction
					action={ action }
					isBusy={ isBusy }
					plugin={ plugin }
					onPluginAction={ onPluginAction }
				/>
			</div>
		</article>
	);
};

const PaidPluginCard = ( { plugin } ) => (
	<article className="wpdfv-plugin-card">
		<span className="wpdfv-plugin-card__mark is-premium" aria-hidden="true">
			{ plugin.label.charAt( 0 ) }
		</span>
		<div className="wpdfv-plugin-card__content">
			<h4>{ plugin.label }</h4>
			<p className="wpdfv-plugin-card__description">
				{ plugin.description }
			</p>
		</div>
		<div className="wpdfv-plugin-card__actions">
			<ExternalLink
				className="wpdfv-plugin-link"
				href={ plugin.websiteUrl }
			>
				{ __( 'View website', 'wp-distraction-free-view' ) }
			</ExternalLink>
		</div>
	</article>
);

const FreePluginAction = ( { action, isBusy, plugin, onPluginAction } ) => {
	if ( ! action ) {
		return (
			<span className="wpdfv-plugin-status is-active">
				{ __( 'Active', 'wp-distraction-free-view' ) }
			</span>
		);
	}

	const isInstallAction = 'install' === action;
	const label = isInstallAction
		? __( 'Install', 'wp-distraction-free-view' )
		: __( 'Activate', 'wp-distraction-free-view' );
	const busyLabel = isInstallAction
		? __( 'Installing', 'wp-distraction-free-view' )
		: __( 'Activating', 'wp-distraction-free-view' );
	const isDisabled =
		isBusy ||
		( isInstallAction ? ! plugin.canInstall : ! plugin.canActivate );

	return (
		<Button
			variant={ isInstallAction ? 'primary' : 'secondary' }
			isBusy={ isBusy }
			disabled={ isDisabled }
			onClick={ () => onPluginAction( plugin.slug, action ) }
		>
			{ isBusy ? busyLabel : label }
		</Button>
	);
};

const getFreePluginAction = ( plugin ) => {
	if ( 'active' === plugin.status ) {
		return '';
	}

	if ( 'installed' === plugin.status ) {
		return 'activate';
	}

	return 'install';
};

const normalizeMorePlugins = ( plugins ) => {
	if ( Array.isArray( plugins ) ) {
		return {
			free: plugins.filter( ( plugin ) => 'paid' !== plugin.type ),
			paid: plugins.filter( ( plugin ) => 'paid' === plugin.type ),
		};
	}

	return {
		free: plugins?.free || [],
		paid: plugins?.paid || [],
	};
};

const ScreenIntro = ( { title, description } ) => (
	<header className="wpdfv-screen-intro">
		<h2>{ title }</h2>
		<p className="wpdfv-screen-intro__description">{ description }</p>
	</header>
);

const SettingsSection = ( { title, description, children } ) => (
	<section className="wpdfv-settings-panel">
		<div className="wpdfv-settings-panel__header">
			<h3>{ title }</h3>
			<p className="wpdfv-settings-panel__description">{ description }</p>
		</div>
		<div className="wpdfv-settings-panel__body">{ children }</div>
	</section>
);

const InfoPanel = ( { title, children } ) => (
	<section className="wpdfv-info-panel">
		<h3>{ title }</h3>
		{ children }
	</section>
);

const getOptionLabel = ( options, selectedValue ) => {
	const selectedOption = options.find(
		( option ) => option.value === selectedValue
	);

	return selectedOption?.label || selectedValue;
};

const root = document.getElementById( 'wpdfv-settings-app' );

if ( root ) {
	render( <SettingsApp />, root );
}
