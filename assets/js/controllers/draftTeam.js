app.controller('createSnakeDraftTeamController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$timeout', '$filter', '$http', 'socket', '$interval', function ($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $timeout, $filter, $http, socket, $interval) {
    $scope.env = environment;
    $scope.data.pageSize = 50;
    $scope.data.pageNo = 1;
    $scope.coreLogic = Mobiweb.helpers;

    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.SeriesGUID = getQueryStringValue('SeriesGUID');
        $scope.ContestGUID = getQueryStringValue('League');
        $scope.counter = 30;
        $scope.Timer = 30;
        $scope.DraftLiveRound = 1;
        $scope.draft_silder_visible = true;
        $scope.ActiveTab = 'QB';
        $scope.propertyName = '';
        $scope.reverse = false;
        $scope.searchPlayer = '';
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
                SeriesGUID: $scope.SeriesGUID
            }
            socket.emit('DraftName', UserInfo);
            socket.on('DraftPlayerStatus', function (data) {
                $scope.playerList = data.auctionGetPlayer.Data.Records;
                angular.forEach($scope.playerList, function (element) {
                    element.IsAdded = false;
                })
                $scope.ActiveTab = 'QB';
                $scope.$apply();
            });

            socket.on('DraftUserChange', function (data) {
                $scope.Timer = parseInt(data.DraftUserTimer);
                localStorage.setItem('DateTime_' + $scope.ContestGUID, data.Datetime);
                $scope.LiveSnakeUserInfo = data.getBidPlayerData.Data;
                localStorage.setItem('LiveUserInfo_' + $scope.ContestGUID, JSON.stringify($scope.LiveSnakeUserInfo));
                $interval.cancel($scope.interval);
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
                }
                $timeout(function () {
                    $scope.userPlayRounds = $scope.userPlayRounds;
                    $scope.draft_silder_visible = true;
                }, 10);
                $scope.ContestInfo.AuctionStatus = 'Running';
                $scope.$apply();
            });


            socket.on('draftJoinedContestUser', function (data) {
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

            socket.on('DraftBidSuccess', function (data) {
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
                if (BidUserInfo.Player.TeamID == $scope.livePlayerInfo.TeamID) {
                    $scope.selectPlayer('');
                    $scope.livePlayerInfo = [];
                }

                $scope.ContestInfo.AuctionStatus = BidUserInfo.DraftStatus;
                if (BidUserInfo.DraftStatus == 'Completed') {
                    $timeout(function () {
                        swal("Draft Completed", "", {
                            icon: "success",
                        });
                    }, 3000);
                    localStorage.removeItem('DateTime_' + $scope.ContestGUID);
                    $scope.counter = 0;
                    $interval.cancel($scope.interval);
                    $scope.DraftPlayer = false;
                    localStorage.removeItem('LiveUserInfo_' + $scope.ContestGUID);
                }
                $scope.$apply();
            });

            socket.on('DraftBidError', function (data) {
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
                var data = {
                    SeriesGUID: $scope.SeriesGUID,
                    ContestGUID: $scope.ContestGUID,
                    PlayerGUID: Player.PlayerGUID,
                    UserGUID: $scope.user_details.UserGUID,
                    PlayerStatus: 'Sold',
                    PlayerRole: ($scope.ActiveTab == 'FLEX') ? 'FLEX' : Player.PlayerRoleShort
                }
                socket.emit('DraftBid', data);
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
            } else {
                $scope.counter = 0;
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
                    localStorage.setItem('DateTime_' + $scope.ContestGUID, info.DraftUserLiveTime);
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
            $data.Params = 'UserTeamCode,FirstName,UserGUID,ProfilePic,AuctionTimeBank,AuctionBudget,AuctionUserStatus';
            appDB
                .callPostForm('SnakeDrafts/getJoinedContestsUsers', $data)
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
        $scope.week = '';
        $scope.getContest = function () {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
            $data.SeriesGUID = $scope.SeriesGUID; //Series GUID
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.Params = 'ContestDuration,DraftPlayerSelectionCriteria,RosterSize,ScoringType,WeekStart,LeagueJoinDateTimeUTC,LeagueJoinDateTime,Status,AuctionStatus,SeriesGUID,UserInvitationCode,ContestID,LeagueJoinDateTime,GameType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,Status,TotalJoined,CustomizeWinning,CashBonusContribution,GameType';
            $data.WeekInfo = 'Yes';
            $data.TimeZone = $scope.getTimeZone();
            appDB
                .callPostForm('SnakeDrafts/getContest', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.ContestInfo = data.Data;
                            if ($scope.ContestInfo.AuctionStatus == 'Running') {
                                $scope.getUserDraftInLive();
                            } else if ($scope.ContestInfo.AuctionStatus == 'Cancelled') {
                                window.location.href = base_url + 'lobby';
                            }
                            if ($scope.ContestInfo.ContestDuration == 'SeasonLong' && $scope.ContestInfo.WeekTeamInfo.length > 0) {
                                $scope.week = $scope.ContestInfo.WeekTeamInfo[0].WeekID;
                            }
                            $scope.getMySquad();
                            $scope.getOtherSquad();
                            $scope.getPlayersDraft();
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
            appDB
                .callPostForm('SnakeDrafts/getRounds', $data)
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
            $data.PlayerBidStatus = 'Yes';
            $data.GameType = $scope.ContestInfo.GameType;
            $data.Params = 'IsInjuries,PlayerRoleShort,PlayerPosition,TeamName,BidSoldCredit,PlayerStatus,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBattingStats,IsPlaying';
            // $data.OrderBy = 'FantasyPoints';
            // $data.Sequence = 'DESC';
            $data.PlayerStatus = 'Upcoming';
            $data.PlayerRoleShort = $scope.ActiveTab;
            $data.IsPlayRoster = 'Yes';
            appDB
                .callPostForm('SnakeDrafts/getPlayersDraft', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            for (i in data.Data.Records) {
                                data.Data.Records[i].IsAdded = false;
                                $scope.playerList.push(data.Data.Records[i]);
                            }
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
                .callPostForm('SnakeDrafts/checkUserDraftInlive', $data)
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
            $data.MySquadPlayer = 'Yes';
            $data.IsAssistant = 'No';
            $data.IsPreTeam = 'No';
            $data.PlayerBidStatus = 'Yes';
            $data.UserGUID = $scope.user_details.UserGUID;
            $data.GameType = $scope.ContestInfo.GameType;
            $data.WeekID = $scope.ContestInfo.WeekStart;
            $data.Params = 'IsAutoDraft,PlayerSelectTypeRole,PlayerRoleShort,TeamName,PlayerStatus,SeriesGUID,ContestGUID,UserTeamGUID,UserID,IsAssistant,UserTeamName';
            $data.OrderBy = 'UTP.DateTime';
            $data.Sequence = 'ASC';
            appDB
                .callPostForm('SnakeDrafts/getPlayersDraft', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data) && data.Data.TotalRecords > 0) {
                            $scope.MySquadPlayers = data.Data.Records;
                            $scope.MySquadPlayerCount = 0;
                            $scope.AssistantStatus = (data.Data.IsAutoDraft == 'Yes') ? true : false;
                            $scope.ContestInfo.SelectionCriteria = [];
                            for (var key in $scope.ContestInfo.DraftPlayerSelectionCriteria) {
                                let info = { occupied: 0, isCompleted: false, name: key, value: $scope.ContestInfo.DraftPlayerSelectionCriteria[key] };
                                $scope.ContestInfo.SelectionCriteria.push(info)
                            }
                            $scope.MySquadPlayers.forEach(e => {
                                if (e.hasOwnProperty('PlayerName')) {
                                    let index = $scope.ContestInfo.SelectionCriteria.map(e => { return e.name }).indexOf(e.PlayerSelectTypeRole);
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
            appDB
                .callPostForm('SnakeDrafts/getJoinedDraftAllTeams', $data)
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
                $scope.getTimeInSecond(localStorage.getItem('DateTime_' + $scope.ContestGUID));
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
            $data.IsAutoDraft = (AssistantStatus) ? 'Yes' : 'No';
            appDB
                .callPostForm('SnakeDrafts/autoDraftOnOff', $data)
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
        }

        $scope.BannerList = [];
        $scope.getBannerList = function () {
            $scope.IsBannerAvailable = false;
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.Status = 'Active';
            appDB
                .callPostForm('utilities/bannerList', $data, contentType)
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
        * Get season long user list
        */
        $scope.JoinedContestUserList = [];
        $scope.getSeasonLongUserList = function (week) {
            if ($scope.week == '') {
                return false;
            }
            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey; //user session key
            $data.ContestGUID = $scope.ContestGUID; //Contest GUID
            $data.SeriesGUID = $scope.SeriesGUID;
            $data.WeekID = week;
            $data.Params = 'UserTeamCode,ProfilePic,TotalPoints,UserRank,WeekTotalPoints,UserWinningAmount';
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
        $scope.weekChange = function (week) {
            $scope.week = week
            let index = $scope.ContestInfo.WeekTeamInfo.map(e => { return e.WeekID }).indexOf(week);
            if (index != -1) {
                $scope.getSeasonLongUserList(week);
            }
            $scope.week = week;
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
            $data.WeekID = $scope.ContestInfo.WeekStart;
            $data.Params = 'TotalPointsSeason,UserTeamCode,ProfilePic,TotalPoints,UserRank,UserWinningAmount';
            $data.OrderBy = 'UserRank';
            $data.Sequence = 'ASC';
            appDB
                .callPostForm('SnakeDrafts/getJoinedContestsUsers', $data)
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
                $data.Params = 'PlayerRoleShort,TeamName,PlayerBattingStats,IsInjuries,PlayerRole';
                $data.PageNo = $scope.data.pageNo;
                $data.PageSize = $scope.data.pageSize;
                $data.IsPlayRoster = "Yes";
                appDB
                    .callPostForm('SnakeDrafts/getPlayersAll', $data)
                    .then(
                        function successCallback(data) {
                            $scope.Nextdata = true;
                            if ($scope.checkResponseCode(data)) {
                                if (data.Data.hasOwnProperty('Records') && data.Data.Records != '') {
                                    $scope.LoadMoreFlag = true;
                                    for (var i in data.Data.Records) {
                                        $scope.PlayerStatList.push(data.Data.Records[i]);
                                    }
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

