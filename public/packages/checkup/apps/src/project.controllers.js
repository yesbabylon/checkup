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