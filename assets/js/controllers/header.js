'use strict';
app.directive('addCash', ['$localStorage', '$sessionStorage', 'appDB', '$location', function ($localStorage, $sessionStorage, appDB, $location) {
    return {
        restrict: 'E',
        controller: 'headerController',
        templateUrl: 'addCashPopup.php',
        link: function (scope, element, attributes) {
        }
    };
}]);
app.controller('headerController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$sce', 'socialLoginService', '$http', '$timeout', function ($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $sce, socialLoginService, $http, $timeout) {
    if (!($localStorage.hasOwnProperty('FandomGamesType') && $localStorage.FandomGamesType)) {
        $scope.GamesType = 'NBA';
        $rootScope.apiPrefix = '';
        $localStorage.FandomGamesType = $scope.GamesType;
    } else {
        $scope.GamesType = $localStorage.FandomGamesType;
        if ($localStorage.FandomGamesType == 'NBA') {
            $rootScope.apiPrefix = 'nba/';
        } else {
            $rootScope.apiPrefix = '';
        }
    }

    $scope.gameTypeSelection = function (GamesType) {
        $scope.GamesType = GamesType;
        $localStorage.FandomGamesType = GamesType;
        delete $localStorage.MatchGUID;
        delete $localStorage.SeriesGUID;
        if ($localStorage.FandomGamesType == 'NBA') {
            $rootScope.apiPrefix = 'nba/';
        } else {
            $rootScope.apiPrefix = '';
        }
        window.location.reload();
        // window.location.href = base_url + 'lobby';;
    }

    $scope.env = environment;
    $scope.paymentMode = 'payu';
    $scope.headerActiveMenu = 'lobby';
    var pathArray = window.location.pathname.split('/');
    var secondLevelLocation = pathArray[2];
    $scope.base_url = base_url;
    if (window.location.host == 'www.FandomRoyale.com') {
        secondLevelLocation = pathArray[1];
    }
    $scope.headerActiveMenu = secondLevelLocation;
    $scope.page = getQueryStringValue('page');
    if ($scope.page) {
        $timeout(function () {
            $('html,body').animate({
                scrollTop: $("section#point_system").offset().top
            }, 1000);
        }, 1000);
    }
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.isLoggedIn = $localStorage.isLoggedIn;
        $scope.base_url = base_url;
        $scope.referral_url = base_url + $localStorage.user_details.ReferralCode;


        /*get notifications*/
        $scope.notificationList = [];
        $scope.getNotifications = function () {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.PageNo = 1;
            $data.PageSize = 10;
            $data.Status = 1;
            appDB
                .callPostForm('notifications', $data)
                .then(
                    function successCallback(data) {
                        $scope.getNotificationCount();
                        $scope.notificationList = [];
                        if ($scope.checkResponseCode(data) && data.Data.Records) {
                            data.Data.Records.forEach(element => {
                                if (element.NotificationID) {
                                    $scope.notificationList.push(element);
                                }
                            });
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    }
                );
        }

        /*get notification count*/
        $scope.notificationCount = 0;
        $scope.getNotificationCount = function () {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            appDB
                .callPostForm('notifications/getNotificationCount', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.notificationCount = Number(data.Data.TotalUnread);
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    }
                );
        }

        $scope.deleteNotification = function (notification_ids) {
            if (notification_ids == undefined) {
                return false
            }
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.NotificationID = notification_ids;
            $http.post($scope.env.api_url + 'notifications/markRead', $.param($data), contentType).then(function (response) {
                var response = response.data;
                if (response.ResponseCode == 200) {
                    $scope.getNotifications();
                }
            });
        }

        $rootScope.resetPromo = function (isPromoCode) {
            if (!isPromoCode) {
                $scope.PromoCodeFlag = false;
                $scope.PromoCode = '';
                $scope.GotCashBonus = 0;
                $scope.CouponData = {};
            }
        }

        /* 
          Description : To apply coupon code 
        */
        $scope.PromoCodeFlag = false;
        $scope.PromoCode = '';
        $scope.GotCashBonus = 0;
        $scope.CouponData = {};

        /*Add and validate coupon code*/
        $scope.applyPromoCode = function (PromoCode, Amount) {
            $scope.PromoCode = PromoCode;
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.CouponCode = $scope.PromoCode;
            $data.Amount = Amount;
            appDB
                .callPostForm('store/validateCoupon', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.PromoCodeFlag = true;
                            $scope.CouponData = data.Data;
                            if ($scope.CouponData.CouponType == 'Percentage') {
                                $scope.GotCashBonus = ($scope.CouponData.CouponValue / 100) * $scope.amount;
                            } else {
                                $scope.GotCashBonus = $scope.CouponData.CouponValue;
                            }
                            $sessionStorage.CouponGUID = $scope.CouponData.CouponGUID;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    }
                );
        }

        /*Remove applied coupon*/
        $scope.removeCoupon = function () {
            $scope.PromoCodeFlag = false;
            $scope.PromoCode = '';
            $scope.GotCashBonus = 0;
            $scope.CouponData = {};
            delete $sessionStorage.CouponGUID;
        }

        /*add cash popup*/
        $scope.addMoreCash = function (amnt) {
            $scope.removeCoupon();
            $scope.amount = (!$scope.amount) ? 0 : $scope.amount;
            $scope.amount = Number($scope.amount) + amnt;
        }
        $scope.cashSubmitted = false;
        $scope.selectPaymentMode = function (amount, form) {
            $scope.cashSubmitted = true;
            if (!form.$valid) {
                return false;
            }
            if (parseFloat(amount) < parseFloat($scope.profileDetails.MinimumDepositLimit)) {
                $scope.errorAmount = true;
                $scope.errorAmountMsg = 'Min add cash limit is $' + $scope.profileDetails.MinimumDepositLimit + '.';
                return false;
            }

            $scope.isWalletSubmitted = false;
            if (!form.$valid) {
                $scope.isWalletSubmitted = true;
                return false;
            }
            if (parseFloat($scope.amount) > parseFloat($scope.profileDetails.MaximumDepositLimit)) {
                $scope.errorAmount = true;
                $scope.errorAmountMsg = 'Max add cash limit is $' + $scope.profileDetails.MaximumDepositLimit + '.';
                return false;
            }
            // if((parseFloat($scope.profileDetails.WalletAmount) + parseFloat($scope.amount)) > parseFloat($scope.profileDetails.MaximumDepositLimit)){
            //     $scope.errorMessageShow('Max add cash limit is $'+$scope.profileDetails.MaximumDepositLimit+'.Currently you have $'+$scope.profileDetails.WalletAmount+'.You can only deposit $'+(parseFloat($scope.profileDetails.MaximumDepositLimit) - parseFloat($scope.profileDetails.WalletAmount))+'.');
            //     return false;
            // }
            $rootScope.addBalance = {
                'amount': amount
            };
            $scope.closePopup('add_money');
            $scope.closePopup('add_more_money');
            window.location.href = 'paymentMethod?amount=' + amount;
        }
        /*validate amount*/
        $scope.validateAmount = function () {
            $scope.isWalletSubmitted = false;
            $scope.errorAmount = false;
            $scope.errorAmount = '';
            if ($scope.amount.match(/^0[0-9].*$/)) {
                $scope.amount = $scope.amount.replace(/^0+/, '');
            }
            if ($scope.amount < 1) {
                $scope.errorAmount = true;
                $scope.errorAmountMsg = 'Min add cash limit $1';
                return false;
            }
            if ($scope.amount > parseFloat($scope.profileDetails.MaximumDepositLimit)) {
                $scope.amount = ($scope.profileDetails.MaximumDepositLimit);
                $scope.errorAmount = true;
                $scope.errorAmountMsg = 'Max add cash limit is $' + parseFloat($scope.profileDetails.MaximumDepositLimit) + '.';
                return false;
            }
        }

        if (getQueryStringValue('amount')) {
            $scope.amount = getQueryStringValue('amount');
        }

        $scope.addExtraCash = function (amount) {
            $scope.amount = parseFloat(amount);
        }

        $scope.payPalReq = function () {

            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.PaymentGateway = 'Paypal';
            $data.RequestSource = 'Web';
            $data.Amount = Number($scope.amount);
            $data.FirstName = $localStorage.user_details.FirstName;
            $data.Email = $localStorage.user_details.Email;
            $data.PhoneNumber = $localStorage.user_details.PhoneNumber;
            if ($sessionStorage.hasOwnProperty('CouponGUID')) {
                $data.CouponGUID = $sessionStorage.CouponGUID;
            }
            $scope.isWalletSubmitted = true;
            $rootScope.payUData = {};
            appDB
                .callPostForm('wallet/add', $data)
                .then(
                    function success(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.payPalData = data.Data;
                            paypal.Button.render({
                                braintree: braintree,
                                client: {
                                    production: $scope.payPalData.ClientToken,
                                    // sandbox: $scope.payPalData.ClientToken
                                },
                                env: 'production', // Or 'sandbox'
                                commit: false, // This will add the transaction amount to the PayPal button
                                payment: function (data, actions) {
                                    return actions.braintree.create({
                                        flow: 'checkout', // Required
                                        amount: Number($scope.amount), // Required
                                        currency: 'USD', // Required
                                    });
                                },
                                onAuthorize: function (payload) {
                                    var $data = {
                                        "SessionKey": $localStorage.user_details.SessionKey,
                                        "PaymentGateway": "Paypal",
                                        "PaymentGatewayStatus": 'Success',
                                        // "WalletID":get.response.productinfo,
                                        "WalletID": $scope.payPalData.OrderID,
                                        "PaymentGatewayResponse": JSON.stringify(payload),
                                        "PaymentNonce": payload.nonce
                                    };
                                    appDB
                                        .callPostForm('wallet/confirm', $data)
                                        .then(
                                            function success(data) {
                                                if ($scope.checkResponseCode(data)) {
                                                    $localStorage.user_details.WalletAmount = parseFloat(data.Data.WalletAmount).toFixed(2);
                                                    delete $sessionStorage.CouponGUID;
                                                    setTimeout(function () {
                                                        window.location.href = base_url + 'myAccount?status=success';
                                                    }, 1000);

                                                    $scope.successMessageShow(data.Message);
                                                } else {
                                                    window.location.href = base_url + 'myAccount?status=failed';
                                                    $scope.errorMessageShow(data.Message);
                                                }
                                            },
                                            function error(data) {
                                                console.log('error', data);
                                            }
                                        );
                                },
                                onCancel: function (response) {
                                    var $data = {
                                        "SessionKey": $localStorage.user_details.SessionKey,
                                        "PaymentGateway": "Paypal",
                                        "PaymentGatewayStatus": 'Cancelled',
                                        // "WalletID":get.response.productinfo,
                                        "WalletID": $scope.payPalData.OrderID,
                                        // "PaymentGatewayResponse": JSON.stringify(payload),
                                        // "PaymentNonce": payload.nonce
                                    };
                                    appDB
                                        .callPostForm('wallet/confirm', $data)
                                        .then(
                                            function success(data) {
                                                if ($scope.checkResponseCode(data)) {
                                                    $localStorage.user_details.WalletAmount = parseFloat(data.Data.WalletAmount).toFixed(2);
                                                    delete $sessionStorage.CouponGUID;
                                                    setTimeout(function () {
                                                        window.location.href = base_url + 'myAccount?status=Cancelled';
                                                    }, 1000);
                                                } else {
                                                    window.location.href = base_url + 'myAccount?status=failed';
                                                    $scope.errorMessageShow(data.Message);
                                                }
                                            },
                                            function error(data) {
                                                console.log('error', data);
                                            }
                                        );
                                }
                            }, '#paypal-button');
                        }
                    },
                    function error(data) {
                        $scope.checkResponseCode(data)
                    }
                );
        }

        /*Logout*/
        $scope.logout = function () {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            appDB
                .callPostForm('signin/signout/', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            localStorage.clear();
                            window.location.href = base_url;
                        }
                    },
                    function errorCallback(data) {
                        localStorage.clear();
                    }
                );
        }

    }
}]);
app.directive('addMoreCash', ['$localStorage', '$sessionStorage', 'appDB', '$location', function ($localStorage, $sessionStorage, appDB, $location) {
    return {
        restrict: 'E',
        controller: 'headerController',
        templateUrl: 'balance.php',
        link: function (scope, element, attributes) {
        }
    };
}]);

app.directive('addWithdrawalRequest', ['$localStorage', '$sessionStorage', 'appDB', '$location', function ($localStorage, $sessionStorage, appDB, $location) {
    return {
        restrict: 'E',
        controller: 'headerController',
        templateUrl: 'WithdrawalRequest.php',
        link: function (scope, element, attributes) {
            scope.withdrawSubmitted = false;
            scope.showOtp = false;
            scope.withdrawRequest = function (form, amount, PaymentGateway) {
                scope.helpers = Mobiweb.helpers;
                scope.withdrawSubmitted = true;
                if (!form.$valid) {
                    return false;
                }
                if (parseFloat(amount) < parseFloat(scope.profileDetails.MinimumWithdrawalLimitBank)) {
                    scope.errorMessageShow('Minimum withdrawal amount is $' + scope.profileDetails.MinimumWithdrawalLimitBank + '.');
                    return false;
                } else if (parseFloat(amount) > parseFloat(scope.profileDetails.WinningAmount)) {
                    scope.errorMessageShow('Withdraw amount can not be more than Winning amount.');
                    return false;
                }
                var $data = {};
                $data.PaymentGateway = PaymentGateway;
                $data.Amount = amount;
                $data.SessionKey = $localStorage.user_details.SessionKey;
                $data.UserGUID = $localStorage.user_details.UserGUID;
                appDB
                    .callPostForm('wallet/withdrawal', $data)
                    .then(
                        function successCallback(data) {
                            if (scope.checkResponseCode(data)) {
                                scope.successMessageShow(data.Message);
                                scope.getWalletDetails();
                                scope.closePopup('withdrawPopup');
                            }
                        },
                        function errorCallback(data) {
                            scope.checkResponseCode(data);
                        });

            }
        }
    };
}]);