app.controller('GameInfoController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$timeout', '$filter', '$http', function ($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $timeout, $filter, $http) {
    $scope.env = environment;
    $scope.coreLogic = Mobiweb.helpers;
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.SeriesGUID = getQueryStringValue('SeriesGUID');
        $scope.ContestGUID = getQueryStringValue('ContestGUID');
        $scope.MatchGUID = getQueryStringValue('MatchGUID');
        $scope.UserGUID = (getQueryStringValue('UserGUID')) ? getQueryStringValue('UserGUID') : $scope.user_details.UserGUID;
        /**
         * Get contest info
         */
        $scope.getContest = function () {
            var $data = {};
            $data.ContestGUID = $scope.ContestGUID // Selected ContestGUID
            $data.SessionKey = $localStorage.user_details.SessionKey; // User SessionKey
            $data.Params = 'DraftPlayerSelectionCriteria,ContestDuration,RosterSize,ScoringType,LeagueJoinDateTimeUTC,LeagueJoinDateTime,Status,AuctionStatus,UserInvitationCode,ContestID,LeagueJoinDateTime,GameType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,Status,TotalJoined,CustomizeWinning,GameType';
            $data.TimeZone = $scope.getTimeZone();
            appDB
                .callPostForm('nba/SnakeDrafts/getContest', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.Contest = data.Data;
                            $scope.getMySquad();
                            $scope.getTransactions();
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });
        }

        /**
         * Get my player roaster with matches
         */

        $scope.userInfo = {};
        $scope.getMySquad = function () {
            var $data = {};
            $data.SeriesGUID = $scope.SeriesGUID;
            $data.ContestGUID = $scope.ContestGUID // Selected ContestGUID
            $data.MatchGUID = $scope.MatchGUID;
            $data.UserGUID = $scope.UserGUID;
            $data.Status = $scope.Contest.Status;
            $data.MySquadPlayer = 'Yes';
            $data.IsAssistant = 'No';
            $data.IsPreTeam = 'No';
            $data.PlayerBidStatus = 'Yes';
            $data.Params = 'TeamNameShort,IsInjuries,PlayerSelectTypeRole,PlayerPic,PlayerRoleShort,TeamName,PlayerStatus,PlayerBattingStats,UserTeamGUID';
            appDB
                .callPostForm('nba/SnakeDrafts/getPlayersMyTeam', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            if (data.Data.Records) {
                                var MySquadTeams = data.Data.Records;
                                $scope.UserTeamGUID = data.Data.UserTeamGUID;
                                $scope.MySquadTeams = MySquadTeams;
                                $scope.MySquadTeams.forEach(e => {
                                    if (e.hasOwnProperty('PlayerName')) {
                                        e.isSelected = true;
                                        e.PlayerBattingStats.points_per_game = (e.PlayerBattingStats.points_per_game) ? e.PlayerBattingStats.points_per_game * 1 : 0
                                        $scope.playerList.push(e)
                                    }
                                });
                            }
                            $scope.getSnakeDraftUsers();
                            $scope.getFreeAgentPlayers();
                        } else if (data.Data.length == 0) {
                            setTimeout(function () {
                                if ($scope.Contest.ScoringType == 'PointLeague') {
                                    window.location.href = base_url + 'pointsLeaderboard?ContestGUID=' + $scope.ContestGUID + '&SeriesGUID=' + $scope.SeriesGUID;
                                }
                            }, 1000);
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });
        }

        $scope.activeTab = 'contest';
        $scope.selectTab = function (tab) {
            $scope.activeTab = tab;
        }
        $scope.ActiveRoleTab = '';
        $scope.chnageTab = function (tab) {
            $scope.ActiveRoleTab = tab;
        }
        /**
         * Get participants sqauds
         */
        $scope.OtherUserSquads = [];
        $scope.UsersList = [];
        $scope.getDraftHistory = function () {
            var $data = {};
            $data.SeriesGUID = $scope.SeriesGUID; //  Series Id
            $data.ContestGUID = $scope.ContestGUID;
            $data.UserGUID = $scope.user_details.UserGUID;
            $data.GameType = $scope.Contest.GameType;
            $data.DraftHistory = 'No';
            appDB
                .callPostForm('nba/SnakeDrafts/getJoinedDraftAllTeams', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.OtherUserSquads = data.Data;
                            $scope.OtherUserSquads['Rounds 1'].forEach(e => {
                                let index = $scope.ContestUserList.map(e => { return e.UserID }).indexOf(e.UserID);
                                if (index != -1) {
                                    $scope.UsersList.push($scope.ContestUserList[index]);
                                }
                            })
                            console.log($scope.UsersList);
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        /**
         * Get draft user list
         */
        $scope.ContestUserList = [];
        $scope.getSnakeDraftUsers = function () {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
            $data.SeriesGUID = $scope.SeriesGUID; //Series GUID
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.Params = 'UserTeamCode,FirstName,UserGUID,ProfilePic';
            appDB
                .callPostForm('nba/SnakeDrafts/getJoinedContestsUsers', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.ContestUserList = data.Data.Records;
                            $scope.getDraftHistory();
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        /**
         * Get free agent player list
         */
        $scope.propertyName = 'PlayerBattingStats.points_per_game';
        $scope.reverse = true;
        $scope.playerList = [];
        $scope.getFreeAgentPlayers = function () {
            var $data = {};
            $data.SeriesGUID = $scope.SeriesGUID; //  Series Id
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.ContestGUID = $scope.ContestGUID;
            $data.MatchGUID = $scope.MatchGUID;
            $data.PlayerBidStatus = 'Yes';
            $data.GameType = $scope.Contest.GameType;
            $data.Params = 'TeamNameShort,IsInjuries,PlayerRoleShort,PlayerPosition,TeamName,BidSoldCredit,PlayerStatus,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBattingStats,IsPlaying';
            $data.PlayerStatus = 'Upcoming';
            $data.IsPlayRoster = 'Yes';
            appDB
                .callPostForm('nba/SnakeDrafts/getPlayersDraft', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data) && data.Data.Records.length > 0) {
                            data.Data.Records.forEach(e => {
                                e.isSelected = false;
                                e.PlayerBattingStats.points_per_game = (e.PlayerBattingStats.points_per_game) ? e.PlayerBattingStats.points_per_game * 1 : 0
                                $scope.playerList.push(e);
                            });
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        $scope.playerRole = '';
        $scope.addPlayer = function (player) {
            $scope.playerRole = '';
            if (player.hasOwnProperty('PlayerName')) {
                $scope.PlayerInfo = player;
                $scope.playerRole = ($scope.ActiveRoleTab == 'FLEX') ? 'FLEX' : player.PlayerRoleShort;
                $scope.openPopup('addPlayerConfirm')
            } else {
                $scope.playerRole = player.PlayerSelectTypeRole;
                $scope.openPopup('addPlayer')
            }
        }
        $scope.PlayerInfo = [];
        $scope.removePlayer = function (player) {
            $scope.PlayerInfo = player;
            $scope.playerRole = (player.PlayerSelectTypeRole) ? player.PlayerSelectTypeRole : player.PlayerRoleShort;
            $scope.openPopup('removePlayer')
        }

        $scope.confirmRemovePlayer = function () {
            var $data = {};
            $data.SeriesGUID = $scope.SeriesGUID; //  Series Id
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.ContestGUID = $scope.ContestGUID;
            $data.PlayerGUID = $scope.PlayerInfo.PlayerGUID;
            $data.UserTeamGUID = $scope.UserTeamGUID;
            $data.PlayerSelectTypeRole = $scope.playerRole;
            $data.MatchGUID = $scope.MatchGUID;
            appDB
                .callPostForm('nba/SnakeDrafts/removeDraftPlayer', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            // $scope.playerList.forEach((e, index) => {
                            //     if (e.PlayerGUID == $scope.PlayerInfo.PlayerGUID) {
                            //         $scope.playerList.splice(index, 1);
                            //     }
                            // })
                            // $scope.MySquadTeams.forEach((e, index) => {
                            //     if (e.hasOwnProperty('PlayerName') && e.PlayerGUID == $scope.PlayerInfo.PlayerGUID) {
                            //         $scope.MySquadTeams.splice(index, 1);
                            //     }
                            // })
                            $scope.closePopup('removePlayer');
                            $timeout(function () {
                                window.location.reload();
                            }, 500)
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        $scope.confirmAddPlayer = function (playerGUID) {
            if (playerGUID == undefined || playerGUID == '') {
                $scope.errorMessageShow('Please select a player');
                return false;
            }
            var $data = {};
            $data.SeriesGUID = $scope.SeriesGUID; //  Series Id
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.ContestGUID = $scope.ContestGUID;
            $data.PlayerGUID = playerGUID;
            $data.UserTeamGUID = $scope.UserTeamGUID;
            $data.PlayerSelectTypeRole = $scope.playerRole;
            $data.MatchGUID = $scope.MatchGUID;
            appDB
                .callPostForm('nba/SnakeDrafts/addDraftPlayer', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            window.location.reload();
                            $scope.closePopup('addPlayer')
                            $scope.closePopup('addPlayerConfirm')
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        /**
        * Get all transaction list
        */
        $scope.AllTransactionList = [];
        $scope.getTransactions = function () {
            var $data = {};
            $data.ContestGUID = $scope.ContestGUID // Selected ContestGUID
            $data.SessionKey = $scope.user_details.SessionKey;
            appDB
                .callPostForm('nba/SnakeDrafts/getDraftPlayerDropAddTransactions', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data) && data.Data.length > 0) {
                            $scope.AllTransactionList = data.Data;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });
        }

        /**
        * Get user list
        */
        $scope.JoinedContestUserStanding = [];
        $scope.getUserTeam = function () {
            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey; //user session key
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.SeriesGUID = $scope.SeriesGUID;
            $data.WeekID = $scope.Contest.WeekStart;
            $data.Params = 'TotalPointsSeason,UserTeamCode,ProfilePic,TotalPoints,UserRank,UserWinningAmount';
            $data.OrderBy = 'UserRank';
            $data.Sequence = 'ASC';
            appDB
                .callPostForm('nba/SnakeDrafts/getJoinedContestsUsers', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.JoinedContestUserStanding = data.Data.Records;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
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
    } else {
        window.location.href = base_url;
    }
}]);