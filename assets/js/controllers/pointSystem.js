'use strict';
app.controller('pointSystemController', ['$scope', 'environment', 'appDB','$rootScope', function ($scope, environment, appDB,$rootScope) {
    $scope.env = environment;
    $scope.Points = [];
    $scope.getPoints = function () {
        var $data = {};
        $data.StatusID = 1;
        appDB
            .callPostForm($rootScope.apiPrefix + 'snakeDrafts/getPoints', $data)
            .then(
                function successCallback(data) {
                    if ($scope.checkResponseCode(data)) {
                        $scope.Points = data.Data.Records;
                    }
                },
                function errorCallback(data) {
                    $scope.checkResponseCode(data);
                });
    }
}]);