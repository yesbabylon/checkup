'use strict';


// Instanciate resiway module
var project = angular.module('project', [
    // dependencies
    'ngRoute',
    'ngSanitize',
    'ngCookies',
    'ngAnimate',
    'ngFileUpload',
    'ui.bootstrap',
    'ui.tinymce',
    'oi.select',
    'pascalprecht.translate',
    'angularMoment',
    'ngToast',
    'ngHello'
])


/**
* Configure ngToast animations
*
*/
.config(['ngToastProvider', function(ngToastProvider) { 
    // Built-in ngToast animations include slide & fade
    ngToastProvider.configure({ animation: 'fade' }); 
}]) 

/**
* moment.js : customization
*
*/
.config(function() {
    moment.updateLocale(global_config.locale, {
        calendar : {
            sameElse: 'LLL'
        }
    });

})

/**
* angular-translate: register translation data
*
*/
.config([
    '$translateProvider', 
    function($translateProvider) {
        // we expect a file holding the 'translations' var definition 
        // to be loaded in index.html
        if(typeof translations != 'undefined') {
            console.log('translations loaded');
            $translateProvider
            .translations(global_config.locale, translations)
            .preferredLanguage(global_config.locale)
            .useSanitizeValueStrategy(['sanitizeParameters']);
        }    
    }
])

/**
* Set HTTP POST format to URLENCODED (instead of JSON)
*
*/
.config([
    '$httpProvider', 
    '$httpParamSerializerJQLikeProvider', 
    function($httpProvider, $httpParamSerializerJQLikeProvider) {
        // Use x-www-form-urlencoded Content-Type
        $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';    
        $httpProvider.defaults.paramSerializer = '$httpParamSerializerJQLike';    
        $httpProvider.defaults.transformRequest.unshift($httpParamSerializerJQLikeProvider.$get());
    }
])

/**
* Enable HTML5 mode
*
*/
.config([
    '$locationProvider', 
    function($locationProvider) {
        // ensure we're in Hashbang mode
        $locationProvider.html5Mode({enabled: true, requireBase: true, rewriteLinks: true}).hashPrefix('!');
    }
])

.config([
    'helloProvider',
    function (helloProvider) {
        helloProvider.init(
            {
                // App public keys
                facebook: '',
                google: '',
                twitter: ''
            }, 
            {
                scope: 'basic, email',
                redirect_uri: 'oauth2callback',
                oauth_proxy: 'https://auth-server.herokuapp.com/proxy'
            }
        );
    }
])

.factory('httpRequestInterceptor', [
    '$cookies',    
    function ($cookies) {
        return {
            request: function (config) {
                config.headers['Authorization'] = 'Bearer ' + $cookies.get('access_token');
                return config;
            }
        };
    }
])

.config(['$httpProvider', function ($httpProvider) {
  $httpProvider.interceptors.push('httpRequestInterceptor');
}])

.run( [
    '$window', 
    '$timeout', 
    '$rootScope', 
    '$location',
    '$cookies',
    '$http',
    'hello',
    function($window, $timeout, $rootScope, $location, $cookies, $http,  hello) {
        console.log('run method invoked');
        
        // @init
        
        // flag indicating that some content is being loaded
        $rootScope.viewContentLoading = true;   

      

        // @events

        // This is triggered afeter loading, when DOM has been processed
        angular.element(document).ready(function () {
            console.log('dom ready');
        });
        
        // when requesting another location (user click some link)
        $rootScope.$on('$locationChangeStart', function(angularEvent) {
            // mark content as being loaded (show loading spinner)
            $rootScope.viewContentLoading = true;
        });


        /**
        * This callback is invoked at each change of view
        * it is used to complete any pending action
        */
        $rootScope.$on('$viewContentLoaded', function(params) {
            console.log('$viewContentLoaded received');
            // hide loading spinner
            $rootScope.viewContentLoading = false;
        });            
    }
])

/**
*
* we take advantage of the rootController to define globaly accessible utility methods
*/
.controller('rootController', [
    '$rootScope', 
    '$scope',
    '$location',
    '$route',
    '$http',
    function($rootScope, $scope, $location, $route, $http) {
        console.log('root controller');

        var rootCtrl = this;

        rootCtrl.isNavCollapsed = true;
        
        rootCtrl.tinymceOptions = {
            inline: false,
            plugins : 'wordcount charcount advlist autolink link image lists charmap fullscreen preview table paste code',
            skin: 'lightgray',
            theme : 'modern',
            content_css: 'packages/resipedia/apps/assets/css/bootstrap.min.css',
            elementpath: false,
            block_formats: 
                    'Paragraph=p;' +
                    'Heading 1=h3;' +
                    'Heading 2=h4;' +
                    'Heading 3=h5;',
            formats: {
                bold : {inline : 'b' },  
                italic : {inline : 'i' },
                underline : {inline : 'u'}
            },                    
            menu : {
                edit: {title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall'},
                format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | charmap | removeformat'}
            },
            menubar: false,
            toolbar: "fullscreen code | undo redo | bold italic | headings formatselect | blockquote bullist numlist outdent indent | link image | table",
            toggle_fullscreen: false,
            setup: function(editor) {
                editor.on("init", function() {
                    angular.element(editor.editorContainer).addClass('form-control');
                });
                editor.on("focus", function() {
                    angular.element(editor.editorContainer).addClass('focused');
                });
                editor.on("blur", function() {
                    angular.element(editor.editorContainer).removeClass('focused');
                });
                editor.on('FullscreenStateChanged', function () {
                    console.log('fs');
                    rootCtrl.tinymceOptions.toggle_fullscreen = !rootCtrl.tinymceOptions.toggle_fullscreen;
                    if(rootCtrl.tinymceOptions.toggle_fullscreen) {
                        angular.element(editor.editorContainer).addClass('mt-2');
                    }
                    else {
                        angular.element(editor.editorContainer).removeClass('mt-2');
                    }
                });                
            }            
        };        

             
        
    }
]);

angular.module('project')

.service('routeObjectProvider', [
    '$http',
    '$route',
    '$q',
    function ($http, $route, $q) {
        return {
            provide: function (provider) {
                var deferred = $q.defer();
                // set an empty object as default result
                deferred.resolve({});

                if(typeof $route.current.params.id == 'undefined'
                || $route.current.params.id == 0) return deferred.promise;

                return $http.get('index.php?get='+provider+'&id='+$route.current.params.id)
                .then(
                    function successCallback(response) {
                        var data = response.data;
                        if(typeof data.result != 'object') return {};
                        return data.result;
                    },
                    function errorCallback(response) {
                        // something went wrong server-side
                        return deferred.promise;
                    }
                );
            }
        };
    }
])


.service('routeArticlesProvider', ['$http', '$rootScope', '$httpParamSerializerJQLike', function($http, $rootScope, $httpParamSerializerJQLike) {
    this.load = function() {
        return $http.get('index.php?get=resilexi_article_list&'+$httpParamSerializerJQLike($rootScope.search.criteria)+'&channel='+$rootScope.config.channel)
        .then(
            function successCallback(response) {
                var data = response.data;
                if(typeof data.result != 'object') {
                    $rootScope.search.criteria.total = 0;
                    return [];
                }
                $rootScope.search.criteria.total = data.total;
                return data.result;
            },
            function errorCallback(response) {
                // something went wrong server-side
                $rootScope.search.criteria.total = 0;
                return [];
            }
        );
    };
}])

.service('routeArticleProvider', ['routeObjectProvider', '$sce', function(routeObjectProvider, $sce) {
    this.load = function() {
        return routeObjectProvider.provide('resilexi_article')
        .then(function(result) {
            // adapt result to view requirements
            var attributes = {
                commentsLimit: 5,
                newCommentShow: false,
                newCommentContent: ''
            }            
            // might receive an article or a term
            if(angular.isDefined(result.articles)) {
                // this is a term
                // process each article
                angular.forEach(result.articles, function(value, index) {
                    // mark html as safe
                    result.articles[index].content = $sce.trustAsHtml(result.articles[index].content);
                    // add meta info attributes
                    angular.extend(result.articles[index], attributes);
                });                
            }
            else {
                // add meta info attributes
                angular.extend(result, attributes);
                // mark html as safe
                result.content = $sce.trustAsHtml(result.content);
            }
            return result;
        });
    };
}])

;

angular.module('project')

.controller('homeController', ['$http', '$scope', '$rootScope', '$location', '$interval', '$q', function($http, $scope, $rootScope, $location, $interval, $q) {
    console.log('home controller');  
    
    var ctrl = this;
    
    ctrl.errors = {
        server: false
    };
    
	ctrl.show_result_pane = false;
    ctrl.report_id = null;
    ctrl.URL = '';
    
    ctrl.resetErrors = function() {
            ctrl.errors.server = false;
    };
    
	function validDomain(str) {
		var re = new RegExp(/^((?:(?:(?:\w[\.\-\+]?)*)\w)+)((?:(?:(?:\w[\.\-\+]?){0,63})\w)+)\.(\w{2,63})$/); 
		return str.match(re);
	}
	
	function validURL(str) {
	  var pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
		'((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name
		'((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
		'(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
		'(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
		'(\\#[-a-z\\d_]*)?$','i'); // fragment locator
	  return !!pattern.test(str);
	}
    
    $scope.results = {
        PER: {
            score: 0,
            tests: 0,
            color: 'blue',
            percent: 100
        },
        SEC: {
            score: 0,
            tests: 0,
            color: 'blue',
            percent: 100
        },
        INT: {
            score: 0,
            tests: 0,
            color: 'blue',
            percent: 100
        },
        ADA: {
            score: 0,
            tests: 0,
            color: 'blue',
            percent: 100
        },
        LEG: {
            score: 0,
            tests: 0,
            color: 'blue',
            percent: 100
        },
        VIS: {
            score: 0,
            tests: 0,
            color: 'blue',
            percent: 100
        }        
    };
    $scope.keydown = function(event) {
        // intercept <enter> keystroke
        if(event.keyCode == 13) {
            event.srcElement.blur();
            $scope.run();
        }
    }
    
	$scope.run = function() {
        console.log('try to run checkup');        
        
		// sanitize URL (input element value might be invalid)
        var url = $scope.formURL.inputURL.$viewValue; 
		if(url.indexOf('http://') == -1 && url.indexOf('https://') == -1) {
			url = 'http://'+url;
		}
		// still invalid ? then abort
		if( !validURL(url) )  return;

        // assign sanitized URL
        ctrl.URL = url;

       
        // UI : set all checks as running
        angular.forEach($scope.results, function(item, key) {
            $scope.results[key]['loading'] = true;
        });
           
        // request a report ID for given domain
		$http({
			method: 'GET',
			url: '/index.php?get=checkup_report-id&url='+ctrl.URL
		})
		.then(
			function success(response) {
                // show result pane
                ctrl.show_result_pane = true;
                
                var json = response.data;
                ctrl.report_id = json.report_id;

                // run available checks
                $q.all([                                
                    $http.get('/index.php?do=checkup_performance&report_id='+ctrl.report_id),
                    $http.get('/index.php?do=checkup_security&report_id='+ctrl.report_id),
                    $http.get('/index.php?do=checkup_adaptability&report_id='+ctrl.report_id),
                    $http.get('/index.php?do=checkup_legality&report_id='+ctrl.report_id),
                    $http.get('/index.php?do=checkup_integrability&report_id='+ctrl.report_id),
                    $http.get('/index.php?do=checkup_visibility&report_id='+ctrl.report_id)

                ]).then(function() {

                    // request results at time interval
                    $interval(function() {
                        
                        angular.forEach($scope.results, function (item, category) {
                            // skip tests already displayed
                            if( $scope.results[category]['loading']) {
                            
                                $http({
                                    method: 'GET',
                                    url: '/index.php?get=checkup_result&report_id='+ctrl.report_id+'&category='+category
                                })
                                .then(
                                    function success(response) {
                                        var json = response.data;
                                        /*
                                            Expected format: 
                                            
                                            {
                                                "SHARED_HOSTING": {
                                                    "value": false,
                                                    "pass": true,
                                                    "description": "Le site est-il sur un h\u00e9bergement mutualis\u00e9 ?"
                                                },   
                                                [...]
                                            }
                                        */
                                        
                                        var score = 0;
                                        var tests_count = 0;
                                        
                                        angular.forEach(json, function(item, key) {
                                            if(item['pass']) ++score;
                                            ++tests_count;
                                        });
                                        
                                        $scope.results[category]['score'] = score;
                                        $scope.results[category]['tests'] = tests_count;
                                        $scope.results[category]['percent'] = Math.floor((score/tests_count)*100);
                                        // assign color
                                        $scope.results[category]['color'] = 'green';
                                        if($scope.results[category]['percent'] <= 66) {
                                            $scope.results[category]['color'] = 'orange';
                                            if($scope.results[category]['percent'] <= 33) {
                                                $scope.results[category]['color'] = 'red';
                                            }                                            
                                        }
                                        // UI : mask loader
                                        $scope.results[category]['loading'] = false;
                                        // hack to hide element without waiting for the end of CSS animation
                                        angular.element(document.getElementById("section-loader-"+category)).css("display", "none");
                                        
                                    },
                                    function error() {
                                        // UI : mask loader
                                        $scope.results[category]['loading'] = false;
                                        // hack to hide element without waiting for the end of CSS animation
                                        angular.element(document.getElementById("section-loader-"+category)).css("display", "none");
                                        
                                    }
                                ); 
                            }                
                        });
                    // run every few seconds
                    }, 2000);


                });


            },
            function error(response) {
                ctrl.errors.server = true;
            }
        );
        
        
        
       
	}
	
}]);
angular.module('project')

.config([
    '$routeProvider', 
    '$routeParamsProvider', 
    '$httpProvider',
    function($routeProvider, $routeParamsProvider, $httpProvider) {
        
        // templates should be pre-loaded (included in main html or dynamically)
        // var templatePath = '';
        var templatePath = 'packages/checkup/apps/views/';
        
        /**
        * Routes definition
        * This call associates handled URL with their related views and controllers
        * 
        * As a convention, a 'ctrl' member is always defined inside a controller as itself
        * so it can be manipulated the same way in view and in controller
        */
        
        // todo : this var should be define in a i18n file
        var paths = {
            '/article/edit/:id': '/article/edition/:id',
            '/article/:id/:title?': '/article/:id/:title?',
            '/articles': '/articles'
        };

        // routes definition
        var routes = {
        
        /**
        * Article related routes
        */
        '/articles': {
                    templateUrl : templatePath+'articles.html',
                    controller  : 'articlesController as ctrl',
                    resolve     : {
                        // list of articles is required as well for selecting parent category
                        articles: ['routeArticlesProvider', function (provider) {
                            return provider.load();
                        }]
                    }
        },
        '/article/edit/:id': {
                    templateUrl : templatePath+'articleEdit.html',
                    controller  : 'articleEditController as ctrl',
                    resolve     : {
                        article: ['routeArticleProvider', function (provider) {
                            return provider.load();
                        }]
                    }        
        },
        '/article/:id/:title?': {
                    templateUrl : templatePath+'article.html',
                    controller  : 'articleController as ctrl',
                    resolve     : {
                        article: ['routeArticleProvider', function (provider) {
                            return provider.load();
                        }]
                    }
        }        
           
        };

        // routes i18n
        angular.forEach(routes, function(route, path) {
            var translation = path;
            if(typeof paths[path] != 'undefined') {
                translation = paths[path];
            }
            else console.warn('missing translation for route '+path);
            // is global locale 'en' ?
            if(path != translation) {
                // no : redirect to current locale translation
                $routeProvider.when(path, { redirectTo  : translation });
            }
            // register route
            $routeProvider.when(translation, routes[path]);
        });
        
        /**
        * Default route
        */           
        $routeProvider 
        .otherwise({
            templateUrl : templatePath+'home.html',
            controller  : 'homeController as ctrl'
        });
        
    }
]);