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
	Spinner,
	TabPanel,
	TextControl,
} from '@wordpress/components';
import { render, useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { check } from '@wordpress/icons';

const SETTINGS_PATH = '/wp-distraction-free-view/v1/settings';

const SettingsApp = () => {
	const [ settings, setSettings ] = useState( null );
	const [ postTypes, setPostTypes ] = useState( [] );
	const [ displayLocations, setDisplayLocations ] = useState( [] );
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
	selectedPostTypes,
	isSaving,
	onTogglePostType,
	onUpdateSetting,
	onSave,
} ) => (
	<Card className="wpdfv-settings-card">
		<CardHeader>
			<div>
				<h2>{ __( 'Reader button', 'wp-distraction-free-view' ) }</h2>
				<p>
					{ __(
						'Control where the distraction free reader appears and how the trigger is labelled.',
						'wp-distraction-free-view'
					) }
				</p>
			</div>
		</CardHeader>
		<CardBody>
			<div className="wpdfv-settings-grid">
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
							'Display location',
							'wp-distraction-free-view'
						) }
						selected={ settings.display_location }
						options={ displayLocations }
						onChange={ ( value ) =>
							onUpdateSetting( 'display_location', value )
						}
					/>
				</section>

				<section className="wpdfv-settings-section">
					<TextControl
						__next40pxDefaultSize
						label={ __(
							'Button text',
							'wp-distraction-free-view'
						) }
						value={ settings.button_text }
						onChange={ ( value ) =>
							onUpdateSetting( 'button_text', value )
						}
						help={ __(
							'This text is shown on the frontend reader trigger and shortcode output.',
							'wp-distraction-free-view'
						) }
					/>
				</section>
			</div>

			<Flex className="wpdfv-settings-actions" justify="flex-end">
				<FlexItem>
					<Button
						variant="primary"
						icon={ check }
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
