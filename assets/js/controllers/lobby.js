app.controller('lobbyController', ['$scope', '$rootScope', 'environment', '$localStorage', 'appDB', '$timeout', function ($scope, $rootScope, environment, $localStorage, appDB, $timeout) {
    $scope.env = environment;
    $scope.data.pageSize = 15;
    $scope.data.pageNo = 1;
    $scope.coreLogic = Mobiweb.helpers;
    $scope.ContestsTotalCount = 0;
    $scope.UserTeamsTotalCount = 0;
    $scope.ContestDuration = 'Any';
    $scope.FilterEntryFee = [];
    $scope.search_data = [];
    $scope.LeagueJoinDate = '';
    $scope.LeagueJoinTime = "any";
    $scope.Participants = "Any";
    $scope.GamesType = $localStorage.FandomGamesType;
    $scope.ParticipantsList = [{ name: '2'},{ name: '3'}, { name: '6'}, { name: '10'}, { name: '16'}];

    $scope.TimeList = [{ name: '10:00 AM', value: '10:00 AM' }, { name: '11:00 AM', value: '11:00 AM' }, { name: '12:00 PM', value: '12:00 PM' }, { name: '1:00 PM', value: '1:00 PM' }, { name: '3:00 PM', value: '3:00 PM' }, { name: '4:00 PM', value: '4:00 PM' }, { name: '5:00 PM', value: '5:00 PM' }, { name: '6:00 PM', value: '6:00 PM' }, { name: '8:00 PM', value: '8:00 PM' }, { name: '9:00 PM', value: '9:00 PM' }, { name: '10:00 PM', value: '10:00 PM' }, { name: '11:00 PM', value: '11:00 PM' }];
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $timeout(() => {
            if ($scope.GamesType == 'NFL') {
                $scope.getContests(true);
            } else {
                $scope.getMatches();
            }
        }, 500);
        /*
        * To manage Tabs
        */
        $scope.activeTab = 'All';
        $scope.gotoTab = function (tab) {
            $scope.activeTab = tab;
            $scope.JoinedContest(true);
        }
        /*Function to get matches */
        $scope.MatchesList = [];
        $scope.MatchStartDate = '';
        $scope.getMatches = function (GamesType) {
            if(GamesType == "NFL") return true;
            var $data = {};
            $scope.silder_visible = false;
            $data.Params = 'SeriesName,MatchType,MatchNo,MatchStartDateTime,MatchStartDateTimeUTC,TeamNameLocal,TeamNameVisitor,TeamNameShortLocal,TeamNameShortVisitor,TeamFlagLocal,TeamFlagVisitor,MatchLocation,Status,StatusID';
            $data.Status = 'Pending';
            $data.PageSize = 15;
            $data.PageNo = 1;
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.OrderBy = 'MatchStartDateTime';
            $data.Sequence = 'ASC';
            $data.Keyword = $scope.dateFormatConverter($scope.MatchStartDate);
            appDB
                .callPostForm($rootScope.apiPrefix + 'sports/getMatches', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.MatchesList = data.Data;
                            if (data.Data.Records) {
                                if ($localStorage.hasOwnProperty('MatchGUID') && $localStorage.MatchGUID != '') {
                                    $scope.MatchGUID = $localStorage.MatchGUID;
                                } else {
                                    $scope.MatchGUID = data.Data.Records[0].MatchGUID;
                                    $localStorage.MatchGUID = $scope.MatchGUID;
                                }
                                var MatchLive = true;
                                var index = 0;
                                $scope.MatchesList.Records.forEach((e, i) => {
                                    if (e.MatchGUID == $localStorage.MatchGUID) {
                                        MatchLive = false;
                                        index = i;
                                    }
                                })
                                if (MatchLive) {
                                    $scope.MatchGUID = data.Data.Records[0].MatchGUID;
                                    $localStorage.MatchGUID = $scope.MatchGUID;
                                }
                                $scope.matchCenterDetails();
                                $scope.silder_visible = true;
                                $timeout(() => {
                                    $('.slider1').slick('slickGoTo', index);
                                }, 500);

                            } else {
                                $scope.MatchesDetail = {};
                                $scope.Contests = [];
                            }
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        /*Function to get mactch center details*/
        $scope.MatchesDetail = {};
        $scope.matchCenterDetails = function () {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
            $data.MatchGUID = $scope.MatchGUID; //Match GUID
            $data.Params = 'MatchStartDateTimeUTC,SeriesName,MatchType,MatchNo,MatchStartDateTime,TeamNameLocal,TeamNameVisitor,TeamNameShortLocal,TeamNameShortVisitor,TeamFlagLocal,TeamFlagVisitor,MatchLocation,SeriesGUID,Status,TeamGUIDVisitor,TeamGUIDLocal';
            appDB
                .callPostForm($rootScope.apiPrefix + 'sports/getMatch', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.MatchesDetail = data.Data;
                            $scope.getContests(true);
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        /* Get All Contests  */
        $scope.Contests = [];
        $scope.Nextdata = true;
        $scope.getContests = function (status) {
            if ($scope.privateContest) {
                return false;
            }
            if (status) {
                $scope.data.pageNo = 1;
                $scope.Contests = [];
                $scope.LoadMoreFlag = true;
                $scope.data.noRecords = false;
            }
            if ($scope.LoadMoreFlag == false || $scope.data.noRecords == true || $scope.Nextdata == false) {
                return false;
            }
            if ($scope.Nextdata) {
                $scope.Nextdata = false;

                var SearchJSON = JSON.stringify($scope.search_data[0]);
                $data = {};
                $data.SessionKey = $localStorage.user_details.SessionKey; // User SessionKey
                $data.PageNo = $scope.data.pageNo; // Page Number
                $data.PageSize = $scope.data.pageSize; // Page Size
                $data.Params = 'ContestDuration,WeekStart,WeekEnd,SubGameType,RosterSize,PlayedRoster,BatchRoster,LeagueJoinDateTimeUTC,SeriesGUID,LeagueJoinDateTime,GameType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,IsJoined,Status,CustomizeWinning,TotalJoined,UserInvitationCode,ScoringType';
                $data.Keyword = (SearchJSON != '') ? SearchJSON : $scope.Keyword;
                $data.ContestFull = 'No';
                $data.Privacy = 'No';
                $data.AuctionStatus = 'Pending';
                $data.LeagueType = 'Draft';
                $data.OrderBy = 'LeagueJoinDateTime';
                $data.Sequence = 'ASC';
                $data.TimeZone = $scope.getTimeZone();
                $data.NewTimeZone = $scope.getTimeZone('offset');
                if ($scope.GamesType == 'NBA') {
                    $data.MatchGUID = $scope.MatchGUID;
                }
                appDB
                    .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getContests', $data)
                    .then(
                        function successCallback(data) {
                            $scope.Nextdata = true;
                            if ($scope.checkResponseCode(data)) {
                                $scope.ContestsTotalCount = data.Data.TotalRecords;
                                if (data.Data.hasOwnProperty('Records') && data.Data.Records != '') {
                                    $scope.LoadMoreFlag = true;
                                    for (var i in data.Data.Records) {
                                        data.Data.Records[i].joinedpercent = parseInt(data.Data.Records[i].TotalJoined) * 100 / parseInt(data.Data.Records[i].ContestSize);
                                        $scope.Contests.push(data.Data.Records[i]);
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
         * To get User joined contest
         */
        $scope.JoinedContest = function (status) {
            if (!$scope.privateContest) {
                return false;
            }
            if (status) {
                $scope.data.pageNo = 1;
                $scope.Contests = [];
                $scope.LoadMoreFlag = true;
                $scope.data.noRecords = false;
            }
            if ($scope.LoadMoreFlag == false || $scope.data.noRecords == true || $scope.Nextdata == false) {
                return false
            }
            if ($scope.Nextdata) {
                $scope.Nextdata = false;

                var SearchJSON = JSON.stringify($scope.search_data[0]);
                var $data = {};
                $data.SessionKey = $localStorage.user_details.SessionKey; //user session key
                $data.Params = 'WeekStart,WeekEnd,SubGameType,RosterSize,PlayedRoster,BatchRoster,LeagueJoinDateTimeUTC,SeriesGUID,LeagueJoinDateTime,GameType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,IsJoined,Status,CustomizeWinning,TotalJoined,UserInvitationCode,ScoringType';
                $data.PageNo = $scope.data.pageNo;
                $data.PageSize = $scope.data.pageSize;
                $data.Keyword = (SearchJSON != '') ? SearchJSON : $scope.Keyword;
                $data.JoinedContestStatusID = 'Yes';
                $data.Status = 'Pending';
                $data.MyJoinedContest = 'Yes';
                // if ($scope.activeTab == 'All') {
                //     $data.Privacy = 'All';
                // } else if ($scope.activeTab == 'Public') {
                //     $data.Privacy = 'No';
                // } else {
                $data.Privacy = 'Yes';
                // }
                $data.LeagueType = 'Draft';
                $data.IsSeriesStarted = 'Yes';
                $data.TimeZone = $scope.getTimeZone();
                appDB
                    .callPostForm($rootScope.apiPrefix + 'SnakeDrafts/getContests', $data)
                    .then(
                        function successCallback(data) {
                            $scope.Nextdata = true;
                            if ($scope.checkResponseCode(data)) {
                                $scope.ContestsTotalCount = data.Data.TotalRecords;
                                if (data.Data.hasOwnProperty('Records') && data.Data.Records != '') {
                                    $scope.LoadMoreFlag = true;
                                    for (var i in data.Data.Records) {
                                        data.Data.Records[i].joinedpercent = parseInt(data.Data.Records[i].TotalJoined) * 100 / parseInt(data.Data.Records[i].ContestSize);
                                        $scope.Contests.push(data.Data.Records[i]);
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
                if ($scope.GamesType == 'NBA') {
                    $data.MatchGUID = $scope.MatchGUID;
                }
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
                                setTimeout(function () {
                                    $scope.data.pageNo = 1;
                                    $scope.getWalletDetails();
                                    $scope.getContests(true);
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
        /* Function for search contest */
        $scope.searchContest = function (search) {
            $scope.Keyword = search;
            $scope.filter();
        }
        /**
         * Show contest winnings payouts 
         */
        $scope.showWinningPayout = function (Winnings) {
            $rootScope.CustomizeWinning = Winnings;
            $scope.openPopup('PayoutBreakUp');
        }

        $scope.setParticipants = function(data) {
            $scope.Participants = data;
            document.getElementById('Participants').value = data;
        }

        /**
         * Contest filter apply function
         */
        $scope.filter = function () {
            $scope.search_data = [];
            if ($scope.ContestDuration && $scope.ContestDuration != 'Any') {
                if ($scope.search_data.length > 0) {
                    $scope.search_data[0].ContestDuration = $scope.ContestDuration;
                } else {
                    $scope.search_data.push({ 'ContestDuration': $scope.ContestDuration });
                }

            }
            if ($scope.Participants && $scope.Participants != "Any") {
                if ($scope.search_data.length > 0) {
                    $scope.search_data[0].ContestSize = $scope.Participants;
                } else {
                    $scope.search_data.push({ 'ContestSize': $scope.Participants });
                }

            }
            if ($scope.LeagueJoinDate) {
                if ($scope.search_data.length > 0) {
                    $scope.search_data[0].LeagueJoinDate = $scope.dateFormatConverter($scope.LeagueJoinDate);
                } else {
                    $scope.search_data.push({ 'LeagueJoinDate': $scope.dateFormatConverter($scope.LeagueJoinDate) });
                }
            }
            if ($scope.MatchStartDate) {
                if ($scope.search_data.length > 0) {
                    $scope.search_data[0].MatchStartDate = $scope.dateFormatConverter($scope.MatchStartDate);
                } else {
                    $scope.search_data.push({ 'ContestStartDateDay': $scope.dateFormatConverter($scope.MatchStartDate) });
                }
            }
            if ($scope.Keyword) {
                if ($scope.search_data.length > 0) {
                    $scope.search_data[0].ContestName = $scope.Keyword;
                } else {
                    $scope.search_data.push({ 'ContestName': $scope.Keyword });
                }
            }
            if ($scope.LeagueJoinTime != 'any') {
                if ($scope.search_data.length > 0) {
                    $scope.search_data[0].LeagueJoinTime = $scope.LeagueJoinTime;
                } else {
                    $scope.search_data.push({ 'LeagueJoinTime': $scope.LeagueJoinTime });
                }
            }
            if ($('#min_price').val() * 1 != 0 || $('#max_price').val() * 1 != 2000) {
                if ($scope.search_data.length > 0) {
                    $scope.search_data[0].EntryFee = [$('#min_price').val() * 1, $('#max_price').val() * 1]
                } else {
                    $scope.search_data.push({ EntryFee: [$('#min_price').val() * 1, $('#max_price').val() * 1] });
                }
            }
            if ($scope.privateContest) {
                $scope.JoinedContest(true);
            } else {
                $scope.getContests(true);
            }
        }

        /**
         * Clear filter info 
         */
        $scope.clear_filter = function () {
            $scope.LeagueJoinDate = '';
            $scope.MatchStartDate = '';
            $scope.LeagueJoinTime = 'any';
            $scope.ContestDuration = 'Any';
            $scope.Participants = "Any";
            $scope.search_data = [];
            $scope.privateContest = false;
            $scope.statusPrivateContest = false;
            $scope.Keyword = '';
            $scope.setParticipants("Any");
            $scope.resetSilder();
            if ($scope.privateContest) {
                $scope.JoinedContest(true);
            } else {
                $scope.getContests(true);
            }
        }
        /**
         * reset range slider
         */
        $scope.resetSilder = function () {
            $(function () {
                $("#slider-range").slider({
                    range: true,
                    orientation: "horizontal",
                    min: 0,
                    max: 2000,
                    values: [0, 2000],
                    step: 100,

                    slide: function (event, ui) {
                        if (ui.values[0] == ui.values[1]) {
                            return false;
                        }
                        $("#min_price").val(ui.values[0]);
                        $("#max_price").val(ui.values[1]);
                    }
                });
                $("#min_price").val($("#slider-range").slider("values", 0));
                $("#max_price").val($("#slider-range").slider("values", 1));

            });
        }
        /**
         * Filter for private contest
         */
        $scope.statusPrivateContest = false;
        $scope.privateContest = false;
        $scope.privateContestFilter = function (status) {
            if (status) {
                $scope.privateContest = true;
                $scope.JoinedContest(true);
            } else {
                $scope.privateContest = false;
                $scope.getContests(true);
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
            if ($scope.GamesType == 'NFL') {
                window.location.href = base_url + 'draftTeam?SeriesGUID=' + info.SeriesGUID + '&League=' + info.ContestGUID;
            } else {
                window.location.href = base_url + 'draftRoom?SeriesGUID=' + info.SeriesGUID + '&League=' + info.ContestGUID + '&MatchGUID=' + $scope.MatchGUID;
            }
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

        /*Function to get another match info */
        $scope.selectMatch = function (MatchInfo) {
            // if (Status || TeamPlayersAvailable === 'No' || ContestAvailable === 'No') {
            //     $scope.errorMessageShow('This match will be open soon.');
            //     return false;
            // }
            $scope.MatchGUID = MatchInfo.MatchGUID;
            $localStorage.MatchGUID = MatchInfo.MatchGUID;
            $scope.matchCenterDetails();
        }
    } else {
        window.location.href = base_url;
    }
}]);
app.directive('slickCustomCarousel', ["$timeout", function ($timeout) {
    return {
        restrict: "A",
        link: {
            post: function (scope, elem, attr) {
                $timeout(function () {
                    $('.slider1').slick({
                        dots: false,
                        infinite: false,
                        speed: 300,
                        slidesToShow: 4,
                        slidesToScroll: 4,
                        responsive: [
                            {
                                breakpoint: 1024,
                                settings: {
                                    slidesToShow: 3,
                                    slidesToScroll: 3,
                                    infinite: true,
                                    dots: false
                                }
                            },
                            {
                                breakpoint: 768,
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

                }, 1);

            }
        }
    }
}]);

