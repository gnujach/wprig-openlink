'use strict';

module.exports = {
	theme: {
		slug: 'wprig',
		name: 'WP Rig OpenLink',
		author: 'Jach'
	},
	dev: {
		browserSync: {
			live: true,
			proxyURL: '127.0.0.1/openlink',
			bypassPort: '8181'
		},
		browserslist: [ // See https://github.com/browserslist/browserslist
			'> 1%',
			'last 2 versions'
		],
		debug: {
			styles: true, // Render verbose CSS for debugging.
			scripts: true // Render verbose JS for debugging.
		}
	},
	export: {
		compress: true
	}
};
