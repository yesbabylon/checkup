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