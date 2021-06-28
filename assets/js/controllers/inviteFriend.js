'use strict';

app.controller('inviteController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$timeout', function ($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $timeout) {
    $scope.env = environment;
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.ContestGUID = getQueryStringValue('ContestGUID');
        $scope.SeriesGUID = getQueryStringValue('SeriesGUID');
        $scope.Contest = [];
        $scope.getContest = function () {
            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey;
            $data.SeriesGUID = $scope.SeriesGUID; //Series GUID
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.Params = 'UserInvitationCode,ContestSize,Status,TotalJoined';
            appDB
                .callPostForm('SnakeDrafts/getContest', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.Contest = data.Data;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });
        }
        /**
         * Send invite friend by email
         */
        $scope.submitted = false;
        $scope.sendEmailInvitation = function (form) {
            $scope.submitted = true;
            if (!form.$valid) {
                return false;
            }
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.Email = $scope.email;
            $data.UserInvitationCode = $scope.Contest.UserInvitationCode;
            appDB
                .callPostForm('SnakeDrafts/contestInviteFriends', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.successMessageShow('Invitation has been sent successfully.')
                            $scope.email = '';
                            $scope.submitted = false;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        /**
         * Send invite friend by phone
         */
        $scope.submittedForm = false;
        $scope.sendPhoneInvitation = function (form) {
            $scope.submittedForm = true;
            if (!form.$valid) {
                return false;
            }
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.Phone = $scope.phone;
            // $data.CountryCode = $scope.countryCode;
            $data.UserInvitationCode = $scope.Contest.UserInvitationCode;
            appDB
                .callPostForm('SnakeDrafts/contestInviteFriends', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.successMessageShow('Invitation has been sent successfully.')
                            $timeout(function () {
                                window.location.reload();
                            }, 1000);
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
    }
    else {
        window.location.href = base_url;
    }
}]);