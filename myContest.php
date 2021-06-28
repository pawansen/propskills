<?php include('header.php');?>
<div class="common_bg">
	<section class="py-4 my_league" ng-controller="myLeagueController" ng-init="JoinedContest(true)" ng-cloak>
		<div class="ml-3 w-25 mb-2 d-flex pull-left lobbySidebar">
			<select ng-model="GamesType" name="GamesType" ng-change="changeGameType(GamesType)" class="custom-select">
				<option value="NFL">NFL</option>
				<option value="NBA">NBA</option>
			</select>
		</div>
		<div class="container-fluid px-3">
			<div class="shadow_box ">
				<div class="px-md-4 p-2">
					<ul class=" nav nav-pills mb-2 btn_tabs" id="nav-tab" role="tablist">
						<li class="nav-item"><a class="nav-item nav-link {{(activeTab == 'upcoming')?'active':''}}" ng-click="gotoTab('upcoming')" id="nav-home-tab" data-toggle="tab" href="javascript:void(0)" role="tab" aria-controls="nav-home" aria-selected="true"> Upcoming </a></li>
						<li class="nav-item"><a class="nav-item nav-link {{(activeTab == 'live')?'active':''}}" ng-click="gotoTab('live')" id="nav-profile-tab" data-toggle="tab" href="javascript:void(0)" role="tab" aria-controls="nav-profile" aria-selected="false"> Live </a></li>
						<li class="nav-item"><a class="nav-item nav-link {{(activeTab == 'completed')?'active':''}}" ng-click="gotoTab('completed')" id="nav-contact-tab" data-toggle="tab" href="javascript:void(0)" role="tab" aria-controls="nav-contact" aria-selected="false"> Completed </a></li>
					</ul>
					<div class="tab-content" id="nav-tabContent">
						<div class="tab-pane fade {{(activeTab == 'upcoming')?'show active':''}}" id="upcoming" role="tabpanel" aria-labelledby="nav-home-tab">
							<div class="table-responsive tableResponsive">
								<table class="upcoming_contest_table table table_scroll" cellspacing="0">
									<thead class="thead-dark">
										<tr>
											<th>CONTEST</th>
											<th>DRAFT TYPE </th>
											<th>BUY IN </th>
											<th>PRIZE </th>
											<th>SCORING</th>
											<th>PARTICIPANTS</th>
											<th>CONTEST DURATION </th>
											<th>DRAFT TIME(EST) </th>
											<th>ACTION </th>
										</tr>
									</thead>
									<tbody scrolly>
										<tr ng-if="UserJoinedContestTotalCount > 0" ng-repeat="contest in contests">
											<td>{{contest.ContestName}} </td>
											<td> {{(contest.GameType == 'Nfl')?'Snake Draft':''}} <br/> ({{(contest.ContestSize == 3)?'8 rounds':'6 rounds'}}) </td>
											<td ng-if="contest.IsPaid == 'Yes'">
												{{moneyFormat(contest.EntryFee)}}
											</td>
											<td ng-if="contest.IsPaid == 'No'">Free</td>
											<td>
												<div class="payoutParBox">
													<a href="javascript:void(0)" ng-click="showWinningPayout(contest.CustomizeWinning)"><cite class="fa fa-eye themeClr" aria-hidden="true"></cite></a>
													<span>{{moneyFormat(contest.WinningAmount)}}</span></td>
												</div>
											</td>
											<td>{{(contest.ScoringType == 'PointLeague')?'Point League':''}}</td>
											<td>{{contest.TotalJoined}}/{{contest.ContestSize}}</td>
											<td>Week {{contest.WeekStart}} - {{contest.WeekEnd}}</td>
											<td><strong> {{contest.LeagueJoinDateTime | myDateFormat}} </strong></td>
											<td ng-if="contest.Status == 'Pending'">
												<a ng-if="contest.AuctionStatus == 'Pending' || contest.AuctionStatus == 'Running'" href="javascript:void(0)" ng-click="EnterDraft(contest)" class="btn_sm_primary text-white"> Enter </a>
												<a ng-if="contest.AuctionStatus == 'Completed'" href="contestInfo?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}" class="btn_sm_primary text-white"> Enter </a>
												<!-- <a href="javascript:void(0)" data-toggle="tooltip" data-placement="top" title="Leave Draft" ng-if="contest.AuctionStatus == 'Pending' && contest.TotalJoined != contest.ContestSize" ng-click="leaveDraft(contest.ContestGUID)" class="leave_contest"><i class="fas fa-times"></i></a> -->
											</td>
											<td ng-if="contest.Status == 'Cancelled'">
												<a href="javascript:void(0)" class="btn_sm_cancel text-white"> {{contest.Status}}</a>
											</td>
										</tr>
										<tr ng-if="UserJoinedContestTotalCount == 0">
											<td colspan="9" class="text-center text-muted">No Upcoming Contest!</td>
										</tr>
									</tbody>
								</table>
							</div>    
						</div>
						<div class="tab-pane fade {{(activeTab == 'live')?'show active':''}}" id="live" role="tabpanel" aria-labelledby="nav-profile-tab">
							<div class="table-responsive tableResponsive">
								<table class="table  table_scroll" cellspacing="0">
								<thead class="thead-dark">
										<tr>
											<th>CONTEST</th>
											<th>DRAFT TYPE </th>
											<th>BUY IN </th>
											<th>PRIZE </th>
											<th>SCORING</th>
											<th>PARTICIPANTS</th>
											<th>CONTEST DURATION </th>
											<th>ACTION </th>
										</tr>
									</thead>
									<tbody scrolly>
										<tr ng-if="UserJoinedContestTotalCount > 0" ng-repeat="contest in contests">
											<td>{{contest.ContestName}} </td>
											<td>{{(contest.GameType == 'Nfl')?'Snake Draft':''}} <br/> ({{(contest.ContestSize == 3)?'8 rounds':'6 rounds'}})</td>
											<td ng-if="contest.IsPaid == 'Yes'">
												{{moneyFormat(contest.EntryFee)}}
											</td>
											<td ng-if="contest.IsPaid == 'No'">Free</td>
											<td>
												<div class="payoutParBox">
													<a href="javascript:void(0)" ng-click="showWinningPayout(contest.CustomizeWinning)"><cite class="fa fa-eye themeClr" aria-hidden="true"></cite></a>
													<span>{{moneyFormat(contest.WinningAmount)}}</span></td>
												</div>
											</td>
											<td>{{(contest.ScoringType == 'PointLeague')?'Point League':''}}</td>
											<td>{{contest.TotalJoined}}/{{contest.ContestSize}}</td>
											<td>Week {{contest.WeekStart}} - {{contest.WeekEnd}}</td>
											<td>
												<a href="contestInfo?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}" class="btn_sm_primary text-white"> Enter </a>
										<!-- 		<a ng-if="contest.ScoringType == 'PointLeague'" href="pointsLeaderboard?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}" class="mt-2 btn_sm_primary text-white"> View Leaderboard </a>	 -->
											</td>
										</tr>
										<tr ng-if="UserJoinedContestTotalCount == 0">
											<td colspan="8" class="text-center text-muted">No Live Contest!</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
						<div class="tab-pane fade {{(activeTab == 'completed')?'show active':''}}" id="completed" role="tabpanel" aria-labelledby="nav-contact-tab">
							<div class="table-responsive tableResponsive">
								<table class="table table_scroll" cellspacing="0">
									<thead class="thead-dark">
										<tr>
											<th>CONTEST</th>
											<th>DRAFT TYPE </th>
											<th>BUY IN </th>
											<th>PRIZE </th>
											<th>SCORING</th>
											<th>PARTICIPANTS</th>
											<th>CONTEST DURATION </th>
											<th>ACTION </th>
										</tr>
									</thead>
									<tbody scrolly>
										<tr ng-if="UserJoinedContestTotalCount > 0" ng-repeat="contest in contests">
											<td>{{contest.ContestName}} </td>
											<td>{{(contest.GameType == 'Nfl')?'Snake Draft':''}} <br/> ({{(contest.ContestSize == 3)?'8 rounds':'6 rounds'}})</td>
											<td ng-if="contest.IsPaid == 'Yes'">
												{{moneyFormat(contest.EntryFee)}}
											</td>
											<td ng-if="contest.IsPaid == 'No'">Free</td>
											<td>
												<div class="payoutParBox">
													<a href="javascript:void(0)" ng-click="showWinningPayout(contest.CustomizeWinning)"><cite class="fa fa-eye themeClr" aria-hidden="true"></cite></a>
													<span>{{moneyFormat(contest.WinningAmount)}}</span></td>
												</div>
											</td>
											<td>{{(contest.ScoringType == 'PointLeague')?'Point League':''}}</td>
											<td>{{contest.TotalJoined}}/{{contest.ContestSize}}</td>
											<td>Week {{contest.WeekStart}} - {{contest.WeekEnd}}</td>

											<td ng-if="contest.Status != 'Cancelled'">
												<a href="contestInfo?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}" class="btn_sm_primary text-white"> Enter </a>
									<!-- 			<a ng-if="contest.ScoringType == 'PointLeague'" href="pointsLeaderboard?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}" class="btn_sm_primary text-white"> View Leaderboard </a>	 -->
											</td>
											<td ng-if="contest.Status == 'Cancelled'">
												<a href="javascript:void(0)" class="btn_sm_cancel text-white"> {{contest.Status}}</a>
											</td>
										</tr>
										<tr ng-if="UserJoinedContestTotalCount == 0">
											<td colspan="8" class="text-center text-muted">No Completed Contest!</td>
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
</div>	
<?php include('innerFooter.php'); ?>
