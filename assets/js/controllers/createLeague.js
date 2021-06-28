app.controller('createLeagueController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$timeout', function ($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $timeout) {
    $scope.env = environment;
    $scope.helpers = Mobiweb.helpers;
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.ContestSize = '6';
        $scope.MinimumUserJoined = '2';
        $scope.LeagueType = 'Draft'
        $scope.agree = false;
        $scope.ScoringType = 'PointLeague';
        $scope.Duration = 'Weekly'
        $scope.Privacy = 'Yes';
        $scope.InvitePermission = 'ByCreator'
        $scope.Points = [];
        $scope.ContestName = '';
        $scope.Week = '';
        $scope.RosterSize = '6';
        $scope.IsAutoDraft = false;
        $timeout(function () {
            $scope.adminPercent = $scope.profileDetails.PrivateContestFeePercentage;
            $("#draftDateTime").datetimepicker({
                autoclose: true,
                minuteStep: 5,
                startDate: new Date(),
                format: 'yyyy-mm-dd HH:ii p'
            });
        }, 1000)
        //$scope.WinningAmount = 0;
        $scope.WeekList = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];
        $scope.getPoints = function () {
            $rootScope.Points = [];
            var $data = {};
            $data.StatusID = 1;
            appDB
                .callPostForm('snakeDrafts/getPoints', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.Points = data.Data.Records;
                            $scope.Points.forEach(e => {
                                e.Points = Number(e.Points)
                            })
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }
        /**
         * Datepiker
         */
        $scope.startDateBeforeRender = startDateBeforeRender;
        $scope.startDateOnSetTime = startDateOnSetTime;

        function startDateOnSetTime() {
            $scope.$broadcast('start-date-changed');
        }
        $scope.Date = new Date();
        function startDateBeforeRender($dates) {
            if ($scope.Date) {
                var activeDate = moment($scope.Date);

                $dates.filter(function (date) {
                    return date.localDateValue() <= activeDate.valueOf()
                }).forEach(function (date) {
                    date.selectable = false;
                })
            }
        }
        /*
         Description : To create private Contest
         */
        $scope.createContestField = {};
        $scope.submitted = false;
        $scope.CheckCreateContestForm = function (form) {
            $scope.submitted = true;
            if (!form.$valid) {
                return false;
            } else {
                $scope.activeTab('scoring');
            }
        }
        /**
         * Create Private League
         */
        $scope.CreateContest = function () {
            if ($scope.EntryFee == '' || $scope.EntryFee == undefined || $scope.EntryFee == null) {
                $scope.errorMessageShow("Entry Fee must be required.");
                return false;
            } else if ($scope.ContestName.length == 0) {
                $scope.errorMessageShow("League Name must be required.");
                return false;
            } else if ($scope.LeagueJoinDateTime == '' || $scope.LeagueJoinDateTime == undefined) {
                $scope.errorMessageShow("Draft Date & time must be required.");
                return false;
            } else if (!$scope.winnings && $scope.EntryFee != 0) {
                $scope.errorMessageShow("Please select winnings breakups.");
                return false;
            }
            var CustomizeWinning = [];
            for (var i in $scope.choices) {
                if ($scope.choices[i].NoOfWinners == $scope.SelectedWinners) {
                    CustomizeWinning = $scope.choices[i].Winners;
                    $scope.NoOfWinners = $scope.choices[i].NoOfWinners;
                }
            }
            var $data = {};
            $scope.createContestField.ContestFormat = 'League';
            $scope.createContestField.ContestType = 'Normal';
            $scope.createContestField.GameType = 'Nfl';
            $scope.createContestField.SubGameType = 'ProFootballRegularSeasonOwners';
            $scope.createContestField.EntryType = 'Single';
            $scope.createContestField.ShowJoinedContest = 'Yes';
            $scope.createContestField.CashBonusContribution = 0;
            $scope.createContestField.IsConfirm = 'No';
            $scope.createContestField.IsPaid = ($scope.EntryFee == 0) ? 'No' : 'Yes';
            $scope.createContestField.SeriesGUID = $scope.SeriesGUID;
            $scope.createContestField.InvitePermission = $scope.InvitePermission;
            $scope.createContestField.SessionKey = $localStorage.user_details.SessionKey;
            $scope.createContestField.Privacy = $scope.Privacy;
            $scope.createContestField.ContestDuration = $scope.Duration;
            $scope.createContestField.LeagueType = $scope.LeagueType;
            $scope.createContestField.ContestName = $scope.ContestName;
            $scope.createContestField.ScoringType = $scope.ScoringType;
            $scope.createContestField.NoOfWinners = $scope.NoOfWinners;
            $scope.createContestField.WinningAmount = $scope.WinningAmount;
            $scope.createContestField.EntryFee = $scope.EntryFee;
            $scope.createContestField.WeekStart = $scope.Week;
            if ($scope.Duration == 'Weekly') {
                $scope.createContestField.WeekEnd = $scope.Week;
            }
            $scope.createContestField.IsAutoDraft = ($scope.IsAutoDraft) ? 'Yes' : 'No';
            $scope.createContestField.RosterSize = $scope.RosterSize;
            $scope.createContestField.ContestSize = $scope.ContestSize;
            $scope.createContestField.LeagueJoinDateTime = $scope.dateFormatConverter($scope.LeagueJoinDateTime) + ' ' + $scope.timeFormatConverter($scope.LeagueJoinDateTime);
            $scope.createContestField.AdminPercent = $scope.adminPercent;
            $scope.createContestField.MinimumUserJoined = $scope.MinimumUserJoined;
            if ($scope.winnings) {
                $scope.createContestField.CustomizeWinning = JSON.stringify(CustomizeWinning);
            }
            $scope.createContestField.PrivatePointScoring = JSON.stringify($scope.Points);
            $scope.createContestField.TimeZone = $scope.getTimeZone('offset');
            $data = $scope.createContestField;
            appDB
                .callPostForm('SnakeDrafts/addPrivateContest', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            var Contests = data.Data;
                            $scope.CreateContestForm = {};
                            $scope.submitted = false;
                            $scope.getWalletDetails();
                            // $scope.check_balance_amount(Contests);
                            $scope.successMessageShow(data.Message);
                            setTimeout(function () {
                                window.location.href = base_url + 'myPrivateLeague';
                            }, 1000);
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });

        }
        /**
         * To join Contest
         */

        $scope.check_balance_amount = function (Contests) {
            $rootScope.ContestInfo = {};
            $rootScope.ContestInfo = Contests;
            if (parseInt($scope.profileDetails.TotalCash) < parseInt(Contests.EntryFee)) {
                $scope.openPopup('add_more_money');
            } else {
                $scope.openPopup('joinLeaguePopup');
            }
        }
        $scope.$watch('Duration', function (newValue, oldValue) {
            if (newValue != oldValue) {
                // if (($scope.ContestSize == '6' || $scope.ContestSize == '8') && $scope.Duration == 'SeasonLong' && $scope.ScoringType == 'PointLeague') {
                //     $scope.MinimumUserJoined = 4
                // } else if (($scope.ContestSize == '10' || $scope.ContestSize == '12') && $scope.Duration == 'SeasonLong' && $scope.ScoringType == 'PointLeague') {
                //     $scope.MinimumUserJoined = 8;
                // } else if ($scope.Duration == 'Weekly') {
                //     $scope.MinimumUserJoined = 2;
                // } else if ($scope.Duration == 'Weekly' && $scope.ScoringType == 'H2H') {
                //     $scope.MinimumUserJoined = 2;
                // }
                if (newValue == 'Weekly') {
                    $('#draftDateTime').datetimepicker('setDaysOfWeekDisabled', null);
                    $scope.WeekList = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];
                } else {
                    $('#draftDateTime').datetimepicker('setDaysOfWeekDisabled', [0,6]);
                    $scope.WeekList = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17];
                }
            }
        })
        $scope.$watch('ContestSize', function (newValue, oldValue) {
            $scope.winnings = false;
            if (newValue != oldValue) {
                // if ((newValue == '6' || newValue == '8') && $scope.Duration == 'SeasonLong' && $scope.ScoringType == 'PointLeague') {
                //     $scope.MinimumUserJoined = 4
                // } else if ((newValue == '10' || newValue == '12') && $scope.Duration == 'SeasonLong' && $scope.ScoringType == 'PointLeague') {
                //     $scope.MinimumUserJoined = 8;
                // } else if ($scope.Duration == 'Weekly') {
                //     $scope.MinimumUserJoined = 2;
                // } else if ($scope.Duration == 'Weekly' && $scope.ScoringType == 'H2H') {
                //     $scope.MinimumUserJoined = 2;
                // }
                if (parseInt($scope.EntryFee) > 0) {
                    var TotalWinningAmount = (newValue * $scope.EntryFee);
                    $scope.WinningAmount = TotalWinningAmount - (TotalWinningAmount * $scope.adminPercent) / 100;
                } else {
                    $scope.WinningAmount = 0;
                }
            }
        });

        $scope.$watch('EntryFee', function (newValue, oldValue) {
            $scope.winnings = false;
            if (newValue != oldValue) {
                if (typeof newValue == 'undefined') {
                    $scope.EntryFee = '';
                    return false;
                }
                if (parseInt($scope.ContestSize) > 0) {
                    var TotalWinningAmount = (newValue * $scope.ContestSize);
                    $scope.WinningAmount = TotalWinningAmount - (TotalWinningAmount * $scope.adminPercent) / 100;
                } else {
                    $scope.EntryFee = '';
                    $scope.WinningAmount = 0;
                }
            }
        });

        $scope.$watch('SeriesGUID', function (newValue, oldValue) {
            if (newValue != oldValue) {
                $scope.Week = '';
                if (newValue == '3817655d-0fc1-1920-e548-3e27b76b78cf') {
                    $scope.WeekList = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];
                } else {
                    $scope.WeekList = [18, 19, 20];
                }
            }
        });

        $scope.tab = 'info';
        $scope.activeTab = function (tab) {
            $scope.tab = tab;
        }
        $scope.$watch('winnings', function (newValue, oldValue) {
            if (newValue) {
                var $data = {};
                $data.SessionKey = $localStorage.user_details.SessionKey;
                $data.UserGUID = $localStorage.user_details.UserGUID;
                $data.WinningAmount = $scope.WinningAmount;
                $data.ContestSize = $scope.ContestSize;
                $data.EntryFee = $scope.EntryFee;
                $data.IsPaid = ($scope.EntryFee == 0) ? 'No' : 'Yes';
                appDB
                    .callPostForm('contest/WinningBreakups', $data)
                    .then(
                        function successCallback(data) {
                            if ($scope.checkResponseCode(data)) {
                                $scope.choices = [];
                                for (var i in data.Data) {
                                    if (data.Data[i].NoOfWinners == 1) {
                                        $scope.SelectedWinners = data.Data[i].NoOfWinners + '';
                                    }
                                    if (data.Data[i].NoOfWinners <= 3) {
                                        $scope.choices.push(data.Data[i]);
                                    }
                                }
                            } else {
                                $scope.choices = [];
                            }
                        },
                        function errorCallback(data) {
                            $scope.checkResponseCode(data)
                        });
            }
        });

        /**
         * get roster info
         */
        $scope.rosterInfo = [];
        $scope.getRoaterInfo = function () {
            $scope.rosterInfo = [];
            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey;
            $data.RosterSize = $scope.RosterSize;
            appDB
                .callPostForm('snakeDrafts/RosterDetails', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.rosterInfo = data.Data;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        /**
         * Join contest function
         */
        $scope.Nextdata = true;
        $scope.JoinContest = function () {
            if ($scope.Nextdata) {
                $scope.Nextdata = false;
                var $data = {};
                $data.ContestGUID = $rootScope.ContestInfo.ContestGUID;
                $data.SeriesGUID = $rootScope.ContestInfo.SeriesGUID;
                $data.SessionKey = $localStorage.user_details.SessionKey;
                $data.UserInvitationCode = $rootScope.ContestInfo.UserInvitationCode;
                appDB
                    .callPostForm('SnakeDrafts/join', $data)
                    .then(
                        function successCallback(data) {
                            $scope.Nextdata = true;
                            if ($scope.checkResponseCode(data)) {
                                $scope.closePopup('joinLeaguePopup');
                                $('#joinLeaguePopup').modal('hide')
                                $scope.successMessageShow(data.Message);
                                $scope.getWalletDetails();
                                setTimeout(function () {
                                    window.location.href = base_url + 'myPrivateLeague';
                                }, 1000);
                            }
                        },
                        function errorCallback(data) {
                            $scope.Nextdata = true;
                            $scope.checkResponseCode(data);
                        });
            }
        }
        $scope.currentWeek = '';
        $scope.getCurrentWeek = function () {
            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey;
            $data.Duration = $scope.Duration;
            appDB
                .callPostForm('SnakeDrafts/getCurrentWeek', $data)
                .then(
                    function successCallback(data) {
                        $scope.Nextdata = true;
                        if ($scope.checkResponseCode(data)) {
                            $scope.currentWeek = data.Data.WeekID;
                        }
                    },
                    function errorCallback(data) {
                        $scope.Nextdata = true;
                        $scope.checkResponseCode(data);
                    });
        }
        $scope.SeriesList = [];
        $scope.getSeriesList = function () {
            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey;
            $data.GameSportsType = 'Nfl';
            $data.Params = 'SeriesName,SeriesGUID'
            $data.StatusID = 2;
            appDB
                .callPostForm('admin/matches/getFilterData', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.SeriesList = data.Data.SeiresData;
                            $scope.SeriesList.splice(1)
                            $scope.SeriesGUID = $scope.SeriesList[0].SeriesGUID;
                        }
                    },
                    function errorCallback(data) {
                        $scope.Nextdata = true;
                        $scope.checkResponseCode(data);
                    });
        }
        $scope.cancelButton = function () {
            window.location.href = base_url + 'myPrivateLeague';
        }
        $scope.ContestSizeList = [2, 4, 6, 8, 10, 12];
        $scope.$watch('Week', function (newValue, oldValue) {
            if (newValue != oldValue) {
                if (newValue == 19 && $scope.Duration == 'Weekly') {
                    $scope.ContestSizeList = [2, 4, 6, 8];
                } else if (newValue == 20 && $scope.Duration == 'Weekly') {
                    $scope.ContestSize = '2';
                    $scope.ContestSizeList = [2, 4];
                } else {
                    $scope.ContestSizeList = [2, 4, 6, 8, 10, 12];
                }
            }
        });

    } else {
        window.location.href = base_url;
    }
}]);
