const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		wpdfv: path.resolve( __dirname, 'assets/src/js/frontend/wpdfv.js' ),
		'wpdfv-admin': path.resolve(
			__dirname,
			'assets/src/js/admin/wpdfv-admin.js'
		),
		'wpdfv-block': path.resolve(
			__dirname,
			'assets/src/js/block/reader-button.js'
		),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'assets/dist' ),
		filename: 'js/[name].js',
	},
};
