<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>AMPROICalculator</title>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.6/angular.min.js"></script>
    <script>
        (function(){
            var app = angular.module('amproicalculator', []);
            app.controller("ARCController",["$scope","$http","$sce",function($scope,$http,$sce){
                var me=this;
                me.url=null;
                me.hasPreview=false;
                me.previewId=null;
                me.isLoading=false;
                me.submit=function(){
                    me.isLoading=true;
                    if(me.url&&me.url!==""){
                        $http.get("/api/get-report?url="+me.url).then(
                            function(response){
                                console.log(response);
                                me.isLoading=false;
                                me.previewUrl=$sce.trustAsResourceUrl('https://docs.google.com/presentation/d/'+response.data.id+'/embed?start=false&loop=false&delayms=3000');
                                me.hasPreview=true;
                            }
                        );
                    }
                }

            }]);
        })();

    </script>
</head>
<body ng-app="amproicalculator" ng-controller="ARCController as ARC" >
<input type="text" ng-model="ARC.url">
<button ng-click="ARC.submit()" ng-disabled="ARC.isLoading">Preview report</button>
<iframe ng-if="ARC.hasPreview" ng-src="{{ARC.previewUrl}}" frameborder="0" width="480" height="299"></iframe>





</body>
</html>