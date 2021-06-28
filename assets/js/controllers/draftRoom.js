app.controller('draftRoomController', ['$scope', '$rootScope', 'environment', '$localStorage', 'appDB', '$timeout', '$filter', 'socket', '$interval', '$http', function ($scope, $rootScope, environment, $localStorage, appDB, $timeout, $filter, socket, $interval, $http) {
    $scope.env = environment;
    $scope.data.pageSize = 50;
    $scope.data.pageNo = 1;
    $scope.coreLogic = Mobiweb.helpers;
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.SeriesGUID = getQueryStringValue('SeriesGUID');
        $scope.ContestGUID = getQueryStringValue('League');
        $scope.MatchGUID = getQueryStringValue('MatchGUID');
        $scope.counter = 30;
        $scope.Timer = 30;
        $scope.DraftLiveRound = 1;
        $scope.draft_silder_visible = true;
        $scope.ActiveTab = 'PG';
        $scope.propertyName = 'PlayerBattingStats.points_per_game';
        $scope.reverse = true;
        $scope.searchPlayer = '';
        $scope.remainder_music = document.getElementById("remainder_music");
        /**
         * Socket code start here
         */
        $scope.LiveSnakeUserInfo = [];
        $scope.DraftTeam = true;
        socket.on('connect', function () {
            console.log("Connected");
            var UserInfo = {
                UserGUID: $scope.user_details.UserGUID,
                ContestGUID: $scope.ContestGUID,
                SeriesGUID: $scope.SeriesGUID,
                MatchGUID: $scope.MatchGUID
            }
            socket.emit('NBADraftName', UserInfo);
            socket.on('NBADraftPlayerStatus', function (data) {
                $scope.playerList = data.auctionGetPlayer.Data.Records;
                angular.forEach($scope.playerList, function (element) {
                    // element.Game = (element.Game) ? JSON.parse(element.Game) : {};
                    element.IsAdded = false;
                    element.PlayerBattingStats.points_per_game = element.PlayerBattingStats.points_per_game * 1;
                })
                $scope.ActiveTab = 'PG';
                $scope.$apply();
            });

            socket.on('NBADraftUserChange', function (data) {
                $scope.Timer = parseInt(data.DraftUserTimer);
                localStorage.setItem('NBADateTime_' + $scope.ContestGUID, data.Datetime);
                $scope.LiveSnakeUserInfo = data.getBidPlayerData.Data;
                localStorage.setItem('NBALiveUserInfo_' + $scope.ContestGUID, JSON.stringify($scope.LiveSnakeUserInfo));
                $interval.cancel($scope.interval);
                $scope.stopRemainderMusic();
                $scope.timerStart();
                $scope.DraftLiveRound = $scope.LiveSnakeUserInfo.DraftNextRound;
                $scope.SliderRound = $scope.DraftLiveRound - 1;
                $scope.DraftTeam = true;
                if ($scope.LiveSnakeUserInfo.UserGUID == $scope.user_details.UserGUID) {
                    $scope.DraftTeam = false;
                }
                if ($scope.LiveSnakeUserInfo.DraftLiveRound != data.getBidPlayerData.Data.DraftNextRound) {
                    $scope.draft_silder_visible = true;
                    $('.round_team').slick('slickGoTo', $scope.SliderRound);
                    if ($scope.DraftLiveRound == 2) {
                        $scope.changeAssistantStatus(false);
                    }
                }
                $timeout(function () {
                    $scope.userPlayRounds = $scope.userPlayRounds;
                    $scope.draft_silder_visible = true;
                }, 10);
                $scope.ContestInfo.AuctionStatus = 'Running';
                $scope.$apply();
            });


            socket.on('NBAdraftJoinedContestUser', function (data) {
                var UserGUID = data.UserGUID;
                var Status = data.UserStatus;
                if (UserGUID != undefined && UserGUID != undefined) {
                    for (var i in $scope.userPlayRounds) {
                        for (var j in $scope.userPlayRounds[i].Users) {
                            if ($scope.userPlayRounds[i].Users[j].UserGUID == UserGUID) {
                                $scope.userPlayRounds[i].Users[j].AuctionUserStatus = Status;
                            }
                        }
                    }
                }
                if ($scope.ContestInfo.AuctionStatus == 'Pending' && data.draftJoinedContestUser.Data[0].Users.length != $scope.TotalJoinedUsers) {
                    $scope.getSnakeDraftUsers();
                    $scope.getUserPlayRound();
                } else if (UserGUID == $scope.user_details.UserGUID && Status == 'Online' && $scope.ContestInfo.AuctionStatus != 'Completed') {
                    $scope.DraftTeam = true;
                    $scope.getSnakeDraftUsers();
                }
                $scope.$apply();
            });

            socket.on('NBADraftBidSuccess', function (data) {
                var BidUserInfo = data.responseData.Data;
                if (BidUserInfo.Player.hasOwnProperty('PlayerName')) {
                    $scope.successMessageShow(BidUserInfo.Player.PlayerName + " is selected by " + BidUserInfo.User.UserTeamCode + ".");
                }
                if (BidUserInfo.User.UserGUID == $scope.user_details.UserGUID) {
                    $scope.getMySquad();
                    $scope.getOtherSquad();
                } else if (BidUserInfo.User.UserGUID == $scope.LiveSnakeUserInfo.UserGUID) {
                    $scope.getOtherSquad();
                }
                $scope.getMyPreSquad();
                if (BidUserInfo.Player.TeamID == $scope.livePlayerInfo.TeamID) {
                    $scope.selectPlayer('');
                    $scope.livePlayerInfo = [];
                }
                $scope.stopRemainderMusic();
                $scope.ContestInfo.AuctionStatus = BidUserInfo.DraftStatus;
                if (BidUserInfo.DraftStatus == 'Completed') {
                    $timeout(function () {
                        swal("Draft Completed", "", {
                            icon: "success",
                        });
                    }, 2000);
                    localStorage.removeItem('NBADateTime_' + $scope.ContestGUID);
                    $scope.counter = 0;
                    $interval.cancel($scope.interval);
                    $scope.DraftPlayer = false;
                    localStorage.removeItem('NBALiveUserInfo_' + $scope.ContestGUID);
                }
                $scope.$apply();
            });

            socket.on('NBADraftBidError', function (data) {
                if ($scope.LiveSnakeUserInfo.UserGUID == data.UserGUID && data.UserGUID == $scope.user_details.UserGUID) {
                    $scope.errorMessageShow(data.Message);
                } else if (data.UserGUID == $scope.user_details.UserGUID && data.Message == 'User not in live') {
                    location.reload();
                }
            })
        });
        /**
         * Add player for draft
         */
        $scope.draftPlayerForSnake = function (Player) {
            if (Player.PlayerGUID) {
                var role = Player.PlayerRoleShort;
                if ($scope.ActiveTab == 'FLEX') {
                    role = 'FLEX';
                } else if ($scope.ActiveTab == 'PF/C') {
                    role = 'PF/C';
                }
                var data = {
                    SeriesGUID: $scope.SeriesGUID,
                    ContestGUID: $scope.ContestGUID,
                    PlayerGUID: Player.PlayerGUID,
                    UserGUID: $scope.user_details.UserGUID,
                    MatchGUID: $scope.MatchGUID,
                    PlayerStatus: 'Sold',
                    PlayerRole: role
                }
                socket.emit('NBADraftBid', data);
            } else {
                $scope.errorMessageShow('Please select a Player for draft.');
            }
        }
        /**
         * Get time difference in second
         */
        $scope.getTimeInSecond = function (date) {
            var a = moment(date).format("s");
            var date = $filter('convertIntoUserTimeZone')(date);
            var diffInSeconds = Math.abs(moment().diff(date) / 1000);
            var days = Math.floor(diffInSeconds / 60 / 60 / 24);
            var hours = Math.floor(diffInSeconds / 60 / 60 % 24);
            var minutes = Math.floor(diffInSeconds / 60 % 60);
            var seconds = Math.floor(diffInSeconds % 60);
            var total_second = ((minutes * 60) + seconds);
            if (total_second < ($scope.Timer + parseInt(a))) {
                $scope.counter = ($scope.Timer + parseInt(a)) - total_second;
                if ($scope.counter <= 30 && $scope.LiveSnakeUserInfo.UserGUID == $scope.user_details.UserGUID) {
                    $scope.remainder_music.play();
                }
            } else {
                $scope.counter = 0;
                $scope.stopRemainderMusic();
            }
        }
        /**
         * After refresh set running user info
         */
        $scope.setRunningValues = function (info) {
            $scope.Timer = parseInt(info.DraftUserTimer);
            if ($scope.ContestUserList) {
                for (var i in $scope.ContestUserList) {
                    if ($scope.ContestUserList[i].UserGUID == info.UserGUID) {
                        $scope.LiveSnakeUserInfo = { FirstName: $scope.ContestUserList[i].UserTeamCode, UserGUID: info.UserGUID };
                    }
                }
                $timeout(function () {
                    $('.round_team').slick('slickGoTo', $scope.SliderRound);
                }, 10);
                if (info.UserLiveInTimeSeconds < $scope.Timer) {
                    localStorage.setItem('NBADateTime_' + $scope.ContestGUID, info.DraftUserLiveTime);
                    $scope.timerStart();
                }
            }
        }
        /**
         * select player info & show info
         */
        $scope.livePlayerInfo = [];
        $scope.selectPlayer = function (player) {
            $scope.livePlayerInfo = player;
        }
        $scope.ContestUserList = [];
        $scope.TotalJoinedUsers = 0;
        /**
         * Get draft user list
         */
        $scope.getSnakeDraftUsers = function () {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
            $data.SeriesGUID = $scope.SeriesGUID; //Series GUID
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.Params = 'UserTeamCode,FirstName,UserGUID,ProfilePic,AuctionUserStatus';
            appDB
                .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getJoinedContestsUsers', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.ContestUserList = data.Data.Records;
                            $scope.TotalJoinedUsers = data.Data.TotalRecords;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        /**
         * Get contest info
         */
        $scope.ContestInfo = [];
        $scope.getContest = function () {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
            $data.SeriesGUID = $scope.SeriesGUID; //Series GUID
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.MatchGUID = $scope.MatchGUID;
            $data.Params = 'ContestDuration,DraftPlayerSelectionCriteria,RosterSize,ScoringType,LeagueJoinDateTimeUTC,LeagueJoinDateTime,Status,AuctionStatus,SeriesGUID,UserInvitationCode,ContestID,LeagueJoinDateTime,GameType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,Status,TotalJoined,CustomizeWinning,GameType';
            $data.TimeZone = $scope.getTimeZone();
            appDB
                .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getContest', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.ContestInfo = data.Data;
                            if ($scope.ContestInfo.AuctionStatus == 'Running') {
                                $scope.getUserDraftInLive();
                            } else if ($scope.ContestInfo.AuctionStatus == 'Cancelled') {
                                window.location.href = base_url + 'lobby';
                            }
                            $scope.getMySquad();
                            $scope.getOtherSquad();
                            $scope.getPlayersDraft();
                            $scope.getMyPreSquad();
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        /**
         * Get User play round
         */
        $scope.userPlayRounds = [];
        $scope.getUserPlayRound = function () {
            var $data = {};
            $scope.draft_silder_visible = false;
            $data.SeriesGUID = $scope.SeriesGUID; //  Series Id
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.ContestGUID = $scope.ContestGUID;
            $data.MatchGUID = $scope.MatchGUID;
            appDB
                .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getRounds', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.userPlayRounds = data.Data;
                            $scope.DraftLiveRound = data.DraftLiveRound;
                            $scope.SliderRound = $scope.DraftLiveRound - 1;
                            $scope.draft_silder_visible = true;
                            $timeout(function () {
                                $('.round_team').slick('slickGoTo', $scope.SliderRound);
                            }, 10);

                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        $scope.chnageTab = function (tab) {
            $scope.ActiveTab = tab;
            $scope.getPlayersDraft();
        }
        /**
         * Get league team list
         */
        $scope.playerList = [];
        $scope.getPlayersDraft = function () {
            $scope.playerList = [];
            $scope.SearchTeam = '';
            var $data = {};
            $data.SeriesGUID = $scope.SeriesGUID; //  Series Id
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.ContestGUID = $scope.ContestGUID;
            $data.MatchGUID = $scope.MatchGUID;
            $data.PlayerBidStatus = 'Yes';
            $data.GameType = $scope.ContestInfo.GameType;
            $data.Params = 'TeamNameShort,IsInjuries,PlayerRoleShort,PlayerPosition,TeamName,PlayerStatus,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBattingStats,IsPlaying';
            // $data.OrderBy = 'points_per_game';
            // $data.Sequence = 'DESC';
            $data.PlayerStatus = 'Upcoming';
            $data.PlayerRoleShort = $scope.ActiveTab;
            $data.IsPlayRoster = 'Yes';
            appDB
                .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getPlayersDraft', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            data.Data.Records.forEach(e => {
                                e.Game = (e.Game) ? JSON.parse(e.Game) : {};
                                e.IsAdded = false;
                                e.PlayerBattingStats.points_per_game = e.PlayerBattingStats.points_per_game * 1;
                                for (let i in $scope.PreAssistantPlayers) {
                                    if (e.PlayerGUID == $scope.PreAssistantPlayers[i].PlayerGUID) {
                                        e.IsAdded = true;
                                    }
                                }
                                $scope.playerList.push(e);
                            })
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        /**
         * Get draft in live details
         */
        $scope.getUserDraftInLive = function () {
            var $data = {};
            $data.SeriesGUID = $scope.SeriesGUID; //  Series Id
            $data.ContestGUID = $scope.ContestGUID;
            appDB
                .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/checkUserDraftInlive', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.setRunningValues(data.Data);
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        /**
         * Get my squad 
         */
        $scope.MySquadPlayerCount = 0;
        $scope.getMySquad = function () {
            $scope.MySquadPlayers = [];
            var $data = {};
            $data.SeriesGUID = $scope.SeriesGUID; //  Series Id
            $data.ContestGUID = $scope.ContestGUID;
            $data.MatchGUID = $scope.MatchGUID;
            $data.MySquadPlayer = 'Yes';
            $data.IsAssistant = 'No';
            $data.IsPreTeam = 'No';
            $data.PlayerBidStatus = 'Yes';
            $data.UserGUID = $scope.user_details.UserGUID;
            $data.GameType = $scope.ContestInfo.GameType;
            $data.Params = 'IsAutoDraft,PlayerSelectTypeRole,PlayerRoleShort,TeamName,PlayerStatus,SeriesGUID,ContestGUID,UserTeamGUID,UserID,IsAssistant,UserTeamName';
            $data.OrderBy = 'UTP.DateTime';
            $data.Sequence = 'ASC';
            appDB
                .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getPlayersMyTeam', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data) && data.Data.TotalRecords > 0) {
                            $scope.MySquadPlayers = data.Data.Records;
                            $scope.MySquadPlayerCount = 0;
                            $scope.ContestInfo.SelectionCriteria = [];
                            for (var key in $scope.ContestInfo.DraftPlayerSelectionCriteria) {
                                let info = { occupied: 0, isCompleted: false, name: key, value: $scope.ContestInfo.DraftPlayerSelectionCriteria[key] };
                                $scope.ContestInfo.SelectionCriteria.push(info)
                            }
                            $scope.MySquadPlayers.forEach(e => {
                                if (e.hasOwnProperty('PlayerName')) {
                                    let index;
                                    if ($scope.ContestInfo.ContestSize == 16 && (e.PlayerSelectTypeRole == 'PF' || e.PlayerSelectTypeRole == 'C')) {
                                        index = $scope.ContestInfo.SelectionCriteria.map(e => { return e.name }).indexOf('PF/C');
                                    } else {
                                        index = $scope.ContestInfo.SelectionCriteria.map(e => { return e.name }).indexOf(e.PlayerSelectTypeRole);
                                    }
                                    if (index != -1) {
                                        $scope.ContestInfo.SelectionCriteria[index].occupied++;
                                        if ($scope.ContestInfo.SelectionCriteria[index].occupied == $scope.ContestInfo.SelectionCriteria[index].value) {
                                            $scope.ContestInfo.SelectionCriteria[index].isCompleted = true;
                                        }
                                    }
                                    $scope.MySquadPlayerCount++;
                                }
                            });
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        /**
         * Get participants sqauds
         */
        $scope.OtherUserSquads = [];
        $scope.getOtherSquad = function () {
            var $data = {};
            $data.SeriesGUID = $scope.SeriesGUID; //  Series Id
            $data.ContestGUID = $scope.ContestGUID;
            $data.UserGUID = $scope.user_details.UserGUID;
            $data.GameType = $scope.ContestInfo.GameType;
            $data.DraftHistory = 'Yes';
            appDB
                .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getJoinedDraftAllTeams', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.OtherUserSquads = data.Data;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        /**
         * Confirmation for draft team
         */
        $scope.confirmDraftTeam = function (player) {
            if (player.PlayerStatus == 'Sold') {
                $scope.errorMessageShow('This player is already sold, Please select other player.');
                return false;
            }
            $scope.draftPlayerForSnake(player);
            // swal({
            //     title: "Draft Player",
            //     text: "Are you sure, you want to draft the " + player.PlayerName + "?",
            //     icon: "warning",
            //     buttons: {
            //         confirm: {
            //             text: "Yes",
            //             value: true,
            //             visible: true,
            //             className: "btn-success",
            //             closeModal: true
            //         },
            //         cancel: {
            //             text: "No",
            //             value: false,
            //             visible: true,
            //             className: "btn-danger",
            //             closeModal: true
            //         }
            //     }
            // }).then((value) => {
            //     if (value) {
            //         $scope.draftPlayerForSnake(player);
            //     }
            // });

        }
        /**
         * Timer Start
         */
        $scope.timerStart = function () {
            var timer_reset = function () {
                $scope.getTimeInSecond(localStorage.getItem('NBADateTime_' + $scope.ContestGUID));
            }
            timer_reset();
            $scope.interval = $interval(timer_reset, 1000);
        }

        /**
         * Change Pre assistant team status
         */
        $scope.changeAssistantStatus = function (AssistantStatus) {
            $scope.AssistantStatus = AssistantStatus;
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
            $data.SeriesGUID = $scope.SeriesGUID; //Series GUID
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.MatchGUID = $scope.MatchGUID;
            $data.IsAutoDraft = (AssistantStatus) ? 'Yes' : 'No';
            appDB
                .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/autoDraftOnOff', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {

                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });
        }

        /**
         * draft room tabs
         */
        $scope.Tabs = 'teams';
        $scope.changeTab = function (tab) {
            $scope.Tabs = tab;
            if($scope.Tabs == 'playerStats') {
                window.open('https://www.espn.com/nba/stats/player/_/table/offensive/sort/avgPoints/dir/desc', "_blank");
            }
            if($scope.Tabs == 'schedule') {
                window.open('https://www.espn.com/nba/schedule', '_blank')
            }
            if($scope.Tabs == 'PlayerInjuries') {
                window.open('https://www.espn.com/nba/injuries', '_blank')
            }
            if($scope.Tabs == 'PlayerProjections') {
                window.open('https://www.fantasysp.com/projections/basketball/weekly/', '_blank')
            }
        }

        $scope.BannerList = [];
        $scope.getBannerList = function () {
            $scope.IsBannerAvailable = false;
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.Status = 'Active';
            appDB
                .callPostForm($rootScope.apiPrefix + 'utilities/bannerList', $data, contentType)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data) && data.Data.hasOwnProperty('Records')) {
                            $scope.BannerList = data.Data.Records;
                            $scope.IsBannerAvailable = true;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
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
            $data.Params = 'TotalPointsSeason,UserTeamCode,ProfilePic,TotalPoints,UserRank,UserWinningAmount';
            $data.OrderBy = 'UserRank';
            $data.Sequence = 'ASC';
            appDB
                .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getJoinedContestsUsers', $data)
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

        $scope.PlayerStatList = [];
        $scope.ActiveRoleTab = ''
        $scope.Nextdata = true;
        $scope.searchPlayer = '';

        $scope.chnageTabStats = function (tab) {
            $scope.ActiveRoleTab = tab;
        }

        $scope.propertyName1 = 'PlayerBattingStats.points_per_game';
        $scope.reverse1 = true;
        $scope.getPlayersStats = function (status) {
            if (status) {
                $scope.data.pageNo = 1;
                $scope.PlayerStatList = [];
                $scope.LoadMoreFlag = true;
                $scope.data.noRecords = false;
            }
            if ($scope.LoadMoreFlag == false || $scope.data.noRecords == true || $scope.Nextdata == false) {
                return false;
            }
            if ($scope.Nextdata) {
                $scope.Nextdata = false;
                var $data = {};
                $data.SessionKey = $localStorage.user_details.SessionKey;
                $data.SeriesGUID = $scope.SeriesGUID;
                $data.Params = 'PlayerRoleShort,TeamName,PlayerBattingStats,IsInjuries,PlayerRole,TeamNameShort';
                $data.PageNo = $scope.data.pageNo;
                $data.PageSize = $scope.data.pageSize;
                $data.IsPlayRoster = "Yes";
                appDB
                    .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getPlayersAll', $data)
                    .then(
                        function successCallback(data) {
                            $scope.Nextdata = true;
                            if ($scope.checkResponseCode(data)) {
                                if (data.Data.hasOwnProperty('Records') && data.Data.Records != '') {
                                    $scope.LoadMoreFlag = true;
                                    data.Data.Records.forEach(e => {
                                        e.PlayerBattingStats.points_per_game = (e.PlayerBattingStats.points_per_game) ? e.PlayerBattingStats.points_per_game * 1 : 0
                                        $scope.PlayerStatList.push(e);
                                    })
                                    $scope.data.pageNo++;
                                } else {
                                    $scope.LoadMoreFlag = false;
                                }
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

        /*Function to get mactch details*/
        $scope.MatchDetails = {};
        $scope.matchDetails = function () {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
            $data.MatchGUID = $scope.MatchGUID; //Match GUID
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
        /**
         * Stop remainder music
         */
        $scope.stopRemainderMusic = function () {
            // $scope.myMusic.pause();
            $scope.remainder_music.pause();
        }

        /**
         * Pre-Draft Team Player
         */
        $scope.PreAssistantPlayers = [];
        $scope.getMyPreSquad = function () {
            var $data = {};
            $data.SeriesGUID = $scope.SeriesGUID; //  Series Id
            $data.ContestGUID = $scope.ContestGUID;
            $data.MatchGUID = $scope.MatchGUID;
            $data.PlayerBidStatus = 'Yes';
            $data.MySquadPlayer = 'Yes';
            $data.IsPreTeam = 'Yes';
            $data.IsAssistant = 'Yes';
            $data.PlayerStatus = 'Upcoming';
            $data.GameType = $scope.ContestInfo.GameType;
            $data.Params = 'PlayerRole,PlayerID,PlayerSelectTypeRole,PlayerRoleShort,TeamNameShort,PlayerStatus,UserTeamGUID,UserID,IsAssistant,UserTeamName';
            $data.UserGUID = $scope.user_details.UserGUID;
            $data.OrderBy = 'UTP.AuctionDraftAssistantPriority';
            $data.Sequence ='ASC';
            appDB
                .callPostForm('nba/SnakeDrafts/getPlayersDraft', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data) && data.Data.Records.length > 0) {
                            $scope.PreAssistantPlayers = data.Data.Records;
                            $scope.AssistantStatus = (data.Data.IsAssistant == 'Yes') ? true : false;
                            $scope.UserTeamGUID = data.Data.UserTeamGUID;
                            $scope.UserTeamName = data.Data.UserTeamName;
                            for (let i in $scope.PreAssistantPlayers) {
                                for (let j in $scope.playerList) {
                                    if ($scope.playerList[j].PlayerGUID == $scope.PreAssistantPlayers[i].PlayerGUID) {
                                        $scope.playerList[j].IsAdded = true;
                                    } else {
                                        $scope.playerList[j].IsAdded = false;
                                    }
                                }
                            }
                            $scope.edit = true;
                        } else {
                            $scope.UserTeamGUID = '';
                            $scope.UserTeamName = '';
                            $scope.AssistantStatus = false;
                            $scope.edit = false;
                            for (let j in $scope.playerList) {
                                $scope.playerList[j].IsAdded = false;
                            }
                        }
                        $("#sortable").sortable();
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        $scope.addPlayerToQueue = function (playerInfo) {
            if (playerInfo) {
                let index = $scope.PreAssistantPlayers.map(e => { return e.PlayerGUID }).indexOf(playerInfo.PlayerGUID);
                if (index == -1) {
                    $scope.PreAssistantPlayers.push(playerInfo);
                    this.updatePlayerInAssistant()
                }
            }
        }

        $scope.updatePlayerInAssistant = function () {
            if ($scope.Nextdata) {
                $scope.Nextdata = false;
                var selected_players = [];
                if ($scope.PreAssistantPlayers) {
                    for (var i in $scope.PreAssistantPlayers) {
                        selected_players.push({
                            'PlayerGUID': $scope.PreAssistantPlayers[i].PlayerGUID,
                            'PlayerRoleShort': $scope.PreAssistantPlayers[i].PlayerRoleShort,
                            'PlayerName': $scope.PreAssistantPlayers[i].PlayerName,
                            'PlayerID': $scope.PreAssistantPlayers[i].PlayerID,
                            'PlayerRole': $scope.PreAssistantPlayers[i].PlayerRole
                        });
                    }
                }
                var $data = {};
                $data.SeriesGUID = $scope.SeriesGUID; //   Series GUID
                $data.SessionKey = $localStorage.user_details.SessionKey; //   User session key
                $data.ContestGUID = $scope.ContestGUID;
                $data.MatchGUID = $scope.MatchGUID;
                $data.UserTeamPlayers = selected_players; //   User selected players
                $data.UserTeamType = 'Draft';
                $data.IsPreTeam = 'Yes';
                if ($scope.edit == true && $scope.UserTeamGUID != '') {
                    if($scope.ContestInfo.GameType == 'Nba') {
                        var $url = 'nba/SnakeDrafts/editUserTeam';
                    } else {
                        var $url = 'SnakeDrafts/editUserTeam';
                    }
                    $data.UserTeamGUID = $scope.UserTeamGUID;
                    $data.UserTeamName = $scope.UserTeamName; //   User team name
                } else {
                    if($scope.ContestInfo.GameType == 'Nba') {
                        var $url = 'nba/SnakeDrafts/addUserTeam';
                    } else {
                        var $url = 'SnakeDrafts/addUserTeam';
                    }                
                }
                $http.post($scope.env.api_url + $url, $.param($data), contentType).then(function (response) {
                    var response = response.data;
                    $scope.Nextdata = true;
                    if ($scope.checkResponseCode(response)) {
                        // $scope.successMessageShow(response.Message);
                        $scope.getMyPreSquad();
                    }
                });
            }
        }
        /**
         * remove player from quque
         */
        $scope.removePlayerToQueue = function (playerInfo) {
            let index = this.PreAssistantPlayers.map(e => {
                return e.PlayerGUID
            }).indexOf(playerInfo.PlayerGUID);
            this.PreAssistantPlayers.splice(index, 1);
            this.updatePlayerInAssistant();
        }

        /* Get All Contests  */
        $scope.Contests = [];
        $scope.getSchedule = function () {
            $scope.Contests = [];
            var SearchJSON = JSON.stringify({ 'LeagueJoinDate': $scope.dateFormatConverter($scope.MatchDetails.MatchStartDateTime) });
            $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey; // User SessionKey
            $data.PageNo = 1; // Page Number
            $data.PageSize = 15; // Page Size
            $data.Params = 'SeriesGUID,ContestDuration,SubGameType,RosterSize,LeagueJoinDateTimeUTC,LeagueJoinDateTime,GameType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,IsJoined,Status,CustomizeWinning,TotalJoined,UserInvitationCode,ScoringType';
            $data.Keyword = SearchJSON;
            $data.ContestFull = 'No';
            $data.Privacy = 'No';
            $data.AuctionStatus = 'Pending';
            $data.LeagueType = 'Draft';
            $data.OrderBy = 'LeagueJoinDateTime';
            $data.Sequence = 'ASC';
            $data.TimeZone = $scope.getTimeZone();
            $data.NewTimeZone = $scope.getTimeZone('offset');
            $data.MatchGUID = $scope.MatchGUID;
            console.log($data);
            appDB
                .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getContests', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.ContestsTotalCount = data.Data.TotalRecords;
                            if (data.Data.hasOwnProperty('Records') && data.Data.Records != '') {
                                data.Data.Records.forEach(e => {
                                    if (e.ContestGUID != $scope.ContestGUID) {
                                        $scope.Contests.push(e);
                                    }
                                })
                            }
                        }
                        $timeout(function () {
                            $('[data-toggle="tooltip"]').tooltip();
                        }, 500);
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        /**
         * Check user balance & join Contest
         */

        $scope.check_balance_amount = function (ContestInfo) {
            $scope.ContestDetails = ContestInfo;
            if (parseInt($scope.profileDetails.TotalCash) < parseInt(ContestInfo.EntryFee)) {
                $scope.openPopup('add_more_money');
            } else {
                $scope.openPopup('confirmToEnter');
            }
        }
        /**
         * Join contest function
         */
        $scope.Nextdata = true;
        $scope.JoinContest = function () {
            if ($scope.Nextdata) {
                $scope.Nextdata = false;
                var $data = {};
                $data.ContestGUID = $scope.ContestDetails.ContestGUID;
                $data.SeriesGUID = $scope.ContestDetails.SeriesGUID;
                $data.MatchGUID = $scope.MatchGUID;
                $data.SessionKey = $localStorage.user_details.SessionKey;
                appDB
                    .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/join', $data)
                    .then(
                        function successCallback(data) {
                            $scope.Nextdata = true;
                            if ($scope.checkResponseCode(data)) {
                                $scope.closePopup('confirmToEnter');
                                $scope.successMessageShow(data.Message);
                                setTimeout(function () {
                                    $scope.getWalletDetails();
                                    $scope.getSchedule();
                                }, 1000);
                            }
                        },
                        function errorCallback(data) {
                            $scope.Nextdata = true;
                            $scope.checkResponseCode(data);
                        });
            }
        }
        /**
         * Show contest winnings payouts 
         */
        $scope.showWinningPayout = function (Winnings) {
            $rootScope.CustomizeWinning = Winnings;
            $scope.openPopup('PayoutBreakUp');
        }

        /**
         * Enter draft
         */
        $rootScope.EnterDraft = function (Contest) {
            $rootScope.activeDraftTab = 'pills-1'
            $rootScope.Info = Contest;
            $rootScope.Info.newDate = new Date(Contest.LeagueJoinDateTime);
            $scope.openPopup('EnterDraftModal');
            $scope.getSnakeDraftUsersList(Contest)
            $scope.getPoints();
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
        $rootScope.ContestUserLists = [];
        $scope.getSnakeDraftUsersList = function (Contest) {
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
         * Set Assistant priority
         */
        // $scope.setPriority = function () {
        $("#sortable").sortable({
            connectWith: ".connectedSortable",
            distance: 5,
            delay: 100,
            opacity: 0.6,
            cursor: 'move',
            update: function (event, ui) {
                var order = $(this).sortable('toArray');
                var Teams = [];
                for (i in order) {
                    index = $scope.PreAssistantPlayers.map(e => { return e.PlayerGUID }).indexOf(order[i]);
                    if(index != -1) {
                        Teams.push({
                            'PlayerGUID': $scope.PreAssistantPlayers[index].PlayerGUID,
                            'PlayerRoleShort': $scope.PreAssistantPlayers[index].PlayerRoleShort,
                            'PlayerName': $scope.PreAssistantPlayers[index].PlayerName,
                            'PlayerID': $scope.PreAssistantPlayers[index].PlayerID,
                            'PlayerRole': $scope.PreAssistantPlayers[index].PlayerRole
                        });
                    }

                    // for (j in $scope.PreAssistantPlayers) {
                    //     if ($scope.PreAssistantPlayers[j].PlayerGUID == order[i]) {
                    //         Teams.push({
                    //             'PlayerGUID': $scope.PreAssistantPlayers[i].PlayerGUID,
                    //             'PlayerRoleShort': $scope.PreAssistantPlayers[i].PlayerRoleShort,
                    //             'PlayerName': $scope.PreAssistantPlayers[i].PlayerName,
                    //             'PlayerID': $scope.PreAssistantPlayers[i].PlayerID,
                    //             'PlayerRole': $scope.PreAssistantPlayers[i].PlayerRole
                    //         });
                    //     }
                    // }
                }
                var $data = {};
                $data.SeriesGUID = $scope.SeriesGUID; //   Series GUID
                $data.SessionKey = $localStorage.user_details.SessionKey; //   User session key
                $data.ContestGUID = $scope.ContestGUID;
                $data.MatchGUID = $scope.MatchGUID;
                $data.UserTeamPlayers = Teams; //   User selected players
                $data.UserTeamType = 'Draft';
                $data.IsPreTeam = 'Yes';
                if ($scope.edit == true && $scope.UserTeamGUID != '') {
                    if($scope.ContestInfo.GameType == 'Nba') {
                        var $url = 'nba/SnakeDrafts/editUserTeam';
                    } else {
                        var $url = 'SnakeDrafts/editUserTeam';
                    }                    
                    $data.UserTeamGUID = $scope.UserTeamGUID;
                    $data.UserTeamName = $scope.UserTeamName; //   User team name
                } else {
                    if($scope.ContestInfo.GameType == 'Nba') {
                        var $url = 'nba/SnakeDrafts/addUserTeam';
                    } else {
                        var $url = 'SnakeDrafts/addUserTeam';
                    }                
                }
                $http.post($scope.env.api_url + $url, $.param($data), contentType).then(function (response) {
                    var response = response.data;
                    if ($scope.checkResponseCode(response)) {
                        $scope.successMessageShow('Your assistant player priority has been changed.');
                    }
                });
            }
        }).disableSelection();
        $timeout(function () {
            $('[data-toggle="tooltip"]').tooltip();
        }, 500);
        // }

    } else {
        window.location.href = base_url;
    }
}]);

app.directive('snakeSlickCustomCarousel', ["$timeout", function ($timeout) {
    return {
        restrict: "A",
        link: {
            post: function (scope, elem, attr) {
                $timeout(function () {
                    $('.round_team').slick({
                        dots: false,
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        touchMove: false,
                        arrows: false
                    });
                }, 50);

            }
        }
    }
}]);

app.directive('slickBannerSlider', ["$timeout", function ($timeout) {
    return {
        restrict: "A",
        link: {
            post: function (scope, elem, attr) {
                $timeout(function () {
                    $('.top_banner_slider').slick({
                        dots: false,
                        easing: 'linear',
                        speed: 300,
                        autoplay: true,
                        autoplaySpeed: 3000,
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        arrows: false,
                        responsive: [
                            {
                                breakpoint: 1024,
                                settings: {
                                    slidesToShow: 2,
                                    slidesToScroll: 2,
                                    infinite: true,
                                    dots: false
                                }
                            },
                            {
                                breakpoint: 600,
                                settings: {
                                    slidesToShow: 2,
                                    slidesToScroll: 2
                                }
                            },
                            {
                                breakpoint: 480,
                                settings: {
                                    slidesToShow: 1,
                                    slidesToScroll: 1
                                }
                            }
                        ]
                    });
                }, 50);
            }
        }
    }
}]);

