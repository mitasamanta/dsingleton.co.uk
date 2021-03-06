module.exports = function(grunt) {
  grunt.initConfig({
    shell: {
      jekyll: {
        command: 'jekyll build'
      }
    },

    connect: {
      options: {
        hostname: 'localhost',
        port: 9000
      },
      dist: {
        options: {
          open: true,
          keepalive: true,
          base: '.dist'
        }
      }
    },

    'gh-pages': {
      options: {
        base: '.dist',
        message:'Automatically generated gh-pages update',
        silent: true
      },
      src: ['**']
    }
  });

  grunt.loadNpmTasks('grunt-shell');
  grunt.loadNpmTasks('grunt-contrib-connect');
  grunt.loadNpmTasks('grunt-gh-pages');

  grunt.registerTask('build', [
    'shell:jekyll',
  ]);

  grunt.registerTask('server', [
    'build',
    'connect:dist'
  ]);

  grunt.registerTask('deploy', function() {

    // Proxy a message argument on to gh-pages, from command line args
    grunt.config.set('gh-pages.options.message', grunt.option('message'));
    console.log(grunt.option('message'));

    grunt.task.run([
      'build',
      'gh-pages'
    ]);

  });
};
