<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <meta content="IE=Edge" http-equiv="X-UA-Compatible">
    <meta property="og:description" content="AMP ROI calculator for publishers and retailers">
    <meta name="description" content="AMP ROI calculator for publishers and retailers">
    <title>AMP ROI calculator for publishers and retailers</title>
    <base href="/">
    <link rel="shortcut icon" href="/resource/default/img/favicon.png">
    <link href="https://fonts.googleapis.com/css?family=Lato:100,300,400" rel="stylesheet">
    <link rel="stylesheet" href="/resource/default/css/basscss.min.css" type="text/css">
    <link rel="stylesheet" href="/resource/default/css/styles.css" type="text/css">
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
                me.email=null;
                me.turl=null;
                var existingUrl=$location.search().url;
                if(existingUrl&&existingUrl!=""){
                    me.url=existingUrl;
                }
                me.hasPreview=false;
                me.previewId=null;
                me.isLoading=false;
                me.emailIsLoading=false;
                me.emailSent=false;
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
                        var x = new Date();
                        x.setDate(1);
                        x.setMonth(x.getMonth()-1);
                        var y = new Date();
                        y.setDate(0);
                        y.setMonth(x.getMonth());
                        me.hasAnalytics=false;
                        gapi.client.analytics.data.ga.get({
                            'ids': ids,
                            'start-date': x.toISOString().slice(0,10),
                            'end-date': y.toISOString().slice(0,10),
                            'metrics': 'ga:users,ga:sessions,ga:pageviewsPerSession'
                        }).then(function(res){
                            me.hasAnalytics=true;
                            me.analyticsData=res.result.totalsForAllResults;
                            me.url=angular.copy(document.getElementById(":1").innerText);
                            $scope.$apply();
                        });
                    });
                };

                me.submit=function(){
                    if(me.url&&me.url!==""){
                        var turl=me.url.replace('https://','');
                        turl=turl.replace('http://','');
                        me.turl=angular.copy(turl);
                        me.isLoading=true;
                        $http.post("/api/get-report?url="+turl).then(
                            function(response){
                                me.isLoading=false;
                                me.previewUrl=$sce.trustAsResourceUrl('https://docs.google.com/presentation/d/'+response.data.id+'/embed?start=false&loop=false&delayms=3000');
                                me.hasPreview=true;
                            }
                        );
                    }
                };

                me.submitWithAnalytics=function(){
                    if(me.url&&me.url!==""){
                        var turl=me.url.replace('https://','');
                        turl=turl.replace('http://','');
                        me.turl=angular.copy(turl);
                        me.isLoading=true;
                        $http.post("/api/get-report?pageViews="+me.analyticsData["ga:sessions"]+"&users="+me.analyticsData["ga:users"]+"&pageviewsPerSession="+me.analyticsData["ga:pageviewsPerSession"]+"&url="+turl).then(
                            function(response){
                                me.isLoading=false;
                                me.previewUrl=$sce.trustAsResourceUrl('https://docs.google.com/presentation/d/'+response.data.id+'/embed?start=false&loop=false&delayms=3000');
                                me.hasPreview=true;
                            }
                        );
                    }
                };
                me.sendEmail=function(){
                    if(me.email&&me.email!==""&&me.turl){
                        me.emailIsLoading=true;
                        me.emailSent=false;
                        $http.post("/api/send-email?email="+me.email+"&url="+me.turl).then(
                            function(response){
                                me.emailIsLoading=false;
                                me.emailSent=true;
                            }
                        );

                    }
                };

            }]);
        })();

    </script>
</head>
<body ng-app="amproicalculator" ng-controller="ARCController as ARC" >

<header class="ampize-header">
    <div class="md-col-10 mx-auto px2">
        <div class="ampize-nav-left flex">
            <a href="/" class="ampize-menu-logo">
                <svg width="130px" height="100%" viewBox="3 -8 275 63" version="1.1" xmlns="http:/www.w3.org/2000/svg" xmlns:xlink="http:/www.w3.org/1999/xlink">
                    <text stroke="none" fill="none" font-size="52.4903461" font-weight="bold">
                        <tspan x="3.03326649" y="43.12557" fill="#EF5425">AMP</tspan>
                        <tspan x="115.858894" y="43.12557" fill="#F5B718">ize</tspan>
                        <tspan x="180.011342" y="43.12557" fill="#329F92">.me</tspan>
                    </text>
                </svg>
            </a>
        </div>
    </div>
</header>
<div class="main">
    <div class="clearfix row">
        <div class="md-col-6 mx-auto mb4 home-hero">
                <h1 class="center">AMP ROI Calculator</h1>
                <p class="intro center">Learn what you could expect from shifting to AMP/PWA and using AMPize with our deep learning based ROI calculator</p>
                <div class="center">
                    <a href="" ng-click="ARC.authorizeGa()" ng-disabled="ARC.isLoading" ng-show="!ARC.hasAnalytics">Get your real site metrics from Google Analytics</a>
                </div>
                <div class="md-col-5 mx-auto">
                    <div id="embed-api-auth-container"></div>
                    <div id="view-selector-container"></div>
                </div>
                <form class="center mt2" name="mainForm">
                    <input type="url" class="input-url" ng-model="ARC.url" placeholder="http://" required>
                    <button type="submit" class="submit-btn" ng-click="ARC.submit()" ng-disabled="ARC.isLoading" ng-show="!ARC.hasAnalytics">Estimate your ROI</button>
                    <button type="submit"  class="submit-btn" ng-click="ARC.submitWithAnalytics()" ng-disabled="ARC.isLoading" ng-show="ARC.hasAnalytics">Estimate your ROI</button>
                </form>
                <div class="center mt2">
                    <div ng-if="ARC.isLoading">
                        Loading
                    </div>
                    <iframe ng-if="ARC.hasPreview&&!ARC.isLoading" ng-src="{{ARC.previewUrl}}" frameborder="0" width="480" height="299"></iframe>
                </div>
                <form class="center mt2" ng-if="ARC.hasPreview&&ARC.turl&&!ARC.isLoading&&!ARC.emailIsLoading" name="emailForm">
                    <input type="email" name="email" class="input-url" ng-model="ARC.email" placeholder="Your email" required>
                    <button type="submit"  class="submit-btn" ng-click="ARC.sendEmail()">Get Report as PDF</button>
                </form>
                <div class="center mt2" ng-if="ARC.hasPreview&&ARC.turl&&!ARC.isLoading&&ARC.emailIsLoading">
                    <div>Loading</div>
                </div>
                <div class="center mt2" ng-if="ARC.hasPreview&&ARC.turl&&!ARC.isLoading&&!ARC.emailIsLoading&&ARC.emailSent">
                    <div>Email sent</div>
                </div>
        </div>
    </div>
</div>

</body>
</html>
