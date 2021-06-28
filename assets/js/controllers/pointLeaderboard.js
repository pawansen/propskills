app.controller('pointLeaderboardController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$timeout', '$filter', '$http', function ($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $timeout, $filter, $http) {
    $scope.env = environment;
    $scope.coreLogic = Mobiweb.helpers;
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.Nextdata = true;
        $scope.ContestGUID = getQueryStringValue('ContestGUID');
        $scope.SeriesGUID = getQueryStringValue('SeriesGUID');
        $scope.week = '';
        /**
         * Get contest info
         */
        $scope.Contest = [];
        $scope.getContest = function () {
            var $data = {};
            $data.ContestGUID = $scope.ContestGUID // Selected ContestGUID
            $data.SessionKey = $scope.user_details.SessionKey; // User SessionKey
            $data.Params = 'isWeekStarted,ContestDuration,CustomizeWinning,GameType,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,Status,ContestType,TotalJoined,MatchStartDateTime,WeekStart,WeekEnd,ScoringType,SubGameType';
            $data.WeekInfo = 'Yes';
            $data.TimeZone = $scope.getTimeZone();
            appDB
                .callPostForm('SnakeDrafts/getContest', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.Contest = data.Data;
                            if ($scope.Contest.ContestDuration == 'SeasonLong') {
                                if ($scope.Contest.WeekTeamInfo.length > 0) {
                                    $scope.Contest.WeekTeamInfo.forEach((e) => {
                                        e.Status = 'Completed';
                                    })
                                    $scope.week = $scope.Contest.WeekTeamInfo[$scope.Contest.WeekTeamInfo.length - 1].WeekID;
                                    $scope.Contest.WeekStart1 = $scope.Contest.WeekStart;
                                    $scope.Contest.WeekStart = $scope.Contest.WeekTeamInfo[0].WeekID;
                                    $scope.getSeasonLongUserList();
                                } else {
                                    $scope.week = $scope.Contest.WeekStart;
                                    $scope.getUserTeam();
                                }
                                if ($scope.Contest.isWeekStarted == 'Yes') {
                                    if ($scope.Contest.WeekTeamInfo.length == 0) {
                                        $scope.Contest.WeekTeamInfo = [{ WeekID: $scope.Contest.WeekStart, Status: 'running' }]
                                    } else {
                                        $scope.Contest.WeekTeamInfo.push({ WeekID: $scope.Contest.WeekStart1, Status: 'running' });
                                    }
                                    $scope.week = $scope.Contest.WeekTeamInfo[$scope.Contest.WeekTeamInfo.length - 1].WeekID;
                                    $scope.getUserTeam();
                                }
                            } else {
                                $scope.week = $scope.Contest.WeekStart;
                                $scope.getUserTeam();
                            }
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });
        }
        /**
         * Get user list
         */
        $scope.JoinedContestUserList = [];
        $scope.getUserTeam = function () {
            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey; //user session key
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.SeriesGUID = $scope.SeriesGUID;
            $data.WeekID = $scope.week;
            $data.Params = 'UserTeamCode,ProfilePic,TotalPoints,UserRank,UserWinningAmount,TotalPointsSeason,WeekTotalPoints';
            $data.OrderBy = 'UserRank';
            $data.Sequence = 'ASC';
            appDB
                .callPostForm('SnakeDrafts/getJoinedContestsUsers', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.JoinedContestUserList = data.Data.Records;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        /**
         * Show contest winnings payouts 
         */
        $scope.showWinningPayout = function (Winnings) {
            $rootScope.CustomizeWinning = Winnings;
            $scope.openPopup('PayoutBreakUp');
        }
        /**
        * Get season long user list
        */
        $scope.getSeasonLongUserList = function () {

            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey; //user session key
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.SeriesGUID = $scope.SeriesGUID;
            $data.WeekID = $scope.week;
            $data.Params = 'UserTeamCode,TotalPointsSeason,ProfilePic,TotalPoints,UserRank,WeekTotalPoints,UserWinningAmount';
            $data.OrderBy = 'Rank';
            $data.Sequence = 'ASC';
            appDB
                .callPostForm('SnakeDrafts/contestUserLeaderboard', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.JoinedContestUserList = data.Data.Records;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        $scope.weekChange = function () {
            let index = $scope.Contest.WeekTeamInfo.map(e => { return e.WeekID }).indexOf($scope.week);
            if (index != -1) {
                if ($scope.Contest.WeekTeamInfo[index].Status == 'Completed') {
                    $scope.getSeasonLongUserList();
                } else {
                    $scope.getUserTeam();
                }
            }
        }
    } else {
        window.location.href = base_url;
    }

}]);
