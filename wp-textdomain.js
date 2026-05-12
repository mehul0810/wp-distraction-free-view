const wpTextdomain = require( 'wp-textdomain' );

wpTextdomain( process.argv[ 2 ], {
	domain: 'wp-distraction-free-view',
	fix: true,
} );
