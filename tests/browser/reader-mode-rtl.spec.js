const { expect, test } = require( '@playwright/test' );

const fixtureUrl =
	process.env.WPDFV_RTL_FIXTURE_URL ||
	process.env.WPDFV_SMOKE_READER_PATH;
const hasFixtureUrl = Boolean( fixtureUrl );
const toggleSelector = '.wpdfv-fullscreen-btn.wpdfv-reader-toggle';
const modalSelector = '.wpdfv-reader-modal';
const requiredTexts = [
	'می‌خواهیم',
	'۱۲۳۴۵۶۷۸۹۰',
	'١٢٣٤٥٦٧٨٩٠',
	'السَّلَامُ عَلَيْكُمْ',
	'https://development.wp.local/?reader-mode=1',
	'نماد Reader Mode با caption فارسی',
	'WPDFV Embed Source',
	'const wpdfvFixture = "می‌خواهیم";',
	'RTL preformatted: می‌خوانیم',
];
const optionalPatternText = 'الگوی همگام Reader Mode';
const requiredSelectors = [
	'.wpdfv-reader-content [dir="rtl"]',
	'.wpdfv-reader-content .wp-block-heading',
	'.wpdfv-reader-content .wp-block-group',
	'.wpdfv-reader-content .wp-block-list',
	'.wpdfv-reader-content .wp-block-table table',
	'.wpdfv-reader-content .wp-block-image img',
	'.wpdfv-reader-content .wp-caption img',
	'.wpdfv-reader-content img.emoji[alt="👩‍💻"]',
	'.wpdfv-reader-content img.emoji[alt="👨‍👩‍👧‍👦"]',
	'.wpdfv-reader-content .wp-block-code code',
	'.wpdfv-reader-content .wp-block-preformatted',
];
const viewports = [
	{
		name: 'desktop',
		size: {
			width: 1440,
			height: 1600,
		},
	},
	{
		name: 'mobile',
		size: {
			width: 390,
			height: 844,
		},
	},
];

test.describe( 'Reader Mode RTL fixture proof', () => {
	test.skip(
		! hasFixtureUrl,
		'Set WPDFV_RTL_FIXTURE_URL to the provisioned Studio fixture URL.'
	);

	for ( const viewport of viewports ) {
		test( `${ viewport.name } renders RTL and Unicode fixture content with top and bottom toggles`, async ( {
			page,
		}, testInfo ) => {
			await page.setViewportSize( viewport.size );
			await page.goto( fixtureUrl );

			const toggles = page.locator( toggleSelector );
			await expect( toggles ).toHaveCount( 2 );

			const firstToggleBox = await toggles.nth( 0 ).boundingBox();
			const secondToggleBox = await toggles.nth( 1 ).boundingBox();

			expect( firstToggleBox ).not.toBeNull();
			expect( secondToggleBox ).not.toBeNull();
			expect( firstToggleBox.y ).toBeLessThan( secondToggleBox.y );

			const readerResponsePromise = page.waitForResponse(
				( response ) =>
					response.url().includes(
						'/wp-json/wp-distraction-free-view/v1/content/'
					) && 'GET' === response.request().method()
			);
			await toggles.first().click();
			const readerResponse = await readerResponsePromise;
			const payload = await readerResponse.json();

			expect( payload ).toMatchObject( {
				id: expect.any( Number ),
				title: expect.any( String ),
				permalink: expect.any( String ),
				content: expect.any( String ),
				scripts: expect.any( Array ),
				toc: expect.any( Array ),
				readingTime: {
					minutes: expect.any( Number ),
					label: expect.any( String ),
				},
			} );
			expect( payload.content ).toContain( 'می‌خواهیم' );
			expect( payload.content ).toContain( '👩‍💻' );
			expect( payload.content ).toContain( '👨‍👩‍👧‍👦' );
			expect( payload.content ).toContain( 'نماد Reader Mode با caption فارسی' );
			expect( payload.content ).toContain( 'RTL preformatted: می‌خوانیم' );

			await expect( page.locator( modalSelector ) ).toBeVisible();
			await expect( page.locator( '.wpdfv-reader-loading' ) ).toHaveCount(
				0
			);

			const readerContent = page.locator(
				`${ modalSelector } .wpdfv-reader-content`
			);
			await expect( readerContent ).toBeVisible();
			await expect(
				page.locator( `${ modalSelector } .wpdfv-reader-toggle` )
			).toHaveCount( 0 );
			await page.screenshot( {
				fullPage: true,
				path: testInfo.outputPath(
					`reader-mode-rtl-${ viewport.name }-open.png`
				),
			} );

			for ( const text of requiredTexts ) {
				await expect( readerContent ).toContainText( text );
			}

			if ( payload.content.includes( optionalPatternText ) ) {
				await expect( readerContent ).toContainText( optionalPatternText );
			}

			for ( const selector of requiredSelectors ) {
				await expect( page.locator( selector ).first() ).toBeVisible();
			}

		} );
	}
} );
