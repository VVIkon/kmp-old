module.exports = function(grunt) {


    grunt.initConfig({
        'pkg': grunt.file.readJSON('package.json'),
        'auto_install': {
          'options': {
            'stdout': true,
            'stderr': true,
            'failOnError': true
          },
          'system': {
            'options': {
              'cwd': ''
            }
          }
        },
        'sass':{
          'options':{
            'style':'compressed',
            'trace':true
          }
        },
        'concat':{
          'options': {
            'sourceMap':true
          }
        },
        'uglify':{
          'options': {
            'sourceMap' : true,
            'sourceMapIncludeSources' : true
          }
        }
    });

    //grunt.config.merge(grunt.file.readJSON('gruntconfig.json'));

    grunt.loadTasks('gruntasks');

    grunt.registerTask('make-core', [
      'build-helpers',
      'build-polyfills',
      'build-core'
    ]);

    grunt.registerTask('build-statusicons',[
      'icons-order_status',
      'icons-service_status',
      'icons-invoice_status'
    ]);

    grunt.registerTask('default',[
      /** prepare */
      'auto_install',
      /** build resources */
      'build-kmpicons',
      'build-statusicons',
      'build-countryflags',
      /** build libs */
      'build-lib',
      /** @deprecated перенести header в модули ядра после реализации getCurrencyRates */
      'build-widgets',
      /** compile core */
      'make-core',
      /** build modules */
      'build-module-stub',
      'build-module-orderEdit',
      'build-module-orderList',
      'build-module-reports',
      'build-module-clientAdmin',
      'build-module-searchAvia',
      'build-module-searchHotel',
      /** build notifications */
      'compile-email-notifications'
    ]);

    /*
     * external dependencies:
     * ruby, ruby-sass, fontforge, ttfautohint, ImageMagick
     */
    grunt.loadNpmTasks('grunt-auto-install');
    grunt.loadNpmTasks('grunt-subgrunt');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-svg2png');
    grunt.loadNpmTasks('grunt-responsive-images');
    grunt.loadNpmTasks('grunt-spritesmith');
    grunt.loadNpmTasks('grunt-webfont');
    grunt.loadNpmTasks('grunt-ttf2eot');
    grunt.loadNpmTasks('grunt-string-replace');
    grunt.loadNpmTasks('grunt-image-embed');
    grunt.loadNpmTasks('grunt-uncss');
    grunt.loadNpmTasks('grunt-static-inline');
    grunt.loadNpmTasks('grunt-execute');
    grunt.loadNpmTasks('grunt-contrib-watch');

};
