module.exports = function(grunt) {
    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON("package.json"),
        clean: {
            coverage: ['build']
        },
        shell: {
            coverage: {
                command: 'phpunit tests'
            }        },
        watch: {
          scripts: {
            files: ['tests/**/*.php', 'lib/*.php'],
            tasks: ['test'],
            options: {
              nospawn: true
            }
          }
        },
        phpunit: {
            classes: {
                dir: 'tests/'
            },
            options: {
                bin: 'phpunit',
                bootstrap: 'tests/bootstrap.php',
                colors: true,
                noConfiguration: true
            }
        }
    });

    grunt.loadNpmTasks('grunt-shell');
    grunt.loadNpmTasks('grunt-phpunit');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-clean');

    grunt.registerTask('test', ['phpunit']);
    grunt.registerTask('coverage', ['clean:coverage', 'shell:coverage']);
};
