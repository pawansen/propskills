'use strict';
app.controller('profileController', ['$scope', 'environment', '$localStorage', 'appDB', 'Upload', function ($scope, environment, $localStorage, appDB, Upload) {
    $scope.env = environment;

    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.isLoggedIn = $localStorage.isLoggedIn;

        /*function to get profile details*/
        $scope.profileDetails = {};
        $scope.getProfileInfo = function () {
            var $data = {};
            $data.UserGUID = $localStorage.user_details.UserGUID;
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.Params = 'CitizenStatus,SocialSecurityNumber,UserTeamCode,UserTypeID,UserTypeName,FirstName, MiddleName, LastName, Email, Username, Gender, BirthDate, CountryCode, CountryName, CityName, StateName, PhoneNumber,Address,ReferralCode,ProfilePic,TotalCash,Source';
            appDB
                .callPostForm('users/getProfile', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.profileDetails = data.Data;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });

        }
        /*function to update profile details*/

        $scope.submitted = false;
        $scope.updateProfile = function (form) {
            var $data = {};

            $scope.helpers = Mobiweb.helpers;
            $scope.submitted = true;
            if (!form.$valid) {
                return false;
            }
            $data = {
                FirstName: $scope.profileDetails.FirstName,
                StateName: $scope.profileDetails.StateName,
                SessionKey: $localStorage.user_details.SessionKey,
                UserTeamCode: $scope.profileDetails.UserTeamCode
            };
            $scope.data.listLoading = true;
            appDB
                .callPostForm('users/updateUserInfo', $data)
                .then(
                    function successCallback(data) {
                        $scope.data.listLoading = false;
                        if ($scope.checkResponseCode(data)) {
                            $scope.profileDetails = data.Data;
                            $scope.getProfileInfo();
                            $scope.submitted = false;
                            $scope.successMessageShow(data.Message);
                        }
                    },
                    function errorCallback(data) {
                        $scope.data.listLoading = false;
                        $scope.checkResponseCode(data);
                    });
        }

        /*change password*/
        $scope.updatePassword = [];
        $scope.changePassword = function (createform1) {

            $scope.helpers = Mobiweb.helpers;
            $scope.updateMsg = false;
            $scope.isSubmitted = true;
            if (!createform1.$valid) {
                return false;
            }
            $scope.data.listLoading = true;
            var data = {};
            $scope.errorMsg = "";
            $scope.showMsg = false;
            data.SessionKey = $localStorage.user_details.SessionKey;
            data.CurrentPassword = $scope.CurrentPassword;
            data.Password = $scope.Password;

            $scope.isSubmitted = true;
            appDB
                .callPostForm('users/changePassword', data)
                .then(
                    function success(data) {
                        $scope.data.listLoading = false;
                        if ($scope.checkResponseCode(data)) {
                            $scope.updatePassword = data.response;
                            $scope.successMessageShow(data.Message);
                        }
                    },
                    function error(data) {
                        $scope.data.listLoading = false;
                        $scope.checkResponseCode(data)
                    }
                );
        }

        /*update profile image*/
        $scope.$watch('picFile', function (files, old) {

            $scope.formUpload = false;
            if (files != null) {
                $scope.data.listLoading = true;
                var fd = new FormData();
                fd.append('SessionKey', $localStorage.user_details.SessionKey);
                fd.append('File', files);
                fd.append('Section', 'ProfilePic');
                fd.append('Caption', 'Profile Pic');
                appDB
                    .callPostImage('upload/image', fd)
                    .then(
                        function success(data) {
                            $scope.data.listLoading = false;
                            if ($scope.checkResponseCode(data)) {
                                $localStorage.user_details.ProfilePic = data.Data.MediaURL;
                                $scope.getProfileInfo();
                            }
                        },
                        function error(data) {
                            $scope.data.listLoading = false;
                            $scope.errorMsg = data.message;
                        }
                    );

            }
        });

        /*function to get country list*/
        $scope.countryList = [];
        $scope.getCountryList = function () {

            var $data = {};
            appDB
                .callPostForm('utilities/getCountries', $data)
                .then(
                    function successCallback(data) {

                        if ($scope.checkResponseCode(data)) {
                            $scope.countryList = data.Data;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });
        }

        /*function to get states by country code*/
        $scope.stateList = [];
        $scope.getStates = function (CountryCode) {
            var $data = {};
            $data.CountryCode = CountryCode;
            appDB
                .callPostForm('utilities/getStates', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.stateList = data.Data;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });
        }
        // $scope.getStates('IN');
        /*remove profile pic*/
        $scope.removeProfilePic = function () {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.MediaGUID = '';
            appDB
                .callPostForm('upload/delete', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.successMessageShow(data.Message);
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });
        }
    } else {
        window.location.href = base_url;
    }

}]);