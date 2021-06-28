app.controller('NBAmyLeagueController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$timeout', '$filter', '$http', function ($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $timeout, $filter, $http) {
    $scope.env = environment;
    $scope.data.pageSize = 15;
    $scope.data.pageNo = 1;
    $scope.coreLogic = Mobiweb.helpers;
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.MatchGUID = getQueryStringValue('MatchGUID'); //Match GUID
        $scope.Nextdata = true;
        /*To manage Tabs*/
        $scope.activeTab = 'upcoming';
        $scope.PageSize = 15;
        $scope.Status = 'Pending'
        $scope.gotoTab = function (tab) {
            $scope.activeTab = tab;
            if ($scope.activeTab == 'upcoming') {
                $scope.Status = 'Pending';
            } else if ($scope.activeTab == 'live') {
                $scope.Status = 'Running';
            } else {
                $scope.Status = 'Completed';
            }
            $scope.LeagueCenter(true);
        }

        /*function to get joined match Status*/
        $scope.MatchesList = [];
        $scope.Statics = [];
        $scope.LeagueCenter = function (ResetStatus) {
            if (ResetStatus) {
                $scope.PageNo = 1;
                $scope.MatchesList = [];
                $scope.LoadMoreFlag = true;
                $scope.data.noRecords = false;
            }
            if ($scope.LoadMoreFlag == false || $scope.data.noRecords == true || $scope.NextData == false) {
                return false;
            }
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
            $data.MatchGUID = ''; //Match GUID
            $data.Params = 'MatchStartDateTimeUTC,TotalUserWinning,MyTotalJoinedContest,SeriesName,MatchType,MatchNo,MatchStartDateTime,TeamNameLocal,TeamNameVisitor,TeamNameShortLocal,TeamNameShortVisitor,TeamFlagLocal,TeamFlagVisitor,MatchLocation,Status,StatusID';
            $data.PageNo = $scope.PageNo;
            $data.PageSize = $scope.PageSize;
            $data.Filter = 'MyJoinedMatch';
            $data.Status = $scope.Status;
            $data.MyJoinedMatchesCount = 1;
            $data.OrderBy = 'MatchStartDateTime';
            $data.Sequence = ($scope.Status != 'Pending') ? 'DESC' : 'ASC';
            appDB
                .callPostForm($rootScope.apiPrefix + 'sports/getMatches', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.MatchesTotalCount = data.Data.TotalRecords;
                            $scope.Statics = data.Data.Statics;
                            if (data.Data.hasOwnProperty('Records') && data.Data.Records != '') {
                                $scope.LoadMoreFlag = true;
                                for (var i in data.Data.Records) {
                                    data.Data.Records[i].MatchStartDateTimeNew = new Date(data.Data.Records[i].MatchStartDateTime);
                                    $scope.MatchesList.push(data.Data.Records[i]);
                                }
                                $scope.PageNo++;
                            } else {
                                $scope.LoadMoreFlag = false;
                            }
                        } else {
                            $scope.data.noRecords = true;
                        }
                    },
                    function errorCallback(data) {
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

        /*To get User joined contest */
        $scope.JoinedContest = function (status) {
            if (status) {
                $scope.data.pageNo = 1;
                $scope.contests = [];
                $scope.LoadMoreFlag = true;
                $scope.data.noRecords = false;
            }
            if ($scope.LoadMoreFlag == false || $scope.data.noRecords == true || $scope.Nextdata == false) {
                return false
            }
            if ($scope.Nextdata) {
                $scope.Nextdata = false;
                var $data = {};
                $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
                $data.Params = 'ContestDuration,LeagueJoinDateTimeUTC,AuctionStatus,SeriesGUID,UserInvitationCode,ContestID,LeagueJoinDateTime,GameType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,Status,TotalJoined,CustomizeWinning,CashBonusContribution,ScoringType,RosterSize';
                $data.PageNo = $scope.data.pageNo;
                $data.PageSize = $scope.data.pageSize;
                $data.MatchGUID = $scope.MatchGUID;
                // $data.JoinedContestStatusID = 'Yes';
                $data.MyJoinedContest = 'Yes';
                $data.Privacy = 'All';
                $data.LeagueType = 'Draft';
                $data.OrderBy = 'LeagueJoinDateTime';
                $data.Sequence = 'ASC';
                $data.TimeZone = $scope.getTimeZone();
                appDB
                    .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getContests', $data)
                    .then(
                        function successCallback(data) {
                            $scope.Nextdata = true;
                            if ($scope.checkResponseCode(data)) {
                                $scope.UserJoinedContestTotalCount = data.Data.TotalRecords;
                                if (data.Data.hasOwnProperty('Records') && data.Data.Records != '') {
                                    $scope.LoadMoreFlag = true;
                                    for (var i in data.Data.Records) {
                                        $scope.contests.push(data.Data.Records[i]);
                                    }
                                    $scope.data.pageNo++;
                                } else {
                                    $scope.LoadMoreFlag = false;
                                }
                                $timeout(function () {
                                    $('[data-toggle="tooltip"]').tooltip();
                                }, 500);
                            } else {
                                $scope.data.noRecords = true;
                            }
                        },
                        function errorCallback(data) {
                            $scope.Nextdata = true;
                            $scope.checkResponseCode(data);
                        });
            }
        }

        /**
         * Enter draft
         */
        $rootScope.EnterDraft = function (Contest) {
            $rootScope.activeDraftTab = 'pills-1'
            $rootScope.Info = Contest;
            $rootScope.Info.newDate = new Date(Contest.LeagueJoinDateTime);
            $scope.openPopup('EnterDraftModal');
            $scope.getSnakeDraftUsers(Contest)
            if (Contest.Privacy == 'Yes') {
                $rootScope.Points = JSON.parse(Contest.PrivatePointSystem);
            } else {
                $scope.getPoints();
            }
        }
        /**
         * draft tab
         */
        $rootScope.activeDraftTab = 'pills-1'
        $rootScope.draftTab = function (tab) {
            $rootScope.activeDraftTab = tab;
        }

        $rootScope.enterDraft = function (info) {
            window.location.href = base_url + 'draftRoom?SeriesGUID=' + info.SeriesGUID + '&League=' + info.ContestGUID + '&MatchGUID=' + $scope.MatchGUID;
        }

        /**
         * Get draft user list
         */
        $rootScope.ContestUserList = [];
        $scope.getSnakeDraftUsers = function (Contest) {
            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey; //user session key
            $data.SeriesGUID = Contest.SeriesGUID; //Series GUID
            $data.ContestGUID = Contest.ContestGUID; //Contest GUID
            $data.Params = 'UserTeamCode,FirstName,UserGUID,ProfilePic,AuctionUserStatus';
            appDB
                .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getJoinedContestsUsers', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $rootScope.ContestUserList = data.Data.Records;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        $rootScope.Points = [];
        $scope.getPoints = function () {
            $rootScope.Points = [];
            var $data = {};
            $data.StatusID = 1;
            appDB
                .callPostForm($rootScope.apiPrefix + 'snakeDrafts/getPoints', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $rootScope.Points = data.Data.Records;
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

        $scope.changeGameType = function (GamesType) {
            $scope.GamesType = GamesType;
            $localStorage.FandomGamesType = GamesType;
            delete $localStorage.MatchGUID;
            delete $localStorage.SeriesGUID;
            if ($localStorage.FandomGamesType == 'NBA') {
                $rootScope.apiPrefix = 'nba/';
                window.location.href = base_url + 'MyJoinedMatches';;
            } else {
                $rootScope.apiPrefix = '';
                window.location.href = base_url + 'myContest';
            }
            // window.location.reload();
        }
    } else {
        window.location.href = base_url;
    }

}]);
