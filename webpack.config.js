/**
 * External dependencies
 */
const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const MiniCSSExtractPlugin = require( 'mini-css-extract-plugin' );
const ImageminPlugin = require( 'imagemin-webpack-plugin' ).default;
const CleanWebpackPlugin = require( 'clean-webpack-plugin' );
const WebpackRTLPlugin = require( 'webpack-rtl-plugin' );
const UglifyJsPlugin = require( 'uglifyjs-webpack-plugin' );
const wpPot = require( 'wp-pot' );

const inProduction = ( 'production' === process.env.NODE_ENV );
const mode = inProduction ? 'production' : 'development';

const config = {
	mode,

	entry: {
		'wpdfv': [ './assets/src/css/frontend/wpdfv.scss', './assets/src/js/frontend/wpdfv.js' ],
		'wpdfv-admin': [ './assets/src/css/admin/wpdfv-admin.scss', './assets/src/js/admin/wpdfv-admin.js' ],
	},
	output: {
		path: path.join( __dirname, './assets/dist/' ),
		filename: 'js/[name].js',
	},

	// Ensure modules like magnific know jQuery is external (loaded via WP).
	externals: {
		$: 'jQuery',
		jquery: 'jQuery',
		lodash: 'lodash',
	},
	devtool: ! inProduction ? 'source-map' : '',
	optimization: {
		minimizer: [
			new UglifyJsPlugin({
				test: /\.js(\?.*)?$/i,
				sourceMap: true,
			}),
		],
	},
	module: {
		rules: [

			// Create RTL styles.
			{
				test: /\.css$/,
				use: [
					MiniCSSExtractPlugin.loader,
					{
						loader: 'style-loader',
						options: {
							sourceMap: true,
						},
					},
				],
			},

			// SASS to CSS.
			{
				test: /\.scss$/,
				use: [
					MiniCSSExtractPlugin.loader,
					{
						loader: 'css-loader',
						options: {
							sourceMap: true,
						},
					},
					{
						loader: 'sass-loader',
						options: {
							sourceMap: true,
							outputStyle: ( inProduction ? 'compressed' : 'nested' ),
						},
					} ],
			},

			// Font files.
			{
				test: /\.(ttf|otf|eot|woff(2)?)(\?[a-z0-9]+)?$/,
				use: [
					{
						loader: 'file-loader',
						options: {
							name: 'fonts/[name].[ext]',
							publicPath: '../',
						},
					},
				],
			},

			// Image files.
			{
				test: /\.(png|jpe?g|gif|svg)$/,
				use: [
					{
						loader: 'file-loader',
						options: {
							name: 'images/[name].[ext]',
							publicPath: '../',
						},
					},
				],
			},
		],
	},

	// Plugins. Gotta have em'.
	plugins: [

		// Removes the "dist" folder before building.
		new CleanWebpackPlugin( [ 'assets/dist' ] ),

		new MiniCSSExtractPlugin( {
			filename: 'css/[name].css',
		} ),

		new CopyWebpackPlugin( [ { from: 'assets/src/images', to: 'images' } ] ),
	],
};

if ( inProduction ) {
	// Create RTL css.
	config.plugins.push( new WebpackRTLPlugin( {
		suffix: '-rtl',
		minify: true,
	} ) );

	// Minify images.
	// Must go after CopyWebpackPlugin above: https://github.com/Klathmon/imagemin-webpack-plugin#example-usage
	config.plugins.push( new ImageminPlugin( { test: /\.(jpe?g|png|gif|svg)$/i } ) );

	// POT file.
	wpPot( {
		package: 'WP Distraction Free View',
		domain: 'wpdfv',
		destFile: 'languages/wpdfv.pot',
		relativeTo: './',
		src: [ './**/*.php', '!./includes/libraries/**/*', '!./vendor/**/*' ],
		bugReport: 'https://github.com/mehul0810/wp-distraction-free-view/issues/new',
		team: 'Mehul Gohil <hello@mehulgohil.com>',
	} );
}

module.exports = config;
