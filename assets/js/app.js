
var app = angular.module('FandomRoyale', ['ngStorage', 'ngAnimate', 'ngFileUpload', 'socialLogin', 'ngCookies', 'ui.bootstrap.datetimepicker', 'ngSanitize']);
var contentType = {
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    }
};
app.factory('socket', function () {
    var socket = io.connect('http://159.65.8.30:3300');
    return socket;
});
/*main controller*/
app.controller('MainController', ["$scope", "$timeout", "$localStorage", "appDB", function ($scope, $timeout, $localStorage, appDB) {
    $scope.data = { dataList: [], totalRecords: '0', pageNo: 1, pageSize: 25, noRecords: false, UserGUID: UserGUID, notificationCount: 0 };
    $scope.orig = angular.copy($scope.data);
    $scope.UserTypeID = UserTypeID;
    $scope.base_url = base_url;
    $scope.Date = new Date();
    $scope.amount = 100;
    $scope.profileDetails = {};
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.isLoggedIn = $localStorage.isLoggedIn;
        $scope.user_details = $localStorage.user_details;
        $scope.getWalletDetails = function () {
            var $data = {};
            $data.UserGUID = $localStorage.user_details.UserGUID;
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.Params = 'Username,PrivateContestFeePercentage,PrivateContestFeeWeek,PrivateContestFeeSeasonLong,MinimumDepositLimit,MaximumDepositLimit,MinimumWithdrawalLimitBank,UserTeamCode,FirstName, Email,ProfilePic,WalletAmount,WinningAmount,CashBonus,TotalCash';
            appDB
                .callPostForm('users/getProfile', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.profileDetails = data.Data;
                            $scope.WinningAmount = $scope.profileDetails.WinningAmount;
                            $localStorage.user_details.Username = data.Data.Username;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });


        }
        $scope.getWalletDetails();
    }
    $scope.getTimeZone = function (type = '') {
        if (type == 'offset') {
            return offset = new Date().getTimezoneOffset();
        } else {
            let offset = new Date().getTimezoneOffset();
            o = Math.abs(offset);
            return (offset < 0 ? "+" : "-") + ("00" + Math.floor(o / 60)).slice(-2) + ":" + ("00" + (o % 60)).slice(-2);
        }
    }
    /**
     * Date converter
     */
    $scope.dateFormatConverter = function (date) {
        if (date == '' || date == undefined || date == null) {
            return '';
        }
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear(),
            hour = '' + d.getHours(),
            min = '' + d.getMinutes();
        if (month.length < 2)
            month = '0' + month;
        if (day.length < 2)
            day = '0' + day;
        if (hour.length < 2)
            hour = '0' + hour;
        if (min.length < 2)
            min = '0' + min;

        return [year, month, day].join('-');
    }

    $scope.timeFormatConverter = function (date) {
        if (date == '' || date == undefined || date == null) {
            return '';
        }
        var d = new Date(date),
            hour = '' + d.getHours(),
            min = '' + d.getMinutes(),
            sec = '' + d.getSeconds();
        if (sec.length < 2)
            sec = '0' + sec;
        if (hour.length < 2)
            hour = '0' + hour;
        if (min.length < 2)
            min = '0' + min;

        return [hour, min, sec].join(':');
    }
    $scope.checkResponseCode = function (data) {
        if (data.ResponseCode == 200) {
            return true;
        } else if (data.ResponseCode == 500) {
            swal(data.Message, {
                buttons: false,
                timer: 2000,
                icon: "error",
            });
            return false;
        } else if (data.ResponseCode == 501) {
            swal(data.Message, {
                buttons: false,
                timer: 2000,
                icon: "warning",
            });
            return false;
        } else if (data.ResponseCode == 502) {
            swal(data.Message, {
                buttons: false,
                timer: 2000,
                icon: "warning",
            });
            $timeout(function () {
                localStorage.clear();
                window.location.reload();
            }, 1000);
            return false;
        }
    }
    $scope.errorMessageShow = function (Message) {
        swal(Message, {
            buttons: false,
            timer: 2000,
            icon: "error",
        });
    }
    $scope.successMessageShow = function (Message) {
        swal(Message, {
            buttons: false,
            timer: 2000,
            icon: "success",
        });
    }
    $scope.warningMessageShow = function (Message) {
        swal(data.Message, {
            buttons: false,
            timer: 2000,
            icon: "warning",
        });
    }
    $scope.moneyFormat = function (money) {
        money = Number(money);
        var a = money.toLocaleString('en-US', {
            maximumFractionDigits: 2,
            style: 'currency',
            currency: 'USD'
        });
        return a;
    }
    $scope.getPage = function (PageGUID) {
        var $data = {};
        $data.PageGUID = PageGUID;
        appDB
            .callPostForm('admin/page/getPage', $data)
            .then(
                function successCallback(data) {
                    $scope.content = data.Data;
                    $scope.Content = $("<div/>").html(data.Data.Content).text();
                },
                function errorCallback(data) {

                });
    }
}]);

$(document).ready(function () {
    $(".form-control").keypress(function (e) {
        if (e.which == 13) {
            $(this.form).find(':submit').focus().click();
        }
    });
    $('[data-toggle="tooltip"]').tooltip();
});

function getQueryStringValue(key) {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return (!vars[key]) ? '' : vars[key];
}


