'use strict';
app.controller('contestStandingController', ['$scope', 'appDB','$rootScope', function ($scope, appDB,$rootScope) {
    $scope.Standings = [];
    $scope.propertyName = 'percentage';
    $scope.reverse = true;
    $scope.getContestStanding = function () {
        var $data = {};
        appDB
            .callPostForm($rootScope.apiPrefix +'utilities/contestStanding', $data)
            .then(
                function successCallback(data) {
                    if ($scope.checkResponseCode(data) && data.Data.length > 0) {
                        $scope.Standings = data.Data;
                        $scope.Standings.forEach(element => {
                            element.percentage = element.WinContestCount / element.JoinContestCount;
                        });
                    }
                },
                function errorCallback(data) {
                    $scope.checkResponseCode(data);
                });
    }
}]);