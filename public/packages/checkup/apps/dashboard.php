<?php
    /**
    * Retrieves current revision identifier from git folder, if any.
    *
    * @return   string
    */
    function current_revision() {
        $date = date("Y.m.d", time());        
        $hash = '';
        if(file_exists('../.git')) {
            $file = trim(explode(' ', file_get_contents('../.git/HEAD'))[1]);
            $hash = substr(file_get_contents("../.git/$file"), 0, 4);
            $time = filemtime ('../.git/index');
            $date = date("Y.m.d", $time);  
        }
        return "$date.$hash";
    }
?>    
<!DOCTYPE html>
<!-- global attr for scrolling to top -->
<!-- app name ('project' as default)-->    
<!-- use of rootCtrl as convention for root (global) controller -->
<html lang="fr" id="top" ng-app="project" ng-controller="rootController as rootCtrl">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

        <!-- define fragment to be used as hashbang (@see https://www.contentside.com/angularjs-seo/) -->        
        <meta name="fragment" content="!">
        <base href="/">
        
        <meta itemscope itemtype="https://schema.org/WebApplication" />        
        <!-- add absolute path of a thumbnail here -->
        <meta itemprop="image" content="" />

        <meta property="og:type" content="website" />
        <!-- add absolute path of a thumbnail here -->        
        <meta property="og:image" content="" />

        <!-- webapp title -->
        <title></title>
        <meta name="title" content="">
        <!-- webapp description -->
        <meta name="description" content="">        
        
        <script type="text/javascript" src="https://code.angularjs.org/1.6.9/angular.min.js"></script>
        <script type="text/javascript" src="https://code.angularjs.org/1.6.9/angular-animate.min.js"></script>
        <script type="text/javascript" src="https://code.angularjs.org/1.6.9/angular-touch.min.js"></script>
        <script type="text/javascript" src="https://code.angularjs.org/1.6.9/angular-sanitize.min.js"></script>
        <script type="text/javascript" src="https://code.angularjs.org/1.6.9/angular-cookies.min.js"></script>
        <script type="text/javascript" src="https://code.angularjs.org/1.6.9/angular-route.min.js"></script>


        <script type="text/javascript" src="/packages/checkup/apps/assets/js/moment.min.js"></script>
        <script type="text/javascript" src="/packages/checkup/apps/assets/js/md5.min.js"></script>
        <script type="text/javascript" src="/packages/checkup/apps/assets/js/tinymce/tinymce.min.js"></script>
        <script type="text/javascript" src="/packages/checkup/apps/assets/js/hello.all.min.js"></script>        
        <script type="text/javascript" src="/packages/checkup/apps/assets/js/angular-translate.min.js"></script>
        <script type="text/javascript" src="/packages/checkup/apps/assets/js/angular-moment.min.js"></script>
        <script type="text/javascript" src="/packages/checkup/apps/assets/js/ng-file-upload.min.js"></script>
        <script type="text/javascript" src="/packages/checkup/apps/assets/js/angular-hello.min.js"></script>
        <script type="text/javascript" src="/packages/checkup/apps/assets/js/ui-bootstrap-tpls-2.2.0.min.js"></script>
        <script type="text/javascript" src="/packages/checkup/apps/assets/js/angular-tinymce.min.js"></script>
        <script type="text/javascript" src="/packages/checkup/apps/assets/js/ngToast.min.js"></script>
        <script type="text/javascript" src="/packages/checkup/apps/assets/js/select-tpls.min.js"></script>
        
        
        <script type="text/javascript" src="/packages/checkup/apps/project.js"></script>

        
        <link rel="stylesheet" type="text/css" href="/packages/checkup/apps/assets/css/project.min.css?v=" />

<style>
.input-group-addon, .input-group-btn .btn, .input-group input {
	height: 100%;
}	
</style>
        <script>
        /* global config object, when necessary */
        var global_config = {
        };
        
        /* additional mandatory js content, if any */
        </script>
        
    </head>


    <body class="ng-cloak">
        <!-- This is a dedicated element wherein FB scripts will create additional DOM elements -->
        <div id="fb-root"></div>
        
        <!-- These elements might be used by some social networks and have 
        Content should be identical to meta/title 
        -->
        <div class="sectiontitle ng-hide"></div>
        <title class="ng-hide"></title>
    
        <!-- This is a dedicated element for displaying notifications (ng-toast or other)
        -->
        <toast></toast>

        <!-- This is a hidden container for embedding some stuff into the current file.
        It can be used for images preload -->
        <div class="ng-hide">
        </div>
        
        <!-- In some cases, html templates must be embedded in rootScope 
        This is the place to hard-code those, if any.
        -->
        
        <!-- header / topbar -->
        <header 
            id="header" 
            class="navbar navbar-default navbar-fixed-top navbar-inner ng-cloak"
            ng-include="'/packages/checkup/apps/views/parts/header.html'">
        </header>
        
        <main id="body" role="main">
            <!-- This is a dedicated element where modal will anchor -->
            <div class="modal-wrapper"></div>
                       
            <div class="container">
                <!-- menu -->

                <!-- gloabl loader overlay -->
                <div ng-show="viewContentLoading" class="loader"><i class="fa fa-spin fa-spinner" aria-hidden="true"></i></div>
                <div ng-view ng-hide="viewContentLoading"></div>
            </div>
        </main>

        <footer id="footer" class="footer">
            <div class="grid wrapper">
                <div class="container col-1-1">
                    <!-- footer -->

                    <div ng-include="'/packages/checkup/apps/views/parts/footer.html'"></div>
                    <span class="small">rev <?php echo current_revision(); ?></span>
                </div>
            </div>
        </footer>
    </body>

</html>