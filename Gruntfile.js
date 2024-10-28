module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    // Configuration for makepot task
    makepot: {
      target: {
        options: {
          domainPath: '/languages',    // Where to save the POT file.
          potFilename: 'integration-barion-payment-gateway-fluent-forms.pot',   // Name of the POT file.
          type: 'wp-plugin',           // Type of project (wp-plugin or wp-theme).
          updateTimestamp: true,        // Whether the POT-Creation-Date should be updated without other changes.
		            exclude: ['includes/Barion/']            // Exclude the lib directory from translation.
        }
      }
    }
  });

  // Load the plugin that provides the "makepot" task.
  grunt.loadNpmTasks('grunt-wp-i18n');

  // Default task(s).
  grunt.registerTask('default', ['makepot']);
};
