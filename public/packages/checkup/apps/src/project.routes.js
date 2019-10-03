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