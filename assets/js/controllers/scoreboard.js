app.controller('scoreboardController', ['$scope', 'environment', '$localStorage', 'appDB', function ($scope, environment, $localStorage, appDB) {
    $scope.env = environment;
    $scope.coreLogic = Mobiweb.helpers;
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.SeriesGUID = getQueryStringValue('SeriesGUID');
        $scope.ContestGUID = getQueryStringValue('ContestGUID');
        $scope.UserGUID = getQueryStringValue('UserGUID');
        $scope.Week = getQueryStringValue('Week');
        $scope.user = $scope.user_details.UserGUID;
        /**
         * Get contest info
         */
        $scope.getContest = function () {
            var $data = {};
            $data.ContestGUID = $scope.ContestGUID // Selected ContestGUID
            $data.SessionKey = $scope.user_details.SessionKey; // User SessionKey
            $data.Params = 'ContestDuration,GameType,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,Status,ContestType,CustomizeWinning,TotalJoined,UserInvitationCode,PlayOff,WeekStart,WeekEnd,ScoringType';
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
                                    if ($scope.Contest.WeekStart == $scope.Week) {
                                        $scope.getUserTeam();
                                    } else {
                                        $scope.getSeasonLongUserList();
                                    }
                                } else {
                                    $scope.getUserTeam();
                                }
                            } else {
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
        $scope.plyersList = [];
        $scope.getUserTeam = function () {
            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey; //user session key
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.SeriesGUID = $scope.SeriesGUID;
            $data.Params = 'UserTeamCode,ProfilePic,TotalPoints,UserRank,PlayerPic,WeekTotalPoints';
            $data.WeekID = $scope.Contest.WeekStart;
            appDB
                .callPostForm('SnakeDrafts/getJoinedContestsUsers', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.JoinedContestUserList = data.Data.Records;
                            $scope.getOtherUserScoreCard($scope.UserGUID)
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });

        }

        $scope.getOtherUserScoreCard = function (UserGUID) {
            $scope.UserGUID = UserGUID;
            let index = $scope.JoinedContestUserList.map(e => { return e.UserGUID }).indexOf(UserGUID);
            if (index != -1) {
                $scope.plyersList = $scope.JoinedContestUserList[index].UserTeamPlayers;
            }
        }
        /**
        * Get season long user list
        */
        $scope.getSeasonLongUserList = function () {
            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey; //user session key
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.SeriesGUID = $scope.SeriesGUID;
            $data.WeekID = $scope.Week;
            $data.Params = 'UserTeamCode,ProfilePic,TotalPoints,UserRank,WeekTotalPoints,UserWinningAmount';
            $data.OrderBy = 'Rank';
            $data.Sequence = 'ASC';
            appDB
                .callPostForm('SnakeDrafts/contestUserLeaderboard', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.JoinedContestUserList = data.Data.Records;
                            $scope.getOtherUserScoreCard($scope.UserGUID)
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        $scope.PlayerInfo = [];
        $scope.openScoreboardPopup = function(PlayerInfo){
            $scope.PlayerInfo = PlayerInfo;
            // $scope.openPopup('scoreboardPopup');
        }
    } else {
        window.location.href = base_url;
    }
}]);