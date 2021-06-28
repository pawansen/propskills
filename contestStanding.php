<?php include('header.php'); ?>
<div class="mainContainer" ng-controller="contestStandingController" ng-cloak ng-init="getContestStanding()">
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
                            <h4>Contest Standings</h4>
                        </div>
                        <div class="innerPageContent innerPage-Contenttwo">
                            <div class="table-responsive fandom_table">
                            <table class="table table-borderless table_scroll">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Rank</th>
                                        <th>User</th>
                                        <th>Win Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr ng-repeat="stand in Standings | orderBy : propertyName:reverse">
                                        <td>{{$index+1}}</td>
                                        <td class="player_profile leaderboard">
											<img ng-src="{{stand.ProfilePic}}" on-error-src="assets/img/default.jpg">
											<div>{{stand.Username}} </div>
										</td>
                                        <td>{{stand.percentage | number :3}}</td>
                                    </tr>
                                    <tr ng-if="Standings.length == 0">
                                        <td colspan='3'>No Records Available.</td>
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