app.controller('PageController', function ($scope, $http,$timeout){
    $scope.data.pageSize = 15;
    /*----------------*/
     /*list*/
    $scope.applyFilter = function() {
        $scope.data = angular.copy($scope.orig); /*copy and reset from original scope*/
        $scope.getList();
    }

    $scope.TransactionMode = 'All';
    /*list append*/
    $scope.getList = function ()
    {
        if ($scope.data.listLoading || $scope.data.noRecords) return;
        $scope.data.listLoading = true;
        var data = 'SessionKey='+SessionKey+'&Params=Amount,Email,PaymentGateway,Status,EntryDate,FirstName,MediaBANK&PageNo=' + $scope.data.pageNo + '&PageSize=' + $scope.data.pageSize + '&OrderBy=EntryDate&Sequence=DESC&' + $('#filterForm1').serialize()+'&'+$('#filterForm').serialize();
        $http.post(API_URL+'admin/users/getWithdrawals', data, contentType).then(function(response) {
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
        });
    }

    /*load edit form*/
    $scope.loadFormEdit = function(Position, WithdrawalID) {
        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE + module + '/edit_form.htm?' + Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL + 'admin/users/getWithdrawal', 'SessionKey=' + SessionKey + '&WithdrawalID=' + WithdrawalID + '&Params=Params=Amount,PaymentGateway,Status,EntryDate,FirstName,Email,PhoneNumber,ProfilePic,MediaBANK,UserID', contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data.Records[0];
                
                $('#edit_model').modal({
                    show: true
                });
                $timeout(function() {
                    $(".chosen-select").chosen({
                        width: '100%',
                        "disable_search_threshold": 8,
                        "placeholder_text_multiple": "Please Select",
                    }).trigger("chosen:updated");
                }, 200);
            }
        });

    }

    /*edit data*/
    $scope.editData = function() {
        $scope.editDataLoading = true;
        var data = 'SessionKey=' + SessionKey + '&Params=WithdrawalID,Amount,PaymentGateway,Status,EntryDate,FirstName,Email,PhoneNumber&' + $('#edit_form').serialize();
        $http.post(API_URL + 'admin/users/changeWithdrawalStatus', data, contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                alertify.success(response.Message);
                $scope.data.dataList[$scope.data.Position].Status = response.Data.Status;
            } else {
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;
        });
    }
}); 
