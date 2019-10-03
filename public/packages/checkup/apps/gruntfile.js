module.exports = function(grunt) {

	grunt.loadNpmTasks('grunt-contrib-cssmin');	
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-uglify'); 
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-clean');
    
	grunt.initConfig({

        
		concat: {
			options: {
				stripBanners: true
			},            
			project_css: {
				src: 'assets/css/src/*.css',
				dest: 'assets/css/project.css'		
			},        
			project_controllers: {
				src: 'src/controllers/*.js',
				dest: 'src/project.controllers.js'
			},            
			project_filters: {
				src: 'src/filters/*.js',
				dest: 'src/project.filters.js'
			},
			project_services: {
				src: 'src/services/*.js',
				dest: 'src/project.services.js'
			},            
			project_all: {
				src: [
                        'src/project.module.js', 
                        'src/project.services.js', 
                        'src/project.filters.js', 
                        'src/project.controllers.js',
                        'src/project.routes.js'
                    ],
				dest: 'project.js'
			}
			
		},

		cssmin: { 
			bootstrap_css: {
				src: 'assets/css/bootstrap/bootstrap.css',
				dest: 'assets/css/bootstrap.min.css'		
			},

			project_css: {
				src: 'assets/css/project.css',
				dest: 'assets/css/project.min.css'		
			}

		},
        
		uglify: {
		
			options: {
				preserveComments: 'some',
				mangle: true,
				quoteStyle: 3
			},

            dependencies_all: {
                files: [{
                    expand: true,
                    src: 'assets/js/src/*.js',
                    dest: 'assets/js',
                    flatten: true,
                    ext: '.min.js'
                }]
            },
            
			project_all: {
                files: {
                    'project.min.js': ['project.js']
                }
			}
          
		},

			
		watch: {
/*
			scripts: {
				files: dir_scripts+'src/*.js',
				tasks: ['concat', 'uglify', 'jshint']
			},
    
			styles: {
				files: dir_styles+'src/*.css',
				tasks: ['concat']
			}
*/     
		}

	});


	grunt.registerTask('compile', ['concat', 'cssmin', 'uglify']);
	grunt.registerTask('default', ['concat', 'cssmin:project_css', 'uglify:project_all']);
};