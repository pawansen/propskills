app.controller('PageController', function ($scope, $http,$timeout){
    $scope.data.pageSize = 15;
    // $scope.data.ParentCategoryGUID = ParentCategoryGUID;
    /*----------------*/
    // $scope.getFilterData = function ()
    // {
    //     var data = 'SessionKey='+SessionKey+'&SeriesGUID='+SeriesGUID+'&'+$('#filterPanel form').serialize();
    //     $http.post(API_URL+'admin/series/getFilterData', data, contentType).then(function(response) {
    //         var response = response.data;
    //         if(response.ResponseCode==200 && response.Data){ /* success case */
    //          $scope.filterData =  response.Data;
    //          $timeout(function(){
    //             $("select.chosen-select").chosen({ width: '100%',"disable_search_threshold": 8}).trigger("chosen:updated");
    //         }, 300);          
    //      }
    //  });
    // }


    /*list*/
    $scope.applyFilter = function ()
    {
        $scope.data = angular.copy($scope.orig); /*copy and reset from original scope*/
        $scope.getList();
    }


    /*list append*/
    $scope.getList = function ()
    {
        if ($scope.data.listLoading || $scope.data.noRecords) return;
        $scope.data.listLoading = true;
        var data = 'SessionKey='+SessionKey+'&Params=SeriesName,SeriesGUID,StatusID,Status,SeriesStartDate,SeriesEndDate,AuctionDraftIsPlayed,GameSportsType,TotalMatches&PageNo=' + $scope.data.pageNo + '&PageSize=' + $scope.data.pageSize+'&'+$('#filterForm').serialize()+'&'+$('#filterForm1').serialize();
        $http.post(API_URL+'sports/getSeries', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200 && response.Data.Records){ /* success case */
                $scope.data.totalRecords = response.Data.TotalRecords;
                for (var i in response.Data.Records) {
                 $scope.data.dataList.push(response.Data.Records[i]);
                }
             $scope.data.pageNo++;               
            }else{
                $scope.data.noRecords = true;
            }
            $scope.data.listLoading = false;
        });
    }


    /*load add form*/
    $scope.loadFormAdd = function (Position, CategoryGUID)
    {
        $scope.templateURLAdd = PATH_TEMPLATE+module+'/add_form.htm?'+Math.random();
        $('#add_model').modal({show:true});
        $timeout(function(){            
           $(".chosen-select").chosen({ width: '100%',"disable_search_threshold": 8 ,"placeholder_text_multiple": "Please Select",}).trigger("chosen:updated");
       }, 200);
    }



    /*load edit form*/
    $scope.loadFormEdit = function (Position, SeriesGUID)
    {
      
        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE+module+'/edit_form.htm?'+Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL+'admin/series/getSeriesDetails','SeriesGUID='+SeriesGUID+'&Params=SeriesName,SeriesGUID,AuctionDraftIsPlayed,Status&SessionKey='+SessionKey, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data
                $('#edit_model').modal({show:true});
                $timeout(function(){            
                   $(".chosen-select").chosen({ width: '100%',"disable_search_threshold": 8 ,"placeholder_text_multiple": "Please Select",}).trigger("chosen:updated");
               }, 200);
            }
        });
    }

    /*load delete form*/
    $scope.loadFormDelete = function (Position, CategoryGUID)
    {
        $scope.data.Position = Position;
        $scope.templateURLDelete = PATH_TEMPLATE+module+'/delete_form.htm?'+Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL+'category/getCategory', 'SessionKey='+SessionKey+'&CategoryGUID='+CategoryGUID, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data
                $('#delete_model').modal({show:true});
                $timeout(function(){            
                   $(".chosen-select").chosen({ width: '100%',"disable_search_threshold": 8 ,"placeholder_text_multiple": "Please Select",}).trigger("chosen:updated");
               }, 200);
            }
        });
    }

     /*edit data*/
    $scope.editData = function() {
        $scope.editDataLoading = true;
        var data = 'SessionKey=' + SessionKey + '&' + $('#edit_form').serialize();
        $http.post(API_URL + 'admin/series/changeStatus', data, contentType).then(function(response) {
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


