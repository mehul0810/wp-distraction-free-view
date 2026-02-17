/**
 * External dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		wpdfv: './assets/src/js/frontend/wpdfv.js',
		'wpdfv-admin': './assets/src/js/admin/wpdfv-admin.js',
	},
	output: {
		path: path.resolve(__dirname, 'assets/dist'),
	},
	plugins: [
		...defaultConfig.plugins,
		new CopyWebpackPlugin({
			patterns: [
				{
					from: 'assets/src/images',
					to: 'images',
				},
			],
		}),
	],
};
