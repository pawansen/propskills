"use strict";
app.factory('$remember', function () {
    return function (name, values) {
        var cookie = name + '=';
        cookie += values + ';';
        var date = new Date();
        date.setDate(date.getDate() + 1);
        cookie += 'expires=' + date.toString() + ';';
        document.cookie = cookie;
    }
});

app.controller('homeController', ['$scope', '$localStorage', '$sessionStorage', '$rootScope', '$location', 'appDB', 'environment', '$remember', '$cookies', '$cookieStore', '$timeout', '$http', function ($scope, $localStorage, $sessionStorage, $rootScope, $location, appDB, environment, $remember, $cookies, $cookieStore, $timeout, $http) {

    $scope.redirectToLobby = function () {
        if ($localStorage.hasOwnProperty('user_details')) {
            window.location.href = base_url + 'lobby';
        } else {
            window.location.href = base_url + 'login';
        }
    }

    $scope.redirectToCreateLeague = function () {
        if ($localStorage.hasOwnProperty('user_details')) {
            window.location.href = base_url + 'createLeague';
        } else {
            window.location.href = base_url + 'login';
        }
    }

    if (!$localStorage.hasOwnProperty('user_details')) {

        $scope.startDateBeforeRender = startDateBeforeRender;
        $scope.startDateOnSetTime = startDateOnSetTime;

        function startDateOnSetTime() {
            $scope.$broadcast('start-date-changed');
        }
        $scope.formData = {};
        $scope.Date = new Date();
        var pastYear = $scope.Date.getFullYear() - 18;
        $scope.Date.setFullYear(pastYear);
        $scope.formData.BirthDate = $scope.Date;
        function startDateBeforeRender($dates) {
            if ($scope.Date) {
                var activeDate = moment($scope.Date);
                $dates.filter(function (date) {
                    return date.localDateValue() >= activeDate.valueOf()
                }).forEach(function (date) {
                    date.selectable = false;
                })
            }
        }
        // if (navigator.geolocation) {
        //     navigator.geolocation.getCurrentPosition(showPosition);
        // } else {
        //     $scope.errorMessageShow("Geolocation is not supported by this browser.");
        // }

        // function showPosition(position) {
        //     let lat = position.coords.latitude;
        //     let log = position.coords.longitude;
        //     console.log(position);
        //     $http.get('https://maps.googleapis.com/maps/api/geocode/json?latlng=' + lat + ',' + log + '&sensor=false&key=AIzaSyCD-dQy9qP_DJAxqtYaSGLgWU7hAwEhk5c').then(response => {
        //         console.log(response);
        //     })
        // }

        $scope.loginData = {};
        if ($cookies.get('remeber_me')) {
            var rem_info = JSON.parse($cookies.get('remeber_me'));
            $scope.loginData.Keyword = rem_info.Keyword;
            $scope.loginData.Password = rem_info.Password;
            $scope.loginData.remeber_me = rem_info.remeber_me;
        }

        /*Login*/
        $scope.LoginSubmitted = false;
        $scope.signIn = function (form) {
            var $data = {};
            $scope.helpers = Mobiweb.helpers;
            $scope.login_error = false;
            $scope.login_message = ''; //login message
            $scope.LoginSubmitted = true;
            if (!form.$valid) {
                return false;
            }

            $scope.loginData.Source = 'Direct';
            $scope.loginData.DeviceType = 'Native';
            var $data = $scope.loginData;
            $data.Keyword = $scope.loginData.Username;
            appDB
                .callPostForm('signin', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data) && data.Data != '') {
                            if ($scope.loginData.remeber_me) {
                                $remember('remeber_me', JSON.stringify($data));
                            } else {
                                $cookies.remove('remeber_me');
                            }
                            $localStorage.user_details = data.Data;
                            $localStorage.isLoggedIn = true;
                            $sessionStorage.walletBalance = data.Data.WalletAmount;

                            window.location.href = base_url + 'lobby';
                            //  $scope.loginData = {};
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });

        }


        if (getQueryStringValue('referral')) {
            $scope.ReferralCode = getQueryStringValue('referral');
        }

        /*signUp*/
        $scope.signupSubmitted = false;
        $scope.signUp = function (form) {
            var $data = {};
            $scope.helpers = Mobiweb.helpers;
            $scope.signup_error = false;
            $scope.signup_message = ''; //login message
            $scope.signupSubmitted = true;
            if (!form.$valid) {
                return false;
            }
            if ($scope.formData.BirthDate == '' || $scope.formData.BirthDate == undefined) {
                $scope.errorMessageShow('DOB is required');
                return false;
            }
            $scope.data.listLoading = true;
            $scope.formData.UserTypeID = 2;
            $scope.formData.Source = 'Direct';
            $scope.formData.DeviceType = 'Native';
            $scope.formData.BirthDate = ($scope.formData.BirthDate != '') ? $scope.dateFormatConverter($scope.formData.BirthDate) : '';
            if (getQueryStringValue('referral')) {
                $scope.formData.ReferralCode = getQueryStringValue('referral');
            }
            var data = $scope.formData;
            data.FirstName = $scope.formData.FullName;
            appDB.callPostForm('signup', data).then(
                function success(data) {
                    $scope.data.listLoading = false;
                    if ($scope.checkResponseCode(data)) {
                        $scope.successMessageShow('Please check your email to verify account.');

                        $scope.signupSubmitted = false;
                        $scope.LoginSubmitted = false;
                        $timeout(function () {
                            window.location.href = 'login';
                        }, 1000);
                        $scope.formData = {};
                    }
                },
                function error(data) {
                    $scope.checkResponseCode(data);
                });
        }

        /* send forgot password email */
        $scope.forgotPasswordData = {};
        $scope.forgotEmailSubmitted = false;
        $scope.sendEmailForgotPassword = function (form) {
            $scope.forgotEmailSubmitted = true;
            if (!form.$valid) {
                return false;
            }
            $scope.data.listLoading = true;
            $scope.forgotPasswordData.type = 'Email';
            var data = $scope.forgotPasswordData;
            appDB
                .callPostForm('recovery', data)
                .then(
                    function success(data) {
                        $scope.data.listLoading = false;
                        if ($scope.checkResponseCode(data)) {
                            $scope.closePopup('forgotPassword');
                            $scope.openPopup('verifyForgotPassword');
                            $scope.successMessageShow(data.Message);
                            $scope.forgotPasswordData = {};
                        }
                    },
                    function error(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        /* verify forgot password & create new password */
        $scope.forgotPassword = {};
        $scope.forgotPasswordSubmitted = false;
        $scope.verifyForgotPassword = function (form) {
            $scope.forgotPasswordSubmitted = true;
            if (!form.$valid) {
                return false;
            }
            $scope.data.listLoading = true;
            var data = $scope.forgotPassword;
            appDB
                .callPostForm('recovery/setPassword', data)
                .then(
                    function success(data) {
                        $scope.data.listLoading = false;
                        if ($scope.checkResponseCode(data)) {
                            $scope.closePopup('verifyForgotPassword');
                            $scope.successMessageShow(data.Message);
                            $scope.forgotPassword = {};
                        }

                    },
                    function error(data) {
                        $scope.data.listLoading = false;
                        $scope.checkResponseCode(data)
                    });
        }

        /*resend otp for account verification*/

        /*Social Login*/
        $scope.SocialLogin = function (Source) {

            $rootScope.$on('event:social-sign-in-success', function (event, userDetails) {
                var $data = {};
                $scope.formData = {};

                $scope.formData.UserTypeID = 2;
                $scope.formData.Source = Source;
                $scope.formData.Password = userDetails.uid;
                $scope.formData.DeviceType = 'Native';
                var $data = $scope.formData;
                appDB
                    .callPostForm('signin', $data)
                    .then(
                        function successCallback(data) {

                            if (data.ResponseCode == 200) {

                                $localStorage.user_details = data.Data;
                                $localStorage.isLoggedIn = true;
                                $localStorage.SocialLogin = true;
                                $sessionStorage.walletBalance = data.Data.WalletAmount;
                                $scope.loginData = {};

                                window.location.href = base_url + 'lobby';
                            }
                            if (data.ResponseCode == 500) {

                                var $data = {};
                                delete $scope.formData;
                                $scope.formData = {};

                                $scope.formData.UserTypeID = 2;
                                $scope.formData.Source = Source;
                                $scope.formData.SourceGUID = userDetails.uid;
                                $scope.formData.FirstName = userDetails.name;
                                $scope.formData.DeviceType = 'Native';
                                $scope.formData.Email = userDetails.email;
                                var $data = $scope.formData;
                                appDB
                                    .callPostForm('signup', $data)
                                    .then(
                                        function success(data) {
                                            if ($scope.checkResponseCode(data)) {
                                                $localStorage.SocialLogin = true;
                                                $localStorage.user_details = data.Data;
                                                $localStorage.isLoggedIn = true;
                                                $sessionStorage.walletBalance = data.Data.WalletAmount;

                                                window.location.href = base_url + 'lobby';
                                            }

                                        },
                                        function error(data) {
                                            $scope.checkResponseCode(data);
                                        });
                            }
                        },
                        function errorCallback(data) {
                            delete $scope.formData;
                            var $data = {};
                            $scope.formData = {};
                            $scope.formData.UserTypeID = 2;
                            $scope.formData.Source = Source;
                            $scope.formData.SourceGUID = userDetails.uid;
                            $scope.formData.FirstName = userDetails.name;
                            $scope.formData.DeviceType = 'Native';
                            $scope.formData.Email = userDetails.email;
                            var $data = $scope.formData;
                            appDB
                                .callPostForm('signup', $data)
                                .then(
                                    function success(data) {
                                        if ($scope.checkResponseCode(data)) {
                                            $localStorage.user_details = data.Data;
                                            $localStorage.isLoggedIn = true;
                                            $sessionStorage.walletBalance = data.Data.WalletAmount;
                                            window.location.href = base_url + 'lobby';
                                        }
                                    },
                                    function error(data) {
                                        $scope.checkResponseCode(data);
                                    });
                        });

            });
        }
        /*function to get states by country code*/
        $scope.stateList = [];
        $scope.getStates = function () {
            var $data = {};
            $data.CountryCode = 'US';
            appDB
                .callPostForm('utilities/getStates', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.stateList = data.Data.Records;
                            $timeout(function () {
                                $('#selectpicker').selectpicker({
                                    liveSearch: true
                                });
                                $('#selectpicker').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
                                    $(this).selectpicker('refresh')
                                });

                            }, 500)
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });
        }

    } else {
        let location = window.location.pathname.split('/');;
        if (location[2] != 'index' && location[2] != '') {
            window.location.href = base_url + 'lobby';
        }
    }
}]);