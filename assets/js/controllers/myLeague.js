app.controller('myLeagueController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$timeout', '$filter', '$http', function ($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $timeout, $filter, $http) {
    $scope.env = environment;
    $scope.data.pageSize = 15;
    $scope.data.pageNo = 1;
    $scope.coreLogic = Mobiweb.helpers;
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.Nextdata = true;
        $scope.GamesType = $localStorage.FandomGamesType;
        var path = window.location.pathname;
        path = path.split('/');
        $scope.Privacy = 'No';
        if (path.length > 0 && path[path.length - 1] == 'myPrivateLeague') {
            $scope.Privacy = 'Yes';
        }
        /*To manage Tabs*/
        $scope.activeTab = 'upcoming';
        $scope.gotoTab = function (tab) {
            $scope.activeTab = tab;
            $scope.JoinedContest(true);
        }

        /*To get User joined contest */
        $scope.JoinedContest = function (status) {
            if($scope.GamesType == 'NBA'){
                return false;
            }
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
                $data.Params = 'isWeekStarted,ContestDuration,PrivatePointSystem,InvitePermission,LeagueJoinDateTimeUTC,AuctionStatus,WeekStart,WeekEnd,SeriesGUID,UserInvitationCode,ContestID,LeagueJoinDateTime,GameType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,Status,TotalJoined,CustomizeWinning,CashBonusContribution,ScoringType,RosterSize';
                $data.PageNo = $scope.data.pageNo;
                $data.PageSize = $scope.data.pageSize;
                if ($scope.activeTab == 'upcoming') {
                    $data.JoinedContestStatusID = 'Yes';
                } else if ($scope.activeTab == 'live') {
                    $data.StatusID = 2;
                } else {
                    $data.StatusID = 5;
                    $data.CompleteContest = 'Yes';
                }
                $data.MyJoinedContest = 'Yes';
                $data.Privacy = $scope.Privacy;
                $data.LeagueType = 'Draft';
                $data.IsSeriesStarted = 'Yes';
                $data.OrderBy = 'LeagueJoinDateTime';
                $data.Sequence = 'ASC';
                $data.WeekInfo = 'Yes';
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
                                        if (data.Data.Records[i].ContestDuration == 'SeasonLong' && data.Data.Records[i].WeekTeamInfo.length > 0) {
                                            data.Data.Records[i].WeekStart = data.Data.Records[i].WeekTeamInfo[0].WeekID;
                                        }
                                        data.Data.Records[i].joinedpercent = parseInt(data.Data.Records[i].TotalJoined) * 100 / parseInt(data.Data.Records[i].ContestSize);
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
            window.location.href = base_url + 'draftTeam?SeriesGUID=' + info.SeriesGUID + '&League=' + info.ContestGUID;
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
            $data.Params = 'UserTeamCode,FirstName,UserGUID,ProfilePic,AuctionTimeBank,AuctionBudget,AuctionUserStatus';
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

        /**
         * Check user balance & join Contest
         */

        $scope.check_balance_amount = function (ContestInfo) {
            $rootScope.ContestInfo = ContestInfo;
            if (parseInt($scope.profileDetails.TotalCash) < parseInt(ContestInfo.EntryFee)) {
                $scope.openPopup('add_more_money');
            } else {
                $scope.openPopup('confirmToEnter');
            }
        }
        /**
         * Join contest function
         */
        $scope.JoinContest = function () {
            if ($scope.Nextdata) {
                $scope.Nextdata = false;
                var $data = {};
                $data.ContestGUID = $rootScope.ContestInfo.ContestGUID;
                $data.SeriesGUID = $rootScope.ContestInfo.SeriesGUID;
                $data.SessionKey = $localStorage.user_details.SessionKey;
                if ($scope.UserInvitationCode) {
                    $data.UserInvitationCode = $scope.UserInvitationCode;
                }
                appDB
                    .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/join', $data)
                    .then(
                        function successCallback(data) {
                            $scope.Nextdata = true;
                            if ($scope.checkResponseCode(data)) {
                                $scope.closePopup('confirmToEnter');
                                $scope.successMessageShow(data.Message);
                                delete $scope.UserInvitationCode;
                                setTimeout(function () {
                                    $scope.data.pageNo = 1;
                                    $scope.getWalletDetails();
                                    $scope.JoinedContest(true);
                                }, 1000);
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

        /*
         Description : To check private contest and join
         */
        $scope.codeSubmitted = false;
        $scope.checkContestCode = function (form, ContestInvitationCode) {
            $scope.codeSubmitted = true;
            if (!form.$valid) {
                return false;
            }
            if ($scope.Nextdata) {
                $scope.Nextdata = false;
                var $data = {};
                $data.SessionKey = $scope.user_details.SessionKey; // User SessionKey
                $data.Params = 'SeriesGUID,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,TotalJoined,CustomizeWinning';
                $data.UserInvitationCode = ContestInvitationCode;
                appDB
                    .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getPrivateContest', $data)
                    .then(
                        function successCallback(data) {
                            $scope.Nextdata = true;
                            if ($scope.checkResponseCode(data)) {
                                var Contests = data.Data;
                                if (Object.keys(Contests).length == 0) {
                                    $scope.errorMessageShow('Invalid League Code');
                                    $scope.ContestInvitationCode = '';
                                } else {
                                    $scope.UserInvitationCode = ContestInvitationCode;
                                    $scope.ContestInvitationCode = '';
                                    $scope.closePopup('JoinContestByCode');
                                    $scope.check_balance_amount(Contests);
                                }
                                $scope.codeSubmitted = false;
                            }
                        },
                        function errorCallback(data) {
                            $scope.Nextdata = true;
                            $scope.checkResponseCode(data)
                        });
            }
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
