<?php include('header.php');?>
<div class="common_bg1 leagueCenter">
	<section class="py-4 my_league" ng-controller="NBAmyLeagueController" ng-init="LeagueCenter(true)" ng-cloak>
		<div class="container-fluid px-3">
			<div class="creatTeamTop">
				<div class="row justify-content-center align-items-center">
					<div class="col-offset-6 col-md-6">
						<div class="coman_bg_overlay text-center primarHead mb-3">
							<h3>My Matches</h3>
							<p class="">List of all upcoming, live and past matches joined by you. </p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="container-fluid px-3">
			<div class="w-25 mb-2 d-flex pull-left lobbySidebar">
				<select ng-model="GamesType" name="GamesType" ng-change="changeGameType(GamesType)" class="custom-select">
					<option value="NFL">NFL</option>
					<option value="NBA">NBA</option>
				</select>
			</div>
			<div class="shadow_box ">
                <div class="px-md-4 p-2">
					<ul class=" nav nav-pills mb-2 btn_tabs" id="nav-tab" role="tablist">
						<li class="nav-item"><a class="nav-item nav-link {{(activeTab == 'upcoming')?'active':''}}" ng-click="gotoTab('upcoming')" id="nav-home-tab" data-toggle="tab" href="javascript:void(0)" role="tab" aria-controls="nav-home" aria-selected="true"> Upcoming <span>{{Statics.UpcomingJoinedContest}}</span></a></li>
						<li class="nav-item"><a class="nav-item nav-link {{(activeTab == 'live')?'active':''}}" ng-click="gotoTab('live')" id="nav-profile-tab" data-toggle="tab" href="javascript:void(0)" role="tab" aria-controls="nav-profile" aria-selected="false"> Live <span>{{Statics.LiveJoinedContest}}</span></a></li>
						<li class="nav-item"><a class="nav-item nav-link {{(activeTab == 'completed')?'active':''}}" ng-click="gotoTab('completed')" id="nav-contact-tab" data-toggle="tab" href="javascript:void(0)" role="tab" aria-controls="nav-contact" aria-selected="false"> Completed <span>{{Statics.CompletedJoinedContest}}</span></a></li>
					</ul>
					<div class="tab-content" id="nav-tabContent">
						<div class="tab-pane fade {{(activeTab == 'upcoming')?'show active':''}}" id="upcoming" role="tabpanel" aria-labelledby="nav-home-tab">
							<div class="table-responsive tableResponsive">
								<table class="upcoming_contest_table table table_scroll" cellspacing="0">
									<thead class="thead-dark">
										<tr>
											<th>MATCH</th>
											<th>LEAGUE CLOSE IN </th>
											<th>LEAGUE JOINED </th>
											<th>ACTION </th>
										</tr>
									</thead>
									<tbody scrolly>
										<tr ng-if="MatchesTotalCount > 0" ng-repeat="match in MatchesList">
											<td>
												<div class="content float-left">
													<img class="leagueCenterMatchImg" ng-src="{{(match.TeamFlagLocal)?match.TeamFlagLocal:'assets/img/default-team-logo.png'}}" on-error-src="assets/img/default-team-logo.png" alt="{{match.TeamNameShortLocal}}"> 
														<strong>{{match.TeamNameShortLocal}} Vs {{match.TeamNameShortVisitor}}</strong>
													<img class="leagueCenterMatchImg" ng-src="{{(match.TeamFlagVisitor)?match.TeamFlagVisitor:'assets/img/default-team-logo.png'}}" on-error-src="assets/img/default-team-logo.png" alt="{{match.TeamNameShortVisitor}}"> 
													<div class="res_font text-center"><a target="_top">{{match.SeriesName}}</a></div>
												</div>
											 </td>
											 <td> 
											 	<p id="demo1" timer-text="{{match.MatchStartDateTimeUTC}}" timer-data="{{match.MatchStartDateTimeUTC}}" match-status="{{match.Status}}" ng-bind-html="clock | trustAsHtml" class="ng-binding"></p></td>
											 <td>{{match.MyTotalJoinedContest}}</td>
											<td >
												<a href="showContest?MatchGUID={{match.MatchGUID}}" class="btn_sm_primary text-white"> Enter </a>
											</td>
										</tr>
										<tr ng-if="MatchesTotalCount == 0">
											<td colspan="4" class="text-center text-muted">No Matches Joined yet!</td>
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
											<th>MATCH</th>
											<th>MATCH STATUS  </th>
											<th>LEAGUE JOINED </th>
											<th>ACTION </th>
										</tr>
									</thead>
									<tbody scrolly>
										<tr ng-if="MatchesTotalCount > 0" ng-repeat="match in MatchesList">
											<td>
												<div class="content float-left">
													<img class="leagueCenterMatchImg" ng-src="{{(match.TeamFlagLocal)?match.TeamFlagLocal:'assets/img/default-team-logo.png'}}" on-error-src="assets/img/default-team-logo.png" alt="{{match.TeamNameShortLocal}}"> 
														<strong>{{match.TeamNameShortLocal}} Vs {{match.TeamNameShortVisitor}}</strong>
													<img class="leagueCenterMatchImg" ng-src="{{(match.TeamFlagVisitor)?match.TeamFlagVisitor:'assets/img/default-team-logo.png'}}" on-error-src="assets/img/default-team-logo.png" alt="{{match.TeamNameShortVisitor}}"> 
													<div class="res_font text-center"><a target="_top">{{match.SeriesName}}</a><p>{{Match.MatchStartDateTime | date : 'yyyy-MM-dd' }}</p></div>
												</div>
											</td>
											<td> 
											   {{(Contests.StatusID == 10) ? 'Reviewing' : 'In Progress'}}  	
											</td>
											<td>{{match.MyTotalJoinedContest}}</td>
											<td>
												<a href="showContest?MatchGUID={{match.MatchGUID}}" class="btn_sm_primary text-white"> View Contests</a>
											</td>
										</tr>
										<tr ng-if="MatchesTotalCount == 0">
											<td colspan="4" class="text-center text-muted">No Live Matches!</td>
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
											<th>MATCH</th>
											<th>LEAGUE DATE </th>
											<th>LEAGUE JOINED </th>
											<th class="text-center">WON</th>
											<th>ACTION </th>
										</tr>
									</thead>
									<tbody scrolly>
										<tr ng-if="MatchesTotalCount > 0" ng-repeat="match in MatchesList">
											<td>
												<div class="content float-left">
													<img class="leagueCenterMatchImg" ng-src="{{(match.TeamFlagLocal)?match.TeamFlagLocal:'assets/img/default-team-logo.png'}}" on-error-src="assets/img/default-team-logo.png" alt="{{match.TeamNameShortLocal}}"> 
														<strong>{{match.TeamNameShortLocal}} Vs {{match.TeamNameShortVisitor}}</strong>
													<img class="leagueCenterMatchImg" ng-src="{{(match.TeamFlagVisitor)?match.TeamFlagVisitor:'assets/img/default-team-logo.png'}}" on-error-src="assets/img/default-team-logo.png" alt="{{match.TeamNameShortVisitor}}"> 
													<div class="res_font text-center"><a target="_top">{{match.SeriesName}}</a></div>
												</div>
											 </td>
											 <td> 
											 	{{match.MatchStartDateTime | myDateFormat }} 	
											</td>
											 <td>{{match.MyTotalJoinedContest}}</td>
											 <td class="text-center">{{moneyFormat(match.TotalUserWinning)}}</td>
											<td>
												<a href="showContest?MatchGUID={{match.MatchGUID}}" class="btn_sm_primary text-white"> View Contests</a>
											</td>
										</tr>
										<tr ng-if="MatchesTotalCount == 0">
											<td colspan="5" class="text-center text-muted">No Completed Matches!</td>
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
