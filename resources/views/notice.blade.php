<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>AMPROICalculator</title>
    <base href="/">
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.6/angular.min.js"></script>
    <script>
        (function(w,d,s,g,js,fs){
            g=w.gapi||(w.gapi={});g.analytics={q:[],ready:function(f){this.q.push(f);}};
            js=d.createElement(s);fs=d.getElementsByTagName(s)[0];
            js.src='https://apis.google.com/js/platform.js';
            fs.parentNode.insertBefore(js,fs);js.onload=function(){g.load('analytics');};
        }(window,document,'script'));
    </script>
    <script>
        (function(){
            var app = angular.module('amproicalculator', []);
            app.config(['$locationProvider',
                function( $locationProvider) {
                    $locationProvider.html5Mode(true);
                }]);
            app.controller("ARCController",["$scope","$http","$sce","$location",function($scope,$http,$sce,$location){
                var me=this;
                me.clientId="295074954994-61se8nkehuupnjet2b5cki2s7ulr9kql.apps.googleusercontent.com";
                me.url=null;
                var existingUrl=$location.search().url;
                if(existingUrl&&existingUrl!=""){
                    me.url=existingUrl;
                }
                me.hasPreview=false;
                me.previewId=null;
                me.isLoading=false;
                me.hasAnalytics=false;
                me.authorizeGa=function(){
                    gapi.analytics.auth.authorize({
                        container: 'embed-api-auth-container',
                        clientid: me.clientId
                    });
                    var viewSelector = new gapi.analytics.ViewSelector({
                        container: 'view-selector-container'
                    });
                    viewSelector.execute();
                    viewSelector.on('change', function(ids) {
                        me.hasAnalytics=false;
                        gapi.client.analytics.data.ga.get({
                            'ids': ids,
                            'start-date': '30daysAgo',
                            'end-date': 'today',
                            'metrics': 'ga:users,ga:pageviews,ga:pageviewsPerSession'
                        }).then(function(res){
                            console.log(res.result);
                            me.hasAnalytics=true;
                            me.analyticsData=res.result.totalsForAllResults;
                            $scope.$apply();
                        });
                    });
                };

                me.submit=function(){
                    me.isLoading=true;
                    if(me.url&&me.url!==""){
                        var turl=me.url.replace('https://','');
                        turl=turl.replace('http://','');
                        $http.get("/api/get-report?url="+turl).then(
                            function(response){
                                console.log(response);
                                me.isLoading=false;
                                me.previewUrl=$sce.trustAsResourceUrl('https://docs.google.com/presentation/d/'+response.data.id+'/embed?start=false&loop=false&delayms=3000');
                                me.hasPreview=true;
                            }
                        );
                    }
                };

                me.submitWithAnalytics=function(){
                    me.isLoading=true;
                    if(me.url&&me.url!==""){
                        var turl=me.url.replace('https://','');
                        turl=turl.replace('http://','');
                        $http.get("/api/get-report?pageViews="+me.analyticsData["ga:pageviews"]+"&users="+me.analyticsData["ga:users"]+"&pageviewsPerSession="+me.analyticsData["ga:pageviewsPerSession"].replace('.',',')+"&url="+turl).then(
                            function(response){
                                console.log(response);
                                me.isLoading=false;
                                me.previewUrl=$sce.trustAsResourceUrl('https://docs.google.com/presentation/d/'+response.data.id+'/embed?start=false&loop=false&delayms=3000');
                                me.hasPreview=true;
                            }
                        );
                    }
                };

            }]);
        })();

    </script>
</head>
<body ng-app="amproicalculator" ng-controller="ARCController as ARC" >
<div id="embed-api-auth-container"></div>
<div id="view-selector-container"></div>
<input type="text" ng-model="ARC.url">
<button ng-click="ARC.submit()" ng-disabled="ARC.isLoading">Preview report</button>
<button ng-click="ARC.submitWithAnalytics()" ng-disabled="ARC.isLoading" ng-show="ARC.hasAnalytics">Preview report with analytics</button>
<button ng-click="ARC.authorizeGa()" ng-disabled="ARC.isLoading" ng-show="!ARC.hasAnalytics">Auth GA</button>
<iframe ng-if="ARC.hasPreview" ng-src="{{ARC.previewUrl}}" frameborder="0" width="480" height="299"></iframe>





</body>
</html>