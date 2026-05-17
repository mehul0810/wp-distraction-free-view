import '../../css/admin/main.scss';

import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	CheckboxControl,
	ExternalLink,
	Flex,
	FlexBlock,
	FlexItem,
	Notice,
	RadioControl,
	SelectControl,
	Spinner,
	TabPanel,
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
import { __ } from '@wordpress/i18n';
import { Path, SVG } from '@wordpress/primitives';

const SETTINGS_PATH = '/wp-distraction-free-view/v1/settings';
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
	const [ recommendedPlugins, setRecommendedPlugins ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		apiFetch( { path: SETTINGS_PATH } )
			.then( ( response ) => {
				setSettings( response.settings );
				setPostTypes( response.postTypes );
				setDisplayLocations( response.displayLocations );
				setReaderThemes( response.readerThemes );
				setContentWidths( response.contentWidths );
				setFontSizes( response.fontSizes );
				setModalTemplates( response.modalTemplates );
				setRecommendedPlugins( response.recommendedPlugins );
			} )
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
				setSettings( response.settings );
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

	if ( isLoading ) {
		return (
			<div className="wpdfv-settings-app wpdfv-settings-app--loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="wpdfv-settings-app">
			{ notice && (
				<Notice
					status={ notice.status }
					onRemove={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<TabPanel
				className="wpdfv-settings-tabs"
				activeClass="is-active"
				tabs={ [
					{
						name: 'settings',
						title: __( 'Settings', 'wp-distraction-free-view' ),
					},
					{
						name: 'recommended',
						title: __(
							'Recommended Plugins',
							'wp-distraction-free-view'
						),
					},
				] }
			>
				{ ( tab ) =>
					'settings' === tab.name ? (
						<SettingsPanel
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
					) : (
						<RecommendedPlugins plugins={ recommendedPlugins } />
					)
				}
			</TabPanel>
		</div>
	);
};

const SettingsPanel = ( {
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
} ) => (
	<Card className="wpdfv-settings-card">
		<CardHeader>
			<div>
				<h2>{ __( 'Reader Mode', 'wp-distraction-free-view' ) }</h2>
				<p>
					{ __(
						'Configure the frontend reading experience shown to visitors.',
						'wp-distraction-free-view'
					) }
				</p>
			</div>
		</CardHeader>
		<CardBody>
			<div className="wpdfv-settings-grid">
				<section className="wpdfv-settings-section">
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
								'manual_only' === settings.display_location
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
				</section>

				<section className="wpdfv-settings-section">
					<h3>{ __( 'Post types', 'wp-distraction-free-view' ) }</h3>
					<p>
						{ __(
							'Choose the public content types where the automatic reader button should appear.',
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
									onTogglePostType( postType.slug, value )
								}
							/>
						) ) }
					</div>
				</section>

				<section className="wpdfv-settings-section">
					<RadioControl
						label={ __(
							'Toggle placement',
							'wp-distraction-free-view'
						) }
						selected={ settings.display_location }
						options={ displayLocations }
						onChange={ ( value ) => {
							onUpdateSetting( 'display_location', value );
							onUpdateSetting(
								'automatic_button_enabled',
								'manual_only' !== value
							);
						} }
					/>
				</section>

				<section className="wpdfv-settings-section">
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
							'Default text for the frontend Reader Mode toggle and shortcode output.',
							'wp-distraction-free-view'
						) }
					/>
				</section>

				<section className="wpdfv-settings-section">
					<TextControl
						__next40pxDefaultSize
						label={ __( 'Exit label', 'wp-distraction-free-view' ) }
						value={ settings.exit_button_text }
						onChange={ ( value ) =>
							onUpdateSetting( 'exit_button_text', value )
						}
						help={ __(
							'Accessible label for the Reader Mode close control.',
							'wp-distraction-free-view'
						) }
					/>
				</section>

				<section className="wpdfv-settings-section">
					<ToggleControl
						label={ __(
							'Show reading progress',
							'wp-distraction-free-view'
						) }
						checked={ settings.reading_progress_enabled }
						onChange={ ( value ) =>
							onUpdateSetting( 'reading_progress_enabled', value )
						}
					/>
				</section>

				<section className="wpdfv-settings-section">
					<ToggleControl
						label={ __(
							'Show estimated reading time',
							'wp-distraction-free-view'
						) }
						checked={ settings.reading_time_enabled }
						onChange={ ( value ) =>
							onUpdateSetting( 'reading_time_enabled', value )
						}
					/>
				</section>

				<section className="wpdfv-settings-section">
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
							'Visitors can choose font size, theme, and content width. Preferences are saved only in their browser.',
							'wp-distraction-free-view'
						) }
					/>
				</section>

				<section className="wpdfv-settings-section">
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
							onUpdateSetting( 'default_reader_theme', value )
						}
					/>
				</section>

				<section className="wpdfv-settings-section">
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
							onUpdateSetting( 'default_content_width', value )
						}
					/>
				</section>

				<section className="wpdfv-settings-section">
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
							onUpdateSetting( 'default_font_size', value )
						}
					/>
				</section>

				<section className="wpdfv-settings-section">
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
						help={ __(
							'Choose the block-based template used inside Reader Mode.',
							'wp-distraction-free-view'
						) }
					/>
				</section>
			</div>

			<Flex className="wpdfv-settings-actions" justify="flex-end">
				<FlexItem>
					<Button
						variant="primary"
						icon={ checkIcon }
						isBusy={ isSaving }
						disabled={ isSaving }
						onClick={ onSave }
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
		</CardBody>
	</Card>
);

const RecommendedPlugins = ( { plugins } ) => (
	<div className="wpdfv-recommended-grid">
		{ plugins.map( ( plugin ) => (
			<Card key={ plugin.url } className="wpdfv-plugin-card">
				<CardHeader>
					<h2>{ plugin.label }</h2>
				</CardHeader>
				<CardBody>
					<Flex align="flex-start" gap={ 4 }>
						<FlexBlock>
							<p>{ plugin.description }</p>
						</FlexBlock>
						<FlexItem>
							<ExternalLink href={ plugin.url }>
								{ __(
									'View plugin',
									'wp-distraction-free-view'
								) }
							</ExternalLink>
						</FlexItem>
					</Flex>
				</CardBody>
			</Card>
		) ) }
	</div>
);

const root = document.getElementById( 'wpdfv-settings-app' );

if ( root ) {
	render( <SettingsApp />, root );
}
