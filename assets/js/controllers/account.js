'use strict';

app.controller('myAccountController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$http', function ($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $http) {
    $scope.env = environment;
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        //do something

        $scope.pageSize = 15;
        $scope.pageNo = 1;
        $scope.activeTab = 'transaction';
        $scope.ChangeTab = function (tab) {
            $scope.activeTab = tab;
            if (tab == 'transaction') {
                $scope.getAccountInfo(true);
            } else if (tab === 'withdrawal') {
                $scope.getWithdrawals(true);
            }
        }

        $rootScope.TransactionMessage = '';
        if (getQueryStringValue('status')) {
            if (getQueryStringValue('status') == 'failed') {
                $('#TransactionModal').modal('show');
                $rootScope.TransactionMessage = 'Transaction Failed!';
            }

            if (getQueryStringValue('status') == 'success') {
                $('#TransactionModal').modal('show');
                $rootScope.TransactionMessage = 'Transaction Success!';
            }

            if (getQueryStringValue('status') == 'Cancelled') {
                $('#TransactionModal').modal('show');
                $rootScope.TransactionMessage = 'Transaction Cancelled!';
            }
        }


        $scope.getAccountInfo = function (status) {
            if ($scope.activeTab != 'transaction') {
                return false;
            }
            if (status) {
                $scope.pageNo = 1;
                $scope.transactions = [];
                $scope.LoadMoreFlag = true;
                $scope.data.noRecords = false;
            }
            if ($scope.LoadMoreFlag == false || $scope.data.noRecords == true) {
                return false
            }
            var $data = {};

            $data.UserGUID = $localStorage.user_details.UserGUID;
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.Params = "Amount,CurrencyPaymentGateway,TransactionType,TransactionID,Status,Narration,OpeningBalance,ClosingBalance,EntryDate,WalletAmount,WinningAmount,CashBonus,TotalCash";
            $data.TransactionMode = 'All';
            $data.PageNo = $scope.pageNo;
            $data.PageSize = $scope.pageSize;
            $data.Filter = 'FailedCompleted';
            $data.OrderBy = 'EntryDate';
            $data.Sequence = 'DESC';
            $http.post($scope.env.api_url + 'wallet/getWallet', $.param($data), contentType).then(function (response) {
                var response = response.data;
                if ($scope.checkResponseCode(response)) {
                    $scope.TotalTransactionCount = response.Data.TotalRecords;
                    if (response.Data.hasOwnProperty('Records') && response.Data.Records != '') {
                        $scope.LoadMoreFlag = true;
                        for (var i in response.Data.Records) {
                            $scope.transactions.push(response.Data.Records[i]);
                        }
                        $scope.pageNo++;
                    } else {
                        $scope.LoadMoreFlag = false;
                    }
                } else {
                    $scope.data.noRecords = true;
                }
            });

        }
        $scope.getWithdrawals = function (status) {
            if ($scope.activeTab != 'withdrawal') {
                return false;
            }
            if (status) {
                $scope.TotalWithdrawTransactionCount = 0;
                $scope.pageNo = 1;
                $scope.WithdrawTransactions = [];
                $scope.LoadMoreFlag = true;
                $scope.data.noRecords = false;
            }
            if ($scope.LoadMoreFlag == false || $scope.data.noRecords == true) {
                return false
            }
            var $data = {};
            $data.UserGUID = $localStorage.user_details.UserGUID;
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.Params = "Amount,PaymentGateway,EntryDate,Status";
            $data.PageNo = $scope.pageNo;
            $data.PageSize = $scope.pageSize;
            $data.OrderBy = 'EntryDate';
            $data.Sequence = 'DESC';
            $http.post($scope.env.api_url + 'wallet/getWithdrawals', $.param($data), contentType).then(function (response) {
                var response = response.data;
                if ($scope.checkResponseCode(response)) {
                    if (response.Data.hasOwnProperty('Records') && response.Data.Records != '') {
                        $scope.TotalWithdrawTransactionCount = response.Data.TotalRecords;
                        $scope.LoadMoreFlag = true;
                        for (var i in response.Data.Records) {
                            $scope.WithdrawTransactions.push(response.Data.Records[i]);
                        }
                        $scope.pageNo++;
                    } else {
                        $scope.LoadMoreFlag = false;
                    }
                } else {
                    $scope.data.noRecords = true;
                }

            });
        }

        $scope.LicenseDetails = {};

        $scope.getProfileInfo = function () {
            var $data = {};
            $data.UserGUID = $localStorage.user_details.UserGUID;
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.Params = 'MediaPAN,PanStatus';
            appDB
                .callPostForm('users/getProfile', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.Details = data.Data;
                            $scope.LicenseDetails = ($scope.Details.MediaPAN.MediaCaption !== '') ? JSON.parse($scope.Details.MediaPAN.MediaCaption) : {};
                            if ($scope.LicenseDetails && $scope.Details.PanStatus != 'Rejected') {
                                $scope.LicenseImage = $scope.Details.MediaPAN.MediaURL
                            }
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });

        }
        $scope.getProfileInfo();
        /*License upload*/
        $scope.LicenseSubmitted = false;
        $scope.uploadLicenseDetails = function (form, files) {
            $scope.LicenseSubmitted = true;
            $scope.helpers = Mobiweb.helpers;
            if (!form.$valid) {
                return false;
            }
            if (files != null) {
                var fd = new FormData();
                fd.append('SessionKey', $localStorage.user_details.SessionKey);
                fd.append('File', files);
                fd.append('Section', 'PAN');
                fd.append('MediaCaption', JSON.stringify($scope.LicenseDetails));
                appDB
                    .callPostImage('upload/image', fd)
                    .then(
                        function success(data) {
                            if ($scope.checkResponseCode(data)) {
                                $scope.successMessageShow(data.Message);
                                setTimeout(function () {
                                    window.location.reload();
                                }, 1000);
                            }
                        },
                        function error(data) {
                            $scope.checkResponseCode(data);
                        }
                    );

            }
        }

        /**
         * Preview license card image
         */
        $scope.LicenseImage = '';
        $scope.SelectLicenseFile = function (e) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $scope.LicenseImage = e.target.result;
                $scope.$apply();
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    } else {
        window.location.href = base_url;
    }
}]);