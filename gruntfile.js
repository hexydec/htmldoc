module.exports = function(grunt) {
	require("load-grunt-tasks")(grunt);

	// grun tasks
	grunt.initConfig({
		pkg: grunt.file.readJSON("package.json"),
		phpunit: {
			classes: {
				dir: "tests/"
			},
			options: {
				bin: "php tools/phpunit.phar",
				bootstrap: "tests/bootstrap.php",
				testdox: true,
				colors: true,
				//debug: true
			}
		}
	});
	grunt.registerTask("test", ["phpunit"]);
};
