app.controller('PageController', function ($scope, $http,$timeout){
    $scope.data.pageSize = 15;
    /*----------------*/
    $scope.getFilterData = function ()
    {
        var data = 'SessionKey='+SessionKey+'&Params=SeriesName,SeriesGUID&'+$('#filterPanel form').serialize();
        $http.post(API_URL+'admin/matches/getFilterData', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200 && response.Data){ 
                /* success case */
             $scope.filterData =  response.Data;
             $timeout(function(){
                $("select.chosen-select").chosen({ width: '100%',"disable_search_threshold": 8}).trigger("chosen:updated");
            }, 300);          
         }
     });
    }

    $scope.Weeks = ['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17']; 
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
        var data = 'SessionKey='+SessionKey+'&Params=GameSportsType,IsPowerTeam,TeamID,FantasyPoints,TeamStats,StatusID,TeamIDLive,TeamName,TeamNameShort,TeamFlag&PageNo='+$scope.data.pageNo+'&PageSize='+$scope.data.pageSize+'&'+$('#filterForm').serialize()+'&'+$('#filterForm1').serialize();
        $http.post(API_URL+'sports/getTeams', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200 && response.Data.Records){ /* success case */
                $scope.data.totalRecords = response.Data.TotalRecords;
                for (var i in response.Data.Records) {
                    response.Data.Records[i].IsPowerTeam = (response.Data.Records[i].IsPowerTeam == 'Yes')?true:false;
                    $scope.data.dataList.push(response.Data.Records[i]);
                }
             $scope.data.pageNo++;               
         }else{
            $scope.data.noRecords = true;
        }
        $scope.data.listLoading = false;
        // setTimeout(function(){ tblsort(); }, 1000);
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
    $scope.loadFormEdit = function (Position, TeamGUID)
    {
      
        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE+module+'/edit_form.htm?'+Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL+'sports/getTeam', 'TeamGUID='+TeamGUID+'&Params=ByeWeek,TeamID,StatusID,GameSportsType,IsPowerTeam,TeamIDLive,TeamName,TeamNameShort,TeamFlag,Status', contentType).then(function(response) {
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

    /*load Update form*/
    $scope.loadFormUpdate = function (Position, TeamGUID)
    {
        $scope.data.Position = Position;
        $scope.templateURLUpdate = PATH_TEMPLATE+module+'/update_stats.htm?'+Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL+'sports/getTeam', 'TeamGUID='+TeamGUID+'&Params=TeamID,FantasyPoints,TeamStats,StatusID,TeamIDLive,TeamName,TeamNameShort,TeamFlag,Status', contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data
                $('#update_form').modal({show:true});
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
    $scope.editData = function (Type,GUID,PowerFive)
    {
        $scope.editDataLoading = true;
        if (Type == 'PowerFive'){
            var data = 'SessionKey='+SessionKey+'&TeamGUID='+GUID+'&IsPowerTeam='+(PowerFive ? 'Yes':'No');
        }else{
            if (Type == 'Stats') {
                var data = 'SessionKey='+SessionKey+'&'+$("form[name='update_form']").serialize();
            }else{
                var data = 'SessionKey='+SessionKey+'&'+$("form[name='edit_form']").serialize();
            }
        }
        $http.post(API_URL+'admin/teams/editTeam', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */               
                alertify.success(response.Message);
                $scope.data.dataList[$scope.data.Position] = response.Data;
                $('.modal-header .close').click();
            }else{
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;          
        });
    }

}); 
