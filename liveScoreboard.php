<?php include('header.php'); ?>
<section class="common_bg1 leaderboard_page" ng-controller="pointLeaderboardController" ng-init="getContest();matchDetails();getPoints();" ng-cloak>
	<div class="container-fluid mb-3">
		<div class="creatTeamTop">
			<div class="row">
				<div class="col-lg-2 col-md-2">
					<a class="back__btn" href="javascript:void(0)" ng-click="Back()" ><i class="fa fa-angle-left"></i>Back </a>
				</div>
				<div class="col-lg-8 col-md-10 coman_bg_overlay p-4">
					<div class="row justify-content-center align-items-center">
						<div class="col-sm-6 border-right">
							<div class="matchCenterbox ">
								<div class="matchCenterHeader">
									<ul class="d-flex justify-content-center">
										<li>
											<div class="teamLogo">
												<img ng-src="{{(MatchDetails.TeamFlagLocal)?MatchDetails.TeamFlagLocal:'assets/img/default-team-logo.png'}}" on-error-src="assets/img/default-team-logo.png" alt="{{MatchDetails.TeamNameShortLocal}}">
											</div>
										</li>
										<li>{{MatchDetails.TeamNameShortLocal}}
											<span>Vs</span> {{MatchDetails.TeamNameShortVisitor}}</li>
										<li>
											<div class="teamLogo">
												<img ng-src="{{(MatchDetails.TeamFlagVisitor)?MatchDetails.TeamFlagVisitor:'assets/img/default-team-logo.png'}}" on-error-src="assets/img/default-team-logo.png" alt="{{MatchDetails.TeamNameShortVisitor}}">
											</div>
										</li>
									</ul>
								</div>
								<div class="matchCenterBody">
									<p>{{MatchDetails.SeriesName}}
										<br> {{MatchDetails.MatchType}} | {{MatchDetails.MatchNo}}</p>
									<!-- <div class="location">
										<i class="fa fa-map-marker"></i> {{MatchDetails.MatchLocation}}
									</div> -->
								</div>
							</div>
						</div>
						<div class="col-sm-6 ">
							<div class="matchCenterbox" ng-if="MatchDetails.Status!='Pending'">
								<div class=" matchFeed">
									<h5 class="mb-2">Match Feed</h5>
									<div class="matchFeedList pl-3">
										<ul class="row">
											<li class="col-7 pr-0 text-left">Match Status</li>
											<li class="col-5 pl-0"><span ng-class="{Pending:'text-danger', Live:'text-success',Cancelled:'text-danger',Completed:'text-success'}[MatchDetails.Status]">{{MatchDetails.Status}}</span></li>
										</ul>
										<ul class="row">
											<li class="col-7 pr-0 d-flex">{{MatchDetails.TeamNameLocal}}</li>
											<li class="col-5 pl-0 numeric " ng-if="MatchDetails.MatchScoreDetails.localteam.total_score">{{MatchDetails.MatchScoreDetails.localteam.total_score}} </li>
										</ul>
										<ul class="row">
											<li class="col-7 pr-0 d-flex">{{MatchDetails.TeamNameVisitor}}</li>
											<li class="col-5 pl-0 numeric" ng-if=" MatchDetails.MatchScoreDetails.visitorteam.total_score">{{MatchDetails.MatchScoreDetails.visitorteam.total_score}} </li>
										</ul>
									</div>
								</div>
							</div>
							<div class="matchCenterbox" ng-if="MatchDetails.Status =='Pending'">
								<div class="coman_bg_overlay">
									<h4>Match Start in</h4>
									<div class="timer" ng-if="MatchDetails.MatchStartDateTimeUTC">
										<p id="demo1" timer-text="{{MatchDetails.MatchStartDateTimeUTC}}" timer-data="{{MatchDetails.MatchStartDateTimeUTC}}" match-status="{{MatchDetails.Status}}" ng-bind-html="clock | trustAsHtml"></p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="container-fluid">
		<div class="shadow_box px-4 pt-4">
			<div class=" mb-4">
				<h4>Leaderboard</h4>	
				<div class="row">
					<div class="col-sm-3 d-flex align-items-center ">
						<h6 class="text-center themeClr">NBA (Regular Season)</h6>
					</div>
					<div class="col text-right">
						<a href="javascript:void(0)" ng-click="fullScorecard()" class="btn_sm_primary">Full Scoreboard </a>
					</div>
				</div>
				<div class="bg_primary p-3 text-white mt-3 leaderboard_header">
					<div class="row align-items-center">
						<div class="col-md-4 d-flex align-items-center">
							<img width="50" src="assets/img/logo.png" alt="" >
							<h5 class="ml-2 mr-3 mb-0">{{Contest.ContestName}} 	</h5>
						</div>
						<div class="col ">
							<div class="row align-items-center">
								<div class="col" >
									<strong>Length </strong>
									<span for="">{{(Contest.ContestDuration == 'Daily')?'1 Day':'1 Week'}} </span>
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
					  <th>Total Points</th>
					  <th ng-if="Contest.Status == 'Completed'">Winning Amount</th>
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
						<td>{{(user.TotalPoints) ? user.TotalPoints : 0}}</td>
						<td ng-if="Contest.Status == 'Completed'">{{moneyFormat(user.UserWinningAmount)}}</td>
						<td><a href="Javascript:void(0)" ng-click="showScoreboard(user.UserTeamPlayers)" class="themeClr">View Scoreboard</a></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Player score board -->
	<div class="modal fade site_sm_modal" popup-handler id="showPlayerScoreboard" tabindex="-1" role="dialog" aria-labelledby="modalLabelSmall" aria-hidden="true">
		<div class="modal-dialog modal-lg"> 
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">×</button>
					<h5 class="modal-title">Scoreboard</h5>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="w-100">
							<div class="text-danger ml-2 mb-2">
								Points will show once player's game is over                                </div>
							</div>
							<table class="table ">
								<thead class="thead-dark">
									<tr>
										<th>Name </th>
										<th ng-repeat="point in Points">{{point.PointsTypeShortDescription}}</th>
										<th>Total Points</th>
									</tr>
								</thead>
								<tbody>
									<tr ng-repeat="player in userPlayers" >
										<td class="player_profile leaderboard">
											<img ng-src="{{player.PlayerPic}}" on-error-src="assets/img/default.jpg">
											<div>{{player.PlayerName}} ({{player.PlayerSelectTypeRole}}) </div>
										</td>
										<td ng-if="player.PointsDataPrivate == ''" ng-repeat="point in Points">0</td>
										<td ng-if="player.PointsDataPrivate" ng-repeat="point in player.PointsDataPrivate">{{point.CalculatedPoints}}</td>
										<td>{{player.TotalPoints}}</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<?php include('innerFooter.php'); ?>
<style>
.comman_table tbody{
	max-height: 500px !important;
}
</style>