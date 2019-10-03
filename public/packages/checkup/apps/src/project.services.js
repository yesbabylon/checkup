
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