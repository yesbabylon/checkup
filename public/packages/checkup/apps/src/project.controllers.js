angular.module('project')

.controller('homeController', [
'$http', '$scope', '$rootScope', '$location', '$interval', '$q', '$uibModal', 'ngToast', '$timeout', 
function($http, $scope, $rootScope, $location, $interval, $q, $uibModal, ngToast, $timeout) {
    console.log('home controller');  
    
    var ctrl = this;
    
    ctrl.errors = {
        url: false,
        server: false
    };

    // make some variables accessible to the whole App
    $rootScope.report_id = null;
    $rootScope.report_ready = false;
    
	ctrl.show_result_pane = false;
    ctrl.URL = '';    
    ctrl.resetErrors = function() {
        ctrl.errors.url = false;
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
            percent: 100,
            loading: true
        },
        SEC: {
            score: 0,
            tests: 0,
            color: 'blue',
            percent: 100,
            loading: true
        },
        INT: {
            score: 0,
            tests: 0,
            color: 'blue',
            percent: 100,
            loading: true
        },
        ADA: {
            score: 0,
            tests: 0,
            color: 'blue',
            percent: 100,
            loading: true
        },
        LEG: {
            score: 0,
            tests: 0,
            color: 'blue',
            percent: 100,
            loading: true
        },
        VIS: {
            score: 0,
            tests: 0,
            color: 'blue',
            percent: 100,
            loading: true
        }        
    };
    
    $scope.keydown = function(event) {
        // intercept <enter> keystroke
        if(event.keyCode == 13) {
            event.srcElement.blur();
            $scope.run();
        }
    }
    
    $rootScope.scrolldown = function() {
        document.getElementById('footer').scrollIntoView(false);
        angular.element(document.getElementById('section-scroll')).css("display", "none");        
    };
    
	$scope.run = function() {
        console.log('try to run checkup');        
                
		// sanitize URL (input element value might be invalid)
        var url =  ctrl.URL;
        
		if(url.indexOf('http://') == -1 && url.indexOf('https://') == -1) {
			url = 'http://'+url;
		}
		// still invalid ? then abort
		if( !validURL(url) )  {
            console.log('invalid URL:' + url);
            document.getElementsByName('inputURL').forEach(function(element) {
                element.setCustomValidity('URL ou domaine invalide');
            });            
            ctrl.errors.url = true;
            document.getElementsByName('formURL')[0].reportValidity();
            return;
        }
        else { 
            document.getElementsByName('inputURL').forEach(function(element) {
                element.setCustomValidity('');
            });
        }
        // assign sanitized URL
        ctrl.URL = url;

        $rootScope.report_ready = false;
        // UI : set all checks as running
        angular.forEach($scope.results, function(item, category) {
            $scope.results[category]['loading'] = true;
            // hack to force immediate hiding
            angular.element(document.getElementById("section-result-"+category)).css("display", "none");
        });

        // request a report ID for given domain
        // POST /api/report {url}
		$http({
			method: 'GET',
			url: '/index.php?get=checkup_report-id&url='+ctrl.URL
		})
		.then(
			function success(response) {
                // show result pane
                ctrl.show_result_pane = true;
                
                var json = response.data;
                $rootScope.report_id = json.report_id;

                // run available checks
                $q.all([                                
                    $http.get('/index.php?do=checkup_performance&report_id='+$rootScope.report_id),
                    $http.get('/index.php?do=checkup_security&report_id='+$rootScope.report_id),
                    $http.get('/index.php?do=checkup_adaptability&report_id='+$rootScope.report_id),
                    $http.get('/index.php?do=checkup_legality&report_id='+$rootScope.report_id),
                    $http.get('/index.php?do=checkup_integrability&report_id='+$rootScope.report_id),
                    $http.get('/index.php?do=checkup_visibility&report_id='+$rootScope.report_id)

                ]).then(function() {

                    // request results at time interval
                    $interval(function() {
                        var remaining_tests = 6;
                        
                        angular.forEach($scope.results, function (item, category) {
                            // skip tests already displayed
                            if( $scope.results[category]['loading']) {
                                // GET /api/report/:id/:category
                                $http({
                                    method: 'GET',
                                    url: '/index.php?get=checkup_result&report_id='+$rootScope.report_id+'&category='+category
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
                                        angular.element(document.getElementById("section-result-"+category)).css("display", "block");
                                        
                                        --remaining_tests;
                                        if(!remaining_tests) {
                                            console.log("report ready");
                                            $rootScope.report_ready = true;
                                        }
                                    },
                                    function error() {
                                        // UI : mask loader
                                        $scope.results[category]['loading'] = false;
                                        // hack to hide element without waiting for the end of CSS animation
                                        angular.element(document.getElementById("section-loader-"+category)).css("display", "none");
                                        angular.element(document.getElementById("section-result-"+category)).css("display", "block");
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


    $scope.popup = function () {

            var modalInstance = $uibModal.open({
                animation: true,
                ariaLabelledBy: 'modal-title',
                ariaDescribedBy: 'modal-body',
                templateUrl: 'emailModal.html',
                controllerAs: 'ctrl',
                controller: function($scope, $rootScope) {
                    var ctrl = this;
                    $scope.errors = [];
                    $scope.result = '';
                    $scope.running = false;
                    
                    ctrl.send = function() {
                        $scope.running = true;
                        $scope.errors = [];
                        if(!$scope.form_login.email.$valid) {
                            $scope.errors.push('Ouille, la syntaxe de l\'adresse email entrée n\'est pas reconnue.');
                        }
                        if(!$scope.form_login.optin.$valid) {
                            $scope.errors.push('Merci de valider l\'accord d\'utilisation de données personnelles.');
                        }

                        if($scope.errors.length) {
                            $scope.running = false;
                            return;
                        }
                        $http({
                            method: 'GET',
                            url: '/index.php?do=checkup_send-report',
                            params: {
                                report_id:  $rootScope.report_id,
                                email: $scope.email
                            }
                        })
                        .then(
                            function success(json) {                                                               
                                console.log(json);

                                ngToast.success({
                                  content: '<b>Rapport envoyé</b>: consultez votre boite email !',
                                  dismissButton:true
                                });							
								
                                $timeout(function() {
                                    $scope.running = false;
                                    modalInstance.close();
                                }, 100);
                                
                            },
                            function error(result) {
                                console.log(result);
                                $scope.result = 'Vérifiez l\'adresse email';

                                ngToast.danger({
                                  content: '<b>Erreur</b>: impossible d\'envoyer le rapport',
                                  dismissButton:true
                                });

                                $timeout(function() {
                                    $scope.result = '';
                                    $scope.running = false;
                                    modalInstance.close();                                    
                                }, 1000);
                                
                            }
                        );                        
                        
                    };
                },
                size: 'md',
                appendTo: angular.element(document.querySelector('.modal-wrapper')),
            });
        };

    $scope.init = function() {
        if(window.location.hash) {
            var hash = window.location.hash.substr(1);
            ctrl.URL = hash;    
            $scope.run();
        }
        else {
            console.log('(no hash found)');
        }
    };
    
    $scope.init();
}]);