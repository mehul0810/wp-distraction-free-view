const { expect, test } = require( '@playwright/test' );
const path = require( 'node:path' );

const coreScripts = process.env.WPDFV_WP_JS_DIR;

async function openFixture( page, content, options = {} ) {
	await page.route( '**/*', ( route ) =>
		route.request().isNavigationRequest()
			? route.fulfill( {
					contentType: 'text/html',
					body: '<button class="wpdfv-fullscreen-btn" data-post-id="11">Open</button>',
				} )
			: route.abort()
	);
	await page.goto( 'https://fixture.invalid/' );
	for ( const name of [
		'vendor/react',
		'vendor/react-dom',
		'hooks',
		'i18n',
		'escape-html',
		'element',
		'primitives',
	] ) {
		await page.addScriptTag( {
			path: path.join( coreScripts, `${ name }.min.js` ),
		} );
	}
	await page.evaluate(
		( fixture ) => {
			window.wpdfvReaderMode = fixture.options;
			window.wp.apiFetch = () =>
				Promise.resolve( {
					id: 11,
					title: 'Article',
					content: fixture.content,
					permalink: 'https://fixture.invalid/article',
					scripts: [],
					toc: fixture.options.toc || [],
				} );
		},
		{ content, options }
	);
	await page.addScriptTag( {
		path: path.resolve( __dirname, '../../assets/dist/js/wpdfv.js' ),
	} );
	await page.getByRole( 'button', { name: 'Open', exact: true } ).click();
	await expect( page.locator( '.wpdfv-reader-loading' ) ).toHaveCount( 0 );
}

test.describe( 'Reader behavior with WordPress scripts', () => {
	test.skip(
		! coreScripts,
		'Set WPDFV_WP_JS_DIR to the WordPress wp-includes/js/dist directory.'
	);

	test( 'preserves protected media and avoids quadratic containment checks', async ( {
		page,
	} ) => {
		const content =
			'<div class="media-container"><picture><img alt="nested"></picture></div>' +
			'<div class="media-container"><span class="protected">Protected child</span></div>' +
			'<div class="protected"><img alt="protected ancestor"></div>' +
			Array.from(
				{ length: 1000 },
				( _, index ) => `<img alt="Image ${ index }">`
			).join( '' );
		await openFixture( page, content, {
			contentSelectors: {
				media: [ 'img', 'picture', '.media-container' ],
				protected: [ '.protected' ],
			},
		} );
		await page.getByRole( 'button', { name: 'Reader settings' } ).click();
		await page.evaluate( () => {
			window.wpdfvContainsCalls = 0;
			const contains = Element.prototype.contains;
			Element.prototype.contains = function ( node ) {
				window.wpdfvContainsCalls += 1;
				return contains.call( this, node );
			};
		} );
		await page.getByLabel( 'Show images and media' ).uncheck();
		await expect(
			page.locator( '.wpdfv-reader-hidden-media' )
		).toHaveCount( 1001 );
		await expect( page.getByText( 'Protected child' ) ).toBeVisible();
		await expect(
			page.getByAltText( 'protected ancestor' )
		).not.toHaveClass( /wpdfv-reader-hidden-media/ );
		await expect( page.locator( '.media-container' ).first() ).toHaveClass(
			/wpdfv-reader-hidden-media/
		);
		expect(
			await page.evaluate( () => window.wpdfvContainsCalls )
		).toBeLessThan( 10000 );
	} );

	test( 'focuses the Reader heading when an earlier element has the same id', async ( {
		page,
	} ) => {
		await openFixture(
			page,
			'<p id="introduction">Start</p><h2 id="introduction">Introduction</h2><h2 id="conclusion">Conclusion</h2>',
			{
				readerTocEnabled: true,
				toc: [
					{ id: 'introduction', level: 2, text: 'Introduction' },
					{ id: 'conclusion', level: 2, text: 'Conclusion' },
				],
			}
		);
		await page.getByRole( 'link', { name: 'Introduction' } ).click();
		await expect(
			page.locator( '.wpdfv-reader-content h2#introduction' )
		).toBeFocused();
	} );
} );
