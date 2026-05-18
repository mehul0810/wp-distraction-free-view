import '../../css/admin/main.scss';

import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	CheckboxControl,
	ExternalLink,
	Flex,
	FlexItem,
	Notice,
	RadioControl,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import {
	createElement,
	render,
	useEffect,
	useMemo,
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

	const applySettingsResponse = ( response ) => {
		setSettings( response.settings );
		setPostTypes( response.postTypes );
		setDisplayLocations( response.displayLocations );
		setReaderThemes( response.readerThemes );
		setContentWidths( response.contentWidths );
		setFontSizes( response.fontSizes );
		setModalTemplates( response.modalTemplates );
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
	};

	useEffect( () => {
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
	}, [] );

	const selectedPostTypes = useMemo(
		() => settings?.where_to_display || [],
		[ settings ]
	);

	const updateSetting = ( key, value ) => {
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
				applySettingsResponse( response );
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

	if ( isLoading ) {
		return (
			<div className="wpdfv-settings-app wpdfv-settings-app--loading">
				<Spinner />
			</div>
		);
	}

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
					selectedPostTypes={ selectedPostTypes }
					isSaving={ isSaving }
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
			/>
		);
	};

	return (
		<div className="wpdfv-settings-app">
			<SettingsHeader
				tabs={ tabs }
				activeTab={ activeTab }
				brandIconUrl={ aboutInfo.brandIconUrl }
				pluginVersion={ aboutInfo.pluginVersion }
				onChangeTab={ setActiveTab }
			/>

			<main className="wpdfv-settings-content">
				{ notice && (
					<Notice
						status={ notice.status }
						onRemove={ () => setNotice( null ) }
					>
						{ notice.message }
					</Notice>
				) }

				<section
					className="wpdfv-settings-tab-panel"
					id={ `wpdfv-panel-${ activeTab }` }
					role="tabpanel"
					aria-labelledby={ `wpdfv-tab-${ activeTab }` }
				>
					{ renderActivePanel() }
				</section>
			</main>
		</div>
	);
};

const SettingsHeader = ( {
	tabs,
	activeTab,
	brandIconUrl,
	pluginVersion,
	onChangeTab,
} ) => (
	<header className="wpdfv-settings-header">
		<div className="wpdfv-settings-header__inner">
			<div className="wpdfv-settings-header__brand-row">
				<div className="wpdfv-settings-brand">
					<span
						className="wpdfv-settings-brand__mark"
						aria-hidden="true"
					>
						{ brandIconUrl && (
							<img src={ brandIconUrl } alt="" loading="eager" />
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
							{ __( 'Free View', 'wp-distraction-free-view' ) }
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
					{ tabs.map( ( tab ) => {
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
								onClick={ () => onChangeTab( tab.name ) }
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

const ConfigurePanel = ( {
	settings,
	postTypes,
	displayLocations,
	readerThemes,
	contentWidths,
	fontSizes,
	modalTemplates,
	selectedPostTypes,
	isSaving,
	onTogglePostType,
	onUpdateSetting,
	onSave,
} ) => {
	const enabledPostTypeCount = selectedPostTypes.length;
	const automaticModeLabel = settings.automatic_button_enabled
		? __( 'Automatic toggle is enabled', 'wp-distraction-free-view' )
		: __( 'Manual placement only', 'wp-distraction-free-view' );

	return (
		<form
			className="wpdfv-configure"
			onSubmit={ ( event ) => {
				event.preventDefault();
				onSave();
			} }
		>
			<ScreenIntro
				eyebrow={ __( 'Configure', 'wp-distraction-free-view' ) }
				title={ __(
					'Tune the reading experience',
					'wp-distraction-free-view'
				) }
				description={ __(
					'Choose where Reader Mode is available, how visitors open it, and which reading preferences are shown.',
					'wp-distraction-free-view'
				) }
			/>

			<div className="wpdfv-configure-layout">
				<aside
					className="wpdfv-status-panel"
					aria-label={ __(
						'Reader Mode summary',
						'wp-distraction-free-view'
					) }
				>
					<p className="wpdfv-status-panel__eyebrow">
						{ __( 'Current setup', 'wp-distraction-free-view' ) }
					</p>
					<h3>{ automaticModeLabel }</h3>
					<dl>
						<div>
							<dt>
								{ __(
									'Content types',
									'wp-distraction-free-view'
								) }
							</dt>
							<dd>
								{ sprintf(
									/* translators: %d: Number of enabled post types. */
									__(
										'%d enabled',
										'wp-distraction-free-view'
									),
									enabledPostTypeCount
								) }
							</dd>
						</div>
						<div>
							<dt>
								{ __(
									'Placement',
									'wp-distraction-free-view'
								) }
							</dt>
							<dd>
								{ getDisplayLocationLabel(
									displayLocations,
									settings.display_location
								) }
							</dd>
						</div>
						<div>
							<dt>
								{ __(
									'Shortcode',
									'wp-distraction-free-view'
								) }
							</dt>
							<dd>
								<code>[wpdfv]</code>
							</dd>
						</div>
					</dl>
					<Button
						variant="primary"
						icon={ checkIcon }
						isBusy={ isSaving }
						disabled={ isSaving }
						type="submit"
					>
						{ isSaving
							? __( 'Saving', 'wp-distraction-free-view' )
							: __(
									'Save settings',
									'wp-distraction-free-view'
							  ) }
					</Button>
				</aside>

				<div className="wpdfv-settings-panels">
					<SettingsSection
						title={ __(
							'Availability and placement',
							'wp-distraction-free-view'
						) }
						description={ __(
							'Control where Reader Mode is available and how visitors enter it.',
							'wp-distraction-free-view'
						) }
					>
						<div className="wpdfv-field-row">
							<ToggleControl
								label={ __(
									'Automatically insert reader toggle',
									'wp-distraction-free-view'
								) }
								checked={ settings.automatic_button_enabled }
								onChange={ ( value ) => {
									onUpdateSetting(
										'automatic_button_enabled',
										value
									);
									if ( ! value ) {
										onUpdateSetting(
											'display_location',
											'manual_only'
										);
									} else if (
										'manual_only' ===
										settings.display_location
									) {
										onUpdateSetting(
											'display_location',
											'after_content'
										);
									}
								} }
								help={ __(
									'Leave this off when you only want to place the toggle with the block or shortcode.',
									'wp-distraction-free-view'
								) }
							/>
						</div>

						<div className="wpdfv-field-row">
							<h4>
								{ __(
									'Post types',
									'wp-distraction-free-view'
								) }
							</h4>
							<p className="wpdfv-field-row__description">
								{ __(
									'Choose the public content types where Reader Mode should be available.',
									'wp-distraction-free-view'
								) }
							</p>
							<div className="wpdfv-checkbox-list">
								{ postTypes.map( ( postType ) => (
									<CheckboxControl
										key={ postType.slug }
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
								) ) }
							</div>
						</div>

						<div className="wpdfv-field-row">
							<RadioControl
								label={ __(
									'Toggle placement',
									'wp-distraction-free-view'
								) }
								selected={ settings.display_location }
								options={ displayLocations }
								onChange={ ( value ) => {
									onUpdateSetting(
										'display_location',
										value
									);
									onUpdateSetting(
										'automatic_button_enabled',
										'manual_only' !== value
									);
								} }
							/>
						</div>

						<div className="wpdfv-field-grid">
							<TextControl
								__next40pxDefaultSize
								label={ __(
									'Toggle label',
									'wp-distraction-free-view'
								) }
								value={ settings.button_text }
								onChange={ ( value ) =>
									onUpdateSetting( 'button_text', value )
								}
								help={ __(
									'Shown on the frontend Reader Mode toggle and shortcode output.',
									'wp-distraction-free-view'
								) }
							/>
							<TextControl
								__next40pxDefaultSize
								label={ __(
									'Exit label',
									'wp-distraction-free-view'
								) }
								value={ settings.exit_button_text }
								onChange={ ( value ) =>
									onUpdateSetting( 'exit_button_text', value )
								}
								help={ __(
									'Accessible label for the Reader Mode close control.',
									'wp-distraction-free-view'
								) }
							/>
						</div>
					</SettingsSection>

					<SettingsSection
						title={ __(
							'Reading tools',
							'wp-distraction-free-view'
						) }
						description={ __(
							'Choose the lightweight tools visitors can use while reading.',
							'wp-distraction-free-view'
						) }
					>
						<div className="wpdfv-field-grid">
							<ToggleControl
								label={ __(
									'Show reading progress',
									'wp-distraction-free-view'
								) }
								checked={ settings.reading_progress_enabled }
								onChange={ ( value ) =>
									onUpdateSetting(
										'reading_progress_enabled',
										value
									)
								}
							/>
							<ToggleControl
								label={ __(
									'Show estimated reading time',
									'wp-distraction-free-view'
								) }
								checked={ settings.reading_time_enabled }
								onChange={ ( value ) =>
									onUpdateSetting(
										'reading_time_enabled',
										value
									)
								}
							/>
							<ToggleControl
								label={ __(
									'Show reader preference controls',
									'wp-distraction-free-view'
								) }
								checked={ settings.preference_controls_enabled }
								onChange={ ( value ) =>
									onUpdateSetting(
										'preference_controls_enabled',
										value
									)
								}
								help={ __(
									'Preferences are saved only in the visitor browser.',
									'wp-distraction-free-view'
								) }
							/>
						</div>

						<div className="wpdfv-field-grid">
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __(
									'Default reader theme',
									'wp-distraction-free-view'
								) }
								value={ settings.default_reader_theme }
								options={ readerThemes }
								onChange={ ( value ) =>
									onUpdateSetting(
										'default_reader_theme',
										value
									)
								}
							/>
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __(
									'Default content width',
									'wp-distraction-free-view'
								) }
								value={ settings.default_content_width }
								options={ contentWidths }
								onChange={ ( value ) =>
									onUpdateSetting(
										'default_content_width',
										value
									)
								}
							/>
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __(
									'Default font size',
									'wp-distraction-free-view'
								) }
								value={ settings.default_font_size }
								options={ fontSizes }
								onChange={ ( value ) =>
									onUpdateSetting(
										'default_font_size',
										value
									)
								}
							/>
						</div>
					</SettingsSection>

					<SettingsSection
						title={ __( 'Template', 'wp-distraction-free-view' ) }
						description={ __(
							'Select the block-based template used inside Reader Mode.',
							'wp-distraction-free-view'
						) }
					>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __(
								'Reader template',
								'wp-distraction-free-view'
							) }
							value={ settings.modal_template }
							options={ modalTemplates }
							onChange={ ( value ) =>
								onUpdateSetting( 'modal_template', value )
							}
						/>
					</SettingsSection>

					<Flex className="wpdfv-settings-actions" justify="flex-end">
						<FlexItem>
							<Button
								variant="primary"
								icon={ checkIcon }
								isBusy={ isSaving }
								disabled={ isSaving }
								type="submit"
							>
								{ isSaving
									? __( 'Saving', 'wp-distraction-free-view' )
									: __(
											'Save settings',
											'wp-distraction-free-view'
									  ) }
							</Button>
						</FlexItem>
					</Flex>
				</div>
			</div>
		</form>
	);
};

const AboutPanel = ( {
	aboutInfo,
	settings,
	readerThemes,
	contentWidths,
	fontSizes,
	selectedPostTypes,
} ) => (
	<div className="wpdfv-about">
		<section className="wpdfv-hero-panel">
			<div>
				<p className="wpdfv-eyebrow">
					{ __( 'Frontend Reader Mode', 'wp-distraction-free-view' ) }
				</p>
				<h2>
					{ __(
						'Give visitors a calmer way to read.',
						'wp-distraction-free-view'
					) }
				</h2>
				<p className="wpdfv-hero-panel__description">
					{ __(
						'WP Distraction Free View turns long-form content into a focused reading experience without changing the original theme layout.',
						'wp-distraction-free-view'
					) }
				</p>
			</div>
			<dl className="wpdfv-hero-metrics">
				<div>
					<dt>{ __( 'Version', 'wp-distraction-free-view' ) }</dt>
					<dd>{ aboutInfo.pluginVersion }</dd>
				</div>
				<div>
					<dt>{ __( 'Shortcode', 'wp-distraction-free-view' ) }</dt>
					<dd>
						<code>[wpdfv]</code>
					</dd>
				</div>
				<div>
					<dt>{ __( 'Post types', 'wp-distraction-free-view' ) }</dt>
					<dd>
						{ sprintf(
							/* translators: %d: Number of enabled post types. */
							__( '%d enabled', 'wp-distraction-free-view' ),
							selectedPostTypes.length
						) }
					</dd>
				</div>
			</dl>
		</section>

		<div className="wpdfv-about-layout">
			<InfoPanel
				title={ __( 'Quick start', 'wp-distraction-free-view' ) }
			>
				<ol className="wpdfv-steps">
					<li>
						{ __(
							'Enable Reader Mode for the content types readers use most.',
							'wp-distraction-free-view'
						) }
					</li>
					<li>
						{ __(
							'Choose automatic placement, or place the Reader Mode Toggle block manually.',
							'wp-distraction-free-view'
						) }
					</li>
					<li>
						{ __(
							'Use [wpdfv] anywhere a shortcode is the better fit.',
							'wp-distraction-free-view'
						) }
					</li>
				</ol>
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
						<dt>{ __( 'Width', 'wp-distraction-free-view' ) }</dt>
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
							{ __( 'Progress', 'wp-distraction-free-view' ) }
						</dt>
						<dd>
							{ settings.reading_progress_enabled
								? __( 'On', 'wp-distraction-free-view' )
								: __( 'Off', 'wp-distraction-free-view' ) }
						</dd>
					</div>
				</dl>
			</InfoPanel>

			<InfoPanel
				title={ __( 'Compatibility', 'wp-distraction-free-view' ) }
			>
				<dl className="wpdfv-compact-details">
					<div>
						<dt>
							{ __(
								'Minimum WordPress',
								'wp-distraction-free-view'
							) }
						</dt>
						<dd>{ aboutInfo.minimumWordPress }</dd>
					</div>
					<div>
						<dt>
							{ __( 'Minimum PHP', 'wp-distraction-free-view' ) }
						</dt>
						<dd>{ aboutInfo.minimumPhp }</dd>
					</div>
				</dl>
			</InfoPanel>
		</div>
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
				eyebrow={ __( 'More Plugins', 'wp-distraction-free-view' ) }
				title={ __(
					'Extend your WordPress reading stack',
					'wp-distraction-free-view'
				) }
				description={ __(
					'Install free companion plugins directly, or explore premium tools for focused WordPress workflows.',
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
		</div>
	);
};

const PluginSection = ( { title, description, children } ) => (
	<section className="wpdfv-plugin-section">
		<div className="wpdfv-plugin-section__header">
			<h3>{ title }</h3>
			<p>{ description }</p>
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
			<div className="wpdfv-plugin-card__content">
				<h4>{ plugin.label }</h4>
				<p className="wpdfv-plugin-card__description">
					{ plugin.description }
				</p>
			</div>
			<div className="wpdfv-plugin-card__links">
				<ExternalLink href={ plugin.wordpressUrl }>
					{ __( 'WordPress.org', 'wp-distraction-free-view' ) }
				</ExternalLink>
				{ plugin.websiteUrl && (
					<ExternalLink href={ plugin.websiteUrl }>
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
		<div className="wpdfv-plugin-card__content">
			<h4>{ plugin.label }</h4>
			<p className="wpdfv-plugin-card__description">
				{ plugin.description }
			</p>
		</div>
		<div className="wpdfv-plugin-card__actions">
			<ExternalLink href={ plugin.websiteUrl }>
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

const ScreenIntro = ( { eyebrow, title, description } ) => (
	<header className="wpdfv-screen-intro">
		<p className="wpdfv-eyebrow">{ eyebrow }</p>
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

const getDisplayLocationLabel = ( displayLocations, selectedValue ) => {
	const selectedLocation = displayLocations.find(
		( location ) => location.value === selectedValue
	);

	return selectedLocation?.label || selectedValue;
};

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
