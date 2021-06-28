<?php include('header.php');?>
<div class="common_bg common-bg-n" ng-controller="myLeagueController">
	<section class="py-4 my_league"  ng-init="JoinedContest(true)" ng-cloak>
		<div class="container-fluid px-3">
            <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-flex-end">
                            <div class="d-inblock">
                                <a class="btn_sm_primary" href="javascript:void(0)" ng-click="openPopup('JoinContestByCode')" > Enter League Invite Code </a>
                            </div>
                            <div class="d-inblock">
                                <a href="createLeague" class="btn_sm_primary">Create A League</a>
                            </div>
                        </div>
                    </div>
                </div>
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
											<th>LEAGUE</th>
											<th>DRAFT TYPE </th>
											<th>BUY IN </th>
											<th>PRIZE </th>
											<th>SCORING</th>
											<th>PARTICIPANTS</th>
											<th>LEAGUE DURATION </th>
											<th>DRAFT TIME(EST) </th>
											<th>ACTION </th>
										</tr>
									</thead>
									<tbody scrolly>
										<tr ng-if="UserJoinedContestTotalCount > 0" ng-repeat="contest in contests">
											<td>{{contest.ContestName}} </td>
											<td> {{(contest.GameType == 'Nfl')?'Snake Draft':''}} <br/> ({{contest.RosterSize}} Rounds) </td>
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
												<a ng-if="contest.AuctionStatus == 'Pending' && contest.ContestSize != contest.TotalJoined && contest.InvitePermission == 'ByAnyone'" href="inviteFriends?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}" class="btn_sm_secondary text-white"> Invite Friends </a>
												<a ng-if="contest.AuctionStatus == 'Pending' && contest.ContestSize != contest.TotalJoined && contest.InvitePermission == 'ByCreator' && contest.ContestCreaterUserGUID == user_details.UserGUID" href="inviteFriends?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}" class="btn_sm_secondary text-white"> Invite Friends </a>
												<!-- <a href="javascript:void(0)" data-toggle="tooltip" data-placement="top" title="Leave Draft" ng-if="contest.AuctionStatus == 'Pending' && contest.TotalJoined != contest.ContestSize" ng-click="leaveDraft(contest.ContestGUID)" class="leave_contest"><i class="fas fa-times"></i></a> -->
											</td>
											<td ng-if="contest.Status == 'Cancelled'">
												<a href="javascript:void(0)" class="btn_sm_cancel text-white"> {{contest.Status}}</a>
											</td>
										</tr>
										<tr ng-if="UserJoinedContestTotalCount == 0">
											<td colspan="9" class="text-center text-muted">No Upcoming League!</td>
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
											<th>LEAGUE</th>
											<th>DRAFT TYPE </th>
											<th>BUY IN </th>
											<th>PRIZE </th>
											<th>SCORING</th>
											<th>PARTICIPANTS</th>
											<th>LEAGUE DURATION </th>
											<th>DRAFT TIME(EST) </th>
											<th>ACTION </th>
										</tr>
									</thead>
									<tbody scrolly>
										<tr ng-if="UserJoinedContestTotalCount > 0" ng-repeat="contest in contests">
											<td>{{contest.ContestName}} </td>
											<td> {{(contest.GameType == 'Nfl')?'Snake Draft':''}} <br/> ({{contest.RosterSize}} Rounds)</td>
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
											<td>
										<!-- 		<a ng-if="contest.AuctionStatus == 'Completed' && contest.isWeekStarted == 'No' && contest.ContestDuration == 'SeasonLong'" href="contestInfo?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}" class="btn_sm_primary text-white"> Enter </a> -->
													<a ng-if="contest.AuctionStatus == 'Completed'" href="contestInfo?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}" class="btn_sm_primary text-white"> Enter </a>
												<a ng-if="contest.AuctionStatus == 'Pending' || contest.AuctionStatus == 'Running'" href="javascript:void(0)" ng-click="EnterDraft(contest)" class="btn_sm_primary text-white"> Enter </a>
									<!-- 			<a ng-if="contest.ScoringType == 'PointLeague'" href="pointsLeaderboard?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}" class="mt-2 btn_sm_primary text-white"> View Leaderboard </a>	 -->
											</td>
										</tr>
										<tr ng-if="UserJoinedContestTotalCount == 0">
											<td colspan="8" class="text-center text-muted">No Live League!</td>
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
											<th>LEAGUE</th>
											<th>DRAFT TYPE </th>
											<th>BUY IN </th>
											<th>PRIZE </th>
											<th>SCORING</th>
											<th>PARTICIPANTS</th>
											<th>LEAGUE DURATION </th>
											<th>ACTION </th>
										</tr>
									</thead>
									<tbody scrolly>
										<tr ng-if="UserJoinedContestTotalCount > 0" ng-repeat="contest in contests">
											<td>{{contest.ContestName}} </td>
											<td> {{(contest.GameType == 'Nfl')?'Snake Draft':''}} <br/> ({{contest.RosterSize}} Rounds)</td>
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
										<!-- 		<a ng-if="contest.ScoringType == 'PointLeague'" href="pointsLeaderboard?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}" class="btn_sm_primary text-white"> View Leaderboard </a>	 -->
												<a ng-if="contest.Status == 'Completed'" href="contestInfo?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}" class="btn_sm_primary text-white"> Enter </a>

											</td>
										
											<td ng-if="contest.Status == 'Cancelled'">
												<a href="javascript:void(0)" class="btn_sm_cancel text-white"> {{contest.Status}}</a>
											</td>
										</tr>
										<tr ng-if="UserJoinedContestTotalCount == 0">
											<td colspan="8" class="text-center text-muted">No Completed League!</td>
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

    <!-- join contest popup -->
    <div class="modal fade  site_sm_modal " popup-handler id="JoinContestByCode" tabindex="-1" role="dialog" aria-labelledby="modalLabelSmall" aria-hidden="true">
        <div class="modal-dialog "> 
            <!-- Modal content-->
            <div class="modal-content ">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h5 class="modal-title">Join League</h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div  style="width: 100%">
                            <form name="JoinContestForm" ng-submit="checkContestCode(JoinContestForm, ContestInvitationCode)" novalidate="">
                                <div class="leauge-popup">
                                    <div class="form-group">
                                        <input type="text" name="ContestInvitationCode" placeholder="League Invite Code" class="form-control" ng-model="ContestInvitationCode" ng-required="true" style="border-radius: 5px;">
                                        <div ng-show="codeSubmitted && (JoinContestForm.ContestInvitationCode.$error.required || !JoinContestForm.ContestInvitationCode.$valid)" class="form-error text-danger">
                                            *League code is required.
                                        </div>
                                    </div>
                                    <div class="form-group">
                                            <button class="btn btn-green text-white">Join</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- confirmToEnter popup -->
    <div class="modal fade confirmToEnter site_modal modal_dark" popup-handler id="confirmToEnter" tabindex="-1" role="dialog" aria-labelledby="modalLabelSmall" aria-hidden="true">
        <div class="modal-dialog modal-md"> 
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h5 class="modal-title">Confirm Contest</h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="details">
                            <img src="assets/img/logo-only.png" alt="">
                            <h6>{{ContestInfo.ContestName}}</h6>
                            <p>You are about to enter this Contest, and account will be deducted <span class="themeClr">{{moneyFormat(ContestInfo.EntryFee)}}</span></p>
                            <h6>Are you sure you want to enter ?</h6>
                            
                            <div class="mt-5">
                                <button type="button" ng-click="closePopup('confirmToEnter')" class=" btn_secondary cursor_pointer mr-2">NO</button>
                                <button type="button" ng-click="JoinContest()" class="btn_trans_dark cursor_pointer">YES</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>	
<?php include('innerFooter.php'); ?>
