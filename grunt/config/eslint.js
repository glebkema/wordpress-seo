// See https://github.com/sindresorhus/grunt-eslint
module.exports = {
	plugin: {
		src: [ "<%= files.js %>" ],
		options: {
			maxWarnings: 143,
		},
	},
	tests: {
		src: [ "<%= files.jsTests %>" ],
		options: {
			maxWarnings: 3,
		},
	},
	grunt: {
		src: [ "<%= files.grunt %>", "<%= files.config %>" ],
		options: {
			maxWarnings: 10,
		},
	},
};
