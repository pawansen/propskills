app.controller('pointLeaderboardController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$timeout', '$filter', '$http', function ($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $timeout, $filter, $http) {
    $scope.env = environment;
    $scope.coreLogic = Mobiweb.helpers;
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        if (!localStorage.hasOwnProperty('league_back_url')) {
            localStorage.setItem('league_back_url', document.referrer);
        }
        $scope.user_details = $localStorage.user_details;
        $scope.ContestGUID = getQueryStringValue('ContestGUID');
        $scope.SeriesGUID = getQueryStringValue('SeriesGUID');
        $scope.MatchGUID = getQueryStringValue('MatchGUID');
        /**
         * Get contest info
         */
        $scope.Contest = [];
        $scope.getContest = function () {
            var $data = {};
            $data.ContestGUID = $scope.ContestGUID // Selected ContestGUID
            $data.SessionKey = $scope.user_details.SessionKey; // User SessionKey
            $data.Params = 'ContestDuration,CustomizeWinning,GameType,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,Status,ContestType,TotalJoined,MatchStartDateTime,ScoringType,SubGameType';
            $data.TimeZone = $scope.getTimeZone();
            appDB
                .callPostForm('nba/SnakeDrafts/getContest', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.Contest = data.Data;
                            $scope.getUserTeam();
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
            $data.Params = 'UserTeamCode,ProfilePic,TotalPoints,UserRank,UserWinningAmount';
            $data.OrderBy = 'UserRank';
            $data.Sequence = 'ASC';
            appDB
                .callPostForm('nba/SnakeDrafts/getJoinedContestsUsers', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.JoinedContestUserList = data.Data.Records;
                            $scope.JoinedContestUserList.forEach(e => {
                                e.UserTeamPlayers.forEach(element=>{
                                    element.PointsDataPrivate = (element.PointsDataPrivate)?JSON.parse(element.PointsDataPrivate):[];
                                })
                            });
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

        /*Function to get mactch details*/
        $scope.MatchDetails = {};
        $scope.matchDetails = function () {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
            $data.MatchGUID = $scope.MatchGUID; //Match GUID
            $data.MatchScoreSelectedData = 'Yes';
            $data.Params = 'MatchStartDateTimeUTC,MatchScoreDetails,SeriesName,MatchType,MatchNo,MatchStartDateTime,TeamNameLocal,TeamNameVisitor,TeamNameShortLocal,TeamNameShortVisitor,TeamFlagLocal,TeamFlagVisitor,MatchLocation,SeriesGUID,Status,TeamGUIDVisitor,TeamGUIDLocal';
            appDB
                .callPostForm($rootScope.apiPrefix + 'sports/getMatch', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.MatchDetails = data.Data;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        $scope.Back = function () {
            window.location.href = localStorage.getItem('league_back_url');
            localStorage.removeItem('league_back_url');
        }
        $scope.userPlayers = [];
        $scope.showScoreboard = function (players) {
            $scope.userPlayers = players;
            $scope.openPopup('showPlayerScoreboard');
        }

        $scope.Points = [];
        $scope.getPoints = function () {
            var $data = {};
            $data.StatusID = 1;
            appDB
                .callPostForm('nba/snakeDrafts/getPoints', $data)
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

        /**
         * Full match scorecard
         */
        $scope.allPlayerList = [];
        $scope.fullScorecard = function(){
            $scope.allPlayerList = [];
            var $data = {};
            $data.SeriesGUID = $scope.SeriesGUID; //  Series Id
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.ContestGUID = $scope.ContestGUID;
            $data.MatchGUID = $scope.MatchGUID;
            $data.PlayerBidStatus = 'Yes';
            $data.GameType = $scope.Contest.GameType;
            $data.Params = 'TeamNameShort,IsInjuries,PlayerRoleShort,PlayerPosition,TeamName,PlayerStatus,PlayerID,PlayerRole,PlayerPic';
            $data.IsPlayRoster = 'Yes';
            $data.TotalPoints = 'Yes';
            appDB
                .callPostForm('nba/SnakeDrafts/getPlayersDraft', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data) && data.Data.Records.length > 0) {
                            data.Data.Records.forEach(e => {
                                e.PointsDataPrivate = e.PointsData;
                                e.PlayerSelectTypeRole = e.PlayerRoleShort;
                                $scope.allPlayerList.push(e);
                            });
                            console.log($scope.allPlayerList);
                            $scope.userPlayers = $scope.allPlayerList;
                            $scope.openPopup('showPlayerScoreboard')
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
    } else {
        window.location.href = base_url;
    }

}]);
