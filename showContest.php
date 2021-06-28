<?php include('header.php');?>
<div class="common_bg1">
	<section class="py-4 my_league" ng-controller="NBAmyLeagueController" ng-init="matchDetails();JoinedContest(true)" ng-cloak>
         <div class="container-fluid mb-3">
            <div class="creatTeamTop">
                <div class="row">
                    <div class="col-lg-2 col-md-2">
                        <a class="back__btn" href="MyJoinedMatches" ><i class="fa fa-angle-left"></i>Back </a>
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
        <div class="container-fluid px-3">
			<div class="shadow_box ">
				<div class="px-md-4 p-2">
					<div class="tab-content" id="nav-tabContent">
						<div class="tab-pane fade show active" id="completed" role="tabpanel" aria-labelledby="nav-contact-tab">
							<div class="table-responsive tableResponsive">
								<table class="table table_scroll" cellspacing="0">
									<thead class="thead-dark">
										<tr>
											<th>CONTEST</th>
											<th>DRAFT TYPE </th>
											<th>BUY IN </th>
											<th>PRIZE </th>
											<th>LENGTH</th>
											<th>PARTICIPANTS</th>
                                            <th>DRAFT TIME</th>
											<th>ACTION </th>
										</tr>
									</thead>
									<tbody scrolly>
										<tr ng-if="UserJoinedContestTotalCount > 0" ng-repeat="contest in contests">
											<td>{{contest.ContestName}} </td>
											<td>{{(contest.GameType == 'Nba')?'Snake Draft':''}} <br/> ({{contest.RosterSize}} Rounds)</td>
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
											<td>{{(contest.ContestDuration == 'Daily')?'1 Day':'1 Week'}} <br /> ({{MatchDetails.MatchStartDateTime | myDateOnlyFormat}})</td>
											<td>{{contest.TotalJoined}}/{{contest.ContestSize}}</td>
                                            <td>{{contest.LeagueJoinDateTime | myDateFormat}} </td>
											<td>
                                                <a ng-if="contest.Status != 'Pending' && contest.AuctionStatus == 'Completed'"href="liveScoreboard?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}&MatchGUID={{MatchGUID}}" class="btn_sm_primary text-white"> Scoreboard </a>
												<a ng-if="contest.Status == 'Pending' && contest.AuctionStatus == 'Completed'"href="gameInfo?ContestGUID={{contest.ContestGUID}}&SeriesGUID={{contest.SeriesGUID}}&MatchGUID={{MatchGUID}}" class="btn_sm_primary text-white"> Enter </a>
                                                <a ng-if="contest.Status == 'Pending' && contest.AuctionStatus != 'Completed'" ng-click="EnterDraft(contest)" href="javascript:void(0)" class="btn_sm_primary text-white"> Enter </a>
												<a ng-if="contest.Status == 'Cancelled'" href="javascript:void(0)" class="btn_sm_cancel text-white"> {{contest.Status}}</a>
									        </td>
										</tr>
										<tr ng-if="UserJoinedContestTotalCount == 0">
											<td colspan="8" class="text-center text-muted">No Contest Joined!</td>
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
