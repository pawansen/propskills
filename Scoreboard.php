<?php include('header.php'); ?>
<section class="burger common_bg" ng-controller="scoreboardController" ng-init="getContest();" ng-cloak>
	<div class="container">
		<div class="shadow_box p-md-5 p-4">
			<p class="font-weight-bold text-right mb-4">Scoring Type : <span class="themeClr">{{(Contest.ScoringType == 'PointLeague')?'Point League':Contest.ScoringType}}</span></p>
			<h6 class="text-center themeClr">Live Scoreboard - Week {{Week}} </h6>
			<div class="alert alert-success" ><strong class="h6">Note :</strong> Player's points will show once their game is over.</div>
			<div class="row box-grid mt-5">
				<div class="col-sm-3" ng-click="getOtherUserScoreCard(user.UserGUID)" ng-repeat="user in JoinedContestUserList">
					<div class="grid-item text-center {{(user.UserGUID == UserGUID)?'active':''}}">
						<div class="profile_wrapr justify-content-center leaderboard">
							<img ng-src="{{user.ProfilePic}}" on-error-src="assets/img/default.jpg" >
							<div>
								<span class="user_name">{{user.UserTeamCode}} </span>
							</div>
						</div>
						<h6 class="mt-2" ng-if="user.UserRank">Rank<span class="ml-2">{{user.UserRank | RankFormat}}</span></h6>
						<h6 class="mt-2">Points<span class="ml-2">{{(user.TotalPoints == '')?'0':user.TotalPoints}}</span></h6>
					</div>
				</div>
			</div>
			<div class="row align-items-center mb-3">
				<div class="col-md-3">
					<select name="user" ng-model="UserGUID" ng-change="getOtherUserScoreCard(UserGUID)" class="custom-select secondary_select">
						<option  ng-repeat="user in JoinedContestUserList" value="{{user.UserGUID}}">{{user.UserTeamCode}}</option>
					</select>
				</div>
			</div>
			<table class="table table-hover table_md score_table" >
				<thead class="thead-dark">
					<tr>
						<th>Player Name</th>
						<th>Role</th>
						<th>Team Name </th>
						<th>Total Points </th>
					</tr>
				</thead>
				<tbody>
					<tr ng-repeat="player in plyersList" >
						<td ng-click="openScoreboardPopup(player)">
							<div class="profile_wrapr leaderboard">
								<img ng-src="{{player.PlayerPic}}" on-error-src="assets/img/default.jpg">
								<div>
									<span class="user_name">{{player.PlayerName}}</span>
								</div>
							</div>		
						</td>
						<td>{{player.PlayerSelectTypeRole}}</td>
						<td>{{player.TeamName}}</td>
						<td><strong class="themeClr">{{player.TotalPoints}}</strong></td>
					</tr>
					<tr ng-if="plyersList.length == 0">
					 <td colspan="4" class="data-not-found">No player found.</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<div class="modal fade rosterPopup site_modal modal_dark" popup-handler id="scoreboardPopup" tabindex="-1" role="dialog" >
        <div class="modal-dialog custom_popup modal-lg"> 
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h6 class="modal-title w-100">Scoreboard</h6>
                </div>
                <div class="modal-body clearfix comon_body ammount_popup">
                    <div class="top-head">
                        <p class="profile_wrapr justify-content-center leaderboard"><img src="{{PlayerInfo.PlayerPic}}">{{PlayerInfo.PlayerName}}</p>
						<p>Team : {{PlayerInfo.TeamName}}</p>
						<p>Role : {{PlayerInfo.PlayerSelectTypeRole}}</p>
                    </div>
                    <div class="form-group mb-0">
                        <ul>
                            <li ng-repeat="ros in rosterInfo">
                                <span>{{ros.FullName}} <span class="themeClr">
                                ({{ros.ShortName}})</span></span>
                                <span>{{ros.Player}}</span>
                            </li>
                        </ul>
                    </div>                        
                </div>
            </div>
        </div>
    </div> 
</section>
<style>
table th{
	vertical-align: top !important; 
}
</style>
<?php include('innerFooter.php'); ?>    
