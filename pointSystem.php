<?php include('header.php'); ?>
<div class="mainContainer" ng-controller="pointSystemController" ng-cloak ng-init="getPoints()">
    <div class="common_bg py-5">
    	<div class="container-fluid">
            <div class="row">
                <div class="col-md-2 mb-2 d-flex pull-left lobbySidebar">
                    <select ng-model="GamesType" name="GamesType" ng-change="gameTypeSelection(GamesType)" class="custom-select">
                        <option value="NFL">NFL</option>
                        <option value="NBA">NBA</option>
                    </select>
                </div>
                <div class="col-md-12 ">
                    <div class="innerPageWrapr">
                        <div class="innerHeader">
                            <h4>Scoring Rules</h4>
                        </div>
                        <div class="innerPageContent innerPage-Contenttwo">
                            <div class="table-responsive fandom_table">
                            <table class="table table-borderless table_scroll myAccountTable">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Type</th>
                                        <th>Points Type Description</th>
                                        <th>Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr ng-repeat="point in Points">
                                        <td>{{$index+1}}</td>
                                        <td>{{point.PointsTypeShortDescription}}</td>
                                        <td>{{point.PointsTypeDescprition}}</td>
                                        <td>{{point.Points}}</td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('innerFooter.php'); ?>