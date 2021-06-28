app.controller('PageController', function ($scope, $http, $timeout) {
    $scope.data.pageSize = 15;
    /*----------------*/
    $scope.getFilterData = function ()
    {
        var data = 'SessionKey=' + SessionKey + '&Params=SeriesName,SeriesGUID&' + $('#filterPanel form').serialize();
        $http.post(API_URL + 'admin/matches/getFilterData', data, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200 && response.Data) {
                /* success case */
                $scope.filterData = response.Data;
                $timeout(function () {
                    $("select.chosen-select").chosen({width: '100%', "disable_search_threshold": 8}).trigger("chosen:updated");
                }, 300);
            }
        });
    }

    /*list*/
    $scope.applyFilter = function ()
    {
        $scope.data = angular.copy($scope.orig); /*copy and reset from original scope*/
        $scope.getList();
    }

    $scope.getMatchDetail = function () {
        $scope.matchDetail = {};
        if (getQueryStringValue('MatchGUID')) {
            var MatchGUID = getQueryStringValue('MatchGUID');
            $scope.AllMatches = false;
        } else {
            var MatchGUID = '';
            $scope.AllMatches = true;
        }
        $http.post(API_URL + 'admin/matches/getMatch', 'MatchGUID=' + MatchGUID + '&Params=SeriesName,MatchType,MatchNo,MatchStartDateTime,TeamNameLocal,TeamNameVisitor,TeamNameShortLocal,TeamNameShortVisitor,TeamFlagLocal,TeamFlagVisitor,MatchLocation&SessionKey=' + SessionKey, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                $scope.matchDetail = response.Data
            }
        });
    }

    /*list append*/
    $scope.getList = function ()
    {
        $scope.getMatchDetail();
        if ($scope.data.listLoading || $scope.data.noRecords)
            return;
        $scope.data.listLoading = true;
        if (getQueryStringValue('MatchGUID')) {
            var MatchGUID = getQueryStringValue('MatchGUID');
            var PlayerRole = 'PlayerRole,PlayerSalary,PlayerSalaryCredit';
        } else {
            var MatchGUID = '';
            var PlayerRole = 'PlayerSalary,PlayerSalaryCredit';
        }
        var data = 'SessionKey=' + SessionKey + '&MatchGUID=' + MatchGUID + '&Params=' + PlayerRole + ',PlayerPic,&PageNo=' + $scope.data.pageNo + '&PageSize=' + $scope.data.pageSize;
        $http.post(API_URL + 'sports/getPlayers', data, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200 && response.Data.Records) { /* success case */
                $scope.data.totalRecords = response.Data.TotalRecords;
                for (var i in response.Data.Records) {
                    $scope.data.dataList.push(response.Data.Records[i]);
                }
                $scope.data.pageNo++;
            } else {
                $scope.data.noRecords = true;
            }
            $scope.data.listLoading = false;
            // setTimeout(function(){ tblsort(); }, 1000);
        });
    }

    /*load add form*/
    $scope.loadFormAdd = function (Position, CategoryGUID)
    {
        $scope.templateURLAdd = PATH_TEMPLATE + module + '/add_form.htm?' + Math.random();
        $('#add_model').modal({show: true});
        $timeout(function () {
            $(".chosen-select").chosen({width: '100%', "disable_search_threshold": 8, "placeholder_text_multiple": "Please Select", }).trigger("chosen:updated");
        }, 200);
    }


    /*load edit form*/
    $scope.loadFormEdit = function (Position, PlayerGUID)
    {
        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE + module + '/edit_form.htm?' + Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL + 'sports/getPlayer', 'PlayerGUID=' + PlayerGUID + '&Params=PlayerRole,PlayerPic,PlayerCountry,PlayerBornPlace,PlayerBattingStyle,PlayerBowlingStyle,MatchType,MatchNo,MatchDateTime,SeriesName,TeamGUID&SessionKey=' + SessionKey, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) {
                /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data
                $('#edit_model').modal({show: true});
                $timeout(function () {
                    $(".chosen-select").chosen({width: '100%', "disable_search_threshold": 8, "placeholder_text_multiple": "Please Select", }).trigger("chosen:updated");
                }, 200);
            }
        });
    }

    /*load delete form*/
    $scope.loadFormDelete = function (Position, CategoryGUID)
    {
        $scope.data.Position = Position;
        $scope.templateURLDelete = PATH_TEMPLATE + module + '/delete_form.htm?' + Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL + 'category/getCategory', 'SessionKey=' + SessionKey + '&CategoryGUID=' + CategoryGUID, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data
                $('#delete_model').modal({show: true});
                $timeout(function () {
                    $(".chosen-select").chosen({width: '100%', "disable_search_threshold": 8, "placeholder_text_multiple": "Please Select", }).trigger("chosen:updated");
                }, 200);
            }
        });
    }

    /*edit data*/
    $scope.editData = function ()
    {
        $scope.editDataLoading = true;
        var data = 'SessionKey=' + SessionKey + '&MatchGUID=' + getQueryStringValue('MatchGUID') + '&' + $("form[name='edit_form']").serialize();
        $http.post(API_URL + 'admin/matches/updatePlayerInfo', data, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                alertify.success(response.Message);
                $scope.data.dataList[$scope.data.Position] = response.Data;
                $('.modal-header .close').click();
            } else {
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;
        });
    }

    /*edit data*/
    $scope.updatePlayerSalary = function (PlayerGUID, MatchGUID, key) {

        $scope.editDataLoading = true;
        var concatString = '';
        if (key.hasOwnProperty('T20Credits')) {
            concatString += '&T20Credits=' + key.T20Credits;
        }
        if (key.hasOwnProperty('T20iCredits')) {
            concatString += '&T20iCredits=' + key.T20iCredits;
        }
        if (key.hasOwnProperty('ODICredits')) {
            concatString += '&ODICredits=' + key.ODICredits;
        }
        if (key.hasOwnProperty('TestCredits')) {
            concatString += '&TestCredits=' + key.TestCredits;
        }
        if (key.hasOwnProperty('PlayerSalaryCredit')) {
            concatString += '&PlayerSalaryCredit=' + key.PlayerSalaryCredit;
        }

        var data = 'SessionKey=' + SessionKey + '&PlayerGUID=' + PlayerGUID + '&MatchGUID=' + MatchGUID + concatString;
        $http.post(API_URL + 'admin/matches/updatePlayerSalary', data, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                alertify.success(response.Message);
                $scope.data.dataList[$scope.data.Position] = response.Data;
                $('.modal-header .close').click();
            } else {
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;
        });
    }
});
