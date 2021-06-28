<?php include('header.php'); ?>
<section class="burger common_bg leaderboard_page" ng-controller="pointLeaderboardController" ng-init="getContest()" ng-cloak>
	<div class="container-fluid">
		<div class="shadow_box px-4 pt-4">
			<div class=" mb-4">
				<h4>Leaderboard</h4>	
				<div class="row">
					<div class="col-sm-5 d-flex align-items-center ">
						<select ng-show="Contest.ContestDuration == 'SeasonLong'" name="week" class="custom-select secondary_select" ng-model="week" ng-change="weekChange()">
							<option ng-repeat="week in Contest.WeekTeamInfo" value="{{week.WeekID}}">Week {{week.WeekID}}</option>
						</select>		
					</div>
					<div class="col-sm-3 d-flex align-items-center ">
						<h6 class="text-center themeClr">{{Contest.SubGameType}}</h6>
					</div>
					<div class="col text-right">
						<a href="Scoreboard?ContestGUID={{ContestGUID}}&SeriesGUID={{SeriesGUID}}&UserGUID={{user_details.UserGUID}}&Week={{week}}" class="btn_sm_primary">Scoreboard </a>
					</div>
				</div>
				<div class="bg_primary p-3 text-white mt-3 leaderboard_header">
					<div class="row align-items-center">
						<div class="col-md-4 d-flex align-items-center">
							<img width="50" src="assets/img/logo.png" alt="" >
							<p class="ml-2 mr-3 mb-0">{{Contest.ContestName}} <strong>({{Contest.IsPaid == 'Yes'?'Private League':'Public Contest'}})</strong></p>
							<span ng-show="Contest.ContestDuration == 'SeasonLong'" class="badge badge-success px-3 py-2 mr-3">Week {{week}}</span>
						</div>
						<div class="col ">
							<div class="row align-items-center">
								<div class="col" >
									<strong>Draft Type </strong>
									<span for="">{{Contest.ContestDuration}} </span>
								</div>
								<div class="col" >
									<strong>Scoring Type </strong>
									<span for="">{{(Contest.ScoringType == 'PointLeague')?'Total Points':''}} </span>
								</div>
								<div class="col" >
									<strong>Entry Fee</strong>
									<span for="">{{moneyFormat(Contest.EntryFee)}}  </span>
								</div>
								<div class="col" >
									<strong>Prize(s)</strong>
									<span for="">
										<div class="payoutParBox">
											<a href="javascript:void(0)" ng-click="showWinningPayout(Contest.CustomizeWinning)"><cite class="fa fa-eye text-white" aria-hidden="true"></cite></a>
											<span>{{moneyFormat(Contest.WinningAmount)}}</span></td>
										</div>	
									</span>
								</div>
								<div class="col">
									<strong for="">Participants</strong>
									<span>{{Contest.TotalJoined}}/{{Contest.ContestSize}}</span>
								</div>
								<div class="col  text-right">
									<strong>Contest Duration</strong>
									<span>Week {{Contest.WeekStart}} - {{Contest.WeekEnd}}</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<table class="table commn_table text-center table-borderless ">
				<thead class="thead-dark">
				    <tr>
				      <th>Rank</th>
				      <th>User</th>
					  <th ng-if="Contest.ContestDuration == 'SeasonLong'">Weekly Points</th>
					  <th>Total Points</th>
					  <th ng-if="Contest.Status == 'Completed' && Contest.WeekEnd == week">Winning Amount</th>
				      <th>Action</th>
				    </tr>
				</thead>
				<tbody>
					<tr ng-repeat="user in JoinedContestUserList">
						<td ng-if="Contest.Status != 'Completed'">{{user.UserRank | RankFormat}}</td>
						<td ng-if="Contest.Status == 'Completed' && user.UserRank != 1">{{user.UserRank | RankFormat}}</td>
						<td ng-if="Contest.Status == 'Completed' && user.UserRank == 1"><img  src="assets/img/winner-free.svg" width='20' alt="champion"><p>Champion</p></td>
						<td>
						<a class="player_profile leaderboard">
							<img ng-src="{{user.ProfilePic}}" on-error-src="assets/img/default.jpg">
							<div>
								<span class="player_name">{{user.UserTeamCode}}</span>		
							</div>
						</a>
						</td>
						<td ng-if="Contest.ContestDuration == 'SeasonLong'">{{(user.WeekTotalPoints)? user.WeekTotalPoints : 0}}</td>
						<td ng-if="Contest.ContestDuration != 'SeasonLong'">{{(user.TotalPoints) ? user.TotalPoints : 0}}</td>
						<td ng-if="Contest.ContestDuration == 'SeasonLong'">{{(user.TotalPointsSeason)? user.TotalPointsSeason :user.TotalPoints}}</td>
						<td ng-if="Contest.Status == 'Completed' && Contest.WeekEnd == week">{{moneyFormat(user.UserWinningAmount)}}</td>
						<td><a href="Scoreboard?ContestGUID={{ContestGUID}}&SeriesGUID={{SeriesGUID}}&UserGUID={{user.UserGUID}}&Week={{week}}" class="themeClr">View Scoreboard</a></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</section>
<?php include('innerFooter.php'); ?>
