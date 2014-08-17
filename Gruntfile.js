module.exports = function(grunt) {
    grunt.initConfig({
        // variables
        paths: {
            assets: {
                css: './www/assets/style/css/',
                less: './www/assets/style/less/',
                js: './www/assets/js/',
                vendor: './www/assets/vendor/'
            },
            css: './www/assets/build/style/',
            js: './www/assets/build/js/'

        },

        less: {
            development: {
                options: {
                    compress: true,
                    yuicompress: true,
                    optimization: 2
                },
                files: {
                    "<%= paths.css %>main.css":"<%= paths.assets.less %>main.less"
                }
            }
        },
        requirejs: {
            compile: {
                options: {
                    appDir: "./www/assets/js",
                    baseUrl: "./",
                    mainConfigFile: "<%= paths.assets.js %>main.js",
                    dir: "<%= paths.js %>",
                    optimize: "uglify2",
                    modules: [
                        {
                            name: "main",
                            include: ["jquery", "bootstrap"]
                        }
                    ],
                    skipDirOptimize: false,
                    keepBuildDir: false
                }
            }
        },
        watch: {
            styles: {
                files: [
                    "<%= paths.assets.less %>**/*.less"
                ],
                tasks: [
                    "less"
                ],
                options: {
                    nospawn: true
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-requirejs');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerTask('default', ['watch']);
};

