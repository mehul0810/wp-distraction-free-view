module.exports = {
	presets: [
		[
			'@babel/preset-env',
			{
				bugfixes: true,
				modules: false,
				targets: {
					browsers: require( '@wordpress/browserslist-config' ),
				},
			},
		],
		'@babel/preset-typescript',
	],
	plugins: [
		'@babel/plugin-syntax-import-attributes',
		'@wordpress/warning/babel-plugin',
		[
			'@babel/plugin-transform-react-jsx',
			{
				pragma: 'createElement',
				runtime: 'classic',
			},
		],
		[
			'@babel/plugin-transform-runtime',
			{
				helpers: true,
				useESModules: false,
			},
		],
	],
};
