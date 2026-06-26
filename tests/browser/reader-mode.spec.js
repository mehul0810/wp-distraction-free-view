const { expect, test } = require( '@playwright/test' );

const hasSmokeBaseUrl = Boolean( process.env.WPDFV_SMOKE_BASE_URL );
const readerUrl =
	process.env.WPDFV_SMOKE_READER_PATH || process.env.WPDFV_SMOKE_BASE_URL;
const toggleSelector = '.wpdfv-fullscreen-btn.wpdfv-reader-toggle';
const modalSelector = '.wpdfv-reader-modal';
const storageKey = 'wpdfv_reader_preferences';
const positionsStorageKey = 'wpdfv_reader_positions';

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

	test( 'keeps the modal close target clickable without a hover tooltip layer', async ( {
		page,
	} ) => {
		await openReader( page );
		const closeButton = page
			.getByRole( 'button', { name: /exit reader mode|close/i } )
			.first();

		await expect( closeButton ).toHaveAttribute( 'aria-label', /.+/ );
		await expect( closeButton ).not.toHaveAttribute( 'title', /.+/ );

		await closeButton.hover();
		await closeButton.focus();
		await closeButton.click();

		await expect( page.locator( modalSelector ) ).toBeHidden();
	} );

	test( 'hydrates core accordion interactions inside the modal', async ( {
		page,
	} ) => {
		await openReader( page );
		await waitForReaderContent( page );

		const accordion = page
			.locator( `${ modalSelector } .wp-block-accordion` )
			.first();

		if ( 0 === ( await accordion.count() ) ) {
			test.skip(
				true,
				'Configured smoke page does not contain a core Accordion block.'
			);
		}

		const toggle = accordion
			.locator( '.wp-block-accordion-heading__toggle' )
			.first();
		await expect( toggle ).toBeVisible();

		const initialExpanded = await toggle.getAttribute( 'aria-expanded' );
		await toggle.click();

		await expect( toggle ).not.toHaveAttribute(
			'aria-expanded',
			initialExpanded || ''
		);
		await expect( page.locator( modalSelector ) ).toBeVisible();

		await page
			.getByRole( 'button', { name: /exit reader mode|close/i } )
			.click();
		await expect( page.locator( modalSelector ) ).toBeHidden();
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

	test( 'persists reader typography preferences', async ( {
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
		await page
			.locator( '.wpdfv-reader-settings-panel' )
			.getByRole( 'group', { name: 'Line height' } )
			.getByRole( 'button', { name: 'Spacious' } )
			.click();
		await page
			.locator( '.wpdfv-reader-settings-panel' )
			.getByRole( 'group', { name: 'Paragraph spacing' } )
			.getByRole( 'button', { name: 'Relaxed' } )
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
		await expect( page.locator( modalSelector ) ).toHaveClass(
			/wpdfv-reader-modal--line-height-spacious/
		);
		await expect( page.locator( modalSelector ) ).toHaveClass(
			/wpdfv-reader-modal--paragraph-spacing-relaxed/
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
				lineHeight: 'spacious',
				paragraphSpacing: 'relaxed',
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
		await expect( page.locator( modalSelector ) ).toHaveClass(
			/wpdfv-reader-modal--line-height-spacious/
		);
		await expect( page.locator( modalSelector ) ).toHaveClass(
			/wpdfv-reader-modal--paragraph-spacing-relaxed/
		);
	} );

	test( 'keeps expanded typography readable in a narrow viewport', async ( {
		page,
	} ) => {
		await page.setViewportSize( { width: 390, height: 844 } );
		await page.addInitScript(
			( key ) => window.localStorage.removeItem( key ),
			storageKey
		);
		await page.goto( readerUrl );
		await openReader( page );

		await page.getByRole( 'button', { name: 'Reader settings' } ).click();
		await page
			.locator( '.wpdfv-reader-settings-panel' )
			.getByRole( 'group', { name: 'Line height' } )
			.getByRole( 'button', { name: 'Spacious' } )
			.click();
		await page
			.locator( '.wpdfv-reader-settings-panel' )
			.getByRole( 'group', { name: 'Paragraph spacing' } )
			.getByRole( 'button', { name: 'Spacious' } )
			.click();

		const settingsPanel = page.locator( '.wpdfv-reader-settings-panel' );
		await expect( settingsPanel ).toBeVisible();

		const panelBox = await settingsPanel.boundingBox();
		expect( panelBox ).not.toBeNull();
		expect( panelBox.x ).toBeGreaterThanOrEqual( 0 );
		expect( panelBox.width ).toBeLessThanOrEqual( 390 );

		await page
			.getByRole( 'button', { name: /close reader settings/i } )
			.click();

		const layout = await page.evaluate( () => {
			const scrollContainer = document.querySelector(
				'.wpdfv-reader-modal .components-modal__content'
			);
			const content = document.querySelector( '.wpdfv-reader-content' );
			const paragraph = content?.querySelector( 'p' );
			const scrollBox = scrollContainer?.getBoundingClientRect();
			const paragraphBox = paragraph?.getBoundingClientRect();
			const contentStyle = content
				? window.getComputedStyle( content )
				: null;
			const paragraphStyle = paragraph
				? window.getComputedStyle( paragraph )
				: null;

			return {
				contentLineHeight: contentStyle?.lineHeight,
				paragraphMarginBottom: paragraphStyle?.marginBottom,
				paragraphInsideModal:
					Boolean( scrollBox && paragraphBox ) &&
					paragraphBox.left >= scrollBox.left &&
					paragraphBox.right <= scrollBox.right + 1,
				scrollsForExpandedSpacing:
					Boolean( scrollContainer ) &&
					scrollContainer.scrollHeight >= scrollContainer.clientHeight,
			};
		} );

		expect( parseFloat( layout.contentLineHeight ) ).toBeGreaterThan( 30 );
		expect( parseFloat( layout.paragraphMarginBottom ) ).toBeGreaterThan(
			30
		);
		expect( layout.paragraphInsideModal ).toBe( true );
		expect( layout.scrollsForExpandedSpacing ).toBe( true );
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

	test( 'copies the generated Reader Mode link with in-modal feedback', async ( {
		page,
	} ) => {
		await page.addInitScript( () => {
			Object.defineProperty( window.navigator, 'clipboard', {
				configurable: true,
				value: undefined,
			} );

			document.execCommand = ( command ) => {
				if ( 'copy' !== command ) {
					return false;
				}

				window.__wpdfvCopiedText = document.activeElement?.value || '';

				return true;
			};
		} );
		await page.goto( readerUrl );
		await openReader( page );
		await waitForReaderContent( page );

		const sourceUrl = await getReaderSourceUrl( page );
		await page
			.getByRole( 'button', { name: 'Copy Reader Mode link' } )
			.click();

		await expect(
			page.getByText( 'Reader Mode link copied.' )
		).toBeVisible();
		await expect
			.poll( () =>
				page.evaluate( () => window.__wpdfvCopiedText || '' )
			)
			.toBe( withReaderModeQuery( sourceUrl ) );
	} );

	test( 'uses native sharing only when supported from the Reader action', async ( {
		page,
	} ) => {
		await page.addInitScript( () => {
			window.__wpdfvSharedData = null;

			Object.defineProperty( window.navigator, 'canShare', {
				configurable: true,
				value: ( data ) => Boolean( data?.url ),
			} );
			Object.defineProperty( window.navigator, 'share', {
				configurable: true,
				value: ( data ) => {
					window.__wpdfvSharedData = data;

					return Promise.resolve();
				},
			} );
		} );
		await page.goto( readerUrl );
		await openReader( page );
		await waitForReaderContent( page );

		const sourceUrl = await getReaderSourceUrl( page );
		await page
			.getByRole( 'button', { name: 'Share Reader Mode link' } )
			.click();

		await expect(
			page.getByText( 'Reader Mode link shared.' )
		).toBeVisible();
		await expect
			.poll( () =>
				page.evaluate( () => window.__wpdfvSharedData?.url || '' )
			)
			.toBe( withReaderModeQuery( sourceUrl ) );
	} );

	test( 'saves Reader Mode position per content item locally', async ( {
		page,
	} ) => {
		await enableReaderResume( page );
		const postId = await getReaderPostId( page );

		await page.evaluate(
			( key ) => window.localStorage.removeItem( key ),
			positionsStorageKey
		);
		await openReader( page );
		await waitForReaderContent( page );
		await scrollReaderTo( page, 520 );

		await expect
			.poll( () =>
				page.evaluate(
					( { key, id } ) => {
						const stored = window.localStorage.getItem( key );
						const parsed = stored ? JSON.parse( stored ) : null;

						return parsed?.entries?.[ id ]?.scrollTop || 0;
					},
					{ key: positionsStorageKey, id: postId }
				)
			)
			.toBeGreaterThan( 0 );
	} );

	test( 'offers resume reading from locally saved position', async ( {
		page,
	} ) => {
		await enableReaderResume( page );
		const postId = await getReaderPostId( page );

		await page.evaluate(
			( { key, id } ) =>
				window.localStorage.setItem(
					key,
					JSON.stringify( {
						entries: {
							[ id ]: {
								scrollTop: 480,
								progress: 35,
								updatedAt: Date.now(),
							},
						},
					} )
				),
			{ key: positionsStorageKey, id: postId }
		);

		await openReader( page );
		await waitForReaderContent( page );
		await expect( page.getByText( /Resume from 35%/ ) ).toBeVisible();
		await page.getByRole( 'button', { name: 'Resume reading' } ).click();

		await expect
			.poll( () => getReaderScrollTop( page ) )
			.toBeGreaterThanOrEqual( 120 );
		await expect( page.getByText( /Resume from 35%/ ) ).toHaveCount( 0 );
	} );

	test( 'clears dismissed and stale Reader Mode positions locally', async ( {
		page,
	} ) => {
		await enableReaderResume( page );
		const postId = await getReaderPostId( page );

		await page.evaluate(
			( { key, id } ) =>
				window.localStorage.setItem(
					key,
					JSON.stringify( {
						entries: {
							[ id ]: {
								scrollTop: 420,
								progress: 30,
								updatedAt: Date.now(),
							},
							stale: {
								scrollTop: 640,
								progress: 45,
								updatedAt: Date.now() - 40 * 24 * 60 * 60 * 1000,
							},
						},
					} )
				),
			{ key: positionsStorageKey, id: postId }
		);

		await openReader( page );
		await waitForReaderContent( page );
		await page.getByRole( 'button', { name: 'Start over' } ).click();

		await expect
			.poll( () =>
				page.evaluate( ( key ) => {
					const stored = window.localStorage.getItem( key );
					const parsed = stored ? JSON.parse( stored ) : null;

					return Object.keys( parsed?.entries || {} );
				}, positionsStorageKey )
			)
			.toEqual( [] );
		await expect( page.getByText( /Resume from/ ) ).toHaveCount( 0 );
	} );

	test( 'continues without resume UI when localStorage is blocked', async ( {
		page,
	} ) => {
		await page.addInitScript( () => {
			let config;

			Object.defineProperty( window, 'wpdfvReaderMode', {
				configurable: true,
				get() {
					return config;
				},
				set( value ) {
					config = {
						...value,
						readerResumeEnabled: true,
						positionsStorageKey: 'wpdfv_reader_positions',
					};
				},
			} );

			const blockedStorage = {
				getItem() {
					throw new Error( 'blocked' );
				},
				setItem() {
					throw new Error( 'blocked' );
				},
				removeItem() {
					throw new Error( 'blocked' );
				},
			};

			Object.defineProperty( window, 'localStorage', {
				configurable: true,
				value: blockedStorage,
			} );
		} );
		await page.goto( readerUrl );
		await openReader( page );
		await waitForReaderContent( page );
		await scrollReaderTo( page, 360 );

		await expect( page.locator( modalSelector ) ).toBeVisible();
		await expect( page.getByText( /Resume from/ ) ).toHaveCount( 0 );
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

async function enableReaderResume( page ) {
	await page.addInitScript( ( key ) => {
		let config;

		Object.defineProperty( window, 'wpdfvReaderMode', {
			configurable: true,
			get() {
				return config;
			},
			set( value ) {
				config = {
					...value,
					readerResumeEnabled: true,
					positionsStorageKey: key,
				};
			},
		} );
	}, positionsStorageKey );
	await page.goto( readerUrl );
	await expect( page.locator( toggleSelector ).first() ).toBeVisible();
}

async function getReaderPostId( page ) {
	const postId = await page.locator( toggleSelector ).first().getAttribute(
		'data-post-id'
	);

	expect( postId ).toBeTruthy();

	return postId;
}

async function waitForReaderContent( page ) {
	await expect( page.locator( '.wpdfv-reader-loading' ) ).toHaveCount( 0 );
	await expect( page.locator( '.wpdfv-reader-content' ) ).not.toBeEmpty();
}

async function getReaderSourceUrl( page ) {
	const sourceText = await page
		.locator( '.wpdfv-reader-print-header p' )
		.textContent();
	const sourceUrl = sourceText?.replace( /^Source:\s*/, '' );

	expect( sourceUrl ).toBeTruthy();

	return sourceUrl;
}

async function scrollReaderTo( page, scrollTop ) {
	await page.evaluate( ( nextScrollTop ) => {
		document
			.querySelector( '.wpdfv-reader-modal .components-modal__content' )
			?.scrollTo( 0, nextScrollTop );
	}, scrollTop );
}

async function getReaderScrollTop( page ) {
	return page.evaluate(
		() =>
			document.querySelector(
				'.wpdfv-reader-modal .components-modal__content'
			)?.scrollTop || 0
	);
}
