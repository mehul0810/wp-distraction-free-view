const wordpress = require( '@wordpress/eslint-plugin' );

module.exports = [
	{
		ignores: [ 'assets/dist/**', 'node_modules/**', 'vendor/**' ],
	},
	...wordpress.configs.recommended,
];
