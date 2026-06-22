const { expect, test } = require( '@playwright/test' );

const hasSmokeBaseUrl = Boolean( process.env.WPDFV_SMOKE_BASE_URL );
const readerUrl =
	process.env.WPDFV_SMOKE_READER_PATH || process.env.WPDFV_SMOKE_BASE_URL;
const toggleSelector = '.wpdfv-fullscreen-btn.wpdfv-reader-toggle';
const modalSelector = '.wpdfv-reader-modal';
const storageKey = 'wpdfv_reader_preferences';

test.describe( 'Reader Mode smoke', () => {
	test.skip(
		! hasSmokeBaseUrl,
		'Set WPDFV_SMOKE_BASE_URL to a WordPress page with WP Distraction Free View active.'
	);

	test.beforeEach( async ( { page } ) => {
		await page.goto( readerUrl );
		await expect( page.locator( toggleSelector ).first() ).toBeVisible();
	} );

	test( 'opens and closes from the Reader Mode toggle', async ( { page } ) => {
		await openReader( page );
		await expect( page.locator( modalSelector ) ).toBeVisible();
		await expect( page.locator( 'html' ) ).toHaveClass(
			/wpdfv-reader-mode-active/
		);
		await expect( page.locator( 'body' ) ).toHaveClass(
			/wpdfv-reader-mode-active/
		);

		await page
			.getByRole( 'button', { name: /exit reader mode|close/i } )
			.click();

		await expect( page.locator( modalSelector ) ).toBeHidden();
		await expect( page.locator( 'html' ) ).not.toHaveClass(
			/wpdfv-reader-mode-active/
		);
		await expect( page.locator( 'body' ) ).not.toHaveClass(
			/wpdfv-reader-mode-active/
		);
	} );

	test( 'auto-opens when reader-mode query parameter is enabled', async ( {
		page,
	} ) => {
		await page.goto( withReaderModeQuery( readerUrl ) );

		await expect( page.locator( modalSelector ) ).toBeVisible();
		await expect( page.locator( 'html' ) ).toHaveClass(
			/wpdfv-reader-mode-active/
		);
	} );

	test( 'persists font size, theme, and width preferences', async ( {
		page,
	} ) => {
		await page.addInitScript(
			( key ) => window.localStorage.removeItem( key ),
			storageKey
		);
		await page.goto( readerUrl );
		await openReader( page );

		await page.getByRole( 'button', { name: 'Reader settings' } ).click();
		await page
			.locator( '.wpdfv-reader-settings-panel' )
			.getByRole( 'button', { name: 'Large' } )
			.click();
		await page
			.locator( '.wpdfv-reader-settings-panel' )
			.getByRole( 'button', { name: 'Dark' } )
			.click();
		await page
			.locator( '.wpdfv-reader-settings-panel' )
			.getByRole( 'button', { name: 'Wide' } )
			.click();

		await expect( page.locator( modalSelector ) ).toHaveClass(
			/wpdfv-reader-modal--font-large/
		);
		await expect( page.locator( modalSelector ) ).toHaveClass(
			/wpdfv-reader-modal--theme-dark/
		);
		await expect( page.locator( modalSelector ) ).toHaveClass(
			/wpdfv-reader-modal--width-wide/
		);

		await expect
			.poll( () =>
				page.evaluate( ( key ) => {
					const stored = window.localStorage.getItem( key );
					return stored ? JSON.parse( stored ) : null;
				}, storageKey )
			)
			.toEqual( {
				fontSize: 'large',
				theme: 'dark',
				width: 'wide',
			} );

		await page.reload();
		await openReader( page );

		await expect( page.locator( modalSelector ) ).toHaveClass(
			/wpdfv-reader-modal--font-large/
		);
		await expect( page.locator( modalSelector ) ).toHaveClass(
			/wpdfv-reader-modal--theme-dark/
		);
		await expect( page.locator( modalSelector ) ).toHaveClass(
			/wpdfv-reader-modal--width-wide/
		);
	} );

	test( 'shows reading progress only when enabled by frontend settings', async ( {
		page,
	} ) => {
		await openReader( page );

		const progressEnabled = await page.evaluate(
			() => window.wpdfvReaderMode?.readingProgressEnabled !== false
		);
		const progress = page.locator( '.wpdfv-reading-progress' );

		if ( progressEnabled ) {
			await expect( progress ).toBeVisible();
			return;
		}

		await expect( progress ).toHaveCount( 0 );
	} );

	test( 'keeps header actions usable in a mobile viewport', async ( {
		page,
	} ) => {
		await page.setViewportSize( { width: 390, height: 844 } );
		await page.goto( readerUrl );
		await openReader( page );

		const header = page.locator( '.components-modal__header' );
		const actions = page.locator( '.wpdfv-reader-header-actions' );

		await expect( header ).toBeVisible();
		await expect( actions ).toBeVisible();

		const headerBox = await header.boundingBox();
		const actionsBox = await actions.boundingBox();

		expect( headerBox ).not.toBeNull();
		expect( actionsBox ).not.toBeNull();
		expect( actionsBox.x + actionsBox.width ).toBeLessThanOrEqual(
			headerBox.x + headerBox.width + 1
		);
		expect( actionsBox.y + actionsBox.height ).toBeLessThanOrEqual(
			headerBox.y + headerBox.height + 1
		);
	} );

	test( 'keeps table of contents heading navigation keyboard accessible', async ( {
		page,
	} ) => {
		await openReader( page );

		const tocEnabled = await page.evaluate(
			() => window.wpdfvReaderMode?.readerTocEnabled !== false
		);
		const toc = page.locator( '.wpdfv-reader-toc' );

		if ( ! tocEnabled ) {
			await expect( toc ).toHaveCount( 0 );
			return;
		}

		await expect( toc ).toBeVisible();

		const firstLink = toc.getByRole( 'link' ).first();
		const href = await firstLink.getAttribute( 'href' );

		expect( href ).toMatch( /^#/ );

		await firstLink.focus();
		await page.keyboard.press( 'Enter' );

		await expect
			.poll( () =>
				page.evaluate(
					( targetId ) => document.activeElement?.id === targetId,
					href.slice( 1 )
				)
			)
			.toBe( true );
	} );

	test( 'uses reader-friendly print media output', async ( { page } ) => {
		await openReader( page );
		await page.emulateMedia( { media: 'print' } );

		await expect(
			page.locator( '.wpdfv-reader-print-header' )
		).toBeVisible();
		await expect(
			page.locator( '.wpdfv-reader-print-header h1' )
		).not.toBeEmpty();
		await expect(
			page.locator( '.wpdfv-reader-print-header' )
		).toContainText( /Source:/ );
		await expect(
			page.locator( '.wpdfv-reader-header-actions' )
		).toBeHidden();
		await expect( page.locator( '.wpdfv-reading-progress' ) ).toBeHidden();

		await page.emulateMedia( { media: 'screen' } );
	} );
} );

async function openReader( page ) {
	await page.locator( toggleSelector ).first().click();
	await expect( page.locator( modalSelector ) ).toBeVisible();
	await expect(
		page.locator( '.wpdfv-reader-loading, .wpdfv-reader-content' )
	).toBeVisible();
}

function withReaderModeQuery( path ) {
	const url = new URL( path, 'http://example.com' );
	url.searchParams.set( 'reader-mode', '1' );

	return path.startsWith( 'http' )
		? url.toString()
		: `${ url.pathname }${ url.search }${ url.hash }`;
}
