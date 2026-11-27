const { expect, test } = require( '@playwright/test' );
const path = require( 'node:path' );

const coreScripts = process.env.WPDFV_WP_JS_DIR;

test( 'keeps edits made while a settings save is pending', async ( {
	page,
} ) => {
	test.skip(
		! coreScripts,
		'Set WPDFV_WP_JS_DIR to the WordPress wp-includes/js/dist directory.'
	);

	await page.route( '**/*', ( route ) =>
		route.request().isNavigationRequest()
			? route.fulfill( {
					contentType: 'text/html',
					body: '<div id="wpcontent"><div id="wpdfv-settings-app"></div></div>',
				} )
			: route.abort()
	);
	await page.goto( 'https://fixture.invalid/?wpdfv_tab=configure' );

	for ( const name of [
		'vendor/regenerator-runtime',
		'vendor/wp-polyfill',
		'vendor/lodash',
		'vendor/moment',
		'vendor/react',
		'vendor/react-dom',
		'dom-ready',
		'hooks',
		'i18n',
		'a11y',
		'deprecated',
		'dom',
		'escape-html',
		'element',
		'is-shallow-equal',
		'keycodes',
		'priority-queue',
		'compose',
		'date',
		'primitives',
		'redux-routine',
		'data',
		'rich-text',
		'warning',
		'components',
	] ) {
		await page.addScriptTag( {
			path: path.join( coreScripts, `${ name }.min.js` ),
		} );
		if ( 'vendor/lodash' === name ) {
			await page.evaluate( () => {
				window.lodash = window._.noConflict();
			} );
		}
	}
	await page.evaluate( () => {
		window.pendingSettingsSaves = [];
		window.wpdfvAdminSettings = { codeEditor: {} };
		window.wp.codeEditor = {
			initialize( textarea ) {
				let editorValue = textarea.value;
				let changeHandler;
				const editor = {
					getValue: () => editorValue,
					setValue( value ) {
						editorValue = value;
						changeHandler?.();
					},
					on( event, handler ) {
						changeHandler = handler;
					},
					off() {
						changeHandler = null;
					},
					toTextArea() {},
				};
				window.fixtureEditor = editor;
				return { codemirror: editor };
			},
		};
		window.wp.apiFetch = ( request ) => {
			const response = {
				settings: {
					button_text: 'Original',
					exit_button_text: 'Close',
					custom_css: '',
					where_to_display: [],
					display_location: 'before_content',
				},
				postTypes: [],
				displayLocations: [],
				readerThemes: [],
				contentWidths: [],
				fontSizes: [],
				modalTemplates: [],
				canEditCustomCss: true,
				morePlugins: { free: [], paid: [] },
			};
			if ( request.method === 'POST' ) {
				return new Promise( ( resolve ) => {
					window.pendingSettingsSaves.push( {
						data: request.data,
						resolve: () =>
							resolve( {
								...response,
								settings: {
									...request.data,
									exit_button_text: 'Normalized close',
									custom_css: (
										request.data.custom_css || ''
									).trim(),
								},
							} ),
					} );
				} );
			}
			return Promise.resolve( response );
		};
	} );
	await page.addScriptTag( {
		path: path.resolve( __dirname, '../../assets/dist/js/wpdfv-admin.js' ),
	} );

	const label = page.getByLabel( 'Open label' );
	await label.fill( 'First' );
	await page.getByRole( 'button', { name: 'Save settings' } ).first().click();
	await expect
		.poll( () => page.evaluate( () => window.pendingSettingsSaves.length ) )
		.toBe( 1 );
	await label.fill( 'Second' );
	await page.evaluate( () => window.pendingSettingsSaves[ 0 ].resolve() );
	await expect( label ).toHaveValue( 'Second' );
	await expect( page.getByLabel( 'Close label' ) ).toHaveValue(
		'Normalized close'
	);
	await expect(
		page.getByText( 'Unsaved changes', { exact: true } ).first()
	).toBeVisible();
	await expect(
		page
			.locator( '#wpdfv-settings-app' )
			.getByText( 'Settings saved. You have unsaved changes.' )
	).toBeVisible();

	await page.getByRole( 'button', { name: 'Save settings' } ).first().click();
	await expect
		.poll( () => page.evaluate( () => window.pendingSettingsSaves.length ) )
		.toBe( 2 );
	await expect
		.poll( () =>
			page.evaluate(
				() => window.pendingSettingsSaves[ 1 ].data.button_text
			)
		)
		.toBe( 'Second' );
	await page.evaluate( () => window.pendingSettingsSaves[ 1 ].resolve() );
	await expect(
		page.getByText( 'Unsaved changes', { exact: true } )
	).toHaveCount( 0 );

	await page.getByRole( 'button', { name: 'Advanced', exact: true } ).click();
	await page.waitForFunction( () => window.fixtureEditor );
	await page.evaluate( () =>
		window.fixtureEditor.setValue( '  .reader {}  ' )
	);
	await page.getByRole( 'button', { name: 'Save settings' } ).first().click();
	await expect
		.poll( () => page.evaluate( () => window.pendingSettingsSaves.length ) )
		.toBe( 3 );
	await page
		.getByRole( 'button', { name: 'General', exact: true } )
		.click();
	await label.fill( 'Third' );
	await page.getByRole( 'button', { name: 'Advanced', exact: true } ).click();
	await page.waitForFunction( () => window.fixtureEditor );
	await page.evaluate( () => window.pendingSettingsSaves[ 2 ].resolve() );
	await expect
		.poll( () => page.evaluate( () => window.fixtureEditor.getValue() ) )
		.toBe( '.reader {}' );
	await expect(
		page.getByText( 'Unsaved changes', { exact: true } ).first()
	).toBeVisible();
	await page
		.getByRole( 'button', { name: 'General', exact: true } )
		.click();
	await expect( label ).toHaveValue( 'Third' );
} );
